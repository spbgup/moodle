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
    private $obj;
    /**
     * @var dof_control
     */
    protected $dof;
    
    function definition()
    {// делаем глобальные переменные видимыми
        $this->obj = $this->_customdata->obj;
        $this->dof = $this->_customdata->dof;
        // создаем ссылку на HTML_QuickForm
        $mform =& $this->_form;
        $mform->addElement('hidden','id', $this->obj->id);
        $mform->setType('id', PARAM_INT);
        //создаем заголовок формы
        $mform->addElement('header','formtitle',  $this->get_form_title($this->obj->id));
        // имя подразделения
        $mform->addElement('text', 'name', $this->dof->get_string('name','departments').':', 'size="20"');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name',$this->dof->get_string('err_required','departments'), 'required');
        // кодовое название
        $mform->addElement('text', 'code', $this->dof->get_string('code','departments').':', 'size="20"');
        $mform->setType('code', PARAM_TEXT);
        if ( isset($this->obj->id) AND $this->obj->id )
        {// если подразделение редактируется - то код обязателен
            $mform->addRule('code',$this->dof->get_string('err_required','departments'), 'required',null,'client');
            $mform->addRule('code',$this->dof->get_string('err_required','departments'), 'required',null,'server');
        }
        
        // руководитель
        $mform->addElement('select', 'manager', $this->dof->get_string('manager','departments').':', 
                                   $this->get_list_manager($this->obj->id));
        // вышестоящее подразделение
        $mform->addElement('select', 'leaddepid', $this->dof->get_string('leaddep','departments').':', 
                                   $this->get_list_leaddep($this->obj->id));
        //$mform->addRule('leaddepid',$this->dof->get_string('err_required','departments'), 'required');
        // часовой пояс                           
        $mform->addElement('select', 'zone', $this->dof->get_string('zone','departments').':', 
                                   $this->get_list_timezone());
        $mform->addRule('zone',$this->dof->get_string('err_required','departments'), 'required');
        // @todo как себя ведет статус при создании и редактировании
        // пока отсутствует
        // $mform->addElement('static', 'status', $this->dof->get_string('status','departments').':');
        
        // адрес
        $mform->addElement('header','formtitleaddress', $this->dof->get_string('departmentaddress','departments') );
        // страна и регион 
        
        //$choices = get_list_of_countries();
        $choices = get_string_manager()->get_list_of_countries(false);
        $sel =& $mform->addElement('hierselect', 'country', $this->dof->get_string('addrcountryregion', 'departments').':',null,'<br>');
        $sel->setMainOptions($choices);
        $sel->setSecOptions($this->get_list_regions($choices));  
        $mform->setAdvanced('country');
        // индекс
        $mform->addElement('text', 'postalcode', $this->dof->get_string('addrpostalcode','departments').':');
        $mform->addRule('postalcode',$this->dof->get_string('err_postalcode','departments'), 'numeric',null,'client');
        $mform->setType('postalcode', PARAM_TEXT);
        $mform->setAdvanced('postalcode');
        // округ/район
        $mform->addElement('text', 'county', $this->dof->get_string('addrcounty','departments').':', 'size="20"');
        $mform->setType('county', PARAM_TEXT);
        $mform->setAdvanced('county');
        // Населенный пункт
        $mform->addElement('text', 'city', $this->dof->get_string('addrcity','departments').':', 'size="20"');
        $mform->setType('city', PARAM_TEXT);
        $mform->setAdvanced('city');
        // название улицы
        $mform->addElement('text', 'streetname', $this->dof->get_string('addrstreetname','departments').':', 'size="20"');
        $mform->setType('streetname', PARAM_TEXT);
        $mform->setAdvanced('streetname');
        $mform->addElement('select', 'streettype', $this->dof->get_string('addrstreettype','departments').':', $this->get_list_typestreet());
        $mform->setType('streettype', PARAM_TEXT);
        $mform->setAdvanced('streettype');
        $mform->addElement('text', 'number', $this->dof->get_string('addrnumber','departments').':', 'size="20"');
        $mform->setType('number', PARAM_TEXT);
        $mform->setAdvanced('number');
        $mform->addElement('text', 'gate', $this->dof->get_string('addrgate','departments').':', 'size="20"');
        $mform->setType('gate', PARAM_TEXT);
        $mform->setAdvanced('gate');
        $mform->addElement('text', 'floor', $this->dof->get_string('addrfloor','departments').':', 'size="20"');
        $mform->setType('floor', PARAM_TEXT);
        $mform->setAdvanced('floor');
        $mform->addElement('text', 'apartment', $this->dof->get_string('addrapartment','departments').':', 'size="20"');
        $mform->setType('apartment', PARAM_TEXT);
        $mform->setAdvanced('apartment');
        $mform->addElement('text', 'latitude', $this->dof->get_string('addrlatitude','departments').':', 'size="20"');
        $mform->setType('latitude', PARAM_TEXT);
        $mform->setAdvanced('latitude');
        $mform->addElement('text', 'longitude', $this->dof->get_string('addrlongitude','departments').':', 'size="20"');
        $mform->setType('longitude', PARAM_TEXT);
        $mform->setAdvanced('longitude');
        $mform->addElement('hidden','addressid', 0);
        $mform->setType('addressid', PARAM_INT);
        if ( $this->obj->id <> 0 )
        {// если id передано - значит редактировалось
            $mform->addElement('hidden','edit', true); 
            $mform->setType('edit', PARAM_BOOL);
        }
        // кнопоки сохранить и отмена
        $this->add_action_buttons(true, $this->dof->get_string('to_save','journal'));
        $mform->addElement('hidden','sesskey', 0);
        $mform->setType('sesskey', PARAM_ALPHANUM);
        // применение проверки ко всем элементам
        $mform->applyFilter('__ALL__', 'trim');
    }
    /**
     * Задаем проверку корректности введенных значений
     */
    function validation($data,$files)
    {
		$error = array();
        if ( ! trim($data['name']) )
        {// если не указано название
            $error['name'] = $this->dof->get_string('err_required','departments');
        }
        if ( ! trim($data['code']) )
        {// если не указан код
            //$r['code'] = $this->dof->get_string('err_required','departments');
        }elseif ( isset($data['id']) AND ($data['id'] <> 0) )
        {// проверка уникальности кода
            // если подразделение редактируется
			$department = $this->dof->storage('departments')->get($data['id']);
		    if ((trim($data['code']) <> $department->code) AND $this->dof->storage('departments')->is_code_notunique(trim($data['code'])))
		    {// и код менялся - он не должен совпадать с другими
			    $error['code'] = $this->dof->get_string('err_unique','departments');
		    }
		    $leaddepid = $this->dof->storage('departments')->get_field($data['id'],'leaddepid');
        	if ( $data['leaddepid'] != $leaddepid )
		    {// изменилось поле "родительское подразделение"
		        if ( ! $this->dof->storage('departments')->is_access('create', NULL, NULL, $data['leaddepid']) OR
                     ! $this->dof->storage('departments')->is_access('delete', NULL, NULL, $leaddepid) )
                {// проверяем право удалять из старого подразделения и добавлять в новое
                    $error['leaddepid'] = $this->dof->get_string('form:error:unable_to_create_smth_in_this_department','departments');
                }
                if ( ! $this->dof->storage('config')->get_limitobject('departments',$data['leaddepid']) )
                {// проверяем, можно ли еще создавать подразделения - или уже хватит
                    $error['leaddepid'] = $this->dof->get_string('limit_message','departments');
                }
		    }
		}else
		{// если подразделение создавалось
	        if ( $this->dof->storage('departments')->is_code_notunique(trim($data['code'])) )
		    {// код не должен совпадать с другими
		        $error['code'] = $this->dof->get_string('err_unique','departments');
		    }
		    if ( ! $this->dof->storage('config')->get_limitobject('departments',$data['leaddepid']) )
		    {// проверяем, можно ли еще создавать подразделения - или уже хватит
		        $error['leaddepid'] = $this->dof->get_string('limit_message','departments');
		    }
            if ( ! $this->dof->storage('departments')->is_access('create', NULL, NULL, $data['leaddepid']) )
            {// нет права создавать новые дочерние подразделения
                $error['leaddepid'] = $this->dof->get_string('form:error:unable_to_create_smth_in_this_department','departments');
            }
		}
        if ( ! empty($data['streetname']) AND empty($data['streettype']) )
		{// существует улица - нужно указать тип 
			$error['streettype'] = $this->dof->get_string('err_streettype','departments');
			
	  	}
		return $error;
	    
    }
    /**
     * Возвращает строку заголовка формы
     * @param int $ageid
     * @return string
     */
    private function get_form_title($id)
    {
        if ( ! $id )
        {//заголовок создания формы
            return $this->dof->get_string('newdepartment','departments');
        }else 
        {//заголовок редактирования формы
            return $this->dof->get_string('editdepartment','departments');
        }
        
    }
    /** Возвращает список регионов приписанных к стране
     * @param array $choices - список стран
     * @return array список регионов
     */
    private function get_list_regions($choices)
    {
        $regions = array();
        if ( ! is_array($choices) )
        {//получили не массив - это ошибка';
            return $rez;
        }
        // к каждой стране припишем ее регионы
        foreach ($choices as $key => $value)
        {
            $regions += $this->dof->modlib('refbook')->region($key);
        }
        return $regions;
        
    }
    /** Возвращает список персон для подразделения
     * @todo нет проверки прав для списка руководителей при создании подразделения
     *       потому что непонятно как ее производить и кому давать право на использование персон
     *       в качестве руководителей
     * 
     * @param int $departmentid - id подразделения
     * @return array
     */
    protected function get_list_manager($departmentid)
    {
    	$rez = array();
    	
    	$rez = array('0'=>'- '.$this->dof->get_string('nonespecify','departments').' -');
    	
        // получаем всех не удаленных пользователей
    	$persons = $this->dof->storage('persons')->get_records(array('status' => 'normal'));
        
        if ( ! is_array($persons) )
        {//получили не массив - это ошибка';
            return $rez;
        }
        foreach ($persons as $id => $person)
        {// проверяем права и оставляем птолько пользователей, которых можно назначить руководителями
            //if ( $this->dof->storage('persons')->is_access('use', $id) )
            //{
                $rez[$id] = $person->sortname;
            //}
        }
        asort($rez);
        return $rez;
    }
    
    /** Возвращает список подразделений
     * @param int $departmentid - id редактируемого подразделения, которое надо исключить
     * @return array
     */
    protected function get_list_leaddep($departmentid)
    {
        // добавляем в список нулевое подразделение только в случае, если пользователь имеет право 
        // создавать подразделения где угодно
        $rez = array();
        if ( $this->dof->storage('departments')->is_access('create', null, null, 0) )
        {
            $rez['0'] = $this->dof->get_string('none','departments');
        }
        if ( $departmentid )
        {// если подразделение редактируется - то мы должны оставить в поле "родительское подразделение"
            // оригинальное значение, даже если пользователь не имеет права его использовать
            // (чтобы не испортить при редактировании уже существующую запись)
            $depobj = $this->dof->storage('departments')->get($departmentid);
            // получаем родительское подразделение и записываем его в select-список
            $rez[$depobj->leaddepid] = $this->dof->storage('departments')->get_field($depobj->leaddepid, 'name');
        }
    	$departments = $this->dof->storage('departments')->departments_list_subordinated(null,'0',null,true);
    	if ( ! empty($this->obj->leaddepid) AND ! array_key_exists($this->obj->leaddepid, $departments) )
    	{
    	    $rez[$this->obj->leaddepid] = $this->dof->storage('departments')->get($this->obj->leaddepid)->name;  
    	}
    	if ( ! is_array($departments) )
        {//получили не массив - это ошибка';
            return $rez;
        }
        //$path = $this->dof->storage('departments')->get_field($departmentid,'path');
        if ( $departmentid <> 0 )
        {//родителя на дочек вешать нельзя
            // найдем дочек
            $daughterdep = $this->dof->storage('departments')->departments_list_subordinated($departmentid,'0',null,true);
            // и исключим их
            $departments = array_diff_key($departments,$daughterdep);
        }
        $rez += $departments;
        if ( array_key_exists($departmentid, $rez) AND $departmentid <> 0 )
        {// исключим из массива текущее подразделение, если оно есть
            unset($rez[$departmentid]);
        }
        return $rez;
    }
    
    /** Возвращает имя подразделения
     * @param int $id - id подразделения
     * @return string
     */
    public function get_departments_name($id)
    {
        if ( $this->dof->storage('departments')->is_exists($id) )
        {// если департамент существует - выведем его имя
            return $this->dof->storage('departments')->get_field($id,'name');
        }else
        {// не существует - выведем надпись "Нет"
            return $this->dof->get_string('none','departments');
        }
    }
    
    /** Возвращает список возможных типов улиц
     * @return array
     */
    private function get_list_typestreet()
    {
        return $this->dof->modlib('refbook')->get_street_types();
    }
    
    /** возвращает список временных зон
     * @return array
     */
    protected function get_list_timezone()
    {
        $rez = get_list_of_timezones();
        // добавим время сервера moodle
        $rez['99'] = get_string("serverlocaltime");
        return $rez;
    }
}

