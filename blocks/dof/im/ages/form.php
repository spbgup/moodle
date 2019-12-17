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
 * Здесь происходит объявление класса формы, 
 * на основе класса формы из плагина modlib/widgets. 
 * Подключается из init.php. 
 */

// Подключаем библиотеки
require_once('lib.php');
// подключаем библиотеку форм
$DOF->modlib('widgets')->webform();

class dof_im_edit extends dof_modlib_widgets_form
{
    private $age;
    protected $dof;
    
    function definition()
    {// делаем глобальные переменные видимыми

        $this->age = $this->_customdata->age;
        $this->dof = $this->_customdata->dof;

        // создаем ссылку на HTML_QuickForm
        $mform =& $this->_form;
       
        $mform->addElement('hidden','ageid', $this->age->id);
        $mform->setType('ageid', PARAM_INT);
        $mform->addElement('hidden','departmentid', optional_param('departmentid', 0, PARAM_INT));
        $mform->setType('departmentid', PARAM_INT);
        $mform->addElement('hidden','sesskey', 0);
        $mform->setType('sesskey', PARAM_ALPHANUM);
        //создаем заголовок формы
        $mform->addElement('header','formtitle', $this->get_form_title($this->age->id));
        // имя периода
        $mform->addElement('text', 'name', $this->dof->get_string('name','ages').':', 'size="20"');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name',$this->dof->get_string('agename_required', 'ages'), 'required',null,'client');
        // даты
        $mform->addElement('date_selector', 'begindate', $this->dof->get_string('begindate','ages').':',$this->get_year($this->age->begindate));
        $mform->addElement('date_selector', 'enddate', $this->dof->get_string('enddate','ages').':',$this->get_year($this->age->enddate));
        // количество недель
        $mform->addElement('text', 'eduweeks', $this->dof->get_string('eduweeks','ages').':', 'size="4"');
        $mform->addRule('eduweeks','Error', 'numeric',null,'client');
        $mform->setType('eduweeks', PARAM_INT);
        // структурное подразделение
        $department = $this->dof->storage('departments')->departments_list_subordinated(null,'0', null,true);
        // оставим в списке только те объекты, на использование которых есть право
        $permissions = array(array('plugintype'=>'storage', 'plugincode'=>'departments', 'code'=>'use'));
        $department = $this->dof_get_acl_filtered_list($department, $permissions);

        $previous =& $mform->addElement('hierselect', 'departprevious', $this->dof->get_string('departprevious','ages').':',null,'<br>');
        $previous->setMainOptions($department);
        $previous->setSecOptions($this->get_list_previous($department));
        $mform->setDefault('departprevious',array($this->age->departmentid, $this->age->previousid));
        // применение проверки ко всем элементам
        $mform->applyFilter('__ALL__', 'trim');
        // кнопоки сохранить и отмена
        $this->add_action_buttons(true, $this->dof->get_string('to_save','ages'));
    }

    /*
     * Проверка на стороне сервера
     * 
     */
    function validation($data,$files)
    {
        $errors = array();
        // создание
        if ( ! $this->dof->storage('config')->get_limitobject('ages',$data['departprevious'][0] ) AND $data['ageid'] == 0 )
        {
            $errors['departprevious'] = $this->dof->get_string('limit_message','ages');
        }
        // редактирование
        if ( $data['ageid'] )
        {
            $depid = $this->dof->storage('ages')->get_field($data['ageid'], 'departmentid');
            if ( ! $this->dof->storage('config')->get_limitobject('ages',$data['departprevious'][0] ) AND $depid != $data['departprevious'][0]  )
            {
                $errors['departprevious'] = $this->dof->get_string('limit_message','ages');
            }
        }            
        // возвращаем ошибки, если они возникли
        return $errors;
    }
    
    /** Возвращает двумерный массив подразделений и периодов
     * @param array $department - список подразделений
     * @return array список периодов, массив(id подразделения=>id периода=>название периода)
     */
    private function get_list_previous($department)
    {
        $previous = array();
        if ( ! is_array($department) )
        {//получили не массив - это значит что в базен нет ни одного подразделения
            return $previous;
        }
        foreach ($department as $key => $value)
        {// забиваем массив данными    
            $previous[$key] = $this->get_list_ages($key);
        }
        return $previous;
    }
    
