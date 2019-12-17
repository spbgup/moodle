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

/** Форма создания/редактирования шаблона урока
 * 
 */
class dof_im_acl_edit_acl_form extends dof_modlib_widgets_form
{
    /**
     * @var dof_control
     */
    protected $dof;
    
    protected function im_code()
    {
        return 'acl';
    }
    
    protected function storage_code()
    {
        return 'acl';
    }
    
    protected function workflow_code()
    {
        return $this->storage_code();
    }
    
    /**
     * @see parent::definition()
     */
    public function definition()
    {
        $this->dof = $this->_customdata->dof;
        // id учебного потока (если расписание создается для потока)
        
        // создаем ссылку на HTML_QuickForm
        $mform =& $this->_form;

        //создаем заголовок формы
        $mform->addElement('header','formtitle', $this->dof->get_string('new_acl',$this->im_code()));
        
        // добавляем новый элемент в форму
        $myselect =& $mform->addElement('hierselect', 'plugin', $this->dof->get_string('plugintype',$this->im_code()).
        										'<br>'.$this->dof->get_string('plugincode',$this->im_code()) ,null,'<br>');
        // устанавливаем для него варианты ответа
        $select1 = array( 'im' => 'im', 
        				  'storage' => 'storage',
                          'workflow' => 'workflow', 
                          'sync' => 'sync', 
                          'modlib' => 'modlib', 
                          'core' => 'core');
        $select2 = $this->get_list_previous($select1);
        $myselect->setOptions(array($select1, $select2));
        
        // код задания
        $mform->addElement('text', 'code', $this->dof->get_string('code',$this->im_code()));
        $mform->setType('code', PARAM_TEXT);    
        // код задания
        $mform->addElement('text', 'objectid', $this->dof->get_string('objectid',$this->im_code()));
        $mform->setType('objectid', PARAM_INT);    
        // родительская мандата
        $mform->addElement('select', 'aclwarrantid', 
                $this->dof->get_string('warrant', $this->im_code()).':', $this->get_list_aclwarrants());
        $mform->setDefault('aclwarrantid',$this->_customdata->aclwarrantid);
        $mform->setType('aclwarrantid', PARAM_INT); 
        // кнопки "сохранить" и "отмена"
        $this->add_action_buttons(true, $this->dof->modlib('ig')->igs('save'));
        // применение проверки ко всем элементам
        $mform->applyFilter('__ALL__', 'trim');        
    }
    
    /** Добавление дополнительльных полей формы и установка значений по умолчанию
     * после загрузки данных в форму (если происходит редактирование)
     * 
     * @return null
     */
    public function definition_after_data()
    {

    }
    
    /** Возвращает двумерный массив типов и
     * соответствующих им кодов плагинов
     * @param array $types - список подразделений
     * @return array список кодов плагина, соответствующих данному типу
     */
    private function get_list_previous($types)
    {
        $previous = array();
        if ( ! is_array($types) )
        {//получили не массив - это значит что в базен нет ни одного подразделения
            return $previous;
        }
        foreach ($types as $type)
        {// забиваем массив данными    
            $previous[$type] = $this->get_list_value($type);
            
        }
        return $previous;
    }
    
    /** Возвращает список кодов плагина по типу плагина
     * @param int $type - id подразделения
     * @return array список кодов
     */
    private function get_list_value($type)
    {
        $code = array();
        if ( $type == 'core' )
        {// для core одоно значение 'coer'
            $codes = array('core' => 'core');
        }else
        {
            $codes = $this->dof->plugin_list_dir($type);
        }    
        foreach ( $codes as $key=>$obj )
        {
            $code[$key] = $key; 
        }
        return $code;
    }
    
