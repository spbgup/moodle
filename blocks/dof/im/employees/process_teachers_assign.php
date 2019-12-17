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
 * Обработчик формы добавления/удаления предметов к назначению на должность
 */
// Подключаем библиотеки
require_once('lib.php');
//проверяем доступ
if ( $DOF->storage('teachers')->is_access('create') )
{// если есть право покажем форму назначения предметов
    if ( empty($pitem) OR (isset($pitem) AND $pitem->status == 'deleted') )
    {
        $DOF->print_error('programmitem_not_found', null, null, 'im' ,'employees');
    }
    //print_object($_POST);
    $result = true;
    if ( $actionadd )
    {// нужно добавить предмет в список преподаваемых
    	if ( isset($_POST['addselect']) )
    	{// если есть список предметов для добавления
    		$teachers = $DOF->im('employees')->check_add_remove_array($_POST['addselect']);
    		//print_object($teachers);die;
    		if ( ! empty($teachers) )
    		{// список не пустой - нужно что-то сделать
    			foreach ( $teachers as $appointmentid )
    			{// обработаем добавление каждого предмета
    			    $appointment = $DOF->storage('appointments')->get($appointmentid);
    				$teacherdata = new object();
    				$teacherdata->appointmentid  = $appointment->id;
    				$teacherdata->programmitemid = $pitem->id;
    				$teacherdata->departmentid   = $appointment->departmentid;
    				$teacherdata->worktime 		 = required_param('worktime', PARAM_NUMBER);
    				if ( ! $teacherdata->worktime )
    				{// если значение не указано обnullяем значение
    				    $teacherdata->worktime = null;
    				}
    				$create = true;
    				$worktime = 0;
    				if ( $aviteachers = $DOF->storage('teachers')->
    				                          get_records(array('appointmentid'=>$appointment->id,
                                                              'status'=>array('plan', 'active'))) )
    				{
                        // тичеры есть
                        foreach ( $aviteachers as $aviteacher )
                        {// узнаем сколько он уже преподает
                            $worktime += $aviteacher->worktime;
                        }
                    }
    			    // добавим введеное кол-во часов 
    			    $freeworktime = $appointment->worktime - $worktime;
                    $worktime += $teacherdata->worktime;
                    if ( $appointment->worktime < $worktime)
                    {// если лимит указанное время превышает ставку
                        // выведем
                        $eagreement = $DOF->storage('eagreements')->get($appointment->eagreementid);
                        $fullname = $DOF->storage('persons')->get_fullname($eagreement->personid);
                        
                        echo '<p style=" color:red; " align="center"><b>'.
                             $DOF->get_string('not_create_teacher', 'employees',$fullname).' '.
                             $DOF->get_string('limit_excess_worktime', 'employees',$freeworktime).'</b></p>';
                        $create = false;
                    }
    				if ( ! $DOF->storage('teachers')->
    				       is_exists(array('appointmentid'=>$appointment->id,
    				       'programmitemid'=>$pitem->id, 'status'=>'active')) AND 
    			         ! $DOF->storage('teachers')->
                           is_exists(array('appointmentid'=>$appointment->id,
                           'programmitemid'=>$pitem->id, 'status'=>'plan')) AND $create)
    				{//такой подписки нет - 
    				    // производим добавление
                        $newteacherid = $DOF->storage('teachers')->add_teacher($teacherdata);
                        // запоминаем результат добавления в базу
        				$result = $result & $newteacherid;
                        if ( $activate AND $newteacherid )
                        {// запись о преподавании нужно сразу же активировать - и вставка успешно удалась
                            $result = $result & $DOF->workflow('teachers')->change($newteacherid, 'active');
                        }
    				}
    			}
    		}
    	}
    }elseif( $actionremove )
    {// Нужно удалить предмет из списка преподаваемых
    	if ( isset($_POST['removeselect']) )
    	{// если есть список предметов для удаления
    		$teachers = $DOF->im('employees')->check_add_remove_array($_POST['removeselect']);
    		//print_object($teachers);die;
    		if ( ! empty($teachers) )
    		{// список не пустой - нужно что-то сделать
    			foreach ( $teachers as $appointmentid )
    			{// обработаем удаление каждого предмета
    			    $appointment = $DOF->storage('appointments')->get($appointmentid);
    				$result = $result & $DOF->storage('teachers')->
    					remove_programmitem_from_appointment($appointment->id, 
    					$pitem->id);
    			}
    		}
    	}
    }
}
?>