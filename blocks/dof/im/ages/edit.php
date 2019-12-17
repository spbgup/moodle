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
 * Отображает форму добавления и редактирования. 
 * Если передан параметр id, 
 * то отображается редактирование, 
 * если не передан - добавление. 
 */

// Подключаем библиотеки
require_once('lib.php');
require_once('form.php');
$ageid = optional_param('ageid', 0, PARAM_INT);

// добавляем уровень навигации
$DOF->modlib('nvg')->add_level($DOF->get_string('title', 'ages'), 
                               $DOF->url_im('ages','/list.php'),$addvars);
if ( $ageid == 0 )
{//проверяем доступ
    $DOF->storage('ages')->require_access('create');
    // добавляем уровень навигации
    $DOF->modlib('nvg')->add_level($DOF->get_string('newages', 'ages'), 
                                   $DOF->url_im('ages','/edit.php?ageid='.$ageid),$addvars);
}else
{//проверяем доступ
    $DOF->storage('ages')->require_access('edit', $ageid);
    // добавляем уровень навигации
    $DOF->modlib('nvg')->add_level($DOF->get_string('editage', 'ages'), 
                                   $DOF->url_im('ages','/edit.php?ageid='.$ageid),$addvars);
}
    

if ( $DOF->storage('ages')->is_exists($ageid) OR  $ageid === 0 )
{
    // загружаем форму
    $form = $DOF->im('ages')->form($ageid);
        
    $error = '';
    //подключаем обработчик формы
    include($DOF->plugin_path('im','ages','/process_form.php'));
}

//печать шапки страницы
$DOF->modlib('nvg')->print_header(NVG_MODE_PORTAL);

if ( ! $DOF->storage('ages')->is_exists($ageid) AND $ageid != 0 )
{// если период не найден, выведем ошибку
    print_error($DOF->get_string('notfoundage','ages'));
}


echo $error;

if ( $ageid == 0 AND ! $DOF->storage('config')->get_limitobject('ages',$addvars['departmentid']) )
{
        $link =  '<span style="color:red;">'.$DOF->get_string('limit_message','ages').'</span>';
        echo '<br>'.$link;     
}else 
{
    // печать формы
    $form->display();
}    

//печать подвала
$DOF->modlib('nvg')->print_footer(NVG_MODE_PORTAL);

?>