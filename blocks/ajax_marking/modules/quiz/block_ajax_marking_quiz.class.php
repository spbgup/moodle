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
 * Class file for the quiz grading functions
 *
 * @package    block
 * @subpackage ajax_marking
 * @copyright  2008 Matt Gibson
 * @author     Matt Gibson {@link http://moodle.org/user/view.php?id=81450}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die();
}

global $CFG;

/** @define "$blockdir" "../.." */
$blockdir = $CFG->dirroot.'/blocks/ajax_marking';
require_once($blockdir.'/classes/query_base.class.php');
require_once($blockdir.'/classes/query_factory.class.php');

// We only need this file for the constants. Doing this so that we don't have problems including
// the file from module.js


if (isset($CFG) && !empty($CFG)) {
    require_once($CFG->dirroot.'/lib/questionlib.php');
    require_once($CFG->dirroot.'/mod/quiz/attemptlib.php');
    require_once($CFG->dirroot.'/mod/quiz/locallib.php');
    require_once($CFG->dirroot.'/question/engine/states.php');
    require_once($CFG->dirroot.'/blocks/ajax_marking/modules/quiz/'.
                 'block_ajax_marking_quiz_form.class.php');
}

/**
 * Provides all marking functionality for the quiz module
 *
 * @copyright 2008-2010 Matt Gibson
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_ajax_marking_quiz extends block_ajax_marking_module_base {

    /**
     * Constructor
     *
     * @internal param object $reference the parent object passed in by reference so that it's data
     * can be used
     * @return \block_ajax_marking_quiz
     */
    public function __construct() {

        // call parent constructor with the same arguments
        parent::__construct();

        // must be the same as the DB modulename
        $this->modulename = $this->moduletable = 'quiz';
        $this->capability           = 'mod/quiz:grade';
        $this->icon                 = 'mod/quiz/icon.gif';
    }

    /**
     * This will alter a query to send back the stuff needed for quiz questions
     *
     * @param \block_ajax_marking_query_base|\type $query
     * @param $operation
     * @param int $questionid the id to filter by
     * @return void
     */
    public function apply_questionid_filter(block_ajax_marking_query_base $query, $operation,
                                            $questionid = 0) {

        $selects = array();

        switch ($operation) {

            case 'where':
                // Apply WHERE clause
                $query->add_where(array(
                        'type' => 'AND',
                        'condition' => 'question.id = :'.$query->prefix_param('questionid')));
                $query->add_param('questionid', $questionid);
                break;

            case 'displayselect':

                $query->add_from(array(
                        'join' => 'INNER JOIN',
                        'table' => 'question',
                        'on' => 'question.id = combinedmodulesubquery.id'));
                $selects = array(
                        array(
                            'table' => 'question',
                            'column' => 'name'),
                        array(
                            'table' => 'question',
                            'column' => 'questiontext',
                            'alias' => 'tooltip')
                );
                break;

            case 'countselect':

                $selects = array(
                    array(
                        'table' => 'question',
                        'column' => 'id',
                        'alias' => 'questionid'),
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
                        )
                    );
                break;
        }

        foreach ($selects as $select) {
            $query->add_select($select);
        }
    }

    /**
     * Makes an HTML link for the pop up to allow grading of a question
     *
     * @param object $item containing the quiz id as ->id
     * @return string
     */
    public function make_html_link($item) {

        global $CFG;
        $address = $CFG->wwwroot.'/mod/quiz/report.php?q='.$item->assessmentid.'&mode=grading';
        return $address;
    }

    /**
     * Returns the name of the column in the submissions table which holds the userid of the
     * submitter
     *
     * @return string
     */
    protected function get_sql_userid_column() {
        return 'qa.userid';
    }

    /**
     * To make up for the fact that in 2.0 there is no screen with both quiz question and feedback
     * text-entry box next to each other (the feedback bit is a separate pop-up), we have to make
     * a custom form to allow grading to happen. It is based on code from
     * /mod/quiz/reviewquestion.php
     *
     * Use questionid, rather than slot so we can group the same question in future, even across
     * random questions.
     *
     * @global stdClass $CFG
     * @global moodle_database $DB
     * @param array $params all of the stuff sent with the node click e.g. questionid
     * @param object $coursemodule
     * @return string the HTML page
     */
    public function grading_popup($params, $coursemodule) {

        global $CFG, $PAGE, $DB, $OUTPUT;

        // TODO what params do we get here?

        require_once($CFG->dirroot.'/mod/quiz/locallib.php');

         //TODO feed in all dynamic variables here
        $url = new moodle_url('/blocks/ajax_marking/actions/grading_popup.php', $params);
        $PAGE->set_url($url);

        $formattributes = array(
                    'method' => 'post',
                    'class'  => 'mform',
                    'id'     => 'manualgradingform',
                    'action' => block_ajax_marking_form_url($params));
        echo html_writer::start_tag('form', $formattributes);

        // We could be looking at multiple attempts and/or multiple questions
        // Assume we have a user/quiz combo to get us here. We may have attemptid or questionid too

        // Get all attempts with unmarked questions. We may or may not have a questionid, but
        // this comes later so we can use the quiz's internal functions
        $questionattempts = $this->get_question_attempts($params);

        if (!$questionattempts) {
            die('Could not retrieve question attempts. Maybe someone else marked them just now');
        }

        // cache the attempt objects for reuse.
        $quizattempts = array();
        // We want to get the first one ready, so we can use it to print the info box
        $firstattempt = reset($questionattempts);
        $quizattempt = quiz_attempt::create($firstattempt->quizattemptid);
        $quizattempts[$firstattempt->quizattemptid] = $quizattempt;

        // Print infobox
        $rows = array();

        // Print user picture and name
        $student = $DB->get_record('user', array('id' => $quizattempt->get_userid()));
        $courseid = $quizattempt->get_courseid();
        $picture = $OUTPUT->user_picture($student, array('courseid' => $courseid));
        $url = $CFG->wwwroot.'/user/view.php?id='.$student->id.'&amp;course='.$courseid;
        $rows[] = '<tr>
                       <th scope="row" class="cell">' . $picture . '</th>
                       <td class="cell">
                           <a href="' .$url. '">' .
                               fullname($student, true) .
                          '</a>
                      </td>
                  </tr>';

        // Now output the summary table, if there are any rows to be shown.
        if (!empty($rows)) {
            echo '<table class="generaltable generalbox quizreviewsummary"><tbody>', "\n";
            echo implode("\n", $rows);
            echo "\n</tbody></table>\n";
        }

        foreach ($questionattempts as $questionattempt) {

            // Everything should already be in the right order:
            // Question 1
            // - Attempt 1
            // - Attempt 2
            // Question 2
            // - Attempt 1
            // - Attempt 2

            // N.B. Using the proper quiz functions in an attempt to make this more robust
            // against future changes
            if (!isset($quizattempts[$questionattempt->quizattemptid])) {
                $quizattempt = quiz_attempt::create($questionattempt->quizattemptid);
                $quizattempts[$questionattempt->quizattemptid] = $quizattempt;
            } else {
                $quizattempt = $quizattempts[$questionattempt->quizattemptid];
            }

            // Log this review.
            $attemptid = $quizattempt->get_attemptid();
            add_to_log($quizattempt->get_courseid(), 'quiz', 'review',
                       'reviewquestion.php?attempt=' .
                       $attemptid . '&question=' . $params['questionid'] ,
                       $quizattempt->get_quizid(), $quizattempt->get_cmid());
            // Now make the actual markup to show one question plus commenting/grading stuff
            echo $quizattempt->render_question_for_commenting($questionattempt->slot);

        }

        echo html_writer::start_tag('div');
        echo html_writer::empty_tag('input', array('type' => 'submit', 'value' => 'Save'));

        foreach ($params as $name => $value) {
            echo html_writer::empty_tag('input', array('type' => 'hidden',
                                                       'name' => $name,
                                                       'value' => $value));
        }
        echo html_writer::empty_tag('input', array('type' => 'hidden',
                                                  'name' => 'sesskey',
                                                  'value' => sesskey()));
        echo html_writer::end_tag('div');

        echo html_writer::end_tag('form');
    }

    /**
     * Deals with data coming in from the grading pop up
     *
     * @param object $data the form data
     * @param $params
     * @return mixed true on success or an error.
     */
    public function process_data($data, $params) {

        global $DB;

        // Get all attempts on all questions that were unmarked.
        // Slight chance that someone else will have marked the questions since this user opened
        // the pop up, which could lead to these grades being ignored, or the other person's
        // being overwritten. Not much we can do about that.
        $questionattempts = $this->get_question_attempts($params);
        // We will have duplicates as there could be multiple questions per attempt
        $processedattempts = array();

        // This will get all of the attempts to pull the relevant data from all the POST stuff
        // and process it. The quiz adds lots of prefix stuff, so we won't have collisions.
        foreach ($questionattempts as $attempt) {
            $id = $attempt->quizattemptid;
            if (isset($processedattempts[$id])) {
                continue;
            }
            $processedattempts[$id] = quiz_attempt::create($id);
            $transaction = $DB->start_delegated_transaction();
            $processedattempts[$id]->process_all_actions(time());
            $transaction->allow_commit();
        }

        return true;
    }

    /**
     * Returns a query object with the basics all set up to get assignment stuff
     *
     * @global moodle_database $DB
     * @return block_ajax_marking_query_base
     */
    public function query_factory() {

        global $DB;

        $query = new block_ajax_marking_query_base($this);
        $query->set_userid_column('quiz_attempts.userid');

        $query->add_from(array(
                'table' => $this->modulename,
                'alias' => 'moduletable',
        ));
        $query->add_from(array(
                'join'  => 'INNER JOIN',
                'table' => 'quiz_attempts',
                'on'    => 'moduletable.id = quiz_attempts.quiz'
        ));
        $query->add_from(array(
                'join'  => 'INNER JOIN',
                'table' => 'question_attempts',
                'on'    => 'question_attempts.questionusageid = quiz_attempts.uniqueid'
        ));
        $query->add_from(array(
                'join'  => 'INNER JOIN',
                'table' => 'question_attempt_steps',
                'alias' => 'sub',
                'on'    => 'question_attempts.id = sub.questionattemptid'
        ));
        $query->add_from(array(
                'join'  => 'INNER JOIN',
                'table' => 'question',
                'on'    => 'question_attempts.questionid = question.id'
        ));

        $query->add_where(array('type' => 'AND',
                                'condition' => 'quiz_attempts.timefinish > 0'));
        $query->add_where(array('type' => 'AND',
                                'condition' => 'quiz_attempts.preview = 0'));
        $comparesql = $DB->sql_compare_text('question_attempts.behaviour')." = 'manualgraded'";
        $query->add_where(array('type' => 'AND',
                                'condition' => $comparesql));
        $query->add_where(array('type' => 'AND',
                                'condition' => "sub.state = '".question_state::$needsgrading."' "));

        // We want to get a list of graded states so we can retrieve all questions that don't have
        // one.
        $gradedstates = array();
        $us = new ReflectionClass('question_state');
        foreach ($us->getStaticProperties() as $name => $class) {
            if ($class->is_graded()) {
                $gradedstates[] = $name;
            }
        }
        list($gradedsql, $gradedparams) = $DB->get_in_or_equal($gradedstates,
                                                               SQL_PARAMS_NAMED,
                                                               'quizq001');
        $subsql = "NOT EXISTS( SELECT 1
                                 FROM {question_attempt_steps} st
                                WHERE st.state {$gradedsql}
                                  AND st.questionattemptid = question_attempts.id)";
        $query->add_where(array('type' => 'AND',
                                'condition' => $subsql));
        $query->add_params($gradedparams, false);
        return $query;
    }

    /**
     * Applies the module-specific stuff for the user nodes
     *
     * @param block_ajax_marking_query_base $query
     * @param $operation
     * @param int $userid
     * @return void
     */
    public function apply_userid_filter(block_ajax_marking_query_base $query, $operation,
                                        $userid = 0) {

        $selects = array();

        switch ($operation) {

            case 'where':
                // Applies if users are not the final nodes,
                $id = $query->prefix_param('submissionid');
                $query->add_where(array(
                        'type' => 'AND',
                        'condition' => 'quiz_attempts.userid = :'.$id)
                );
                $query->add_param('submissionid', $userid);
                break;

            case 'displayselect':
                $selects = array(
                        array(
                            'table'    => 'usertable',
                            'column'   => 'firstname'),
                        array(
                            'table'    => 'usertable',
                            'column'   => 'lastname'));

                $query->add_from(array(
                        'join'  => 'INNER JOIN',
                        'table' => 'user',
                        'alias' => 'usertable',
                        'on'    => 'usertable.id = combinedmodulesubquery.id'
                ));
                break;

            case 'countselect':
                $selects = array(
                    array(
                        'table'    => 'quiz_attempts',
                        'column'   => 'userid'),
                    array( // Count in case we have user as something other than the last node
                        'function' => 'COUNT',
                        'table'    => 'sub',
                        'column'   => 'id',
                        'alias'    => 'count'),
                    // This is only needed to add the right callback function.
                    array(
                        'column' => "'".$query->get_modulename()."'",
                        'alias' => 'modulename'
                        ));
                break;
        }

        foreach ($selects as $select) {
            $query->add_select($select);
        }

    }

    /**
     * Based on the supplied param from the node that was clicked, go and get all question attempts
     * that we need to grade. Both grading_pop_up() and process_data() need this in order to either
     * present or process the attempts.
     *
     * @param array $params
     * @return array
     */
    protected function get_question_attempts($params) {

        $query = block_ajax_marking_query_factory::get_filtered_module_query($params, $this);
        $query->add_select(array('table' => 'question_attempts',
                               'column' => 'id',
                               'distinct' => true
                           ));
        $query->add_select(array('table' => 'quiz_attempts',
                               'column' => 'id',
                               'alias' => 'quizattemptid'
                           ));
        $query->add_select(array('table' => 'question_attempts',
                               'column' => 'questionid',
                           ));
        $query->add_select(array('table' => 'question_attempts',
                               'column' => 'slot',
                           ));
        // We want the oldest at the top so that the tutor can see how the answer changes over time
        $query->add_orderby('question_attempts.slot, quiz_attempts.id ASC');
        $questionattempts = $query->execute();
        return $questionattempts;
    }


}

