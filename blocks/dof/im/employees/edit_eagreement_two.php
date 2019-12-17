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
// подключаем библиотеки верхнего уровня
require_once('lib.php');
require_once('form.php');
// получаем id должности, которую будем редактировать
$id = required_param('id', PARAM_INT);
//проверяем доступ
$DOF->storage('eagreements')->require_access('edit',$id);

$DOF->modlib('nvg')->add_level($DOF->get_string('title', 'employees'),
    $DOF->url_im('employees','/list.php', $addvars));
// добавляем уровень навигации - заголовок "редактирование должности"
$DOF->modlib('nvg')->add_level($DOF->get_string('edit_eagreement', 'employees'),
    $DOF->url_im('employees','/edit_eagreement_two.php?id='.$id,$addvars));
if( ! $eagreement = $DOF->storage('eagreements')->get($id) )
{// в базе нет такой записи
    $DOF->print_error($DOF->get_string('appointment_not_found', 'employees', $id));
}
// регион по умолчанию берем из настроек
$defaultdepartment = $DOF->storage('contracts')->get_field($id, 'departmentid');
$defaultregion = $DOF->storage('config')->get_config('defaultregion', 'im', 'sel', $defaultdepartment);
if ( isset($defaultregion->value) )
{
    $defaultregion = $defaultregion->value;
}else
{
    $defaultregion = 0;
} 

$error = '';
$errordischarge='';
$customdata = new object();
$customdata->dof = $DOF;
$customdata->id = $id;

// Устанавливаем значения по умолчанию
$customdata->id = $id;
$customdata->seller = false;
$customdata->edit_student = true;
$customdata->addressid = 0;
$customdata->personid = 0;
if ( $id )
{// если id контракта указано
    //выставим нулевые значения
    $default['dateofbirth'] = -1893421800;
    $default['passportdate'] = 0;
    $default['addrcountry'] = array('RU', $defaultregion);
    if ( $eagreement->personid <> 0 )
    {// если студент указан в договоре
        // проверим ученика на права
        // @todo - вставить проверку на принадлежность к другим контрактам
        if ( $DOF->storage('eagreements')->is_personel($eagreement->personid, $id, 'fdo') )
        {// если он уже учавствует в других контрактах 
            // или является учитилем или админом - редактировать нельзя 
            $customdata->edit_student = false;
        }
        // найдем студента
        $person = get_object_vars($DOF->storage('persons')->get($eagreement->personid));
        $customdata->personid = $person['id'];
        unset($person['id']);
        // установим значения по умолчанию для студента
        foreach ($person as $key=>$value)
        {// добавим к ним префикс st
            $default["{$key}"] = $value;
        }
        if ( isset($person['passportaddrid']) AND 
                   $addrstudent = get_object_vars($DOF->storage('addresses')->get($person['passportaddrid'])) )
        {// если существует адрес у студента - установим по умолчанию и его
            $customdata->addressid = $person['passportaddrid'];
            // выставим значения для hierselectа
            $default['addrcountry'] = array($addrstudent['country'],$addrstudent['region']);
            // удалим чтобы не конфликтовали с hierselectом
            unset($addrstudent['country']);
            unset($addrstudent['region']);
            // установим поля адреса по умолчанию студенту
            foreach ($addrstudent as $key=>$value)
            {// добавим к ним префикс staddr
                $default["addr{$key}"]= $value;
            }
        }
    }else
    {// если студент в договоре не указан - значит он создается
        $customdata->person = 'new';
    }
}else
{// id контракта не указано
    $default->date = time();
    $default->dateofbirth = -1893421800;
    $default->addrcountry = array('RU', $defaultregion);
    $eagreement->person->passportdate = 0;
}
// установим значения по умолчанию для подписки
if ( $DOF->storage('eagreements')->count_list(array('id'=>$id)) >= 2 )
{// подписок много - просто выведем их списком
    $customdata->countappoint = true;
}else
{// подписка одна или вообще нет - будем создавать/редактировать
   $customdata->countappoint = false;
   $customdata->appointment = new stdClass;
   $customdata->appointment->id = 0;
   if ( $appointment = $DOF->storage('appointments')->get_record(array('eagreementid'=>$id)) )
   {// если подписка есть - редактируем ее
       // ставим значения по умолчанию
       $default['appoint'] = 1;
       $default['schpositionid'] = $appointment->schpositionid;
       $default['enumber'] = $appointment->enumber;
       $default['worktime'] = $appointment->worktime;
       $default['date'] = $appointment->date;
       $customdata->appointment = $appointment;
   }
}

if ( $id AND $eagreement->status == 'canceled' )
{// удаленный договор нельзя редактировать
    $form = new dof_im_employees_eagreement_edit_form_two_page(
        $DOF->url_im('employees', '/edit_eagreement_two.php?id='.$id,$addvars), $customdata, 'post', null, null, false);
    // устанавливаем данные по умолчанию
    $form->set_data($default);
}else
{// остальные договоры редактировать можно
    // создаем объект формы
    $form = new dof_im_employees_eagreement_edit_form_two_page(
        $DOF->url_im('employees', '/edit_eagreement_two.php?id='.$id,$addvars), $customdata);
    // устанавливаем данные по умолчанию
    $form->set_data($default);
    $form->process();
}




//печать шапки страницы
$DOF->modlib('nvg')->print_header(NVG_MODE_PORTAL);
print '<br>'.$error.'<br>';
// отображаем форму
$form->display();

//печать подвала
$DOF->modlib('nvg')->print_footer(NVG_MODE_PORTAL);

?>