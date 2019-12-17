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

/** Договоры с учениками
 * 
 */
class dof_storage_contracts extends dof_storage implements dof_storage_config_interface
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
        
        $result = true;
        $table = new xmldb_table($this->tablename());
        if ($oldversion < 2012110300)
        {
            // добавляем поле метаконтракта
            $field = new xmldb_field('metacontractid',XMLDB_TYPE_INTEGER, '7', 
                     null, null, null, null, 'enddate'); 
            if ( !$dbman->field_exists($table, $field) )
            {// поле еще не установлено
                $dbman->add_field($table, $field);
                               
            }
            $index = new xmldb_index('imetacontractid', XMLDB_INDEX_NOTUNIQUE, 
                     array('metacontractid'));
            // добавляем индекс для поля
            if ( !$dbman->index_exists($table, $index) ) 
            {// индекс еще не установлен
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
        return 2012120100;
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
        return 'contracts';
    }
    /** Возвращает список плагинов, 
     * без которых этот плагин работать не может
     * @return array
     * @access public
     */
    public function need_plugins()
    {
        return array('storage'=>array('departments'   => 2009040800,
                                      'persons'       => 2008101600,
                                      'config'        => 2011080900,
                                      'acl'           => 2011040504,
                                      'metacontracts' => 2012101500));
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
        return 'block_dof_s_contracts';
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
     *  a[] = array( 'code'  => 'код полномочия',
     *               'roles' => array('student' ,'...');
     */
    public function acldefault()
    {
        $a = array();
        
        $a['view']     = array('roles'=>array('manager','methodist'));
        $a['edit']     = array('roles'=>array('manager'));
        $a['create']   = array('roles'=>array('manager'));
        $a['delete']   = array('roles'=>array('manager'));
        $a['use']      = array('roles'=>array('manager','methodist'));
        
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
        $num = time(); $i=1;
        while ($this->is_exists(array('num'=>$num)))
        {   // Если код не уникален - расширяем
            $num = $num.'_'.$i;
            ++$i;
        }
        // Номер контракта для первоначального добавления
        $dataobject->num = $num;
        // Добавляем текущее время
        $dataobject->adddate = time();
        // Исходный статус
        $dataobject->status = 'tmp';
        //$dataobject->statusdate = time();
        // Вызываем метод из родительского класса
        if ($id = parent::insert($dataobject,$quiet))
        {
            // Устанавливаем номер контракта по номеру записи в БД
            $obj = new object();
            $obj->id = intval($id);
            $obj->status = 'tmp';
            //$obj->statusdate = time();
            $obj->num = sprintf('%06d',$id).'/'.date('y',$dataobject->date).'/'.rand(10,99);
            $this->update($obj);
            return $id; 
        }else
        {
            return false;
        }
    }
    /** Получить список контрактов, заключенных продавцом
     * @param int $pid - id персоны-продавца
     * @return array - список контрактов
     * @access public
     */
    public function get_list_by_seller($pid = NULL)
    {
        // Только для кураторов: если не указан, берем текущую персону
        if (is_null($pid))
        {   // Берем id текущего пользователя
            if ( ! $seller = $this->dof->storage('persons')->get_bu() )
            {
                return array();
            }
            $pid = $seller->id;
        }
        return $this->get_records(array('sellerid'=>$pid), 'id ASC');
    }
    /** Получить список контрактов, заключенных данным клиентом
     * @param int $pid - id персоны-продавца
     * @return array - список контрактов
     * @access public
     */
    public function get_list_by_client($pid = NULL)
    {
        if (is_null($pid))
        {   // Берем id текущего пользователя
            if ( ! $client = $this->dof->storage('persons')->get_bu() )
            {
                return array();
            }
            $pid = $client->id;
        }
        return $this->get_records(array('clientid'=>$pid), 'id ASC');
    }
    /** Получить список актуальных контрактов, в которых участвует этот студент
     * @param int $pid - id персоны-продавца
     * @return array - список контрактов
     * @access public
     */
    public function get_list_by_student($pid = NULL)
    {
        if (is_null($pid))
        {   // Берем id текущего пользователя
            if ( ! $student = $this->dof->storage('persons')->get_bu() )
            {
                return array();
            }
            $pid = $student->id;
        }
        // Ищем по personid
        $pid = (int) $pid;
        $sql = "studentid = {$pid} AND ";
        
        // Ищем по статусу
        // Получаем фрагмент sql, содержащий поиск по списку актуальных статусов
        $sql .= $this->query_part_select('status', array_flip($this->dof->workflow('contracts')->get_list_actual()));
        
        
        return $this->get_records_select($sql, null, 'id ASC', '*');
        //return $this->get_list('studentid', (int) $pid,'id ASC' );
    }
    
    /** Получить список актуальных контрактов, в которых участвует этот студент
     * @param int $pid - id персоны-студента
     * @return array - список контрактов
     * @access public
     */
    public function get_list_by_student_age($pid = NULL)
    {
        if (is_null($pid))
        {    // Берем id текущего пользователя
            if ( ! $student = $this->dof->storage('persons')->get_bu() )
            {
                return array();
            }
            $pid = $student->id;
        }
        // Ищем по personid
        $pid = (int) $pid;
        $sql = "studentid = {$pid} AND ";
        // Ищем по статусу
        // Получаем фрагмент sql, содержащий поиск по списку актуальных статусов
        $sql .= $this->query_part_select('status', array_flip($this->dof->workflow('contracts')->get_list_actual_age()));
        return $this->get_records_select($sql, null, 'id ASC', 'id');
    }
    
    /** Получить список контрактов по статусу
     * @param string $status - статус
     * @return array - список контрактов
     * @access public
     */
    public function get_list_by_status($status,$depid = false)
    {
        if ( $depid )
        {// только для переданного подразделения
            return $this->get_records(array('status'=>(string)$status, 'departmentid'=>$depid,'id ASC'));
        }
        return $this->get_records(array('status'=>$status), 'id ASC');
    }
    /** Есть ли другие активные договора, где используется учетная запись
     * @param int $pid - id пользователя 
     * @param int $except - id контракта, который надо исключить из поиска
     * @return bool
     * @access public
     */
    public function is_person_used($pid,$except=null)
    {
        $pid = (int) $pid;
        $select = " (clientid={$pid} OR studentid={$pid}) AND
         (status<>'cancel' AND status<>'archives') ";
        if ($except)
        {   // Задан контракт, который нужно исключить
            $except = (int) $except;
            $select .= " AND id<>{$except}";
        }
        //print $select;
        return (bool) $this->count_records_select($select);
        
    }
    /** Является ли данная персона куратором по данному контракту
     * @param int $personid - id проверяемого пользователя 
     * @param int $contractid - id контракта в таблице contracts
     * @return bool
     * @access public
     */
    public function is_seller($contractid = null,$personid = null)
    {
        if ( is_null($personid) )
        {//получаем id пользователя
            $personid = $this->dof->storage('persons')->get_by_moodleid_id();
        }
        if ( ! $personid )
        {//что-то с id пользователя не чисто
            return false;
        }
        if ( is_null($contractid) )
        {//контракт не указан возвращаем да, 
            //если пользователь числится в базе как куратор 
            return $this->is_exists(array('sellerid'=>$personid));
        }
        //контракт указан, возвращаем да, 
        //если пользователь числится в базе как куратор этого контракта
        return $this->is_exists(array('sellerid'=>$personid, 'id'=>$contractid));
    }
    
    /**
     * Отвечает на вопрос - является ли данный пользователь студентом
     * Возвращает true, если пользователь числится студентом в контрактах со статусом
     * "подписан нами" или "действует" 
     * @param int $contractid - id контракта в таблице contracts
     * @param int $personid - id проверяемого пользователя по таблице persons  
     * @return bool true - если является студентом, иначе false
     */
    public function is_student($contractid = null,$personid = null)
    {
        if ( is_null($personid) )
        {//получаем id пользователя
            $personid = $this->dof->storage('persons')->get_by_moodleid_id();
        }
        if ( ! $personid )
        {//что-то с id пользователя не чисто
            return false;
        }
        if ( is_null($contractid) )
        {//контракт не указан возвращаем да, 
            //если пользователь числится в базе как клиент
            return $this->is_exists(array('studentid'=>$personid));
        }
        //контракт указан, возвращаем да, 
        //если пользователь числится в базе как студента этого контракта
        return $this->is_exists(array('studentid'=>$personid, 'id'=>$contractid));
    }
    
   /**
     * Отвечает на вопрос - является ли данный пользователь клиентом
     * Возвращает true, если пользователь числится клиентом в контрактах со статусом
     * "подписан нами" или "действует" 
     * @param int $contractid - id контракта в таблице contracts
     * @param int $personid - id проверяемого пользователя по таблице persons  
     * @return bool true - если является студентом, иначе false
     */
    public function is_client($contractid = null, $personid = null)
    {
        if ( is_null($personid) )
        {//получаем id пользователя
            $personid = $this->dof->storage('persons')->get_by_moodleid_id();
        }
        if ( ! $personid )
        {//что-то с id пользователя не чисто
            return false;
        }
        if ( is_null($contractid) )
        {//контракт не указан возвращаем да, 
            //если пользователь числится в базе как клиент
            return $this->is_exists(array('clientid'=>$personid)); 

        }
        //контракт указан, возвращаем да, 
        //если пользователь числится в базе как клиент этого контракта
        return $this->is_exists(array('clientid'=>$personid, 'id'=>$contractid));

    }
    

    /**
     * Если пользователь упомянут в контракте или он учитель либо админ - вернем true
     * @param int $mdluser - id пользователя в moodle
     * @param bool $except - id контракта, который надо исключить из поиска
     * @param string $where - идентификатор происхождения id пользователя 
     * mоodle - id из таблицы mdl_user, fdo - из таблицы persons 
     * @return bool
     */
    public function is_personel($userid, $except=null, $where = 'moodle' )
    {
        
        if ( 'moodle' == $where )
        {//передан id пользователя в moodle
            $mdluser = $userid;
            // найдем пользователя деканата
            if ( ! $personid = $this->dof->storage('persons')->get_by_moodleid_id($mdluser) )
            {// пользователь не найден - укажем невозможный id
                $personid = -1;
            }
        }elseif ( 'fdo' == $where )
        {//передан id пользователя в деканате
            $personid = $userid;
            // найдем пользователя Moodle
            if ( ! $mdluser = $this->dof->storage('persons')->get_field($userid, 'mdluser') )
            {//пользователь не найден - укажем невозможный id 
                $mdluser = 0;
            }
        }else
        {
            return false;
        }
        //если пользователь упомянут в контракте или он учитель либо админ - вернем true
        return $this->dof->modlib('ama')->user(false)->is_teacher($mdluser) OR 
               $this->is_person_used($personid, $except) OR 
               $this->dof->storage('eagreements')->is_person_used($personid);;
    }
    
    /** Получает ФИО продавца и его id по id контракта
     * @param int $id - id контракта
     * @return object - ФИО продовца и его id
     * или false
     */
    public function get_seller($id)
    {
        if ( ! is_int_string($id) )
        {//входные данные неверного формата 
            return false;
        }
        if ( ! $contract = $this->get($id) )
        {// контракт не существует
            return false;
        }
        if ( empty($contract->sellerid)  )
        {// продавец не указан
            return false;
        }
        $seller = new object;
        $seller->id = $contract->sellerid;
        $seller->name = $this->dof->storage('persons')->get_fullname($contract->sellerid);
        if ( ! $seller->name )
        {//не получили имя
            return false;
        }
        return $seller;
    }
    /** Получить список договоров для конкретной персоны
     * @param int $pid - id персоны
     * @return array - список контрактов
     * @access public
     */
    public function get_contracts_for_person($pid = NULL,$depid = false)
    {
        if (is_null($pid))
        {   // Берем id текущего пользователя
            if ( ! $student = $this->dof->storage('persons')->get_bu() )
            {
                return array();
            }
            $pid = $student->id;
        }
        // Ищем по personid
        $pid = (int) $pid;
        // для персоны как студента
        $sql = "studentid = {$pid} OR ";
        // для персоны как клиента
        $sql .= "clientid = {$pid} ";
        if ( $depid )
        {
            $sql = '('.$sql.') AND departmentid='.$depid;
        }
        return $this->get_records_select($sql, null,'id ASC', '*');
    }
    
    /** Возвращает списокконтрактов по заданным критериям 
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
        // @todo - обработку можно было сделать пооптимизированней, не было на это времени
        $addwhere = '';
        if ( ! empty($conds->state) )
        {
            $addwhere = 'AND pr.passportaddrid=adr.id AND adr.region='.$state;
        }
        //формируем строку запроса
        $select = $this->get_select_listing($conds,'c.');
        // возвращаем ту часть массива записей таблицы, которую нужно
        $tblpersons = $this->dof->storage('persons')->prefix().$this->dof->storage('persons')->tablename();
        $tblcontracts = $this->prefix().$this->tablename();
        if (strlen($select)>0)
        {// сделаем необходимые замены в запросе
            $select .= ' AND ';
        }
        $sql = "FROM {$tblcontracts} as c, {$tblpersons} as pr
                WHERE {$select} c.studentid=pr.id {$addwhere}";
        if ( $countonly )
        {// посчитаем общее количество записей, которые нужно извлечь
            return $this->count_records_sql("SELECT COUNT(*) {$sql}");
        }
        // Добавим сортировку
        $sql .= $this->get_orderby_listing($sort);

        return $this->get_records_sql("SELECT c.*, pr.sortname as sortname {$sql}",null, $limitfrom, $limitnum);
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
        if ( isset($conds->personid) AND intval($conds->personid) )
        {
            $selects[] = " (".$prefix."clientid={$conds->personid} OR ".$prefix."studentid={$conds->personid} OR ".$prefix."sellerid={$conds->personid})";
            unset($conds->personid);
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
        if ( is_null($sort) OR empty($sort) OR $sort == 'sortname' )
        {
            return " ORDER BY pr.sortname"; 
        }
        return " ORDER BY c.{$sort}, pr.sortname";
    }
}

?>