<?php
// Список всех вебинаров в структуре (ресурсах) курса

    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id', PARAM_INT);   // course

    if (! $course = get_record("course", "id", $id)) {
        error("Course ID is incorrect");
    }

    require_login($course->id);

    add_to_log($course->id, "webinarru", "view all", "index.php?id=$course->id", "");


/// Get all required stringswebinarru

    $strwebinarru = get_string("modulenameplural", "webinarru");
    $strwebinarru  = get_string("modulename", "webinarru");

/// Print the header

    if ($course->category) {
        $navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
    } else {
        $navigation = '';
    }

    print_header("$course->shortname: $strwebinarru", "$course->fullname", "$navigation $strwebinarru", "", "", true, "", navmenu($course));

/// Get all the appropriate data

    if (! $webinarru = get_all_instances_in_course("webinarru", $course)) {
        notice("There are no webinarru", "../../course/view.php?id=$course->id");
        die;
    }

/// Print the list of instances (your module will probably extend this)

    $timenow = time();
    $strname  = get_string("name");
    $strweek  = get_string("week");
    $strtopic  = get_string("topic");

    if ($course->format == "weeks") {
        $table->head  = array ($strweek, $strname);
        $table->align = array ("center", "left");
    } else if ($course->format == "topics") {
        $table->head  = array ($strtopic, $strname);
        $table->align = array ("center", "left", "left", "left");
    } else {
        $table->head  = array ($strname);
        $table->align = array ("left", "left", "left");
    }

    foreach ($webinarru as $webinarru) {
        if (!$webinarru->visible) {
            //Show dimmed if the mod is hidden
            $link = "<a class=\"dimmed\" href=\"view.php?id=$webinarru->coursemodule\">$webinarru->name</a>";
        } else {
            //Show normal if the mod is visible
            $link = "<a href=\"view.php?id=$webinarru->coursemodule\">$webinarru->name</a>";
        }

        if ($course->format == "weeks" or $course->format == "topics") {
            $table->data[] = array ($webinarru->section, $link);
        } else {
            $table->data[] = array ($link);
        }
    }

    echo "<br />";

    print_table($table);

/// Finish the page

    print_footer($course);

?>