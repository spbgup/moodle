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



/** Подразделения
 * 
 */
class dof_im_departments implements dof_plugin_im
{
    /**
     * @var dof_control
     */
    protected $dof;
    // **********************************************
    // Методы, предусмотренные интерфейсом plugin
    // **********************************************
    /** Метод, реализующий инсталяцию плагина в систему
     * Создает или модифицирует существующие таблицы в БД
     * и заполняет их начальными значениями
     * @return boolean
     * Может надо возвращать массив с названиями таблиц и результатами их создания?
     * чтобы потом можно было распечатать сообщения о результатах обновления
     * @access public
     */
    public function install()
    {
        return true;
    }
    
    /** Метод, реализующий обновление плагина в системе
     * Создает или модифицирует существующие таблицы в БД
     * @param string $old_version - версия установленного в системе плагина
     * @return boolean
     * Может надо возвращать массив с названиями таблиц и результатами их создания/изменения?
     * чтобы потом можно было распечатать сообщения о результатах обновления
     * @access public
     */
    public function upgrade($oldversion)
    {
        return true;
    }
    /** Метод, реализующий удаление плагина в системе  
	 * @return bool
	 */
	public function uninstall()
	{
		return $this->dof->storage('acl')->save_roles($this->type(),$this->code(),array());
	}
    /** Возвращает версию установленного плагина
     * @return string
     * @access public
     */
    public function version()
    {
        return 2012060600;
    }
    /** Возвращает версии интерфейса Деканата, 
     * с которыми этот плагин может работать
     * @return string
     * @access public
     */
    public function compat_dof()
    {
        return 'aquarium';
    }

    /** Возвращает версии стандарта плагина этого типа, 
     * которым этот плагин соответствует
     * @return string
     * @access public
     */
    public function compat()
    {
        return 'angelfish';
    }
    
    /** Возвращает тип плагина
     * @return string 
     * @access public
     */
    public function type()
    {
        return 'im';
    }
    /** Возвращает короткое имя плагина
     * Оно должно быть уникально среди плагинов этого типа
     * @return string
     * @access public
     */
    public function code()
    {
        return 'departments';
    }
    /** Возвращает список плагинов, 
     * без которых этот плагин работать не может
     * @return array
     * @access public
     */
    public function need_plugins()
    {
        return array('modlib'=>array('nvg'          => 2008060300,
                                     'widgets'      => 2009050800),
                                     
                     'storage'=>array('persons'     => 2009060400,
                                      'departments' => 2011091900,
                                      'ages'        => 2009050600,
                                      'acl'         => 2011041800));
    }
    /** Список обрабатываемых плагином событий 
     * @return array - array(array('plugintype'=>..,'plugincode'=>..,'eventcode'=>..),...)
     * @access public
     */
    public function list_catch_events()
    {
       return array(
                array('plugintype' => 'im',
                      'plugincode' => 'obj',
                      'eventcode'  => 'get_object_url'));
    }
    /** Требуется ли запуск cron в плагине
     * @return bool
     * @access public
     */
    public function is_cron()
    {
       return false;
    }
    
