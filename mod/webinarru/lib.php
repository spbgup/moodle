<?php

//$old_error_handler = set_error_handler("myErrorHandler");
require_once($CFG->dirroot.'/config.php');
//require_once($CFG->dirroot.'/mod/webinarru/webinarru_gateway.php');

// error handler function
/* function myErrorHandler($errno, $errstr, $errfile, $errline)
{
    switch ($errno) {
    case E_USER_ERROR:
        echo "<b>My ERROR</b> [$errno] $errstr<br />\n";
        echo "  Fatal error on line $errline in file $errfile";
        echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
        echo "Aborting...<br />\n";
        exit(1);
        break;

    case E_USER_WARNING:
        echo "<b>My WARNING</b> [$errno] $errstr<br />\n";
        break;

    case E_USER_NOTICE:
        echo "<b>My NOTICE</b> [$errno] $errstr<br />\n";
        break;

    default:
        break;
    }

    // Don't execute PHP internal error handler
    return true;
} */

// http://my.webinar.ru/api0/Create.php?key={ключ заказывается в фирме}&name={NAME}&time={TIME}&description={DESRIPTION}&access={“open”|“close”|“close_password”}&maxAllowedUsers={MAXALLOWEDUSERS}
function webinarru_add_instance($webinarru) {
	global $USER, $CFG, $DB;
	
	$data = array(
		'key' => $CFG->webinarru_ModuleKey,
		'name' => $webinarru->name,
		'description' => $webinarru->description,
		'time' => $webinarru->starttime + 14400,
		'access' => $webinarru->access,
		'maxAllowedUsers' => $webinarru->maxallowedusers
	);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "http://my.webinar.ru/api0/Create.php");
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	$report = curl_exec($ch);
	curl_close($ch);
	
	$xml = new SimpleXMLElement($report);
	foreach($xml->attributes() as $a => $b) {
		$webinarru->event_id = (int) $b;
	}

    return $DB->insert_record("webinarru", $webinarru);

}

// http://my.webinar.ru/api0/Update.php?key={KEY}&event_id={EVENT_ID}name={NAME}&time={TIME}&description={DESCRIPTION}&maxAllowedUsers={MAXALLOWEDUSERS}
function webinarru_update_instance($webinarru) {
	global $CFG, $DB;
	
//	$webinarru = $DB->get_record("webinarru", array("id"=>"$id"))) {

	$data = array(
		'key' => $CFG->webinarru_ModuleKey,
		'name' => $webinarru->name,
		'description' => $webinarru->description,
		'time' => $webinarru->starttime + 14400,
		'access' => $webinarru->access,
		'maxAllowedUsers' => $webinarru->maxallowedusers,
		'event_id' => $webinarru->event_id
	);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "http://my.webinar.ru/api0/Update.php");
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	$report = curl_exec($ch);
	curl_close($ch);

	$xml = new SimpleXMLElement($report);
	foreach($xml->attributes() as $a => $b) {
		$webinarru->event_id = (int) $b;
	}
	
    return $DB->update_record("webinarru", $webinarru);
}


// http://my.webinar.ru/api0/Delete.php?event_id=XXX&key=XXX
function webinarru_delete_instance($id) {
	global $CFG, $DB;
	
    if (! $webinarru = $DB->get_record("webinarru", array("id"=>"$id"))) {
        return false;
    }

    $result = true;
    
	$data = array(
		'key'      => $CFG->webinarru_ModuleKey,
		'event_id' => $webinarru->event_id
	);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, "http://my.webinar.ru/api0/Delete.php");
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_exec($ch);
	curl_close($ch);

    if (! $DB->delete_records("webinarru", array("id"=>"$webinarru->id"))) {
        $result = false;
    }

    return $result;
}

function webinarru_user_outline($course, $user, $mod, $webinarru) {
    return $return;
}

function webinarru_user_complete($course, $user, $mod, $webinarru) {
    return true;
}

function webinarru_print_recent_activity($course, $isteacher, $timestart) {
    global $CFG;

    return false;  //  True if anything was printed, otherwise false 
}

function webinarru_cron () {
    global $CFG;

    return true;
}

function webinarru_grades($webinarruid) {
   return NULL;
}

function webinarru_get_participants($webinarruid) {
    return false;
}

function webinarru_scale_used ($webinarruid,$scaleid) {
    $return = false;

    return $return;
}

?>