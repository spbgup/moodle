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
$DOF->modlib('nvg')->add_level($DOF->get_string('title'), $DOF->url_im('standard','/index.php', $addvars));
$DOF->modlib('nvg')->add_level($DOF->get_string('title', 'departments'), $DOF->url_im('departments','/index.php'), $addvars);
// id подразделения в котором мы сейчас находимся
$departmentid = optional_param('departmentid', 0, PARAM_INT);
// id подразделения, которое редактируется, или 0 если подразделение создается
$id = required_param('id', PARAM_INT);
// id родительского подразделения по умолчанию
$leaddepid = optional_param('leaddepid', $departmentid, PARAM_INT);


if ( ! $id )
{// проверяем право создавать подразделение в другом подразделении (или в корне системы)
    $DOF->storage('departments')->require_access('create', null, null, $leaddepid);
}else 
{// право редактировать подразделение
    $DOF->storage('departments')->require_access('edit', $id);
}    

if ( $DOF->storage('departments')->is_exists($id) OR ! $id )
{// если подразделение есть или id подразделения не передано
    // загружаем форму
    
    $form = $DOF->im('departments')->form($id, $departmentid);
    // добавляем уровень навигации
    if ( $departmentid == 0 )
    {
        $DOF->modlib('nvg')->add_level($DOF->get_string('newdepartment', 'departments'),
            $DOF->url_im('departments','/edit.php'), $addvars);
    }else
    {
        $DOF->modlib('nvg')->add_level($DOF->get_string('editdepartment', 'departments'),
            $DOF->url_im('departments','/edit.php'), $addvars);
    }
    
}else
{// если подразделение не ненайдено, выведем ошибку
    $errorlink = $DOF->url_im('departments','',$addvars);
    $DOF->print_error('notfound',$errorlink,null,'im','departments');
}

$error = '';
//подключаем обработчик формы
include($DOF->plugin_path('im','departments','/process_form.php'));
//вывод на экран

//печать шапки страницы
$DOF->modlib('nvg')->print_header(NVG_MODE_PORTAL);

echo $error;

// печать формы
$form->display();

//печать подвала
$DOF->modlib('nvg')->print_footer(NVG_MODE_PORTAL);

?>