    /** Проверяет полномочия на совершение действий
     * @param string $do - идентификатор действия, которое должно быть совершено
     * @param int $id_obj - идентификатор экземпляра объекта, 
     * по отношению к которому это действие должно быть применено
     * @param int $user_id - идентификатор пользователя, полномочия которого проверяются
     * @return bool true - можно выполнить указанное действие по отношению к выбранному объекту
     * false - доступ запрещен
     * @access public
     */
    public function is_access($do, $objid = NULL, $userid = NULL)
    {
        if ( $this->dof->is_access('datamanage') OR $this->dof->is_access('admin') 
             OR $this->dof->is_access('manage') )
        {// манагеру можно все
            return true;
        }  
        // получаем id пользователя в persons
        $personid = $this->dof->storage('persons')->get_by_moodleid_id($userid);
        // получаем все нужные параметры для функции проверки прав
        $acldata = $this->get_access_parametrs($do, $objid, $personid);   
        // проверка
        return $this->acl_check_access_paramenrs($acldata);
    }
    /** Требует наличия полномочия на совершение действий
     * @param string $do - идентификатор действия, которое должно быть совершено
     * @param int $id_obj - идентификатор экземпляра объекта, 
     * по отношению к которому это действие должно быть применено
     * @param int $user_id - идентификатор пользователя, полномочия которого проверяются
     * @return bool true - можно выполнить указанное действие по отношению к выбранному объекту
     * false - доступ запрещен
     * @access public
     */
    public function require_access($do, $objid = NULL, $userid = NULL)
    {
        if ( ! $this->is_access($do, $objid, $userid) )
        {
            $notice = "departments/{$do} (block/dof/im/departments: {$do})";
            if ($objid){$notice.=" id={$objid}";}
            $this->dof->print_error('nopermissions','',$notice);
        }
    }
    /** Обработать событие
     * @param string $gentype - тип модуля, сгенерировавшего событие
     * @param string $gencode - код модуля, сгенерировавшего событие
     * @param string $eventcode - код задания
     * @param int $intvar - дополнительный параметр 
     * @param mixed $mixedvar - дополнительные параметры
     * @return bool - true в случае выполнения без ошибок
     * @access public
     */
    public function catch_event($gentype,$gencode,$eventcode,$intvar,$mixedvar)
    {
        if ( $gentype == 'im' AND $gencode == 'obj' AND $eventcode == 'get_object_url' )
        {
            if ( $mixedvar['storage'] == 'departments' )
            {
                if ( isset($mixedvar['action']) AND $mixedvar['action'] == 'view' )
                {// Получение ссылки на просмотр объекта
                    $params = array('id' => $intvar);
                    if ( isset($mixedvar['urlparams']) AND is_array($mixedvar['urlparams']) )
                    {
                        $params = array_merge($params, $mixedvar['urlparams']);
                    }
                    return $this->url('/view.php', $params);
                }
            }
        }
        return false;
    }
    /** Запустить обработку периодических процессов
     * @param int $loan - нагрузка (1 - только срочные, 2 - нормальный режим, 3 - ресурсоемкие операции)
     * @param int $messages - количество отображаемых сообщений (0 - не выводить,1 - статистика,
     *  2 - индикатор, 3 - детальная диагностика)
     * @return bool - true в случае выполнения без ошибок
     * @access public
     */
    public function cron($loan,$messages)
    {
        return true;
    }
    /** Обработать задание, отложенное ранее в связи с его длительностью
     * @param string $code - код задания
     * @param int $intvar - дополнительный параметр 
     * @param mixed $mixedvar - дополнительные параметры
     * @return bool - true в случае выполнения без ошибок
     * @access public
     */
    public function todo($code,$intvar,$mixedvar)
    {
        return true;
    }
    /** Конструктор
     * @param dof_control $dof - идентификатор действия, которое должно быть совершено
     * @access public
     */
    public function __construct($dof)
    {
        $this->dof = $dof;
    }
    // **********************************************
    // Методы, предусмотренные интерфейсом im
    // **********************************************
    /** Возвращает текст для отображения в блоке на странице dof
     * @param string $name - название набора текстов для отображания
     * @param int $id - id текста в наборе
     * @return string - html-код содержимого блока
     */
    function get_block($name, $id = 1)
    {
        $depid = optional_param('departmentid', 0, PARAM_INT);
        $rez = '';
        // адрес текущей станицы
        $url = $this->dof->modlib('nvg')->get_url();

        switch ($name)
        {
            case 'main':
                // список всех подразделений
                $dep  = $this->dof->storage($this->code())->departments_list_subordinated(null,'0', null,true,'',true);
                if ( $dep )
                {// есть права
                    foreach ( $dep as $id=>$objdep )
                    {
                        // получим полное название подразделения для всплывающей подсказки
                        $deptname = $this->dof->storage($this->code())->get_field($id, 'name');
                        if ( strstr($url, 'departmentid=') )
                        {// есть подразделение заменим на соответствующий id
                            $path = str_replace('departmentid='.$depid, 'departmentid='.$id, $url);
                        }else 
                        {// установим свои подразделения
                            if (  strstr($url, '?') )
                            {
                                $path= $url.'&departmentid='.$id;
                            }else 
                            {    
                                $path= $url.'?departmentid='.$id;
                            }
                        }    
                        if ( $id==0 )
                        {// есть право смотреть все объекты - покажем это
                            if ( $depid == 0)
                            {
                                $rez = "<a href='{$path}' style='color:green; font-size:17px;'>".$this->dof->get_string('see_allobj', 'departments') ."</a><br>".$rez;
                            }else 
                            {
                                $rez = "<a href='{$path}'>". $this->dof->get_string('see_allobj', 'departments') ."</a><br>".$rez;
                            }     
                        }elseif ( $depid == $id)
                        {// ссылка, на которой мы сейчас находимся
                            $rez .= "<a href='{$path}' title='{$deptname}' style='color:green; font-size:17px;'>{$objdep}</a><br>";
                        }else 
                        {// все остальные ссылки
                            $rez .= "<a href='{$path}' title='{$deptname}'>{$objdep}</a><br>";
                        }  
                    }
                }  
        }
        if ( $rez )
        {     
            return $rez;
        }    
    }
    /** Возвращает html-код, который отображается внутри секции
     * @param string $name - название набора текстов для отображания
     * @param int $id - id текста в наборе
     * @return string  - html-код содержимого секции секции
     */
    function get_section($name, $id = 1)
    {
        $rez = '';
        switch ($name)
        {

        }
        return $rez;
    }
     /** Возвращает текст, отображаемый в блоке на странице курса MOODLE 
      * @return string  - html-код для отображения
      */
    public function get_blocknotes($format='other')
    {
        return "<a href='{$this->dof->url_im('departments','/index.php')}'>"
                    .$this->dof->get_string('page_main_name')."</a>";
    }
    
