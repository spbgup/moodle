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


/** Класс стандартных функций интерфейса
 * 
 */
class dof_workflow_contracts implements dof_workflow
{
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
        return $this->dof->storage('acl')->save_roles($this->type(),$this->code(),$this->acldefault());
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
        return $this->dof->storage('acl')->save_roles($this->type(),$this->code(),$this->acldefault());
    }
    /** Возвращает версию установленного плагина
     * @return string
     * @access public
     */
    public function version()
    {
        return 20011082200;
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
        return 'guppy_a';
    }
    
    /** Возвращает тип плагина
     * @return string 
     * @access public
     */
    public function type()
    {
        return 'workflow';
    }
    /** Возвращает короткое имя плагина
     * Оно должно быть уникально среди плагинов этого типа
     * @return string
     * @access public
     */
    public function code()
    {
        return 'contracts';
    }
    /** Возвращает список плагинов, 
     * без которых этот плагин работать не может
     * @return array
     * @access public
     */
    public function need_plugins()
    {
        return array('storage'=>array('contracts'     => 2008103100,
                                      'persons'       => 2008101600,
                                      'statushistory' => 2009060100,
                                      'acl'           => 2011082200),
        			 'sync'=>array('personstom'=>2009043000)
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
        return array('storage'=>array('acl'=>2011040504));
    }
    /** Список обрабатываемых плагином событий 
     * @return array - array(array('plugintype'=>..,'plugincode'=>..,'eventcode'=>..),...)
     * @access public
     */
    public function list_catch_events()
    {
        return array(array('plugintype'=>'storage','plugincode'=>'contracts','eventcode'=>'insert'));
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
		if ($gentype==='storage' AND $gencode === 'contracts' AND $eventcode === 'insert')
		{
			// Отлавливаем добавление нового объекта
			// Инициализируем плагин
			return $this->init($intvar);
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
        return true;
    }
    // **********************************************
    // Методы, предусмотренные интерфейсом workflow
    // **********************************************
   	/** Возвращает код справочника, в котором хранятся отслеживаемые объекты
     * @return string
     * @access public
	 */
	public function get_storage()
	{
		return 'contracts';
	}
    /** Возвращает массив всех состояний,   
     * в которых может находиться экземпляр объекта,
     * обрабатываемый этим плагином
     * @return array
     * @access public
     */
    public function get_list()
    {
		return array('tmp'=>$this->dof->get_string('status:tmp','contracts',NULL,'workflow'),
						'new'=>$this->dof->get_string('status:new','contracts',NULL,'workflow'),
						'clientsign'=>$this->dof->get_string('status:clientsign','contracts',NULL,'workflow'),
						'studreg'=>$this->dof->get_string('status:studreg','contracts',NULL,'workflow'),
						'wesign'=>$this->dof->get_string('status:wesign','contracts',NULL,'workflow'),
						'work'=>$this->dof->get_string('status:work','contracts',NULL,'workflow'),
						'frozen'=>$this->dof->get_string('status:frozen','contracts',NULL,'workflow'),
						'archives'=>$this->dof->get_string('status:archives','contracts',NULL,'workflow'),
						'cancel'=>$this->dof->get_string('status:cancel','contracts',NULL,'workflow'));
    }
    
    /** Возвращает массив метастатусов
     * @param string $type - тип списка метастатусов
     *               'active' - активный 
     *               'actual' - актуальный
     *               'real' - реальный
     *               'junk' - мусорный
     * @return array
     */
    public function get_meta_list($type)
    {
        switch ( $type )
        {
            case 'active':   
                return array('work'=>$this->dof->get_string('status:work',$this->code(),NULL,'workflow'));
            case 'actual':
                return array('new'=>$this->dof->get_string('status:new',$this->code(),NULL,'workflow'),
                             'clientsign'=>$this->dof->get_string('status:clientsign',$this->code(),NULL,'workflow'),
                             'studreg'=>$this->dof->get_string('status:studreg',$this->code(),NULL,'workflow'),
                             'wesign'=>$this->dof->get_string('status:wesign',$this->code(),NULL,'workflow'),
                             'work'=>$this->dof->get_string('status:work',$this->code(),NULL,'workflow'),
                             'frozen'=>$this->dof->get_string('status:frozen',$this->code(),NULL,'workflow'));
            case 'real':
                return array('new'=>$this->dof->get_string('status:new',$this->code(),NULL,'workflow'),
                             'clientsign'=>$this->dof->get_string('status:clientsign',$this->code(),NULL,'workflow'),
                             'studreg'=>$this->dof->get_string('status:studreg',$this->code(),NULL,'workflow'),
                             'wesign'=>$this->dof->get_string('status:wesign',$this->code(),NULL,'workflow'),
                             'work'=>$this->dof->get_string('status:work',$this->code(),NULL,'workflow'),
                             'frozen'=>$this->dof->get_string('status:frozen',$this->code(),NULL,'workflow'),
                             'archives'=>$this->dof->get_string('status:archives',$this->code(),NULL,'workflow')); 
            case 'junk':                
                return array('tmp'=>$this->dof->get_string('status:tmp',$this->code(),NULL,'workflow'),
                             'cancel'=>$this->dof->get_string('status:cancel',$this->code(),NULL,'workflow'));
            default:
                dof_debugging('workflow/'.$this->code().' get_meta_list.This type of metastatus does not exist', DEBUG_DEVELOPER);
                return array();
        }
    }
    
    /**
     * Получить список статусов, находясь в которых контракты считаются неактуальными
     * @return array
     */
    public function get_list_unactual()
    {
    	$all = $this->get_list();
        $unactual = $this->get_list_actual();
        return array_diff_key($all, $unactual); // Удаляем неактивные элементы
    }
    /**
     * Получить список статусов, находясь в которых контракты считаются актуальными (все, кроме tmp и cancel)
     * @return array
     */
    public function get_list_actual()
    {
    	$all = $this->get_list();
    	// Удаляем неактивные элементы
    	// tmp считается неактуальным, поскольку это черновик, который еще не подтвержден создателем
        unset($all['tmp']);
        unset($all['cancel']);
        return $all;
    }
    
    /**
     * Получить список статусов, актуальных для выбора периодов обучения 
     * (все, кроме tmp, new, clientsign, studreg, wesign)
     * @return array
     */
    public function get_list_actual_age()
    {
    	$all = $this->get_list();
    	// Удаляем неактивные элементы
        unset($all['tmp']);
        unset($all['new']);
        unset($all['clientsign']);
        unset($all['studreg']);
        unset($all['wesign']);
        return $all;
    }
    
    /** Возвращает имя статуса
     * @param string status - название состояния
     * @return string
     * @access public
     */
    public function get_name($status)
    {
		$list = $this->get_list();
		return $list[$status];
    }
    /** Возвращает массив состояний,
     * в которые может переходить объект 
     * из текущего состояния  
     * @param int id - id объекта
     * @return mixed array - массив возможных состояний или false
     * @access public
     */
    public function get_available($id)
    {
		// Получаем объект из examplest
		if (!$obj = $this->dof->storage($this->get_storage())->get($id))
		{
			// Объект не найден
			return false;
		}
		$list = $this->get_list();
		// Определяем возможные состояния в зависимости от текущего статуса
		switch ($obj->status)
		{
			// Неподтвержденный
			case 'tmp':
				return array('new'=>$this->get_name('new'),'cancel'=>$this->get_name('cancel'));
			break;
			// Новый
			case 'new':
				return array('clientsign'=>$this->get_name('clientsign'),'cancel'=>$this->get_name('cancel'));
			break;
			// Подписан клиентом
			case 'clientsign':
				return array('studreg'=>$this->get_name('studreg'),'cancel'=>$this->get_name('cancel'));
			break;
			// Студент зарегистрирован
			case 'studreg':
				return array('wesign'=>$this->get_name('wesign'),'cancel'=>$this->get_name('cancel'));
			break;
			// Подписан с нашей стороны
			case 'wesign':
				return array('work'=>$this->get_name('work'),'frozen'=>$this->get_name('frozen'),'archives'=>$this->get_name('archives'));
			break;
			// В работе
			case 'work':
				return array('frozen'=>$this->get_name('frozen'),'archives'=>$this->get_name('archives'));
			break;
			// Приостановлен
			case 'frozen':
				return array('work'=>$this->get_name('work'),'archives'=>$this->get_name('archives'));
			break;
			// Расторжен и переведен в архив
			case 'archives':
				return array();
			break;
			// Отменен
			case 'cancel':
				return array();
			break;
			default:
				return array('new'=>$this->get_name('new'));
			break;
		}
    }
    /** Переводит экземпляр объекта с указанным id в переданное состояние
     * @param int id - id экземпляра объекта
     * @param string status - название состояния
     * @return boolean true - удалось перевести в указанное состояние, 
     * false - не удалось перевести в указанное состояние
     * @access public
     */
    public function change($id, $status,$opt=null)
    {
    	if (!$obj = $this->dof->storage($this->get_storage())->get($id))
		{
			// Объект не найден
			return false;
		}
		if (!$list = $this->get_available($id))
		{
			// Ошибка получения статуса для объекта';
			return false;
		}
		if (!isset($list[$status]))
		{
			// Переход в данный статус из текущего невозможен';
			return false;
		}
        // создаем объект и записываем в него старый и новый статус.
        $statusobj = new Object();
        $statusobj->old = $obj->status;
        $statusobj->new = $status;
		// Дополнительные действия, в зависимости от статуса, в который мы переходим
		switch ($status)
		{
		    case 'new':
		        if ( empty($obj->studentid) OR empty($obj->clientid) )
		        {// если ученик или клиент не заполнен - сменять статус нельзя
		            return false;
		        }
		    break;
			case 'clientsign':
				// Получаем информацию об ученике
				$student = $this->dof->storage('persons')->get($obj->studentid);
				// $student->sync2moodle = 1;
				// Синхронизируем пользователя и проверяем результат
				if ( isset($student->mdluser) AND $student->sync2moodle == 1)
				{// студент уже зарегестрирован
				    $status = 'studreg';
				}elseif ($this->dof->sync('personstom')->sync($student,false,true))
				{
					// Устанавливаем статус
					$status = 'studreg';
					// Устанавливаем флаг синхронизации с moodle без создания "события"
					// $student2 = new object();
					// $student2->sync2moodle = 1;
					// $this->dof->storage('persons')->update($student2,$obj->studentid,true);
				}else
				{
					// Не смогли создать ученика
					// возможно договор неправильно заполнен
					$status = 'tmp';
				}
			break;
			case 'studreg':
				// Нельзя установить этот статус вручную
				return false;
			break;
			case 'work':
			    $rez = true;
			    $obj2 = new object();
        		$obj2->id = intval($id);
        		$obj2->status = $status;
        		if ( $this->dof->storage($this->get_storage())->update($obj2) )
        		{// если статус сменился
                    if ( $sbcs = $this->dof->storage('programmsbcs')->get_records(array('contractid'=>$obj->id,'status'=>'application')) )
                    {// если у контракта есть подписки на программы
                        foreach ( $sbcs as $sbc)
                        {// сменим им статус на запланированы
                            if ( $statusobj->old != 'frozen' )
                            {// если из frozen в work то application оставляем в application
                                $rez = $rez & $this->dof->workflow('programmsbcs')->change($sbc->id,'plan');
                            }    
                            if ( isset($sbc->agestartid) AND isset($sbc->agenum) AND 
                                   ((isset($sbc->agroupid) AND $sbc->edutype == 'group') OR $sbc->edutype == 'individual') )
                            {// если у подписки указаны стартовый периоди параллель,
                                // а у групповых подписок группа, то сменим статус на активный
                                $rez = $rez & $this->dof->workflow('programmsbcs')->change($sbc->id, 'active');
                            }
                        }
                    }
        		}else
        		{// если нет, то плохо
        		    return false;
        		}
        		if ( ! $rez )
        		{// если что-то пошло не так, откатим изменения назад
        		    $this->dof->storage($this->get_storage())->update($obj);
        		}
                $this->dof->send_event('workflow','contracts','changestatus',$id,$statusobj);
        		return $rez;
			break;
			case 'archives':
			   	// Помечаем ученика как несинхронизируемого с moodle
                $student2 = $this->dof->storage('persons')->get($obj->studentid);
                // Если не передано опций, или в опциях не просят оставить пользователя
                if (is_array($opt) and (!isset($opt['muserkeep']) OR !$opt['muserkeep']) and $obj->studentid)
                {// Удаляем ученика из Moodle, если он не встречается в других активных договорах
                    if (!$this->dof->storage('contracts')->is_person_used($obj->studentid,$obj->id))
                    {// Рассинхронизируем персону и пользователя
                        $this->dof->sync('personstom')->unsync($student2,false);
                        $obj3 = new object;
                        $obj3->id = $obj->studentid; 
                        $obj3->status = 'deleted';
                        $this->dof->storage('persons')->update($obj3);
                    }
                }
                // переводим подписки на программы в статус неуспешно завершенная (failed)
                // ищем все подписки на данный контракт
                if ( $listsb = $this->dof->storage('programmsbcs')->get_programmsbcs_by_contractid_ids($id) )
                {// в каждой подписке переводим статус в ОТМЕНЕН
                    foreach ($listsb as $idsb)
                    {
                        $statussbsc = $this->dof->storage('programmsbcs')->get($idsb)->status;
                        if ( $statussbsc == 'plan' OR $statussbsc == 'application' )
                        {// статус plan и application в canceled
                            $this->dof->workflow('programmsbcs')-> change($idsb, 'canceled');
                        }elseif ( $statussbsc == 'active' OR $statussbsc == 'suspend' 
                                  OR $statussbsc == 'condactive' OR $statussbsc == 'onleave' )
                        {// статусы active и suspend в failed
                            $this->dof->workflow('programmsbcs')-> change($idsb, 'failed');
                        }
                    }
                }				
			break;	
			case 'cancel':
				// Помечаем ученика как несинхронизируемого с moodle
				$student2 = $this->dof->storage('persons')->get($obj->studentid);
				// $student = new object();
				// $student->sync2moodle = 0;
				// $this->dof->storage('persons')->update($student,$obj->studentid);
				// Если не передано опций, или в опциях не просят оставить пользователя
				if (is_array($opt) and (!isset($opt['muserkeep']) OR !$opt['muserkeep']) and $obj->studentid)
				{
					// Удаляем ученика из Moodle, если он не встречается в других активных договорах
					if (!$this->dof->storage('contracts')->is_person_used($obj->studentid,$obj->id))
					{
					    // Рассинхронизируем персону и пользователя
						$this->dof->sync('personstom')->unsync($student2,false);
                        $obj3 = new object;
                        $obj3->id = $obj->studentid; 
                        $obj3->status = 'deleted';
                        $this->dof->storage('persons')->update($obj3);
					}
				}
                // переводим подписки на программы в статус отменен
                // ищем все подписки на данный контракт
                if ( $listsb = $this->dof->storage('programmsbcs')->get_programmsbcs_by_contractid_ids($id) )
                {// в каждой подписке переводим статус в ОТМЕНЕН
                    foreach ($listsb as $idsb)
                    {
                        $this->dof->workflow('programmsbcs')-> change($idsb, 'canceled');
                    }
                }				
			break;			
		}
		
		// Протоколируем
		$this->dof->storage('statushistory')->change_status($this->get_storage(),intval($id), $status,$obj->status,$opt);
		// Меняем статус';
		$obj2 = new object();
		$obj2->id = intval($id);
		$obj2->status = $status;
		//$obj2->statusdate = time();
        if ( ! $this->dof->storage($this->get_storage())->update($obj2) )
        {// не удалось обновит запись в БД
            return false;
        }
        // посылаем событие о смене статуса
        return $this->dof->send_event('workflow','contracts','changestatus',$id,$statusobj);
    }
    /** Инициализируем состояние объекта
     * @param int id - id экземпляра
     * @return boolean true - удалось инициализировать состояние объекта 
     * false - не удалось перевести в указанное состояние
     * @access public
     */
    public function init($id)
    {
	    // Получаем объект из contracts
		if (!$obj = $this->dof->storage($this->get_storage())->get($id))
		{
			// Объект не найден
			return false;
		}
		// Меняем статус
		$obj = new object();
		$obj->id = intval($id);
		$obj->status = 'tmp';
		//$obj->statusdate = time();
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
     *  a[] = array( 'code'  => 'код полномочия',
     *               'roles' => array('student' ,'...');
     */
    public function acldefault()
    {
        $a = array();
        
        $a['changestatus'] = array('roles'=>array('manager'));
        
        return $a;
    }
    
    // **********************************************
    // Собственные методы
    // **********************************************
    /** Конструктор
     */
    public function __construct($dof)
    {
        $this->dof = $dof;
    }
}
?>