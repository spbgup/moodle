<?php

defined('MOODLE_INTERNAL') || die;

$settings->add(new admin_setting_configtext('webinarru_host', get_string('host', 'webinarru'),
                       get_string('host', 'webinarru'), "my.webinar.ru", PARAM_TEXT));
                       
$settings->add(new admin_setting_configtext('webinarru_AdminUser', get_string('AdminUser', 'webinarru'),
                       get_string('AdminUser', 'webinarru'), "admin", PARAM_TEXT));
                       
$settings->add(new admin_setting_configpasswordunmask('webinarru_AdminUserPass', get_string('AdminUserPass', 'webinarru'),
                       get_string('AdminUserPass', 'webinarru'), "password", PARAM_TEXT));                       
                   
$settings->add(new admin_setting_configtext('webinarru_ModuleKey', get_string('ModuleKeyLabel', 'webinarru'),
                       get_string('ModuleKey', 'webinarru'), "moodle", PARAM_TEXT));
                       
                       