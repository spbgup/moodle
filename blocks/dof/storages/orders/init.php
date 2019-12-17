<?php
////////////////////////////////////////////////////////////////////////////
//                                                                        //
// NOTICE OF COPYRIGHT                                                    //
//                                                                        //
// Dean`s Office for Moodle                                               //
// Электронный деканат                                                    //
// <http://www.deansoffice.ru/>                                           //
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

// Подключаем дополнительную библиотеку
require_once $DOF->plugin_path('storage', 'orders','/baseorder.php');

/** Приказы
 * 
 */
class dof_storage_orders extends dof_storage
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
        global $CFG;
        require_once($CFG->libdir.'/ddllib.php');//методы для установки таблиц из xml
        $result = true;
        
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
		return 2012042500;
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
        return 'orders';
    }
    /** Возвращает список плагинов, 
     * без которых этот плагин работать не может
     * @return array
     * @access public
     */
    public function need_plugins()
    {
		return array('storage'=>array( 'acl' => 2011041800 ) );
    }/** Определить, возможна ли установка плагина в текущий момент
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
            case 'orders_on_orderdata': $this->rewrite_orders(); break;
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
        return 'block_dof_s_orders';
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
      
    /** Задаем права доступа для объектов этого хранилища
     * 
     * @return array
     */
    public function acldefault()
    {
        $a = array();
        
        $a['view']   = array('roles'=>array('manager'));
        $a['edit']   = array('roles'=>array('manager'));
        $a['create'] = array('roles'=>array('manager'));
        $a['delete'] = array('roles'=>array());
        $a['use']    = array('roles'=>array('manager'));
        
        return $a;
    }
    
    // **********************************************
    //              Собственные методы
    // **********************************************

    /*
     * Получить список приказов по параметрам
     */
    public function get_list_by_code($plugintype,$plogincode,$code,$departmentid=null,
                            $ownerid=null,$signerid=null,$status=null,$limitfrom='',$limitnum='',
                            $time=null, $sort='id ASC', $countonly=false)
    {
        $select = '';
        if (!empty($plugintype))
        {
            if (!empty($select)){$select .= " AND ";}
            $select .= "plugintype='{$plugintype}'";
        }
        if (!empty($plogincode))
        {
            if (!empty($select)){$select .= " AND ";}
            $select .= "plugincode='{$plogincode}'";
        }
        if (!empty($code))
        {
            if (!empty($select)){$select .= " AND ";}
            $select .= "code='{$code}'";
        }
        if (!empty($departmentid))
        {
            if (!empty($select)){$select .= " AND ";}
            $select .= "departmentid='{$departmentid}'";
        }
        if (!empty($ownerid))
        {
            if (!empty($select)){$select .= " AND ";}
            $select .= "ownerid='{$ownerid}'";
        }
        if (!empty($signerid))
        {
            if (!empty($select)){$select .= " AND ";}
            $select .= "signerid='{$signerid}'";
        }
        if (!empty($status))
        {
            if (!empty($select)){$select .= " AND ";}
            $select .= "status='{$status}'";
        }
        if (!empty($time) AND is_array($time))
        {
            if (!empty($select))
            {    
                $select .= " AND ";
            }
            
            if ( ! empty($time['begindate']) )
            {
                $select .= "exdate>='{$time['begindate']}'";
            }
            
            if ( ! empty($time['enddate']) )
            {
                $select .= "AND exdate<='{$time['enddate']}'";
            }     
        }
        
        if ( $countonly )
        {// посчитаем общее количество записей, которые нужно извлечь
            return $this->count_records_select($select, array(),'COUNT(*)');
        }
        
        return $this->get_records_select($select, null,$sort, 'id,plugintype,plugincode,code,departmentid,ownerid,signerid,date,signdate,exdate,changedate,status,notes', $limitfrom, $limitnum);
    }

    /** Перезаписывает ордера на новую таблицу orderdata
     * 
     * @return array
     */
    public function rewrite_orders()
    {
        $result = true;
        // времени понадобится много
        dof_hugeprocess();
        // выводим сообщение о том что начинается очистка таблицы событий
        dof_mtrace(2, 'Starting orders rewrite on orderdata(storage/orderss)...');
        
        while ( $orders = $this->get_records_select(' sdata IS NOT NULL ',null, '', '*', 0, 100) )
        {// нас интересуют все запланированные или отложенные события, которые привязаны к 
            // несуществующим или удаленным дням
            foreach ( $orders as $order )
            {
                $this->todo_rewrite_data($order);
                dof_mtrace(2, 'orderid='.$order->id.' rewrite on orderdata');
            }
        }
        
        return $result;
    }

    /**
     * Метод, который записывает в таблицу orderdata присланный ордер
     * если запись прошла плохо - данные sdata не затираются
     * @param object $order - сам ордер
     * @param object/array $data - данные масива для сериалицации(они тут разбираются)
     */
    protected function todo_rewrite_data($order)
    {
        // переменная для подсчета НОМЕРА переменной(чтоб правильно собрать)
        $i = 0;
        $obj = new object();
        // сохраняем ордер, на который ссылается
        $obj->orderid = $order->id;
        // переменная - удалять из ордера sdata или нет
        $delete = true;
        
        $data = unserialize($order->sdata);
        if ( is_array($data) OR is_object($data) )
        { 
            foreach ( $data as $key=>$value )
            {// перебираем сами данные
                $i++;
                // запоминаме имя
                $obj->firstlvlname = $key;
                // порядковый номер имени
                $obj->varnum = $i;
    
                if ( is_array($value) OR is_object($value) )
                {// если не расшипился до значений - сериализуеи и сохраняем
                    // не скаляр
                    $obj->scalar = 0;
                    $obj->data = serialize($value);
                    // запишем данные поле в индекс
                    // исключение проблемы экранирования кавычки
                    $obj->ind = substr($obj->data,0,254);
                }else 
                {// обычный скаляр
                    $obj->scalar = 1;
                    $obj->data = $value;
                    // запишем данные в поле индекс
                    $obj->ind = $value; 
                }
                // вставляем
                if ( ! $this->dof->storage('orderdata')->insert($obj) )
                {// что-то не установилось - удалять нельзя 
                    $delete = false;
                }
            }    
        }elseif( is_null($data) ) 
        {// как правило ЭТО при выставлении оценок и закрытии ведомости с пустой оценкой
            $delete = true;
        } 
        if ( $delete )
        {
            $order->sdata = NULL;
            $this->dof->storage($this->code())->update($order, $order->id);
        }

        return true;
    }    
    
    
    
    
}

?>
