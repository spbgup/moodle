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

/** Изучаемые и пройденные курсы
 * 
 */
class dof_storage_cpassed extends dof_storage implements dof_storage_config_interface
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
        global $CFG;
        require_once($CFG->libdir.'/ddllib.php');//методы для установки таблиц из xml
        $result = true;
        
        // обновляем права доступа, если есть такая необходимость
        return $result && $this->dof->storage('acl')->save_roles($this->type(),$this->code(),$this->acldefault());
     }
    /** Возвращает версию установленного плагина
     * @return string
     * @access public
     */
    public function version()
    {
        // Версия плагина (используется при определении обновления)
		return 2013082900;
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
        return 'cpassed';
    }
    /** Возвращает список плагинов, 
     * без которых этот плагин работать не может
     * @return array
     * @access public
     */
    public function need_plugins()
    {
		return array('storage'=>array('cstreams'      => 2009060800,
		                              'programmsbcs'  => 2009052900,
		                              'programmitems' => 2009060800,
		                              'persons'       => 2009060400,
                                      'acl'           => 2011041800,
                                      'config'        => 2011080900));
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
                                      'config'=> 2011080900));
        }
        if ( $oldversion AND $oldversion < 2010120900 )
        {
            return array('storage'=>array('acl'=>2011040504,
                                      'config'=> 2011080900,
                                      'statushistory' => 0,
                                      'cstreams' => 2009060800));
        }
        if ( $oldversion AND $oldversion < 2010123000 )
        {
            return array('storage'=>array('acl'=>2011040504,
                                      'config'=> 2011080900,
                                      'statushistory' => 0,
                                      'cstreams' => 2009060800));
        }
        return array();
    }
    /** Список обрабатываемых плагином событий 
     * @return array - array(array('plugintype'=>..,'plugincode'=>..,'eventcode'=>..),...)
     * @access public
     */
    public function list_catch_events()
    {
        return array(// обрабатываем зачисление и отчисление из группы
                     array('plugintype'=>'storage', 'plugincode'=>'programmsbcs', 'eventcode'=>'changegroup'),
                     // Обрабатываем подписку/отписку группы на поток
                     array('plugintype'=>'storage', 'plugincode'=>'cstreamlinks', 'eventcode'=>'insert'),
                     array('plugintype'=>'storage', 'plugincode'=>'cstreamlinks', 'eventcode'=>'delete'),
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
        switch ( $do )
        {// определяем дополнительные параметры в зависимости от запрашиваемого права
            // право на создание события в своем журнале             
            case 'edit:grade/own':
                $cstreamid = $objid;
                if ( time() < $this->dof->storage('cstreams')->get_field($cstreamid,'enddate') )
                {// поток еще не завершился, нельзя выставлять итоговые оченки
                    return false;
                }
                if ( ! $this->dof->storage('cstreams')->is_exists(array('id'=>$cstreamid,'teacherid'=>$personid)) )
                {// пользователь - не препадаватель на данном потоке
                    return false;
                }
            break;
            //право выставить итоговые оценки
            case 'edit:grade/auto':
                $cstreamid = $objid;
                if ( time() < $this->dof->storage('cstreams')->get_field($cstreamid,'enddate') )
                {// поток еще не завершился, нельзя выставлять итоговые оченки
                    return false;
                }
                 if ( ! $this->dof->storage('cstreams')->is_exists(array('id'=>$cstreamid,'teacherid'=>$personid)) )
                {// пользователь - не препадаватель на данном потоке
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
        if ( $gentype === 'storage' AND $gencode === 'cstreamlinks' )
        {//обрабатываем события от справочника cstreamlink
            switch($eventcode)
            {
                //синхронизируем подписки группы
                case 'insert': return $this->syncronize_agroup_with_cstream($intvar);
                //удаляем подписки';
                case 'delete': return $this->unsign_agroups_from_cstream($mixedvar['old']->cstreamid);
            }
        }
        if ( $gentype === 'storage' AND $gencode === 'programmsbcs' )
        {//обрабатываем события от справочника programmsbcs
            switch ( $eventcode )
            {
                //произошла смена группы';
                case 'changegroup': 
                {    
                    return $this->change_group($mixedvar['oldagroup'], $mixedvar['newagroup'], $mixedvar['programmsbc']);
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
        switch ($code)
        {
            // Сопоставим активные cpasseed с cstream
            case 'comparecpassed':     return $this->compare_cpassed_with_cstream(); break;
            // приостановить, а затем запустить все записи об изучаемых 
            // и пройденных курсах у всех учеников в указанном периоде
            case 'resync_age_cpassed': return $this->todo_resync_age_cpassed($intvar,$mixedvar->personid) ; break;
            case 'resync_cancaled_active_cpassed': $this->todo_cancaled_active_cpassed(); break;
            // запуск всез приостановленных
            case 'suspend_to_active_cpassed': return $this->todo_suspend_to_active($intvar,$mixedvar->personid); break;
            // остановка всех активных периода
            case 'active_to_suspend_cpassed': return $this->todo_active_to_suspend($intvar,$mixedvar->personid); break;
            // Разрывает связку отмененной подписки с пересдачамии
            case 'cancaled_repeatid_to_null': return $this->todo_cancaled_active_cpassed(); break;
            
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
        return 'block_dof_s_cpassed';
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
        
        $a['view']     = array('roles'=>array('manager', 'methodist'));
        $a['edit']     = array('roles'=>array('manager'));
        $a['create']   = array('roles'=>array('manager'));
        $a['delete']   = array('roles'=>array());
        $a['use']      = array('roles'=>array('manager', 'methodist'));
        // право выставлять оценку
        $a['edit:grade'] = array('roles'=>array('manager'));
        // право выставлять оценку в своем журнале
        $a['edit:grade/own'] = array('roles'=>array('teacher'));
        // право выставлять оценку автоматически
        $a['edit:grade/auto'] = array('roles'=>array('manager'));
        
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
        // настройка выбора cpassed для
        // итоговых оценок в журнале
        $obj = new object();
        $obj->type = 'select';
        $obj->code = 'finalgrade';
        $obj->value = '0';
        $config[$obj->code] = $obj;  
        //поправочный зарплатный коэффициент кол-ва учеников
        $obj = new object();
        $obj->type = 'text';
        $obj->code = 'salfactor_count_students';
        $obj->value = '1-0;2-0.5;3-0.5;4-0.5;5-3';
        $config[$obj->code] = $obj;         
        return $config;
    }
    
    // **********************************************
    //              Собственные методы
    // **********************************************
    
    /** Возвращает список учебных потоков по заданным критериям 
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
        $select = $this->get_select_listing($conds,'c.');
        // возвращаем ту часть массива записей таблицы, которую нужно
        $tblprogramms = $this->dof->storage('programms')->prefix().$this->dof->storage('programms')->tablename();
        $tblprogrammitems = $this->dof->storage('programmitems')->prefix().$this->dof->storage('programmitems')->tablename();
        $tblpersons = $this->dof->storage('persons')->prefix().$this->dof->storage('persons')->tablename();
        $tblages = $this->dof->storage('ages')->prefix().$this->dof->storage('ages')->tablename();
        $tblprogrammsbcs = $this->dof->storage('programmsbcs')->prefix().$this->dof->storage('programmsbcs')->tablename();
        $tblcpassed = $this->prefix().$this->tablename();
        if (strlen($select)>0)
        {
            $select .= ' AND ';
        }
        $sql = "FROM {$tblcpassed} as c, {$tblprogrammitems} as pi, {$tblprogramms} as p, {$tblpersons} as pr, {$tblages} as ag, {$tblprogrammsbcs} as ps
                WHERE $select c.programmitemid=pi.id AND pi.programmid=p.id AND c.studentid=pr.id AND c.ageid=ag.id AND c.programmsbcid=ps.id";
        if ( $countonly )
        {// посчитаем общее количество записей, которые нужно извлечь
            return $this->count_records_sql("SELECT COUNT(*) {$sql}");
        }
        $sql = "SELECT c.*, pi.name as pitemname, pi.code as pitemcode,
                       p.name as progname, p.code as progcode {$sql}";
                
        // сортировка
        $order_by = ' ORDER BY ';
        switch ($sort)
        {
            case 'sortname'     : $sql .= $order_by."pr.sortname ASC, pitemname ASC, ps.agenum ASC "; break;
            case 'sortprogramm' : $sql .= $order_by."pitemname ASC, ag.name ASC, ps.agenum ASC";  break;
            case 'sortage'      : $sql .= $order_by."ag.name ASC, ps.agenum ASC, pitemname ASC "; break;
            case 'sortstatus'   : $sql .= $order_by."c.status ASC, ag.name ASC ";   break;
            case 'sortagenum'   : $sql .= $order_by."ps.agenum ASC, ag.name ASC ";  break;
            case 'sortagroup'   : $sql .= $order_by." c.agroupid , ag.name ASC ";   break;
            default:  $sql .= " ORDER BY p.name ASC, pi.name ASC, pr.sortname ASC";
        }
        
        return $this->get_records_sql($sql, null,$limitfrom, $limitnum);
    }
    
    /**
     * Возвращает фрагмент sql-запроса после слова WHERE
     * @param object $inputconds - список полей с условиями запроса в формате "поле_БД->значение" 
     * @param string $prefix - префикс к полям, если запрос составляется для нескольких таблиц
     * @return string
     */
    public function get_select_listing($inputconds,$prefix='')
    {
        // создадим массив для фрагментов sql-запроса
        $selects = array();
        $conds = fullclone($inputconds);
        if ( isset($conds->agroupid) AND intval($conds->agroupid) )
        {// ищем записи по академической группе
            $cstreams = $this->dof->storage('cstreamlinks')->get_records(array('agroupid'=>$conds->agroupid), null, 'cstreamid');
            if ( $cstreams )
            {// есть записи принадлежащие такой академической группе
                $cstreamids = array();
                foreach ( $cstreams as $cstream )
                {// собираем все cstreamids
                    $cstreamids[] = $cstream->cstreamid;
                }
                // склеиваем их в строку
                $cstreamidsstring = implode(', ', $cstreamids);
                // составляем условие
                $selects[] = ' '.$prefix.'cstreamid IN ('.$cstreamidsstring.')';
            }else
            {// нет записей принадлежащих такой академической группе
                // составим запрос, который гарантированно вернет false
                return ' '.$prefix.'cstreamid = -1 ';
            }
            // убираем agroupid из запроса для того чтобы не создать 2 условия для одного поля
            unset($conds->agroupid);
        }
        if ( isset($conds->noagroupid) AND intval($conds->noagroupid) )
        {// ищем записи по академической группе
            $conds->agroupid = array(0,null);
            unset($conds->noagroupid);
        }
        if ( isset($conds->programmid) AND intval($conds->programmid) )
        {// ищем записи по академической группе
            $cstreams = $this->dof->storage('programmitems')->get_records(array('programmid'=>$conds->programmid), null, 'id');
            if ( $cstreams )
            {// есть записи принадлежащие такой академической группе
                $cstreamids = array();
                foreach ( $cstreams as $cstream )
                {// собираем все cstreamids
                    $cstreamids[] = $cstream->id;
                }
                // склеиваем их в строку
                $cstreamidsstring = implode(', ', $cstreamids);
                // составляем условие
                $selects[] = ' '.$prefix.'programmitemid IN ('.$cstreamidsstring.')';
            }else
            {// нет записей принадлежащих такой академической группе
                // составим запрос, который гарантированно вернет false
                return ' '.$prefix.'programmitemid = -1 ';
            }
            // убираем agroupid из запроса для того чтобы не создать 2 условия для одного поля
            unset($conds->programmid);
        }
        if ( isset($conds->departmentid) AND intval($conds->departmentid) )
        {// ищем записи по подразделению
            // получим их из зависимости с потоком
            $cstreams   = $this->dof->storage('cstreams')->get_records(array('departmentid'=>$conds->departmentid), null, 'id');
            if ( $cstreams )
            {// есть записи принадлежащие такому подразделению
                $cstreamids = array();
                foreach ( $cstreams as $cstream )
                {// собираем все cstreamids
                    $cstreamids[] = $cstream->id;
                }
                // склеиваем их в строку
                $cstreamidsstring = implode(', ', $cstreamids);
                // составляем условие
                $selects[] = ' '.$prefix.'cstreamid IN ('.$cstreamidsstring.')';
            }else
            {// нет записей принадлежащих такой академической группе
                // составим запрос, который гарантированно вернет false
                return ' '.$prefix.'cstreamid = -1 ';
            }
            // убираем agroupid из запроса для того чтобы не создать 2 условия для одного поля
            unset($conds->departmentid);
        }
        if ( isset($conds->status) AND is_array($conds->status) )
        {
            //@TODO - разобраться, можно ли без этого обойтись
            // склеиваем их в строку
            $status = implode('\', \'', $conds->status);
            // составляем условие
            $selects[] = ''.$prefix.'status IN (\''.$status.'\')';
            unset($conds->status);
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
    
    /** Добавить оценку в режиме "перезачета" 
     *  В случае пересдачи одновременно обновляется статус исходной подписки на "пересдан". 
     * @param int $stid - id студента
     * @param int $prid - id дисциплины
     * @param int $cpid - id подписки на программу
     * @param int $grade - итоговая оценка
     * @param string $comment - коментарий
     * @param string $status - статус - перезачет или пересдача
     * @param int $initialid - id исходной подписки
     * @return mixed int id добавленной записи и bool false если добавление не удалось
     */
    public function insert_grade_reoffset($stid, $prid, $cpid, $grade, $status, $comment, $initialid)
    {
        if ( ! is_int_string($initialid) OR ! is_int_string($stid) 
                OR ! is_int_string($prid) OR ! is_int_string($initialid))
        {//входные данные неверного формата 
            return false;
        }
        if ( ('reoffset' <> $status) AND ('repeating' <> $status))
        {
        	return false;
        }
        if ( 'repeating' == $status )
        {
        	$obj1->status = $status;
            if ( ! $this->update($obj1, $initialid) )
            {
            	return false;
            }
        }
        $obj = new object;
        $obj->studentid = $stid;
        $obj->programmitemid = $prid;
        $obj->programmsbcid = $cpid;
        $obj->grade = $grade;
        $obj->notice = $comment;
        $obj->status = 'reoffset';
        $obj->repeatid = $initialid;
        return $this->insert($obj);       

    }
    
    /** Отписывает студента от одного потока
     * @param int $studentid - id студента
     * @param int $cstreamid - id потока
     * @return boоl true в случае успеха и falsе в остальных
     */
    public function unsign_student_one_cpassed($studentid, $cstreamid)
    {
        if ( ! is_int_string($studentid) OR ! is_int_string($cstreamid) )
        {//входные данные неверного формата
            return false;
        }
        // найдем подписки студента по одному потоку
        $params = array();
        $params['studentid'] = $studentid;
        $params['cstreamid'] = $cstreamid;
        $params['status'] = array('plan','active','suspend');
        $cpassed = $this->get_record($params);
        if ( ! $cpassed )
        {// подписка не найдены';
            return true;
        }
        // отпишем подписку
        return $this->set_final_grade($cpassed->id);
    }

    /** Удаление всех "лишних" подписок из потока,
     * которые были записаны в составе группы, связи с которой сейчас нет
     */
    public function unsign_agroups_from_cstream($cstreamid)
    {
        //получаем cstream';
        if ( ! $cstream = $this->dof->storage('cstreams')->get($cstreamid) )
        {//не получили предмето-поток';
            return false;
        }
        //print_object($cstream);
        //получаем все cpassed, привязанные к этому cstream
        if ( ! $cpassedlist = $this->get_records(array('cstreamid'=>$cstream->id)) )
        {//не получили список предмето потоков';
            return false;
        }
        //Получить массив agroup привязанных к одному потоку
        if ( ! $agrouplist = $this->dof->storage('agroups')->get_group_cstream($cstream->id) )
        {//не получили список групп';
            $agrouplist = array();
        }
        //print_object($agrouplist);die;
        $agroupids = array();
        foreach ( $agrouplist as $one )
        {
            $agroupids[$one->id] = $one;
        }
        //print_object($agroupids);
        $rez = true;
        foreach ($cpassedlist as $one)
        {
           //print_object($one);
           if ( ! is_null($one->agroupid) AND ! array_key_exists($one->agroupid, $agroupids) )
           {// Прекращаем подписку';
               $rez = $rez AND $this->unsign_student_one_cpassed($one->studentid, $cstream->id);
           }

        }
        return $rez;
    }
    
    /** Отписывает студента от всех потоков программы
     * @param int $studentid - id студента
     * @param int $programmsbcid - id подписки студента на программу
     * @return boоl true в случае успеха и falsе в остальных
     */
    public function unsign_student_all_cpassed($studentid, $programmsbcid)
    {
        if ( ! is_int_string($studentid) OR ! is_int_string($programmsbcid) )
        {//входные данные неверного формата
            return false;
        }
        if ( ! $programmsbc = $this->dof->storage('programmsbcs')->get($programmsbcid) )
        {//не получена подписка на программу
            return false;
        }
        if ( ! $ageid = $this->dof->storage('ages')->
                get_next_ageid($programmsbc->agestartid, $programmsbc->agenum) )
        {//не получили id текущего учебного периода
            return false;
        }
//        if ( ! $cstreamlist = $this->dof->storage('cstreams')->get_list() )
        // выбираем подписки студента
        $cpassed = $this->get_records(array('programmsbcid'=>$programmsbcid,'studentid'=>$studentid));
        if ( ! $cpassed )
        {// подписки не найдены
            return true;
        }
        // переберем подписки
        foreach ($cpassed as $cpass)
        {// отпишем каждую
            if ( ! $this->unsign_student_one_cpassed($studentid, $cpass->cstreamid) )
            {//не удалось отписать, сообщим об ошибке
                return false;
            }
        }
        // успешно все отписали
        return true;
    }
    
    /** Отписывает нескольких студентов от всех потоков программы
     * @param array $students - массив (id=>подписки на программу студента=>id студента)
     * @return boоl true в случае успеха и falsе в остальных
     */
    public function unsign_students_all_cpassed($students)
    {
        if ( ! is_array($students) )
        {//входные данные неверного формата
            return false;
        }
        foreach ( $students as $sbcid=>$stid)
        {// для каждого студента отпишем его подписки
            if ( ! $this->unsign_student_all_cpassed($stid, $sbcid) )
            {//не удалось отписать
                return false;
            }
        }
        // успешно всех отписали
        return true;
    }
    
    /** Получить всех учеников, не подписанных на академическую группу
     * 
     * @return bool|array - массив из записей таблицы cpassed или false, если ничего не нашлось
     */
    public function get_students_without_agroup()
    {
        return $this->get_records_select(' (agroupid IS NULL OR agroupid = 0 OR agroupid = "") 
        AND ( status="plan" OR status="active" )');
    }
    
    /** Отписать всех учеников, не имеющих подписки ни на одну группу
     * 
     * @return bool true  - если изменение всех статусов прошло нормально или
     *              false - если в процессе работы произошли ошибки
     */
    public function unsign_students_without_agroup()
    {
        // получим все подписки учеников, не имеющих связи с группой
        $cpasseds = $this->get_students_without_agroup();
        if ( ! $cpasseds )
        {// отписывать некого - все и так нормально
            return true;
        }
        $result = true;
        foreach ( $cpasseds as $cpassed )
        {// перебираем подписки по одной и меняем их статус
            $result = $result AND $this->dof->workflow('cpassed')->change($cpassed->id, 'canceled');
        }
        // возвращаем результат: true если все прошло успешно или false в случае ошибки
        return $result;
    }
    
    /** Проверяет, подписан ли уже этот ученик на переданный поток
     * из поиска исключаются записи с переданным статусом
     * 
     * @return int|bool - id записи если такая запись уже есть, или false, если ученик еще не подписан 
     * @param int $studentid - id ученика в таблице persons
     * @param int $cstreamid - id учебного потока в таблице cstreams
     * @param mixed $status string - код статуса или
     * array - массив статусов, или null, если их нет 
     */
    public function is_already_enroled($studentid, $cstreamid, $status = null)
    {
        //dof_debugging('storage/cpassed is_already_enroled(). Переписать sql-выборку по-нормальному', DEBUG_DEVELOPER);
        if ( is_null($status) )
        {//статусы не переданы';
            $cpassed = $this->get_records(array('studentid'=>$studentid, 
                                                'cstreamid'=>$cstreamid,'repeatid'=>array(0,null)));
        }elseif ( is_string($status) )
        {//передан только один статус';
            $select = "studentid = {$studentid} AND (repeatid = 0 OR repeatid IS NULL) AND status <> '{$status}' AND cstreamid = {$cstreamid}";
            $cpassed = $this->get_records_select($select);
        }else
        {//значит передан - массив статусов';
            //добавим в строку запроса
            $status = implode("' AND status<>'", $status);
            $select = " AND status<> '$status'";
            $select = " studentid = {$studentid} AND (repeatid = 0 OR repeatid IS NULL) AND cstreamid = {$cstreamid}".$select;
            $cpassed = $this->get_records_select($select);
        }
        
        if ( ! $cpassed )
        {// такой записи нет';
            return false;
        }
        // возвращаем единственный элемент массива, даже если объектов несколько
        // (если таких объектов нашлось несколько - то это значит что уже нарушена связность таблицы, 
        // но в данном случае эта проблема не входит в обязанности этой функции)
        $cpassed = current($cpassed);
        // такая запись есть - вернем ее id
        return $cpassed->id;
    }
    
    /** Подписать одного ученика на один поток
     * 
     * @return int|bool - id записи если подписка произошла успешно (или уже существует), 
     *                    false в случае ошибки
     * @param int $cstreamid - id учебного потока в таблице cstreams
     * @param int $programmsbcid - id подписки студента на программу в таблице programmsbcs
     * @param int $sbcorderid - id приказа, которым пользователь был подписан на дисциплину
     */
    public function sign_student_on_cstream($cstreamid, $programmsbcid, $sbcorderid = null)
    {
        if ( ! is_int_string($cstreamid) OR ! is_int_string($programmsbcid)
                OR ( ! is_null($sbcorderid) AND ! is_int_string($sbcorderid) ) )
        {// не указаны необходимые параметры
            return false;
        }
        // собираем необходимые параметры для объекта
        if ( ! $programmsbc = $this->dof->storage('programmsbcs')->get($programmsbcid) )
        {// не найдена подписка с переданными параметрами
            return false;
        }
        if ( ! $contract = $this->dof->storage('contracts')->get($programmsbc->contractid) )
        {// не найден контракт, принадлежаший подписке
            return false;
        }
        if ( ! $cstream = $this->dof->storage('cstreams')->get($cstreamid) )
        {// такого предмето-потока нет в базе
            return false;
        }
        if ( $this->dof->storage('programmitems')->
             get_field($cstream->programmitemid,'agenum') != 0 AND
             $programmsbc->agenum != $this->dof->storage('programmitems')->
                                     get_field($cstream->programmitemid,'agenum') )
        {// если параллель подписки не совпадает с паралелью предмета
            // создавать подписку на дисциплину нельзя
            return true;
        }
        // создаем объект с необходимыми данными
        $cpassed = new Object();
        $cpassed->cstreamid      = $cstreamid;
        $cpassed->programmsbcid  = $programmsbcid; 
        $cpassed->programmitemid = $cstream->programmitemid;
        $cpassed->studentid      = $contract->studentid;
        //$cpassed->teacherid      = $cstream->teacherid;
        $cpassed->ageid          = $cstream->ageid;
        $cpassed->agroupid       = $programmsbc->agroupid;
        $cpassed->sbcorderid     = $sbcorderid;
      //  $cpassed->status         = 'plan';
        // остальные параметры не задаются, но возможно мы захотим изменить их 
        //в будущем при создании подписки
        $cpassed->grade       = null;
        $cpassed->gradelevel  = null;
        $cpassed->credit      = null;
        $cpassed->notice      = null;
        $cpassed->repeatid    = null;
        // @todo добавить механизм синхронизации оценок с moodle
        $cpassed->typesync    = 0;
        $cpassed->mdlinstance = null;
        
        if ( $oldcpid = $this->is_already_enroled($contract->studentid, $cstreamid, array('canceled')) )
        {// если ученик уже подписан на этот поток, то возвращается id старой подписки';
            return $oldcpid;
        }
        // если ученик еще не подписан - подпишем его, и вернем id новой записи
        return $this->insert($cpassed);
    }
    
    /** Подписать группу на один поток
     * @param int $agroupid - id группы
     * @param int $cstreamid - id потока
     * @return bool - true если все операции прошли успешно, и false в случае ошибки
     */
    public function sign_agroup_on_cstream($agroupid, $cstreamid, $programmsbc = null)
    {
        if ( ! is_int_string($cstreamid) OR ! is_int_string($agroupid) )
        {// не указаны необходимые параметры
            return false;
        }
        // найдем группу, чтоб узнать в какой паралели она учится
        if ( ! $agroup =  $this->dof->storage('agroups')->get($agroupid) )
        {// группа не найдена
            return false;
        }
        // найдем поток, чтоб достать id предмета
        if ( ! $cstream =  $this->dof->storage('cstreams')->get($cstreamid) )
        {// поток не найден
            return false;
        }
        // найдем предмет, чтоб достать id программы
        if ( ! $item = $this->dof->storage('programmitems')->get($cstream->programmitemid) )
        {// не найдена программа
            return false;
        }
        if ( ! is_null($programmsbc) )
        {// указана подписка - надо подписать конкретно ее';
            if ( $agroupid == $programmsbc->agroupid )
            {// только если ее группа совпадает с общей группой
                return $this->sign_student_on_cstream( $cstreamid, $programmsbc->id);
            }
            return true;
        }elseif ( ! $programmsbcs = $this->dof->storage('programmsbcs')->
                get_records(array('agroupid'=>$agroupid, 'programmid'=>$item->programmid, 'agenum'=>$agroup->agenum)) )
        {// найдем все подписки группы по предмету
            //если подписок нет - возвращаем true, и считаем что все нормально';
            return true;
        }
        $rez = true;
        foreach ( $programmsbcs as $sbc )
        {// подпишем каждого студкнта на поток';
            $rez = $rez AND $this->sign_student_on_cstream( $cstreamid, $sbc->id);
        }
        // всех успешно подписали
        return $rez;
        
    }
    
    /** Подписыает студента на все потоки программы по данному периоду 
     * @param int $programmsbcid - id подписку студента на программу
     * @param int $ageid - id текущего периода
     * @return bool true если ученик успешно подписан на потоки, или потоков в программе посто нет 
     *              false в остальных случаях
     */
    public function sign_student_on_all_cstreams($programmsbcid, $ageid = null)
    {
        if ( ! is_int_string($programmsbcid) )
        {//входные данные неверного формата
            return false;
        }elseif ( $ageid AND ! is_int_string($ageid) )
        {// необязательный параметр передан, но имеет неправильный формат
            return false;
        }
        // получим подписку на программу студента
        if ( ! $sbc = $this->dof->storage('programmsbcs')->get($programmsbcid) )
        {// переданой подписки не существует
            return false;
        }
        // найдем все потоки программы по данному периоду
        if ( ! $cstreams = $this->dof->storage('cstreams')->
                get_programm_age_cstreams($sbc->programmid,$ageid) )
        {// потоков нет - значит и подписывать никого не надо
            return true;
        }
        // переберем подписки
        foreach ($cstreams as $cstream)
        {// подпишем каждого
            if ( ! $this->sign_student_on_cstream( $cstream->id, $programmsbcid) )
            {//не удалось подписать, сообщим об ошибке
                return false;
            }
        }
        // успешно подписали на все потоки программы
        return true;
    }
    
    /** Подписывает все группы на потоки программы по данному периоду
     * @param int $programmid - id программы, на которую создаем подписки на предмет
     * @param int $ageid - id текущего периода
     * @return bool - true если все операции прошли успешно, и false в случае ошибки
     */
    public function sign_all_agroups_on_all_cstreams($programmid, $ageid)
    {
        if ( ! is_int_string($programmid) OR ! is_int_string($ageid) )
        {//входные данные неверного формата
            return false;
        }
        // получим все группы
        if ( ! $agroups = $this->dof->storage('agroups')->get_groups_programm($programmid, 'learn') )
        {// группы не найдены';
            return false;
        }
        foreach ( $agroups as $agroup )
        {// запишем каждую группу на потоки программы по данному периоду
            if ( ! $this->sign_agroup_on_all_cstreams($agroup->id, $programmid, $ageid) )
            {// что-то не удалось, сообщим об ошибке
                return false;
            }
        }
        // успешно всех подписали
        return true;
        
    }
    
    /** Подписывает одну академическую группу на все потоки указанной программы в переданом периоде
     * 
     * @return bool - true если все операции прошли успешно, и false в случае ошибки
     * @param int $agroupid - id учебной группы в таблице agroups
     * @param int $programmid - id учебной программы в таблице programms
     * @param int $ageid - id периода в таблице ages
     */
    public function sign_agroup_on_all_cstreams($agroupid, $programmid, $ageid)
    {
        if ( ! is_int_string($agroupid) OR ! is_int_string($programmid) OR ! is_int_string($ageid) )
        {// не указаны необходимые параметры
            return false;
        }
        // собираем необходимые параметры для запроса
        if ( ! $agroup = $this->dof->storage('agroups')->get($agroupid) )
        {// не найдена группа
            return false;
        }
        if ( ! $this->dof->storage('programms')->is_exists($programmid) )
        {// не найдена программа
            return false;
        }
        if ( ! $this->dof->storage('ages')->is_exists($ageid) )
        {// не найден период
            return false;
        }
        // получаем все подписки на программу для переданной программы и группы
        if ( ! $programmsbcs = $this->dof->storage('programmsbcs')->
                get_records(array('agroupid'=>$agroup->id, 'programmid'=>$programmid, 'agenum'=>$agroup->agenum)) )
        {//если подписок нет - возвращаем true, и считаем что все нормально';
            return true;
        }
        $result = true;
        // перебираем подписки учеников на программы  
        foreach ( $programmsbcs as $programmsbc )
        {// и каждого ученика подписываем на все потоки программы';
            $result = $result AND $this->sign_student_on_all_cstreams($programmsbc->id, $ageid);
        }
        return $result;
    }
    
    /** Отменить подписки учеников, у которых группа, указанная в подписке на программу не совпадает 
     * с группой, указанной в таблице связей учебных потоков с группами
     * @return bool true если все операции завершились успешно 
     *              false в случае возникновения ошибок
     * @param object $cstreamlink - объект, содержащий запись из таблицы cstreamlinks
     */
    public function unsign_students_without_real_agroup($cstreamlink) 
    {
        if ( ! is_object($cstreamlink) OR ! isset($cstreamlink->cstreamid) OR ! isset($cstreamlink->agroupid) )
        {// неправильный формат исходных данных;
            return false;
        }
        
        if ( ! $cpassed = $this->get_cstream_agroup($cstreamlink->cstreamid, $cstreamlink->agroupid) )
        {// не найдены подписки для такой группы и такого потока - значит и отписывать некого;
            return true;
        }
        $result = true;
        foreach ( $cpassed as $id=>$record )
        {// перебираем все подписки, и проверяем, совпадает ли ее группа с указанным в
            // cstreamlink значением 
            if ( ! $programmsbc = $this->dof->storage('programmsbcs')->get($record->programmsbcid) )
            {// не найдена подписка на программу
                // запомним, что произошла ошибка, и попробуем доделать проверку для остальных подписок
                $result = false;
                continue;
            }
            if ( $programmsbc->agroupid != $cstreamlink->agroupid )
            {// id групп не совпадают - удалим подписку пользователя
                $result = $result AND $this->dof->workflow('cpassed')->change($id, 'canceled');
            }
        }
        
        return $result;
    }

    /** Синхронизирует ученика с потоками при смене группы
     * @param int $oldagroupid - id старой группы студента
     * @param int $newagroupid - id новой группы студента
     * @param int $programmsbcid - id подписки на программу
     * @return bool true - если все прошло успешно или false в случае ошибки
     */
    private function change_group($oldagroupid, $newagroupid, $programmsbc)
    {
        //получаем текущий ageid
        // не выбираем потоки по периоду - записываем во все актуальные потоки
        //if ( ! $ageid = $this->dof->storage('ages')->
        //        get_next_ageid($programmsbc->agestartid, $programmsbc->agenum) )
        //{//не получили id текущего учебного периода';
        //    return false;
        //}
        $rez = true;
        if ( $oldagroupid )
        {//надо отписать от старой группы';
            $rez = $rez AND $this->syncronize_agroup_with_programm($oldagroupid, null, $programmsbc);
        }
        if ( ! is_null($newagroupid) AND $newagroupid )
        {//надо подписать на новую группу';
            $rez = $rez AND $this->syncronize_agroup_with_programm($newagroupid, null, $programmsbc);
        }
        return $rez;
    }
    
    /** Синхронизировать учебную группу с потоком, проверим и обновив все подписки
     * 
     * @return bool
     * @param int $cstreamlinkid - id записи в таблице cstreamlinks
     */
    private function syncronize_agroup_with_cstream($cstreamlinkid)
    {
        if ( ! is_int_string($cstreamlinkid) )
        {//входные данные неверного формата 
            return false;
        }
        if ( ! $cstreamlink = $this->dof->storage('cstreamlinks')->get($cstreamlinkid) )
        {// не найдена переданная связь - это ошибка';
            return false;
        }
        if ( $cstreamlink->agroupsync == 'nolink' )
        {// если связи нет - то ничего делать не надо, все нормально';
            return true;
        }
        // устанавливаем переменную для отслеживания ошибок при выполнении функций
        $result = true;
        // производим синхронизацию, отписывая учеников с несовпадающими значениями agroupid 
        $result = $result AND $this->unsign_agroups_from_cstream($cstreamlink->cstreamid);
        if ( $cstreamlink->agroupsync == 'norequired' )
        {// если тип связи - "неполная" - то на этом и закончим';
            return $result;
        }
        if ( $cstreamlink->agroupsync == 'full' )
        {// если тип связи - "Полная" - то создадим новые подписки на новую группу, взамен старых';
            $result = $result AND $this->sign_agroup_on_cstream
                ($cstreamlink->agroupid, $cstreamlink->cstreamid);
        }
        // возвращаем итоговый результат';
        return $result;
    }

    /** Синхронизировать все группы, связанные с данным потоком
     * @param int $cstreamid - id потока
     * @return bool 
     */
    public function syncronize_agroups_with_cstream($cstreamid)
    {
        if ( ! is_int_string($cstreamid) )
        {//входные данные неверного формата 
            return false;
        }
        // найдем все группы связанные с потоком
        if ( ! $cstreamslinks = $this->dof->storage('cstreamlinks')->get_cstream_cstreamlink($cstreamid) )
        {// связей нет
            return true;
        }
        foreach ( $cstreamslinks as $cstreamslink)
        {// синхронизируем каждую группу
            if ( ! $this->syncronize_agroup_with_cstream($cstreamslink->id) )
            {//что-то не получилось, сообщим об этом
                return false;
            }
        }
        // всех успешно синхронизировали
        return true;
    }
    
    /** Синхронизировать группу, со связанными с нею потоками
     * @param int $agroupid - id группы
     * @return bool 
     */
    public function syncronize_agroup_with_cstreams($agroupid)
    {
        if ( ! is_int_string($agroupid) )
        {//входные данные неверного формата 
            return false;
        }
        // найдем все группы связанные с потоком
        if ( ! $cstreamslinks = $this->dof->storage('cstreamlinks')->get_agroup_cstreamlink($agroupid) )
        {// связей нет
            return true;
        }
        foreach ( $cstreamslinks as $cstreamslink)
        {// синхронизируем каждую группу
            if ( ! $this->syncronize_agroup_with_cstream($cstreamslink->id) )
            {//что-то не получилось, сообщим об этом
                return false;
            }
        }
        // всех успешно синхронизировали
        return true;
    }

    /** Отписывает студентов всех остальных групп от изучения программы
     * @param int $agroupid - id программы
     * @return bool 
     */
    private function syncronize_agroup_with_programm($oldagroupid, $ageid = null, $programmsbc = null)
    {
        //получаем все потоки программы указанном периоде
        if ( ! $cstreamlist = $this->dof->storage('cstreams')->get_agroup_cstream($oldagroupid) )
        {//не получили потоки группы
            return false;
        }
        $rez = true;
        foreach ( $cstreamlist as $one )
        {//перебираем потоки
            if ( $one->status == 'plan' OR $one->status == 'active' OR $one->status == 'suspend' )
            {//нашли поток текущего периода
                //получаем связь группы с потоком
                $params = array();
                $params['agroupid'] = $oldagroupid;
                $params['cstreamid'] = $one->id;
                $cstreamlink = $this->dof->storage('cstreamlinks')->get_record($params);
                if ( ! $cstreamlink )
                {//не нашли связь
                    return false;
                }
                //синхронизируем группы и потоки
                $rez = $rez AND $this->syncronize_cpassed_with_cstream($cstreamlink->id, $oldagroupid, $programmsbc);
            }
        }
        return $rez;
    }
    
    /** Синхронизировать учебную группу с потоком, проверим и обновив все подписки
     * 
     * @return bool
     * @param int $cstreamlinkid - id записи в таблице cstreamlinks
     */
    private function syncronize_cpassed_with_cstream($cstreamlinkid, $agroupid, $programmsbc = null)
    {
        if ( ! is_int_string($cstreamlinkid) )
        {//входные данные неверного формата 
            return false;
        }
        if ( ! $cstreamlink = $this->dof->storage('cstreamlinks')->get($cstreamlinkid) )
        {// не найдена переданная связь - это ошибка';
            return false;
        }
        if ( $cstreamlink->agroupsync == 'nolink' )
        {// если связи нет - то ничего делать не надо, все нормально';
            return true;
        }
        // устанавливаем переменную для отслеживания ошибок при выполнении функций
        $result = true;
        // производим синхронизацию, отписывая учеников с несовпадающими значениями agroupid 
        $result = $result AND $this->unsign_cpassed_from_cstream($cstreamlink->cstreamid, $agroupid, $programmsbc);
        if ( $cstreamlink->agroupsync == 'norequired' )
        {// если тип связи - "неполная" - то на этом и закончим';
            return $result;
        }
        if ( $cstreamlink->agroupsync == 'full' )
        {// если тип связи - "Полная" - то создадим новые подписки на новую группу, взамен старых';
            $result = $result AND $this->sign_agroup_on_cstream
                ($cstreamlink->agroupid, $cstreamlink->cstreamid, $programmsbc);
        }
        // возвращаем итоговый результат';
        return $result;
    }
    
    /** Удаление всех "лишних" подписок из потока,
     * которые были записаны в составе группы, связи с которой сейчас нет
     */
    private function unsign_cpassed_from_cstream($cstreamid, $agroupid, $programmsbc = null )
     {
        //получаем cstream';
        if ( ! $cstream = $this->dof->storage('cstreams')->get($cstreamid) )
        {//не получили предмето-поток';
            return false;
        }
        //получаем все cpassed, привязанные к этому cstream
        if ( ! $cpassedlist = $this->get_records(array('cstreamid'=>$cstream->id)) )
        {//не получили список предмето потоков';
            return false;
        }
        $rez = true;
        foreach ($cpassedlist as $one)
        {
           //print_object($one);
           if ( ! is_null($one->agroupid) AND $one->agroupid == $agroupid )
           {//Прекращаем подписку';
               if ( ! is_null($programmsbc) )
               {// если указана подписка
                   // найдем id студента
                   $studentid = $this->dof->storage('contracts')->get_field($programmsbc->contractid,'studentid');
                   if ( $studentid != $one->studentid )
                   {// подписка не того студента - пропускаем
                       continue;
                   }
               }
               $rez = $rez AND $this->unsign_student_one_cpassed($one->studentid, $cstream->id);
           }

        }
        return $rez;
    }
    
    /** Выставить итоговую оценку, и в зависимости от нее перевести подписку в новый статус
     * 
     * @return bool true  - если все прошло успешно 
     *              false - в случае ошибки
     * @param int $cpassedid - id подписки на дисциплину
     * @param string $grade - выставляемая итоговая оценка, если не указана, 
     * то подписка автоматически переходит в статус неуспешно завершен
     * @param int $orderid - id приказа, на основании которого происходит выставление оценки
     */
    public function set_final_grade($cpassedid, $grade = null, $orderid = null)
    {
        if ( ! $cpassed = $this->get($cpassedid) )
        {//нет такой записи';
            return false;
        }
        if ( ! $this->dof->storage('programmitems')->is_exists($cpassed->programmitemid) )
        {// предмет, по которому выставляется оценка не существует';
            return false;
        }
        if ( $cpassed->status == 'canceled')
		{// подписка отменена -все хорошо';
			return true;
		}
		$obj = new object; 
        if ( $cpassed->status == 'plan' )
		{// если подписка запланирована, она может быть только отменена';
			// @todo или перезачтена, но мы пока такой вариант не рассматриваем
			$obj->orderid = $orderid;
            $this->update($obj, $cpassedid); 
			return $this->dof->workflow('cpassed')->change($cpassedid, 'canceled');
		}
        if ( is_null($grade) )
		{// если оценка не указана, подписка неуспешно завершена';
		    $obj->orderid = $orderid;
            $this->update($obj, $cpassedid); 
			return $this->dof->workflow('cpassed')->change($cpassedid, 'failed');
		}
        if ( ! $this->dof->storage('programmitems')->is_grade_valid($cpassed->programmitemid, $grade) )
        {// выставляемая оценка недопустима для такой дисциплины';
            return false;
        }
        if ( $cpassed->status == 'reoffset' OR $cpassed->status == 'completed' 
               OR $cpassed->status == 'failed' )
		{// подписка пересдана или завершена
			// меняем статус старой подписки
			if ( $cpassed->status != 'failed' ) 
			{// только если она уже не завершена неуспешно
				if (! $this->dof->workflow('cpassed')->change($cpassedid, 'failed') )
				{// статус не поменялся - создавать новую не имеет смысла'; 
					return false;
				}
			}
			// создаем новую подписку
			$new_cpass = $cpassed;
			// избавимся от ненужных данных
		    unset($new_cpass->grade);
		    unset($new_cpass->orderid);
		    unset($new_cpass->status);
		    $new_cpass->repeatid = $cpassedid;
		    // запомним id новой подписки и будем работать с ней
		    if ( ! $cpassedid = $this->insert($new_cpass) )
		    {// новая подписка не создалась';
		    	return false;
		    }
            // после создания подписки - сменим ее статус на "Active"
            // Это нужно сделать из-за того, что все изменения статусов происходят через workflow
            // и все подписки на дисциплины создаются в статусе "plan"
            // нельзя создать подписку сразу с каким-либо статусом, кроме "plan"
            $this->dof->workflow('cpassed')->change($cpassedid, 'active',array('orderid'=>$orderid));
		}
        $obj->grade = $grade;
        $obj->orderid = $orderid;
        //$obj->notice = $comment;
        if ( $this->update($obj, $cpassedid) ) 
        {// подписка успешно обновилась
	        if ( $this->dof->storage('programmitems')->is_positive_grade($cpassed->programmitemid, $grade) )
	        {// оценка положительная - обучение успешно завершено";
	            return $this->dof->workflow('cpassed')->change($cpassedid, 'completed');
	        }else
	        {// оценка неудовлетворительная';
	            return $this->dof->workflow('cpassed')->change($cpassedid, 'failed');
	        }
        }else
        {// ошибка обновления';
        	return false;
        }
    }
    
    /** Находит последнего наследника данной подписки
     * @param int $cpassedid - id подписки
     * @return int id последнего наследника подписки
     */
    public function get_last_successor($cpassedid)
    {
        if ( ! is_int_string($cpassedid) )
        {//входные данные неверного формата 
            return false;
        }
        if ( $successor = $this->get_record(array('repeatid'=>$cpassedid)) )
        {// если наследник есть, найдем его наследника
        	return $this->get_last_successor($successor->id);
        }else
        {// это последний наследник
        	return $cpassedid;
        }
    }
    
    /** Получает информацию о подписках на дисциплину 
     * по подписке на программу
     * @param int $id - id подписки на программу
     * @return array - массив с информацией о подписках на дисциплину
     * или false
     */
    public function get_cpassed_on_programmsbcid($id, $status = 'active')
    {
        if ( ! is_int_string($id) )
        {//входные данные неверного формата 
            return false;
        }
        $rez = array();
        //найдем подписки на дисциплину по подписке на программу
        if ( ! $cpassed = $this->get_records(array('programmsbcid'=>$id,'status'=>$status)) )
        {// ничего не нашли
            return false;
        }
        foreach ( $cpassed as $cpass )
        {// для каждой из подписок соберем информацию
            // данные для нахождения id учителя
            $appointmentid = $this->dof->storage('cstreams')->
                             get_field($cpass->cstreamid,'appointmentid');
            $eagreementid = $this->dof->storage('appointments')->
                            get_field($appointmentid,'eagreementid');
            // информация по подписке
            $cpassinfo = new object;
            $cpassinfo->itemname = $this->dof->storage('programmitems')->
                                      get_field($cpass->programmitemid,'name');
            if ( ! $cpassinfo->itemname )
            {
                return false;
            }
            $cpassinfo->itemid = $cpass->programmitemid;
            $cpassinfo->cstreamid = $cpass->cstreamid;
            $cpassinfo->teacherid = $this->dof->storage('eagreements')->
                                      get_field($eagreementid,'personid');
            if ( ! $cpassinfo->teacherid )
            {
                return false;
            }
            $cpassinfo->teachername = $this->dof->storage('persons')->
                                      get_fullname($cpassinfo->teacherid);
            if ( ! $cpassinfo->teachername )
            {
                return false;
            }
            $rez[$cpass->id] = $cpassinfo;
        }
        return $rez;
        
    }
    
    /** Функция принимает масив оценок(cpassed) и
     *  оставляет только итоговые/активные (без пересдач)
     *  переданный cpassed принадлежит одной программе, одной подписке, 
     *  одному человеку (все нужные проверки на уникальность уже проведены)
     * 
     * @param array $cpassed - массив оценок из таблицы cpassed
     * @return array - итоговые/текущие оценки без пересдач
     */
    public function get_norepeatid_cpassed($cpassed)
    {
        if ( ! is_array($cpassed) )
        {// неверный формат данных
            return false;
        }
        // узнаем насиройку
        $depid = optional_param('departmentid','0', PARAM_INT);
        $value = $this->dof->storage('config')->get_config_value('finalgrade', 'storage', 'cpassed', $depid);
        if ( ! $value )
        {// не надо группировать
            return $cpassed;
        }
        
        // создаем вспомогательный масив
        // массив с пересдачами(repetead)
        $repeats = array();
        foreach ( $cpassed as $id=>$obj )
        {
            if ( ! empty($obj->repeatid) )
            {// есть пересдача - запомним в массив
                $repeats[$id] = $obj->repeatid;
            }    
        }
        // перебор опять всех cpassed и есть в пересдаче - не учитываем ЕГО
        foreach ( $cpassed as $id=>$obj )
        { 
            if ( ! in_array($id, $repeats) )
            {// нет в массиве с пересдачей - итоговая значит
                $mas[$id] = $cpassed[$id]; 
            }
        }
        
        // 1 элемент - сразу выводим
        if ( count($mas) == 1 )
        {
            return $mas;
        }
        // избавимся от повтора 
        // (для случая, когда ученик в одном предмето классе завершил обучение
        // а в другом продолжил) - это 1 запись
        // для этого берем первый элемент и сравниваем его со всеми значениями
        if ( $value == '1' )
        {// группировать по программе
            foreach ( $mas as $key=>$value)
            {// тут только выбираем уже по времени
                foreach ( $mas as $key1=>$value1)
                {// тут только выбираем уже по времени
                    if ( $value->enddate <= $value1->enddate AND $value->programmitemid == $value1->programmitemid AND $value->id != $value1->id)
                    {
                        unset($mas[$key]);
                    }
                }    
            }            
        }elseif( $value == '2' )
        {// групировать по repeat(учитывать разные cstreams-потоки)
            foreach ( $mas as $key=>$value)
            {// тут только выбираем уже по времени
                foreach ( $mas as $key1=>$value1)
                {// тут только выбираем уже по времени
                    if ( $value->cstreamid == $value1->cstreamid AND$value->enddate <= $value1->enddate AND 
                            $value->programmitemid == $value1->programmitemid AND $value->id != $value1->id)
                    {
                        unset($mas[$key]);
                    }
                }    
            }              
        }

        return $mas;
    }

    /** Считывает поправочный зарплатный коэффициент для кол-ва студентов
     * @param int num - кол-во студентов для которых нужно вернуть зарплатный коэффициент
     * @return int|array
     */
    public function get_salfactor_count_students($num=null,$departmentid=0)
    {
        // получим зарплатный коэффициент из конфига
        $salfactors = $this->dof->storage('config')->get_config_value(
                'salfactor_count_students', 'storage', 'cpassed', $departmentid);
        // разбиваем его
        $salfactors = explode(';',$salfactors);
        $params = array();
        foreach ( $salfactors as $salfactor )
        {
            $count = explode('-',$salfactor);
            if ( is_int_string($num) )
            {// передано кол-во студентов
                if ( $count[0] == $num )
                {// нашли нужный номер - вернем зарплатный коэффициент
                    return $count[1];
                }
            }
            // заносим параметры в массив
            $params[$count[0]] = $count[1];
        }
        
        if ( is_int_string($num) )
        {// передано кол-во студентов, но номер не найден - найдем близкое значение
            if ( empty($params) )
            {// если массива по какой-то причине нет - вернем 1
                return 1;
            }
            // отсортируем ключи в обратном порядке
            krsort($params);
            $count = array_keys($params);
            foreach ( $count as $key )
            {
                $value = $params[$key];
                if ($key < $num) 
                {//значение меньше кол-ва учеников - нашли ближайший показатель
                    break;
                }
            }
            return $value;
        }
        return $params;
    }

    /** Находит общий поправочный зарплатный коэффициент для студентов потока
     * @param int cstreamid - id потока
     * @return int 
     */
    public function get_salfactor_programmsbcs($cstreamid,$full=false)
    {
        $salfactors = array();
        if ( ! $cpassed = $this->dof->storage('cpassed')->get_records(array
                          ('cstreamid'=>$cstreamid,'status'=>'active')) )
        {// подписок нет - коэффициент равен 0
            if ( $full )
            {
                $salfactors['all'] = 0;
                return $salfactors;
            }
            return 0;
        }
        $salfactor = 0;
        foreach ( $cpassed as $cpass )
        {// сложим коэффициенты каждого
            $sbcsalfactor = $this->dof->storage('programmsbcs')->get_field($cpass->programmsbcid, 'salfactor');
            $salfactor += $sbcsalfactor;
            if ( $full )
            {
                $salfactors[$cpass->programmsbcid] = $sbcsalfactor;
            }
        }
        if ( $full )
        {
            $salfactors['all'] = $salfactor;
            return $salfactors;
        }
        return $salfactor;
    }
    
    /**************************************************/
    /*************** Устаревшие методы ****************/
    /**************************************************/ 
    
    /** Получить список всех подписок студентов 
     * к указанному учебному потоку или 
     * только подписок с указанным статусом
     * @param int $csid - id учебного потока
     * @param string $status - статус учебной дисциплины
     * @return mixed array массив записей из таблица или bool false если записи не найдены
     */
    public function get_cstream_students($csid, $status = null)
    {
        dof_debugging('storage/cpassed get_cstream_students. Этот метод не используется. Используйте get_select_listing.', DEBUG_DEVELOPER);
        if ( ! is_int_string($csid) )
        {//входные данные неверного формата 
            return false;
        }
        if ( ! is_string($status) )
        {// вернем все подписки
            return $this->get_records(array('cstreamid'=>$csid));
        }else
        {// вернем только подписки с определенным статусом
            return $this->get_records_select('cstreamid = '.$csid.' AND status = \''.$status.'\'');
        }
    }
    
    /** Получить список всех подписок студентов 
     * к указанному учебному потоку или 
     * только подписок с указанным статусом
     * @param int $cstreamid - id учебного потока
     * @param int $agroupid - id академической группы
     * @param string $status - статус учебной дисциплины
     * @return mixed array массив записей из таблица или bool false если записи не найдены
     */
    public function get_cstream_agroup($cstreamid, $agroupid, $status = null)
    {
        dof_debugging('storage/cpassed get_cstream_agroup. Этот метод не используется. Используйте get_select_listing.', DEBUG_DEVELOPER);
        if ( ! is_int_string($cstreamid) )
        {//входные данные неверного формата 
            return false;
        }
        if ( ! is_int_string($agroupid) )
        {//входные данные неверного формата 
            return false;
        }
        $select = "cstreamid={$cstreamid} AND agroupid={$agroupid}";
        if ( ! is_null($status) )
        {// добавим статус в запрос
            $select .= " AND status='{$status}'";
        }
        return $this->get_records_select($select);
    }
    
    /** Получить список дисциплин для слушателя. 
     * @param int $stid - id студента
     * @param string $status - статус подписки, по умолчанию "идет"
     * @return mixed array список подписок на дисциплину или 
     * bool false, если дисциплины не найдены  
     */
    public function get_cpasseds_student($stid, $status = 'active')
    {
        dof_debugging('storage/get_cpasseds_student. Этот метод не используется. Используйте get_select_listing.', DEBUG_DEVELOPER);
        if ( ! is_int_string($stid) )
        {//входные данные неверного формата';
            return false;
        }
        if ( ! is_string($status) )
        {// вернем все подписки';
            return $this->get_records(array('studentid'=>$stid));
        }else
        {// вернем только подписки с определенным статусом
            return $this->get_records_select('studentid = '.$stid.' AND status = \''.$status.'\'');
        }
    }
    /** Получить список дисциплин, изученных слушателем в рамках учебной программы 
     * @param int $stid - id студента
     * @param int $prid - id подписки на программу
     * @param string $status - статус подписки, по умолчанию "завершен"
     * @return mixed array список подписок на дисциплину или bool false, если дисциплины не найдены  
     */
    public function get_cpassed_programm($stid, $prid, $status = 'complete')
    { 
        dof_debugging('storage/get_cpassed_programm. Этот метод не используется. Используйте get_select_listing.', DEBUG_DEVELOPER);
        if ( ! is_int_string($stid) OR ! is_int_string($prid) )
        {//входные данные неверного формата 
            return false;
        }
        $select = 'studentid = '.$stid.' AND programmsbcid = '.$prid;
        if ( is_string($status) )
        {//статус указан - включаем в запрос
            $select = $select.' AND status = \''.$status.'\'';
        }
        return $this->get_records_select($select);
    }
    /** Получить информацию о дисциплине, изученной слушателем (статусы "успешно завершен" и "перезачет) 
     * по id слушателя и id дисциплины 
     * @param int $stid - id студента
     * @param int $prid - id дисциплины
     * @param string $status - название статуса, в котором находится подписка
     * @param string $levelgrade - уровень оценки
     * @return mixed array - массив подписок удовлетворяющих запросу или 
     * bool false, если ничего не найдено
     */
    public function get_cpasseds_programmitem($stid, $prid, $status = 'complete', $levelgrade = null)
    {
        dof_debugging('storage/get_cpasseds_programmitem. Этот метод не используется. Используйте get_select_listing.', DEBUG_DEVELOPER);
        if ( ! is_int_string($stid) OR ! is_int_string($prid) )
        {//входные данные неверного формата 
            return false;
        }
        //формируем строку запроса
        $select = 'studentid = '.$stid.' AND programmitemid = '.$prid;
        if ( is_string($status) )
        {//добавляем в нее статус
            $select .= ' AND status=\''.$status.'\'';
        }
        if ( is_string($levelgrade))
        {//добавляем в запрос уровень оценки
            $select .= ' AND gradelevel=\''.$levelgrade.'\'';
        }
        return $this->get_records_select($select);
    }
    
    /**************************************************/
    /********* Функции обработки todo-заданий *********/
    /**************************************************/ 
    
    /** Находит последнего наследника данной подписки
     * @param int $cpassedid - id подписки
     * @return int id последнего наследника подписки
     */
    public function compare_cpassed_with_cstream()
    {
        dof_hugeprocess();
        $num = 0;
        //$select = '';
        $result = true;
        $select = " ( status IN ('plan','active','suspend') )";
        while ( $list = $this->get_records_select($select, null, '', '*', $num, 100) )
        {
            $num +=100;
            foreach ($list as $cpassed)
            {// добавим ко всем подразделениям путь и глубину
                $obj = new object;
                $obj->programmitemid = $this->dof->storage('cstreams')->get_field($cpassed->cstreamid,'programmitemid');
                $result = $result && $this->update($obj,$cpassed->id,true);
                $result = $result && $this->dof->storage('learninghistory')->add($cpassed);
            }             
        }
        return $result;
    }
    
    /** Приостановить, а затем опять запустить все cpassed (записи об изучаемых и пройденных курсах)
     * 
     */
    protected function todo_resync_age_cpassed($ageid,$personid)
    {
        
        // времени понадобится много
        dof_hugeprocess();
        
        $cpassedids = array();
        $num = 0;
        
        // сообщаем о том, что начинаем todo
        $this->dof->mtrace(2, '(storage/cpassed:todo)Resyncronizing cpassed for ageid='.$ageid);
        $this->dof->mtrace(2, 'Collecting ids...');
        $opt = array();
        $opt['personid'] = $personid;
        while ( $cpassed = $this->get_records_select(' ageid='.$ageid.' AND status="active" ',null, '', 'id', $num, 100) )
        {// собираем все записи об изучаемых или пройденных курсах, которые надо перезапустить
            $num += 100;
            foreach ( $cpassed as $id=>$cpobj )
            {
                $cpassedids[] = (int)$id;
            }
        }
        $this->dof->mtrace(2, 'Collected. Starting resync.');
        
        // собрали все id cpassed которые нужно приостановить, а потом запустить
        foreach ( $cpassedids as $id )
        {
            $this->dof->mtrace(2, 'Resyncing cpassedid='.$id); 
            // приостанавливаем и запускаем каждый cpassed по очереди
            // чтобы не скапливалось большое количество приостановленных cpassed
            if ( ! $this->dof->workflow($this->code())->change($id, 'suspend', $opt) )
            {
                $this->dof->mtrace(2, 'ERROR: cpassedid='.$id.' is not suspended');
            }
            if ( ! $this->dof->workflow($this->code())->change($id, 'active', $opt) )
            {
                $this->dof->mtrace(2, 'ERROR: cpassedid='.$id.' is not activated');
            }
        }
        
        $this->dof->mtrace(2, '(storage/cpassed:todo) DONE.');
        
        return true;
    }

    /** Разрывает связку отмененной подписки с пересдачамии
     * @return bool
     */
    public function todo_cancaled_active_cpassed()
    {
        $result = true;
        // времени понадобится много
        dof_hugeprocess();
        $select = " status = 'canceled' AND repeatid !=0 ";
        while ( $list = $this->get_records_select($select, null,'', 'id,repeatid') )
        {
            foreach ($list as $cpassed)
            {// обнуляем
                $obj = new object;
                $obj->repeatid = 0;
                $result = $result && $this->update($obj,$cpassed->id,true);
            }             
        }
        return $result;
    }


    /* Останавливает все активные cpassed
     *  @param $id - id периода
     */
    public function todo_active_to_suspend($ageid,$personid)
    {
        // времени понадобится много
        dof_hugeprocess();
        
        $cpassedids = array();
        $num = 0;
        // сообщаем о том, что начинаем todo
        $this->dof->mtrace(2, '(storage/cpassed:todo)Suspend all active cpassed for ageid='.$ageid);
        $this->dof->mtrace(2, 'Collecting ids...');
        $opt = array();
        $opt['personid'] = $personid;
        while ( $cpassed = $this->get_records_select(' ageid='.$ageid.' AND status="active" ', null,'', 'id', $num, 100) )
        {// собираем все записи об изучаемых или пройденных курсах, которые надо перезапустить
            $num += 100;
            foreach ( $cpassed as $id=>$cpobj )
            {
                $cpassedids[] = (int)$id;
            }
        }
        $this->dof->mtrace(2, 'Collected. Starting active.');
        
        // собрали все id cpassed которые нужно приостановить, а потом запустить
        foreach ( $cpassedids as $id )
        {
            $this->dof->mtrace(2, 'Suspend cpassedid='.$id); 
            // приостанавливаем и запускаем каждый cpassed по очереди
            // чтобы не скапливалось большое количество приостановленных cpassed
            if ( ! $this->dof->workflow($this->code())->change($id, 'suspend', $opt) )
            {
                $this->dof->mtrace(2, 'ERROR: cpassedid='.$id.' is not suspended');
            }
        }
        
        $this->dof->mtrace(2, '(storage/cpassed:todo) DONE.');
        
        return true;
    }
    
    /* Запускает все приостановленные cpassed
     *  @param $id - id периода
     */
    public function todo_suspend_to_active($ageid,$personid)
    {
        
        // времени понадобится много
        dof_hugeprocess();
        
        $cpassedids = array();
        $num = 0;
        // сообщаем о том, что начинаем todo
        $this->dof->mtrace(2, '(storage/cpassed:todo)Active all suspend cpassed for ageid='.$ageid);
        $this->dof->mtrace(2, 'Collecting ids...');
        $opt = array();
        $opt['personid'] = $personid;
        while ( $cpassed = $this->get_records_select(' ageid='.$ageid.' AND status="suspend" ', null,'', 'id', $num, 100) )
        {// собираем все записи об изучаемых или пройденных курсах, которые надо перезапустить
            $num += 100;
            foreach ( $cpassed as $id=>$cpobj )
            {
                $cpassedids[] = (int)$id;
            }
        }
        $this->dof->mtrace(2, 'Collected. Starting active.');
        
        // собрали все id cpassed которые нужно приостановить, а потом запустить
        foreach ( $cpassedids as $id )
        {
            $this->dof->mtrace(2, 'Active cpassedid='.$id); 
            // приостанавливаем и запускаем каждый cpassed по очереди
            // чтобы не скапливалось большое количество приостановленных cpassed
            if ( ! $this->dof->workflow($this->code())->change($id, 'active', $opt) )
            {
                $this->dof->mtrace(2, 'ERROR: cpassedid='.$id.' is not activated');
            }
        }
        
        $this->dof->mtrace(2, '(storage/cpassed:todo) DONE.');
        
        return true;
    }
    
    
}
?>