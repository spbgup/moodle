<?php
////////////////////////////////////////////////////////////////////////////
//                                                                        //
// NOTICE OF COPYRIGHT                                                    //
//                                                                        //
// Dean`s Office for Moodle                                               //
// Электронный деканат                                                    //
// <http://deansoffice.ru/>                                               //
//                                                                        //
// Copyright (C) 2008-2999  Alex Djachenko (Алексей Дьяченко)             //
// alex-pub@my-site.ru                                                    //
// Copyright (C) 2008-2999  Evgenij Cigancov (Евгений Цыганцов)           //
// Copyright (C) 2008-2999  Ilia Smirnov (Илья Смирнов)                   // 
// Copyright (C) 2008-2999  Mariya Rojayskaya (Мария Рожайская)           // 
//                                                                        //
// This program is free software: you can redistribute it and/or modify   //
// it under the terms of the GNU General Public License as published by   //
// the Free Software Foundation, either version 3 of the Licensen.        //
//                                                                        //
// This program is distributed in the hope that it will be useful,        //
// but WITHOUT ANY WARRANTY; without even the implied warranty of         //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the          //
// GNU General Public License for more details.                           //
//                                                                        //
// You should have received a copy of the GNU General Public License      //
// along with this program.  If not, see <http://www.gnu.org/licenses/>.  //
//                                                                        //
////////////////////////////////////////////////////////////////////////////

// Подключаем библиотеки
require_once('lib.php');
require_once('libform.php');

// создаем объект отвечающий за отрисовку дневника
$recordbook    = new dof_im_recordbook_recordbook($DOF);
$programmsbcid = required_param('programmsbcid', PARAM_INT);
// получаем метки времени выбранного в календаре дня
$date_from     = optional_param('date_from', time(), PARAM_INT);
$contractid = $DOF->storage('programmsbcs')->get_field($programmsbcid, 'contractid');
$studentid  = $DOF->storage('contracts')->get_field($contractid, 'studentid');

$calendar = optional_param_array('calendar',null,PARAM_TEXT);
// если не подключан js, то поля date будут пустые
// потому делаем тут эту проверку
if ( !empty($calendar) AND is_array($calendar) AND !empty($calendar['date_from']) )
{
    $date_from = $calendar['date_from'];
}
// определяем выходные параметры формы
$addvars['programmsbcid'] = $programmsbcid;

$default = new stdClass();
$default->dof = $DOF;
$default->date_from = $date_from;
$default->depid = $addvars['departmentid'];

// создаем календарь
$date_picker = new dof_im_recordbook_datepicker_form($DOF->url_im("recordbook",
        "/recordbook.php", $addvars), $default);

if ( $date_picker->is_submitted() AND $formdata = $date_picker->get_data() )
{// данные отправлены и получены - установим дату вывода
    if ( !empty($calendar) AND is_array($calendar) AND !empty($calendar['date_from']) )
    {// из календаря
        $date_from = $formdata->calendar['date_from'];
    }else
    {// обычный select
        $date_from = $formdata->date_fr;
    }
}

//добавление уровня навигации, ведущего на страницу дневника
$DOF->modlib('nvg')->add_level($DOF->get_string('title', 'recordbook'), 
    $DOF->url_im('recordbook','/index.php?clientid='.$studentid,$addvars));
//вывод на экран
$DOF->modlib('nvg')->add_level($DOF->get_string('lesson_schedule', 'recordbook'), 
    $DOF->url_im('recordbook', '/recordbook.php', array_merge(array('programmsbcid' => $programmsbcid),$addvars)));
    
//печать шапки страницы
$DOF->modlib('nvg')->print_header(NVG_MODE_PORTAL);

//проверка прав доступа';
$DOF->im('recordbook')->require_access('view_recordbook', $programmsbcid);
 
$DOF->modlib('widgets')->print_heading($DOF->modlib('ig')->igs('you_from_timezone',dof_usertimezone()));

// выводим календарь
$date_picker->display();

// печатаем информацию об уроках, оценках, и посещаемости ученика за неделю
print($recordbook->get_all_data($programmsbcid, $date_from));

//печать подвала
$DOF->modlib('nvg')->print_footer(NVG_MODE_PORTAL);

?>