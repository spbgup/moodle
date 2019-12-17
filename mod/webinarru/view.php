<?php  
// Просмотр вебинара

require_once("../../config.php");
require_once("lib.php");
//require_once("webinarru_gateway.php");


$id = optional_param('id', 0, PARAM_INT); // Course Module ID, or
$g  = optional_param('g', 0, PARAM_INT);

if (!empty($id)) {
	if (! $cm = get_coursemodule_from_id('webinarru', $id)) {
		print_error('invalidcoursemodule');
	}
	if (! $course = $DB->get_record("course", array("id"=>$cm->course))) {
		print_error('coursemisconf');
	}
	if (! $webinarru = $DB->get_record("webinarru", array("id"=>$cm->instance))) {
		print_error('invalidid', 'webinarru');
	}

} else if (!empty($g)) {
	if (! $webinarru = $DB->get_record("webinarru", array("id"=>$g))) {
		print_error('invalidid', 'webinarru');
	}
	if (! $course = $DB->get_record("course", array("id"=>$webinarru->course))) {
		print_error('invalidcourseid');
	}
	if (!$cm = get_coursemodule_from_instance("webinarru", $webinarru->id, $course->id)) {
		print_error('invalidcoursemodule');
	}
	$id = $cm->id;
} else {
	print_error('invalidid', 'webinarru');
}

require_login($course->id);

add_to_log($course->id, "webinarru", "view", "view.php?id=$cm->id", "$webinarru->id");

/// Print the page header

if ($course->category) {
	$navigation = "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> ->";
} else {
	$navigation = '';
}

$strwebinarrus = get_string("modulenameplural", "webinarru");
$strwebinarru  = get_string("modulename", "webinarru");

print_header("$course->shortname: $webinarru->name", "$course->fullname",
			 "$navigation <a href=index.php?id=$course->id>$strwebinarrus</a> -> $webinarru->name", 
			  "", "", true, update_module_button($cm->id, $course->id, $strwebinarru), 
			  navmenu($course, $cm));

/// Print the main part of the page
  
$sitelink = str_replace("http://", "", $CFG->wwwroot);
   	
$context = get_context_instance(CONTEXT_MODULE, $cm->id);
    	
$webinarFrame = '<iframe src="http://' . $CFG->webinarru_host . '/event/' . $webinarru->event_id . '/?t=1&export=1" width="1024" height="768" frameborder="0" style="border:none"></iframe>';
echo $webinarFrame;
				
/// Finish the page
print_footer($course);
?>