    /** Получить список родительских мандат
     */
    public function get_list_aclwarrants()
    {
        // найдем неархивные мандаты, которые можно наследовать
        if ( ! $aclwarrants = $this->dof->storage('aclwarrants')->get_records(array('status' => array('draft','active'))) )
        {// таких нет - вернем пустой элемент
            return $this->dof_get_select_values();
        }
        $rez = array();
        foreach ( $aclwarrants as $aclwarrant )
        {
            // отыщем хозяина мандаты
            if ( ! empty($aclwarrant->linkptype) AND ! empty($aclwarrant->linkpcode) )
            {// хозяин плагин - выведем его имя
                $note = $this->dof->get_string('title', $aclwarrant->linkpcode, null, $aclwarrant->linkptype);
            }else
            {// мандата ядра
                $note = $this->dof->get_string('core',$this->im_code());
            }
            $rez[$aclwarrant->id] = $aclwarrant->name.'['.$aclwarrant->code.']('.$note.')';
            
        }
        
        // сортируем и возвращаем
        asort($rez);
        return $this->dof_get_select_values() + $rez;
        
    }
    
    /** Проверка данных формы
     * @param array $data - данные, пришедшие из формы
     * 
     * @todo добавить проверку пересечения времени с другими уроками. Выводить
     * текст ошибки в поле begintime, вместе со ссылкой на другой шаблон
     * @todo добавить проверку прав создания объектов в подразделении
     * 
     * @return array - массив ошибок, или пустой массив, если ошибок нет
     */
    public function validation($data,$files)
    {
        // создаем ссылку на HTML_QuickForm
        $mform =& $this->_form;
        
        $errors = array();
        
        // убираем лишние пробелы со всех полей формы
        $mform->applyFilter('__ALL__', 'trim');
        
        // Возвращаем ошибки, если они есть
        return $errors;
    }
    
