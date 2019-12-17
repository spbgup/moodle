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
/*
 * Описание файла
 */
require_once('lib.php');
// получаем id контрольной точки в планировании, для которой редактируются оценки
$planid = required_param('planid',PARAM_INT);

$DOF->modlib('nvg')->print_header(NVG_MODE_PORTAL);

// получаем и проверяем данные
$checkdata = new dof_im_journal_process_gradesform($DOF, $_POST);
if ( data_submitted() AND confirm_sesskey() )
{//сохранение оценок и присутствия на занятиях
    if ( ! $checkdata->process_form() )
    {// Если данные не удалось сохранить по какой-либо причине
        print '<br />';
        notice($DOF->get_string('error_data_not_saved', 'journal'), 
               $DOF->url_im('journal', '/group_journal/index.php', 
                            array_merge(array('csid'=> $checkdata->csid),$addvars)) );

    }
}
$DOF->modlib('nvg')->print_footer(NVG_MODE_PORTAL);
?>