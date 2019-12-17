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


/** 
 * Класс стандартных функций интерфейса
 * 
 */
class dof_workflow_schevents implements dof_workflow
{
    /**
     * Хранит методы ядра деканата
     * @var dof_control
     */
    protected $dof;
    // **********************************************
    // Методы, предусмотренные интерфейсом plugin
    // **********************************************
    /** 
     * Метод, реализующий инсталяцию плагина в систему
     * Создает или модифицирует существующие таблицы в БД
     * и заполняет их начальными значениями
     * @return boolean
     * Может надо возвращать массив с названиями таблиц и результатами их создания?
     * чтобы потом можно было распечатать сообщения о результатах обновления
     * @access public
     */
    public function install()
    {
        return $this->dof->storage('acl')->save_roles($this->type(),$this->code(),$this->acldefault());
    }
    /** 
     * Метод, реализующий обновление плагина в системе
     * Создает или модифицирует существующие таблицы в БД
     * @param string $old_version - версия установленного в системе плагина
     * @return boolean
     * Может надо возвращать массив с названиями таблиц и результатами их создания/изменения?
     * чтобы потом можно было распечатать сообщения о результатах обновления
     * @access public
     */
    public function upgrade($oldversion)
    {
        return $this->dof->storage('acl')->save_roles($this->type(),$this->code(),$this->acldefault());
    }
    /** 
     * Возвращает версию установленного плагина
     * @return string
     * @access public
     */
    public function version()
    {
        return 2012021400;
    }
    /** 
     * Возвращает версии интерфейса Деканата, 
     * с которыми этот плагин может работать
     * @return string
     * @access public
     */
    public function compat_dof()
    {
        return 'aquarium';
    }

    /** 
     * Возвращает версии стандарта плагина этого типа, 
     * которым этот плагин соответствует
     * @return string
     * @access public
     */
    public function compat()
    {
        return 'guppy_a';
    }
    
