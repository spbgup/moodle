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

/** Справочник подписок на учебные программы
 * 
 */
class dof_storage_programmsbcs extends dof_storage implements dof_storage_config_interface
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
        // Модификация базы данных через XMLDB
        $result = true;
        
        $dbman = $DB->get_manager();
        $table = new xmldb_table($this->tablename());
        if ($oldversion < 2013062700)
        {// добавим поле salfactor
            $field = new xmldb_field('salfactor', XMLDB_TYPE_FLOAT, '6', XMLDB_UNSIGNED, 
                    true, null, '1', 'dateend');
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
                    XMLDB_NOTNULL, null, '0', 'dateend');
            $dbman->change_field_default($table, $field);
            if (!$dbman->index_exists($table, $index))
            {// если индекс еще не установлен
                $dbman->add_index($table, $index);
            }
            while ( $list = $this->get_records_select('salfactor = 1',null,'','*',0,100) )
            {
                foreach ($list as $schevent)
                {// ищем уроки где appointmentid не совпадает с teacherid
                    $obj = new stdClass;
                    $obj->salfactor = 0;
                    $this->update($obj,$schevent->id);
                }               
            }
            
        }
        return $result && $this->dof->storage('acl')->save_roles($this->type(),$this->code(),$this->acldefault());// применяем обновления
 
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
        return 'programmsbcs';
    }
    /** Возвращает список плагинов, 
     * без которых этот плагин работать не может
     * @return array
     * @access public
     */
    public function need_plugins()
    {
		return array('storage'=>array('contracts' => 2009101200,
                                      'acl'       => 2011041800,
                                      'config'    => 2011080900));
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
        return array(
                     // обрабатываем создание, изменение, или удаление новой подписки
                     array('plugintype'=>'storage', 'plugincode'=>'programmsbcs', 'eventcode'=>'insert'),
                     array('plugintype'=>'storage', 'plugincode'=>'programmsbcs', 'eventcode'=>'update'),
                     array('plugintype'=>'storage', 'plugincode'=>'programmsbcs', 'eventcode'=>'delete')
        
        );
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
        if ( $this->acl_check_access_paramenrs($acldata) )
        {// право есть заканчиваем обработку
            return true;
        } 
        return false;
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
        if ( $gentype == 'storage' AND $gencode == 'programmsbcs' )
        {//есть событие от этого справочника';
            switch ($eventcode)
            {//обработаем его
                case 'insert': return $this->send_addto_agroup($intvar, $mixedvar['new']);  break;
                case 'update': return $this->send_change_agroup($intvar, $mixedvar['old'], $mixedvar['new']); break;
                case 'delete': return $this->send_from_agroup($intvar, $mixedvar['old']);   break;
            }
        }
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
        return 'block_dof_s_programmsbcs';
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
        $a['create'] = array('roles'=>array('manager'));
        $a['delete'] = array('roles'=>array());
        $a['use']    = array('roles'=>array('manager','methodist'));
        $a['edit:agroupid'] = array('roles'=>array('manager'));
        
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
    
    /** Возвращает список контрактов учеников данной группы, у которых нет подписок на предмето-поток
     * @param int $agroupid - id группы
     * @param int $cstreamid - id потока
     * @return array список контрактов
     */
    public function get_contracts_without_cpassed($agroupid, $cstreamid)
    {
        // находим все подписки данного потока
        if ( $cpassed = $this->dof->storage('cpassed')->get_cstream_students($cstreamid) )
        {// подписки есть
            $str = array();
            // переберем их
            foreach ($cpassed as $cpass)
            {// для каждого запомним id подписки на программу
                if ( $this->is_exists($cpass->programmsbcid) )
                {
                    $str[] = $cpass->programmsbcid;
                }
            }
        }
        // формируем запрос
        if ( ! empty($str) )
        {// подписки на потоки были - исключим из поиска найденные подписки на программы
            $select = 'agroupid = '.$agroupid.' AND id NOT IN ('.implode($str, ',').')';
        }else
        {// не было подисок на поток - значит нужны все подписки на программы 
            $select = 'agroupid = '.$agroupid;
        }
        if ( ! $sbcs = $this->get_records_select($select) )
        {// если подписок найдено не было - значит и контрактов нет
            return array();
        }
        // вернем все найденные контракты
        return $this->dof->storage('contracts')->get_list_by_list($sbcs,'contractid');
        
    }
    
   /** Возвращает список подписок по заданным критериям 
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
        $countselect = $this->get_select_listing($conds);
        if ( $countonly )
        {// посчитаем общее количество записей, которые нужно извлечь
            return $this->count_records_select($countselect);
        }
        //формируем строку запроса
        $select = $this->get_select_listing($conds);
        // возвращаем ту часть массива записей таблицы, которую нужно
        $tblpersons = $this->dof->storage('persons')->prefix().$this->dof->storage('persons')->tablename();
        $tblprogramms = $this->dof->storage('programms')->prefix().$this->dof->storage('programms')->tablename();
        $tblcontracts = $this->dof->storage('contracts')->prefix().$this->dof->storage('contracts')->tablename();
        $tblagroups = $this->dof->storage('agroups')->prefix().$this->dof->storage('agroups')->tablename();
        $tblprogrammsbcs = $this->prefix().$this->tablename();
        if (strlen($select)>0)
        {// сделаем необходимые замены в запросе
            $select = 'WHERE p.'.preg_replace('/ AND /',' AND p.',$select.' ');
            $select = preg_replace('/ OR /',' OR p.',$select);
            $select = str_replace('p. (','(p.',$select);
            $select = str_replace('p.(','(p.',$select);

        }
        $sql = "SELECT p.*, pr.sortname as sortname, pr.id as studentid, pg.name as programm
                FROM {$tblprogrammsbcs} as p
                LEFT JOIN {$tblagroups} as ag ON  p.agroupid=ag.id
                LEFT JOIN {$tblcontracts} as ct ON p.contractid=ct.id
                LEFT JOIN {$tblpersons} as pr ON ct.studentid=pr.id
                LEFT JOIN {$tblprogramms} as pg ON  p.programmid=pg.id
                $select ";
        
        // Добавим сортировку
        $sql .= $this->get_orderby_listing($sort);
        //print $sql;
        return $this->get_records_sql($sql, null, $limitfrom, $limitnum);       
    }
    
    /**
     * Возвращает фрагмент sql-запроса после слова WHERE
     * @param object $inputconds - список полей с условиями запроса в формате "поле_БД->значение" 
     * @return string
     */
    public function get_select_listing($inputconds)
    {
        // создадим массив для фрагментов sql-запроса
        $selects = array();
        //$conds = fullclone($inputconds);
        $conds = (array)$inputconds;
        // теперь создадим все остальные условия
        foreach ( $conds as $name=>$field )
        {
            if ( $field )
            {// если условие не пустое, то для каждого поля получим фрагмент запроса
                $selects[] = $this->query_part_select($name,$field);
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
        $order_by = " ORDER BY ";
        switch ( $sort )
        {
            case 'sortname':
            break;
            case 'programm':
                $order_by .= "pg.name, ";
            break;
            case 'agroup':
                $order_by .= "ag.name, ";
            break;
            case 'agenumprogramm':
                $order_by .= "pg.name, p.agenum, ";
            break;
            default:
                $order_by .= "p.{$sort}, ";
            break;
        }
        return $order_by."pr.sortname";
    }
        
    /** Получить список всех возможных форм обучения
     * 
     * @return array массив вида "форма обучения"=>"название"
     */
    public function get_eduforms_list()
    {
        return array('internal'                  => $this->dof->get_string('eduform:internal', 
                                                     'programmsbcs', null, 'storage'),
                     'correspondence'            => $this->dof->get_string('eduform:correspondence', 
                                                     'programmsbcs', null, 'storage'),
                     'internally-correspondence' => $this->dof->get_string('eduform:internally-correspondence', 
                                                     'programmsbcs', null, 'storage'),
                     'external-studies'          => $this->dof->get_string('eduform:external-studies', 
                                                     'programmsbcs', null, 'storage'));
    }
    
    /** Получить обозначение формы обучения по ее коду
     * 
     * @return mixed string|bool - название формы обучения или пробел, 
     * если такая форма обучения не найдена
     * @param string $code - код формы обучения 
     */
    public function get_eduform_name($code)
    {
        //получим список всех возможных форм обучения
        $eduforms = $this->get_eduforms_list();
        if ( array_key_exists($code,$eduforms) )
        {// если такая форма обучения зарегестрирована - вернем ее название
            return $eduforms[$code];
        }
        // в остальных случаях false
        return '&nbsp;';
    }
    /** Получить список всех возможных типов обучения
     * 
     * @return array массив вида "тип обучения"=>"название"
     */
    public function get_edutypes_list()
    {
        return array('individual'=> $this->dof->get_string('edutype:individual',
                                          'programmsbcs', null, 'storage'),
                     'group'     => $this->dof->get_string('edutype:group', 
                                          'programmsbcs', null, 'storage'));
    }
    
    /** Получить обозначение формы обучения по ее коду
     * 
     * @return mixed string|bool - название формы обучения или пробел, 
     * если такая форма обучения не найдена
     * @param string $code - код формы обучения 
     */
    public function get_edutype_name($code)
    {
        //получим список всех возможных форм обучения
        $edutypes = $this->get_edutypes_list();
        if ( array_key_exists($code,$edutypes) )
        {// если такая форма обучения зарегестрирована - вернем ее название
            return $edutypes[$code];
        }
        // в остальных случаях false
        return '&nbsp;';
    }
    /** Проверяет существование подписка с параметрами
     * @param int $contractid - id контракта
     * @param int $programmid - id программы
     * @param int $agroupid - id группы, по умолчанию - нет
     * @param int $id - id подписки, которую не следует учитывать, по умолчанию - нет
     * @return bool true если подписки найдены и false если таковых нет
     */
    public function is_programmsbc($contractid, $programmid, $agroupid = null, $agestartid = null, $id = null)
    {
        // получим контракт ученика для того чтобы узнать id ученика
        $contract = $this->dof->storage('contracts')->get($contractid);
        if ( ! isset($contract) AND ! isset($contract->studentid) )
        {// нет контракта и студента - проверять нечего
            return false;
        }
        // получим все контракты ученика
        if (!$contracts = $this->dof->storage('contracts')->get_list_by_student($contract->studentid))
        {
        	// У ученика нет контрактов
        	return false;
        }
        $contractids = array();
        $select = '';
        foreach ($contracts as $contract )
        {// собираем все id контрактов
            $contractids[] = $contract->id;
        }
        // склеиваем их в строку
        $contractidsstring = implode(', ', $contractids);
        // составим условие
        $select .= ' contractid IN ('.$contractidsstring.')';
        if ( ! is_null($agroupid) )
        {// id группы указан - дополним условие';
            if ( ($agroupid == 0) )
            {// если id группы не найдено - ученик учится индивидуально
                $select .= ' AND agroupid IS NULL';
            }else
            {// id группы найдено - в группе
                $select .= ' AND agroupid = \''.$agroupid.'\'';
            }
        }
        if ( ! is_null($agestartid) )
        {// id начального периода указан - дополним условие';
            if ( ($agestartid == 0) )
            {// если id стартового периода не найдено
                $select .= ' AND agestartid IS NULL';
            }else
            {// id периода найдено - в группе
                $select .= ' AND agestartid = \''.$agestartid.'\'';
            }
        }
        
        // включим в условие поиска программу и статус
        // ищем только подписки со статусом заявка, подтвержденная, действующая, приостановленная
        $select .= " AND programmid = '".$programmid."' AND 
                             status IN ('application', 'plan', 'active', 'suspend')";
        if ( ! is_null($id) AND ($id <> 0) )
        {// если указано id, которое следует исключить
            // исключаем его
            $select .= ' AND id != '.$id;
        }
        // метода проверяющего существование записи по SQL-запросу нет,
        // придется использовать метод подсчитывающий кол-во записей
        if ( $sbc = $this->get_records_select($select) )
        {// запись найдена - вернем ее id
            return current($sbc)->id;
        }
        return false;
    }
    /** Изменяет параметры подписки
     * @param int $id - id подписки, которая обновляется
     * @param string $edutype - тип обучения
     * @param string $eduform - форма обучения
     * @param int $freeattendance - свободное посещение
     * @param int $agroupid - id группы, по умолчанию - нет
     * @param int $departmentid - id подразделения в таблице departments
     * @param int $agenum - номер  параллели, в которой находится учебная подписка
     * @return bool true если запись успешно обнавлена и false в остальных случаях
     * 
     * @todo оптимизировать эту функцию, переместив однотипные данные в один объект
     */
    public function change_sbc_parametres($id, $edutype, $eduform, $freeattendance, $datestart, 
            $agestartid = null, $agroupid = null, $departmentid = null, $agenum = null)
    {
        // создадим объект для вставки
        $sbc = new object;
        $sbc->edutype = $edutype;
        $sbc->eduform = $eduform;
        $sbc->freeattendance = $freeattendance;
        $sbc->datestart = $datestart;
        if ( ! is_null($agestartid) )
        {
            $sbc->agestartid = $agestartid;
        }
        if ( $agroupid == 0 )
        {// если группа указана равной 0, переопределим ее в значение null
            $agroupid = null;
        }
        if ( is_numeric($departmentid) AND $departmentid )
        {// если изменился id подразделения - обновим его
            $sbc->departmentid = $departmentid;
        }
        if ( is_numeric($agenum) AND $agenum )
        {// если изменился период - обновим ео
            $sbc->agenum = $agenum;
        }
        $sbc->agroupid = $agroupid;
        // обновим запись в БД
        return $this->update($sbc, $id);
    }
    /** Подписывает ученика на программу
     * @param obj $sbc - объект вставки записи в БД
     * @return mixed int id вставленой записи или bool false в остальных случаях
     */
    public function sign($sbc)
    {
        if ( ! isset($sbc->contractid) AND ! isset($sbc->programmid) 
               AND ! isset($sbc->agroupid) AND ! isset($sbc->agestartid) )
        {// нету id контракта, программы и группы - вставлять нельзя
            return false;
        }
        if ( $this->is_programmsbc($sbc->contractid, $sbc->programmid, $sbc->agroupid, $sbc->agestartid) )
        {// подписка с такими параметрами уже существует - вставлять нельзя
            return false;
        }
        // вставляем запись в БД
        return $this->insert($sbc);
    }
    
    /**
     * Посылает событие "changeagroup", 
     * в случае зачисления студента в группу 
     * @param int $id - id подписки на программу 
     * @param stdClass $object - объект, вставляемый в таблицу
     * @return bool - true, если вызван обработчик события из 
     * соответствующего плагина или false
     */
    public function send_addto_agroup($id, $object)
    {
        $object->id = $id;
        if ( isset($object->agroupid) AND $object->agroupid )
        {//зачислили в группу';
            return $this->dof->send_event($this->type(), $this->code(), 'changegroup', $id,
                    array('oldagroup' => null, 'newagroup' => $object->agroupid, 'programmsbc' => $object));
        }
        return false;
    }
    
    /**
     * Посылает событие "changeagroup", 
     * в случае перевода студента из одной группы в другую 
     * @param int $id - id подписки на программу 
     * @param stdClass $oldobject - объект, который был в таблице
     * @param stdClass $newobject - объект, вставляемый в таблицу
     * @return bool - true, если вызван обработчик события из 
     * соответствующего плагина или false
     */
    public function send_change_agroup($id, $oldobject, $newobject)
    {
        if ( isset($oldobject->agroupid) AND $oldobject->agroupid )
        {//старая группа есть';
            $oldagroupid = $oldobject->agroupid;
        }else
        {//раньше студент не был в группе';
            $oldagroupid = null;
        }
        if ( isset($newobject->agroupid) AND $newobject->agroupid )
        {//новая группа есть';
            $newagroupid = $newobject->agroupid;
        }else
        {//новой группы нет';
            $newagroupid = null;
        }
        if ( is_null($oldagroupid) AND is_null($newagroupid) )
        {//студент как был вне группы, так и остался';
            return true;
        }
        if ( $oldagroupid == $newagroupid )
        {//студент остался в прежней группе';
            return true;
        }
        //группа изменилась - генерим событие';
        return $this->dof->send_event($this->type(), $this->code(), 'changegroup', $id,
                    array('oldagroup' => $oldagroupid, 'newagroup' => $newagroupid, 'programmsbc' => $newobject));
    }
    
    /** Посылает событие при исключении ученика из группы 
     * 
     * @param int $id - id удаленной (на момент обработки события) записи из таблицы programmsbcs 
     * @param stdClass $object - объект, удаленный из таблицы programmsbcs
     * @return bool - true если событие послано успешно, или false в случае ошибки
     */
    public function send_from_agroup($id, $object)
    {
        if ( ! $object->agroupid )
        {//студент вне группы - событие посылать не надо';
            return false;
        }
        //посылаем событие';
        return $this->dof->send_event($this->type(),$this->code(),'changegroup',$id,
                array('oldagroup' => $object->agroupid, 'newagroup' => null, 'programmsbc' => $object) );
    }
    
    /** Получить id ученика в таблице persons на которого зарегестрирована эта подписка
     * 
     * @return bool|int
     * @param int $programmsbcid - id подписки на программу в таблице programmsbcs
     */
    public function get_studentid_by_programmsbc($programmsbcid)
    {
        // получаем подписку
        if ( ! $programmsbc = $this->get($programmsbcid) )
        {// не найдена запись - это ошибка
            //@todo сообщить об этом через исключение, когда будет возможность
            return false;
        }
        // получаем контракт
        if ( ! $contract = $this->dof->storage('contracts')->get($programmsbc->contractid) )
        {// не нашли контракт
            //@todo сообщить об этом через исключение, когда будет возможность
            return false;
        }
        return $contract->studentid;
    }
    /** Метод будет удален: используйте get_programmsbcs_by_contractid($id)
     */
    public function get_programmsbcs_on_contractid($id)
    {
    	return $this->get_programmsbcs_by_contractid($id);
    }
    	/** Получает информацию о подписках на программу по id контракта
     * @param int $id - id контракта
     * @return array - массив с информацией о подписках на программу
     */
    public function get_programmsbcs_by_contractid($id)
    {
        if ( ! is_int_string($id) )
        {//входные данные неверного формата 
            return false;
        }
        $rez = array();
        //найдем подписки на программу по id контракта
        if ( ! $sbcs = $this->get_records(array('contractid'=>$id,'status'=>array('application','plan','active','suspend'))) )
        {// ничего не нашли
            return $rez;
        }
        foreach ( $sbcs as $sbc )
        {// для каждой из подписок соберем информацию
            $sbcinfo = new object;
            if ( ! $sbcinfo->programmname = $this->dof->storage('programms')->
                                      get_field($sbc->programmid,'name') )
            {
                $sbcinfo->programmname = '';
            }
            $sbcinfo->agenum = $sbc->agenum;
            $sbcinfo->status = $sbc->status;
            $sbcinfo->programmid = $sbc->programmid;
            $rez[$sbc->id] = $sbcinfo;
        }
        return $rez;
        
    }
    
     /** Получает id подписки на программу по id контракта
     * @param int $id - id контракта
     * @return array - массив id подписок
     */
    public function get_programmsbcs_by_contractid_ids($id)
    {
        if ( ! is_int_string($id) )
        {//входные данные неверного формата 
            return false;
        }
        $rez = array();
        //найдем подписки на программу по id контракта
        if ( ! $sbcs = $this->get_records(array('contractid'=>$id)) )
        {// ничего не нашли
            return $rez;
        }
        foreach ( $sbcs as $sbc )
        {// для каждой из подписок соберем информацию
            $rez[] = $sbc->id;
        }
        return $rez;
    }
    
     /** Получает информацию о подписках на программу по id персоны ученика
     * @param int $id - id персоны
     * @return array - массив с информацией о подписках на программу
     */
    public function get_programmsbcs_by_personid($id)
    {
        if ( ! is_int_string($id) )
        {//входные данные неверного формата 
            return false;
        }
        // Получаем список контрактов
        if ( ! $contracts = $this->dof->storage('contracts')->get_list_by_student($id) )
        {
            return array();
        }
        // Собираем подписки по всем контрактам
		$sbcs = array();
        foreach ($contracts as $contract)
        {
        	// Сливаем массивы с наложением (наложения быть не может - при одинаковых id объекты одинаковы)
        	$sbcs = $this->get_programmsbcs_by_contractid($contract->id) +$sbcs;
        }
        return $sbcs;
        
    }
    
     /** Проверяем, подписана ли персона на указанную программу
     * @param int $id - id персоны
     * @return array - массив с информацией о подписках на программу
     */
    public function is_sbc_to_programm($personid,$programmid)
    {
    	// Получаем все подписки пользователя (знаю что криво, но писать sql-запрос на проверку без выборки - некогда)
        $sbcs = $this->get_programmsbcs_by_personid($personid);
        foreach ($sbcs as $sbc)
        {
            //print_object($sbc);
        	if ($sbc->programmid == $programmid)
        	{
        		// Нашли
        		return true;
        	}
        }
        // Ничего не нашли
        return false;
        
    }
}
?>