    // ***********************************************************
    //       Методы для работы с полномочиями и конфигурацией
    // ***********************************************************   
    
    /** Получить список параметров для фунции has_hight()
     * @todo завести дополнительные права в плагине storage/persons и storage/contracts 
     * и при редактировании контракта или персоны обращаться к ним
     * 
     * @return object - список параметров для фунции has_hight()
     * @param string $action - совершаемое действие
     * @param int $objectid - id объекта над которым совершается действие
     * @param int $userid
     */
    protected function get_access_parametrs($action, $objectid, $userid, $depid = null)
    {
        $result = new object();
        $result->plugintype   = $this->type();
        $result->plugincode   = $this->code();
        $result->code         = $action;
        $result->userid       = $userid;
        $result->departmentid = $depid;
        if ( is_null($depid) )
        {// подразделение не задано - берем текущее
            $result->departmentid = optional_param('departmentid', 0, PARAM_INT);
        }
        $result->objectid     = $objectid;
        if ( ! $objectid )
        {// если objectid не указан - установим туда 0 чтобы не было проблем с sql-запросами
            $result->objectid = 0;
        }
        return $result;        
        
        
        
    }    
    /** Проверить права через плагин acl.
     * Функция вынесена сюда, чтобы постоянно не писать длинный вызов и не перечислять все аргументы
     * 
     * @return bool
     * @param object $acldata - объект с данными для функции storage/acl->has_right() 
     */
    protected function acl_check_access_paramenrs($acldata)
    {
        return $this->dof->storage('acl')->
                    has_right($acldata->plugintype, $acldata->plugincode, $acldata->code, 
                              $acldata->userid, $acldata->departmentid, $acldata->objectid);
    }

    // **********************************************
    //              Собственные методы
    // **********************************************
    
