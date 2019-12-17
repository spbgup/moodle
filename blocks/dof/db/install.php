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
 * Free Dean's Office installation.
 *
 * @package    block
 * @subpackage dof
 * @author     Kirill Krasnoschekov, Ilya Fastenko, OpenTechnology ltd.
 * @copyright  2013
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/blocks/dof/otapilib.php');

function xmldb_block_dof_install()
{
    global $DB, $OUTPUT;
    $otapi = new block_dof_otserial();

    // Пытаемся найти серийник и ключ в конфиге
    $otserial = get_config('block_dof', 'otserial');
    $otkey = get_config('block_dof', 'otkey');

    // В конфиге не нашлось: пытаемся получить
    if (empty($otserial) OR empty($otkey)) 
    {
        $otdata = $otapi->get_otserial();
        if ( isset($otdata->status) AND preg_match('/^error/', $otdata->status ) ) 
        {
            // Сервер не выдал серийник, вернул ошибку
            $msg = $otdata->message;
            echo $OUTPUT->notification(get_string('get_otserial_fail', 'block_dof', $msg));
        }elseif( !empty($otdata->otserial) AND !empty($otdata->otkey) ) 
        {
            // Сервер вернул серийник и ключ, сохраняем в конфиг
            set_config('otserial', $otdata->otserial, 'block_dof');
            set_config('otkey', $otdata->otkey, 'block_dof');
            $otserial = $otdata->otserial;
            $otkey = $otdata->otkey;
        }
    }

    // Проверяем статус
    if ( !empty($otserial) AND !empty($otkey) ) 
    {
        $stdata = $otapi->get_otserial_status($otserial, $otkey);
        if ( isset($stdata->status) AND preg_match('/^error/', $stdata->status) ) 
        {
            // Ошибка проверки серийника, показываем пользователю
            $msg = $stdata->message;
            echo $OUTPUT->notification(get_string('otserial_check_fail', 'block_dof', $msg));
        }else 
        {
            // Серийник прошел проверку
            echo $OUTPUT->notification(get_string('otserial_check_ok', 'block_dof'), 'notifysuccess');
        }
    }
}

?>