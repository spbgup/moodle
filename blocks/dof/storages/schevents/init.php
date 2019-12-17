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
class dof_storage_schevents extends dof_storage implements dof_storage_config_interface
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
        // после установки плагина устанавливаем права
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
        $result = true;
        if ( $result AND $oldversion < 2012041200 )
        {
            dof_hugeprocess();
            $num = 0;
            while ( $list = $this->get_records_select('replaceid IS NOT NULL',null,'','*',$num,100) )
            {
                $num += 100;
                foreach ($list as $schevent)
                {// ищем уроки где appointmentid не совпадает с teacherid
                    $teacher = $this->dof->storage('appointments')->get_person_by_appointment($schevent->appointmentid);
                    if ( $teacher->id != $schevent->teacherid )
                    {// записываем
                        $pitemid = $this->dof->storage('cstreams')->get($schevent->cstreamid)->programmitemid;                        
                        // находив список табельных номеров для персоны
                        $apoints = $this->dof->storage('appointments')->get_appointment_by_persons($schevent->teacherid);
                        if ( ! is_array($apoints) )
                        {// нет табельных номеров для этого предмета
                            continue;
                        }
                        foreach ($apoints as $appoint)
                        {// сравниваем в таблице teacher progritemid и appoitmentid
                            if ( $this->dof->storage('teachers')->get_records(array('appointmentid'=>$appoint->id, 'programmitemid'=>$pitemid)) )
                            {// нашли - заносим и переходим к следующему schevent
                                $obj = new object();
                                $obj->appointmentid = $appoint->id;
                                $this->update($obj,$schevent->id);
                                break;
                            }else 
                            {// если ничего не нашли, то берем просто последнее значение appoint 
                                $obj = new object();
                                $obj->appointmentid = $appoint->id;
                                $this->update($obj,$schevent->id);
                            }
                        }
                    }
                }               
            }
        }
        
        if ($oldversion < 2013062700)
        {// добавим поле ahours
            $field = new xmldb_field('ahours', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
                    null, null, null, 'appointmentid');
            
            if ( !$dbman->field_exists($table, $field) )
            {// поле еще не установлено
                $dbman->add_field($table, $field);
            }
            // добавляем индекс к полю
            $index = new xmldb_index('iahours', XMLDB_INDEX_NOTUNIQUE,
                    array('ahours'));
            if (!$dbman->index_exists($table, $index))
            {// если индекс еще не установлен
                $dbman->add_index($table, $index);
            }
            
            // добавим поле salfactor
            $field = new xmldb_field('salfactor', XMLDB_TYPE_FLOAT, '6', XMLDB_UNSIGNED,
                    true, null, '1', 'ahours');
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
            
            // добавим поле salfactorparts
            $field = new xmldb_field('salfactorparts', XMLDB_TYPE_TEXT, 'big', null,
                    false, null, null, 'salfactor');
            
            if ( !$dbman->field_exists($table, $field) )
            {// поле еще не установлено
                $dbman->add_field($table, $field);
            }
         
            // добавим поле rhours
            $field = new xmldb_field('rhours', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED,
                    null, null, null, 'salfactorparts');
            
            if ( !$dbman->field_exists($table, $field) )
            {// поле еще не установлено
                $dbman->add_field($table, $field);
            }
            // добавляем индекс к полю
            $index = new xmldb_index('irhours', XMLDB_INDEX_NOTUNIQUE,
                    array('rhours'));
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
                    XMLDB_NOTNULL, null, '0', 'ahours');
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
        if ($oldversion < 2013083000)
        {// добавим поле salfactor
            dof_hugeprocess();
            $index = new xmldb_index('irhours', XMLDB_INDEX_NOTUNIQUE,
                    array('rhours'));
            if ($dbman->index_exists($table, $index))
            {// если индекс еще не установлен
                $dbman->drop_index($table, $index);
            }
            $field = new xmldb_field('rhours', XMLDB_TYPE_FLOAT, '6, 2', null, 
                    null, null, null, 'salfactorparts');
            $dbman->change_field_type($table, $field);
                        if (!$dbman->index_exists($table, $index))
            {// если индекс еще не установлен
                $dbman->add_index($table, $index);
            }
            if (!$dbman->index_exists($table, $index))
            {// если индекс еще не установлен
                $dbman->add_index($table, $index);
            }
            
        }
        
        return $result && $this->dof->storage('acl')->save_roles($this->type(),$this->code(),$this->acldefault());
     }
    /** Возвращает версию установленного плагина
     * @return string
     * @access public
     */
    public function version()
    {
        // Версия плагина (используется при определении обновления)
		return 2013100201;
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
        return 'schevents';
    }
    /** Возвращает список плагинов, 
     * без которых этот плагин работать не может
     * @return array
     * @access public
     */
    public function need_plugins()
    {
		return array('storage'=>array('cstreams' => 2009060800,
		                              'plans'    => 2009060900,
		                              'persons'  => 2009060400,
		                              'acl'      => 2011041800));
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
        {// при установке плагина требуем только таблицы прав
            return array('storage'=>array('acl'=>2011040504));
        }
        if ( $oldversion AND $oldversion < 2010122702 )
        {// при обновлении с версии раньше чем 2010122702 требуем установки
            // всех плагинов в которые производится запись
            return array('storage'=>array('acl'=>2011040504,
                                      'cstreams'=> 2009060800,
                                      'appointments' => 2011011300,
                                      'teachers' => 2011011300));
        }
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
        switch ( $do )
        {// определяем дополнительные параметры в зависимости от запрашиваемого права
            // право на создание события в своем журнале             
            case 'create/in_own_journal':
                //$objid = $cstreamid
                if ( ! $this->dof->storage('cstreams')->is_exists(array('id'=>$objid,'teacherid'=>$personid)) ) 
                {// персона не учитель потока    
                    return false;
                }
            break;
        }
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
        
        switch ( $code )
        {
            case 'delete_broken_events': $this->delete_broken_events($intvar); break;
            case 'update_salfactors': $this->update_salfactors($intvar); break;
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
        return 'block_dof_s_schevents';
    }
    
    // **********************************************
    //       Методы для работы с полномочиями
    // **********************************************    
    
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
     *  a[] = array( 'code'  => 'код полномочия',
     * 				 'roles' => array('student' ,'...');
     */
    public function acldefault()
    {
        $a = array();
        
        $a['view']        = array('roles'=>array('manager','teacher'));
        $a['edit']        = array('roles'=>array('manager'));
        $a['edit:ahours'] = array('roles'=>array('manager'));
        $a['create']      = array('roles'=>array('manager'));
        // создавать уроки в своем журнале
        $a['create/in_own_journal']   = array('roles'=>array('teacher'));
        $a['delete']      = array('roles'=>array());
        $a['use']         = array('roles'=>array('manager','teacher'));
        // просмотр мнимых уроков
        $a['view:implied'] = array('roles'=>array('manager'));
        return $a;
    }   
    
    /** Функция получения настроек для плагина
     *  
     */
    public function config_default($code=null)
    {
        // формула расчета зарплатного коэффициента
        $obj = new object();
        $obj->type = 'textarea';
        $obj->code = 'salfactors_calculation_formula';
        $obj->value = '=ahours*schevents_completed*(1 + absence_substsalfactor*(programmitem_salfactor+programmsbcs_salfactor+config_salfactor_countstudents)+cstreams_substsalfactor)';
        $config[$obj->code] = $obj;
        // поправочный зарплатный коэффициент для подразделений
        $obj = new object();
        $obj->type = 'text';
        $obj->code = 'salfactor_department';
        $obj->value = '0';
        $config[$obj->code] = $obj;
        // ограничение времени для отмены урока
        $obj  = new object();
        $obj->type = 'checkbox';
        $obj->code = 'time_limit';
        $obj->value = '0';
        $config[$obj->code] = $obj;
        return $config;
    }      
    
    // **********************************************
    //              Собственные методы
    // **********************************************
    
    /** Сформировать замену для события событием. 
     * 
     * @param int $eventid - id события, для которого формируется замена
     * @param int $date - дата, на которую переносится событие
     * @param int $appointmentid - id табельного номера учителя, на которого переносится событие
     * @return bool true в случае успеха 
     */
    public function replace_events($eventid, $date=0, $appointmentid=null )
    {
        if ( ( ! $obj = $this->get($eventid)) )
        {// объект не найден - ошибка
            return false;
        }
        if ( $obj->status == 'canceled' OR $obj->status == 'completed' )
        {// нельзя переносить урок в конечных статусах
            return false;
        }
        // проверка на ДАТУ
        if ( $date )
        {// мепняем время тоже
            $cstreamid = $obj->cstreamid;
            $ageid = $this->dof->storage('cstreams')->get_field($cstreamid, 'ageid');
            $age = $this->dof->storage('ages')->get($ageid);
            if ( ($date < $age->begindate OR $date > $age->enddate) AND ! $this->dof->is_access('datamanage') )
            {// даты начала и окончания события не должны вылезать за границы периода
                return false;
            }
            // проверим по времени
            // раз мы тут - значит мы или учитель или манагер или датаманагер
            // ЭТА проверка только для учителя
            if ( ! $this->dof->is_access('datamanage') AND ! $this->dof->is_access('manage') )
            {// отсекаем манагера и датаманагера
                if ( $date < time() )
                {// переносить можно только на еще не наступившее время
                   return false;
                }
                // @todo если границы бутут определятся в конфиге сделаем потом через него
                // @todo сделать проверку, если у ученика или учителя уже есть на это время уроки
            }
        }    
        
        $rez = true;
        // устанавливаем замену события
        if ( $obj->status == 'replaced' )
		{// у события есть замена - найдем ее
		    if ( $replace = $this->get_records(array('replaceid'=>$obj->id,'status'=>
		                    array('plan','completed','postponed','replaced'))) )
		    {// замена есть
		        $replace = current($replace);
		        if ( $replace->status == 'completed' )
		        {// замена уже проведена
		            return false;
		        }
		        if ( $replace->status == 'plan' )
		        {// замена только запланирована - отменяем ее
		            $rez = $rez & $this->dof->workflow('schevents')->change($replace->id, 'canceled');
		        }
		        // остальные статусы нас не интересуют
		    }		
		}
        if ( $obj->status == 'plan' OR $obj->status == 'postponed' )
		{// событие запланировано или отложено - меняем статус на замененный
		    $rez = $rez & $this->dof->workflow('schevents')->change($eventid, 'replaced');
		}
		if ( ! $rez )
		{// что-то не так - создавать замену нельзя
		    return false;
		}
		// создаем замену 
        $newreplace = $obj;
        // статус и id установятся автоматически
        $newreplace->replaceid = $eventid;
        // замена времени
        if ( $date )
        {
            $newreplace->date = $date;
        }else 
        {// оставляем дату предыдущего
            if ( isset($replace) AND $replace )
            {// замены уже были, берем дату из замененного
                $newreplace->date = $replace->date;
            }else 
            {// меняем в самый первый раз
                $newreplace->date = $obj->date;
            }        
        }
        // замена учителя
        if ( ! empty($appointmentid) )
        {
            $newreplace->appointmentid = $appointmentid;
            if ( $person = $this->dof->storage('appointments')->get_person_by_appointment($appointmentid) )
            {// если есть есть персона - запишем ее в учителя
                $newreplace->teacherid = $person->id;
            }
        }    
        if ( isset($replace) AND $replace)
        {// установим КТ из замены если такая существует
            $newreplace->planid = $replace->planid;
        }
        unset($newreplace->status);
        unset($newreplace->id);
        return $this->insert($newreplace);
    }
    /** Удалить все события дня 
     * 
     * @param int $date - день, в котором надо удалить все события, если не указано, то текущий день 
     * @return bool true если все записи удалились и false в остальных случаях
     */
    public function delete_events($date=null)
    {
        if ( is_null($date) )
        {
            $date = time();
        }
        // формируем дату начала дня
        $dateday = getdate($date);
        $time = $this->dof->storage('persons')->get_make_timestamp(0,0,0,$dateday['mon'],$dateday['mday'],$dateday['year']);
        if ( ! $events = $this->get_records(array()) )
        {
        	return false;
        }
        foreach ( $events as $key=>$event )
        {
            if ( ($event->date >= $time) AND ($event->date < ($time+86400)) )
            {
                if ( ! $this->delete($event->id) ) return false;
            }
        }
        return true;
    }
    /** Получить список событий для учебного потока
     * @param int $csid - id учебного потока
     * @param int $begin - дата начала (если требуются события в указанном интервале) в unixtime
     * @param int $end - дата окончания (если требуются события в указанном интервале) в unixtime
     * @param string $status - статус учебного процесса
     * @return mixed array массив объектов из базы - события для учебного потока
     * или bool false если события не найдены
     */
    public function get_cstream_events($csid, $status=null, $begin=null, $end=null)
    {
    	if ( ! is_int_string($csid) )
    	{//входные данные неверного формата 
    		return false;
    	}
        $select = 'cstreamid = '.$csid;
        if ( is_int_string($begin) AND is_int_string($end) )
        {// получить события за указанный период
            $select .= ' AND ((date >= '.$begin.') AND (date <= '.$end.')) ';
        }
        
        if ( is_string($status) )
        {// получить события с указанным статусом
            $select .= ' AND status = \''.$status.'\'';
        }
        
        return $this->get_records_select($select, null,'date ASC');
    }
    /** Получить список событий для преподавателя
     * 
     * @param int $dpid - id преподавателя
     * @param int $begin - дата начала (если требуются события в указанном интервале) в unixtime
     * @param int $end - дата окончания (если требуются события в указанном интервале) в unixtime
     * @param string $status - статус учебного процесса
     * @return mixed array массив объектов из базы - события для преподавателя 
     * или bool false если процессы для событий не найдены
     */
    public function get_teacher_events($tcid, $status=null, $begin=null, $end=null)
    {
    	if ( ! is_int_string($tcid) )
    	{//входные данные неверного формата 
    		return false;
    	}
        $select = 'teacherid = '.$tcid;
        if ( is_int_string($begin) AND is_int_string($end) )
        {// получить события за указанный период
            $select .= ' AND ((date >= '.$begin.') AND (date <= '.$end.')) ';
        }
        
        if ( is_string($status) )
        {// получить события с указанным статусом
            $select .= ' AND status = \''.$status.'\'';
        }
        
        return $this->get_records_select($select, null,'date ASC');
    }
    /** Получить список событий, принадлежащих структурному подразделению с даты по дату 
     * 
     * @param int $dpid - id структурного подразделения
     * @param int $begin - дата начала (если требуются события в указанном интервале) в unixtime
     * @param int $end - дата окончания (если требуются события в указанном интервале) в unixtime
     * @param string $status - статус учебного процесса
     * @return mixed array массив объектов из базы - события для структурного подразделения 
     * или bool false если процессы для событий не найдены
     */
    public function get_department_events($dpid, $status=null, $begin=null, $end=null)
    {
        // получаем список всех процессов, принадлежащих структурному подразделению
        if ( ! $cstream = $this->dof->storage('cstreams')->get_department_cstream($dpid,null) )
        {// процессов нет - событий нет
            return false;
        }
        $events = array();
        foreach ( $cstream as $key=>$obj )
        {// получаем все события по потоку
        	if ( $event = $this->get_cstream_events($obj->id, $status, $begin, $end) )
        	{
        	    $events += $event;
        	}    
        }
        return $events;
    }
    
    /** Состыковывает контрольную точку с событием
     * @param object $point - контрольная точка
     * @param object $event - событие
     * @return object cостыкованную запись 
     */
    public function get_pevent($point, $event)
    {
        $rez = clone $point;
        // если id точки и события совпадают, состыковываем их
    	if ($event->planid == $rez->id)
    	{
    		$rez->event = $event;
    	}
    	return $rez;
    }
    
    /** Получает массив состыкованных записей контрольной точки с ее событиями
     * 
     * @param object $point - контрольная точка из таблицы plans
     * @param int $cstreamid - id учебного потока в таблице cstreams
     * @param array $statuses - массив статусов с которыми нужно получать события
     * @return array список состыкованных записей
     */
    public function get_pointevents($point, $cstreamid = null, $statuses=null)
    {
    	$mas = array();
    	// получаем все события точки
    	if ( is_null($cstreamid) )
    	{// поток не имеет значения
    	    if ( is_array($statuses) AND ! empty($statuses) )
            {// нужно извлечь события с определенным статусом
                $events = $this->get_records(array('planid'=>$point->id, 'status'=>$statuses));
            }else
            {// статус не имеет значения
                $events = $this->get_records(array('planid'=>$point->id));
            }
    	}else
    	{// все события потока
    	    if ( is_array($statuses) AND ! empty($statuses) )
            {// нужно извлечь события с определенным статусом
                $events = $this->get_records(array('planid'=>$point->id, 'cstreamid'=>$cstreamid, 'status'=>$statuses));
            }else
            {// статус не имеет значения
                $events = $this->get_records(array('planid'=>$point->id, 'cstreamid'=>$cstreamid));
            }
    	}
    	if ( $events )
    	{
    	    foreach ($events as $event)
    	    {// для каждого события
    	        $mas[] = $this->get_pevent($point, $event);
    	    }
    	}
    	return $mas;
    }
    
    /** Получает массив состыкованных записей контрольных точек с их событиями
     * @param array $plans - массив записей контрольных точек из таблицы plans
     * @param int $cstreamid - id потока в таблице cstreams
     * @param array $statuses - массив статусов с которыми нужно получать события
     * @return array список состыкованных записей
     */
    public function get_points_and_events($plans, $cstreamid, $statuses=null )
    {
    	$mas = array();
        if ( !is_array($plans) )
        {// нет контрольных точек - возвращать нечего
            return $mas;
        }
    	foreach ($plans as $point)
    	{// для каждой КТ найдем все события и пристыкуем их
    	    $pointevent = $this->get_pointevents($point, $cstreamid, $statuses);
            if ( ! empty($pointevent) )
            {// если массив не пустой - сольем его с остальными
                $mas = array_merge($mas,$pointevent);
            }elseif ( $point->directmap == 1 )
            {// если массив не пустой - сольем его с остальными
                $pointmas = array($point);
                $mas = array_merge($mas,$pointmas);
            }
    	}
    	return $mas;
    }
    /** Отменяет урок
     * @param int $eventid - id урока
     * @param bool $cancel_all - отменить все события: true - все, включая замененные
     * @return bool true - если удалось обновить, false - иначе
     */
    public function canceled_event($eventid, $cancel_all = false, $cancel_replace = false)
    {
        if ( ! $event = $this->get($eventid) )
        {//событие не найдено';
            return false;
        }
        if ( $event->status == 'canceled' )
        {//урок уже отменен';
            return true;
        }
        if ( $event->status == 'plan' AND ! empty($event->replaceid) AND ! $cancel_replace )
        {// наткнулись на замену - нельзя ее удалять
            return true;
        }
        if ( $cancel_replace AND ! empty($event->replaceid) )
        {// попросили удалить замененный урок - сделаем это
            $this->dof->workflow('schevents')->cancel_any($event->replaceid);
        }
        if ( $event->planid )
        {// если указана тема
            if ( $plan = $this->dof->storage('plans')->get($event->planid) )
            {// и она существует
                if ( $plan->linktype == 'cstreams' AND $plan->status != 'canceled' AND 
                    ! $this->get_records_select('planid='.$plan->id.' AND id != '.$event->id.' AND status != \'canceled\' ' ) )
                {// и она на поток, еще не удалена и нигде не используется
                    $this->dof->workflow('plans')->change($plan->id,'canceled');
                }
            }
        }
        if ( $cancel_all )
        {//надо отменить любое занятие';
            if ( $cancel_all AND $event->status == 'replaced')
            {// если надо удалить замену - найдем ее';
                $this->dof->workflow('schevents')->cancel_any($eventid);
                $eventid = $this->get_replace_event($eventid);
            }
            return $this->dof->workflow('schevents')->cancel_any($eventid);
        }
        if ( ! $cancel_all AND $event->status == 'completed' )
        {//если удалять нужно не все, а статус события проведенный';
            // все хорошо - менять ничего не надо
            return true;
        }
        //меняем статус';
   	    return $this->dof->workflow('schevents')->change($eventid,'canceled');
    }
    /** Находит последнюю замену для события
     * @param int $eventid - id события для которого ищем замену
     * @return int - id замененного события или bool false
     */
    public function get_replace_event($eventid)
    {
        if ( ! is_int_string($eventid) )
        {//входные данные неверного формата 
            return false;
        }
        if ( $replace = $this->get_record(array('replaceid'=>$eventid)) )
        {// если замена есть, найдем ее замену
        	return $this->get_replace_event($replace->id);
        }else
        {// это последняя замена
        	return $eventid;
        }
    }
    
    /** Возвращает массив id КТ уроков для указанного дня
     * @param int $date - день, по котором надо сделать выборку, если не указано, то текущий день 
     * @return array - массив id или bool false - если ничего не найдено
     */
    public function get_plansid_anchored_day($date = null)
    {
        if ( is_null($date) )
        {// если день не указан - возьмем текущий день
            $date = time();
        }
        // формируем дату начала дня
        $dateday = getdate($date);
        $time = $this->dof->storage('persons')->get_make_timestamp(0,0,0,$dateday['mon'],$dateday['mday'],$dateday['year']);
        // создадим условие при котором выборка происходит 
        // не раньше начала дня и не позже его конца
        $select = 'date >= '.$time.' AND date < '.($time+86400);
        return get_fieldset_select($this->tablename(),'planid',$select);
    }
    /** Возвращает список событий по заданным критериям 
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
        $tblcpassed = $this->dof->storage('cpassed')->prefix().$this->dof->storage('cpassed')->tablename();
        $tblcstreams = $this->dof->storage('cstreams')->prefix().$this->dof->storage('cstreams')->tablename();
        $tblschevents = $this->prefix().$this->tablename();
        if (strlen($select)>0)
        {
            $select = 'ev.'.preg_replace('/ AND /',' AND ev.',$select.' ').' AND ';
            $select = preg_replace('/ OR /',' OR ev.',$select);
            $select = str_replace('ev. (','(ev.',$select);
            $select = str_replace('ev.(','(ev.',$select);
        }
        $sql = "SELECT ev.*, cs.programmitemid as programmitemid
                FROM {$tblschevents} as ev, {$tblcpassed} as cp, {$tblcstreams} as cs 
                WHERE $select ev.cstreamid=cp.cstreamid AND ev.cstreamid=cs.id
                ORDER BY ev.date ASC";
        return $this->get_records_sql($sql, null,$limitfrom, $limitnum);
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
        $conds = fullclone($inputconds);
        if ( isset($conds->date_from) AND isset($conds->date_to) )
        {
            // формируем дату начала дня
            $dateday = getdate($conds->date_from);
            $time_from = $this->dof->storage('persons')->get_make_timestamp(0,0,0,$dateday['mon'],$dateday['mday'],$dateday['year']);
            $dateday = getdate($conds->date_to);
            $time_to = $this->dof->storage('persons')->get_make_timestamp(0,0,0,$dateday['mon'],$dateday['mday'],$dateday['year']);
            // создадим условие при котором выборка происходит 
            // не раньше начала дня и не позже его конца
            $selects[] = 'date >= '.$time_from.' AND date < '.($time_to+86400);
            unset($conds->date_from);
            unset($conds->date_to);
        }  
        if ( isset($conds->studentid) )
        {
            if ( isset($conds->cpassedstatus) )
            {// если для подписок заданы статусы - учтем их
                $cpassed = $this->dof->storage('cpassed')->get_records(array
                           ('studentid'=>$conds->studentid,'status'=>$conds->cpassedstatus));
            }else
            {// выведем все подписки
                $cpassed = $this->dof->storage('cpassed')->get_records(array('studentid'=>$conds->studentid));
            }
            if ( $cpassed )
            {// есть записи принадлежащие такой академической группе
                $cpassids = array();
                foreach ( $cpassed as $cpass )
                {// собираем все cstreamids
                    $cpassids[] = $cpass->cstreamid;
                }
                // склеиваем их в строку
                $cpassedstring = implode(', ', $cpassids);
                // составляем условие
                $selects[] = ' cstreamid IN ('.$cpassedstring.')';
            }elseif ($conds->studentid)
            {
                return 'id = -1';
            }
            unset($conds->studentid);
            unset($conds->cpassedstatus);
        }
        if ( isset($conds->cpassedstatus) )
        {// удалим статусы подписок
            unset($conds->cpassedstatus);
        }
        if ( isset($conds->status) )
        {
            // склеиваем их в строку
            $status = implode('\', \'', $conds->status);
            // составляем условие
            $selects[] = 'status IN (\''.$status.'\')';
            unset($conds->status);
        }
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
     * Возвращающая отсортированный по дате массив объектов КТ и событий
     * @param int cstreamid      - id КТ 
     * @param string stevent - статус событий
     * @param string stplan  - статус КТ
     * @param bool emevent[optional] - флаг(true-вывод весь массив, false-вывод, где КТ<>null)
     * @return array
     */
    public function get_mass_date($cstreamid,$stevent,$stplan,$emevent=true)
    {
        if ( ! $this->dof->storage('cstreams')->get($cstreamid) )
        {// не нашли запись с такими данными
            return false;
        }
        // выборка для events по условию
        $rezevent = $this->get_records(array('cstreamid'=>$cstreamid, 'status'=>$stevent));
        // выборка КТ
        $rezplan = $this->dof->storage('plans')->get_theme_plan('cstreams', $cstreamid, $stplan, true, '1');
        // записываем в massiv объект с полями
        $massiv = array();
        // ->date = date события
        // ->plan = (obj)plan,если нашелся в массиве rezplan иначе - null
        // и удаляяем plan в случае записи его
        // ->event = (obj)$event
        if ( isset($rezevent) AND ! empty($rezevent) )
        {// есть запись по событиям
            foreach ($rezevent as $event)
            {
                $obj = new object;
                $obj->date = $event->date;
                $obj->event = $event;
                $obj->plan = null;
                if ( isset($rezplan) AND ! empty($rezplan) 
                         AND isset($rezplan[$event->planid]) AND $event->status != 'replaced' )
                {// есть запись rezplan
                    $obj->plan = $rezplan[$event->planid];
                    unset($rezplan[$event->planid]);
                }
                // в зависимости от флага пишем массивs
                if ( $emevent)
                {
                    $massiv[] = $obj;
                }elseif ( $obj->plan )
                {
                    $massiv[] = $obj;                 
                }
            }
        }
        // остались КТ - дозапишем их в массив
        if ( isset($rezplan) AND ! empty($rezplan) )
        {
            foreach ($rezplan as $plan)
            {// рассматриваем массив из оставшихся КТ
                $obj = new object;
                // определим объект plan
                $obj->event = null;
                $obj->plan = $plan;
                // пишем дату иcходя из типа linktype
                switch ($plan->linktype)
                {
                    case 'ages' : $recage = $this->dof->storage('ages')->get($plan->linkid);
                        $obj->date = $recage->begindate + $plan->reldate;
                        break;
                    case 'programmitems' :
                    case 'cstreams' :
                    case 'plan' : $cstream = $this->dof->storage('cstreams')->get($cstreamid);
                        $obj->date = $cstream->begindate +  $plan->reldate;
                        break;
                }  
                // дозаписываем в массив
                $massiv[] = $obj;          
            }
        }
        // отсортируем массив по дате
        usort($massiv, array('dof_storage_schevents','sort_by_date'));
        
        return $massiv;
    }
    
    /** Привязать событие к контрольной точке планирования
     * Обновляет событие, записывая в поле planid - id контрольной точки
     * @todo сделать проверку того, можно ли переданное событие привязывать к переданной контрольной точке
     * @todo сделать проверку на статусы события, потока, и контрольной точки
     * 
     * @return bool
     * @param int $eventid - id учебного события в таблице schevents
     * @param int $planid - id точки тематического планирования в таблице plans
     * @param int $cstreamid[optional] - id учебного потока в таблице cstreams
     */
    public function link_event_with_plan($eventid, $planid, $cstreamid=null)
    {
        if ( ! $event = $this->get($eventid) )
        {// нет такого события
            return false;
        }
        if ( ! $this->dof->storage('plans')->is_exists($planid) )
        {// переданной контрольной точки не существует
            return false;
        }
        // записываем в событие id контрольной точки
        $event->planid = $planid;
        
        return $this->update($event);
    }
    
    /** Функция сортировки объектов по дате
     * @param $obj1
     * @param $obj2
     * @return unknown_type
     */
    private function sort_by_date($obj1,$obj2)
    {
        return strnatcmp($obj1->date, $obj2->date);
    }
    
    /** Возвращает список событий по заданным критериям(отображение по времени) 
     * 
     * @return array массив записей из базы, или false в случае ошибки
     * @param object $conds[optional] - объект со списком свойств, по которым будет происходить поиск
     * @return array массив записей из базы, или false в случае ошибки
     */
   public function get_time_list($conds)
    {
        // таблицы, учавствующие в запросе
        $tblcstreams = $this->prefix().$this->dof->storage('cstreams')->tablename();
        $tbcpassed = $this->prefix().$this->dof->storage('cpassed')->tablename();
        $tbl = $this->prefix().$this->tablename();
        // условия выборки по статусам
        $sqlcsstatus    =trim($this->query_part_select('c.status',$conds->cstreamsstatus));
        $sqleventstatus = trim($this->query_part_select('sch.status',$conds->status));
        // условие по времени
        $time = '';
        if ( isset($conds->date_from) AND isset($conds->date_to) )
        {// времеенной интервал
            // формируем дату начала дня
            $dateday = getdate($conds->date_from);
            $time_from = $this->dof->storage('persons')->get_make_timestamp(0,0,0,$dateday['mon'],$dateday['mday'],$dateday['year']);
            $dateday = getdate($conds->date_to);
            $time_to = $this->dof->storage('persons')->get_make_timestamp(0,0,0,$dateday['mon'],$dateday['mday'],$dateday['year']);
            // создадим условие при котором выборка происходит 
            // не раньше начала дня и не позже его конца
            $time = ' (sch.date >= '.$time_from.' AND sch.date < '.($time_to+86400).') AND';
        }
        if ( isset($conds->to_end_lesson) AND $conds->to_end_lesson )
        {
            // создадим условие при котором выборка происходит 
            // не раньше конца урока
            $time = ' (sch.date + sch.duration < '.time().') AND ';
        }
        // передали персону-учитель
        $teacher = '';
        if ( isset($conds->teacherid) )
        {// только те уроки, где персона учитель
            $teacher = ' AND sch.teacherid='.$conds->teacherid;
        }
        // передали персону - ученик
        $sqlcpasstatus = '';
        $tabelcpassed = '';
        $studentid = '';
        if ( isset($conds->studentid) )
        {// только те потоки, где персона учитель
            $sqlcpasstatus = trim($this->query_part_select('cpas.status',$conds->cpassedstatus));
            $tabelcpassed = " INNER JOIN {$tbcpassed} as cpas ON sch.cstreamid=cpas.cstreamid";
            
            $studentid = " AND {$sqlcpasstatus} AND cpas.studentid={$conds->studentid}";
        }
        // условия по подразделениям
        if ( isset($conds->departmentid) AND $conds->departmentid )
        {// учитываем подразделения
            // объединяем два запроса, т.к. в один слить нельзя, потому что в одном запросе 2 , а в др 3 таблицы учавствуют
            $depid = $conds->departmentid;
            $sql = "(SELECT sch.*, c.programmitemid as programmitemid
                FROM {$tbl} as sch INNER JOIN {$tblcstreams} as c ON sch.cstreamid=c.id {$tabelcpassed}
                WHERE {$time} {$sqleventstatus} {$teacher} {$studentid} AND {$sqlcsstatus} AND c.departmentid={$depid})";    
            // сортировка
            $sql .= " ORDER BY date ASC";        
        }else 
        {// без подразделения
           $sql = "SELECT DISTINCT sch.*, c.programmitemid as programmitemid
                FROM {$tbl} as sch INNER JOIN {$tblcstreams} as c ON sch.cstreamid=c.id {$tabelcpassed}
                WHERE {$time} {$sqleventstatus} {$teacher} {$studentid} AND {$sqlcsstatus} "; 
           // сортировка
           $sql .= " ORDER BY sch.date ASC";
        }
        return $this->get_records_sql($sql);
    }    

    /** Возвращает список персон(ученики/учителя)
     * по сложной выборке по нескольким таблицам
     * @param object $conds[optional] - объект со списком свойств, по которым будет происходить поиск
     * @return array массив записей из базы, или false в случае ошибки
     */
    public function get_persons_list($conds, $object)
    {
        // таблицы, учавствующие в запросе
        $tblappoint = $this->prefix().$this->dof->storage('appointments')->tablename();
        $tbleagreement = $this->prefix().$this->dof->storage('eagreements')->tablename();
        $tblcstreams = $this->prefix().$this->dof->storage('cstreams')->tablename();
        $tblperson = $this->prefix().$this->dof->storage('persons')->tablename();
        $tbcpassed = $this->prefix().$this->dof->storage('cpassed')->tablename();
        $tblcstreams = $this->prefix().$this->dof->storage('cstreams')->tablename();
        $tbl = $this->prefix().$this->tablename();
        // первоначальный запрос
        switch ($object)
        {
            case 'students':
                $sql = "SELECT DISTINCT pr.*
                	FROM {$tbl} as sch INNER JOIN {$tblcstreams} as c ON sch.cstreamid=c.id
                					   INNER JOIN {$tbcpassed} as cp ON c.id=cp.cstreamid
                					   INNER JOIN {$tblperson} as pr ON cp.studentid=pr.id 
                	WHERE ";
                $sql .= trim($this->query_part_select('c.status',$conds->cstreamsstatus));				   
                break;
            case 'teachers':
                $sql = "SELECT DISTINCT pr.*
                	FROM {$tbl} as sch INNER JOIN {$tblappoint} as ap ON sch.appointmentid=ap.id
                					   INNER JOIN {$tbleagreement} as ea ON ap.eagreementid=ea.id
                					   INNER JOIN {$tblperson} as pr ON ea.personid=pr.id
                					   INNER JOIN {$tblcstreams} as c ON sch.cstreamid=c.id 
                	WHERE ";
               	break;
        }
        if ( $object == 'students' )
        {
            $sql .= ' AND ';
        }
        // условия выборки
        $sql .=trim($this->query_part_select('sch.status',$conds->status));      
        if ( isset($conds->cpassedstatus) )
        {// статусы - добавим в выборку
             $sql .=' AND '.trim($this->query_part_select('cp.status',$conds->cpassedstatus));
        }
        if ( isset($conds->appointstatus) )
        {// статусы - добавим в выборку
             $sql .=' AND '.trim($this->query_part_select('ap.status',$conds->appointstatus));
        }
          
        if ( isset($conds->date_from) AND isset($conds->date_to) )
        {// времеенной интервал
            // формируем дату начала дня
            $dateday = getdate($conds->date_from);
            $time_from = $this->dof->storage('persons')->get_make_timestamp(0,0,0,$dateday['mon'],$dateday['mday'],$dateday['year']);
            $dateday = getdate($conds->date_to);
            $time_to = $this->dof->storage('persons')->get_make_timestamp(0,0,0,$dateday['mon'],$dateday['mday'],$dateday['year']);
            // создадим условие при котором выборка происходит 
            // не раньше начала дня и не позже его конца
            $sql .= ' AND (sch.date >= '.$time_from.' AND sch.date < '.($time_to+86400).')';
        }
        // усливие по времени
        if ( isset($conds->departmentid) AND $conds->departmentid  )
        {
             $sql .=' AND c.departmentid='.$conds->departmentid;
        }
        // объединение + сортировка запросов
        $sql2 = "{$sql} ORDER BY sortname";  
        return $this->get_records_sql($sql2);
    }      
    
    /** Переводит в удаленный статус запланированные события, которые привязаны к удаленным или несуществующим дням
     * @todo вот в этой функции с потрясающей яркостью ощущается отсутствие в нашей системе возможности
     *       записывать ошибки в лог. Потому что false во время одно й и той же ошибки возвращать бесполезно
     *       а что произошло узнать надо. Добавить здесь использование логов, как только появится такая возможность
     * @todo оптимизировать эту функцию, сделать один join-запрос по таблицам days и schevents
     * @todo выводить процесс выполнения (в режиме отладки)
     * 
     * @param int $begintime - время, начиная с которогно нужно удалить события
     * 
     * @return bool true если все события удалены или false в случае ошибки
     */
    protected function delete_broken_events($begintime=0)
    {
        $result = true;
        $eventids = array();
        // времени понадобится много
        dof_hugeprocess();
        $datesql = '';
        if ( $begintime )
        {
            $datesql = 'date > '.$begintime.' AND ';
        }
        // выводим сообщение о том что начинается очистка таблицы событий
        dof_mtrace(2, 'Starting schevents cleanup (storage/schevents)...');
        $num = 0;
        while ( $events = $this->get_records_select($datesql.'  status IN("plan", "postponed") AND dayid IS NOT NULL ', null,'', 'id, dayid, status', $num, 100) )
        {// нас интересуют все запланированные или отложенные события, которые привязаны к 
            $num += 100;
            // несуществующим или удаленным дням
            foreach ( $events as $event )
            {
                if ( ! $day = $this->dof->storage('schdays')->get($event->dayid) OR
                       $day->status == 'deleted' )
                {
                    $eventids[] = (int)$event->id;
                }
            }
        }
        
        foreach ( $eventids as $eventid )
        {// перебираем все события, которые надо изменить
            $result = $result AND $this->dof->workflow('schevents')->change($eventid, 'canceled');
            dof_mtrace(2, 'eventid='.$eventid.' has been linked with the broken day. Fixed.');
        }
        
        return $result;
    }
    
    /** Переводит в удаленный статус запланированные события, которые привязаны к удаленным или несуществующим дням
     * @todo вот в этой функции с потрясающей яркостью ощущается отсутствие в нашей системе возможности
     *       записывать ошибки в лог. Потому что false во время одно й и той же ошибки возвращать бесполезно
     *       а что произошло узнать надо. Добавить здесь использование логов, как только появится такая возможность
     * @todo оптимизировать эту функцию, сделать один join-запрос по таблицам days и schevents
     * @todo выводить процесс выполнения (в режиме отладки)
     * 
     * @param int $begintime - время, начиная с которогно нужно удалить события
     * 
     * @return bool true если все события удалены или false в случае ошибки
     */
    protected function update_salfactors($departmentid)
    {
        $result = true;
        // времени понадобится много
        dof_hugeprocess();
        $dopsql = '';
        if ( $departmentid )
        {
            $dopsql = 'departmentid = '.$departmentid.' AND ';
        }
        // выводим сообщение о том что начинается очистка таблицы событий
        dof_mtrace(2, 'Starting schevents update (storage/schevents)...');
        $num = 0;
        while ( $events = $this->get_records_select($dopsql.' date > 1377993600 ', null,'', 'id', $num, 100) )
        {// нас интересуют все события с коэффициентами
            $num += 100;
            // несуществующим или удаленным дням
            foreach ( $events as $event )
            {
                $obj = new stdClass;
                $obj->ahours = 1;
                $result = $result && $this->update($obj,$event->id,true);
                $obj->salfactor      = $this->dof->workflow('schevents')->calculation_salfactor($event->id);
        		$obj->salfactorparts = serialize($this->dof->workflow('schevents')->calculation_salfactor($event->id, true, true));
        		$obj->rhours         = $this->dof->workflow('schevents')->calculation_salfactor($event->id,true);
        		$result = $result && $this->update($obj,$event->id,true);
            }
        }
        
        return $result;
    }
    
    /** 
     * Получить список уроков по назначению на должность
     * 
     * @param int $apid - id из таблица appointments(назначение на должность)
     * @param $begintime - время в формате uniх с какого времени брать уроки
     * @param $endtime - время в формате uniх до какого времени брать уроки 
     * @return array $result - рузультат выборки, либо false
     */
    public function get_event_on_appoint($apid, $begintime=0, $endtime=0)
    {
        if ( ! $apid )
        {// теряется смысл этого метода
            return array();
        }
        // учтем вреям начало
        $sql = '';
        if ( $begintime )
        {
            $sql .= " date>=$begintime AND ";
        }
        if ( $endtime )
        {
            $sql .= " date<=$endtime AND ";
        }
        // сформируем до конца запрос
        $sql .= " appointmentid=$apid";
        
        return $this->get_records_select($sql,null,'date');
    }   
      
} 
?>