    /** Получить URL к собственным файлам плагина
     * @param string $adds[optional] - фрагмент пути внутри папки плагина
     *                                 начинается с /. Например '/index.php'
     * @param array $vars[optional] - параметры, передаваемые вместе с url
     * @return string - путь к папке с плагином 
     * @access public
     */
    public function url($adds='', $vars=array())
    {
        return $this->dof->url_im($this->code(), $adds, $vars);
    }
    /**
     * Возвращает html-код отображения 
     * информации о подразделении
     * @param stdClass $obj - запись из таблицы
     * @return mixed string html-код или false в случае ошибки
     */
    public function show($obj)
    {
        if (! is_object($obj))
        {// переданны данные неверного формата
        	return false;
        }
        if ( empty($obj->addressid) )
        {
            $obj->addressid = 0;
        }
        $customdata = new stdClass;
        $customdata->obj = $obj;
        $customdata->dof = $this->dof;
        $form = new dof_im_departments_card(null,$customdata);
        unset($obj->zone);
        $form->set_data($obj); 
    	// выводим таблицу на экран
        return $form;
    }
    
    /**
     * Возвращает html-код отображения 
     * информации о подразделении
     * @param int $id - id записи из таблицы
     * @return mixed string html-код или false в случае ошибки
     */
    public function show_id($id)
    {
        if ( ! is_int_string($id) )
        {//входные данные неверного формата 
            return false;
        }
    	if ( ! $obj = $this->dof->storage('departments')->get($id) )
    	{// подразделение не найден
    		return false;
    	} 
    	return $this->show($obj);
    }
    
    /**
     * Возвращает html-код отображения 
     * информации о нескольких подразделениях
     * @param array $list - массив записей подразделений, которые надо отобразить
     * @param int $departmentid - id подразделения в котором пользователь в текущий момент находится,
     *                            (для формирования ссылки)
     * @return mixed string в string html-код или false в случае ошибки
     */
    public function showlist($list, $departmentid)
    {
        if ( ! is_array($list))
        {// переданны данные неверного формата
        	return false;
        }
        $data = array();
    	// заносим данные в таблицу
    	foreach ($list as $obj)
    	{   
            
            if ( $this->dof->storage('departments')->is_access('view', $obj->id) )
            {// название подразделение сделаем ссылкой на просмотр
                $name =  '<a href='.$this->dof->url_im('departments','/view.php', array('departmentid' =>
                $departmentid, 'id' => $obj->id)).'>'.$obj->name.'</a>';
            }else
            {// отобразим просто название если прав нет
                $name = $obj->name;
            }
            
    		$department = $this->dof->storage('departments')->get_field($obj->id,'name').' <br>['.
    	                      $this->dof->storage('departments')->get_field($obj->id,'code').']';
   		    // ответственное лицо
   	    	$manager = $this->dof->storage('persons')->get_field($obj->managerid,'sortname');
   	    	// вышестоящее подразделение
   	    	if ( $obj->leaddepid <> 0 )
   	    	{// если оно есть - выведем прям с кодом
   		        $leaddep = $this->dof->storage('departments')->get_field($obj->leaddepid,'name').' <br>['.
    	                       $this->dof->storage('departments')->get_field($obj->leaddepid,'code').']';
   	    	}else
   	    	{//нет - пустую строчку
   	    	    $leaddep = '';
   	    	}
   		    //получаем ссылки на картинки
            $imgedit = '<img src="'.$this->dof->url_im('departments', '/icons/edit.png').'"
                alt="'.$this->dof->get_string('edit', 'departments').'" title="'.$this->dof->get_string('edit', 'departments').'">';
            $imgview = '<img src="'.$this->dof->url_im('departments', '/icons/view.png').'" 
                alt="'.$this->dof->get_string('view', 'departments').'" title="'.$this->dof->get_string('view', 'departments').'">';
            $imgdelet = '<img src="'.$this->dof->url_im('departments', '/icons/delete.png').'" 
                alt="'.$this->dof->get_string('deletedepartment', 'departments').'" title="'.$this->dof->get_string('deletedepartment', 'departments').'">';
   		    //рисуем картинку
   		    $link = '';
    	    if ( $this->dof->storage('departments')->is_access('edit', $obj->id) )
            {//покажем ссылку на страницу редактирования
                $link .= '<a href='.$this->dof->url_im('departments','/edit.php', array('departmentid' =>
                $departmentid, 'id' => $obj->id)).'>'.$imgedit.'</a>&nbsp;';
            }
            if ( $this->dof->storage('departments')->is_access('view', $obj->id) )
            {//покажем ссылку на страницу просмотра
                $link .= '<a href='.$this->dof->url_im('departments','/view.php', array('departmentid' =>
                $departmentid, 'id' => $obj->id)).'>'.$imgview.'</a>&nbsp;';
            }
    	    if (  $this->is_access('datamanage') AND $obj->status != 'deleted')
            {//покажем ссылку на страницу удаления
                $link .= '<a href='.$this->dof->url_im('departments','/delete.php', array('departmentid' =>
                $departmentid, 'id' => $obj->id)).'>'.$imgdelet.'</a>&nbsp;';
            }            
            if ($obj->status == 'deleted')
            {
                $status = $this->dof->get_string($obj->status,'departments');
            }else 
            {
                $status = null;
            }
   	        $data[] = array($link,$name,$obj->code,$manager,$leaddep,$status);
    	}
    	
    	// выводим таблицу на экран
        return $this->print_table($data);
    }
    