    /** 
     * Возвращает тип плагина
     * @return string 
     * @access public
     */
    public function type()
    {
        return 'workflow';
    }
    /** 
     * Возвращает короткое имя плагина
     * Оно должно быть уникально среди плагинов этого типа
     * @return string
     * @access public
     */
    public function code()
    {
        return 'schevents';
    }
    /** 
     * Возвращает список плагинов, 
     * без которых этот плагин работать не может
     * @return array
     * @access public
     */
    public function need_plugins()
    {
        return array('storage'=>array('schevents' => 2009060800,
                                      'acl'       => 2011082200));
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
        return array('storage'=>array('acl'=>2011040504));
    }
    /** 
     * Список обрабатываемых плагином событий 
     * @return array - array(array('plugintype'=>..,'plugincode'=>..,'eventcode'=>..),...)
     * @access public
     */
    public function list_catch_events()
    {
        return array(array('plugintype'=>'storage','plugincode'=>'schevents','eventcode'=>'insert'));
    }
    /** 
     * Требуется ли запуск cron в плагине
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
    public function is_access($do, $objid = NULL, $userid = NULL, $depid = null)
    {
        if ( $this->dof->is_access('datamanage') OR $this->dof->is_access('admin') )
        {// манагеру можно все
            return true;
        }
        // получаем id пользователя в persons
        $personid = $this->dof->storage('persons')->get_by_moodleid_id($userid);
        // получаем все нужные параметры для функции проверки прав
        $acldata = $this->get_access_parametrs($do, $objid, $personid, $depid);  
        switch ( $do )
        {
                
        } 
        if ( $this->dof->is_access('manage') )
        {// манагеру можно, кроме отмечать статус отмененным
            return true;
        
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
    /** 
     * Обработать событие
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
		if ( $gentype==='storage' AND $gencode === 'schevents' AND $eventcode === 'insert' )
		{
			// Отлавливаем добавление нового объекта
			// Инициализируем плагин
			return $this->init($intvar);
		}
        return true;
    }
    /** 
     * Запустить обработку периодических процессов
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
    /** 
     * Обработать задание, отложенное ранее в связи с его длительностью
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
    // **********************************************
    // Методы, предусмотренные интерфейсом workflow
    // **********************************************
   	/** 
   	 * Возвращает код справочника, в котором хранятся отслеживаемые объекты
     * @return string
     * @access public
	 */
	public function get_storage()
	{
		return 'schevents';
	}
    /** 
     * Возвращает массив всех состояний,   
     * в которых может находиться экземпляр объекта,
     * обрабатываемый этим плагином
     * @return array
     * @access public
     */
    public function get_list()
    {
		return array('plan'=>$this->dof->get_string('status:plan',$this->get_storage(),NULL,'workflow'),
					 'completed'=>$this->dof->get_string('status:completed',$this->get_storage(),NULL,'workflow'),
		             'replaced'=>$this->dof->get_string('status:replaced',$this->get_storage(),NULL,'workflow'),
		             'canceled'=>$this->dof->get_string('status:canceled',$this->get_storage(),NULL,'workflow'),
		             'postponed'=>$this->dof->get_string('status:postponed',$this->get_storage(),NULL,'workflow'),
		             'implied'=>$this->dof->get_string('status:implied',$this->get_storage(),NULL,'workflow'));
    }
    /** 
     * Возвращает имя статуса
     * @param string status - название состояния
     * @return string
     * @access public
     */
    public function get_name($status)
    {
		$list = $this->get_list();
		if ( isset($list[$status]) )
		{
		    return $list[$status];
		}
		return '';
    }
    /** 
     * Возвращает массив состояний,
     * в которые может переходить объект 
     * из текущего состояния  
     * @param int id - id объекта
     * @return mixed array - массив возможных состояний или false
     * @access public
     */
    public function get_available($id)
    {
		// Получаем объект из ages
		if ( ! $obj = $this->dof->storage($this->get_storage())->get($id) )
		{
			// Объект не найден
			return false;
		}
		// Определяем возможные состояния в зависимости от текущего статуса
		switch ( $obj->status )
		{
			case 'plan':       // переход из статуса "запланирован"
				$statuses['completed'] = $this->get_name('completed');
				$statuses['canceled'] = $this->get_name('canceled');
				$statuses['replaced'] = $this->get_name('replaced');
				$statuses['postponed'] = $this->get_name('postponed');
				$statuses['implied'] = $this->get_name('implied');
            break;
            case 'postponed':       // переход из статуса "отложено"
				$statuses['replaced'] = $this->get_name('replaced');
				$statuses['canceled'] = $this->get_name('canceled');
            break;
            case 'canceled':  // переход из статуса "отменен"
                $statuses = array();
            break;
            case 'completed':  // переход из статуса "завершено"
                $statuses = array();
            break;
            case 'replaced':  // переход из статуса "заменено"
                $statuses = array();
            break;
            default: $statuses = array('plan'=>$this->get_name('plan'));
		}
        
		return $statuses;
		
    }
    /** 
     * Переводит экземпляр объекта с указанным id в переданное состояние
     * @param int id - id экземпляра объекта
     * @param string status - название состояния
     * @return boolean true - удалось перевести в указанное состояние, 
     * false - не удалось перевести в указанное состояние
     * @access public
     */
    public function change($id, $status,$opt=null)
    {
        if ( ! $event = $this->dof->storage($this->get_storage())->get($id) )
		{// Период не найден
			return false;
		}
		
		$astatus = $this->dof->storage('appointments')->get_field(
		        $event->appointmentid,'status');
		$personid = $this->dof->storage('persons')->get_by_moodleid()->id;
		$apersonid = $this->dof->storage('appointments')->get_person_by_appointment(
		        $event->appointmentid)->id;
		if ( $apersonid == $personid AND $astatus == 'patient' )
		{// персона на больничном не может менять статусы
		    return false;
		}
        if ( $this->dof->is_access('datamanage') )
        {// датаманагеру можно переводить в любой статус
            // под личную ответственность датаманагера
            $list = $this->get_list();
        }else
        {// только список доступных
            $list = $this->get_available($id);
        }
		if ( ! $list )
		{// Ошибка получения статуса для объекта';
			return false;
		}
		if ( ! isset($list[$status]) )
		{// Переход в данный статус из текущего невозможен';
			return false;
		}	
    	switch ($status)
		{
		    // отмечаем проведение урока
		    case 'completed':
		        if ( ! $this->limit_time($event->date) OR ! isset($event->teacherid) OR ! $event->teacherid )
		        {// если есть ограничения или нет учителя - провести занятие нельзя
		            return false;
		        }
            break;		
		}
		$this->dof->storage('statushistory')->change_status($this->get_storage(),intval($id), $status,$event->status,$opt);
		// Меняем статус';
		$obj = new object();
		$obj->id = intval($id);
		$obj->status = $status;
        $this->dof->storage($this->get_storage())->update($obj);
        //перерасчитываем коэффициенты
        $obj->salfactor      = $this->calculation_salfactor($id);
        $obj->salfactorparts = serialize($this->calculation_salfactor($id, true, true));
        $obj->rhours         = $this->calculation_salfactor($id,true);
		//$obj->statusdate = time();
		return $this->dof->storage($this->get_storage())->update($obj);
    }
    /** 
     * Инициализируем состояние объекта
     * @param int id - id экземпляра
     * @return boolean true - удалось инициализировать состояние объекта 
     * false - не удалось перевести в указанное состояние
     * @access public
     */
    public function init($id)
    {
	    // Получаем объект из справочника
		if (!$obj = $this->dof->storage($this->get_storage())->get($id))
		{// Объект не найден
			return false;
		}
		// Меняем статус
		$obj = new object();
		$obj->id = intval($id);
		$obj->status = 'plan';
        $this->dof->storage($this->get_storage())->update($obj);
        //перерасчитываем коэффициенты
        $obj->salfactor      = $this->calculation_salfactor($id);
		$obj->salfactorparts = serialize($this->calculation_salfactor($id, true, true));
		$obj->rhours         = $this->calculation_salfactor($id,true);
		return $this->dof->storage($this->get_storage())->update($obj);
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
        $result->objectid     = $objectid;
        $result->departmentid = $depid;
        if ( is_null($depid) )
        {// подразделение не задано - берем текущее
            $result->departmentid = optional_param('departmentid', 0, PARAM_INT);
        }
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
     *               'roles' => array('student' ,'...');
     */
    public function acldefault()
    {
        $a = array();
        
        $a['changestatus'] = array('roles'=>array('manager','teacher','methodist')); 
        $a['changestatus:to:canceled'] = array('roles'=>array('manager')); 
        
        return $a;
    }
    
    // **********************************************
    // Собственные методы
    // **********************************************
    /** 
     * Конструктор
     * @param dof_control $dof - это $DOF
     * объект с методами ядра деканата
     */
    public function __construct($dof)
    {
        $this->dof = $dof;
    }
    /**
     * Переводит объект с любым статусом в отмененный
     * @param int $id - id объекта, который надо отменить
     * @return bool - true, если статус сменили, 
     * false - в остальных случаях
     */
    public function cancel_any($id)
    {

        if ( ! $obj = $this->dof->storage($this->get_storage())->get($id) )
        {// Объект не найден
            return false;
        }
        //меняем статус на отмененный
        $obj = new object();
        $obj->id = intval($id);
        $obj->status = 'canceled';
        $this->dof->storage($this->get_storage())->update($obj);
        //перерасчитываем коэффициенты
        $obj->salfactor      = $this->calculation_salfactor($id);
        $obj->salfactorparts = serialize($this->calculation_salfactor($id, true, true));
        $obj->rhours         = $this->calculation_salfactor($id,true);
        return $this->dof->storage($this->get_storage())->update($obj);
    } 
    /** Ставит ограничения на отметку о проведении урока
     * @param int $date - дата проведения урока
     * @return bool true - все в порядке, ограничений нет или false
     */
    public function limit_time($date)
    {
        if ( $this->is_access('manage') )
        {//особенным всегда можно
            return true;
        }
        // проверим по времени
        if ( $date >= time() )
        {// время не наступило
            return false;
        }
        if ( $this->dof->storage('config')->get_config_value('time_limit', 
                'storage', 'schevents', optional_param('departmentid', 0, PARAM_INT)) )
        {// активирована настройка - проверим, укладывается ли отмена в отведенный лимит
            $edate     = dof_gmgetdate($date);
            $fixdate = mktime(0,0,0,$edate['mon'],26,$edate['year']);
            if ( time() > $fixdate AND $date < $fixdate )
            {// текущая дата более 25-ого текущего месяца
                return false;
            }
        }
        // проверки не пройдены
        return true;
    }

    /**
     * Возвращает id персоны того юзера,
     * чьи полномочия проверяются
     * @param int $userid - id проверяемого юзера
     * @return int 
     */
    private function store_userid($personid = null)
    {
        global $USER;
        //запоминаем проверяемого пользователя
        if ( is_null($personid) )
        {
            if ( isset($USER->id) )
            {
                $userid = $USER->id;
                $personid = $this->dof->storage('persons')->get_by_moodleid_id($userid);
            }else
            {
                $personid = 0;
            }
        }
        return intval($personid);
    }
    
    /** Вычисляет расчетный коэффициент для урока
     * @param int $schevents - урок или его id 
     * @param bool part - вернуть только значения без расчета
     * @return int|object|bool
     */
    public function calculation_salfactor($schevents,$ahours=false,$part=false)
    {
        if ( ! is_object($schevents) )
        {//если передан урок, а его id
            $schevents = $this->dof->storage('schevents')->get($schevents);
            if ( ! $schevents )
            {//не получили урок
                return false;
            }
        }
        $cstream = $this->dof->storage('cstreams')->get($schevents->cstreamid);
        $schtemplates_salfactor = 0;
        if ( $schevents->templateid )
        {// у урока имеется шаблон
            $schtemplates = $this->dof->storage('schtemplates')->get($schevents->templateid);
            $schtemplates_salfactor = $schtemplates->salfactor;
        }
        // формула из конфига
        $formula = $this->dof->storage('config')->get_config_value('salfactors_calculation_formula',
                'storage', 'schevents', $cstream->departmentid);
        // параметры
        $params = array();
        // для подразделения из конфига
        $params['config_salfactor_department'] = $this->dof->storage('config')->get_config_value(
                'salfactor_department', 'storage', 'schevents', $cstream->departmentid);
        // определяем кол-во студентов потока
        $num = $this->dof->storage('cpassed')->count_list(array(
               'cstreamid'=>$cstream->id, 
               'status'=>array('active','suspend')));
        // поправочный зарплатный коэффициент для кол-ва студентов из конфига
        $params['config_salfactor_countstudents'] = $this->dof->storage('cpassed')->
               get_salfactor_count_students($num,$cstream->departmentid);
        // замещающий зарплатный коэффициент потока
        $params['cstreams_substsalfactor'] = $cstream->substsalfactor;
        // поправочный зарплатный коэффициент потока
        $params['cstreams_salfactor'] = $cstream->salfactor;
        // замещающий зарплатный коэффициент потока
        $subsalfactor = round($cstream->substsalfactor,2);
        $params['absence_substsalfactor'] = 1;
        if ( !empty($subsalfactor) )
        {// замещающий зарплатный коэффициент потока
            $params['absence_substsalfactor'] = 0;
        }
        // поправочный зарплатный коэффициент предмета
        $params['programmitem_salfactor'] = $this->dof->storage('programmitems')->get_field($cstream->programmitemid, 'salfactor');
        // поправочный зарплатный коэффициент подписок
        $programmsbcs_salfactors = $this->dof->storage('cpassed')->get_salfactor_programmsbcs($cstream->id,true);
        $params['programmsbcs_salfactors'] = $programmsbcs_salfactors;
        $params['programmsbcs_salfactor'] = $programmsbcs_salfactors['all'];
        // поправочный зарплатный коэффициент групп
        $agroups_salfactors = $this->dof->storage('cstreamlinks')->get_salfactor_agroups($cstream->id,true);
        $params['agroups_salfactors'] = $agroups_salfactors;
        $params['agroups_salfactor'] = $agroups_salfactors['all'];
        // кол-во академических часов
        $params['ahours'] = $schevents->ahours;
        if ( ! $ahours )
        {// надо расчитать для одного часа
            $params['ahours'] = 1;
        }
        // поправочный зарплатный коэффициент шаблона
        $params['schtemplates_salfactor'] = $schtemplates_salfactor;
        // фактор проведения урока
        $params['schevents_completed'] = 0;
        if ( $schevents->status == 'completed' OR $schevents->status == 'implied' )
        {// урок был отмечен
            $params['schevents_completed'] = 1;
        }
        // фактор отметки урока вовремя @todo доработать через настройки
        $params['schevents_completed_on_time'] = 1;
        if ( $schevents->date + $schevents->duration > 3600 ) 
        {
            $params['schevents_completed_on_time'] = 1;
        }
        // кол-во активных учеников
        $params['count_active_cpassed'] = $this->dof->storage('cpassed')->count_list(array(
                'cstreamid'=>$cstream->id, 
                'status'=>array_keys($this->dof->workflow('cpassed')->get_meta_list('active'))));
        // кол-во приостановленных учеников
        $params['count_suspend_cpassed'] = $this->dof->storage('cpassed')->count_list(array(
                'cstreamid'=>$cstream->id, 
                'status'=>'suspend'));
        // общее кол-во учеников
        $params['count_all_cpassed'] = $num;
        
        // кол-во присутствовавших учеников
        $params['count_presented_cpassed'] = $this->dof->storage('schpresences')->count_list(array(
                'eventid' =>$schevents->id,
                'present' =>1));
        // кол-во отсутствовавших учеников
        $params['count_absented_cpassed'] = $this->dof->storage('schpresences')->count_list(array(
                'eventid' =>$schevents->id,
                'present' =>0));
        // перенос урока (да,нет)
        $params['schevent_replaced'] = 0;
        if ( isset($schevents->replacedid) )
        {
            $params['schevent_replaced'] = 1;
        }
        // @todo Урок имеет статус "ученики временно отсутствуют"? статуса нет, пока только запомним, что это тоже нужно.
        
        // групповой или индивидуальный урок
        $params['schevent_group'] = 0;
        $params['schevent_individual'] = 1;
        if ( $num > 1 )
        {
            $params['schevent_group'] = 1;
            $params['schevent_individual'] = 0;
        }
        
        if ( $part )
        {// следует вернуть значения без расчета по формуле
            $obj = new stdClass;
            $obj->vars = $params;
            $obj->formula = $formula;
            return $obj;
        }
        // расчитаем по формуле
        return $this->dof->modlib('calcformula')->calc_formula($formula,$params);      
    }
    
}
?>