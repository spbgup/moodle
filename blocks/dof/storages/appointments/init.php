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

/** Назначения людей на должности (табельные номера)
 * 
 */
class dof_storage_appointments extends dof_storage implements dof_storage_config_interface
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
        $result = true;
        require_once($CFG->libdir.'/ddllib.php');//методы для установки таблиц из xml
        // Модификация базы данных через XMLDB
        // if ($result && $oldversion < 2008121000) 
        // {
        
        // }
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
        return 'appointments';
    }
    /** Возвращает список плагинов, 
     * без которых этот плагин работать не может
     * @return array
     * @access public
     */
    public function need_plugins()
    {
		return array('storage'=>array('eagreements'  => 2010040200,
                                      'schpositions' => 2010040200,
                                      'departments'  => 2010022700,
                                      'acl'          => 2011040504,
                                      'config'       => 2011080900) );
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
        return array(array('plugintype'=>'storage', 'plugincode'=>'appointments', 'eventcode'=>'insert'),
                     array('plugintype'=>'storage', 'plugincode'=>'appointments', 'eventcode'=>'update'));
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
    public function is_access($do, $objid = NULL, $userid = NULL)
    {
        if ( $this->dof->is_access('datamanage') OR $this->dof->is_access('admin') 
             OR $this->dof->is_access('manage') )
        {// манагеру можно все
            return true;
        }
        // получаем id пользователя в persons
        $personid = $this->dof->storage('persons')->get_by_moodleid_id($userid);
        // получаем все нужные параметры для функции проверки прав
        $acldata = $this->get_access_parametrs($do, $objid, $personid);   
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
    public function require_access($do, $objid = NULL, $userid = NULL)
    {
        // Используем функционал из $DOFFICE
        //return $this->dof->require_access($do, NULL, $userid);
        if ( ! $this->is_access($do, $objid, $userid) )
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
        // ловим событие самого себя
        if ( $gentype === 'storage' AND $gencode === 'appointments' )
        {// словили
            switch($eventcode)
            {// при вставке и обновлении
                case 'update': 
                    if ( $mixedvar['new']->departmentid != $mixedvar['old']->departmentid )
                    {
                        $select = new object;
                        $select->baseptype = 'storage';
                        $select->basepcode = 'appointments';
                        $select->basetype  = 'record';
                        $select->baseid    = $mixedvar['old']->id;
                        if ( ! $waapist = $this->dof->storage('aclwarrantagents')->get_listing($select) )
                        {// не нашли - переопределим переменную как пустой массив
                            $waapist = array();
                        }
                        $select = new object;
                        $select->baseptype = 'storage';
                        $select->basepcode = 'departments';
                        $select->basetype  = 'record';
                        $select->baseid    = $mixedvar['old']->departmentid;
                        $select->personid  = $this->dof->storage('eagreements')->get_field($mixedvar['old']->eagreementid,'personid');
                        if ( ! $wadeplist = $this->dof->storage('aclwarrantagents')->get_listing($select) )
                        {// если такие записи нашлись
                            $wadeplist = array();
                        }
                        // найдем еще активные табельные номера для данной персоны
                        $counds = new object;
                        $counds->personid = $select->personid;
                        $counds->departmentid = $mixedvar['old']->departmentid;
                        $counds->status = 'active';
                        if ( $this->get_listing($counds) )
                        {// если в подразделении есть еще табельные номера - не отменяем право на просмотр данных
                            $wadeplist = array();
                        }
                        $oldlist = $waapist + $wadeplist;
                        foreach($oldlist as $key => $record)
                        {//Обновляем все записи, касающиеся данного назначения
        		            $this->dof->workflow('aclwarrantagents')->change($record->id,'archive');
                        }
                    }
                case 'insert': 
                    // добавляем применения доверенностей
                    $positionid = $this->dof->storage('schpositions')->get_field($mixedvar['new']->schpositionid,'positionid');
                    $conds = new object;
                    $conds->linkptype = 'storage';
                    $conds->linkpcode = 'positions';
                    $conds->linkid = $positionid;
                    $conds->linktype = 'record';
                    $conds->status = 'active';
                    if ( $warrants = $this->dof->storage('aclwarrants')->get_listing($conds) )
                    {// если сами эти доверенности существуют
                        foreach ( $warrants as $warrant )
                        {
                            $this->add_warrentagent_for_appointment($mixedvar['new'],$warrant);
                        }
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
        return 'block_dof_s_appointments';
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
    protected function get_access_parametrs($action, $objectid, $personid)
    {
        $result = new object();
        $result->plugintype   = $this->type();
        $result->plugincode   = $this->code();
        $result->code         = $action;
        $result->personid     = $personid;
        $result->departmentid = optional_param('departmentid', 0, PARAM_INT);
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
     * 				 'roles' => array('student' ,'...');
     */
    public function acldefault()
    {
        $a = array();
        
        $a['view']     = array('roles'=>array('manager','methodist'));
        $a['edit']     = array('roles'=>array('manager'));
        $a['create']   = array('roles'=>array('manager'));
        $a['delete']   = array('roles'=>array());
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
    /** Получить фрагмент списка учебных периодов для вывода таблицы 
     * 
     * @return array массив записей из базы, или false в случае ошибки
     * @param int $limitfrom - начиная с какой записи просматривается фрагмент списка записей
     * @param int $limitnum - сколько записей нужно извлечь из базы
     * @param bool $flag - вспомогательный параметр - вывод частного случая или ДЕЛАТЬ другую сортировку
     * @param object $conds - список параметров для выборки периодов 
     */
    public function get_listing($conds=null, $limitfrom=null, $limitnum=null, $countonly=false, $flag=false)
    {
        if ( ! $conds )
        {// если список потоков не передан - то создадим объект, чтобы не было ошибок
            $conds = new Object();
        }
        $conds = (object) $conds;
        if ( ($limitnum <= 0) AND ! is_null($limitnum) )
        {// количество записей на странице может быть 
            //только положительным числом
            $limitnum = $this->dof->modlib('widgets')->get_limitnum_bydefault();
        }
        if ( ($limitfrom < 0) AND ! is_null($limitfrom) )
        {//отрицательные значения номера просматриваемой записи недопустимы
            $limitfrom = 0;
        }
        // сортировка 
        if ( ! isset($conds->orderby) )
        {
            $orderby = 'ASC';
        }else 
        {
            $orderby = $conds->orderby;
            unset($conds->orderby);
        }
        // сортировка по полю(имя или должность)
        if ( ! isset($conds->sort) )
        {
            $sort = 'ORDER BY per.sortname '.$orderby;
        }else 
        {
            // определим по имени или по должности
            if ( $conds->sort == 'sortname' )
            {
                $sort = 'ORDER BY per.sortname '.$orderby;
            }else 
            {
                $sort = 'ORDER BY pos.name '.$orderby;    
            }    
            unset($conds->sort);
        }        
        //формируем строку запроса
        $select = $this->get_select_listing($conds);
        if ( $countonly )
        {// посчитаем общее количество записей, которые нужно извлечь
            return $this->count_records_select($select);
        }
        // возвращаем ту часть массива записей таблицы, которую нужно
        // сложнызапрос - для сортировки по имени и должности
        if ( $flag )
        {
            // Готовим SQL-запрос
            $tblpersons = $this->prefix().$this->dof->storage('persons')->tablename();
            $tbleagreements = $this->prefix().$this->dof->storage('eagreements')->tablename();
            $tblschpositions = $this->prefix().$this->dof->storage('schpositions')->tablename();
            $tblpositions = $this->prefix().$this->dof->storage('positions')->tablename();
            $tblappointments = $this->prefix().$this->tablename();
            if (strlen($select)>0)
            {
                $select = 'app.'.preg_replace('/ AND /',' AND app.',$select.' ').' AND ';
            }
    
            $sql = "SELECT app.id AS id, per.sortname as name, pos.name as posname,pos.code as code, eag.num as num, 
            		app.enumber as enumber, app.worktime as worktime, app.status as status" 
            	." FROM {$tbleagreements} as eag,{$tblpersons} as per,{$tblappointments} as app, 
            	        {$tblpositions} as pos, {$tblschpositions} as schpos "
            	." WHERE {$select} app.eagreementid=eag.id AND eag.personid=per.id AND app.schpositionid=schpos.id 
            			ANd schpos.positionid=pos.id ".$sort;

            return $this->get_records_sql($sql,null,$limitfrom, $limitnum);
        }    
        
        return $this->get_records_select($select, null, '', '*', $limitfrom, $limitnum);
    }
    
    /** Возвращает список учебных потоков по заданным критериям 
     * 
     * @return array массив записей из базы, или false в случае ошибки
     * @param int $limitfrom - начиная с какой записи просматривается фрагмент списка записей
     * @param int $limitnum - сколько записей нужно извлечь из базы
     * @param object $conds[optional] - объект со списком свойств, по которым будет происходить поиск
     * @param object $countonly[optional] - только вернуть количество записей по указанным условиям
     */
    public function get_teacher_list(object $conds = null)
    {
        if ( ! $conds )
        {// если список потоков не передан - то создадим объект, чтобы не было ошибок
            $conds = new Object();
        }
        // возвращаем ту часть массива записей таблицы, которую нужно
        $tblperson = $this->dof->storage('persons')->prefix().$this->dof->storage('persons')->tablename();
        $tbleagreement = $this->dof->storage('eagreements')->prefix().$this->dof->storage('eagreements')->tablename();
        $tbl = $this->prefix().$this->tablename();
        $sql = "SELECT ap.*, ea.id as eagreementid, pr.id as personid
                FROM  {$tbl} as ap, {$tbleagreement} as ea, {$tblperson} as pr
                WHERE ap.eagreementid=ea.id AND ea.personid=pr.id";
        if ( isset($conds->eagreementdepartmentid) AND $conds->eagreementdepartmentid )
        {// поле потока - добавим в выборку
             $sql .=' AND '.trim($this->query_part_select('ea.departmentid',$conds->eagreementdepartmentid));
             // удалим из полей шаблона
             unset($conds->eagreementdepartmentid);
        }

        if ( $select = $this->get_select_listing($conds) )
        {
            $select = ' AND ap.'.preg_replace('/ AND /',' AND ap.',$select.' ');
            $select = preg_replace('/ OR /',' OR ap.',$select);
            $select = str_replace('ap. (','(ap.',$select);
            $select = str_replace('ap.(','(ap.',$select);
            $sql .= " {$select}";
        }
        $sql .= " ORDER BY pr.sortname";
        return $this->get_records_sql($sql);
    }
    
    /**Возвращает фрагмент sql-запроса после слова WHERE
     * @param object $inputconds - список полей с условиями запроса в формате "поле_БД->значение" 
     * @param string $prefix - префикс к полям, если запрос составляется для нескольких таблиц
     * @return string
     */
    public function get_select_listing($inputconds,$prefix='')
    {
        // создадим массив для фрагментов sql-запроса
        $selects = array();
        $conds = fullclone($inputconds);
        if ( isset($conds->positionid) AND intval($conds->positionid) )
        {// ищем записи по должности
            $schpositions = $this->dof->storage('schpositions')->get_records(array('positionid'=>$conds->positionid), null, 'id');
            if ( $schpositions )
            {// есть записи принадлежащие такой должности
                $schpositionids = array();
                foreach ( $schpositions as $schposition )
                {// собираем все schpositionids
                    $schpositionids[] = $schposition->id;
                }
                // склеиваем их в строку
                $schpositionidsstring = implode(', ', $schpositionids);
                // составляем условие
                $selects[] = ' '.$prefix.'schpositionid IN ('.$schpositionidsstring.')';
            }else
            {// нет записей принадлежащих такой должности
                // составим запрос, который гарантированно вернет false
                return ' '.$prefix.'schpositionid = -1 ';
            }
            // убираем positionid из запроса для того чтобы не создать 2 условия для одного поля
            unset($conds->positionid);
        }
        if ( isset($conds->personid)  )
        {// ищем записи по должности
            $eagreements = $this->dof->storage('eagreements')->get_records(array('personid'=>$conds->personid), null, 'id');
            if ( $eagreements )
            {// есть записи принадлежащие такой должности
                $eagreementids = array();
                foreach ( $eagreements as $eagreement )
                {// собираем все schpositionids
                    $eagreementids[] = $eagreement->id;
                }
                // склеиваем их в строку
                $eagreementidsstring = implode(', ', $eagreementids);
                // составляем условие
                $selects[] = ' '.$prefix.'eagreementid IN ('.$eagreementidsstring.')';
            }else
            {// нет записей принадлежащих такой должности
                // составим запрос, который гарантированно вернет false
                return ' '.$prefix.'eagreementid = -1 ';
            }
            // убираем positionid из запроса для того чтобы не создать 2 условия для одного поля
            unset($conds->personid);
        }
        // теперь создадим все остальные условия
        foreach ( $conds as $name=>$field )
        {
            if ( $field )
            {// если условие не пустое, то для каждого поля получим фрагмент запроса
                $selects[] = $this->query_part_select($prefix.$name,$field);
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
    /** Возвращает количество периодов
     * 
     * @param string $select - критерии отбора записей
     * @return int количество найденных записей
     */
    public function get_numberof($select)
    {
        dof_debugging('storage/apointments get_numberof.Этот метод не имеет смысла', DEBUG_DEVELOPER);
        return $this->count_select($select);
    }
    /** Есть ли другие записи с таким табельным номером
     * @param string $enumber
     * @return bool
     */
	public function is_enumber_unique($enumber)
	{
		return !(bool) $this->count_list(array('enumber'=>$enumber));	
	}
    /** Возвращает допустимое время вакансии
     * @param int $schpositionid - id вакансии
     * @return bool
     */
	public function get_free_worktime($schpositionid)
	{
		$worktime = 0;
		// найдем другие назначения на эту вакансию, кроме уже освобожденных
        if ( $appointments = $this->dof->storage('appointments')->
                 get_records(array('schpositionid'=>$schpositionid, 'status'=>array('plan','active'))) )
        {// если что-то нашли
            foreach ( $appointments as $appointment )
            {// сложим все ставки вместе
                $worktime += $appointment->worktime;
            }
        } 
        // вычтем из общей ставки вакансии уже занятое время и вернем результат
        return $this->dof->storage('schpositions')->get_field($schpositionid,'worktime') - $worktime;	
	}
    /** Получить объект из таблицы persons по id в таблице appointments
     * 
     * @return object|bool - объект из таблицы persons или false если ничего не нашлось
     * @param object|int $appointment - объект из таблицы appointments или id объекта из таблицы appointments 
     */
    public function get_person_by_appointment($appointment, $enum = false)
    {
        if ( ! is_object($appointment) )
        {// если переменная - не объект, значит нам передали id
            if ( ! $appointment = $this->get($appointment) )
            {// неправильный формат данных или такой записи не существуэ
                return false;
            }
        }else
        {
            if ( ! isset($appointment->eagreementid) )
            {// передан неправильный объект
                return false;
            }
        }
        if ( ! $eagreement = $this->dof->storage('eagreements')->get($appointment->eagreementid) )
        {// договор не найден
            // @todo это означает ошибку целостности базы данных - в будущем надо будет записать в лог
            return false;
        }
        if ( ! $person = $this->dof->storage('persons')->get($eagreement->personid) )
        {// пользователь с таким id не найден
            // @todo это означает ошибку целостности базы данных - в будущем надо будет записать в лог
            return false;
        }
        $person->appointmentid = $appointment->id;
        if ( $enum )
        {// сказано, что нужно вернуть и табельный номер
            $person->enumber = $appointment->enumber;
        }
        // возвращаем найденную запись
        return $person;
    }
    
    /**
     * Возвращает список персон по переданным записям appointments,
     * отсортированный по ФИО
     * @param array $appointments - массив записей из таблицы appointments
     * @return mixed array - массив записей из таблицы persons, 
     * упорядоченных по sortname ASC. К каждой записи добавлено 
     * поле enumber - табельный номер
     * поле appointmentid - id назначения на должность
     * или bool false, если что-то не получилось 
     */
    public function get_persons_by_appointments($appointments)
    {
        if ( ! is_array($appointments) )
        {
            return false;
        }
        $rez = array();
        foreach ( $appointments as $one )
        {
            if ( ! $person = $this->get_person_by_appointment($one->id, true) )
            {
                continue;
            }
            $rez[$one->id] = $person;
        }
        usort($rez, 'sortapp_by_sortname');
        return $rez;
    }
    /**
     * Возвращает список табельных номеров для персоны,
     * @param int $persons - массив записей из таблицы persons
     * @return mixed array - массив записей из таблицы appointments
     * или bool false, если что-то не получилось 
     */
    public function get_appointment_by_persons($personid)
    {
        if ( ! is_int_string($personid) )
        {// неверный формат данных
            return false;
        }
        $eaids = array();
        if ( ! $eagreements = $this->dof->storage('eagreements')->get_records(array
               ('personid'=>$personid,'status'=>array('plan','active'))) )
        {// догворов у персоны нет  - значит и табельных номеров нет
            return array();
        }
        foreach ( $eagreements as $eagreement )
        {// запомним все договора
            $eaids[] = $eagreement->id;
        }
        // найдем все табельные номера на персону
        return $appoints = $this->dof->storage('appointments')->get_records(array
                   ('eagreementid'=>$eaids,'status'=>array('plan','active')));
    }
    
    /** Добавляет применение для данного мандата
     * @param int $positionid - i
     * @param object $warrant - запись из справочника
     */
    public function add_warrentagents($positionid,$warrant)
    {
        $rez = true;
        //Получим список назначений, для которых нужно вставить применения мандат
        $select = new object();
        $select->positionid = $positionid;
        $select->status = array('plan','active');
        if ( $list = $this->get_listing($select) )
        {//Если назначения есть
            foreach ( $list as $record )
            {//Создаём для них соответствующие записи применения мандатов
                $rez = $rez && $this->add_warrentagent_for_appointment($record,$warrant);
            }
           
        }
        return $rez;
    }
    
    /** Добавляет применение для данного мандата
     * @param int $positionid - i
     * @param object $warrant - запись из справочника
     */
    public function add_warrentagent_for_appointment($appointment,$warrant)
    {
        $rez = true;
        if ( ! is_object($appointment) )
        {//если передано не назначение, а его id
            if ( ! $appointment = $this->dof->storage('appointments')->get($appointment) )
            {//назначения нет в БД
                return false;
            }
        }
        //Создаём для нее соответствующие записи применения мандатов
        $obj = new object;
        $obj->baseptype = 'storage';
        $obj->basepcode = 'appointments';
        $obj->basetype = 'record';
        $obj->aclwarrantid = $warrant->id;
        $obj->begindate = $obj->datecreate = time();
        $date = date_create('2038-01-01');
        $obj->duration = $date->format('U') - $obj->begindate;
        $obj->isdelegatable = $warrant->isdelegatable;
        $obj->departmentid = $appointment->departmentid;
        if ( $person = $this->get_person_by_appointment($appointment->id) )
        {// есть запись - хорошо
            $obj->personid = $person->id;    
        }else
        {
            return false;
        }
        $obj->baseid = $appointment->id;
        if ( ! empty($appointment->begindate) )
        {// дата начала не пустая - добавим в примененик
            $obj->begindate = $appointment->begindate;
        }
        // добавляем запись
        if ( $waid = $this->dof->storage('aclwarrantagents')->add($obj) )
        {// если запись добавилась удачно
            if ( $this->get_field($appointment->id,'status') == 'active' AND $warrant->status == 'active' )
            {// сменим ей статус если назначение и мандата активные
                $rez = $rez && $this->dof->workflow('aclwarrantagents')->change($waid,'active');
            }
        }else
        {// запись не добавилась - вернем ошибку
            return false;
        }
        // добавляем запись для просмотра подразделений
        $obj->basepcode = 'departments';
        $obj->baseid = $appointment->departmentid;
        // добавляем запись
        if ( $waid = $this->dof->storage('aclwarrantagents')->add($obj) )
        {// если запись добавилась удачно
            if ( $this->get_field($appointment->id,'status') == 'active' AND $warrant->status == 'active' )
            {// сменим ей статус если назначение и мандата активные
                $rez = $rez && $this->dof->workflow('aclwarrantagents')->change($waid,'active');
            }
        }else
        {// запись не добавилась - вернем ошибку
            return false;
        }
        return $rez;
    }
    
}

/**
 * Функция сравнения двух объектов 
 * из таблицы persons по полю sortname
 * @param object $person1 - запись из таблицы persons
 * @param object $person2 - другая запись из таблицы persons
 * @return -1, 0, 1 в зависимости от результата сравнения
 * используется в методе get_persons_by_appointments
 * для сортировки по алфавиту
 */
function sortapp_by_sortname($person1,$person2)
{
    return strnatcmp($person1->sortname, $person2->sortname);
}
?>