    /**
     * Возвращает форму создания/редактирования с начальными данными
     * @param int $id - id записи, значения 
     * которой устанавливаются в поля формы по умолчанию
     * @return moodle quickform object
     */
    public function form($id = NULL, $leaddepid = NULL)
    {
        global $USER;
        // устанавливаем начальные данные
        $customdata = new object;
        if (isset($id) AND ($id <> 0) )
        {// id передано
            $department = $this->form_edit_data($id);
        }else
        {// id не передано
            $department = $this->form_new_data();
        }
        if ( isset($department->addressid) AND ($address = $this->dof->storage('addresses')->get($department->addressid)) )
        {// у подразделения есть адрес и он существует
            // изменим значения по умолчанию
            $address->country = array($address->country,$address->region);
            unset($address->id);
        }else
        {// нету - вставим сами
            $address = new stdClass();
            $address->country = array('RU');
        }
        if ( isset($USER->sesskey) )
        {//сохраним идентификатор сессии
            $department->sesskey = $USER->sesskey;
        }else
        {//идентификатор сессии не найден
            $department->sesskey = 0;
        }
        if ( ! is_null($leaddepid) AND $leaddepid !=0 )
        {
            $department->leaddepid = $leaddepid;
        }
        $customdata->obj = $department;
        $customdata->dof = $this->dof;
        
        // подключаем методы вывода формы
        $depart = optional_param('departmentid', 0, PARAM_INT);
        $dep = optional_param('dep',0,PARAM_INTEGER);

        $path = $this->dof->url_im('departments','/edit.php?departmentid='.$depart.'&dep='.$dep);
        
        $form = new dof_im_edit($path,$customdata);
        // заносим значения по умолчению
        
        $form->set_data($department);
        $form->set_data($address); 
        // возвращаем форму
        return $form;
    }
    /**
     * Возвращает заготовку для формы редактирования подразделения
     * @return stdclassObject
     */
    private function form_edit_data($id)
    {
        $department = $this->dof->storage('departments')->get($id);
        $department->manager = $department->managerid;
        // Определяем, существует ли родительский отдел
        if (!isset($department->leaddepid) OR !$this->dof->storage('departments')->is_exists($department->leaddepid))
        {
            // Родительским будет отдел по умолчанию
            $this->dof->storage('departments')->get_default_id();
        }   
        return $department;
    } 
    /**
     * Возвращает заготовку для формы создания подразделения
     * @return stdclassObject
     */
    private function form_new_data()
    {
        $department = new object;
        $department->id = 0;
        $department->leaddep = $this->dof->storage('departments')->get_default_id();
        $department->zone = 99;
        return $department;
    } 
    /** Возвращает html-код таблицы
     * @param array $date - данные в таблицу
     * @return string - html-код или пустая строка
     */
    private function print_table($date)
    {
        // рисуем таблицу
        $table = new object();
        $table->tablealign = "center";
        $table->cellpadding = 5;
        $table->cellspacing = 5;
        $table->size = array ('100px','150px','150px','200px','150px','100px');
        $table->align = array ("center","center","center","center","center","left");
        // шапка таблицы
        $table->head = $this->get_fields_description();
        // заносим данные в таблицу     
        $table->data = $date;
        return $this->dof->modlib('widgets')->print_table($table,true);
    }
    
