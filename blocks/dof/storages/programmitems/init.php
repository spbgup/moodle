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

/** Справочник учебных программ
 * 
 */
class dof_storage_programmitems extends dof_storage  implements dof_storage_config_interface
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
        $table = new xmldb_table($this->tablename());
        if ($oldversion < 2012071713) 
        {//добавляем поля
            $field = new xmldb_field('metaprogrammitemid',XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'altgradeitem');
            if ( !$dbman->field_exists($table, $field) )
            {
                $dbman->add_field($table, $field);
            }
            
            $field = new xmldb_field('metasyncon',XMLDB_TYPE_CHAR, '10', null, XMLDB_NOTNULL, null, '0', 'metaprogrammitemid');
            if ( !$dbman->field_exists($table, $field) )
            {
                $dbman->add_field($table, $field);
            }
        }
        if ($oldversion < 2013040905)
        {// добавим поле billingrules
            $field = new xmldb_field('billingtext', XMLDB_TYPE_TEXT, 'big', null, false, null, null, 'metaprogrammitemid');
            if ( !$dbman->field_exists($table, $field) )
            {// поле еще не установлено
                $dbman->add_field($table, $field);
            }
            $field = new xmldb_field('billingrules', XMLDB_TYPE_TEXT, 'big', null, false, null, null, 'billingtext');
            if ( !$dbman->field_exists($table, $field) )
            {// поле еще не установлено
                $dbman->add_field($table, $field);
            }
        }
        if ($oldversion < 2013062700)
        {// добавим поле salfactor
            $field = new xmldb_field('salfactor',XMLDB_TYPE_FLOAT, '6', XMLDB_UNSIGNED, 
                    true, null, '1', 'billingrules');
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
                    XMLDB_NOTNULL, null, '0', 'billingrules');
            $dbman->change_field_default($table, $field);
            if (!$dbman->index_exists($table, $index))
            {// если индекс еще не установлен
                $dbman->add_index($table, $index);
            }
            while ( $list = $this->get_records_select('salfactor = 1',null,'','*',0,100) )
            {
                foreach ($list as $item)
                {// ищем уроки где appointmentid не совпадает с teacherid
                    $obj = new stdClass;
                    $obj->salfactor = 0;
                    $this->update($obj,$item->id);
                }               
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
        return 'programmitems';
    }
    /** Возвращает список плагинов, 
     * без которых этот плагин работать не может
     * @return array
     * @access public
     */
    public function need_plugins()
    {
		return array('storage'=>array('departments' => 2009040800,
		                              'programms'   => 2009040800,
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
        return array('storage'=>array('acl'=>2011040504,
                                      'config'=> 2011080900));
    }
    /** Список обрабатываемых плагином событий 
     * @return array - array(array('plugintype'=>..,'plugincode'=>..,'eventcode'=>..),...)
     * @access public
     */
    public function list_catch_events()
    {
        return array(array('plugintype'=>'storage', 'plugincode'=>'programmitems', 'eventcode'=>'insert'),
                     array('plugintype'=>'storage', 'plugincode'=>'programmitems', 'eventcode'=>'update'),
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
        switch($eventcode)
        {
            case 'update': 
                if ( isset($mixedvar['new']->metaprogrammitemid) )
                {
                    if ( $mixedvar['new']->metaprogrammitemid == '0'  )
                    {// это метадисциплина - синхронизируем ее наследников
                        if ( ! $inheritors = $this->dof->storage('programmitems')->get_records
                               (array('metasyncon'=>'1','metaprogrammitemid'=>$mixedvar['new']->id)) )
                        {// неследников нет - значит все хорошо
                            return true;
                        }
                        foreach ($inheritors as $id => $obj)
                        {
                            $this->sync_pitem_with_metapitems($obj);
                        }
                    }elseif ( $mixedvar['new']->metasyncon )
                    {// это дисциплина привязанная к метедисциплине - синхронизируем ее
                        $this->sync_pitem_with_metapitems($mixedvar['new']);
                    }
                }
        }
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
        switch ( $code )
        {
            // пересинхронизация всех потоков дисциплины
            case 'change_mcourse_in_programmitem': 
                if ( !$mixedvar->mdlcourse )
                {// нету курса, работать не с чем
                    return false;
                }
                // останавливаем все потоки этой дисциплины
                $this->dof->storage('cstreams')->todo_itemid_active_to_suspend($intvar,$mixedvar->personid);
                // меняем курс moodle
                $this->change_mcourse_in_programmitem($intvar,$mixedvar->mdlcourse,$mixedvar->personid);
                // возобновляем все потоки этой дисциплины 
                $this->dof->storage('cstreams')->todo_itemid_suspend_to_active($intvar,$mixedvar->personid);
            break;
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
        return 'block_dof_s_programmitems';
    }
    
    /** Переопределение функции вставки записи в таблицу - для произведения дополнительных
     * операций с данными до или после вставки
     * 
     * @param object $dataobject - объект с данными для вставки
     * @param bool $quiet[optional]- не генерировать событий
     * @return mixed bool false если операция не удалась или id вставленной записи
     */
    public function insert($dataobject, $quiet=false)
    {
        if ( ! $id = parent::insert($dataobject, $quiet) )
        {// вставка объекта не удалась
            return false;
        }
        // получаем только что вставленный в базу объект
        $oldobj = $this->get($id);
        
        if ( $oldobj->code )
        {// если код был уже указан - значит все хорошо
            return $id;
        }
        // Если код записи не указан - то заменим его на id
        $newobj       = new Object();
        $newobj->id   = $id;
        $newobj->code = 'id'.$id;
        
        // добавляем код к созданной записи и возвращаем результат
        // @todo проверить результат вставки и записать ошибку в лог если это не удалось
        $this->update($newobj);
        return $id;
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
        
        $a['view']           = array('roles'=>array('manager', 'methodist'));
        $a['edit']           = array('roles'=>array('manager', 'methodist'));
        $a['create']         = array('roles'=>array('manager', 'methodist'));
        $a['delete']         = array('roles'=>array());
        $a['use']            = array('roles'=>array('manager', 'methodist'));
        $a['edit:mdlcourse'] = array('roles'=>array(''));
        $a['view/meta']      = array('roles'=>array('manager'));
        $a['edit/meta']      = array('roles'=>array('manager'));
        $a['create/meta']    = array('roles'=>array('manager'));
        $a['delete/meta']    = array('roles'=>array());
        $a['use/meta']       = array('roles'=>array('manager'));
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

        $obj = new object();
        $obj->type = 'text';
        $obj->code = 'metapitemslimit';
        $obj->value = '-1';
        $config[$obj->code] = $obj;
        return $config;
    }
    
    /************************************************/
    /****** Функции для обработки заданий todo ******/
    /************************************************/
    
    
    /* Запускает все приостановленные cpassed
     *  @param integer $itemid - id дисциплины
     */
    public function change_mcourse_in_programmitem($itemid,$mdlcourse,$personid)
    {
        // времени понадобится много
        dof_hugeprocess();
        
        $cstreamsids = array();
        $num = 0;
        // сообщаем о том, что начинаем todo
        $this->dof->mtrace(2, '(storage/programmitems:todo) Change mdlcourse for programmitemid='.$itemid);
        $userid = $this->dof->storage('persons')->get_field($personid,'mdluser');
        if ( $this->is_access('edit:mdlcourse', $itemid, $userid) )
        {// если есть право - сменим курс мудла
            $pitem = new object();
            $pitem->mdlcourse = $mdlcourse;
            $pitem->id = $itemid; 
            $this->update($pitem);
        }else
        {// таких прав нет
            $this->dof->mtrace(2, 'You are not allowed to change course.');
        }
        
        $this->dof->mtrace(2, '(storage/programmitems:todo) DONE.');
        
        return true;
    }
    
    // **********************************************
    //              Собственные методы
    // **********************************************
    /** Получить дисциплину по ее коду
     * 
     * @return object - объект, с данными о дисциплине или false
     * @param string $code - код учебной дисциплины
     */
    public function get_pitem_by_code($code)
    {
        return $this->get_record(array('code'=>$code));
    }
    
    /** Список дисциплин, относящихся к учебной программе и учебному периоду
     * 
     * @return array - массив записей из таблицы programmitems, удовлетроряющих указанным условиям
     * @param int $programid - id учебной программы в таблице programs
     * @param int $agenum[optional] - порядковый номер периода или false, 
     * обозначающий получение дисциплин отсортированных в порядке 
     * возрастания периодов и по названию внутри них
     */
    public function get_pitems_list($programmid, $agenum=false, $status = null)
    {
        if ( ! $this->dof->storage('programms')->is_exists($programmid) )
        {// не передан необходимый параметр, или такой программы нет в базе
            return false;
        }
        $select = '';
        if ( is_array($status) )
        {
            $select = " AND status IN ('".implode("','",$status)."')";
        }elseif ( ! is_null($status) )
        {
            $select = ' AND status != \''.$status.'\'';
        }
        if ( ! is_int_string($agenum) )
        {
            return $this->get_records_select('programmid = '.$programmid.$select, null,'agenum ASC, name ASC');
        }else
        {
            return $this->get_records_select('programmid = '.$programmid.' AND agenum = '.$agenum.$select,null, 'agenum ASC, name ASC');
        }
    }
    
    /** возвращает максимальное количество периодов для данной учебной программы
     * @todo выяснить, как правильно поступать с необязательными дисциплинами
     * @return bool|int 
     *     false - если не нашлось не одной записи
     *     0 - если нашлись только записи с agenum=0, то есть необязательные
     *     в остальных случаях возвращается целое число - максимальное кол-во дисциплин в данном периоде
     * @param int $programmid - id учебной программы в таблице programms
     */
    public function get_maxagenum($programmid)
    {
        $result = $this->get_record(array('programmid'=>$programmid), $fields = 'max(agenum) as maxagenum');
        if ( $result === false )
        {// не нашлось ни одной записи с такими параметрами
            return false;
        }
        // возвращает максимальное количество периодов для данной учебной программы
        return (int)$result->maxagenum;
    }
    
    /** Получить список всех возможных уровней оценки
     * 
     * @return array
     */
    public function get_gradelevels()
    {
        return array('notused'    => $this->dof->get_string('notused', 'programmitems', null, 'storage'),
                     'discipline' => $this->dof->get_string('discipline', 'programmitems', null, 'storage'),
                     'coursework' => $this->dof->get_string('coursework', 'programmitems', null, 'storage'),
                     'practice'   => $this->dof->get_string('practice', 'programmitems', null, 'storage'),
                     'finalexam'  => $this->dof->get_string('finalexam', 'programmitems', null, 'storage'),
                     'diplom'     => $this->dof->get_string('diplom', 'programmitems', null, 'storage'));
    }
    
    /** Получить название уровня оценки по его обозначению
     * 
     * @return string
     * @param string $value обозначение уровня оценки в БД
     */
    public function get_gradelevel_name($value)
    {
        if ($value)
        {
            $levels = $this->get_gradelevels();
            if ( isset($levels[$value]) )
            {// если такой уровень дисциплины зарегестрирован
                return $levels[$value];
            }
            // если уровень дисциплины не опознан - сообщим об этом
            return $this->dof->get_string('unknown_level', 'programmitems');
        }
        return '';
    }
    
    /** Получить все возможные типы дисциплины
     * 
     * @return array
     */
    public function get_types()
    {
        return array('required'    => $this->dof->get_string('type_required',    'programmitems', null, 'storage'),
                     'recommended' => $this->dof->get_string('type_recommended', 'programmitems', null, 'storage'),
                     'free'        => $this->dof->get_string('type_free',        'programmitems', null, 'storage'));
    }
    
    /** Получить название типа по его идентификатору в базе
     * 
     * @return string
     * @param string $value
     */
    public function get_type_name($value)
    {
        if ($value)
        {
            $types = $this->get_types();
            if ( isset($types[$value]) )
            {// если такой уровень дисциплины зарегестрирован
                return $types[$value];
            }
            // если уровень дисциплины не опознан - сообщим об этом
            return $this->dof->get_string('unknown_type', 'programmitems');
        }
        return '';
    }
    
    /** Определить, является ли переданная оценка положительной
     * (достаточной, для продолжения обучения)
     * @return bool
     * @param int $programmitemid - id предмета, по которому выставляется итоговая оценка
     * @param string $grade - выставляемая оценка
     * @todo разбить функцию на более мелкие фрагменты
     */
    public function is_positive_grade($programmitemid, $grade)
    {
        if ( ! $pitem = $this->get($programmitemid) )
        {// не найдено такой записи
            return false;
        }
        
        if ( ! trim($pitem->scale) )
        {// если шкала не задана - мы вообще не можем выставлять оценки
            return false;
        }
        
        if ( ! $this->is_grade_valid($pitem->id, $grade) )
        {// переданная оценка недопустима
            return false;
        }
        
        if ( ! trim($pitem->mingrade) )
        {// минимальная оценка не задана - считаем любую оценку положительной
            return true;
        }
        
        if ( ! $this->analyze_grade_scale($pitem, $grade) )
        {// путем анализа шкалы, мы установили, что оценка положительная
            return false;
        }
        // все проверки прошли успешно
        return true;
    }
    
    /** Определить, является ли переданная оценка допустимой для шкалы текущего предмета
     * 
     * @return bool
     * @param object $pitem - объект из таблицы programmitems
     * @param string $grade - выставляемая оценка
     * 
     * @todo доработать вариант со шкалой, определенной ва обратном порядке 
     * (например, где 1-максимум, а 10-минимум)
     */
    private function analyze_grade_scale($pitem, $grade)
    {
        if ( ! is_object($pitem) )
        {// неверный формат данных
            return false;
        }
        // преобразеум шкалу в индексный массив
        $scale = array_values($this->dof->storage('plans')->get_grades_scale_str($pitem->scale));
        $mingrade = $pitem->mingrade;        
        $key_mingrade = array_keys($scale,$mingrade);
        $key_grade = array_keys($scale,$grade); 
        if ( $key_grade[0] >= $key_mingrade[0] )
        {
            return true; 
        }
        return false;
    }
    
    /** Определяет, допустима ли переданная оценка для данной дисциплины
     * 
     * @return bool
     * @param int $id - id предмета в таблице programmitems
     * @param string $grade - выставляемая оценка
     * @param[optional] string $scale - шкала оценок, если она указывается вручную 
     */
    public function is_grade_valid($id, $grade, $scale=null)
    {
        if ( is_null($scale) )
        {// шкала оценок не указана - берем ее из базы
            // получаем предмет
            if ( ! $pitem = $this->get($id) )
            {// нет такого предмета - нельзя выставлять оценку';
                return false;
            }
            // смотрим на его шкалу оценок
            if ( ! trim($pitem->scale) )
            {// нет шкалы оценок - не можем выставлять оценки'; 
                return false;
            }
            // шкала оценок есть - запомним ее
            $scale = trim($pitem->scale);
        }
        if ( is_null($grade) OR (! trim($grade) AND trim($grade) != '0') )
        {// нет оценки - значит мы не можем ее выставить';
            return false;
        }
        // преобразуем шкула в массив
        $scale = $this->dof->storage('plans')->get_grades_scale_str($scale);
        if ( in_array($grade, $scale) )
        {
            return true;
        }
        return false;
    }
    
    /** Переводит дисциплину в статус "удален"
     * @param int $id - id дисциплины 
     * @return bool true - если поток удачно завершен и 
     * false в остальных случаях
     */
    public function set_status_deleted($id)
    {
    	if ( ! is_int_string($id) )
        {// входные данные неверного формата
            return false;
        }
        if ( ! $obj = $this->get($id) )
		{// объект не найден
			return false;
		} 
		if ( $obj->status == "deleted")
		{// дисциплина уже удалена
			return true;
		}
		// меняем статус и возвращаем результат
	    return $this->dof->workflow('programmitems')->change($id,'deleted');
    }

    /**
     * Возвращает массив предметов для селекта
     * @param int $programmid - id программы, в которой ищем предметы
     * @param int $agenum - параллель, для которой ищем предметы
     * @return 
     */
    public function get_pitems_select_list($programmid, $agenum)
    {
        // найдем предметы указанной программы
        if ( ! $this->dof->storage('programms')->is_exists($programmid) )
        {// не передан необходимый параметр, или такой программы нет в базе
            return array();
        }
        $select = "programmid = '".$programmid."' AND status = 'active' AND ";
        if ( is_int_string($agenum) )
        {
            $select .= "(agenum = '".$agenum."' OR agenum = '0')";
        }else
        {
            $select .= "agenum = '0'";
        }
        if ( ! $items = $this->get_records_select($select, null,'agenum ASC, name ASC') )
        {
            return array();
        }
        $list = array();
        foreach($items as $key => $record)
        {
            $list[$key] = $record->name.'['.$record->code.']';
        }
        return $list;
    }

/*
 * ДОБАВЛЕННЫЕ МЕТОДЫ ДЛЯ РАБОТЫ С МЕТАДИСЦИПЛИНАМИ
 *
 *
 *
 *
 */

    /** 
     * Список метадисциплин, относящихся к парралели, подразделению
     * @return array - массив записей из таблицы programmitems, удовлетроряющих указанным условиям
     */
    public function get_metapitems_list($depid=null,$agenum=false)
    {
        $select = 'metaprogrammitemid = 0 AND ';
        if ($depid !== null)
        {
            $select .= 'departmentid = '.$depid.' AND ';
        }
        
        if ( is_int_string($agenum) )
        {
            $select .= "(agenum = '".$agenum."' OR agenum = '0')";
        }
        else
        {
            $select .= "agenum = '0'";
        }
        
        return $this->get_records_select($select, null, 'agenum ASC, name ASC');
    }

    /** 
     * Список метадисциплин, относящихся к подразделению со статусом "активен"
     * @param int $depid[optional] - id подразделения
     * @return int - количество метадисциплин в заданном подразделении(либо во всех подразделениях)
     */
    public function get_metapitems_count($depid=0)
    {
        $cond = array('metaprogrammitemid'=>0, 'status'=>'active');
        if ($depid != 0)
        {
            $cond['departmentid'] = $depid;
        }

        return $this->dof->storage($this->code())->count_list($cond);
    }

    /** 
     * Лимит метадисциплин, получаемый из настроек
     * @return int-лимит метадисциплин
     */
    public function get_limit_metapitems()
    {
        $depid = optional_param('departmentid', 0, PARAM_INT);
        
        $num = $this->dof->storage('config')->get_config_value('metapitemslimit', 'storage', $this->code(), $depid);
        
        if ( $num == '-1' )
        {// бесконечно много
            return '&#8734;';
        }
        if ( $num )
        {// определенное кол-во
            return $num;
        }
        return '';
    }

    /**Проверка, не достигнут ли лимит метадисциплин
     *
     * @return bool - true-лимит не достигнут
     * @param int $depid[optional] - id подразделения
     */
    public function check_limit_metapitems($depid=null)
    {
        if (strcmp($this->get_limit_metapitems(),'&#8734;') == 0)
        {
            return true;
        }
        if ($this->get_limit_metapitems() > $this->get_metapitems_count($depid))
        {
            return true;
        }
        return false;

    }
    
    public function sync_pitem_with_metapitems($pitem)
    {
        //получим данные метадисциплины
        $programmitemmeta = $this->dof->storage('programmitems')->get($pitem->metaprogrammitemid);
    
        //Уберем поля, которые не будем синхронизировать
        unset($programmitemmeta->status);
        unset($programmitemmeta->code);
        unset($programmitemmeta->name);
        unset($programmitemmeta->agenum);
        unset($programmitemmeta->programmid);
        unset($programmitemmeta->metaprogrammitemid);
        unset($programmitemmeta->metasyncon);
        unset($programmitemmeta->agenum);
        unset($programmitemmeta->notice);
        unset($programmitemmeta->about);
        
        return $this->dof->storage('programmitems')->update($programmitemmeta,$pitem->id,true);
    }
    
}

?>