/**
 * Класс для вывода карточки структурного подразделения
 */
class dof_im_departments_card extends dof_im_edit
{
    
    /*
     * 
     */
    var $departmentid = 0;

    function definition()
    {
        $this->departmentid = $depid = optional_param('departmentid', 0, PARAM_INT);
        $addvars = array();
        $addvars['departmentid'] = $depid;
        $this->obj = $this->_customdata->obj;
        $this->dof = $this->_customdata->dof;
        // print_object($this->_customdata->obj);
        // создаем ссылку на HTML_QuickForm
        $mform =& $this->_form;
        // заголовок
        $mform->addElement('header','formtitle',$this->get_departments_name($this->obj->id));
        // кодовое название
        $mform->addElement('html','&nbsp;&nbsp;&nbsp;'.$this->dof->get_string('code','departments').': '.$this->obj->code.'<br>');
        // имя подразделения
        $mform->addElement('html','&nbsp;&nbsp;&nbsp;'.$this->dof->get_string('name','departments').': '.
                                  $this->get_departments_name($this->obj->id, true).'<br>');
        // руководитель
        $mform->addElement('html','&nbsp;&nbsp;&nbsp;'.$this->dof->get_string('manager','departments').': '.
                           $this->get_manager_name($this->obj->managerid).'<br>');
        // вышестоящее подразделение
        $mform->addElement('html','&nbsp;&nbsp;&nbsp;'.$this->dof->get_string('leaddep','departments').': '.
                           $this->get_departments_name($this->obj->leaddepid).'<br>');                 
        // адрес подразделения
        $mform->addElement('html','&nbsp;&nbsp;&nbsp;'.$this->dof->get_string('departmentaddress','departments').': '.
                           $this->get_string_address($this->obj->addressid).'<br>');
        // часовой пояс
        $zone = $this->get_list_timezone();
        if ( ! isset($zone[$this->obj->zone]) )
        {//временная зона не установлена
            $timezone = '';
        }else
        {
            $timezone = $zone[$this->obj->zone];
        }
        $mform->addElement('html','&nbsp;&nbsp;&nbsp;'.$this->dof->get_string('zone','departments').': '. 
                           $timezone.'<br>');
        // статус - пока отсутствует
        // $mform->addElement('static', 'status', $this->dof->get_string('status','departments').': ');
        // дочерние подразделения
        $mform->addElement('header','formtitle',$this->dof->get_string('subordinated','departments'));
        $departments = $this->dof->storage('departments')->departments_list_subordinated($this->obj->id, $this->obj->depth,$this->obj->path,true);

        if ( $this->dof->storage('config')->get_limitobject('departments',$addvars['departmentid']) )
        {// ссылка на создание
            $mform->addElement('html','&nbsp;&nbsp;&nbsp;<a href='.$this->dof->url_im('departments',
                           '/edit.php?departmentid='.$depid.'&id=0').'>'.
                           $this->dof->get_string('newdepartment','departments').'</a><br><br>');
        }                 
        foreach ($departments as $id=>$department)
        {
            if ( $this->dof->storage('departments')->is_access('view', $id) )
            {// если есть право на просмотр
                    $department = '<a href='.$this->dof->url_im('departments','/view.php?departmentid='.$depid.'&id='.$id).'>'
                                  .$department.'</a>';
            }
            $mform->addElement('html','&nbsp;&nbsp;&nbsp;'.$department.'<br>');
        }
        // применение проверки ко всем элементам
        $mform->applyFilter('__ALL__', 'trim');
        
    }
    
