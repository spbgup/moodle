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

/**
 * отображает одну запись по ее id 
 */

// Подключаем библиотеки
require_once('lib.php');
// Подключаем формы
require_once($DOF->plugin_path('im', 'ages', '/form.php'));
// получаем id просматриваемого периода
$ageid = required_param('ageid', PARAM_INT);
//проверяем доступ
$DOF->storage('ages')->require_access('view',$ageid);

// создаем оъект данных для формы
$customdata = new object();
$customdata->dof = $DOF;
// объявляем форму смены статуса 
$statusform = new dof_im_ages_changestatus_form($DOF->url_im('ages', '/view.php?ageid='.$ageid,$addvars), $customdata);

// добавляем данные периода
$dataobj = new object();
$dataobj->id = $ageid;
// обрабатываем данные
$statusform->process();
// устанавливаем значения по умолчанию
$statusform->set_data($dataobj);

$customdata->id = $ageid;
// объявляем форму пересинхронизации потоков дисциплины
$resyncform = new dof_im_ages_resync_form($DOF->url_im('ages', '/view.php?ageid='.$ageid,$addvars), $customdata);
$resyncform->process();
// переобъявляем, чтоб корректно отображалась кнопка пересинхронизации
$resyncform = new dof_im_ages_resync_form($DOF->url_im('ages', '/view.php?ageid='.$ageid,$addvars), $customdata);

//вывод на экран
//добавление уровня навигации
$DOF->modlib('nvg')->add_level($DOF->get_string('title', 'ages'), 
                               $DOF->url_im('ages','/list.php'),$addvars);
if ( $DOF->storage('ages')->is_exists($ageid) )
{
    $DOF->modlib('nvg')->add_level($DOF->storage('ages')->get_field($ageid, 'name'),
                                   $DOF->url_im('ages','/view.php?ageid='.$ageid,$addvars));
}else
{
    $DOF->modlib('nvg')->add_level($DOF->modlib('ig')->igs('error'),'');
}
//печать шапки страницы
$DOF->modlib('nvg')->print_header(NVG_MODE_PORTAL);

if ( ! $age = $DOF->im('ages')->show_id($ageid,$addvars) )
{// если период не найден, выведем ошибку
	print_error($DOF->get_string('notfoundage','ages'));
}
// ссылка на создание периода
if ( $DOF->storage('ages')->is_access('create') )
{// если есть право создавать период
    if ( $DOF->storage('config')->get_limitobject('ages',$addvars['departmentid']) )
    {
        $link = '<a href='.$DOF->url_im('ages','/edit.php',$addvars).'>'.
        $DOF->get_string('newages', 'ages').'</a>';
        echo '<br>'.$link;
    }else 
    {
        $link =  '<span style="color:silver;">'.$DOF->get_string('newages', 'ages').
        	' ('.$DOF->get_string('limit_message','ages').')</span>';
        echo '<br>'.$link; 
    } 
}
// вывод информации о периоде
echo '<br>'.$age;

if ( $DOF->workflow('ages')->is_access('changestatus',$ageid) )
{// если у пользователя есть полномочия вручную изменять статус - то покажем ему форму для этого
    // показываем форму
    $statusform->display();
}
if ( $DOF->im('ages')->is_access('manage') )
{// показываем форму пересинхронизации только пользователям с правами manage
    $resyncform->display();
}

//печать подвала
$DOF->modlib('nvg')->print_footer(NVG_MODE_PORTAL);
?>