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
// Copyright (C) 2008-2999  Ilia Smirnov (Илья Смирнов)                   // 
// Copyright (C) 2008-2999  Mariya Rojayskaya (Мария Рожайская)           // 
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

/** Учебные периоды
 * 
 */
class dof_im_ages implements dof_plugin_im
{
    /**
     * @var dof_control
     */
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
        return 2012060600;
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
        return 'angelfish';
    }
    
    /** Возвращает тип плагина
     * @return string 
     * @access public
     */
    public function type()
    {
        return 'im';
    }
    /** Возвращает короткое имя плагина
     * Оно должно быть уникально среди плагинов этого типа
     * @return string
     * @access public
     */
    public function code()
    {
        return 'ages';
    }
    /** Возвращает список плагинов, 
     * без которых этот плагин работать не может
     * @return array
     * @access public
     */
    public function need_plugins()
    {
        return array('modlib'=>array('nvg'     => 2008060300,
                                     'widgets' => 2009050800),
                     'storage'=>array('persons'     => 2009060400,
                                      'departments' => 2009040800,
                                      'ages'        => 2009050600,
                                      'acl'         => 2011040504) );
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
     * и без которых начать установку невозможно
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
       return array(
                array('plugintype' => 'im',
                      'plugincode' => 'obj',
                      'eventcode'  => 'get_object_url'));
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
     * @param int $userid - идентификатор пользователя в Moodle, полномочия которого проверяются
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
    public function require_access($do, $objid = NULL, $userid = NULL)
    {
        // Используем функционал из $DOFFICE
        //return $this->dof->require_access($do, NULL, $userid);
        if ( ! $this->is_access($do, $objid, $userid) )
        {
            $notice = "ages/{$do} (block/dof/im/ages: {$do})";
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
    public function catch_event($gentype, $gencode, $eventcode, $intvar, $mixedvar)
    {
        if ( $gentype == 'im' AND $gencode == 'obj' AND $eventcode == 'get_object_url' )
        {
            if ( $mixedvar['storage'] == 'ages' )
            {
                if ( isset($mixedvar['action']) AND $mixedvar['action'] == 'view' )
                {// Получение ссылки на просмотр объекта
                    $params = array('ageid' => $intvar);
                    if ( isset($mixedvar['urlparams']) AND is_array($mixedvar['urlparams']) )
                    {
                        $params = array_merge($params, $mixedvar['urlparams']);
                    }
                    return $this->url('/view.php', $params);
                }
            }
        }
        return false;
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
     * @param dof_control $dof - идентификатор действия, которое должно быть совершено
     * @access public
     */
    public function __construct($dof)
    {
        $this->dof = $dof;
    }
    // **********************************************
    // Методы, предусмотренные интерфейсом im
    // **********************************************
    /** Возвращает текст для отображения в блоке на странице dof
     * @param string $name - название набора текстов для отображания
     * @param int $id - id текста в наборе
     * @return string - html-код содержимого блока
     */
    function get_block($name, $id = 1)
    {
        $rez = '';
        $depid = optional_param('depid', 0, PARAM_INT);
        $addvars = array();
        $addvars['departmentid'] = $depid;
        switch ($name)
        {
            case 'main':
                $path = $this->dof->url_im($this->code(),'/index.php',$addvars);
                if ( $this->dof->storage('ages')->is_access('view') )
                {//может видеть все периоды
                    $path = $this->dof->url_im($this->code(),'/list.php',$addvars);
                }
                //ссылка на список подразделений
                $rez .= "<a href=\"{$path}\">".$this->dof->get_string('list', $this->code()).'</a>';
                if ( $this->dof->storage('ages')->is_access('create') )
                {//может создавать период - покажем ссылку
                    $rez .= "<br />";
                    $path = $this->dof->url_im($this->code(),'/edit.php',$addvars);
                    $rez .= "<a href=\"{$path}\">".$this->dof->get_string('new', $this->code()).'</a>';
                }
            break;
        }
        return $rez;
    }
    /** Возвращает html-код, который отображается внутри секции
     * @param string $name - название набора текстов для отображания
     * @param int $id - id текста в наборе
     * @return string  - html-код содержимого секции секции
     */
    function get_section($name, $id = 1)
    {
        $rez = '';
        switch ($name)
        {

        }
        return $rez;
    }
     /** Возвращает текст, отображаемый в блоке на странице курса MOODLE 
      * @return string  - html-код для отображения
      */
    public function get_blocknotes($format='other')
    {
        return "<a href='{$this->dof->url_im($this->code(),'/index.php')}'>"
                    .$this->dof->get_string('page_main_name')."</a>";
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
            $result->departmentid = $this->dof->storage('ages')->get_field($objectid, 'departmentid');
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
        return $a;
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
     * Возвращает html-код отображения 
     * информации об учебном периоде
     * @param stdClass $obj - запись из таблицы
     * @return mixed string html-код или false в случае ошибки
     */
    public function show($obj,$conds)
    {
        if (! is_object($obj))
        {// переданны данные неверного формата
        	return false;
        }
        $data = array();
    	// заносим данные в таблицу
   		$data = $this->get_string_table($obj,$conds);
    	// выводим таблицу на экран
        return $this->print_single_table($data);
    }
    
    /** Распечатать вертикальную таблицу для удобного отображения информации по элементу
     * 
     * @return null
     * @param object $data объект с отображаемыми значениями
     */
    private function print_single_table($data)
    {
        $table = new Object();
        if ( ! $data )
        {
            return '';
        }
        // получаем подписи с пояснениями
        $descriptions = $this->get_fields_description();
        $i = 0;
        foreach ( $data as $elm )
        {
            $table->data[] = array('<b>'.current(each($descriptions)).'</b>', $elm);
        }
        return $this->dof->modlib('widgets')->print_table($table, true);
    }
    
    /**
     * Возвращает html-код отображения 
     * информации об учебном периоде
     * @param int $id - id записи из таблицы
     * @return mixed string html-код или false в случае ошибки
     */
    public function show_id($id,$addvars)
    {
        if ( ! is_int_string($id) )
        {//входные данные неверного формата 
            return false;
        }
    	if ( ! $obj = $this->dof->storage($this->code())->get($id) )
    	{// период не найден
    		return false;
    	}
    	return $this->show($obj,$addvars);
    }
    
    /**
     * Возвращает html-код отображения 
     * информации о нескольких периодах
     * @param массив $list - массив записей 
     * периодов, которые надо отобразить 
     * @return mixed string в string html-код или false в случае ошибки
     */
    public function showlist($list,$conds)
    {
        if ( ! is_array($list))
        {// переданны данные неверного формата
        	return false;
        }
        $data = array();
    	// заносим данные в таблицу
    	foreach ($list as $obj)
    	{   
    	    $data[] = $this->get_string_table($obj,$conds);
    	}
    	
    	// выводим таблицу на экран
        return $this->print_table($data);
    }
    
    /**
     * Возвращает форму создания/редактирования с начальными данными
     * @param int $id - id записи, значения 
     * которой устанавливаются в поля формы по умолчанию
     * @return moodle quickform object
     */
    public function form($id = NULL)
    {
        global $USER;
        // устанавливаем начальные данные
        if (isset($id) AND ($id <> 0) )
        {// id передано
            $age = $this->dof->storage($this->code())->get($id); 
        }else
        {// id не передано
            $age = $this->form_new_data();
        }
        if ( isset($USER->sesskey) )
        {//сохраним идентификатор сессии
            $age->sesskey = $USER->sesskey;
        }else
        {//идентификатор сессии не найден
            $age->sesskey = 0;
        }
        $customdata = new stdClass;
        $customdata->age = $age;
        $customdata->dof = $this->dof;
        // подключаем методы вывода формы
        $form = new dof_im_edit(null,$customdata);
        // очистим статус, чтобы не отображался как в БД
        unset($age->status);
        // заносим значения по умолчению
        $form->set_data($age); 
        // возвращаем форму
        return $form;
    }
    
    /**
     * Возвращает заготовку для формы создания периода
     * @return stdclassObject
     */
    private function form_new_data()
    {
        $age = new object;
        $age->id = 0;
        $age->enddate = $age->begindate = time();
        $age->departmentid = optional_param('departmentid', 0, PARAM_INT);
        $age->previousid = 0;
        return $age;
    }
    
    /** Возвращает html-код таблицы
     * @param array $date - данные в таблицу
     * @return string - html-код или пустая строка
     */
    private function print_table($date)
    {
        // рисуем таблицу
        $table = new object();
        $table->tablealign = "center";
        $table->cellpadding = 5;
        $table->cellspacing = 5;
        $table->size = array ('100px','150px','150px','200px','150px','100px');
        $table->align = array ("center","center","center","center","center","center","center","center");
        // шапка таблицы
        $table->head =  $this->get_fields_description();
        // заносим данные в таблицу     
        $table->data = $date;
        return $this->dof->modlib('widgets')->print_table($table,true);
    }
    
    /** Получить заголовок для списка таблицы, или список полей
     * для отображения одного объекта 
     * @return array
     */
    private function get_fields_description()
    {
        return array($this->dof->get_string('actions',     $this->code()),
                     $this->dof->get_string('name',        $this->code()),
                     $this->dof->get_string('begindate',   $this->code()),
                     $this->dof->get_string('enddate',     $this->code()),
                     $this->dof->get_string('eduweeks',    $this->code()),
                     $this->dof->get_string('department',  $this->code()),
                     $this->dof->get_string('previousage', $this->code()),
                     $this->dof->get_string('status',      $this->code())); 
    }
    
    /** Возвращает массив для вставки в таблицу
     * @param object $obj
     * @return array
     */
    private function get_string_table($obj,$conds)
    {
        $department = $this->dof->storage('departments')->get_field($obj->departmentid,'name').' <br>['.
    	              $this->dof->storage('departments')->get_field($obj->departmentid,'code').']';
   		$begindate = dof_userdate($obj->begindate,"%d-%m-%Y");
   		$enddate = dof_userdate($obj->enddate,"%d-%m-%Y");
   		if ( ! $previous = $this->dof->storage($this->code())->get_field($obj->previousid,'name') )
   		{//если названия периода нет - выведем пустую строчку
   		    $previous = '';
   		}
   		//получаем ссылки на картинки
        $imgedit = '<img src="'.$this->dof->url_im($this->code(), '/icons/edit.png').'"
            alt="'.$this->dof->get_string('edit', $this->code()).'" title="'.$this->dof->get_string('edit', $this->code()).'">';
        $imgview = '<img src="'.$this->dof->url_im($this->code(), '/icons/view.png').'" 
            alt="'.$this->dof->get_string('view', $this->code()).'" title="'.$this->dof->get_string('view', $this->code()).'">';
        // добавляем ссылку
        $link = '';
        if ( $this->dof->storage('ages')->is_access('edit', $obj->id) )
        {//покажем ссылку на страницу редактирования
            $link .= '<a href='.$this->dof->url_im($this->code(),'/edit.php?ageid='.$obj->id,$conds)
            .'>'.$imgedit.'</a>&nbsp;';
        }
        if ( $this->dof->storage('ages')->is_access('view', $obj->id) )
        {//покажем ссылку на страницу просмотра
            $link .= '<a href='.$this->dof->url_im($this->code(),'/view.php?ageid='.$obj->id,$conds).'>'.$imgview.'</a>&nbsp;';
        }
        if ( $this->dof->im('plans')->is_access('viewthemeplan',$obj->id,null,'ages') )
        {// если есть право на просмотр планирования
            $link.= '<a href="'.$this->dof->url_im('plans','/themeplan/viewthemeplan.php?linktype=ages&linkid='.$obj->id,$conds).'">';
            $link .= '<img src="'.$this->dof->url_im('ages', '/icons/plan.png').'"
                alt=  "'.$this->dof->get_string('view_plancstream', 'ages').'" 
                title="'.$this->dof->get_string('view_plancstream', 'ages').'" /></a>&nbsp;';
        }
	    $status = $this->dof->workflow($this->code())->get_name($obj->status);
   	    return array($link, $obj->name,$begindate,$enddate,$obj->eduweeks,$department,$previous,$status);
    }

    /** Получить фрагмент списка учебных периодов для вывода таблицы 
     * 
     * @return array массив записей из базы, или false в случае ошибки
     * @param int $limitfrom - начиная с какой записи просматривается фрагмент списка записей
     * @param int $limitnum - сколько записей нужно извлечь из базы
     * @param object $conds - список параметров для выборки периодов 
     */
    public function get_listing($limitfrom, $limitnum, $conds=null)
    {
        dof_debugging('im/ages get_listing.Метод перенесен в storage', DEBUG_DEVELOPER);
        if ( ! $conds )
        {// если список периодов не передан - то создадим объект, чтобы не было ошибок
            $conds = new Object();
        }
        if ( $limitnum <= 0 )
        {// количество записей на странице может быть 
            //только положительным числом
            $limitnum = $this->dof->modlib('widgets')->get_limitnum_bydefault(); 
        }
        if ( $limitfrom < 0 )
        {//отрицательные значения номера просматриваемой записи недопустимы
            $limitfrom = 0;
        }
        $countselect = $this->get_select_listing($conds);
        // посчитаем общее количество записей, которые нужно извлечь
        $recordscount = $this->dof->storage($this->code())->get_numberof_ages($countselect);
        if ( $recordscount < $limitfrom )
        {// если количество записей в базе меньше, 
            //чем порядковый номер записи, которую надо показать  
            //покажем последнюю страницу
            $limitfrom = $recordscount;
        }
        //формируем строку запроса
        $select = $this->get_select_listing($conds);
        //определяем порядок сортировки
        $sort = 'name ASC, departmentid ASC, begindate ASC, eduweeks ASC, status ASC';
        // возвращаем ту часть массива записей таблицы, которую нужно
        return $this->dof->storage($this->code())->get_list_select($select, $sort, '*', $limitfrom, $limitnum);
    }
    
    /**Возвращает фрагмент sql-запроса после слова WHERE
     * @param object $inputconds - список полей с условиями запроса в формате "поле_БД->значение" 
     * @return string
     */
    public function get_select_listing($inputconds)
    {
        dof_debugging('im/ages get_listing.Метод перенесен в storage', DEBUG_DEVELOPER);
        // создадим массив для фрагментов sql-запроса
        $selects = array();
        $conds = fullclone($inputconds);
        // теперь создадим все остальные условия
        foreach ( $conds as $name=>$field )
        {
            if ( $field )
            {// если условие не пустое, то для каждого поля получим фрагмент запроса
                $selects[] = $this->dof->storage('programms')->query_part_select($name,$field);
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
     * Возвращает объект приказа
     *
     * @param string $code
     * @param integer  $id
     * @return dof_storage_orders_baseorder
     */
    public function order($code, $id = NULL)
    {
        require_once($this->dof->plugin_path('im',$this->code(),'/order/change_status.php'));
        switch ($code)
        {
            case 'change_status':
                $order = new dof_im_ages_order_change_status($this->dof);
                if ( ! is_null($id))
                {// нам передали id, загрузим приказ
                    if ( ! $order->load($id))
                    {// Не найден
                        return false;
                    }
                }
                // Возвращаем объект
                return $order;
            break;
        }
    }   
    
}