    /** Возвращает массив периодов для текущего структурного подразделения
     * @param int $departmentid - id подразделения
     * @return array список периодов, массив(id периода=>название)
     */
    private function get_list_ages($departmentid)
    {
    	$rez = array();
        $params = array();
        $params['departmentid'] = $departmentid;
        $params['status'] = $this->dof->workflow('ages')->get_meta_list('real');
    	$ages = $this->dof->storage('ages')->get_records(array('departmentid'=>$departmentid));
    	if ( ! is_array($ages) )
        {//получили не массив - это значит, что в базе пока нет ни одного периода
            // выведем слово "нет"
            return array(0 => $this->dof->get_string('no', 'ages'));
        }
        foreach ( $ages as $age )
        {// забиваем массив данными
            if ( ! $this->dof->storage('ages')->get_next_ageid($age->id,2)  )
            {// занесем только те периоды, у которых нет последующих
                if ($age->id <> $this->age->id )
                {// себя родимого невключаем
                    $rez[$age->id] = $age->name;
                }
            }
        }
        if ( isset($this->age->previousid) )
        {// если уже был предыдущий период, выставим его по умолчанию
            $rez[$this->age->previousid] = $this->dof->storage('ages')->get_field($this->age->previousid,'name');
        }
        // оставим в списке только те объекты, на использование которых есть право
        $permissions = array(array('plugintype'=>'storage', 'plugincode'=>'ages', 'code'=>'use'));
        $rez = $this->dof_get_acl_filtered_list($rez, $permissions);
        // сортируем в алфавитном порядке
        asort($rez);
        $rez = array(0 => $this->dof->get_string('no', 'ages')) + $rez;
        return $rez;
    }
    
    /**
     * Возвращает строку заголовка формы
     * @param int $ageid
     * @return string
     */
    private function get_form_title($ageid)
    {
        if ( ! $ageid )
        {//заголовок создания формы
            return $this->dof->get_string('newages','ages');
        }else 
        {//заголовок редактирования формы
            return $this->dof->get_string('editage','ages');
        }
        
    }
    
    /**
     * Возвращает год для
     * @param $date
     * @param $begin
     * @param $new
     * @return integer
     */
    private function get_year($date, $new=true)
    {
        $dateform = array();
        $dateform['startyear'] = dof_userdate($date-1*365*24*3600,'%Y');
        $dateform['stopyear']  = dof_userdate($date+10*365*24*3600,'%Y');
        $dateform['optional']  = false;
        return $dateform;
    }
    
    /**
     * Возвращает имя подразделения
     * @param $id
     * @return unknown_type
     */
    private function get_department_name($id)
    {
        return $this->dof->storage('departments')->get_field($id,'name').' ['.
               $this->dof->storage('departments')->get_field($id,'code').']';
    }
    
    /**
     * Возвращает имя предыдущего периода
     * @param $id
     * @return unknown_type
     */
    private function get_previous_agename($id)
    {
        return $this->dof->storage('ages')->get_field($id,'name');
    }
    
    /**
     * Возврашает название статуса
     * @return unknown_type
     */
    private function get_status_name($status)
    {
        return $this->dof->workflow('ages')->get_name($status);
    }
    /**
     * Возвращает id подразделения 
     * для которого создается период
     * @return unknown_type
     */
    private function get_department()
    {
        global $USER;
        //@TODO когда будет готово - вставить сюда определение 
        //приписки пользователя к департаменту
        if ($person = $this->dof->storage('persons')->get_by_moodleid($USER->id))
        {// если пользователь закреплен за конкретным подразделением
            // выведем название подразделения
            return $person->departmentid;
        }else
        {// не закреплен, но имеет соответствующие права - зарегестрируем его
            $person = $this->dof->storage('persons')->get_by_moodleid($USER->id, true);
            return $person->departmentid;
        }        
    }
}

/** Класс, отвечающий за форму смену статуса учебного периода вручную
 * 
 */
class dof_im_ages_changestatus_form extends dof_modlib_widgets_changestatus_form
{
    /**
     * @var dof_control
     */
    protected $dof;
    
    protected function im_code()
    {
        return 'ages';
    }
    
    protected function workflow_code()
    {
        return 'ages';
    }
    
     /** Дополнительные проверки и действия в форме смены статуса 
     * (переопределяется в дочерних классах, если необходимо)
     * @param object $formdata - данные пришедние из формы
     * 
     * @return bool
     */
    protected function dof_custom_changestatus_checks($formdata, $result=true)
    {
        if ( ! $result )
        {// если не удалось сменить статус - то возможно это из-за учителей
            if ( $cstreams = $DOF->storage('cstreams')->get_records(array('ageid'=>$formdata->id)) )
            {// если у периода есть потоки
                foreach ( $cstreams as $cstream )
                {
                    if ( $cstream->teacherid == 0 )
                    {// не указан учитель потока
                        $message = '<div style="color:red;"><b>'.
                                    $DOF->get_string('no_teacher', $this->im_code()).'</b></div>';
                        $link = $this->dof->url_im('cstreams'. '/view.php', array('cstreamid' => $cstream->id));
                        $message .= '<a href="'.$link.'">'.$cstream->name.'</a>';
                        $mform->addElement('static', 'noteacher'.$cstream->id, '', $message);
                    }
                }
            }
            
            return false;
        }
        return true;
    }
}

/** Форма с кнопкой пересинхронизации всех cpassed за период
 * @todo в сообщении выводить когда было добавлено задание на пересинхронизацию
 * @todo выводить когда была последняя пересинхронизация
 * @todo добавить notice_yesno после нажатии на кнопку
 * @todo Переместить объявление кнопки в definition_after_data чтобы она всегда отражала актуальные изменения в базе
 */
