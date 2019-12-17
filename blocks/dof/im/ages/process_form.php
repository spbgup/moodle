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


if ( $ageid == 0 )
{//проверяем доступ
    $DOF->storage('ages')->require_access('create');
}else
{//проверяем доступ
    $DOF->storage('ages')->require_access('edit', $ageid);
}
// создаем путь на возврат
$path = $DOF->url_im('ages','/list.php',$addvars);
if ( $form->is_cancelled() )
{//ввод данных отменен - возвращаем на страницу просмотра периода
    redirect($path);
}elseif ( $form->is_submitted() AND confirm_sesskey() AND $formdata = $form->get_data() )
{//даные переданы в текущей сессии - получаем
    
    //print_object($formdata);    
    // создаем объект для сохранения в БД
    $age = new object;
    if ( $formdata->begindate > $formdata->enddate )
    {// дата начала больше даты конца - выведем сообщение
        $error .= '<br>'.$DOF->get_string('errorbeginenddate','ages').'<br>';
    }else
    {
        $age->begindate = $formdata->begindate;
        $age->enddate = $formdata->enddate;
        $age->name = $formdata->name;
        $age->eduweeks = $formdata->eduweeks;
        if (isset($formdata->departprevious))
        {// подразделение редактировалось
            $age->departmentid = $formdata->departprevious[0];
            if ( $formdata->departprevious[1] <> 0)
            {// у подразделения уже были предыдущие периоды
                $age->previousid = $formdata->departprevious[1];
            }else
            {// предыдущих периодов нет
                $age->previousid = null;
            }
        }else
        {// подразделение не редактировалось
            $age->departmentid = $formdata->departmentid;
            if ( $formdata->previous <> 0)
            {// у подразделения уже были предыдущие периоды
                $age->previousid = $formdata->previous;
            }else
            {// предыдущих периодов нет
                $age->previousid = null;
            }
        }
        if ( $formdata->ageid )
        {// период редактировался - обновим запись в БД
            if ( $DOF->storage('ages')->update($age,$formdata->ageid) )
            {
                $addvars['ageid'] = $formdata->ageid;
                redirect($DOF->url_im('ages','/view.php',$addvars));
            }else
            {
                $error .= '<br>'.$DOF->get_string('errorsaveage','ages').'<br>';
            }
        }else
        {// период создавался
            // сохраняем запись в БД
            if( $id = $DOF->storage('ages')->create_period_for_department($age->departmentid,$age->begindate,
                                        $age->enddate,$age->eduweeks,$age->name,$age->previousid) )
            {// все в порядке - сохраняем статус и возвращаем на страниу просмотра периода
                $DOF->workflow('ages')->init($id);
                $addvars['ageid'] = $id;
                redirect($DOF->url_im('ages','/view.php',$addvars));
            }else
            {// период выбран неверно - сообщаем об ошибке
                $error .=  '<br>'.$DOF->get_string('errorsaveage','ages').'<br>';
            }
        }
    }
}
?>