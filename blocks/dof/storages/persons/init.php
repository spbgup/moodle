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

// подключение интерфейса настроек
require_once($DOF->plugin_path('storage','config','/config_default.php'));

/** Справочник персоналий
 * 
 */
class dof_storage_persons extends dof_storage implements dof_storage_config_interface
{
    /**
     * @var dof_control
     */
    protected $dof;
    // **********************************************
    // Методы, предусмотренные интерфейсом plugin
    // **********************************************
	/** Устанавливает плагин в fdo
	 * @return bool
	 */
	public function install()
	{
	    // Устанавливаем таблицы
        parent::install();
        // Создаем персону под себя
        global $USER;
        if ($USER->id)
        {
            $obj = new object();
            $obj->mdluser = $USER->id;
            $obj->email = $USER->email;
            $obj->lastname = $USER->lastname;
            $obj->sync2moodle = 1;
            $obj->departmentid = $this->dof->storage('departments')->get_default_id();
            $this->insert($obj, true);
        }
        return $this->dof->storage('acl')->save_roles($this->type(),$this->code(),$this->acldefault());
	}
    /** Метод, реализующий обновление плагина в системе
     * Создает или модифицирует существующие таблицы в БД
     * @param string $old_version - версия установленного в системе плагина
     * @return boolean
     * @access public
     */
    public function upgrade($oldversion)
    {
        global $CFG, $DB;
        $dbman = $DB->get_manager();
        $table = new xmldb_table($this->tablename());
        if ($oldversion < 2012030700) 
        {//удалим enum поля
            // для поля noextend
            $field = new xmldb_field('sync2moodle', XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, null, 'mdluser');
            $dbman->drop_enum_from_field($table, $field);
            // для поля gender
            $field = new xmldb_field('gender', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, 'unknown', 'dateofbirth');
            $dbman->drop_enum_from_field($table, $field);
        }
        if ($oldversion < 2013040900)
        {// добавим поле birthadressid
            $field = new xmldb_field('birthaddressid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, 'adddate');
            if ( !$dbman->field_exists($table, $field) )
            {// поле еще не установлено
                $dbman->add_field($table, $field);
            }
            $index = new xmldb_index('ibirthaddressid', XMLDB_INDEX_NOTUNIQUE, 
                     array('birthaddressid'));
            // добавляем индекс для поля
            if ( !$dbman->index_exists($table, $index) ) 
            {// индекс еще не установлен
                $dbman->add_index($table, $index);
            }
            $index = new xmldb_index('idepartmentid', XMLDB_INDEX_NOTUNIQUE, 
                     array('departmentid'));
            // добавляем индекс для поля
            if ( !$dbman->index_exists($table, $index) ) 
            {// индекс еще не установлен
                $dbman->add_index($table, $index);
            }
        }
        return $this->dof->storage('acl')->save_roles($this->type(),$this->code(),$this->acldefault()); 
    }
    /** Возвращает версию установленного плагина
     * @return string
     * @access public
     */
    public function version()
    {
        // Версия плагина (используется при определении обновления)
		return 2013040900;
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
        return 'paradusefish';
    }
    
    /** Возвращает тип плагина
     * @return string 
     * @access public
     */
    public function type()
    {
        return 'storage';
    }
    /** Возвращает короткое имя плагина
     * Оно должно быть уникально среди плагинов этого типа
     * @return string
     * @access public
     */
    public function code()
    {
        return 'persons';
    }
    /** Возвращает список плагинов, 
     * без которых этот плагин работать не может
     * @return array
     * @access public
     */
    public function need_plugins()
    {
		return array('storage'=>array('departments' => 2011051800,
		                              'addresses'   => 2009032400,
                                      'acl'         => 2011041800,
                                      'config'      => 2011080900));
    }
    /** Определить, возможна ли установка плагина в текущий момент
     * Эта функция одинакова абсолютно для всех плагинов и не содержит в себе каких-либо зависимостей
     * @TODO УДАЛИТЬ эту функцию при рефакторинге. Вместо нее использовать наследование
     * от класса dof_modlib_base_plugin 
     * @see dof_modlib_base_plugin::is_setup_possible()
     * 
     * @param int $oldversion[optional] - старая версия плагина в базе (если плагин обновляется)
     *                                    или 0 если плагин устанавливается
     * 
     * @return bool 
     *              true - если плагин можно устанавливать
     *              false - если плагин устанавливать нельзя
     */
    public function is_setup_possible($oldversion=0)
    {
        return dof_is_plugin_setup_possible($this, $oldversion);
    }
    /** Получить список плагинов, которые уже должны быть установлены в системе,
     * и без которых начать установку или обновление невозможно
     * 
     * @param int $oldversion[optional] - старая версия плагина в базе (если плагин обновляется)
     *                                    или 0 если плагин устанавливается
     * @return array массив плагинов, необходимых для установки
     *      Формат: array('plugintype'=>array('plugincode' => YYYYMMDD00));
     */
    public function is_setup_possible_list($oldversion=0)
    {
        if ( ! $oldversion )
        {
            return array('storage'=>array('acl'=>2011040504,
                                      'config'=> 2011080900,
                                      'departments' => 2011080900));
        }
        if ( $oldversion AND $oldversion < 2009032700 )
        {
            return array('storage'=>array('acl'=>2011040504,
                                      'config'=> 2011080900,
                                      'addresses'=>0,
                                      'departments' => 2011080900));
        }
        if ( $oldversion AND $oldversion < 2011050400 )
        {
            return array('storage'=>array('acl'=>2011040504,
                                      'config'=> 2011080900,
                                      'addresses'=>0,
                                      'departments' => 2011080900,
                                      'contracts' => 2011080900));
        }
        
        return array();
    }
    /** Список обрабатываемых плагином событий 
     * @return array - array(array('plugintype'=>..,'plugincode'=>..,'eventcode'=>..),...)
     * @access public
     */
    public function list_catch_events()
    {
        // Пока событий не обрабатываем
        return array();
    }
    /** Требуется ли запуск cron в плагине
     * @return bool
     * @access public
     */
    public function is_cron()
    {
        // Просим запускать крон не чаще раза в 15 минут
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
    public function is_access($do, $objid = NULL, $userid = NULL, $depid = null)
    {
        if ( $this->dof->is_access('datamanage') OR $this->dof->is_access('admin') 
             OR $this->dof->is_access('manage') )
        {// манагеру можно все
            return true;
        }
        // получаем id пользователя в persons
        $personid = $this->dof->storage('persons')->get_by_moodleid_id($userid);
        // получаем все нужные параметры для функции проверки прав
        $acldata = $this->get_access_parametrs($do, $objid, $personid, $depid);   
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
    public function require_access($do, $objid = NULL, $userid = NULL, $depid = null)
    {
        // Используем функционал из $DOFFICE
        //return $this->dof->require_access($do, NULL, $userid);
        if ( ! $this->is_access($do, $objid, $userid, $depid) )
        {
            $notice = "{$this->code()}/{$do} (block/dof/{$this->type()}/{$this->code()}: {$do})";
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
        // Ничего не делаем, но отчитаемся об "успехе"
        return true;
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
        if ($code === 'recalcsortnameall')
        {
            // Нас попросили провести "очистку"
            return $this->remake_all_sortname();
        }
        return true;
    }
    /** Конструктор
     * @param dof_control $dof - объект с методами ядра деканата
     * @access public
     */
    public function __construct($dof)
    {
        // Сохраняем ссылку на DOF, чтоб вызывать его через $this->dof
        $this->dof = $dof;
    }

    /** Возвращает название таблицы без префикса (mdl_)
     * @return text
     * @access public
     */
    public function tablename()
    {
        // Имя таблицы, с которой работаем
        return 'block_dof_s_persons';
    }
    
    /** Получить название объекта из хранилища для отображения или составления ссылки
     * Этот метод переопределяется для тех хранилищ, объекты в которых не имеют поля name
     * @todo дописать алгоритм работы с дополнительными полями
     * 
     * @param int|object - id объекта или сам объект
     * @param array $fields[optional] - список дополнительных полей, которые будут выведены после названия
     * 
     * @return string название объекта
     */
    public function get_object_name($id, array $fields=array())
    {
        if ( is_object($id) )
        {
            $obj = $id;
        }elseif ( is_int_string($id) )
        {
            if ( ! $obj = $this->get($id) )
            {
                dof_debugging(get_class($this).'::get_object_name() object not found!', DEBUG_DEVELOPER);
                return '[[object_not_found!]]';
            }
        }else
        {
            dof_debugging(get_class($this).'::get_object_name() wrong parameter type!', DEBUG_DEVELOPER);
            return '';
        }
        
        return $obj->lastname.' '.$obj->firstname.' '.$obj->middlename;
    }
    

    // ***********************************************************
    //       Методы для работы с полномочиями и конфигурацией
    // ***********************************************************  
    
    /** Получить список параметров для фунции has_hight()
     * 
     * @return object - список параметров для фунции has_hight()
     * @param string $action - совершаемое действие
     * @param int $objectid - id объекта над которым совершается действие
     * @param int $personid
     */
    protected function get_access_parametrs($action, $objectid, $personid, $depid = null)
    {
        $result = new object();
        $result->plugintype   = $this->type();
        $result->plugincode   = $this->code();
        $result->code         = $action;
        $result->personid     = $personid;
        $result->departmentid = $depid;
        if ( is_null($depid) )
        {// подразделение не задано - берем текущее
            $result->departmentid = optional_param('departmentid', 0, PARAM_INT);
        }
        $result->objectid     = $objectid;
        if ( ! $objectid )
        {// если objectid не указан - установим туда 0 чтобы не было проблем с sql-запросами
            $result->objectid = 0;
        }else
        {// если указан - то установим подразделение
            $result->departmentid = $this->dof->storage($this->code())->get_field($objectid, 'departmentid');
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
                              $acldata->personid, $acldata->departmentid, $acldata->objectid);
    }    
      
    /** Возвращает стандартные полномочия доступа в плагине
     * @return array
     *  a[] = array( 'код полномочия'  => array('список ролей'),
     * 				 'roles' => array('student' ,'...');
     */
    public function acldefault()
    {
        $a = array();
        $a['view']         = array('roles'=>array('manager','methodist'));
        $a['view/parent']  = array('roles'=>array('manager', 'parent'));
        $a['view/sellerid']  = array('roles'=>array('manager', 'parent'));        
        $a['edit']         = array('roles'=>array('manager'));
        // право изменять информацию в договоре для законного представителя (если понадобится)
        $a['edit/parent']  = array('roles'=>array('manager', 'parent'));
        $a['use']          = array('roles'=>array('manager','methodist'));
        $a['create']       = array('roles'=>array('manager'));
        $a['delete']       = array('roles'=>array());
        // Это право будет находится здесь если только мы не решим создать workflow
        // для плагина persons 
        $a['changestatus'] = array('roles'=>array(''));
        // право персоне назначать комплект(выдать комплект)
        $a['give_set'] = array('roles'=>array('manager'));
        // право редактировать time_zone
        $a['edit_timezone'] = array('roles'=>array('manager'));
        //право вручную синхронизировать персону с пользователем Moodle
        $a['edit:sync2moodle'] = array('roles'=>array(''));
        return $a;
    }
    
    /** Функция получения настроек для плагина
     *  
     */
    public function config_default($code=null)
    {
        // плагин включен и используется
        $config = array();
        $obj = new object();
        $obj->type = 'checkbox';
        $obj->code = 'enabled';
        $obj->value = '1';
        $config[$obj->code] = $obj;
        // моксимально разрешенное количество объектов этого типа в базе
        // (указывается индивидуально для каждого подразделения)
        $obj = new object();
        $obj->type = 'text';
        $obj->code = 'objectlimit';
        $obj->value = '-1';
        $config[$obj->code] = $obj;        
        return $config;
    }
    
    // **********************************************
    //              Собственные методы
    // **********************************************
    /** Вставляет запись в таблицу(ы) плагина 
     * @param object dataobject 
     * @return mixed bool false если операция не удалась или id вставленной записи
     * @access public
     */
    public function insert($dataobject,$quiet=NULL)
    {

        // Добавляем текущее время
        $dataobject->adddate = time();
        if (!isset($dataobject->status))
        {
            // Статус присваиваем сами
            $dataobject->status = 'normal';            
        }
        
        if (!isset($dataobject->sortname))
        {
            // Имя для сортировки формируем сами
            if (!$dataobject->sortname = $this->make_sortname($dataobject))
            {
                // Ничего нет
                unset($dataobject->sortname);
            }
            
        }
		//$dataobject->statusdate = time();
        // Вызываем метод из родительского класса
        return parent::insert($dataobject,$quiet);
    }
    /** Обновляет запись данными из объекта.
     * Отсутствующие в объекте записи не изменяются.
     * Если id передан, то обновляется запись с переданным id.
     * Если id не передан обновляется запись с id, который передан в объекте
     * @param object dataobject - данные, которыми надо заменить запись в таблице 
     * @param int id - id обновляемой записи
     * @param bool quiet - не генерировать событий
     * @return boolean true если обновление прошло успешно и false во всех остальных случаях
     * @access public
     */
    public function update($dataobject,$id = NULL,$quiet=false)
    {
        if (!isset($dataobject->sortname))
        {
            // Имя для сортировки формируем сами
            if (!$dataobject->sortname = $this->make_sortname($dataobject))
            {
                // Ничего нет
                unset($dataobject->sortname);
            }
        }
        // Вызываем исходный метод обновления
        return parent::update($dataobject,$id,$quiet);
    }
	/** Получить объект по moodleid
     * @param int $userid - id пользователя в moodle
     * @return object - данные персоны
     * @access public
     */
	public function get_bu($userid = NULL,$create=false)
	{
		global $USER;
		if ( is_null($userid) )
		{	// Берем id текущего пользователя
			$userid = $USER->id;
		}
		if ( ! $userid )
		{// юзера не пепредали
		    return false;
		}
		if ( $person = $this->get_record(array('mdluser'=>intval($userid))))
		{
		    return $person;
		}elseif ( $create )
		{   
		    //var_dump($USER);
		    // Нас попросили создать персону по текущему пользователю
		    if ( $userid == $USER->id )
		    {// но только если она соответствует текущему пользователю
    		    if ( $id = $this->reg_moodleuser($USER) )
    		    {
    		        // Возвращаем объект
    		        return $this->get($id);
    		    }
		    }
		}
		return false;
	}
	/** Получить объект по moodleid
     * @param int $muserid - id пользователя в moodle
     * @return object - данные персоны
     * @access public
     */
	public function get_by_moodleid($muserid = NULL,$create=false)
	{
		return $this->get_bu($muserid,$create);
	}
	/** Получить объект по moodleid
     * @param int $muserid - id пользователя в moodle
     * если не указан берется $USER->id
     * @return mixed int id персоны или bool false
     * @access public
     */
	public function get_by_moodleid_id($muserid = NULL,$create=false)
	{
		if (is_object($person = $this->get_by_moodleid($muserid,$create)))
		{
		    return $person->id;
		}
        
		return false;
	}
	/** Получить список синхронизируемых персон
     * @return array - список персон, требующих синхронизации
     * @access public
     */
	public function get_list_synced()
	{
		return $this->get_records(array('sync2moodle' => 1),'sortname ASC');
	}
	/** Получить список неудаленных
     * @return array - список персон
     * @access public
     */
	public function get_list_normal($depid = false,$limitfrom='0',$limitnum='0')
	{
	    if ( $depid )
	    {// только для переданного подразделения
	        return $this->get_records(array('status'=>'normal','departmentid'=>$depid),'sortname ASC','*',$limitfrom,$limitnum);
	    }
		return $this->get_records(array('status'=>'normal'),'sortname ASC','*',$limitfrom,$limitnum);
	}
   /** Получить список персон по запрашиваемой фамилии
     * @param $query - фамилия, которую ищем
     * @param $depid - id записи из таблицы departments
     * @param $children - сообщает, использовать ли дочерние подразделения
     * @return array - список персон
     * @access public
     */
	public function get_list_search_lastname($query, $depid = false, $children = false, $limitfrom=0, $limitnum=0)
	{
		if ( $depid )
	    {// только для переданного подразделения
	        if( $children )
            {
                if ( $department = $this->dof->storage('departments')->get($depid) AND $childids = 
                        $this->dof->storage('departments')->get_records_select("path LIKE '".$department->path."/%'") )
                {
                    $depidstr = $depid;
                    foreach($childids as $key=>$dep)
                    {
                        $depidstr .= ','.$key;
                    }
                    return $this->get_records_select("lastname LIKE '{$query}%' AND departmentid IN (".$depidstr.")", null,'sortname ASC','*', $limitfrom, $limitnum);
                }
            }
            return $this->get_records_select("lastname LIKE '{$query}%' AND departmentid=".$depid, null,'sortname ASC','*', $limitfrom, $limitnum);
	    }
		return $this->get_records_select("lastname LIKE '{$query}%'",null, 'sortname ASC','*', $limitfrom, $limitnum);
	}
    /** Получить список персон по запрашиваемой фамилии
     * @param $query - фамилия, которую ищем 
     * @return array - список персон
     * @access public
     */
	public function get_list_search($query, $depid = false, $children = false, $limitfrom='0', $limitnum='0')
	{
	    $sql = "lastname LIKE '{$query}%' OR firstname LIKE '{$query}%'  "
					."OR middlename LIKE '{$query}%' OR email LIKE '{$query}%' OR (mdluser='{$query}' AND mdluser<>'0') OR id='{$query}'";
					
	    if ( $depid )
	    {// только для переданного подразделения
	        if( $children )
            {
                if ( $department = $this->dof->storage('departments')->get($depid) AND $childids = 
                        $this->dof->storage('departments')->get_records_select("path LIKE '".$department->path."/%'") )
                {
                    $depidstr = $depid;
                    foreach($childids as $key=>$dep)
                    {
                        $depidstr .= ','.$key;
                    }
                    return $this->get_records_select("(".$sql.") AND departmentid IN (".$depidstr.")", null,'sortname ASC','*',$limitfrom, $limitnum);
                }
            }
            return $this->get_records_select("(".$sql.") AND departmentid=".$depid, null,'sortname ASC','*',$limitfrom, $limitnum);
	    }
		return $this->get_records_select($sql, null,'sortname ASC','*',$limitfrom, $limitnum);
	}
	/** Сообщает, используется ли в системе этот е-mail
     * @param string $email - адрес email
	 * @return bool
     * @access public
     */
	public function is_email_unique($email)
	{
		return !(bool) $this->count_list(array('email'=>$email,'status'=>'normal'));	
	}
	
	/** Отправляет письмо персоне
	 * @param $toid - id персоны получателя
	 * @param $subject
	 * @param $messagetext
	 * @param $fromid - id персоны отправителя
	 * @param $messagehtml
	 * @param $attachment
	 * @param $attachname
	 * @return unknown_type
	 */
	public function send_email($toid,$subject, $messagetext,$fromid='',$messagehtml='', $attachment='', $attachname='')
	{
		// Получаем персону-получателя
		if ( ! $personto = $this->get($toid) )
		{
			return false;
		}
		// Если указан id в fromid
		if ( ! empty($fromid) AND ctype_digit($fromid) )
		{
			// Удалось найти персону отправителя 
			if ($personfrom = $this->get($fromid))
			{
				// Синхронизирована ли персона-отправитель с Moodle?
				if ( ! empty($personfrom->sync2moodle) AND !empty($personfrom->mdluser) 
								AND $this->dof->plugin_exists('modlib', 'ama'))
				{
					// Да!
					// Извлекаем пользователя Moodle в качестве отправителя
					$from = $this->dof->modlib('ama')->user($personfrom->mdluser)->get();
				}else
				{
					// Нет!
					// Подставляем только имя отправителя
					$from = $this->get_fullname($personfrom->id);
				}
			}
		}else
		{
			// Приравниваем вместо fromid отправится письмо от noreply с $fromid в качестве имени отправителя
			$from = $fromid;
		}
		
		// Если персона синхронизирована с Moodle и есть плагин ama - посылаем сообщение через ama
		if ( ! empty($personto->sync2moodle) AND ! empty($personto->mdluser) AND $this->dof->plugin_exists('modlib', 'ama'))
		{
			return $this->dof->modlib('ama')->user($personto->mdluser)->send_email($subject, $messagetext, 
															$from, $messagehtml, $attachment, $attachname);
		}
		// Отправку напрямую пока не поддерживаем - нужно добавить метод в dof
		return false;
	}
    
	/** 
	 * Зарегистрировать персону для переданного пользователя Moodle
	 * @param object $USER - объект с пользователем Moodle
	 * @return int - id новой записи в таблице persons
	 * 
	 * @todo вынести проверку на то, что пользователь с таким id в moodle уже существует
	 * в таблице persons в функцию безопасной вставки (safe_insert()) 
	 * @todo добавить возможность задавать параметры для нового пользователя в таблицы persons
	 * (например подразделение, и т. п.)
	 */
	public function reg_moodleuser($USER)
	{
	    if (
	            !is_object($USER)
	            OR !isset($USER->id)
	            OR !$USER->id
	            OR !isset($USER->email)
	            OR !$USER->email
	            OR !isset($USER->firstname)
	            OR !isset($USER->lastname)
	        )
	    {
	        // Нам передали плохой объект без данных пользователя
            return false;
	    }
        // Регистрируем персону
		$obj = new object();
		$obj->mdluser = $USER->id;
		$obj->email = $USER->email;
		$obj->firstname = $USER->firstname;
		$obj->lastname = $USER->lastname;
		$obj->sync2moodle = 1;
		$obj->addressid = null;
        $departmentid = $this->get_cfg('departmentid');
        if ( isset($departmentid) AND $departmentid
            AND $this->dof->storage('departments')->is_exists($departmentid) )
        {
            $obj->departmentid = $departmentid;
        }else
		{
		    $obj->departmentid = $this->dof->storage('departments')->get_default_id();
        }
        // проверим, есть ли в базе уже пользователь с таким id в Moodle
        if ( $person = $this->get_record(array('mdluser' => $USER->id)) )
        {// персона деканата для такого пользователя уже существует - все нормально
            return $person->id;
        }
        // в остальных случаях - регистрируем пользователя moodle в таблице persons
		return $this->insert($obj);
	}
	/**
	 * Пересчитать sortname для всех пользователей
	 * @return unknown_type
	 */
	protected function remake_all_sortname()
	{
	    $persons = $this->get_list_normal();
	    foreach ($persons as $person)
	    {
	       $dataobject = new object();
	       $dataobject->sortname = $this->make_sortname($person); 
	       $this->update($dataobject,$person->id); 
	    }
	    return true;
	}
	
	protected function make_sortname($person)
	{
	    $str = '';
	    if (isset($person->lastname))
	    {
	        if ($str)
	        {
	            // Вставляем разделитель
	            $str .= ' ';
	        }
	        // Дополняем имя для поиска
	        $str .= $person->lastname;
	    }
		if (isset($person->firstname))
	    {
	        if ($str)
	        {
	            // Вставляем разделитель
	            $str .= ' ';
	        }
	        // Дополняем имя для поиска
	        $str .= $person->firstname;
	    }
		if (isset($person->middlename))
	    {
	        if ($str)
	        {
	            // Вставляем разделитель
	            $str .= ' ';
	        }
	        // Дополняем имя для поиска
	        $str .= $person->middlename;
	    }
	    return $str;
	}
	/**
	 * Возвращает полное имя пользователя в формате ФИО 
	 * @param $id - id записи пользователя, 
	 * чье имя необходимо
	 * @return string - полное имя пользователя или 
	 * пустая строка, если пользователь не найден
	 */
	public function get_fullname($id = null)
	{
	    if ( is_null($id) )
	    {// отобразим фио текущего пользователя 
	        global $USER;
	        $user = $this->get_by_moodleid($USER->id);
	    }elseif (is_object($id) AND isset($id->firstname) AND isset($id->lastname)
	        AND isset($id->middlename))
	    {// передан объект, ничего в БД искать не надо
	         $user = $id;   
	    }elseif ( ! $user = $this->get($id) )
	    {//не получили запись пользователя
	        return '';
	    }
	    //сформировали строку ФИО
	    return $user->lastname.' '.$user->firstname.' '.$user->middlename;
	}
	
    /**
	 * Возвращает полное имя пользователя в формате Фамилия И.О. 
	 * @param $id - id записи пользователя, 
	 * чье имя необходимо
	 * @return string - полное имя пользователя или 
	 * пустая строка, если пользователь не найден
	 */
	public function get_fullname_initials($id)
	{
	    if (is_object($id) AND isset($id->firstname) AND isset($id->lastname)
	        AND isset($id->middlename))
	    {
	         $user = $id;   
	    }elseif ( ! $user = $this->dof->storage('persons')->get($id) )
	    {//не получили запись пользователя
	        return '';
	    }
	    //сформировали строку ФИО
	    $str = $user->lastname.' '.mb_substr($user->firstname,0,1,'utf-8').'. ';
	    if ( ! empty($user->middlename) )
	    {
	        $str .= mb_substr($user->middlename,0,1,'utf-8').'.';
	    }
	    return $str;
	}
	
    /**
     * Вернуть массив с настройками или одну переменную
     * @param $key - переменная
     * @return mixed
     */
    protected function get_cfg($key=null)
    {
    	// Возвращает параметры конфигурации
    	include_once ($this->dof->plugin_path($this->type(),$this->code(),'/cfg/personcfg.php'));
    	if (empty($key))
    	{
    		return $storage_persons;
    	}else
    	{
    		return @$storage_persons[$key];
    	}
    } 
    
    /** Возвращает список персон по заданным критериям 
     * 
     * @return array массив записей из базы, или false в случае ошибки
     * @param int $limitfrom - начиная с какой записи просматривается фрагмент списка записей
     * @param int $limitnum - сколько записей нужно извлечь из базы
     * @param object $conds[optional] - объект со списком свойств, по которым будет происходить поиск
     * @param object $countonly[optional] - только вернуть количество записей по указанным условиям
     */
    public function get_listing($conds=null, $limitfrom = null, $limitnum = null, $sort='', $fields='*', $countonly=false)
    {
        
        if ( ! $conds )
        {// если список потоков не передан - то создадим объект, чтобы не было ошибок
            $conds = new Object();
        }
        $conds = (object) $conds;
        if ( ! is_null($limitnum) AND $limitnum <= 0 )
        {// количество записей на странице может быть 
            //только положительным числом
            $limitnum = $this->dof->modlib('widgets')->get_limitnum_bydefault();
        }
        if ( ! is_null($limitfrom) AND $limitfrom < 0 )
        {//отрицательные значения номера просматриваемой записи недопустимы
            $limitfrom = 0;
        }
        //формируем строку запроса
        $select = $this->get_select_listing($conds);
        // возвращаем ту часть массива записей таблицы, которую нужно
        $tblpersons = $this->prefix().$this->tablename();
        // @todo - пока выборка происходит без дополнительных таблиц - переопределение select не нужно
        //if (strlen($select)>0)
        //{// сделаем необходимые замены в запросе
        //    $select = 'c.'.ereg_replace(' AND ',' AND c.',$select.' ').' AND ';
        //    $select = ereg_replace(' OR ',' OR c.',$select);
        //    $select = str_replace('c. (','(c.',$select);
        //}
        if ( $select )
        {
            $select = "WHERE {$select}";
        }
        $fields = "*";
        $sql = " FROM {$tblpersons}
                $select ";
        
        if ( $countonly )
        {// посчитаем общее количество записей, которые нужно извлечь
            return $this->count_records_sql("SELECT COUNT({$fields}) {$sql}");
        }
        // Добавим сортировку
        $sql .= $this->get_orderby_listing($sort);
        return $this->get_records_sql("SELECT {$fields} {$sql}", null, $limitfrom, $limitnum);
    }
    
    /** Возвращает фрагмент sql-запроса после слова WHERE
     * @param object $inputconds - список полей с условиями запроса в формате "поле_БД->значение" 
     * @param string $prefix - префикс к полям, если запрос составляется для нескольких таблиц
     * @return string
     */
    public function get_select_listing($inputconds,$prefix='')
    {
        // создадим массив для фрагментов sql-запроса
        $selects = array();
        $conds = fullclone($inputconds);
        if ( isset($conds->fioemailmdluser) AND $conds->fioemailmdluser )
        {
    		$selects[] = "({$prefix}lastname LIKE '{$conds->fioemailmdluser}%' "
    		            ."OR {$prefix}firstname LIKE '{$conds->fioemailmdluser}%'  "
					    ."OR {$prefix}middlename LIKE '{$conds->fioemailmdluser}%' "
					    ."OR {$prefix}email LIKE '{$conds->fioemailmdluser}%' "
					    ."OR ({$prefix}mdluser='{$conds->fioemailmdluser}' AND {$prefix}mdluser<>'0') "
					    ."OR {$prefix}id='{$conds->fioemailmdluser}')";
    		unset($conds->fioemailmdluser);
        }
        if ( isset($conds->childrendepid) AND intval($conds->childrendepid) )
        {
            $childids = array();
            if ( $childs = $this->dof->storage('departments')->get_records_select("path LIKE '"
                    .$this->dof->storage('departments')->get_field($conds->childrendepid,'path')."/%'") )
            {// есть дочки - добавим их к запросу
                foreach($childs as $dep)
                {
                    $childids[] = $dep->id;
                }
            }
            if ( isset($conds->departmentid) )
            {// есть подразделение - добавим его к поиску
                $childids[] = $conds->departmentid;
            }
            $selects[] = "{$prefix}departmentid IN (".implode(',',$childids).")";
            unset($conds->departmentid);
    		unset($conds->childrendepid);
        }
        if ( isset($conds->lastname) AND $conds->lastname )
        {
    		$selects[] = "{$prefix}lastname LIKE '{$conds->lastname}%' ";
    		unset($conds->lastname);
        }
        if ( ! empty($conds) )
        {// теперь создадим все остальные условия
            foreach ( $conds as $name=>$field )
            {
                if ( $field )
                {// если условие не пустое, то для каждого поля получим фрагмент запроса
                    $selects[] = $this->query_part_select($prefix.$name,$field);
                }
            } 
        }
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
    
    /**
     * Возвращает фрагмент sql-запроса c ORDER BY
     * @param object $inputconds - список полей с условиями запроса в формате "поле_БД->значение" 
     * @return string
     */
    public function get_orderby_listing($sort)
    {
        if ( is_null($sort) OR empty($sort) )
        {
            return ''; 
        }
        return " ORDER BY {$sort} ASC";
    }
    
    /**
     * Поск персон по имени(выбирает персон, которые имеют cstream в этом подразделениии(ученики/учителя))
     * Отображает уроки за промежуток времени
     * @param string $name - имя для поиска персоны( не полное(like) )
     * @param integer $depid - текущее подразделение(по умолчанию-все подразделения) 
     * @return array
     */
    public function get_person_lastname($name, $depid=0)
    {
        // таблицы
        $tbl = $this->prefix().$this->tablename();
        $tblcstreams = $this->prefix().$this->dof->storage('cstreams')->tablename();
        $tblcpassed   = $this->prefix().$this->dof->storage('cpassed') ->tablename();
        
        $csdep = '';
        if ( $depid )
        {// учитываем подразделение
            $csdep = "AND cs.departmentid={$depid}";
        }
        // сам запрос
        $sql = "SELECT DISTINCT per.id as id, per.sortname as sortname from {$tbl} as per, {$tblcpassed} as cpas, {$tblcstreams} as cs 
				 WHERE cs.status<>'canceled' {$csdep} AND ( (cs.teacherid=per.id AND per.lastname LIKE '{$name}%') OR
            	  (cpas.cstreamid=cs.id AND cpas.status<>'canceled' AND cpas.studentid=per.id AND per.lastname LIKE '{$name}%') ) 
            	  ORDER BY per.sortname";
        return $this->get_records_sql($sql);
        
    }
    
    /** Обработка AJAX-запросов из форм
     * @param string $querytype - тип запроса
     * @param int $objectid - id объекта с которым производятся действия
     * @param array $data - дополнительные данные пришедшие из json-запроса
     * 
     * @return array
     */
    public function widgets_field_variants_list($querytype, $depid, $data)
    {
        switch ( $querytype )
        {
            // список персон для autocomplete-элемента
            // результат содержит список неудаленных персон из базы в формате personid => ФИО
            case 'persons_list':    return $this->widgets_persons_list($depid, $data);
            // список пользователей moodle для autocomplete
            case 'mdluser_list':    return $this->widgets_mdluser_list($data);
            // ???
            // @todo определить для чего нужен этот тип запроса
            case 'person_name':     return $this->result_of_autocomplete('person_name', $depid, $data);
            // список персон для выдачи комплекта оборудования
            case 'person_give_set': return $this->result_of_autocomplete('person_give_set', $depid, $data);
            default: return array(0 => $this->dof->modlib('ig')->igs('choose'));
        }
    }
    
    /** Получить список персон по первым буквам фамилии
     * @todo добавить проверку прав: проверять что получатель списка имеет право "use" в storage/persons
     * @param int $departmenid - подразделение, в котором ищутся пользователи
     *                           если передан 0 - то пользователи ищутся во всех подразделениях
     * @param string $lastname - первые несколько букв фамилии пользователя
     *
     * @return array массив объектов для AJAX-элемента dof_autocomplete
     */
    protected function widgets_persons_list($departmentid, $lastname)
    {
        $params = null;
        $lastname = clean_param($lastname, PARAM_TEXT);
        
        if ( is_int_string($lastname) )
        {// персону ищут по id
            $id = $lastname;
            $select = ' id = :id AND status != "deleted" AND status != "archived" ';
            $params = array('id' => $id);
        }else
        {// персону ищут по ФИО
            if ( mb_strlen($lastname) < 3 )
            {// Слишком короткий фрагмент фамилии - не начинаем поиск
                return array();
            }
            if ( $departmentid )
            {// ищем персон конкретного подразделения
                // @wisdom по непонятным причинам макроподстановки в LIKE-шаблонах не работают
                // Поэтому подставляем $lastname в запрос как есть
                $select = ' lastname LIKE "'.$lastname.'%" AND status != "deleted" 
                            AND status != "archived" AND departmentid=:departmentid';
                $params = array('departmentid' => $departmentid);
            }else
            {// ищем всех персон
                $select = ' lastname LIKE "'.$lastname.'%" AND status != "deleted" AND status != "archived"';
            }
        }
        
        if ( ! $persons = $this->get_records_select($select, $params, ' lastname ASC', 
            'id, firstname, lastname, middlename, sortname', 0, 15) )
        {// Нет пользователей с такой фамилией
            return array();
        }
        
        // Формируем массив объектов нужной структуры для dof_autocomplete
        $result = array();
        foreach ( $persons as $person )
        {
            $obj = new stdClass;
            $obj->id   = $person->id;
            $obj->name = $this->get_fullname($person).' ['.$person->id.']';
            $result[$person->id] = $obj;
        }
        
        return $result;
    }
    
    /** Получить список пользователей Moodle по первым буквам фамилии
     * @param string $lastname - первые несколько букв фамилии пользователя
     *
     * @return array массив объектов для AJAX-элемента dof_autocomplete
     */
    protected function widgets_mdluser_list($lastname)
    {
        $lastname = clean_param($lastname, PARAM_TEXT);
        
        if ( is_int_string($lastname) )
        {// пользователя Moodle ищут по id
            $id = $lastname;
            if ( ! $this->dof->modlib('ama')->user(false)->is_exists($id) )
            {// нет пользователя с таким id
                return array();
            }
            $users = array($id => $this->dof->modlib('ama')->user($id)->get() );
        }else
        {// пользователя Moodle ищут по ФИО
            if ( mb_strlen($lastname) < 3 )
            {// Слишком короткий фрагмент фамилии - не начинаем поиск
                return array();
            }
            $conditions = new stdClass;
            $conditions->lastname = $lastname;
            // Ищем пользователя по фамилии
            if ( ! $users = $this->dof->modlib('ama')->user(false)->search($conditions, 'lastname ASC', 0, 15) )
            {// не нашли
                return array();
            }
        }
        
        // Формируем массив объектов нужной структуры для dof_autocomplete
        $result = array();
        foreach ( $users as $user )
        {
            $obj = new stdClass;
            $obj->id   = $user->id;
            $obj->name = $user->lastname.' '.$user->firstname.' ['.$user->id.']';
            $result[$user->id] = $obj;
        }
        
        return $result;
    }
    
    /** Метод, который возаращает список для автозаполнения
     * @todo следует более внятно назвать типы запросов и проставить комментарии 
     *       для типа запроса "person_name" - сейчас невозможно понять где он используется и зачем нужен
     * @todo разбить эту функцию на 2 (по 1 функции на каждый тип запроса). Сейчас она слишком длинная
     * 
     * @param string $querytype - тип завпроса(поу молчанию стандарт)
     * @param string $data - строка
     * @param integer $depid - id подразделения  
     * 
     * @return array or false - запись, если есть или false, если нет
     */
    public function result_of_autocomplete($querytype, $depid, $data)
    {
        if ( ! $data )
        {// пустые даные
            return false;
        }
        // таблица выборки
        $tbl = $this->prefix().$this->tablename();
        $tblcstreams = $this->prefix().$this->dof->storage('cstreams')->tablename();
        $tblcpassed   = $this->prefix().$this->dof->storage('cpassed') ->tablename();
        // от типа запроса - своя выборка
        switch ($querytype)
        {
            case 'person_name' :
        
                $data = $this->get_sql_fio($data, 'per');
                $csdep = '';
                // выбираем учителей
                $sqlteacher = "SELECT DISTINCT per.id as id, per.sortname as sortname from {$tbl} as per INNER JOIN {$tblcstreams} as cs
                				ON per.id=cs.teacherid 
        						WHERE cs.status<>'canceled' {$csdep} {$data}";
                if ( ! $selectteacher =  $this->get_records_sql($sqlteacher, null, 0, 10) )
                {// создадим пустой, дабы не было ошибок при объединении массивов
                    $selectteacher = array();
                }
                // выбираем учиников
                $sqlstudent = "SELECT DISTINCT per.id as id, per.sortname as sortname from {$tbl} as per INNER JOIN {$tblcpassed} as cpas 
                				ON per.id=cpas.studentid INNER JOIN {$tblcstreams} as cs ON  cpas.cstreamid=cs.id
        						WHERE cpas.status<>'canceled' {$data} AND cs.status<>'canceled' 
        						  {$csdep} ";
                if ( ! $selectstudent =  $this->get_records_sql($sqlstudent, null, 0, 10) )
                {// создадим пустой, дабы не было ошибок при объединении массивов
                    $selectstudent = array();
                }                
                // объединим результаты
                $select = $selectstudent + $selectteacher;
                $mas = array();
                // сделаем в порядке ключ->значение
                if ( $select )
                {
                    foreach ( $select as $key=>$obj )
                    {// создаем массив объектов для json
                        $a = new object;
                        $a->id = $obj->id;
                        $a->name = $obj->sortname;
                        $mas[$obj->id] = $a;
                    }
                    // отсортируем по фамилии
                    asort($mas);
                }    
                return $mas;
           // выдача комплекта персоне
           // ищем всех персон в системе, критерии - имя и фильтр на права
           case 'person_give_set' : 
               // кол найденных персон
               $mas = array();
               // ветвь отслеживания по id персоны из системы
               $id = (int)$data;
               
               // передали id
               // выведеи по id персону
               if ( $id )
               {
                   if ( $person = $this->get_record(array('id' => $id)) AND $this->is_access('give_set',$person->id) )
                   {// нашли персону
                       $per = new object;
                       $per->name = '';
                       $per->id = $person->id;
                       if ( empty($person->sortname) )
                       {
                           if ( ! empty($person->lastname) )
                           {
                               $per->name .= $person->lastname.' ';
                           }
                           if ( ! empty($person->firstname) )
                           {
                               $per->name .= $person->firstname.' ';
                           }                           
                           if ( ! empty($person->middlename) )
                           {
                               $per->name .= $person->middlename.' ';
                           }                             
                       }else 
                       {
                           $per->name = $person->sortname;    
                       }                       
                       $mas[$person->id] = $per;
                       return $mas;
                   }
                   return $mas; 
                      
               }
               
               $data = $this->get_sql_fio($data);
               // счетчик
               $num = 0;

               while ( count($mas) < 10 )
               {
                   // берем по 100 персон
                   if ( $persons = $this->get_records_select(" status='normal' {$data}", null,'sortname', '*', $num, 100) )
                   {   
                       // проверяем на права
                       foreach ( $persons as $person )
                       {
                           if ( $this->is_access('give_set',$person->id) )
                           {
                               $per = new object;
                               $per->name = '';
                               $per->id = $person->id;
                               if ( empty($person->sortname) )
                               {
                                   if ( ! empty($per->lastname) )
                                   {
                                       $per->name .= $per->lastname.' ';
                                   }
                                   if ( ! empty($per->firstname) )
                                   {
                                       $per->name .= $per->firstname.' ';
                                   }                           
                                   if ( ! empty($per->middlename) )
                                   {
                                       $per->name .= $per->middlename.' ';
                                   }                             
                               }else 
                               {
                                   $per->name = $person->sortname;    
                               } 
                               $mas[$person->id] = $per;
                               // нашли 10 - выходим, больше не надо
                               if ( count($mas) == 10 )
                               {
                                   return $mas;
                               }
                           }
                       }
                   }else 
                   {
                       return $mas;    
                   }    
                   // следующие 100
                   $num += 100;
               }
               
                return $mas;
            default:
        }
        // нет ни одного из типа
        return false;
    }    
    

    /** Метод возвращает строку для sql-запроса
     * с поиском по персонам ФИО
     * 
     * @param string $$tbl - имя таблицы персон(по умолчанию mdl_block_dof_s_persons )
     * @param string $data - строка с данными для выборки(Ф И О)(Иванов Максим Петрович)
     * 
     * @return string - строку с готовым sql-кодом, начинающ на слово ' AND'
     **/    
    public function get_sql_fio($data='', $tbl='')
    {
        if (empty($data))
        {// пусто - и вернем пустую строку
            return '';
        }
        if ( ! empty($tbl) )
        {// таблица персоны
            $tbl .= "." ;
        }
        // уберем пробелы по краям
        $data = trim($data);
        // разобьём массив на пробелы
        $mas = explode(" ", $data);
        switch (count($mas))
        {
            case 1: return " AND {$tbl}lastname LIKE '".$mas[0]."%'"; break; 
            case 2: return " AND {$tbl}lastname='".$mas[0]."' AND {$tbl}firstname LIKE '".$mas[1]."%'"; break; 
            case 3: return " AND {$tbl}lastname='".$mas[0]."' AND {$tbl}firstname='".$mas[1]."' 
            		AND {$tbl}middlename LIKE '".$mas[2]."%'"; break;   
            default: return '';
        }
    }
    
    /** Получить часовой пояс персоны по ее id
     * 
     * @return float - часовой пояс в UTC (как положительное или отричательное дробное число) или false
     * @param int $personid[optional] - id пользователя в таблице persons. 
     *                        Если не передано - то берется текущий пользователь
     */
    public function get_usertimezone_as_number($personid = null)
    {
        if ( ! $personid )
        {
            $person = $this->get_by_moodleid();
        }
        
        if ( ! isset($person->mdluser) OR ! $person->mdluser )
        {// пользователя Moodle нет а у нас в таблице временные 
            // зоны не хранятся - не знаем что делать
            return false;
        }
        
        if ( ! $this->dof->modlib('ama')->user(false)->is_exists($person->mdluser) )
        {// пользователя нет в Moodle - не можем получит временную зону
            return false;
        }
        // пользователь есть в moodle - получаем его вместе с таймзоной
        $user = $this->dof->modlib('ama')->user($person->mdluser)->get();
        
        return $user->timezone;
    }
    
    /** Получить дату и время с учетом часового пояса
     * 
     * @return string - время с учетом часового пояса или пустая строка
     * @param int $date - время в unixtime
     * @param string $format - формат даты с учетом символов используемых в strftime
     * @param int $mdluserid - id пользователя в moolde
     * @param boolean $fixday - true стирает нуль перед %d
     *                          false - не стирает
     */
    public function get_userdate($date, $format = '', $personid = null, $fixday = false)
    {
        $mdluserid = null;
        if ( ! is_null($personid) )
        {   // Берем id текущего пользователя
            $mdluserid = $this->get_field($personid,'mdluser');
        }
        return $this->dof->sync('personstom')->get_userdate($date,$format,$mdluserid,$fixday);
    }
    
    /** Получить дату и время с учетом часового пояса
     * 
     * @return array - время с учетом часового пояса или пустая строка
     * @param int $date - время в unixtime
     * @param int $mdluserid - id пользователя в moolde
     */
    public function get_usergetdate($date, $personid = null)
    {
        $mdluserid = null;
        if ( ! is_null($personid) )
        {   // Берем id текущего пользователя
            $mdluserid = $this->get_field($personid,'mdluser');
        }
        return $this->dof->sync('personstom')->get_usergetdate($date,$mdluserid);
    }
    /** Получить дату и время с учетом часового пояса
     * 
     * @return array - время с учетом часового пояса или пустая строка
     * @param int $date - время в unixtime
     * @param int $mdluserid - id пользователя в moolde
     */
    public function get_make_timestamp($hour=0, $minute=0, $second=0, $month=1, $day=1, $year=0, $personid = null, $applydst=true)
    {
        $mdluserid = null;
        if ( ! is_null($personid) )
        {   // Берем id текущего пользователя
            $mdluserid = $this->get_field($personid,'mdluser');
        }
        return $this->dof->sync('personstom')->get_make_timestamp($hour, $minute, $second, $month, $day, $year, $mdluserid, $applydst);
    }
}
?>