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
// подключаем библиотеку форм
$DOF->modlib('widgets')->webform();

/** Класс формы редактирования временной 
 * зоны персон всего подразделения
 */
class dof_im_persons_edit_timezone extends dof_modlib_widgets_form
{
    /*
     * Строим форму
     */
    function definition()
    {
        $this->dof  = $this->_customdata->dof;
        $this->depid = $this->_customdata->depid;
        // создаем ссылку на HTML_QuickForm
        $mform =& $this->_form;
        
        $mform->addElement('header','headername', '');
        // примечение
        $mform->addElement('static', 'staticname', $this->dof->get_string('notice','persons').':', $this->dof->get_string('notice_ans','persons'));
        // выбор подразделения
        $departments = $this->dof->storage('departments')->departments_list_subordinated(null,0,null,true);
        $mform->addElement('select','depart',$this->dof->get_string('select_depart','persons'),$departments);
        $mform->setDefault('depart',$this->depid);
        // выбор временной зоны
        $UTC = dof_get_list_of_timezones();
        $mform->addElement('select','timezone',$this->dof->get_string('select_time_zone','persons'),$UTC);
        // по умолчанию время на сервере
        $mform->setDefault('timezone','99');
        // сохранить
        $mform->addElement('dof_single_use_submit', 'save', $this->dof->modlib('ig')->igs('save'));
        
    }    
    
     /**
     * Задаем проверку корректности введенных значений
     * 
     * return array $error - массив с ошибками, если таковы есть
     */
    function validation($data,$files)
    {
        $error = array();
        if ( empty($data['depart']) )
        {
            $error['depart'] = $this->dof->get_string('select_depart','persons');
        }
        
        return $error;
    }   
    
    
    /* Метод обработки(сохранеия)
     * данных из формы
     * 
     * @param array $addvars - массив с доп данными(подразделение)
     */
    function process($addvars)
    {
        if ( $formdata = $this->get_data() )
		{// нажали сохранить
		    // выберем всех персон деканата этого подразделения
		    // которые синхронизированы с moodle
		    // во избежения зависания системы - берем по 100 человек
		    dof_hugeprocess();
            $num = 0;
            $flag = true;
            while ( $list = $this->dof->storage('persons')->get_records(
                array('departmentid'=>$formdata->depart,'sync2moodle'=>'1'),'','id,mdluser', $num, 100) )
            {
                $num +=100;
                foreach ($list as $obj)
                {// меняем время на выбранное
                    $objmdluser = new object;
                    $obj->id = $obj->mdluser;
                    $obj->timezone = $formdata->timezone;
                    if ( ! $this->dof->modlib('ama')->user(false)->is_exists($obj->mdluser) )
                    {// если пользователя не существует - то мы не сможем его вернуть
                        continue;
                    }
                    $flag = $flag AND $this->dof->modlib('ama')->user($obj->mdluser)->update($obj);
                }   
                          
            }
    		if ( $flag )
            {
                return $this->dof->modlib('widgets')->success_message($this->dof->modlib('ig')->igs('data_save_success')).
                       "<div align='center'><a href=".$this->dof->url_im('persons',"/list.php",$addvars).">".
                       $this->dof->modlib('ig')->igs('back')."</a></div>";
            }
            return $this->dof->modlib('widgets')->error_message($this->dof->modlib('ig')->igs('data_save_failure'));    
		} 
		return '';
    }
    
}



?>