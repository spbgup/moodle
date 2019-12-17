<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Class file for the block_ajax_marking_query_factory class
 *
 * @package    block
 * @subpackage ajax_marking
 * @copyright  2011 Matt Gibson
 * @author     Matt Gibson {@link http://moodle.org/user/view.php?id=81450}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * This is to build a query based on the parameters passed into the constructor. Without parameters,
 * the query should return all unmarked items across all of the site.
 */
class block_ajax_marking_query_factory {

    /**
     * This will take the parameters which were supplied by the clicked node and its ancestors and
     * construct an SQL query to get the relevant work from the database. It can be used by the
     * grading popups in cases where there are multiple items e.g. multiple attempts at a quiz, but
     * it is mostly for making the queries used to get the next set of nodes.
     *
     * @param array $filters
     * @param block_ajax_marking_module_base $moduleclass e.g. quiz, assignment
     * @return block_ajax_marking_query_base
     */
    public static function get_filtered_module_query($filters, $moduleclass) {

        // Might be a config nodes query, in which case, we want to leave off the unmarked work
        // stuff and make sure we add the display select stuff to this query instead of leaving
        // it for the outer displayquery that the unmarked work needs
        $confignodes = isset($filters['config']) ? true : false;
        if ($confignodes) {
            $query = new block_ajax_marking_query_base($moduleclass);
            $query->add_from(array(
                    'table' => $moduleclass->get_module_name(),
                    'alias' => 'moduletable',
            ));
        } else {
            $query = $moduleclass->query_factory($confignodes);

            // Apply all the standard filters. These only make sense when there's unmarked work
            self::apply_sql_enrolled_students($query);
            self::apply_sql_visible($query);
            self::apply_sql_display_settings($query);

        }

        // Apply any filters specific to this request. Next node type one should be a GROUP BY,
        // the rest need to be WHEREs i.e. starting from the requested nodes, and moving back up
        // the tree e.g. 'student', 'assessment', 'course'
        foreach ($filters as $name => $value) {
            if ($name == 'nextnodefilter') {
                $filterfunctionname = 'apply_'.$value.'_filter';
                // The new node filter is in the form 'nextnodefilter => 'functionname', rather
                // than 'filtername' => <rowid> We want to pass the name of the filter in with
                // an empty value, so we set the value here.
                $value = false;
                $operation = $confignodes ? 'configselect' : 'countselect';
            } else {
                $filterfunctionname = 'apply_'.$name.'_filter';
                $operation = 'where';
            }

            // Find the function. Core ones are part of this class, others will be methods of
            // the module object.
            // If we are filtering by a specific module, look there first
            if (method_exists($moduleclass, $filterfunctionname)) {
                // All core filters are methods of query_base and module specific ones will be
                // methods of the module-specific subclass. If we have one of these, it will
                // always be accompanied by a coursemoduleid, so will only be called on the
                // relevant module query and not the rest
                $moduleclass->$filterfunctionname($query, $operation, $value);
            } else if (method_exists(__CLASS__, $filterfunctionname)) {
                // config tree needs to have select stuff that doesn't mention sub. Like for the
                // outer wrappers of the normal query for the unmarked work nodes
                self::$filterfunctionname($query, $operation, $value);
            }
        }

        self::apply_sql_owncourses($query);

        return $query;
    }

    /**
     * This is to build whatever query is needed in order to return the requested nodes. It may be
     * necessary to compose this query from quite a few different pieces. Without filters, this
     * should return all unmarked work across the whole site for this teacher.
     *
     * The main union query structure involves two levels of nesting: First, all modules provide a
     * query that counts the unmarked work and leaves us with
     *
     * In:
     * - filters as an array. course, coursemodule, student, others (as defined by module base
     *   classes
     *
     * Issues:
     * - maintainability: easy to add and subtract query filters
     * - readability: this is very complex
     *
     * @global moodle_database $DB
     * @param array $filters list of functions to run on the query. Methods of this or the module
     * class
     * @return array
     */
    public static function get_unmarked_nodes($filters = array()) {

        global $DB;

        // if not a union query, we will want to remember which module we are narrowed down to so we
        // can apply the postprocessing hook later

        $modulequeries = array();
        $moduleid = false;
        $moduleclasses = block_ajax_marking_get_module_classes();
        if (!$moduleclasses) {
            return array(); // No nodes
        }

        $filternames = array_keys($filters);
        $havecoursemodulefilter = in_array('coursemoduleid', $filternames);
        $makingcoursemodulenodes = ($filters['nextnodefilter'] === 'coursemoduleid');

        // If one of the filters is coursemodule, then we want to avoid querying all of the module
        // tables and just stick to the one with that coursemodule. If not, we do a UNION of all
        // the modules
        if ($havecoursemodulefilter) {
            // Get the right module id
            $moduleid = $DB->get_field('course_modules', 'module',
                                       array('id' => $filters['coursemoduleid']));
        }

        foreach ($moduleclasses as $modname => $moduleclass) {
            /** @var $moduleclass block_ajax_marking_module_base */

            if ($moduleid && $moduleclass->get_module_id() !== $moduleid) {
                // We don't want this one as we're filtering by a single coursemodule
                continue;
            }

            $modulequeries[$modname] = self::get_filtered_module_query($filters, $moduleclass);

            if ($moduleid) {
                break; // No need to carry on once we've got the only one we need
            }
        }

        // Make an array of queries to join with UNION ALL. This will get us the counts for each
        // module. Implode separate subqueries with UNION ALL. Must use ALL to cope with duplicate
        // rows with same counts and ids across the UNION. Doing it this way keeps the other items
        // needing to be placed into the SELECT  out of the way of the GROUP BY bit, which makes
        // Oracle bork up.
        $unionallmodulesqueries = array();
        $unionallmodulesparams = array();
        foreach ($modulequeries as $query) {
            /** @var $query block_ajax_marking_query_base */
            $unionallmodulesqueries[] = $query->to_string();
            $unionallmodulesparams = array_merge($unionallmodulesparams, $query->get_params());
        }
        $unionallmodulesqueries = implode("\n\n UNION ALL \n\n", $unionallmodulesqueries);
        // We want the bare minimum here. The idea is to avoid problems with GROUP BY ambiguity,
        // so we just get the counts as well as the node ids
        $countwrapperselect = "moduleunion.{$filters['nextnodefilter']} AS id,
                   SUM(moduleunion.count) AS count ";
        $countwrappergroupby = "moduleunion.".$filters['nextnodefilter'];

        if ($havecoursemodulefilter || $makingcoursemodulenodes) {
            // Needed to access the correct javascript so we can open the correct popup, so
            // we include the name of the module
            $countwrapperselect .=  ", moduleunion.modulename AS modulename ";
            $countwrappergroupby .=  ", moduleunion.modulename ";
        }
        // This (called countwrapper elsewhere) gets us the ids of the nodes we want, plus counts
        // of how many bits of unmarked work there are in a way that works for fussy DBs like
        // Oracle.
        $countwrappersubquery = "
            SELECT {$countwrapperselect}
              FROM ({$unionallmodulesqueries}) moduleunion
          GROUP BY {$countwrappergroupby}
          "; // Newlines so the debug query reads better

        // The outermost query just joins the already counted nodes with their display data e.g. we
        // already have a count for each courseid, now we want course name and course description
        // but we don't do this in the counting bit so as to avoid weird issues with group by on
        // oracle
        $displayquery = new block_ajax_marking_query_base();
        $displayquery->add_select(array(
                'table'    => 'combinedmodulesubquery',
                'column'   => 'id',
                'alias'    => $filters['nextnodefilter']));
        $displayquery->add_select(array(
                'table'    => 'combinedmodulesubquery',
                'column'   => 'count'));
        if ($havecoursemodulefilter) { // Need to have this pass through in case we have a mixture
            $displayquery->add_select(array(
                'table'    => 'combinedmodulesubquery',
                'column'   => 'modulename'));
        }
        $displayquery->add_from(array(
                'table'    => $countwrappersubquery,
                'alias'    => 'combinedmodulesubquery',
                'subquery' => true));
        $displayquery->add_params($unionallmodulesparams, false);

        // Now we need to run the final query through the filter for the nextnodetype so that the
        // rest of the necessary SELECT columns can be added, along with the JOIN to get them
        $nextnodefilterfunction = 'apply_'.$filters['nextnodefilter'].'_filter';
        if ($moduleid && method_exists($moduleclass, $nextnodefilterfunction)) {
            // If we stopped when we got to the class we wanted in the loop above, $moduleclass
            // will still be assigned to the one we want
            $moduleclass->$nextnodefilterfunction($displayquery, 'displayselect'); // allow override
        } else if (method_exists(__CLASS__, $nextnodefilterfunction)) {
            self::$nextnodefilterfunction($displayquery, 'displayselect');
        } else {
            // Problem - we have nothing to provide node display data.
            $text = 'No final filter applied for nextnodetype! ('.$nextnodefilterfunction.')';
            throw new coding_exception($text);
        }

        // This is just for copying and pasting from the paused debugger into a DB GUI
        $debugquery = block_ajax_marking_debuggable_query($displayquery);

        $nodes = $displayquery->execute();
        if ($moduleid) {
            // this does e.g. allowing the forum module to tweak the name depending on forum type
            $moduleclass->postprocess_nodes_hook($nodes, $filters);
        }
        return $nodes;

    }

    /**
     * Applies the filter needed for course nodes or their descendants
     *
     * @param block_ajax_marking_query_base $query
     * @param bool|string $operation If we are gluing many module queries together, we will need to
     *                    run a wrapper query that will select from the UNIONed subquery
     * @param int $courseid Optional. Will apply SELECT and GROUP BY for nodes if missing
     * @return void|string
     */
    private static function apply_courseid_filter($query, $operation, $courseid = 0) {
        // Apply SELECT clauses for course nodes

        $selects = array();
        $tablename = 'combinedmodulesubquery.id';

        switch ($operation) {

            case 'where':
                // This is for when a courseid node is an ancestor of the node that has been
                // selected, so we just do a where
                $query->add_where(array(
                        'type' => 'AND',
                        'condition' => 'moduletable.course = :'.$query->prefix_param('courseid')));
                $query->add_param('courseid', $courseid);
                break;

            case 'countselect':
                // This is for the module queries when we are making course nodes
                $selects = array(
                    array(
                        'table'    => 'moduletable',
                        'column'   => 'course',
                        'alias'    => 'courseid'),
                    array(
                        'table'    => 'sub',
                        'column'   => 'id',
                        'alias'    => 'count',
                        'function' => 'COUNT',
                        'distinct' => true));
                break;

            case 'configselect':
                $query->add_select(array(
                        'table'    => 'moduletable',
                        'column'   => 'course',
                        'alias'    => 'courseid'));
                // Without the wrapped queries, we just join straight to the modules
                $tablename = 'moduletable.course';

            case 'displayselect':
                // This is for the displayquery when we are making course nodes
                $query->add_from(array(
                        'join' => 'INNER JOIN',
                        'table' =>'course',
                        'alias' => 'course',
                        'on' => $tablename.' = course.id'
                ));
                $selects = array(
                    array(
                        'table'    => 'course',
                        'column'   => 'shortname',
                        'alias'    => 'name'),
                    array(
                        'table'    => 'course',
                        'column'   => 'fullname',
                        'alias'    => 'tooltip'));
                break;
        }

        foreach ($selects as $select) {
            $query->add_select($select);
        }
    }

    /**
     *
     * @param block_ajax_marking_query_base $query
     * @param $operation
     * @param bool|int $groupid
     * @return void
     */
    private static function apply_groupid_filter ($query, $operation, $groupid = false) {

        if (!$groupid) {
            $selects = array(array(
                    'table'    => 'groups',
                    'column'   => 'id',
                    'alias'    => 'groupid'),
                array(
                    'table'    => 'sub',
                    'column'   => 'id',
                    'alias'    => 'count',
                    'function' => 'COUNT',
                    'distinct' => true),
                array(
                    'table'    => 'groups',
                    'column'   => 'name',
                    'alias'    => 'name'),
                array(
                    'table'    => 'groups',
                    'column'   => 'description',
                    'alias'    => 'tooltip')
            );
            foreach ($selects as $select) {
                $query->add_select($select);
            }
        } else {
            // Apply WHERE clause
            $query->add_where(array(
                    'type' => 'AND',
                    'condition' => 'groups.id = :'.$query->prefix_param('groupid')));
            $query->add_param('groupid', $groupid);
        }
    }

    /**
     * Applies a filter so that only nodes from a certain cohort are returned
     *
     * @param \block_ajax_marking_query_base|bool $query
     * @param $operation
     * @param bool|int $cohortid
     * @global moodle_database $DB
     * @return void
     */
    private static function apply_cohortid_filter(block_ajax_marking_query_base $query,
                                                  $operation, $cohortid = false) {

        global $DB;

        $selects = array();

        // Note: Adding a cohort filter after any other filter will cause a problem as e.g. courseid
        // will not include the code below limiting users to just those who are in a cohort. This
        // means that the total count may well be higher for

        // We need to join the userid to the cohort, if there is one.
        $useridcolumn = $query->get_userid_column();
        if ($useridcolumn) {
            // Add join to cohort_members
            $query->add_from(array(
                    'join' => 'INNER JOIN',
                    'table' => 'cohort_members',
                    'on' => 'cohort_members.userid = '.$useridcolumn
            ));
            $query->add_from(array(
                    'join' => 'INNER JOIN',
                    'table' => 'cohort',
                    'on' => 'cohort_members.cohortid = cohort.id'
            ));

            // Join cohort_members only to cohorts that are enrolled in the course.
            // We already have a check for enrolments, so we just need a where.

            $query->add_where(array(
                    'type' => 'AND',
                    'condition' => $DB->sql_compare_text('ue.enrol')." = 'cohort'"));
        }

        switch ($operation) {

            case 'where':

                // Apply WHERE clause
                $query->add_where(array(
                        'type' => 'AND',
                        'condition' => 'cohort.id = :'.$query->prefix_param('cohortid')));
                $query->add_param('cohortid', $cohortid);
                break;

            case 'countselect':

                $selects = array(array(
                        'table'    => 'cohort',
                        'column'   => 'id',
                        'alias'    => 'cohortid'),
                    array(
                        'table'    => 'sub',
                        'column'   => 'id',
                        'alias'    => 'count',
                        'function' => 'COUNT',
                        'distinct' => true),
                );
                break;

            case 'displayselect':

                // What do we need for the nodes?
                $query->add_from(array(
                        'join' => 'INNER JOIN',
                        'table' => 'cohort',
                        'on' => 'combinedmodulesubquery.id = cohort.id'
                ));
                $selects = array(
                    array(
                        'table'    => 'cohort',
                        'column'   => 'name'),
                    array(
                        'table'    => 'cohort',
                        'column'   => 'description'));
                break;
        }

        foreach ($selects as $select) {
            $query->add_select($select);
        }
    }

    /**
     * Applies the filter needed for assessment nodes or their descendants
     *
     * @param block_ajax_marking_query_base $query
     * @param int $coursemoduleid optional. Will apply SELECT and GROUP BY for nodes if missing
     * @param bool $operation
     * @return void
     */
    private static function apply_coursemoduleid_filter($query, $operation, $coursemoduleid = 0 ) {
        $selects = array();
        $jointable = 'combinedmodulesubquery.id';

        switch ($operation) {

            case 'where':
                $query->add_where(array(
                        'type' => 'AND',
                        'condition' => 'cm.id = :'.$query->prefix_param('coursemoduleid')));
                $query->add_param('coursemoduleid', $coursemoduleid);
                break;

            case 'countselect':

                // Same order as the next query will need them
                $selects = array(
                    array(
                        'table' => 'cm',
                        'column' => 'id',
                        'alias' => 'coursemoduleid'),
                    array(
                        'table' => 'sub',
                        'column' => 'id',
                        'alias' => 'count',
                        'function' => 'COUNT',
                        'distinct' => true),
                    // This is only needed to add the right callback function.
                    array(
                        'column' => "'".$query->get_modulename()."'",
                        'alias' => 'modulename'
                        ));
                    break;

            case 'configselect':

                // This query just unions together simple queries that get all

                $selects = array(
                    array(
                        'table' => 'course_modules',
                        'column' => 'id',
                        'alias' => 'coursemoduleid'),
                    array(
                        'table' => 'moduletable',
                        'column' => 'name'),
                    array(
                        'table' => 'moduletable',
                        'column' => 'intro',
                        'alias' => 'tooltip'));

                $query->add_from(array(
                        'join' => 'INNER JOIN',
                        'table' => 'course_modules',
                        'on' => 'moduletable.id = course_modules.instance'
                ));
                $query->add_where(array(
                        'type' => 'AND',
                        'condition' => 'course_modules.module = '.$query->get_module_id()));
                break;

            case 'displayselect':

                // Need to get the module stuff from specific tables, not coursemodule
                $query->add_from(array(
                        'join' => 'INNER JOIN',
                        'table' => 'course_modules',
                        'on' => $jointable.' = course_modules.id'
                ));

                // Awkwardly, the course_module table doesn't hold the name and description of the
                // module instances, so we need to join to the module tables. This will cause a mess
                // unless we specify that only coursemodules with a specific module id should join
                // to a specific module table
                $moduleclasses = block_ajax_marking_get_module_classes();
                $introcoalesce = array();
                $namecoalesce = array();
                foreach ($moduleclasses as $moduleclass) {
                    $query->add_from(array(
                        'join' => 'LEFT JOIN',
                        'table' => $moduleclass->get_module_table(),
                        'on' => "(course_modules.instance = ".$moduleclass->get_module_table().".id
                                  AND course_modules.module = '".$moduleclass->get_module_id()."')"
                    ));
                    $namecoalesce[$moduleclass->get_module_table()] = 'name';
                    $introcoalesce[$moduleclass->get_module_table()] = 'intro';
                }

                $selects = array(
                    array(
                            'table'    => 'course_modules',
                            'column'   => 'id',
                            'alias'    => 'coursemoduleid'),
                    array(
                            'table'    => 'combinedmodulesubquery',
                            'column'   => 'modulename'),
                    array(
                            'table'    => $namecoalesce,
                            'function' => 'COALESCE',
                            'column'   => 'name',
                            'alias'    => 'name'),
                    array(
                            'table'    => $introcoalesce,
                            'function' => 'COALESCE',
                            'column'   => 'intro',
                            'alias'    => 'tooltip')
                );
                break;
        }

        foreach ($selects as $select) {
            $query->add_select($select);
        }
    }

    /**
     * We need to check whether the assessment can be displayed (the user may have hidden it).
     * This sql can be dropped into a query so that it will get the right students. This will also
     * make sure that if only some groups are being displayed, the submission is by a user who
     * is in one of the displayed groups.
     *
     * @param block_ajax_marking_query_base $query a query object to apply these changes to
     * @return void
     */
    protected function apply_sql_display_settings($query) {

        global $DB;

        // User settings for individual activities
        $coursemodulescompare = $DB->sql_compare_text('settings_course_modules.tablename');
        $query->add_from(array(
                'join'  => 'LEFT JOIN',
                'table' => 'block_ajax_marking',
                'alias' => 'settings_course_modules',
                'on'    => "(cm.id = settings_course_modules.instanceid ".
                           "AND {$coursemodulescompare} = 'course_modules')"
        ));
        // User settings for courses (defaults in case of no activity settings)
        $coursecompare = $DB->sql_compare_text('settings_course.tablename');
        $query->add_from(array(
                'join'  => 'LEFT JOIN',
                'table' => 'block_ajax_marking',
                'alias' => 'settings_course',
                'on'    => "(cm.course = settings_course.instanceid ".
                           "AND {$coursecompare} = 'course')"
        ));
        // User settings for groups per course module. Will have a value if there is any groups
        // settings for this user and coursemodule
        $useridfield = $query->get_userid_column();
        list ($groupuserspersetting, $groupsparams) = self::get_sql_groups_subquery();
        $query->add_params($groupsparams, false);
        $query->add_from(array(
                'join'  => 'LEFT JOIN',
                'table' => $groupuserspersetting,
                'subquery' => true,
                'alias' => 'settings_course_modules_groups',
                'on'    => "settings_course_modules.id = settings_course_modules_groups.configid".
                           " AND settings_course_modules_groups.userid = {$useridfield}"
        ));
        // Same thing for the courses. Provides a default.
        // Need to get the sql again to regenerate the params to a unique placeholder.
        list ($groupuserspersetting, $groupsparams) = self::get_sql_groups_subquery();
        $query->add_params($groupsparams, false);
        $query->add_from(array(
                'join'  => 'LEFT JOIN',
                'table' => $groupuserspersetting,
                'subquery' => true,
                'alias' => 'settings_course_groups',
                'on'    => "settings_course.id = settings_course_groups.configid".
                           " AND settings_course_groups.userid = {$useridfield}"
        ));

        // Hierarchy of displaying things, simplest first. Hopefully lazy evaluation will make it
        // quick.
        // - No display settings (default to show without any groups)
        // - settings_course_modules display is 1, settings_course_modules.groupsdisplay is 0.
        //   Overrides any course settings
        // - settings_course_modules display is 1, groupsdisplay is 1 and user is in OK group
        // - settings_course_modules display is null, settings_course.display is 1,
        //   settings_course.groupsdisplay is 0
        // - settings_course_modules display is null, settings_course.display is 1,
        //   settings_course.groupsdisplay is 1 and user is in OK group.
        //   Only used if there is no setting at course_module level, so overrides that hide stuff
        //   which is shown at course level work.
        // - settings_course_modules display is null, settings_course.display is 1,
        //   settings_course.groupsdisplay is 1 and user is in OK group.
        //   Only used if there is no setting at course_module level, so overrides that hide stuff
        //   which is shown at course level work.
        $query->add_where(array(
                'type' => 'AND',
                'condition' => "( (settings_course_modules.display IS NULL
                                   AND settings_course.display IS NULL)

                                  OR

                                  (settings_course_modules.display = 1
                                   AND settings_course_modules.groupsdisplay = 0)

                                  OR

                                   (settings_course_modules.display = 1
                                    AND settings_course_modules.groupsdisplay = 0
                                    AND settings_course_modules_groups.display = 1)

                                  OR

                                  (settings_course_modules.display IS NULL
                                   AND settings_course.display = 1
                                   AND settings_course.groupsdisplay = 0)

                                  OR

                                  (settings_course_modules.display IS NULL
                                   AND settings_course.display = 1
                                   AND settings_course.groupsdisplay = 1
                                   AND settings_course_groups.display = 1)
                                )"));

    }

    /**
     * All modules have a common need to hide work which has been submitted to items that are now
     * hidden. Not sure if this is relevant so much, but it's worth doing so that test data and test
     * courses don't appear. General approach is to use cached context info from user session to
     * find a small list of contexts that a teacher cannot grade in within the courses where they
     * normally can, then do a NOT IN thing with it. Also the obvious visible = 1 stuff.
     *
     * @param \block_ajax_marking_query_base $query
     * @return array The join string, where string and params array. Note, where starts with 'AND'
     */
    protected function apply_sql_visible(block_ajax_marking_query_base $query) {

        global $DB;

        $query->add_from(array(
                'join' => 'INNER JOIN',
                'table' => 'course_modules',
                'alias' => 'cm',
                'on' => 'cm.instance = moduletable.id'
        ));
        $query->add_from(array(
                'join' => 'INNER JOIN',
                'table' => 'course',
                'alias' => 'course',
                'on' => 'course.id = moduletable.course'
        ));

        // Get coursemoduleids for all items of this type in all courses as one query. Won't come
        // back empty or else we would not have gotten this far
        $courses = block_ajax_marking_get_my_teacher_courses();
        // TODO Note that change to login as... in another tab may break this

        list($coursesql, $params) = $DB->get_in_or_equal(array_keys($courses), SQL_PARAMS_NAMED);

        // Get all coursemodules the current user could potentially access.
        // TODO this may return literally millions for a whole site admin. Change it to the one
        // that's limited by explicit category and course permissions
        $sql = "SELECT id
                  FROM {course_modules}
                 WHERE course {$coursesql}
                   AND module = :moduleid";
        $params['moduleid'] = $query->get_module_id();
        // no point caching - only one request per module per page request:
        $coursemoduleids = $DB->get_records_sql($sql, $params);
        // Get all contexts (will cache them). This is expensive and hopefully has been cached in]
        // the session already, so we take advantage of it.
        $contexts = get_context_instance(CONTEXT_MODULE, array_keys($coursemoduleids));
        // Use has_capability to loop through them finding out which are blocked. Unset all that we
        // have permission to grade, leaving just those we are not allowed (smaller list). Hopefully
        // this will never exceed 1000 (oracle hard limit on number of IN values).
        foreach ($contexts as $key => $context) {
            if (has_capability($query->get_capability(), $context)) { // this is fast because cached
                unset($contexts[$key]);
            }
        }
        // return a get_in_or_equals with NOT IN if there are any, or empty strings if there aren't.
        if (!empty($contexts)) {
            list($contextssql, $contextsparams) = $DB->get_in_or_equal(array_keys($contexts),
                                                                       SQL_PARAMS_NAMED,
                                                                       'context0000',
                                                                       false);
            $query->add_where(array('type' => 'AND', 'condition' => "cm.id {$contextssql}"));
            $query->add_params($contextsparams);
        }

        $query->add_where(array(
                'type' => 'AND',
                'condition' => 'cm.module = :'.$query->prefix_param('visiblemoduleid')));
        $query->add_where(array('type' => 'AND', 'condition' => 'cm.visible = 1'));
        $query->add_where(array('type' => 'AND', 'condition' => 'course.visible = 1'));

        $query->add_param('visiblemoduleid', $query->get_module_id());

    }

    /**
     * Makes sure we only get stuff for the courses this user is a teacher in
     *
     * @param block_ajax_marking_query_base $query
     * @return void
     */
    private function apply_sql_owncourses(block_ajax_marking_query_base $query) {

        global $DB;

        $courses = block_ajax_marking_get_my_teacher_courses();

        $courseids = array_keys($courses);

        if ($courseids) {
            $startname = $query->prefix_param('courseid0000');
            list($sql, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, $startname);

            // Sometimes course.id, sometimes moduletable.course, depending on the query
            if ($query->has_join_table('course')) {
                $coursecolumn = 'course.id';
            } else {
                $coursecolumn = 'moduletable.course';
            }

            $query->add_where(array(
                    'type' => 'AND',
                    'condition' => $coursecolumn.' '.$sql));
            $query->add_params($params, false);
        }
    }

    /**
     * Returns an SQL snippet that will tell us whether a student is directly enrolled in this
     * course
     * @TODO: Needs to also check parent contexts.
     *
     * @param \block_ajax_marking_query_base $query
     * @internal param string $useralias the thing that contains the userid e.g. s.userid
     * @internal param string $moduletable the thing that contains the courseid e.g. a.course
     * @return array The join and where strings, with params. (Where starts with 'AND)
     */
    private function apply_sql_enrolled_students(block_ajax_marking_query_base $query) {

        global $DB, $CFG, $USER;

        $usercolumn = $query->get_userid_column();

        // Hide users added by plugins which are now disabled.
        if ($CFG->enrol_plugins_enabled) {
            // returns list of english names of enrolment plugins
            $plugins = explode(',', $CFG->enrol_plugins_enabled);
            $startparam = $query->prefix_param('enrol001');
            list($enabledsql, $params) = $DB->get_in_or_equal($plugins,
                                                              SQL_PARAMS_NAMED,
                                                              $startparam);
            $query->add_params($params, false);
        } else {
            // no enabled enrolment plugins
            $enabledsql = ' = :'.$query->prefix_param('never');
            $query->add_param('never', -1);
        }

        $subquery = new block_ajax_marking_query_base();
        $subquery->add_select(array(
                'table' => 'enrol',
                'column' => 'courseid'
        ));
        $subquery->add_select(array(
                'table' => 'enrol',
                'column' => 'enrol'
        ));
        $subquery->add_select(array(
                'table' => 'user_enrolments',
                'column' => 'userid'
        ));
        $subquery->add_from(array(
                'table' => 'user_enrolments'
        ));
        $subquery->add_from(array(
                'join' => 'INNER JOIN',
                'table' => 'enrol',
                'on' => 'enrol.id = user_enrolments.enrolid'
        ));
        // Also hide our own work. Only really applies in testing, but still.
        $subquery->add_where(array(
                'type' => 'AND',
                'condition' => "user_enrolments.userid != :".$query->prefix_param('currentuser')
        ));
        $subquery->add_where(array(
                'type' => 'AND',
                'condition' => "enrol.enrol {$enabledsql}"
        ));
        $query->add_from(array(
                'join' => 'INNER JOIN',
                'table' => $subquery,
                'alias' => 'ue',
                'on' => "(ue.courseid = moduletable.course AND ue.userid = {$usercolumn})"
        ));
        $query->add_param('currentuser', $USER->id);
    }

    /**
     * Provides a subquery with all users who are in groups that ought to be displayed, per config
     * setting e.g. which users are in displayed groups display for items where groups display is
     * enabled. We use a SELECT 1 to see if the user of the submission is there for the relevant
     * config thing.
     *
     * @return array SQL fragment and params
     */
    private function get_sql_groups_subquery() {
        global $USER;

        static $count = 1; // If we reuse this, we cannot have the same names for the params

        // If a user is in two groups, this will lead to duplicates. We use DISTINCT in the
        // SELECT to prevent this. Possibly one group will say 'display' and the other will say
        // 'hide'. We assume display if it's there, using MAX to get any 1 that there is.
        $groupsql = " SELECT DISTINCT gm.userid, groups_settings.configid,
                             MAX(groups_settings.display) AS display
                        FROM {groups_members} gm
                  INNER JOIN {groups} g
                          ON gm.groupid = g.id
                  INNER JOIN {block_ajax_marking_groups} groups_settings
                          ON g.id = groups_settings.groupid
                  INNER JOIN {block_ajax_marking} settings
                          ON groups_settings.configid = settings.id
                       WHERE settings.groupsdisplay = 1
                         AND settings.userid = :groupsettingsuserid{$count}
                    GROUP BY gm.userid, groups_settings.configid";
        // Adding userid to reduce the results set so that the SQL can be more efficient
        $params = array('groupsettingsuserid'.$count => $USER->id);
        $count++;

        return array($groupsql, $params);

    }

    /**
     * For the config nodes, we want all of the coursemodules. No need to worry about counting etc
     *
     * @param array $filters
     * @return array
     */
    public static function get_config_nodes($filters) {

        global $DB;

        $modulequeries = array();
        $moduleclasses = block_ajax_marking_get_module_classes();
        if (!$moduleclasses) {
            return array(); // No nodes
        }

        foreach ($moduleclasses as $modname => $moduleclass) {
            /** @var $moduleclass block_ajax_marking_module_base */
            $modulequeries[$modname] = self::get_filtered_module_query($filters, $moduleclass);
        }

        $unionallmodulesquery = array();
        $unionallmodulesparams = array();
        foreach ($modulequeries as $query) {
            /** @var $query block_ajax_marking_query_base */
            $unionallmodulesquery[] = $query->to_string();
            $unionallmodulesparams = array_merge($unionallmodulesparams, $query->get_params());
        }

        // Just straight union here. No counts, so duplicates will happen and can be merged
        // We just want anything where the user has permissions to grade.
        $unionallmodulesquery = implode("\n\n UNION \n\n", $unionallmodulesquery);

        // This is just for copying and pasting from the paused debugger into a DB GUI
        $debugquery = block_ajax_marking_debuggable_query($unionallmodulesquery,
                                                          $unionallmodulesparams);

        $nodes = $DB->get_records_sql($unionallmodulesquery, $unionallmodulesparams);
        return $nodes;

    }




}