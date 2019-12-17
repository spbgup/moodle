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

/** Изучаемые и пройденные курсы
 * 
 */
class dof_im_cpassed implements dof_plugin_im
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
        return 2012102800;
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
        return 'cpassed';
    }
    /** Возвращает список плагинов, 
     * без которых этот плагин работать не может
     * @return array
     * @access public
     */
    public function need_plugins()
    {
        return array('modlib'=>array('nvg'      => 2008060300,
                                     'widgets'  => 2009050800),
                                     
                     'storage'=>array('persons'       => 2009060400,
                                      'ages'          => 2009050600,
                                      'cpassed'       => 2009101900,
                                      'programmitems' => 2009060800,
                                      'programms'     => 2009040800,
                                      'programmsbcs'  => 2009052900,
                                      'contracts'     => 2009101200,
        							  'acl'           => 2011040504) );
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
        if ( ! $this->is_access($do, $objid, $userid) )
        {
            $notice = "cpassed/{$do} (block/dof/im/cpassed: {$do})";
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
        if ( $gentype == 'im' AND $gencode == 'obj' AND $eventcode == 'get_object_url' )
        {
            if ( $mixedvar['storage'] == 'cpassed' )
            {
                if ( isset($mixedvar['action']) AND $mixedvar['action'] == 'view' )
                {// Получение ссылки на просмотр объекта
                    $params = array('cpassedid' => $intvar);
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
     * @param dof_control $dof 
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
     * @todo разобраться с правами доступа
     * @param string $name - название набора текстов для отображания
     * @param int $id - id текста в наборе
     * @return string - html-код содержимого блока
     */
    public function get_block($name, $id = 1)
    {
        $rez = '';
        $depid = optional_param('departmentid', 0, PARAM_INT);
        $addvars = array();
        $addvars['departmentid'] = $depid;
        switch ($name)
        {
            case 'main':
                $path = $this->dof->url_im('cpassed','/index.php',$addvars);
//                $rez .= "<a href=\"{$path}\">".$this->dof->get_string('title', 'ages').'</a>';
//                $rez .= "<br />";
                if ( $this->is_access('viewall') )
                {//может видеть все классы
                    $path = $this->dof->url_im('cpassed','/list.php',$addvars);
                }
                //ссылка на список подписок на курсы
                $rez .= "<a href=\"{$path}\">".$this->dof->get_string('list', 'cpassed').'</a>';
                if ( $this->is_access('addcpassed') )
                {//может создавать подписку на курс - покажем ссылку
                    $rez .= "<br />";
                    $path = $this->dof->url_im('cpassed','/edit.php',$addvars);
                    $rez .= "<a href=\"{$path}\">".$this->dof->get_string('new', 'cpassed').'</a>';
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
    public function get_section($name, $id = 1)
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
        return "<a href='{$this->dof->url_im('cpassed','/index.php')}'>"
                    .$this->dof->get_string('page_main_name')."</a>";
    }
    
    // ***********************************************************
    //       Методы для работы с полномочиями и конфигурацией
    // ***********************************************************   
    
        /** Получить список параметров для фунции has_hight()
     * 
     * @return object - список параметров для фунции has_hight()
     * @param string $action - совершаемое действие
     * @param int $objectid - id объекта над которым совершается действие
     * @param int $userid - id пользователя в таблице persons 
     */
    protected function get_access_parametrs($action, $objectid, $userid)
    {
        $result = new object();
        // чаще всего будем запрашивать полномочие из хранилища
        $result->plugintype   = $this->type();
        $result->plugincode   = $this->code();
        $result->code         = $action;
        $result->userid       = $userid;
        $result->departmentid = optional_param('departmentid', 0, PARAM_INT);
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
     * информации о подписке на курс
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
   		$data = $this->get_string_table($obj,$conds,false,'single');
    	// выводим таблицу на экран
        return $this->print_single_table($data,'single');
    }
    
    /**
     * Возвращает html-код отображения 
     * информации о подписке на курс
     * @param int $id - id записи из таблицы
     * @return mixed string html-код или false в случае ошибки
     */
    public function show_id($id,$conds)
    {
        if ( ! is_int_string($id) )
        {//входные данные неверного формата 
            return false;
        }
    	if ( ! $obj = $this->dof->storage('cpassed')->get($id) )
    	{// курс не найден
    		return false;
    	} 
    	return $this->show($obj,$conds);
    }
    
    /**
     * Возвращает html-код отображения 
     * информации о нескольких подписках на курсы
     * @param массив $list - массив записей 
     * периодов, которые надо отобразить 
     * @param bool $flag - показывать все или часть(для стр list)
     * @return mixed string в string html-код или false в случае ошибки
     */
    public function showlist($list,$conds,$flag=false,$addvars)
    {
        if ( ! is_array($list))
        {// переданны данные неверного формата
        	return false;
        }
        
        $data = array();
        $delpass = false;
        // если указано, что надо показать галочки удаления
        if (isset($list['delpass']))
        {// зафиксируем это
            $delpass = $list['delpass'];
            // удалим воизбежание конфликта с перебором массива
            unset($list['delpass']);
        }
    	// заносим данные в таблицу
    	foreach ($list as $obj)
    	{   
    	    // укажем что к объекту надо прикрепить галочку
    	    $obj->delpass = $delpass;
    	    $data[] = $this->get_string_table($obj,$conds,$flag);
    	}
    	
    	// выводим таблицу на экран
        return $this->print_table($data,$flag,$conds);
    }
    
    /**
     * Возвращает форму создания/редактирования с начальными данными
     * @param int $id - id записи, значения 
     * которой устанавливаются в поля формы по умолчанию
     * @return moodle quickform object
     */
    public function form($id = NULL, $type = 'edit',$csid = 0)
    {
        global $USER;
        // устанавливаем начальные данные
        if (isset($id) AND ($id <> 0) )
        {// id передано
            $cpassed = $this->dof->storage('cpassed')->get($id); 
        }else
        {// id не передано
            $cpassed = $this->form_new_data($csid);
        }
        if ( isset($USER->sesskey) )
        {//сохраним идентификатор сессии
            $cpassed->sesskey = $USER->sesskey;
        }else
        {//идентификатор сессии не найден
            $cpassed->sesskey = 0;
        }
        $customdata = new stdClass();
        $customdata->cpassed = $cpassed;
        $customdata->dof = $this->dof;
        // подключаем методы вывода формы
        if ( $type != 'pitem' )
        {
            $form = new dof_im_cpassed_edit_form(null,$customdata);
        }else
        {
            $form = new dof_im_cpassed_edit_pitem_form(null,$customdata);
        }
        // очистим статус, чтобы он не отображался
        // английскими буквами как в БД
        unset($cpassed->status);
        // заносим значения по умолчению
        $form->set_data($cpassed); 
        // возвращаем форму
        return $form;
    }
    
    /**
     * Возвращает исходные данные для формы создания подписки
     * @return stdclassObject
     */
    private function form_new_data($csid)
    {
        $formdata = new object;
        $formdata->id             = 0;
        $formdata->ageid          = 0;
        $formdata->programmitemid = 0;
        $formdata->teacherid      = 0;
        $formdata->studentid      = 0;
        $formdata->cstreamid      = 0;
        $formdata->agroupid       = 0;
        if ( $csid )
        {
            $formdata->cstreamid = $csid;
            $formdata->programmitemid = $this->dof->storage('cstreams')->get_field($csid,'programmitemid');
            $formdata->ageid = $this->dof->storage('cstreams')->get_field($csid,'ageid');
        }
        return $formdata;
    }
    
   /** Возвращает html-код таблицы
     * @param array $date - данные в таблицу
     * @param bool $flag - показывать все или часть(для стр list)
     * @return string - html-код или пустая строка
     */
    private function print_table($date,$flag=false,$addvars)
    {
        // рисуем таблицу
        $table = new object();
        $table->tablealign = "center";
        $table->cellpadding = 5;
        $table->cellspacing = 5;
        //$table->size = array ('100px','150px','150px','200px','150px','100px');
        $table->wrap = array ("","","","","","","","","","nowrap");
        $table->align = array ("center","center","center","center",
                                "center","center","center","center","center","center");
        // шапка таблицы
        // @todo занести сюда графу "задание в moodle" когда будет реализована синхронизация
        $table->head = $this->get_fields_description($flag,$addvars);
        // заносим данные в таблицу     
        $table->data = $date;
        return $this->dof->modlib('widgets')->print_table($table,true);
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
    
    /** Получить заголовок для списка таблицы, или список полей
     * для списка отображения одного объекта 
     * @param bool $flag - показывать все или часть(для стр list)
     * @return array
     */
    private function get_fields_description($flag=false,$addvars='')
    { 
        if ( $flag )
        {
            return array($this->dof->get_string('actions','cpassed'),
              "<a href='".$this->dof->url_im('cpassed','/list.php?sort=sortname',$addvars)."'>".     $this->dof->get_string('student','cpassed')."</a>",       
         	  "<a href='".$this->dof->url_im('cpassed','/list.php?sort=sortprogramm',$addvars)."'>". $this->dof->get_string('programmitem','cpassed')."</a>",            
			  "<a href='".$this->dof->url_im('cpassed','/list.php?sort=sortagenum',$addvars)."'>".   $this->dof->get_string('agenum','cpassed')."</a>",           
              "<a href='".$this->dof->url_im('cpassed','/list.php?sort=sortagroup',$addvars)."'>".   $this->dof->get_string('agroup','cpassed')."</a>",  
              "<a href='".$this->dof->url_im('cpassed','/list.php?sort=sortage',$addvars)."'>".      $this->dof->get_string('age','cpassed')."</a>",       
         	  "<a href='".$this->dof->url_im('cpassed','/list.php?sort=sortstatus',$addvars)."'>".   $this->dof->get_string('status','cpassed')."</a>");              
        }else 
        {
            return array($this->dof->get_string('actions','cpassed'),
            		 $this->dof->get_string('age','cpassed'),
                     $this->dof->get_string('programmitem','cpassed'),
                     $this->dof->get_string('student','cpassed'),
                     $this->dof->get_string('agenum','cpassed'),
                     $this->dof->get_string('agroup','cpassed'),
                     $this->dof->get_string('grade','cpassed'),
                     $this->dof->get_string('gradelevel_thead','cpassed'),
                     $this->dof->get_string('credit','cpassed'),
                     $this->dof->get_string('notice','cpassed'),
                     $this->dof->get_string('teacher','cpassed'),
                     $this->dof->get_string('status','cpassed'),
                     $this->dof->get_string('begindate','cpassed'),
                     $this->dof->get_string('enddate','cpassed'));            
        }
    }
    
    /** Возвращает массив для вставки в таблицу
     * @param object $obj
     * $param bool $flag   - показывать все или часть(для стр list) 
     * @return array
     */
    private function get_string_table($obj,$conds,$flag=false)
    {
        // для ссылок вне плагина
        $conds = (array) $conds;
        $outconds = array();
        $outconds['departmentid'] = $conds['departmentid'];
    	if ( ! $agename = $this->dof->storage('ages')->get_field($obj->ageid, 'name') )
   		{//номера периода нет - выведем пустую строчку
   		    $agename = '&nbsp;';
   		}elseif( $this->dof->storage('ages')->is_access('view',$obj->ageid) )
   		{
   		    $agename = "<a href='".$this->dof->url_im('ages','/view.php?ageid='.$obj->ageid,$outconds)."'>".$agename."</a>";
   		}  
        if ( ! $programmitem = $this->dof->storage('programmitems')->get($obj->programmitemid) )
        {//названия предмета нет - выведем пустую строчку
            $programmitemname = '&nbsp;';
        }else
        {// название предмета есть - выведем его вместе с кодом
            $programmitemname = $programmitem->name.' (<i>'.$programmitem->code.'</i>)';
            if ( $this->dof->storage('programmitems')->is_access('view',$obj->programmitemid) )
            {
                $programmitemname = "<a href='".$this->dof->url_im('programmitems','/view.php?pitemid='.$obj->programmitemid,$outconds)."'>".$programmitemname."</a>";
            }
        }
        if ( ! $teachername = $this->dof->storage('persons')->get_fullname($obj->teacherid) )
        {//имени учителя нет - выведем пустую строчку
            $teachername = '&nbsp;';
        }elseif( $this->dof->storage('persons')->is_access('view',$obj->teacherid) )
        {
            $teachername = "<a href='".$this->dof->url_im('persons','/view.php?id='.$obj->teacherid,$outconds)."'>".$teachername."</a>";
            $appointmentid = $this->dof->storage('cstreams')->get_field($obj->cstreamid, 'appointmentid');
           	// добавим просмотр табельного номера
            if ( $this->dof->storage('appointments')->is_access('view',$appointmentid) AND $appointmentid )
            {
                $imgapp = '<img src="'.$this->dof->url_im('cpassed', '/icons/view-eagreement.png').'"
                    alt="'.$this->dof->get_string('appointment','cpassed').'" title="'.$this->dof->get_string('appointment','cpassed').'">';       		    
       		    $teachername .= '<br><a href='.$this->dof->url_im('employees','/view_appointment.php?id='.$appointmentid,$outconds).'>
       		    '.$imgapp.'</a>';
       		}
        }
        
        if ( ! $studentname = $this->dof->storage('persons')->get_fullname($obj->studentid) )
        {//ученик не указан - выведем пустую строчку
            $studentname = '&nbsp;';
        }elseif( $this->dof->storage('persons')->is_access('view',$obj->studentid) ) 
        {
            $studentname = "<a href='".$this->dof->url_im('persons','/view.php?id='.$obj->studentid,$outconds)."'>".$studentname."</a>";
        }
        //получим название статуса
   		if ( ! $statusname = $this->dof->workflow('cpassed')->get_name($obj->status) )
        {//статуса нет - выведем пустую строчку
            $statusname = '&nbsp;';
        }
        //получим параллеь
   		if ( ! $agenum = $this->dof->storage('programmsbcs')->get_field($obj->programmsbcid,'agenum') )
        {//статуса нет - выведем пустую строчку
            $agenum = '&nbsp;';
        }        
   		//получаем ссылки на картинки
        $imgedit = '<img src="'.$this->dof->url_im('cpassed', '/icons/edit.png').'"
            alt="'.$this->dof->get_string('edit', 'cpassed').'" title="'.$this->dof->get_string('edit', 'cpassed').'">';
        $imgview = '<img src="'.$this->dof->url_im('cpassed', '/icons/view.png').'" 
            alt="'.$this->dof->get_string('view', 'cpassed').'" title="'.$this->dof->get_string('view', 'cpassed').'">';
        $imgsbc = '<img src="'.$this->dof->url_im('cpassed', '/icons/programmsbcs.png').'" 
            alt="'.$this->dof->get_string('view_sbcs', 'cpassed').'" title="'.$this->dof->get_string('view_sbcs', 'cpassed').'">';
        $imgcstream = '<img src="'.$this->dof->url_im('cpassed', '/icons/cstreams.png').'" 
            alt="'.$this->dof->get_string('view_cstream', 'cpassed').'" title="'.$this->dof->get_string('view_cstream', 'cpassed').'">';        
        
        // добавляем ссылку
        $actions = '';
        if ( $this->dof->storage('cpassed')->is_access('edit', $obj->id) )
        {//покажем ссылку на страницу редактирования
            $actions .= '<a href='.$this->dof->url_im('cpassed','/edit_pitem.php?cpassedid='.
            $obj->id, $conds).'>'.$imgedit.'</a>&nbsp;';
        }
        if ( $this->dof->storage('cpassed')->is_access('view', $obj->id) )
        {//покажем ссылку на страницу просмотра
            $actions .= '<a href='.$this->dof->url_im('cpassed','/view.php?cpassedid='.
            $obj->id,$conds).'>'.$imgview.'</a>&nbsp;';
        }
        if ( $this->dof->storage('programmsbcs')->is_access('view', $obj->programmsbcid) )
        {//покажем ссылку на страницу просмотра
            $actions .= '<a href='.$this->dof->url_im('programmsbcs','/view.php?programmsbcid='.
            $obj->programmsbcid,$outconds).'>'.$imgsbc.'</a>&nbsp;';
        }
        if ( $this->dof->storage('cstreams')->is_access('view', $obj->cstreamid) AND $obj->cstreamid )
        {//покажем ссылку на предмето-класс
            $actions .= '<a href='.$this->dof->url_im('cstreams','/view.php?cstreamid='.
            $obj->cstreamid,$outconds).'>'.$imgcstream.'</a>&nbsp;';
        }        
        
        // галока удаления
        if ( $this->dof->workflow('cpassed')->is_access('changestatus', $obj->id) )
        {// если есть право менять статус подписки
            if ( isset($obj->delpass) AND ($obj->delpass <> false) AND ($obj->status <> 'canceled'))
            {// если она указана и статус подписки еще не отменен
                $actions .= '<input type="checkbox" name="delpass['.$obj->id.']">'.$this->dof->get_string('to_tromwrite', 'cpassed');
            }
        }
        $group = '&nbsp;';
        if ( isset($obj->agroupid) AND $obj->agroupid )
        {
            $group = $this->dof->storage('agroups')->get_field($obj->agroupid, 'name');
            if ( $this->dof->storage('agroups')->is_access('view',$obj->agroupid) )
            {
                $group = "<a href='".$this->dof->url_im('agroups','/view.php?agroupid='.$obj->agroupid,$outconds)."'>".$group."</a>";
            }
        }
        // начало и конец обучения
        // делаем для того, чтобы в случае не заполнения даты не выводил 1970 год
        $begindate = $enddate = '';
        if ( $obj->begindate )
        {
            $begindate = dof_userdate($obj->begindate,'%d.%m.%Y');
        }
        if ( $obj->enddate )
        {
            $enddate = dof_userdate($obj->enddate,'%d.%m.%Y');
        }
        // выводим поля в таблицу в нужном порядке и формате
   	    $data = array($actions, $agename, $programmitemname, $studentname,$agenum,$group,
                     $obj->grade, $obj->gradelevel, $obj->credit, $obj->notice,
                     $teachername, $statusname, $begindate, $enddate);
   	    if ( $flag )
   	    {
   	        $data = array($actions, "<a href='".$this->dof->url_im('persons','/view.php',array('id'=>$obj->studentid,
                            'departmentid'=>optional_param('departmentid',0, PARAM_INT)))."'>".$studentname."</a>",
   	                      "<a href='".$this->dof->url_im('programmitems','/view.php',array('pitemid'=>$obj->programmitemid,
                            'departmentid'=>optional_param('departmentid',0, PARAM_INT)))."'>".$programmitemname."</a>",
   	                      $agenum, 
   	                      "<a href='".$this->dof->url_im('agroups','/view.php',array('agroupid'=>$obj->agroupid,
                            'departmentid'=>optional_param('departmentid',0, PARAM_INT)))."'>".$group."</a>",
   	                      "<a href='".$this->dof->url_im('ages','/view.php',array('ageid'=>$obj->ageid,
   	                        'departmentid'=>optional_param('departmentid',0, PARAM_INT)))."'>".$agename."</a>", 
                          $statusname);   	        
   	    }
   	    return $data;
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
        require_once($this->dof->plugin_path('im','cpassed','/order/change_status.php'));
        switch ($code)
        {
            case 'change_status':
                $order = new dof_im_cpassed_order_change_status($this->dof);
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