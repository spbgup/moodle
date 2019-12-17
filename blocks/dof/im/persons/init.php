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


/** Класс плагина адресной книги
 * 
 */
class dof_im_persons implements dof_plugin_im
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
        return 2012112000;
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
        return 'persons';
    }
    /** Возвращает список плагинов, 
     * без которых этот плагин работать не может
     * @return array
     * @access public
     */
    public function need_plugins()
    {
        return array('modlib'=>array('nvg'     => 2008102300),
                     'storage'=>array('persons' => 2010061600,
                                     'acl'     => 2011040504));
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
        if ( $this->dof->is_access('datamanage') OR $this->dof->is_access('manage') 
             OR $this->dof->is_access('admin') )
        {//если глобальное право есть - пропускаем';
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
        if (!$this->is_access($do, $objid, $userid))
        {
            $link = "{$this->type()}/{$this->code()}:{$do}";
            $notice = "persons/{$do} (block/dof/im/persons: {$do})";
            if ($objid){$notice.="#{$objid}";}
            $this->dof->print_error('nopermissions',$link,$notice);  
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
            if ( $mixedvar['storage'] == 'persons' )
            {
                if ( isset($mixedvar['action']) AND $mixedvar['action'] == 'view' )
                {// Получение ссылки на просмотр объекта
                    $params = array('id' => $intvar);
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
    /**Возвращает содержимое блока, отображаемого на страницах fdo
     * @param string $name - название набора текстов для отображания
     * @param int $id - id текста в наборе
     * @return string - html-код текста
     */
    function get_block($name, $id = 1)
    {
        $depid = optional_param('departmentid', 0, PARAM_INT);
        $addvars = array();
        $addvars['departmentid'] = $depid;
        switch ($name)
    	{
       		case 'main':
				$str = '';
				if ($this->dof->storage('persons')->is_access('create'))
				{
					$str = $str.'<a href="'.$this->dof->url_im('persons/edit.php','',$addvars).'">'
							.$this->dof->get_string('createperson', 'persons').'</a>';
				}
				if ($this->dof->storage('persons')->is_access('view'))
				{
				    if ($str)
				    {
				        $str .= "\n<br />";
				    }
       		 		$str = $str.'<a href="'.$this->dof->url_im('persons/list.php','',$addvars).'">'
							.$this->dof->get_string('listpersons', 'persons').'</a><br>';
				    $str = $str.'<a href="'.$this->dof->url_im('persons/search.php','',$addvars).'">'
							.$this->dof->get_string('searchperson', 'persons').'</a>';
				}
				return $str;
		}
    }
    /** Возвращает содержимое секции
     * @param string $name - название набора текстов для отображания
     * @param int $id - id текста в наборе
     * @return string  - html-код названия секции
     */
    function get_section($name, $id = 1)
    {
        return '';
    }
     /** Возвращает текст для отображения в блоке на страницах MOODLE 
     * @return string  - html-код для отображения
     */
    public function get_blocknotes($format='other')
    {
        if ($this->dof->is_access('view'))
        {
            return "";
        }else
        {
            return '';
        }
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
        $result->departmentid = optional_param('departmentid', 0, PARAM_INT);
        $result->objectid     = $objectid;
        if ( ! $objectid )
        {// если objectid не указан - установим туда 0 чтобы не было проблем с sql-запросами
            $result->objectid = 0;
        }else
        {// если указан - то установим подразделение
            $result->departmentid = $this->dof->storage('persons')->get_field($objectid, 'departmentid');
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
     * Возвращает полное имя пользователя в формате ФИО и ссылку
     * @param $peronid - id записи пользователя
     * @param $islink - имя должно быть ссылкой
     * @param $peronobj - готовый объект для уменьшения кол-ва запросов
     * @return string - полное имя пользователя или 
     * пустая строка, если пользователь не найден
     */
    public function get_fullname($personid,$islink=false,$personobj=null,$depid=null)
    {
        if (is_object($personobj) AND isset($personobj->firstname) AND isset($personobj->lastname)
            AND isset($personobj->middlename))
        {
            // Объект есть и там все, что требуется
            $personorid = $personobj;
        }else
        {
            // Запросим данные по id
            $personorid = $personid;
        };
        $fullname = $this->dof->storage('persons')->get_fullname($personorid);
        if ($islink)
        {
	        if ( is_null($depid) )
	        {// id подразделения не  передано
	            $depid = optional_param('departmentid', 0, PARAM_INT);
	        }
	        return "<a href=\"{$this->dof->url_im('persons', '/view.php',
	                        array('id'=>$personid,'departmentid'=>$depid))}\">{$fullname}</a>";
        }else
        {
            return $fullname;
        }
    }
    
    /**
     * Отобразить информацию по персоне
     */
    function show_person($id,$addvars)
    {
        global $DOF,$CFG;
        if (!$person = $DOF->storage('persons')->get($id))
        {
            return false;
        }
        // Рисуем таблицу
        $table = new object();
        $table->data = array();
        $table->data[] = array($DOF->get_string('fullname', 'sel'),"{$person->firstname} {$person->middlename} {$person->lastname}");
        $table->data[] = array($DOF->get_string('email', 'sel'),"{$person->email}");
        $table->data[] = array($DOF->get_string('gender', 'sel'),"{$person->gender}");
        $table->data[] = array($DOF->get_string('dateofbirth', 'sel'),
                         dof_userdate($person->dateofbirth,'%d-%m-%Y'));
        $table->data[] = array($DOF->get_string('phonehome', 'sel'),$person->phonehome);
        $table->data[] = array($DOF->get_string('phonework', 'sel'),$person->phonework);
        $table->data[] = array($DOF->get_string('phonecell', 'sel'),$person->phonecell);
        if (($person->passtypeid == 0) or (!isset($person->passtypeid)))
        {
            $type = $DOF->get_string('nonepasport', 'sel');
        } else
        {
            $type = $DOF->modlib('refbook')->pasport_type($person->passtypeid);
        }
        $table->data[] = array($DOF->get_string('passtypeid', 'sel'),$type);
        $table->data[] = array($DOF->get_string('passportserial', 'sel'),$person->passportserial);
        $table->data[] = array($DOF->get_string('passportnum', 'sel'),$person->passportnum);
        $table->data[] = array($DOF->get_string('passportdate', 'sel'),
                         dof_userdate($person->passportdate,'%d-%m-%Y'));
        $table->data[] = array($DOF->get_string('passportem', 'sel'),$person->passportem);
        if (isset($person->passportaddrid))
        {
            $addres = $DOF->storage('addresses')->get($person->passportaddrid);
            $table->data[] = array($DOF->get_string('addrcountry', 'sel'),$addres->country);
            if (isset($addres->region))
            {
                $addres->region = $DOF->modlib('refbook')->region($addres->country,$addres->region);
            }
            $table->data[] = array($DOF->get_string('addrregion', 'sel'),$addres->region);
            $table->data[] = array($DOF->get_string('addrpostalcode', 'sel'),$addres->postalcode);
            $table->data[] = array($DOF->get_string('addrcounty', 'sel'),$addres->county);
            $table->data[] = array($DOF->get_string('addrcity', 'sel'),$addres->city);
            $table->data[] = array($DOF->get_string('addrstreetname', 'sel'),$addres->streetname);
            $table->data[] = array($DOF->get_string('addrstreettype', 'sel'),$addres->streettype);
            $table->data[] = array($DOF->get_string('addrnumber', 'sel'),$addres->number);
            $table->data[] = array($DOF->get_string('addrgate', 'sel'),$addres->gate);
            $table->data[] = array($DOF->get_string('addrfloor', 'sel'),$addres->floor);
            $table->data[] = array($DOF->get_string('addrapartment', 'sel'),$addres->apartment);
        }
        if( isset($person->departmentid) AND $person->departmentid AND $department = $this->dof->storage('departments')->get($person->departmentid) )
        {
            $table->data[] = array($DOF->get_string('department', 'sel'), $department->name.'['.$department->code.']');
        }
        //по умолчанию установим пустые строки
        //организация
        $orgname = '';
        //должность
        $post = '';
        //получим назначение на должность и возьмем оттуда организацию и должность
        $workplace = $DOF->storage('workplaces')
                ->get_record(array('personid' => $id,'statuswork' => 'active'), 'id, organizationid, post');
        //если для пользователя найдено назначение на должность        
        if (!empty($workplace))
        {
            //если задана организация-заносим в переменную для вывода
            if (!empty($workplace->organizationid))
            {
                $organization = $DOF->storage('organizations')->get($workplace->organizationid, 'shortname');
                $orgname = $organization->shortname;       
            }
            //если задана должность-заносим в переменную для вывода
            if ( !empty($workplace->post))
            {
                $post = $workplace->post;  
            }
        }
        
        $table->data[] = array($DOF->get_string('organization', 'sel'), $orgname);
        $table->data[] = array($DOF->get_string('workplace', 'sel'), $post);
        
        if ( $person->sync2moodle )
        {// если пользователь синхронизирован с moodle - напишем "да"
            $sync2moodle = $this->dof->modlib('ig')->igs('yes');
        }else
        {// в противном случае - напишем "нет"
            $sync2moodle = $this->dof->modlib('ig')->igs('no');
        }
        $table->data[] = array($DOF->get_string('sync2moodle', 'sel'), $sync2moodle);
        if ($person->mdluser)
        {
            $table->data[] = array($DOF->get_string('moodleuser', 'sel'),
                    "<a href='{$CFG->wwwroot}/user/view.php?id={$person->mdluser}&course=1'>{$person->mdluser}</a>");
        }
        $table->data[] = array($DOF->get_string('adddate', 'sel'),
                         dof_userdate($person->adddate,'%d-%m-%Y %H:%M:%S'));
        if ($this->is_access('viewaccount'))
        {
            $table->data[] = array('id',"<a href='{$this->dof->url_im('persons',"/view.php?id={$person->id}",$addvars)}'>{$person->id}</a>");
        }else
        {
            $table->data[] = array('personid',$person->id);
        }
        if ($person->status == 'deleted')
        {// @todo заменить когда у персон будет нормальный плагин смены статусов
            $table->data[] = array($DOF->modlib('ig')->igs('status'),'Удаленный');
        }
        // часовой пояс
        if ( isset($person->mdluser) )
        {
            $UTC = $this->dof->sync('personstom')->get_usertimezone($person->mdluser);
        }else
        {
            $UTC = '';
        }
        $table->data[] = array($DOF->get_string('time_zone','persons'), $UTC );
        // Договора и другое
        $table->data[] = array($DOF->modlib('ig')->igs('other_sr'),
                                "<a href='{$this->dof->url_im('sel',"/contracts/list.php?personid={$person->id}",$addvars)}'>
                                {$DOF->get_string('view_contracts','persons')}</a><br>
                                <a href='{$this->dof->url_im('employees',"/list.php?personid={$person->id}",$addvars)}'>
                                {$DOF->get_string('view_employees','persons')}</a><br>
                                <a href='{$this->dof->url_im('journal',"/person.php?personid={$person->id}",$addvars)}'>
                                {$DOF->get_string('info_recordbook','persons')}</a>");
    //  $table->data[] = array($DOF->get_string('statusdate', 'sel'),date('d-m-Y H:i:s',$person->statusdate));
    //  $table->data[] = array($DOF->get_string('status', 'sel'),$person->status);
        $table->tablealign = "center";
        $table->align = array ("left","left");
        $table->wrap = array ("","");
        $table->cellpadding = 5;
        $table->cellspacing = 0;
        $table->width = '600';
        $table->size = array('200px','400px');
        // $table->head = array('', '');
        $this->dof->modlib('widgets')->print_table($table);
        return true;    
    }
    /**
     * Отобразить список персон
     */
    function show_list($list,$addvars,$options=null)
    {
        // Собираем данные
        $data = array();
        if (!is_array($list))
        {// не получили список пользователей
            print('<p align="center"><i>('.$this->dof->get_string('persons_list_is_empty', 'persons').')</i></p>');
            return false;
        }
        foreach ($list as $obj)
        {
            $link = '';
            if ( $this->is_access('deleteperson',$obj->id) AND $obj->status != 'deleted' )
            {
                $link = '<a href='.$this->dof->url_im('persons','/delete.php?personid='.$obj->id,$addvars).'><img src="'.
                $this->dof->url_im('persons', '/icons/delete.png').'" alt="'.$this->dof->modlib('ig')->igs('delete').
                '" title="'.$this->dof->modlib('ig')->igs('delete').'"></a>&nbsp;';
            }
            if ( $this->is_access('archiveperson',$obj->id) AND $obj->status != 'archived' )
            {
                $link .= '<a href='.$this->dof->url_im('persons','/archive.php?personid='.$obj->id,$addvars).'><img src="'.
                $this->dof->url_im('persons', '/icons/archive.png').'" alt="'.$this->dof->modlib('ig')->igs('archive').
                '" title="'.$this->dof->modlib('ig')->igs('archive').'"></a>&nbsp;';
            }
            $check = '';
            if ( is_array($options) )
            {// добавляем галочки
                $check = '<input type="checkbox" name="'.$options['prefix'].'_'.
                $options['listname'].'['.$obj->id.']" value="'.$obj->id.'"/>';
            }            
            $data[] = array($check, $link, "<a href='{$this->dof->url_im('persons',"/view.php?id={$obj->id}",$addvars)}'>{$obj->id}</a>",
                            "<a href='{$this->dof->url_im('persons',"/view.php?id={$obj->id}",$addvars)}'>{$obj->lastname}</a>",
                            $obj->firstname,
                            $obj->middlename,
                            $obj->email);
        }
        // Рисуем таблицу
        $table = new object();
        $table->tablealign = "center";
        // $table->align = array ("center","center","center", "center", "center");
        // $table->wrap = array ("nowrap","","","");
        $table->cellpadding = 5;
        $table->cellspacing = 0;
        $table->width = '600';
        $table->head = array('', $this->dof->get_string('actions','persons'),
                             $this->dof->get_string('id','persons'),
                             $this->dof->get_string('lastname','persons'),
                             $this->dof->get_string('firstname','persons'),                             
                             $this->dof->get_string('middlename','persons'),
                             $this->dof->get_string('email','persons') );;
        $table->data = $data;
        //передали данные в таблицу
        $this->dof->modlib('widgets')->print_table($table);
    }

    /** Проверить права через старую систему полномочий (пока оставлено для совместимости)
     * 
     * @todo избавится от этой функции после полного перехода на новую систему полномочий
     * @deprecated Эта функция существует здесь для совместимости со старой системой прав
     * 
     * @param string $do - идентификатор действия, которое должно быть совершено
     * @param int $id_obj - идентификатор экземпляра объекта, 
     * по отношению к которому это действие должно быть применено
     * @param int $userid - идентификатор пользователя в Moodle, полномочия которого проверяются
     * @return bool true - можно выполнить указанное действие по отношению к выбранному объекту
     * false - доступ запрещен
     */    
    protected function old_access_check($do, $objid = NULL, $userid = NULL)
    {
        // Просмотр списка персон
        switch ($do)
        {
            //просмотр списка персон
            case 'viewpersonslist':return $this->is_access('viewaccount',null,$userid);
            //просмотр персоны
            case 'viewperson':
                // Можно администраторам с соответствующим полномочием
                if ($this->is_access('viewaccount',null,$userid))
                {    
                    return true;
                }
            
                // Еще можно кураторам, если персона упоминается хотя бы в одном их договоре
                // Проверяем, является ли запросивший - куратором договора с данной персоной
                if (!$this->is_access('openaccount',NULL,$userid))
                {
                    // Если он не куратор - дальше проверять нет смысла
                    return false;
                }
                // Получаем id персоны, соответсвующей текущему пользователю
                if (!$personid = $this->dof->storage('persons')->get_by_moodleid_id($userid))
                {
                    // Дальше проверять нечего: если не записан в персоны, то и курируемых договоров нет
                    return false;
                }
                // Получаем клиентские и студенческие контракты проверяемой персоны
                $contracts = array();
                $contracts += $this->dof->storage('contracts')->get_list_by_client($objid);
                $contracts += $this->dof->storage('contracts')->get_list_by_student($objid);
                foreach ($contracts as $contract)
                {
                    // if ($this->dof->storage('contracts')->is_seller($contract->id,$this->dof->storage('persons')->get_by_moodleid_id($userid)))
                    if ($contract->sellerid === $personid)
                    {
                        // Является куратором договора - все хорошо
                        return true;
                    }
                }
                // Никакой он не куратор
                return false;
            // Регистрация персоны
            case 'createperson':
                // id персоны пока игнорируем, и проверяем право редактировать всех
                return $this->is_access('manageaccount',null,$userid);
            // Редактирование персоны
            case 'editperson':
                // id персоны пока игнорируем, и проверяем право редактировать всех
                return $this->is_access('manageaccount',null,$userid);
            // Редактирование синхронизации с Moodle
            case 'managemdlsync':
                return $this->is_access('datamanage',null,$userid);
            // Удаление персоны деканата
            case 'deleteperson':
                if ( $this->dof->storage('contracts')->is_person_used($objid) )
                {// нельзя удалять персоны - у которых есть активные контракты
                    return false;
                }
                return $this->is_access('datamanage');
            case 'archiveperson':
                if ( $this->dof->storage('contracts')->is_person_used($objid) )
                {// нельзя удалять персоны - у которых есть активные контракты
                    return false;
                }
                return $this->is_access('datamanage');
            default: return false;
        }
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
        // получаем все нужные параметры для функции проверки прав
        $acldata = $this->get_access_parametrs($do, $objectid, $userid);   
             
        switch ( $do )
        {// определяем дополнительные параметры в зависимости от запрашиваемого права
            //просмотр списка персон
            case 'viewpersonslist':
                $acldata->code = 'view';
                $acldata->objectid = 0;
                break;
            //просмотр персоны
            case 'viewperson':
                $acldata->code = 'view';
                break;
            // Регистрация персоны
            case 'createperson':
                $acldata->code = 'create';
                break;
            // Редактирование персоны
            case 'editperson':
                $acldata->code = 'edit';
                break;
            // Редактирование синхронизации с Moodle
            case 'managemdlsync':
                $acldata->code = 'edit:sync2moodle';
                $acldata->type = 'im';
                break;
            // Удаление персоны деканата
            case 'deleteperson':
            case 'archiveperson':
                $acldata->code = 'changestatus';
                if ( $this->dof->storage('contracts')->is_person_used($objectid) )
                {// нельзя удалять персоны - у которых есть активные контракты
                    return false;
                } 
                break;

            // для некоторых прав название полномочия заменим на стандартное, для совместимости
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
                
                // если указан - то получим контракт (с другими типами объектов мы в этом плагине не работаем)
                if ( $object = $this->dof->storage('contracts')->get($objectid) )
                {
                    if ( $userid == $object->sellerid )
                    {// пользователь является законным представителем 
                        $acldata->code = 'view/sellerid';
                        if ( $this->acl_check_access_paramenrs($acldata) )
                        {// законным представителям разрешено просматривать договоры
                            return true;
                        }
                    }
                }    

            }
        }        
        
        // проверка
        return false;
    }
}   

?>