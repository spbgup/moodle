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



/** Дневник/зачетная книжка
 * 
 */
class dof_im_recordbook implements dof_plugin_im
{
    /**
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
        return true;
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
        return true;
    }
    /** 
     * Возвращает версию установленного плагина
     * @return string
     * @access public
     */
    public function version()
    {
        return 2012052900;
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
        return 'recordbook';
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
                                     'templater'=>2009031600),
                     'storage'=>array('persons'=>2009060400,
                                      'plans'=>2009060900,
                                      'cpgrades'=>2009060900,
                                      'schpresences'=>2009060800,
                                      'schevents'=>2009060800,
                                      'cstreams'=>2009060800,
                                      'cpassed'=>2009060800,
                                      'departments'=>2009040800,
                                      'programms'=>2009040800,
                                      'contracts'=>2009052900,
                                      'programmsbcs'=>2009052900,
                                      'ages'=>2009050600,
                                      'programmitems'=>2009060800));
    }
    /** 
     * Список обрабатываемых плагином событий 
     * @return array - array(array('plugintype'=>..,'plugincode'=>..,'eventcode'=>..),...)
     * @access public
     */
    public function list_catch_events()
    {
       return array();       
    }
    /** 
     * Требуется ли запуск cron в плагине
     * @return bool
     * @access public
     */
    public function is_cron()
    {
		// Этому плагину не нужен крон
		return false;
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
    public function is_access($do, $objid = NULL, $userid = NULL)
    {
        
        if ( $this->dof->is_access('datamanage') OR $this->dof->is_access('manage') 
             OR $this->dof->is_access('admin') )
        {// манагеру можно все
            return true;
        }        
        // получаем id пользователя в persons
        $personid = $this->dof->storage('persons')->get_by_moodleid_id($userid);
        // получаем все нужные параметры для функции проверки прав
        $acldata = $this->get_access_parametrs($do, $objid, $personid);  
        switch ($do)
        {
            case 'view_recordbook':
                return $this->can_view_recordbook($objid, $personid);
            break;
            // запрошено неизвестное полномочие
            default: $acldata->code = $do;
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
        //return $this->require_access($do, $objid, $userid);
        if ( ! $this->is_access($do, $objid, $userid) )
        {
            $notice = "recordbook/{$do} (block/dof/im/recordbook: {$do})";
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
    public function get_block($name, $id = 1)
    {
        return $rez;
    }
    /** Возвращает содержимое секции
     * @param string $name - название набора текстов для отображания
     * @param int $id - id текста в наборе
     * @return string  - html-код названия секции
     */
    public function get_section($name, $id = 1)
    {
    	switch ($name)
		{//выбираем содержание
		    case 'test':
                return $this->dof->get_string('thisis_test_section', 'exampleim', $id);
            break;
            default:
                {//соответствия не нашлось выведем и имя и id
                    $a = new object;
                    $a->name = $name;
                    $a->id = $id;
                    return $this->dof->get_string('thisis_section_number', 'exampleim', $a);
                }
		}
    }
     /** Возвращает текст для отображения в блоке dof
     * @return string  - html-код для отображения
     */
    public function get_blocknotes($format='other')
    {
		return "<a href='{$this->dof->url_im('exampleim','/')}'>"
                    .$this->dof->get_string('title','exampleim')."</a>";
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
    
    /** Задаем права доступа для объектов этого хранилища
     * 
     * @return array
     */
    public function acldefault()
    {
        $a = array();
        return $a;
    }
    
    /** Проверить права через систему полномочий acl
     * 
     * @param string $do - идентификатор действия, которое должно быть совершено
     * @param int $id_obj - идентификатор экземпляра объекта, 
     * по отношению к которому это действие должно быть применено
     * @param int $userid - идентификатор пользователя в Moodle, полномочия которого проверяются
     * @return bool true - можно выполнить указанное действие по отношению к выбранному объекту
     * false - доступ запрещен
     */
    protected function acl_access_check($do, $objectid, $userid)
    {
        if ( ! $userid )
        {// получаем id пользователя в persons
            $userid = $this->dof->storage('persons')->get_by_moodleid_id($userid);
        }
        
        if ( ! $programmsbc = $this->dof->storage('programmsbcs')->get((int)$objectid) )
        {//не получили подписку на программу';
            return false;
        }
        $contractid = $programmsbc->contractid;
        if ( ! $contract = $this->dof->storage('contracts')->get((int)$contractid) )
        {//не получили контракт';
            return false;
        }
        
        // получаем все нужные параметры для функции проверки прав
        $acldata = $this->get_access_parametrs($do, $contractid, $userid);
        
              
        switch ($do)
        {
            case 'view_recordbook':
                $acldata->code       = 'view';
            break;
            // запрошено неизвестное полномочие
            default: $acldata->code = $do;
        }
        if ( $this->acl_check_access_paramenrs($acldata) )
        {// право есть заканчиваем обработку
            return true;
        }
        // нет права view, проверим другие права
        if ( $acldata->code == 'view' )
        {// если нет права view - то проверим права view/seller и view/parent
            if ( $acldata->objectid )
            {// если запрашивается право на просмотр конкретного договора - 
                // то проверим - является ли пользователь законным представителем или куратором 
            
                if ( $userid == $contract->clientid )
                {// пользователь является законным представителем 
                    $acldata->code = 'view/clientid';
                    if ( $this->acl_check_access_paramenrs($acldata) )
                    {// законным представителям разрешено просматривать договоры
                        return true;
                    }
                }
                if ( $userid == $contract->studentid )
                {// пользователь является куратором
                    $acldata->code = 'view/studentid';
                    if ( $this->acl_check_access_paramenrs($acldata) )
                    {// законным представителям разрешено просматривать договоры
                        return true;
                    }
                }
            }
        }
        
        // никаких дополнительных прав тоже нет
        return false;        
    }
    
    /**
     * Проверяет права на просмотр дневника
     * @param int $programmsbcid - id подписки на программу
     * @param int $userid - id проверяемого пользователя
     * @return bool
     */
    private function can_view_recordbook($programmsbcid, $userid = NULL)
    {
        //получим подписку на программу';
        $programmsbc = $this->dof->storage('programmsbcs')->get((int)$programmsbcid);
        if ( ! $programmsbc )
        {//не получили подписку на программу';
            return false;
        }
        //получим контракт
        $contract = $this->dof->storage('contracts')->get((int)$programmsbc->contractid);
        if ( ! $contract )
        {//не получили контракт';
            return false;
        }
        $fdouserid = $userid;
        if ( $fdouserid == $contract->studentid )
        {//это ученик и он хочет видеть свой дневник';
            return true;
        }
        if ( $fdouserid == $contract->clientid )
        {//это клиент и он хочет видеть дневник своего ученика';
            return true;
        }
        //это неизвестно кто';
        return false;
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

}
