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



/** Журнал
 *
 */
class dof_im_journal implements dof_plugin_im
{
    /**
     * @var dof_control
     */
    protected $dof;
    /**
     * @todo удалить если нигде не используется
     */
    protected $otech;
    
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
        return 2013091700;
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
        return 'angelfish';
    }

    /**
     * Возвращает тип плагина
     * @return string
     * @access public
     */
    public function type()
    {
        return 'im';
    }
    /**
     * Возвращает короткое имя плагина
     * Оно должно быть уникально среди плагинов этого типа
     * @return string
     * @access public
     */
    public function code()
    {
        return 'journal';
    }
    /**
     * Возвращает список плагинов,
     * без которых этот плагин работать не может
     * @return array
     * @access public
     */
    public function need_plugins()
    {
        return array('modlib'=>array('nvg'=>2008060300,
                                     'ama'=>2009042900,
                                     'templater'=>2009031600,
                                     'widgets'=>2009050800),
                     'storage'=>array('persons'=>2009060400,
                                      'plans'=>2009060900,
                                      'cpgrades'=>2009060900,
                                      'schpresences'=>2009060800,
                                      'schevents'=>2009060800,
                                      'cstreams'=>2009060800,
                                      'cpassed'=>2009060800,
                                      'orders'=>2009052500,
                                      'departments'=>2009040800,
                                      'programms'=>2009040800,
                                      'programmitems'=>2009060800,
                                      'acl'=>2011040504) );
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
        return array(
                array('plugintype' => 'im',
                        'plugincode' => 'journal',
                        'eventcode'  => 'info'),
             
                array('plugintype' => 'im',
                        'plugincode' => 'my',
                        'eventcode'  => 'info'),
                
                array('plugintype' => 'im',
                        'plugincode' => 'persons',
                        'eventcode'  => 'persondata'));
    }
    /**
     * Требуется ли запуск cron в плагине
     * @return bool
     * @access public
     */
    public function is_cron()
    {
        // каждые 30 мин
        return 1800;
    }

    /**
     * Проверяет полномочия на совершение действий
     * @param string $do - идентификатор действия, которое должно быть совершено
     * @param int $id_obj - идентификатор экземпляра объекта,
     * по отношению к которому это действие должно быть применено
     * @param int $user_id - идентификатор пользователя, полномочия которого проверяются
     * @return bool true - можно выполнить указанное действие по отношению к выбранному объекту
     * false - доступ запрещен
     * @access public
     */
    public function is_access($do, $objid = NULL, $userid = NULL, $depid = NULL)
    {
        if ( $this->dof->is_access('datamanage') OR $this->dof->is_access('manage') 
             OR $this->dof->is_access('admin') )
        {//если глобальное право есть - пропускаем';
            return true;
        }
        // получаем id пользователя в persons
        $personid = $this->dof->storage('persons')->get_by_moodleid_id($userid);
        // получаем все нужные параметры для функции проверки прав
        $acldata = $this->get_access_parametrs($do, $objid, $personid, $depid);  
        switch ( $do )
        {// определяем дополнительные параметры в зависимости от запрашиваемого права
            // право на просмотр своего журнала предмето-потока              
            case 'view_journal/own':
                if ( ! $this->dof->storage('schevents')->is_exists(array('teacherid'=>$personid,'cstreamid'=>$objid)) 
                            AND $personid != $this->dof->storage('cstreams')->get_field($objid,'teacherid')) 
                {// персона не учитель потока и не заменяет ни один урок из потока      
                    return false;
                }
            break;
            // право на отметить проведение своего урока    
            case 'can_complete_lesson/own':        
                if ( ! $this->dof->storage('schevents')->is_exists(array('teacherid'=>$personid,'id'=>$objid)) )
                {// персона не ведет данный урок
                    return false;
                }
            case 'can_complete_lesson': 
                if ( $this->dof->storage('schevents')->get_field($objid,'status') != 'plan' )
                {// завершать можно только заплпнированные уроки
                    return false;
                }
            break; 
            //право выставить оценку в своем журнале
            case 'give_grade/in_own_journal':
                if ( $schevents = $this->dof->storage('schevents')->get_records
                       (array('planid'=>$objid,'status'=>array('plan','completed'))) )
                {//если для темы есть событие 
                    if ( $personid != current($schevents)->teacherid )
                    {// персона не ведет данный урок
                        return false;
                    }    
                }else
                {//тема - промежуточная оценка
                    $statusplan = $this->dof->storage('plans')->get_field($objid,'status');
                    if ( ($statusplan != 'active' AND $statusplan != 'checked' AND $statusplan != 'completed') ) 
                    {// статус темы не позволяет редактировать оценки';
                        return false;
                    }
                }
            break;    
            // право на отметку посещаемости своего урока
            case 'give_attendance/own_event':
                if ( $personid != $this->dof->storage('schevents')->get_field($objid,'teacherid') )
                {// только учитель урока
                    return false;
                }               
            break;
            // право указать тему для своего события
            case 'give_theme_event/own_event':
                if ( ! $event = $this->dof->storage('schevents')->get($objid) )
                {// нет урока - нечего и менять
                    return false;
                }
                if ( ($event->status != "plan" AND $event->status != "postponed") 
                     OR ($this->dof->storage('cstreams')->get_field($event->cstreamid,'status') != 'active'
                     AND $this->dof->storage('cstreams')->get_field($event->cstreamid,'status') != 'suspend')
                     OR $personid != $event->teacherid )
                {// только для учителя урока, если статус не "запланирован" или "отложено" и предмето-класс активен
                    return false;
                }
            break;
            // право заменять урок(учителя, дату, учителя потока)
            case 'replace_schevent:date_dis': 
                if ( ! $event = $this->dof->storage('schevents')->get($objid) )
                {// нет урока - нечего и менять
                    return false;
                }
                if ( $event->form != 'distantly' OR $event->status == 'completed' )
                {// проведен - запрет на редактирование
                    return false;
                } 
            break;
            case 'replace_schevent:date_int':  
                if ( ! $event = $this->dof->storage('schevents')->get($objid) )
                {// нет урока - нечего и менять
                    return false;
                }
                if ( $event->form != 'internal' OR $event->status == 'completed' )
                {// проведен - запрет на редактирование
                    return false;
                }
            break;
            case 'replace_schevent:teacher':  
                if ( ! $event = $this->dof->storage('schevents')->get($objid) )
                {// нет урока - нечего и менять
                    return false;
                }
                if ( $event->status == 'completed' )
                {// проведен - запрет на редактирование
                    return false;
                }
            break;  
            // право заменять свой дистанционный урок
            case 'replace_schevent:date_dis/own':   
                if ( ! $event = $this->dof->storage('schevents')->get($objid) )
                {// нет урока - нечего и менять
                   return false;
                }
                if ( $event->form != 'distantly' OR $event->status == 'completed' OR $personid != $event->teacherid )
                {// проведен - запрет на редактирование
                   return false;
                }
            break; 
            // право видеть свою нагрузку
            case 'view:salfactors/own':   
                if ( $personid != $objid )
                {// персона не та
                   return false;
                }
            break;   
        }
        // проверка
        return $this->acl_check_access_paramenrs($acldata);
    }
    /**
     * Требует наличия полномочия на совершение действий
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
        // Используем функционал из $DOFFICE
        //return $this->dof->require_access($do, NULL, $userid);
        if ( ! $this->is_access($do, $objid, $userid) )
        {
            $notice = "journal/{$do} (block/dof/im/journal: {$do})";
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
        $result = '';
        
        if ( $gentype == 'im' AND $gencode == 'journal' AND $eventcode == 'info' )
        {// распечатываем секции
            $path = $this->dof->plugin_path('im','journal','/cfg/main_events.php');
            $this->dof->modlib('nvg')->print_sections($path);
            return true;
        }
        
        if ( $gentype == 'im' AND $gencode == 'my' AND $eventcode == 'info' )
        {// отобразить секции, в которых информация из журнала
            global $PAGE,$DOF;
            require_once('show_events/lib.php');
            $sections = array();
            if ( $this->get_section('my_events') )
            {// если в секции "мои занятия" есть данные - выведем секцию
                $sections[] = array('im'=>'journal','name'=>'my_events','id'=>1, 'title'=>$this->dof->get_string('view_today_events_teacher','journal'));
            }
            if ( $this->get_section('my_load') )
            {// если в секции "моя нагрузка" есть данные - выведем секцию
                $sections[] = array('im'=>'journal','name'=>'my_load','id'=>1, 'title'=>$this->dof->get_string('view_teacher_load','journal'));
            }
            if ( $this->get_section('my_salfactors') )
            {// если в секции "Фактическая персональная нагрузка за месяц" есть данные - выведем секцию
                $sections[] = array('im'=>'journal','name'=>'my_salfactors','id'=>1, 'title'=>$this->dof->get_string('view_teacher_salfactors','journal'));
            }
            return $sections;
        }
        if ( $gentype == 'im' AND $gencode == 'persons' AND $eventcode == 'persondata' )
        {// отобразить ссылку на нагрузку за месяц
            $depid = optional_param('departmentid', 0, PARAM_INT);
            if ( $this->dof->storage('schevents')->is_access('view:salfactors',null,null,$depid) )
            {// проверка прав
                if ( $this->dof->storage('appointments')->get_appointment_by_persons($intvar) )
                {// id учителя - вернем ссылку на нагрузку за месяц
                    return $this->show_my_salfactors($intvar, true);
                }
            }
            return '';
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
        $result = true;
        if ( $loan == 3 )
        {// генерацию отчетов запускаем только в режиме
            $result = $result && $this->dof->storage('reports')->generate_reports($this->type(), $this->code());
        }
        return $result;
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
    /**
     * Конструктор
     * @param dof_control $dof - объект $DOF
     * @access public
     */
    public function __construct($dof)
    {
        $this->dof = $dof;

    }
    // **********************************************
    // Методы, предусмотренные интерфейсом im
    // **********************************************
    /**
    * Возвращает содержимое блока
    * @param string $name - название набора текстов для отображания
    * @param int $id - id текста в наборе
    * @return string - html-код названия блока
    */
    function get_block($name, $id = 1)
    {
        $depid = optional_param('departmentid', 0, PARAM_INT);
        $addvars = array();
        $addvars['departmentid'] = $depid;
        switch ($name)
        {//выбираем нужнуое содержание по названию
            case 'main':
                return '<a href="'.$this->dof->url_im('journal','',$addvars).'">'
                .$this->dof->get_string('title', 'journal').'</a>';
                break;
            case 'test':
                return $this->dof->get_string('thisis_test_block', 'journal', $id);
                break;
            default:
                {//соответствия не нашлось выведем и имя и id
                    $a = new object;
                    $a->name = $name;
                    $a->id = $id;
                    return $this->dof->get_string('thisis_block_number', 'journal', $a);
                }
        }
    }
    /** Возвращает содержимое секции
     * @param string $name - название набора текстов для отображания
     * @param int $id - id текста в наборе
     * @return string  - html-код названия секции
     */
    function get_section($name, $id = 1)
    {
        switch ($name)
        {//выбираем содержание
            case 'navigation': return $this->get_section_navigation($id); break;
            case 'my_events': return $this->show_my_events(); break;
            case 'my_load': return $this->show_my_load(); break;
            case 'unmarked_events': return $this->show_unmarked_events(); break;
            case 'my_salfactors': return $this->show_my_salfactors(); break;
            default:
                {//соответствия не нашлось выведем и имя и id
                    $a = new object;
                    $a->name = $name;
                    $a->id = $id;
                    return $this->dof->get_string('thisis_section_number', 'journal', $a);
                }
        }
    }
    /** Возвращает текст для отображения в блоке dof
     * @return string  - html-код для отображения
     */
    public function get_blocknotes($format='other')
    {
        return "<a href='{$this->dof->url_im('journal','/')}'>"
        .$this->dof->get_string('title','journal')."</a>";
    }
    
    // ***********************************************************
    //       Методы для работы с полномочиями и конфигурацией
    // ***********************************************************  
    
    /** Задаем права доступа для объектов этого хранилища
     * 
     * @return array
     */
    public function acldefault()
    {
        $a = array();
        // право видеть журнал
        $a['view_journal']                  = array('roles'=>array('manager','methodist'));
        // право видеть свой журнал
        $a['view_journal/own']              = array('roles'=>array('teacher'));
        // видеть список уроков
        $a['view_schevents']                = array('roles'=>array('manager','methodist','teacher'));
        // видеть информацию о персоне
        $a['view_person_info']              = array('roles'=>array('manager','methodist','teacher'));
        // завершать урок
        $a['can_complete_lesson']           = array('roles'=>array('manager'));
        // завершать свой урок
        $a['can_complete_lesson/own']       = array('roles'=>array('teacher'));
        // проверять как учителя ведут журнал (обычно право используется завучами)
        $a['control_journal']               = array('roles'=>array('manager'));
        // ставить оценку
        $a['give_grade']                    = array('roles'=>array('manager'));
        // ставить оценку в своем журнале
        $a['give_grade/in_own_journal']     = array('roles'=>array('teacher'));
        // отмечать посещаемость
        $a['give_attendance']               = array('roles'=>array('manager'));
        // отмечать посещаемость своего урока
        $a['give_attendance/own_event']     = array('roles'=>array('teacher'));
        // задавать тему для урока
        $a['give_theme_event']              = array('roles'=>array('manager'));
        // задавать тему для своего урока
        $a['give_theme_event/own_event']    = array('roles'=>array('teacher'));
        // заменять дистанционный урок
        $a['replace_schevent:date_dis']     = array('roles'=>array('manager'));
        // заменять свой дистанционный урок
        $a['replace_schevent:date_dis/own'] = array('roles'=>array('teacher'));
        // заменять очный урок
        $a['replace_schevent:date_int']     = array('roles'=>array('manager'));
        // заменять урок, меняя при этом учителя
        $a['replace_schevent:teacher']      = array('roles'=>array('manager'));
        // снять галочку н/о
        $a['remove_not_studied']            = array('roles'=>array('manager'));
        // скачать список уроков
        $a['export_events']                 = array('roles'=>array('manager'));
        // завершать cstream до истечения срока cstream
        $a['complete_cstream_before_enddate'] = array('roles'=>array('manager'));
        // завершать cstream после истечения срока cstream (пересдача)
        $a['complete_cstream_after_enddate']  = array('roles'=>array('manager','teacher'));
        // Закрывать итоговую ведомость до завершения cstream 
        // (под завершением имеется в виду cstream в конечном статусе)
        $a['close_journal_before_closing_cstream'] = array('roles'=>array('manager'));
        // Закрывать итоговую ведомость до истечения даты cstream
        $a['close_journal_before_cstream_enddate'] = array('roles'=>array('manager'));
        // Закрывать итоговую ведомость после истечения даты cstream, но до завершения cstream
        $a['close_journal_after_active_cstream_enddate'] = array('roles'=>array('manager','teacher'));
        // просмотр фактической нагрузки
        $a['view:salfactors'] = array('roles' =>array('manager'));
        // просмотр персональной фактической нагрузки
        $a['view:salfactors/own'] = array('roles' =>array('teacher','methodist'));
        // просмотр персональной фактической нагрузки дальше чем на месяц назад
        $a['view:salfactors_history'] = array('roles' =>array('manager','methodist'));
        
        return $a;
    }

    /** Получить список параметров для фунции has_hight()
     * 
     * @return object - список параметров для фунции has_hight()
     * @param string $action - совершаемое действие
     * @param int $objectid - id объекта над которым совершается действие
     * @param int $userid
     */
    protected function get_access_parametrs($action, $objectid, $userid)
    {
        $result = new object();
        $result->plugintype   = $this->type();
        $result->plugincode   = $this->code();
        $result->code         = $action;
        $result->userid       = $userid;
        $result->departmentid = optional_param('departmentid', 0, PARAM_INT);;
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
     * Возвращает контейнер с краткой информацией о группо-потоке
     * @param int $csid - id потока (cstream)
     * @return string
     */
    public function get_cstream_info($csid)
    {
        global $CFG;
        global $DOF;
        $depid = optional_param('departmentid', 0, PARAM_INT);
        $addvars = array();
        $addvars['departmentid'] = $depid;
        if ( ! $cstream = $this->dof->storage('cstreams')->get($csid) )
        {//не получили поток
            $progname = '';
            $coursename = '';
            $teacherfio = '';
        }else
        {
            //получаем имя преподавателя
            $teacherfio = $this->dof->storage('persons')->get_fullname($cstream->teacherid).
                       ' <a href="'.$this->dof->url_im('journal', '/show_events/show_events.php?personid='.$cstream->teacherid.
                       '&date_to='.time().'&date_from='.time(),$addvars).'">
                       <img src="'.$this->dof->url_im('journal', '/icons/events_student.png').'"
                       alt=  "'.$this->dof->get_string('view_events_teacher', 'journal').'" 
                       title="'.$this->dof->get_string('view_events_teacher', 'journal').'" /></a>';
                        $link = '';
            if ( $this->dof->storage('schtemplates')->is_access('view') )
            {// можно просматривать шаблон - добавим ссылку на просмотр шаблона на неделю
                $teacherfio .= '<a href="'.$this->dof->url_im('schedule', '/view_week.php?teacherid='.$cstream->teacherid.'&ageid='.$cstream->ageid,$addvars).
                        '"><img src="'.$this->dof->url_im('journal', '/icons/show_schedule_week.png').'"
                         alt=  "'.$this->dof->get_string('view_week_template_on_teacher', 'journal').'" 
                         title="'.$this->dof->get_string('view_week_template_on_teacher', 'journal').'" /></a>';
            }
            //получаем название предмета
            if ( ! $progitem = $DOF->storage('programmitems')->get($cstream->programmitemid) )
            {//не получили запись
                $coursename = '';
                $progname = '';
                $agenum = '';
            }else
            {//получаем имя курса и программы
                $coursename = $progitem->name.' ['.$progitem->code.']';
                if ( $this->dof->storage('programmitems')->is_access('view',$progitem->id) )
                {// ссылка на просмотр предмета
                    $coursename = '<a href='.$this->dof->url_im('programmitems','/view.php?pitemid='.$progitem->id,$addvars).'>'.
                                $coursename.'</a>';
                }
                //получаем название программы
                $progname = $DOF->storage('programms')->get_field($progitem->programmid, 'name').' ['.
                $DOF->storage('programms')->get_field($progitem->programmid, 'code').' ]';
                $agenum = $progitem->agenum;

            }
        }
        $rez = new object;
        $rez->data[] = array($this->dof->get_string('programm','journal'),$progname);
        $rez->data[] = array($this->dof->get_string('agenum','journal'),$agenum);
        $rez->data[] = array($this->dof->get_string('course','journal'),$coursename);
        $rez->data[] = array($this->dof->get_string('teacher','journal'),$teacherfio);
        // ссылка на предмето-класс
        $path = $DOF->url_im('cstreams','/view.php?cstreamid='.$cstream->id, $addvars);
        if ( $this->dof->storage('cstreams')->is_access('view',$cstream->id) )
        {// ссылка на просмотр предмето-класса
            $cstream->name = "<a href =$path>".$cstream->name."</a>";
        }
        $rez->data[] = array($this->dof->get_string('name','journal'),$cstream->name);
        // ссылка на курс в moodle
        $cname = '';
        if ( isset($progitem->mdlcourse) AND $this->dof->modlib('ama')->course(false)->is_course($progitem->mdlcourse) )
        {
            $course = $this->dof->modlib('ama')->course($progitem->mdlcourse)->get();
            $cname = "<a href = ".$CFG->wwwroot."/course/view.php?id=".$progitem->mdlcourse." >".$course->fullname."</a>";
        }
        $rez->data[] = array($this->dof->get_string('course_moodle','journal'), $cname);
        
        $rez->tablealign = 'left';
        $rez->width = '100%';
        return $this->dof->modlib('widgets')->print_table($rez, true);
    }

    /** Отображение секции навигации в зависимости от страницы
     * @param $code - код секции
     * @return string - html-код страницы
     */
    public function get_section_navigation($code)
    {
        $rez = '';
        $rez .= '<ul>';
        $depid = optional_param('departmentid', 0, PARAM_INT);
        $addvars = array();
        $addvars['departmentid'] = $depid;
        $viewform = optional_param('viewform', 0, PARAM_INT);
        switch ($code)
        {
            case 1:
                if ( $this->is_access('view_schevents') )
                {
                    $rez .= '<li><a href="'.$this->dof->url_im('journal','/show_events/show_events.php',$addvars).'">'.
                    $this->dof->get_string('show_events','journal').'</a></li>';
                    $personid = $this->dof->storage('persons')->get_by_moodleid_id();
                    if ( $this->is_access('view:salfactors') OR 
                         $this->is_access('view:salfactors/own',$personid))
                    {// ссылка на отчет по фактической нагрузке
                        $date = dof_userdate(time(), '%Y_%m');
                        $rez .= '<li><a href="'.$this->dof->url_im('journal','/load_personal/loadpersonal.php',
                        $addvars+array('personid'=>$personid,'date'=>$date)).'">'.
                        $this->dof->get_string('view_teacher_salfactors','journal').'</a></li>';
                    }
                    if ( $this->dof->storage('reports')->is_access('view_report_im_journal_loadteachers') )
                    {// ссылка на отчет по фактической нагрузке
                        $rez .= '<li><a href="'.$this->dof->url_im('reports','/list.php',
                        $addvars+array('plugintype'=>'im','plugincode'=>'journal','code'=>'loadteachers')).'">'.
                        $this->dof->get_string('report_actual_load','journal').'</a></li>';
                    }
                    if ( $this->dof->storage('reports')->is_access('view_report_im_journal_replacedevents') )
                    {// ссылка на отчет по заменам уроков
                        $rez .= '<li><a href="'.$this->dof->url_im('reports','/list.php',
                        $addvars+array('plugintype'=>'im','plugincode'=>'journal','code'=>'replacedevents')).'">'.
                        $this->dof->get_string('report_replacedevents','journal').'</a></li>';
                    }
                    //есть ли право добавлять события
                    if ( $this->dof->storage('schevents')->is_access('create') )
                    {   //ссылка на добавление события для нескольких учебных процессов.
                        $rez .= '<li><a href="'.$this->dof->url_im('journal','/mass_events/index.php',$addvars).'">'.
                        $this->dof->get_string('add_event_for_some_cstreams','journal').'</a></li>';
                    }
                }
                break;
            case 2:
                $rez .= '<li><a href="'.$this->dof->url_im('journal','/show_events/index.php',$addvars).'">'.
                $this->dof->modlib('ig')->igs('back').'</a></li>';
                if ( $viewform )
                {
                    $rez .= '<li><a href="'.$this->dof->url_im('journal','/show_events/show_events.php',$addvars).'">'.
                    $this->dof->get_string('search_events_back','journal').'</a></li>';
                }else 
                {
                    $rez .= '<li><a href="'.$this->dof->url_im('journal','/show_events/show_events.php?viewform=1',$addvars).'">'.
                    $this->dof->get_string('search_events','journal').'</a></li>';                    
                }    
                break;
        }
        $rez .= '</ul>';
        return $rez;
    }

    /** Отображение секции "Мои уроки за сегодня"
     * @return string - html-код страницы
     */
    public function show_my_events()
    {
        $personid = $this->dof->storage('persons')->get_by_moodleid_id();
        $rez = '';
        if ( $this->dof->storage('eagreements')->is_exists(array('personid'=>$personid)) )
        {// считаем, что персона учитель
            //подключаем методы получения списка журналов
            $d = new dof_im_journal_show_events($this->dof);
            //инициализируем начальную структуру
            $d->set_data(null, $personid);
            //получаем список журналов
            $rez = '<br>'.$d->get_table_events();
        }
        if ( $this->dof->storage('contracts')->is_exists(array('studentid'=>$personid)) )
        {// считаем, что персона студент
            //подключаем методы получения списка журналов
            $d = new dof_im_journal_show_events($this->dof);
            //инициализируем начальную структуру
            $d->set_data(null, null, $personid);
            //получаем список журналов
            $rez = '<br>'.$d->get_table_events();
        }
        
        return $rez;
    }
    /** Отображение секции "Моя нагрузка"
     * @return string - html-код страницы
     */
    public function show_my_load()
    {
        $personid = $this->dof->storage('persons')->get_by_moodleid_id();
        $rez = '';
        //подключаем методы получения списка журналов
        $d = new dof_im_journal_show_events($this->dof);
        //инициализируем начальную структуру
        $d->set_data(null, $personid);
        //получаем список журналов
        return '<br>'.$d->get_table_teaching_load();
    }
    
    /** Отображение секции "Мои не отмеченные занятия"
     * @return string - html-код страницы
     */
    public function show_unmarked_events()
    {
        $personid = $this->dof->storage('persons')->get_by_moodleid_id();
        $rez = '';
        //подключаем методы получения списка журналов
        $d = new dof_im_journal_show_events($this->dof);
        //инициализируем начальную структуру
        $d->set_data(null, $personid);
        //получаем список журналов
        return '<br>'.$d->get_table_unmarked_events();
    }
    
    /** Отображение секции "Фактическая персональная нагрузка за месяц"
     * @param int $personid
     * @param bool $linktitle
     * @return string - html-код секции
     */
    public function show_my_salfactors($personid=0, $linktitle=false)
    {
        // ссылка на отчет
        if ( ! $personid )
        {// пользователь не указан - берем текущего
            $personid = $this->dof->storage('persons')->get_by_moodleid_id();
            if ( ! $this->dof->storage('appointments')->get_appointment_by_persons($personid) )
            {// не учитель - выходим
                return '';
            }
        }
    
        // дата отчета
        $date = dof_userdate(time(), '%Y_%m');
        $params = array('personid' => $personid,
                'date' => $date,
                'departmentid' => optional_param('departmentid', 0, PARAM_INT));
    
        $title = $this->dof->get_string('view_teacher_salfactors_go_link', 'journal');
    
        if ( $linktitle )
        {// заголовок и будет ссылкой
            $title = $this->dof->get_string('view_teacher_salfactors', 'journal');
        }
    
        return "<div align='center'><br><a href='".$this->dof->url_im('journal',
                '/load_personal/loadpersonal.php', $params)."'>".$title."</a></div>";
    }
    

    /**
     * Возвращает объект приказа
     *
     * @param string $code
     * @param integer  $id
     * @return dof_storage_orders_baseorder
     */
    public function order($code, $id = NULL)
    {
        global $DOF;
        require_once($this->dof->plugin_path('im','journal','/order/grades.php'));
        require_once($this->dof->plugin_path('im','journal','/order/presences.php'));
        require_once($this->dof->plugin_path('im','journal','/order/itog_grades.php'));
        switch ($code)
        {
            case 'presence':
                $order = new dof_im_journal_order_presences($this->dof);
                if (!is_null($id))
                {
                    if (!$order->load($id))
                    {
                        // Не найден
                        return false;
                    }
                }
                // Возвращаем объект
                return $order;
                break;
            case 'set_grade':
                $order = new dof_im_journal_order_set_grade($this->dof);
                if (!is_null($id))
                {
                    if (!$order->load($id))
                    {
                        // Не найден
                        return false;
                    }
                }
                // Возвращаем объект
                return $order;
                break;
            case 'delete_grade':
                $order = new dof_im_journal_order_delete_grade($this->dof);
                if (!is_null($id))
                {
                    if (!$order->load($id))
                    {
                        // Не найден
                        return false;
                    }
                }
                // Возвращаем объект
                return $order;
                break;
            case 'set_itog_grade':
                $order = new dof_im_journal_order_set_itog_grade($this->dof);
                if (!is_null($id))
                {
                    if (!$order->load($id))
                    {
                        // Не найден
                        return false;
                    }
                }
                // Возвращаем объект
                return $order;
                break;
            default:
                // Ошибка
                return false;
                break;
        }
    }

    /**
     * Возвращает объект отчета
     *
     * @param string $code
     * @param integer  $id
     * @return dof_storage_orders_baseorder
     */
    public function report($code, $id = NULL)
    {
        return $this->dof->storage('reports')->report($this->type(), $this->code(), $code, $id);
    }

    
   /**
    * Возвращает вкладки на просмотр по времение/учителям/учинекам
    * @param string $id -идентификатор,определяет какая вкладка активна в данный момент
    * @param arrrya $addvars - массив параметров GET/POST 
    * @return смешанную строку 
    */
    public function print_tab($addvars, $id)
    {
        unset($addvars['display']);
        // соберем данные для вкладок
        $tabs = array();
        // операции
        $link = $this->dof->url_im($this->code(),'/show_events/show_events.php',$addvars);
        $text = $this->dof->get_string('display_mode:time', $this->code());
        $tabs[] = $this->dof->modlib('widgets')->create_tab('time', $link, $text, NULL, true); 
        // оборудование
        $link = $this->dof->url_im($this->code(),'/show_events/show_events.php?display=students',$addvars);
        $text = $this->dof->get_string('display_mode:students', $this->code());
        $tabs[] = $this->dof->modlib('widgets')->create_tab('students', $link, $text, NULL, true);
        // комплекты оборудования
        $link = $this->dof->url_im($this->code(),'/show_events/show_events.php?display=teachers',$addvars);
        $text = $this->dof->get_string('display_mode:teachers', $this->code());
        $tabs[] = $this->dof->modlib('widgets')->create_tab('teachers', $link, $text, NULL, true);        
        // готовим для вывода
        return $this->dof->modlib('widgets')->print_tabs($tabs, $id, NULL, NULL, true);
    }    
    
    /** Метод, который возаращает список для автозаполнения
     * 
     * @param string $querytype - тип завпроса(поу молчанию стандарт)
     * @param string $data - строка
     * @param integer $depid - id подразделения  
     * 
     * @return array or false - запись, если есть или false, если нет
     */
    public function widgets_field_variants_list($querytype, $depid, $data='')
    {
        // в зависимости от типа, проверяем те или иные права
        switch ($querytype)
        {
            case 'person_name' :        
            // есть права - то посылаем запрос
            if ( $this->is_access('view_schevents',NULL,NULL,$depid) )
            {
                return $this->dof->storage('persons')->result_of_autocomplete($querytype, $depid, $data);
            }
        }    
        
       // нет ничего
       return false;
        
    } 
    
    /***************************************************/
    /************ МЕТОДЫ ПРОВЕРКИ    *******************/
    /************ ПРАВ ДОСТУПА.      *******************/
    /************ МОЖНО ИСПОЛЬЗОВАТЬ *******************/
    /************ ТОЛЬКО В МЕТОДЕ    *******************/
    /************ $this->is_access() *******************/
    /***************************************************/

    /**
     * Вернуть массив с настройками или одну переменную
     * @param $key - переменная
     * @return mixed
     */
    public function get_cfg($key=null)
    {
        // Возвращает параметры конфигурации
        include ($this->dof->plugin_path($this->type(),$this->code(),'/cfg/cfg.php'));
        if (empty($key))
        {
            return $im_journal;
        }else
        {
            return @$im_journal[$key];
        }
    }

    /** Проверяет полномочия на перенос уроков
     * @param int $objid - id переносимого события
     * @param int $personid - id персоны, запрашивающей перенос уроков
     * @return bool true - все в порядке, ограничений нет или false
     */
    public function is_access_replace($objid, $userid = null, $roles = array())
    {
        global $USER;
        if ( is_null($userid) )
        {
            $userid = $USER->id;
        }
        $access = new object();
        $access->selectdate    = false; // право выбрать время урока
        $access->ignorform     = false; // игнорирование формы урока
        $access->ignorolddate  = false; // игнорирование старой даты урока
        $access->ignornewdate  = false; // игнорирование новой даты урока
        // TODO убрать тут все после OR после перехода на новые права поностью
        if ( in_array('manager', $roles) OR $this->dof->is_access('manage', $objid, $userid)  )
        {//особенным всегда можно
            $access->ignorform    = true;
            $access->selectdate   = true;
            $access->ignorolddate = true;
            $access->ignornewdate = true;
        }
        $event = $this->dof->storage('schevents')->get($objid);
        // TODO убрать послу OR как перейдем на новые права
        if ( $event->teacherid == $userid OR 
                $event->teacherid == $this->dof->storage('persons')->get_by_moodleid_id($userid) )
        {// указанная персона учитель
            if ( $event->form == 'distantly' )
            {// ему можно переносить только дистанционные уроки
                $access->selectdate = true;
            }
        }
        // @todo добавить проверки на замену урока
        // проверки не пройдены
        return $access;
    }
    
}