    /** Получить заголовок для списка таблицы, или список полей
     * для списка отображения одного объекта 
     * @return array
     */
    private function get_fields_description()
    {
        return array($this->dof->get_string('actions','departments'),
                     $this->dof->get_string('name','departments'),
                     $this->dof->get_string('code','departments'),
                     $this->dof->get_string('manager','departments'),
                     $this->dof->get_string('leaddep','departments'),
                     $this->dof->get_string('status','departments'));  
    }

    /** Получить фрагмент списка подразделений для вывода таблицы 
     * 
     * @return array массив записей из базы, или false в случае ошибки
     * @param int $limitfrom - начиная с какой записи просматривается фрагмент списка записей
     * @param int $limitnum - сколько записей нужно извлечь из базы
     * @param string $select - SQL-код с дополнительными условиями, если потребуется 
     * @param string $sort - по какому полю и в каком порядке сортировать записи 
     * (sql-параметр ORDER BY)
     */
    public function get_listing($conds=null, $limitfrom = null, $limitnum = null, $sort='', $fields='*', $countonly=false)
    {
        if ( ! $conds )
        {// если список подразделений не передан - то создадим объект, чтобы не было ошибок
            $conds = new Object();
        }
        if ( ! is_null($limitnum) AND $limitnum <= 0 )
        {// количество записей на странице может быть 
            //только положительным числом
            $limitnum = $this->dof->modlib('widgets')->get_limitnum_bydefault();
        }
        if ( ! is_null($limitfrom) AND $limitfrom < 0 )
        {//отрицательные значения номера просматриваемой записи недопустимы
            $limitfrom = 0;
        }
        $countselect = $this->get_select_listing($conds);
        //формируем строку запроса
        $select = $this->get_select_listing($conds);
        //определяем порядок сортировки
        $sort = 'leaddepid ASC, name ASC , code ASC, managerid ASC, status ASC';
        // возвращаем ту часть массива записей таблицы, которую нужно
        
        return $this->dof->storage('departments')->get_records_select($select, null, $sort, '*', $limitfrom, $limitnum);
    }
    
    /**
     * Возвращает фрагмент sql-запроса после слова WHERE
     * @param int $departmentid - id подразделения
     * @param string $status - название статуса
     * @return string
     */
    public function get_select_listing($inputconds)
    {
        // создадим массив для фрагментов sql-запроса
        $selects = array();
        $conds = fullclone($inputconds);
        // теперь создадим все остальные условия
        foreach ( $conds as $name=>$field )
        {
            if ( ! is_null($field) AND ! empty($field))
            {// если условие не пустое, то для каждого поля получим фрагмент запроса
                $selects[] = $this->dof->storage('departments')->query_part_select('id',$field);
            }
        }
        $selects[] = "(status <> 'deleted' OR status IS NULL)";
        //формируем запрос
        if ( empty($selects) )
        {// если условий нет - то вернем пустую строку
            return '';
        }elseif ( count($selects) == 1 )
        {// если в запросе только одно поле - вернем его
            return current($selects);
        }else
        {// у нас несколько полей - составим запрос с ними, включив их всех
            return implode($selects, ' AND ');
        }
    }


    
    /** Получить название подразделения в виде ссылки
     * @param int id - id подразделения в таблице departments
     * @param bool $withcode - добавлять или не добавлять код в конце
     * 
     * @return string html-строка со ссылкой на подразделение или пустая строка в случае ошибки
     */
    public function get_html_link($id, $withcode=false)
    {
        if ( ! $name = $this->dof->storage('departments')->get_field($id, 'name') )
        {
            return '';
        }
        if ( $withcode )
        {
            $code = $this->dof->storage('departments')->get_field($id, 'code');
            $name = $name.' ['.$code.']';
        }
        return '<a href="'.$this->dof->url_im($this->code(), 
                    '/view.php', array('departmentid' => $id)).'">'.$name.'</a>';
    }         
}