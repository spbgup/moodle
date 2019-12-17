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
if ( $agenumform->is_submitted() AND $agenumform->is_validated() AND $formdata = $agenumform->get_data() )
{// данные отправлены в форму, и не возникло ошибок
    //print_object($formdata);
    $sbc = new object;
    $sbc->agenum = $formdata->agenum;
    if ( $DOF->storage('programmsbcs')->update($sbc, $formdata->id) )
    {// удалось изменить парралель
        $message .= '<p style=" color:green; "><b>'.$DOF->get_string('agenum_change_success', 'programmsbcs').'</b></p>';
    }else
    {// не удалось изменить парралель
        $message .= '<p style=" color:red; "><b>'.$DOF->get_string('agenum_change_failure', 'programmsbcs').'</b></p>';
    }
}

?>