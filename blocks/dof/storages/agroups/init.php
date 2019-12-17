<?php
////////////////////////////////////////////////////////////////////////////
//                                                                        //
// NOTICE OF COPYRIGHT                                                    //
//                                                                        //
// Dean`s Office for Moodle                                               //
// Электронный деканат                                                    //
// <http://sourceforge.net/projects/freedeansoffice/>                     //
//                                                                        //
// Copyright (C) 2008-2999  Alex Djachenko (Алексей Дьяченко)             //
// alex-pub@my-site.ru                                                    //
// Copyright (C) 2008-2999  Pupinin Dmitry (Пупынин Дмитрий)              //
// dlnsk@mail.ru                                                          //
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

/** Академические группы
 * 
 */
class dof_storage_agroups extends dof_storage implements dof_storage_config_interface
{
    /**
     * @var dof_control
     */
    protected $dof;
    // **********************************************
    // Методы, предусмотренные интерфейсом plugin
    // **********************************************

    public function install()
    {
        if ( ! parent::install() )
        {
            return false;
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
        global $DB;
        $dbman = $DB->get_manager();
        
        $result = true;
        $table = new xmldb_table($this->tablename());
        if ($oldversion < 2012110300)
        {
            // добавляем поле метаконтрактов
            $field = new xmldb_field('metacontractid',XMLDB_TYPE_INTEGER, '7', 
                    null, null, null, null, 'status'); 
            if ( !$dbman->field_exists($table, $field) )
            {// если поле еще не установлено
                $dbman->add_field($table, $field);
                               
            }
            // добавляем индекс к полю
            $index = new xmldb_index('imetacontractid', XMLDB_INDEX_NOTUNIQUE, 
                    array('metacontractid'));
            if (!$dbman->index_exists($table, $index)) 
            {// если индекс еще не установлен
                $dbman->add_index($table, $index);
            }        
        }
        if ($oldversion < 2013062700)
        {// добавим поле salfactor
            $field = new xmldb_field('salfactor', XMLDB_TYPE_FLOAT, '6', XMLDB_UNSIGNED, 
                    true, null, '1', 'metacontractid');
            // количество знаков после запятой
            $field->setDecimals('2');
            if ( !$dbman->field_exists($table, $field) )
            {// поле еще не установлено
                $dbman->add_field($table, $field);
            }
            // добавляем индекс к полю
            $index = new xmldb_index('isalfactor', XMLDB_INDEX_NOTUNIQUE,
                    array('salfactor'));
            if (!$dbman->index_exists($table, $index))
            {// если индекс еще не установлен
                $dbman->add_index($table, $index);
            }
        }
        if ($oldversion < 2013082800)
        {// добавим поле salfactor
            dof_hugeprocess();
            $index = new xmldb_index('isalfactor', XMLDB_INDEX_NOTUNIQUE,
                    array('salfactor'));
            if ($dbman->index_exists($table, $index))
            {// если индекс еще не установлен
                $dbman->drop_index($table, $index);
            }
            $field = new xmldb_field('salfactor', XMLDB_TYPE_FLOAT, '6, 2', null, 
                    XMLDB_NOTNULL, null, '0', 'metacontractid');
            $dbman->change_field_default($table, $field);
            if (!$dbman->index_exists($table, $index))
            {// если индекс еще не установлен
                $dbman->add_index($table, $index);
            }
            while ( $list = $this->get_records_select('salfactor = 1',null,'','*',0,100) )
            {
                foreach ($list as $agroup)
                {// ищем уроки где appointmentid не совпадает с teacherid
                    $obj = new stdClass;
                    $obj->salfactor = 0;
                    $this->update($obj,$agroup->id);
                }               
            }
            
        }
        
        // обновляем полномочия, если они изменились
        return $result && $this->dof->storage('acl')->save_roles($this->type(),$this->code(),$this->acldefault());
    }
    /** Возвращает версию установленного плагина
     * @return string
     * @access public
     */
    public function version()
    {
        // Версия плагина (используется при определении обновления)
		return 2013082800;
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
        return 'agroups';
    }
    /** Возвращает список плагинов, 
     * без которых этот плагин работать не может
     * @return array
     * @access public
     */
    public function need_plugins()
    {
		return array('storage'=>array('departments'   => 2009040800,
		                              'programms'     => 2009040800,
		                              'acl'           => 2011040504,
                                      'config'        => 2011080900,
                                      'metacontracts' => 2012101500) 
                                      );
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
        return array('storage'=>array('acl'=>2011040504,
                                      'config'=> 2011080900));
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
        return 'block_dof_s_agroups';
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
        
    /** Задаем права доступа для объектов этого хранилища
     * 
     * @return array
     */
    public function acldefault()
    {
        $a = array();
        
        $a['view']   = array('roles'=>array('manager','methodist'));
        $a['edit']   = array('roles'=>array('manager'));
        $a['use']    = array('roles'=>array('manager','methodist'));
        $a['create'] = array('roles'=>array('manager'));
        $a['delete'] = array('roles'=>array());
        $a['edit:programmid'] = array('roles'=>array(''));
        $a['edit:departmentid'] = array('roles'=>array('manager'));
                              
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
		// Проверняем код на уникальность
		if ($this->is_exists(array('code'=>$dataobject->code)))
		{   // Код не уникален
			return false;
		}
        // Вызываем метод из родительского класса
        return parent::insert($dataobject,$quiet);
    }

    /** Получить все группы, подписанные на учебный процесс с данным id. 
     * @param int $csid - id учебного процесса
     * @return mixed array список групп или bool false если группы не найдены
     */
    public function get_group_cstream($csid)
    {
        if ( ! $cstream = $this->dof->storage('cstreamlinks')->get_cstream_cstreamlink($csid) )
        {
        	return false;
        }

        return $this->get_list_by_list($cstream,'agroupid');
    }    
    
    /** Возвращает список групп не связаных с данным потоком
     * @param int $csid - id учебного процесса
     * @return array - список групп 
     */
    public function get_group_nocstream($csid)
    {
        if ( ! $piteamid = $this->dof->storage('cstreams')->get_field($csid,'programmitemid') )
        {// нет id предмета - нет групп
            return false;
        }
        if ( ! $progid = $this->dof->storage('programmitems')->get_field($piteamid,'programmid') )
        {// нет id программы - нет групп
            return false;
        }
        // найдем группы привязанные к потоку
        if ( ! $agroups = $this->get_group_cstream($csid) )
        {// если таковых нет - вернем все
            return $this->get_records(array('programmid'=>$progid));
        }
        $selects = array();
        $selects[] = 'programmid='.$progid;
        // для каждой группы сформируем запрос
        foreach ($agroups as $group)
        {// id не должно равнятся id найденной группы
            $selects[] = ' id != '.$group->id;
        }
        // разделим запросы оператором AND
        $select = implode($selects, ' AND ');
        // добавим сортировку
        $select .= " ORDER BY code ASC";
        // вернем список групп
        return $this->get_records_select($select);
        
    }

    /** Возвращает список учебных групп по заданным критериям 
     * 
     * @return array массив записей из базы, или false в случае ошибки
     * @param int $limitfrom - начиная с какой записи просматривается фрагмент списка записей
     * @param int $limitnum - сколько записей нужно извлечь из базы
     * @param object $conds - список параметров для выборки периодов 
     */
    public function get_listing($conds=null, $limitfrom = null, $limitnum = null, $sort='', $fields='*', $countonly=false)
    {
        if ( is_null($conds) )
        {// если список периодов не передан - то создадим объект, чтобы не было ошибок
            $conds = new Object();
        }
        $conds = (object) $conds;
        if ( $limitnum <= 0 AND ! is_null($limitnum) )
        {// количество записей на странице может быть 
            //только положительным числом
            $limitnum = $this->dof->modlib('widgets')->get_limitnum_bydefault(); 
        }
        if ( $limitfrom < 0 AND ! is_null($limitnum) )
        {//отрицательные значения номера просматриваемой записи недопустимы
            $limitfrom = 0;
        }
        //формируем строку запроса
        $select = $this->get_select_listing($conds);
        // посчитаем общее количество записей, которые нужно извлечь
        if ( $countonly )
        {// посчитаем общее количество записей, которые нужно извлечь
            return $this->count_records_secect($select);
        }
        //определяем порядок сортировки
        if ( empty($sort) )
        {
            $sort = 'name ASC, departmentid ASC, programmid ASC, status ASC';
        }
        // возвращаем ту часть массива записей таблицы, которую нужно
        return $this->get_records_select($select,null,$sort,$fields,$limitfrom,$limitnum);
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
        if ( isset($conds->nameorcode) AND strlen(trim($conds->nameorcode)) )
        {// для имени используем шаблон LIKE
            $selects[] = "( ".$prefix."name LIKE '%".$conds->nameorcode."%' OR ".$prefix."code='".$conds->nameorcode."')";
            // убираем имя из запроса для того чтобы не создать 2 условия для одного поля
            unset($conds->nameorcode);
        }
        // теперь создадим все остальные условия
        foreach ( $conds as $name=>$field )
        {
            if ( $field )
            {// если условие не пустое, то для каждого поля получим фрагмент запроса
                $selects[] = $this->query_part_select($prefix.$name,$field);
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

    // **********************************************
    //              Устаревшие методы
    // **********************************************

    /** Поиск группы по коду 
     * @deprecated эта функция устарела, не используйте ее в новых плагинах
     * @param string $code - код группы
     * @return mixed object объект группы или bool false если группа не найдена 
     */
    public function search_group_code($code)
    {
        dof_debugging('storage/agroups search_group_code.Этот метод не используется', DEBUG_DEVELOPER);
        if ( ! is_int_string($code) )
        {//входные данные неверного формата 
            return false;
        }
        return $this->get_filter('code', $code);
    }
    
    /** Получить все группы, обучающиеся по учебной программе 
     * @deprecated эта функция устарела, не используйте ее в новых плагинах 
     * @param int $prid - id программы
     * @param string $status - статус группы, по умолчанию - обучается
     * @return mixed array список групп или bool false если группы не найдены
     */
    public function get_groups_programm($prid, $status = 'learn', $agenum = null, $dpid = null)
    {
        dof_debugging('storage/agroups get_groups_programm.Этот метод не используется', DEBUG_DEVELOPER);
        if ( ! is_int_string($prid) )
        {//входные данные неверного формата 
            return false;
        }
        $select = 'programmid = '.$prid;
        if ( is_string($status) )
        {
            $select = $select.' AND status = \''.$status.'\'';
        }
        if ( ! is_null($agenum) )
        {
            $select .= ' AND agenum='.$agenum;
        }
        if ( ! is_null($dpid) )
        {
            $select .= ' AND departmentid = '.$dpid;
        }
        return $this->get_records_select($select);
    }
    
    /** Получить все группы, относящиеся к структурному подразделению и обучающиеся по программе 
     * @deprecated эта функция устарела, не используйте ее в новых плагинах
     * @param int $dpid - id структурного подразделения
     * @param int $prid - id программы,  по-умолчанию - все
     * @param string $status - статус группы, по умолчанию - обучается
     * @return mixed array список групп или bool false если группы не найдены
     */
    public function get_group_department($dpid, $prid = null, $status = 'learn')
    {
        dof_debugging('storage/agroups get_group_department.Этот метод не используется', DEBUG_DEVELOPER);
        if ( ! is_int_string($dpid) )
        {//входные данные неверного формата 
            return false;
        }
        $select = 'departmentid = '.$dpid;
        if ( is_int_string($prid) )
        {
            $select = $select.' AND programmid = '.$prid;
        }
        if ( is_string($status) )
        {
            $select = $select.' AND status = \''.$status.'\'';
        }
        return $this->get_records_select($select);
    }
    
    /** Возвращает количество групп
     * @deprecated эта функция устарела, не используйте ее в новых плагинах
     * @param string $select - критерии отбора записей
     * @return int количество найденных записей
     */
    public function get_numberof_agroups($select)
    {
        dof_debugging('storage/agroups get_numberof_agroups.Этот метод не имеет смысла', DEBUG_DEVELOPER);
        return $this->count_select($select);
    }
    
}
?>