    /** Возвращает полное имя персоны
     * @param int $id - id персоны
     * @return string
     */
    private function get_manager_name($id)
    {
        $depid = optional_param('departmentid', 0, PARAM_INT);
        $addvars = array();
        $addvars['departmentid'] = $depid;
        if ( $this->dof->storage('persons')->is_exists($id) )
        {// если руководитель указан - выведем его имя
            $manager = $this->dof->storage('persons')->get_field($id,'sortname');
            if ( $this->dof->storage('persons')->is_access('view',$this->obj->id) )
            {
                $manager = '<a href='.$this->dof->url_im('persons','/view.php?id='.$this->obj->managerid,$addvars).'>'
                           .$manager.'</a>';
            }
            return $manager;
        }else
        {// не указан - так и напишем
            return $this->dof->get_string('nonespecify','departments');
        }
    }
    /** Возвращает имя подразделения
     * @param int $id - id подразделения
     * @param bool $edit - ссылка на редактирование, по умолчанию на просмотр
     * @return string
     */
    public function get_departments_name($id, $edit = false)
    {
        if ( $this->dof->storage('departments')->is_exists($id) )
        {// если департамент существует - выведем его имя
            $departmentname = $this->dof->storage('departments')->get_field($id,'name').' ['.
    	                      $this->dof->storage('departments')->get_field($id,'code').']';
            if ( $edit )
            {// если нам сказано передать ссылку на редактирование - передаем ее
                if ( $this->dof->storage('departments')->is_access('edit', $id) )
                {// если есть право на редактирование
                    $departmentname = '<a href='.$this->dof->url_im('departments','/edit.php?id='.$id.'&departmentid='.$this->departmentid).'>'
                                      .$departmentname.'</a>';
                }
            }else
            {// иначе ссылку на просмор
              
                if ( $this->dof->storage('departments')->is_access('view',$id,NULL,$id)  ) 
                {// если есть право на просмтр
                    $departmentname = '<a href='.$this->dof->url_im('departments','/view.php?departmentid='.$id).'>'
                                      .$departmentname.'</a>';
                }   
            } 
            return $departmentname;
        }else
        {// не существует - выведем надпись "Нет"
            return $this->dof->get_string('none','departments');
        }
    }
    
    /** Возвращает полный адрес подразделения
     * @param int $id - id адреса
     * @return string
     */
    private function get_string_address($id)
    {
        if ( ! $address = $this->dof->storage('addresses')->get($id) )
        {// адреса нет - так и напишем
            return $this->dof->get_string('nonespecify','departments');
        }else
        {// сформируем строчку адреса
            // регион
            $address->region = $this->dof->modlib('refbook')->region($address->country,$address->region);
            // улица
            $address->street = $address->streettype.'&nbsp;'.$address->streetname;
            // элементы адреса
            $mas = array('region','postalcode','city','street','number');
            $str = '';
            // перечислим элементы адреса через запятую
            foreach ($mas as $value)
            {
                if ( isset($address->$value) AND ( $address->$value <> '' ) AND ( $address->$value <> '&nbsp;' ) )
                {// элемент присутствует - включим его в строчку и поставим запятую
                    $str .= $address->$value.', ';
                }
            }
            // уберем лишнюю последнюю запятую и вернем строчку
            return substr($str, 0, -2);
        }
    }
    
}

?>