    /** Обработать пришедшие из формы данные
     *
     * @return bool 
     */
    public function process()
    {
        // переменная, хранящая результат операции сохранения
        $result = true;
        if ( $this->is_cancelled() )
        {//ввод данных отменен - возвращаем на страницу просмотра шаблонов
            redirect($this->dof->url_im('acl','/warrantlist.php'));
        }
        if ( $this->is_submitted() AND $formdata = $this->get_data() )
        {
            
            dof_debugging('im/acl form.php. Запрещенный в im sgl-код. Вынести в storage', DEBUG_DEVELOPER);
            $acl = new object;
            
            $acl->plugintype   = $formdata->plugin[0];
            $acl->plugincode   = $formdata->plugin[1];
            $acl->code         = $formdata->code;
            $acl->objectid     = $formdata->objectid;
            $acl->aclwarrantid = $formdata->aclwarrantid;
            if ( ! $this->dof->storage('acl')->count_records_select("plugintype=? 
                                             AND plugincode=?
                                             AND code=? AND objectid=? 
                                             AND aclwarrantid=?",(array) $acl) )
            {// если такого права еще нет - добавим';
                $result = ($result AND (bool)$this->dof->storage('acl')->insert($acl));
            }
            if ( $result )
            {// если все успешно - делаем редирект
                redirect($this->dof->url_im('acl','/warrantacl.php?id='.$formdata->aclwarrantid));
            }
            return $result;
        }
    } 
    
}


/** Форма для передачи своих полномочий другим пользователей
 *
 */
class dof_im_give_warrant_acl_form extends dof_modlib_widgets_form
{
    /**
     * @var dof_control
     */
    protected $dof;
    protected $id;
    protected $aclwarrantid;

    protected function im_code()
    {
        return 'acl';
    }

    /**
     * @see parent::definition()
     */
    public function definition()
    {
        $this->dof          = $this->_customdata->dof;
        $this->aclwarrantid = $this->_customdata->aclwarrantid;
        $this->id           = $this->_customdata->id;
        $departmentid       = $this->_customdata->departmentid;

        // создаем ссылку на HTML_QuickForm
        $mform =& $this->_form;
        // hidden поля с id доверенности и департемента и назначения
        $mform->addElement('hidden', 'aclwarrantid', $this->aclwarrantid);
        $mform->setType('aclwarrantid', PARAM_INT);
        $mform->addElement('hidden', 'subdepartmentid', $departmentid);
        $mform->setType('subdepartmentid', PARAM_INT);
        $mform->addElement('hidden', 'id', $this->id);
        $mform->setType('id', PARAM_INT);
        // поля для проверок
        $mform->addElement('hidden', 'name', '');
        $mform->setType('name', PARAM_TEXT);
        $mform->addElement('hidden', 'code', '');
        $mform->setType('code', PARAM_TEXT);
        // Создаем элементы формы
        $button[] =& $mform->createElement('hidden', 'next');
        $button[] =& $mform->createElement('hidden', 'break');
        $button[] =& $mform->createElement('hidden', 'finish');
        $button[] =& $mform->createElement('hidden', 'cancel');
        $button[] =& $mform->createElement('hidden', 'ok');
        // добавляем элементы в форму
        $grp =& $mform->addElement('group', 'groupsubmit', null, $button);
        $mform->setType('groupsubmit[next]',    PARAM_TEXT);
        $mform->setType('groupsubmit[break]',   PARAM_TEXT);
        $mform->setType('groupsubmit[finish]',  PARAM_TEXT);
        $mform->setType('groupsubmit[cancel]',  PARAM_TEXT);
        $mform->setType('groupsubmit[ok]',      PARAM_TEXT);     
    }
    
    /** Добавление дополнительльных полей формы и установка значений по умолчанию
     * после загрузки данных в форму (если происходит редактирование)
     *
     * @return null
     */
    public function definition_after_data()
    {
        // создаем ссылку на HTML_QuickForm
        $mform =& $this->_form;
        // получаем статус кнопок
        $submit = $mform->getElementValue("groupsubmit");
        if ( (isset($submit['next']) AND $submit['next']) OR 
             (isset($submit['finish']) AND $submit['finish']) )
        {//нажата кнопка далее в шаге1 или кнопка закончить в шаге2
            $this->set_subwarrant_form();
        }elseif( isset($submit['break']) AND $submit['break'] )
        {
            $this->set_message_break_form();
        }elseif( isset($submit['cancel']) AND $submit['cancel'] )
        {
            $this->set_message_cancel_form();
        }elseif( isset($submit['ok']) AND $submit['ok'] )
        {
            redirect($this->dof->url_im('acl','/warrantview.php?aclwarrantid='.$this->id.
               '&departmentid='.optional_param('departmentid', 0, PARAM_INT)));
        }else
        {
            $this->set_personslist_form();
        }
        $mform->applyFilter('__ALL__', 'trim');
    }
    
    public function set_subwarrant_form()
    {
        // создаем ссылку на HTML_QuickForm
        $mform =& $this->_form;
        if ( $mform->elementExists('groupsubmit') )
        {// удаляем лишнее поле
            $mform->removeElement('groupsubmit');
        }
        if ( $mform->elementExists('name') )
        {// удаляем лишнее поле
            $mform->removeElement('name');
        }
        if ( $mform->elementExists('code') )
        {// удаляем лишнее поле
            $mform->removeElement('code');
        }
        $mform->addElement('html', '<div style="text-align: center"><h2>'.
                           $this->dof->get_string('step_two',$this->im_code()));
        $mform->addElement('html', '</h2></div>');
        //создаем заголовок формы
        $mform->addElement('header','formtitle', $this->dof->get_string('give_warrant',$this->im_code()));
        $mform->addElement('text', 'name', $this->dof->get_string('warrant_name',$this->im_code()).':', 
                array('size' => '100'));
        
        $mform->addElement('text', 'code', $this->dof->get_string('warrant_code',$this->im_code()).':',
                array('size' => '18'));
        
        $mform->addElement('textarea', 'description', $this->dof->get_string('warrant_notice',$this->im_code()).':',
                array('wrap' => 'virtual', 'cols' => '50', 'rows' => '10'));
        $options = array();
        $options['startyear'] = dof_userdate(time(),'%Y');
        $options['stopyear']  = dof_userdate(time(),'%Y')+1;
        $options['optional']  = false;
        $mform->addElement('html', '<br/>');
        $mform->addElement('date_selector', 'begindate', $this->dof->get_string('warrant_duration_begin',$this->im_code()).':',$options);
        $mform->addElement('date_selector', 'enddate', $this->dof->get_string('warrant_duration_end',$this->im_code()).':',$options);
        $mform->setDefault('enddate',time()+(365*24*3600));
        $options = array('0' => $this->dof->get_string('warrant_regive_allow', $this->im_code()),
                         '1' => $this->dof->get_string('warrant_regive_forbid', $this->im_code()));
        
        $mform->addElement('select', 'isdelegatable', $this->dof->get_string('warrant_regive', $this->im_code()).':',
                $options);
        
        $mform->addElement('header','formtitleacl', $this->dof->get_string('warrant_select_acls',$this->im_code()));
        // получаем массив прав по id доверенности
        if ( $list = $this->dof->storage('acl')->get_records(array(
                'aclwarrantid' => $this->aclwarrantid), 'plugintype,plugincode,code'))
        {
            foreach ($list as $rule)
            {// создаем checkbox каждому полю
                $code = $rule->plugintype.'-'.$rule->plugincode.'-'.$rule->code;
                $name = $this->dof->get_string($rule->plugintype.'_'.$rule->plugincode.'_'.$rule->code,$this->im_code()); 
                $mform->addElement('advcheckbox', 'acls['.$code.']', '', "&nbsp;&nbsp;".$name);
            }
        }
        
        $mform->addElement('html', '<div style="text-align: center">');
        $button[] =& $mform->createElement('submit', 'finish', $this->dof->modlib('ig')->igs('finish'));
        $button[] =& $mform->createElement('submit', 'cancel', $this->dof->modlib('ig')->igs('cancel'));
        // добавляем элементы в форму
        $grp =& $mform->addElement('group', 'groupsubmit', null, $button);
        $mform->addElement('html', '</div>');
        // проверки
        $mform->addRule('name',$this->dof->get_string('warrant_regive_error_name',$this->im_code()),'required',null,'client');
        $mform->addRule('code',$this->dof->get_string('warrant_regive_error_code',$this->im_code()),'required',null,'client');
    }
    
    public function set_personslist_form()
    {
        $this->dof->modlib('widgets')->print_heading($this->dof->get_string('step_one',$this->im_code()));
        // создаем ссылку на HTML_QuickForm
        $mform =& $this->_form;
        $addremove = $this->dof->modlib('widgets')->addremove($this->dof->url_im('acl', '/givewarrant.php', 
			array('id' => $this->id, 'aclwarrantid' => $this->aclwarrantid,
            'departmentid' => optional_param('departmentid', 0, PARAM_INT))));
	
    	// Устанавливаем надписи в форме
    	$addremovestrings = new Object();
    	
    	$addremovestrings->addlabel    = $this->dof->get_string('warrant_applicants_on_subwarrant', 'acl');
    	$addremovestrings->removelabel = $this->dof->get_string('warrant_persons_on_subwarrant', 'acl');
    	$addremovestrings->addarrow    = $this->dof->modlib('ig')->igs('add');
    	$addremovestrings->removearrow = $this->dof->modlib('ig')->igs('remove');
    	$addremove->set_default_strings($addremovestrings);
    	// список учеников входящих в список для получения доверености
    	$addremove->set_remove_list($this->dof->storage('aclwarrantagents')->get_subwarrant_personlist($this->id));
    	
    	// список персон доступных для получения доверенности
    	$addremove->set_add_list($this->dof->storage('aclwarrantagents')->get_subwarrant_applicantlist($this->id));
    	
    	// Отображаем форму
    	$addremove->print_html();

        if ( $mform->elementExists('groupsubmit') )
        {// удаляем лишнее поле
            $mform->removeElement('groupsubmit');
        }
        $mform->addElement('static', 'next_text', '', 
            $this->dof->get_string('message_next_form', 'acl'));
        $mform->addElement('html', '<div style="text-align: center">');
        $button[] =& $mform->createElement('submit', 'next', $this->dof->modlib('ig')->igs('next'));
        $button[] =& $mform->createElement('submit', 'break', $this->dof->modlib('ig')->igs('finish'));
        // добавляем элементы в форму
        $grp =& $mform->addElement('group', 'groupsubmit', null, $button);
        $mform->addElement('html', '</div>');
    }
    
    public function set_message_break_form()
    {
        $mform  =& $this->_form;
        
        if ( $mform->elementExists('groupsubmit') )
        {// удаляем галочку подтверждения
            $mform->removeElement('groupsubmit');
        }
        // получаем запись из базы по переданному id
        $mform->addElement('static', 'break_text', '', 
            '<b style="color:green;">'.$this->dof->get_string('message_break_form', $this->im_code()).'</b>');
        $button = array();
        $mform->addElement('html', '<div style="text-align: center">');
        $button[] =& $mform->createElement('submit', 'ok', $this->dof->modlib('ig')->igs('next'));
        // добавляем элементы в форму
        $grp =& $mform->addElement('group', 'groupsubmit', null, $button);
        $mform->addElement('html', '</div>');
    }
    
    public function set_message_cancel_form()
    {
        $mform  =& $this->_form;
        
        if ( $mform->elementExists('groupsubmit') )
        {// удаляем галочку подтверждения
            $mform->removeElement('groupsubmit');
        }
        // получаем запись из базы по переданному id
        $mform->addElement('static', 'cancel_text', '', 
            '<b style="color:red;">'.$this->dof->get_string('message_cancel_form', $this->im_code()).'</b>');
        $button = array();
        $mform->addElement('html', '<div style="text-align: center">');
        $button[] =& $mform->createElement('submit', 'ok', $this->dof->modlib('ig')->igs('next'));
        // добавляем элементы в форму
        $grp =& $mform->addElement('group', 'groupsubmit', null, $button);
        $mform->addElement('html', '</div>');
    }
    
    /** Проверка данных формы
     * @param array $data - данные, пришедшие из формы
     *
     * @todo добавить проверку пересечения времени с другими уроками. Выводить
     * текст ошибки в поле begintime, вместе со ссылкой на другой шаблон
     * @todo добавить проверку прав создания объектов в подразделении
     *
     * @return array - массив ошибок, или пустой массив, если ошибок нет
     */
    public function validation($data,$files)
    {
        $errors = array();
        //print_object($data);die;
        // проводим проверку параметров
        if ( empty($data['groupsubmit']['finish']) )
        {// кнопка завершить в шаге 2 не нажата - проверок не надо
            return $errors;
        }
        if ( empty($data['name']) )
        {// название доверенности должно быть обязательно
            $errors['name'] = $this->dof->get_string('warrant_regive_error_name', $this->im_code());
        }
        if ( empty($data['code']) )
        {// код тоже обязательное поле
            $errors['code'] = $this->dof->get_string('warrant_regive_error_code', $this->im_code());
        }else
        {// поле указано - проверим на уникальность
        	$sub = $this->dof->storage('aclwarrants')->get($data['id']);
		    if ( ($data['code'] <> $sub->code) AND $this->dof->storage('aclwarrants')->is_exists(array('code'=>$sub->code)) )
		    {// код поменялся и стал неуникальным - сообщим
			    $errors['code'] = $this->dof->get_string('warrant_regive_error_unique_code', $this->im_code());
		    }
        }
        $duration = $this->dof->storage('config')->get_config('duration', 'storage', 'aclwarrantagents');
        if ( $data['enddate'] < $data['begindate'] )
        {// некорректные данные
            $errors['begindate'] = $this->dof->get_string('warrant_regive_error_uncorrect_date', $this->im_code());
        }elseif( $data['enddate'] - time() > $duration->value )
        {
            $errors['begindate'] = $this->dof->get_string('warrant_regive_error_duration_date', $this->im_code(), date('d-m-Y',time()+$duration->value));
        }
        
        // Возвращаем ошибки, если они есть
        return $errors;
    }
    
    /** Обработать пришедшие из формы данные
     *
     * @return bool
     */
    public function process()
    {
        $message = '';
        if ( $this->is_submitted() AND confirm_sesskey() AND $data = $this->get_data() )
        {// получены данные из формы - обрабатываем
            if ( isset($data->groupsubmit['finish']) )
            {// дошли таки до конца - сохраняем изминения
                //print_object($data);
                // складываем чекбоксы в отдельный массив
                $acls = array();
                if ( ! empty($data->acls) )
                {// права не пустые - запоминаем их
                    foreach ($data->acls as $key => $value)
                    {
                        if ( empty($value) )
                        {// значение пустое - прав не надо
                            continue;
                        }
                        $tmp = explode('-', $key);
                        $acl = new stdClass;
                        $acl->plugintype = $tmp[0];
                        $acl->plugincode = $tmp[1];
                        $acl->code = $tmp[2];
                        $acl->objectid = 0;
                        $acls[] = $acl;
                    }
                }
                // данные правильные, создаем новую доверенность в aclwarrants в случае, если
                // параметр id отсутствует, иначе обновляем существующую субдоверенность
                $res = true;
                if ($data->id)
                {// обновляем все
                    // сначала доверенность
                    $aclwarrant = new object();
                    $aclwarrant->code = $data->code;
                    $aclwarrant->name = $data->name;
                    $aclwarrant->description = $data->description;
                    $aclwarrant->isdelegatable = $data->isdelegatable;
                    if ( !$this->dof->storage('aclwarrants')->update($aclwarrant, $data->id) )
                    {// не уделось обновить данные для доверенности - сообщим пользователю
                        $message .= '<div style=" color:red; "><b>'.
                                    $this->dof->get_string('warrant_update_failed', $this->im_code()).
                                    '</b></div>';
                    }
                    
                    // создаем применение доверенности
                    $obj = new object();
                    $obj->begindate = $data->begindate;
                    $obj->duration = $data->enddate - $data->begindate;
                    $obj->isdelegatable = $data->isdelegatable;
                    
                    // обновляем всех доверенных лиц (aclwarrantagentg)
                    if ( $warrantagents = $this->dof->storage('aclwarrantagents')->get_records(
                            array('aclwarrantid' => $data->id, 'status' => array('draft','active'))) )
                    {//если они есть
                        foreach ($warrantagents as $warrantagent)
                        {//обновляем каждую
                            $res = $res && $this->dof->storage('aclwarrantagents')->update($obj,$warrantagent->id);
                        }
                    }
                    if ( !$res )
                    {// что-то где-то пошло не так - сообщим
                        $message .= '<div style=" color:red; "><b>'.
                                    $this->dof->get_string('warrantagent_update_failed', $this->im_code()).
                                    '</b></div>';
                    }
                    // обновляем права
                    if ( !$this->dof->storage('acl')->update_warrant_acls($data->id, $acls) )
                    {// что-то где-то пошло не так - сообщим
                        $message .= '<div style=" color:red; "><b>'.
                                    $this->dof->get_string('acl_update_failed', $this->im_code()).
                                    '</b></div>';
                    }
                    if ( empty($message) )
                    {// сообщений нет - делаем редирект на страницу просмотра вакансии
                        redirect($this->dof->url_im('acl','/warrantview.php?aclwarrantid='.$data->id.
                            '&departmentid='.optional_param('departmentid', 0, PARAM_INT)));
                    }
                    
                }
            }
        }
        return $message;
    }
}

?>