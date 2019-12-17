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


if ( $agroupid == 0 )
{//проверяем доступ
    $DOF->storage('agroups')->require_access('create');
}else
{//проверяем доступ
    $DOF->storage('agroups')->require_access('edit', $agroupid);
}
// создаем путь на возврат
$path = $DOF->url_im('agroups','/list.php',$addvars);
if ( $form->is_cancelled() )
{//ввод данных отменен - возвращаем на страницу просмотра класса
    redirect($path);
}elseif ( $form->is_submitted() AND confirm_sesskey() AND $formdata = $form->get_data() )
{//даные переданы в текущей сессии - получаем
    // print_object($formdata);    
    // создаем объект для сохранения в БД
    $agroup = new object;
    $agroup->name         = trim($formdata->name);
    $agroup->code         = trim(mb_strtolower($formdata->code,'utf-8'));
    $agroup->departmentid = $formdata->department;
    $agroup->programmid   = $formdata->progages[0]; // программа и номер периода уcтанавливаются через hierselect
    if ( isset($formdata->progages[1]) )
    {
        $agroup->agenum   = $formdata->progages[1];
    }else
    {
        $agroup->agenum   = 0;
    }
    $agroup->salfactor = $formdata->salfactor;
    // создали метаконтракт - запишем в объект
    $agroup->metacontractid = $DOF->storage('metacontracts')
            ->handle_metacontract($formdata->metacontract,$formdata->department);
    
    if (isset($formdata->agroupid) AND $formdata->agroupid )
    {// класс редактировался - обновим запись в БД
        unset($formdata->agenum);
        if ( $DOF->storage('agroups')->update($agroup, $formdata->agroupid) )
        {
            redirect($DOF->url_im('agroups','/view.php?agroupid='.$formdata->agroupid,$addvars));
        }else
        {
            $error .= '<br>'.$DOF->get_string('errorsaveagroup','agroups').'<br>';
        }
    }else
    {// класс создавался
        // сохраняем запись в БД
        if( $id = $DOF->storage('agroups')->insert($agroup) )
        {// все в порядке - сохраняем статус и возвращаем на страниу просмотра класса
            $DOF->workflow('agroups')->init($id);
            redirect($DOF->url_im('agroups','/view.php?agroupid='.$id,$addvars));
        }else
        {// класс выбран неверно - сообщаем об ошибке
            $error .=  '<br>'.$DOF->get_string('errorsaveagroup','agroups').'<br>';
        }
    }
}
?>