class dof_im_ages_resync_form extends dof_modlib_widgets_form
{
    /**
     * @param int - id учебного периода в таблице ages
     */
    protected $id;
    
    protected function im_code()
    {
        return 'ages';
    }

    public function definition()
    {
        GLOBAL $DB;
        $mform =& $this->_form;
        $this->dof = $this->_customdata->dof;
        if ( ! $this->id  = $this->_customdata->id )
        {// не можем отобразить форму без периода
            $this->dof->print_error('err_age_not_exists', $this->im_code());
        }
        
        $mform->addElement('hidden', 'id', $this->id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'sesskey', sesskey());
        $mform->setType('sesskey', PARAM_ALPHANUM);
        
        //создаем заголовок формы
        $mform->addElement('header','formtitle', $this->dof->get_string('resync',$this->im_code()));
        
        //  Получаем список неисполненных заданий
        // @todo на текущий момент в ядре нет нормального API для работы с таблицей todo
        // поэтому здесь используется прямое обращение к get_records_select
        $mform->addElement('static', 'resync_notice', '', $this->dof->get_string('resync_notice',$this->im_code()));
        if ( $DB->get_records_select('block_dof_todo'," exdate=0  AND plugintype='storage' AND 
                 plugincode='cpassed' AND todocode='resync_age_cpassed' AND intvar=".$this->id) )
        {// в базе уже есть добавленное необработанное задание на пересинхронизацию всех потоков курса
            // не показывем кнопку, чтобы нельзя было добавить одно задание несколько раз, и перегрузить систему
            $mform->addElement('static', 'resync', '', 
                        $this->dof->get_string('resync_task_added',$this->im_code()));
        }else
        {// задание еще не добавлено - показываем кнопку
            $mform->addElement('submit', 'save', $this->dof->get_string('resync_cstreams',$this->im_code()));
        }
        
        // Кнопкa АКТИВАЦИИ ВСЕХ cpassed этого периода
        $mform->addElement('static', 'active_notice', '', $this->dof->get_string('active_notice',$this->im_code()));
        if ( ! $DB->get_records_select('block_dof_todo'," exdate=0  AND plugintype='storage' AND 
                 plugincode='cpassed' AND todocode='suspend_to_active_cpassed' AND intvar=".$this->id) )
        {// в базе уже есть добавленное необработанное задание на пересинхронизацию всех потоков курса
            // не показывем кнопку, чтобы нельзя было добавить одно задание несколько раз, и перегрузить систему
            $mform->addElement('submit', 'sus_go', $this->dof->get_string('suspend_go',$this->im_code()));
        }else 
        {// в базе уже есть добавленное необработанное задание на пересинхронизацию всех потоков курса
            // не показывем кнопку, чтобы нельзя было добавить одно задание несколько раз, и перегрузить систему
            $mform->addElement('static', 'active', '', 
                        $this->dof->get_string('active_suspend',$this->im_code(),$this->dof->get_string('suspend_go',$this->im_code())) );
        }
        // Кнопкa ПРИОСТАНОВКИ ВСЕХ cpassed этого периода  
        $mform->addElement('static', 'stop_notice', '', $this->dof->get_string('stop_notice',$this->im_code()));      
        if ( ! $DB->get_records_select('block_dof_todo'," exdate=0  AND plugintype='storage' AND 
                 plugincode='cpassed' AND todocode='active_to_suspend_cpassed' AND intvar=".$this->id) )
        {// в базе уже есть добавленное задание 
            // не показывем кнопку, чтобы нельзя было добавить одно задание несколько раз, и перегрузить систему
            $mform->addElement('submit', 'act_stop', $this->dof->get_string('active_stop',$this->im_code()));
        }else 
        {// в базе уже есть добавленное задание
            // не показывем кнопку, чтобы нельзя было добавить одно задание несколько раз, и перегрузить систему
            $mform->addElement('static', 'stop', '', 
                        $this->dof->get_string('active_suspend',$this->im_code(),$this->dof->get_string('active_stop',$this->im_code())) );
        }
        
    }
    
    /** Обработчик формы
     * 
     */
    public function process()
    {
        $mform =& $this->_form;
        
        if ( $formdata = $this->get_data() AND $this->dof->is_access('manage') AND confirm_sesskey() )
        {
             if ( isset($formdata->save) )
             {
                 return $this->dof->add_todo('storage', 'cpassed', 'resync_age_cpassed', $formdata->id, null, 2, time());
             }
             // запуск всех приостановленных
             if ( isset($formdata->sus_go) )
             {
                 return $this->dof->add_todo('storage', 'cpassed', 'suspend_to_active_cpassed', $formdata->id, null, 2, time());
             }
             // остановка всех активных
             if ( isset($formdata->act_stop) )
             {
                 return $this->dof->add_todo('storage', 'cpassed', 'active_to_suspend_cpassed', $formdata->id, null, 2, time());
             }
        }
        
        return true;
    }
}
?>