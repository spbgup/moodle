<?php

if (!defined('MOODLE_INTERNAL')) {
	die('Прямой доступ к этой странице запрещен.');
}

require_once ($CFG->dirroot.'/course/moodleform_mod.php');

class mod_webinarru_mod_form extends moodleform_mod {

	function definition() {

		global $COURSE;
		$mform  =& $this->_form;

		/// Adding the "general" fieldset, where all the common settings are showed
		$mform->addElement('header', 'general', get_string('general', 'form'));

		// Название
		$mform->addElement('text', 'name', get_string('Webinar_Name', 'webinarru'), array('size'=>'100'));
		$mform->addRule('name', null, 'required', null, 'client');

		$mform->addElement('hidden', 'event_id', '0', array('size'=>'10'));
		$mform->addElement('hidden', 'id', '0', array('size'=>'10'));

		// Описание
		$mform->addElement('text', 'description', get_string('Comment', 'webinarru'), array('size'=>'100'));
		$mform->addRule('description', null, 'required', null, 'client');
		
		/// Язык
		$language_array = array ('1' => 'Русский', '2' => 'English');
		$mform->addElement('select', 'language', get_string('Webinar_Language', 'webinarru'), $language_array);
			
		/// Доступ
		$mform->addElement('select', 'access', get_string('Webinar_Access', 'webinarru'), array('open'=>get_string('access_open', 'webinarru'), 'close'=>get_string('access_close', 'webinarru'), 'close_password'=>get_string('access_close_password', 'webinarru')));

		/// Количество участников
		$mform->addElement('select', 'maxallowedusers', get_string('Max_User', 'webinarru'), array('5'=>'5', '10'=>'10', '15'=>'15', '20'=>'20', '25'=>'25'));

		/// Adding the optional "intro" and "introformat" pair of fields
//		$mform->addElement('htmleditor', 'intro', 'Comment');
//		$mform->setType('intro', PARAM_RAW);
		
		// Стандартные элементы модуля
        $mform->addElement('date_time_selector', 'starttime', get_string('starttime', 'webinarru'));
		$this->standard_coursemodule_elements();

		// Стандартные кнопки
		$this->add_action_buttons();
	}
}

?>