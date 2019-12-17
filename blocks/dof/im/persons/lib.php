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

//загрузка библиотек верхнего уровня
require_once(dirname(realpath(__FILE__))."/../../lib.php");

// устанавливаем контекст сайта (во всех режимах отображения по умолчанию)
// контекст имеет отношение к системе полномочий (подробнее - см. документацию Moodle)
// поскольку мы не пользуемся контекстами Moodle и используем собственную
// систему полномочий - все действия внутри блока dof оцениваются с точки зрения
// контекста сайта

$PAGE->set_context(context_system::instance());
// эту функцию обязательно нужно вызвать до вывода заголовка на всех страницах
require_login();

$depid = optional_param('departmentid', 0, PARAM_INT);
$addvars = array();
$addvars['departmentid'] = $depid;
//задаем первый уровень навигации
$DOF->modlib('nvg')->add_level($DOF->get_string('title'), $DOF->url_im('standard','/index.php'),$addvars);

// Подключаем библиотеку форм
$DOF->modlib('widgets')->webform();

/*
 * Класс формы для ввода данных договора
 */
class persons_edit_form extends dof_modlib_widgets_form
{
    /**
     * @var dof_control
     */
    protected $dof;
    
    function definition()
    {
        global $DOF;
        $this->dof = $DOF;
        $mform =& $this->_form;
        // Если персона редактируется передаем id персоны
        $id = optional_param('id',null,PARAM_INT);
        if (isset($id))
        {
            $mform->addElement('hidden', 'id',$id);
        }
        // Даем разрешение редактировать поля синхронизации
        if ($DOF->storage('persons')->is_access('edit:sync2moodle'))
        {
        	$mform->addElement('hidden', 'managemdlsync','0');
        }else
        {
        	$mform->addElement('hidden', 'managemdlsync','1');
        }
        $mform->setTYpe('managemdlsync', PARAM_TEXT);
        // обьявляем заголовок формы
        $mform->addElement('header','stheader', $DOF->get_string('person', 'persons'));
        // фамилия, имя, отчество
        $mform->addElement('text', 'lastname', $DOF->get_string('lastname','sel').':', 'size="20"');
        $mform->setType('lastname', PARAM_TEXT);
        $mform->addRule('lastname','Error', 'required',null,'client');
        $mform->addElement('text', 'firstname', $DOF->get_string('firstname','sel').':', 'size="20"');
        $mform->setType('firstname', PARAM_TEXT);
        $mform->addRule('firstname','Error', 'required',null,'client');
        $mform->addElement('text', 'middlename', $DOF->get_string('middlename','sel').':', 'size="20"');
        $mform->setType('middlename', PARAM_TEXT);
        $mform->addRule('middlename','Error', 'required',null,'client');
        // дата рождения
        // выставим дату до 1970 года
        $options = array();
        $options['startyear'] = 1930;
        $options['stopyear']  = dof_userdate(time()-5*365*24*3600,'%Y');
        $options['optional']  = false;
        $mform->addElement('date_selector', 'dateofbirth', $DOF->get_string('dateofbirth', 'sel'), $options);
        
        // пол 
        $displaylist = array();
        $displaylist['unknown'] = $DOF->get_string('unknown','persons');
        $displaylist['male'] = $DOF->get_string('male', 'sel');
        $displaylist['female'] = $DOF->get_string('female', 'sel');
        $mform->addElement('select', 'gender', $DOF->get_string('gender', 'sel').':', $displaylist);
        $mform->setType('gender', PARAM_TEXT);
        $mform->addRule('gender','Error', 'required',null,'client');
        // email
        $mform->addElement('text', 'email', $DOF->get_string('email','sel').':', 'size="20"');
        $mform->setType('email', PARAM_TEXT);
        $mform->addRule('email','Error', 'required',null,'client');
        $mform->addRule('email','Error', 'email',null,'client');
        // страна и регион 
        $choices = get_string_manager()->get_list_of_countries(false);
        $regions = array();
        foreach ($choices as $key => $value)
        {
            $regions += $DOF->modlib('refbook')->region($key);
        }
        $sel =& $mform->addElement('hierselect', 'country', $DOF->get_string('addrcountryregion', 'sel').':');
        
        $sel->setMainOptions($choices);
        $sel->setSecOptions($regions);  
        $mform->addRule('country','Error', 'required',null,'client');
        // телефоны
        $mform->addElement('text', 'phonehome', $DOF->get_string('phonehome','sel').':', 'size="20"');
        $mform->setType('phonehome', PARAM_TEXT);
        $mform->addElement('text', 'phonework', $DOF->get_string('phonework','sel').':', 'size="20"');
        $mform->setType('phonework', PARAM_TEXT);
        $mform->addElement('text', 'phonecell', $DOF->get_string('phonecell','sel').':', 'size="20"');
        $mform->setType('phonecell', PARAM_TEXT);
        // удостоверение личности
        $pass = $DOF->modlib('refbook')->pasport_type();
        $pass['0'] = $DOF->get_string('nonepasport', 'sel');
        ksort($pass);
        // array_push($pass,$DOF->get_string('nonepasport', 'sel'));
        $mform->addElement('select', 'passtypeid', $DOF->get_string('passtypeid', 'sel').':', $pass);
        $mform->setType('passtypeid', PARAM_TEXT);
        //$mform->addRule('stpasstypeid','Error', 'required',null,'client');
        $mform->addElement('text', 'passportserial', $DOF->get_string('passportserial','sel').':', 'size="20"');
        $mform->setType('passportserial', PARAM_TEXT);
        $mform->disabledIf('passportserial', 'passtypeid','eq','0');
        // $mform->addRule('stpassportserial','Error', 'required',null,'client');
        // $mform->addRule('clpassportnum','Error', 'numeric',null,'client');

        $mform->addElement('text', 'passportnum', $DOF->get_string('passportnum','sel').':', 'size="20"');
        $mform->setType('passportnum', PARAM_TEXT);
        $mform->disabledIf('passportnum', 'passtypeid','eq','0');
        // $mform->addRule('stpassportnum','Error', 'required',null,'client');
        // $mform->addRule('stpassportnum','Error', 'numeric',null,'client');
        $options = array();
        $mform->addElement('date_selector', 'passportdate', $DOF->get_string('passportdate', 'sel').':',array('optional'=>false));
        $mform->disabledIf('passportdate', 'passtypeid','eq','0');
        $mform->addElement('text', 'passportem',$DOF->get_string('passportem','sel').':', 'size="20"');
        $mform->setType('passportem', PARAM_TEXT);
        $mform->disabledIf('passportem', 'passtypeid','eq','0');
        // $mform->addRule('stpassportem','Error', 'required',null,'client');
        // адрес
        // индекс
        $mform->addElement('text', 'postalcode', $DOF->get_string('addrpostalcode','sel').':');
        $mform->setType('postalcode', PARAM_TEXT);
        // $mform->addRule('staddrpostalcode','Error', 'required',null,'client');
        // округ/район
        $mform->addElement('text', 'county', $DOF->get_string('addrcounty','sel').':', 'size="20"');
        $mform->setType('county', PARAM_TEXT);
        // Населенный пункт
        $mform->addElement('text', 'city', $DOF->get_string('addrcity','sel').':', 'size="20"');
        $mform->setType('city', PARAM_TEXT);
        // $mform->addRule('staddrcity','Error', 'required',null,'client');
        // название улицы
        $mform->addElement('text', 'streetname', $DOF->get_string('addrstreetname','sel').':', 'size="20"');
        $mform->setType('streetname', PARAM_TEXT);
        // $mform->addRule('staddrstreetname','Error', 'required',null,'client');
        //получим список типов улиц
        if ( ! $street = $DOF->modlib('refbook')->get_street_types() )
        {//не получили
            $street = array();            
        }
        $mform->addElement('select', 'streettype', $DOF->get_string('addrstreettype','sel').':',$street);
        $mform->setType('streettype', PARAM_TEXT);
        // $mform->addRule('staddrstreettype','Error', 'required',null,'client');
        $mform->addElement('text', 'number', $DOF->get_string('addrnumber','sel').':', 'size="20"');
        $mform->setType('number', PARAM_TEXT);
        // $mform->addRule('staddrnumber','Error', 'required',null,'client');
        $mform->addElement('text', 'gate', $DOF->get_string('addrgate','sel').':', 'size="20"');
        $mform->setType('gate', PARAM_TEXT);
        $mform->addElement('text', 'floor', $DOF->get_string('addrfloor','sel').':', 'size="20"');
        $mform->setType('floor', PARAM_TEXT);
        $mform->addElement('text', 'apartment', $DOF->get_string('addrapartment','sel').':', 'size="20"');
        $mform->setType('apartment', PARAM_TEXT);
        $mform->addElement('text', 'latitude', $DOF->get_string('addrlatitude','sel').':', 'size="20"');
        $mform->setType('latitude', PARAM_TEXT);
        $mform->addElement('text', 'longitude', $DOF->get_string('addrlongitude','sel').':', 'size="20"');
        $mform->setType('longitude', PARAM_TEXT);
        $mform->addElement('select', 'departmentid', $DOF->get_string('department','agroups').':', $this->get_departments_list());
        $mform->setType('departmentid', PARAM_INT);
        //$mform->setdefault('departmentid', $mform->_customdata->departmentid);
        $sync = array();
        // поля синхронизации с Moodle
        $sync[] =& $mform->createElement('radio', 'sync2moodle', null, $DOF->modlib('ig')->igs('yes'), 1);
        $sync[] =& $mform->createElement('radio', 'sync2moodle', null, $DOF->modlib('ig')->igs('no'),  0);
        $mform->addGroup($sync, 'sync', $DOF->get_string('sync2moodle', 'sel'), "<br/>", false);
        $mform->disabledIf('sync', 'managemdlsync','eq','1');
        $mform->addElement('text', 'mdluser', $DOF->get_string('moodleuser','sel').':');
        $mform->setType('mdluser', PARAM_INT);
        $mform->disabledIf('mdluser', 'managemdlsync','eq','1');
        // часовой пояс
        // есть права и ЭТА персона синхронизирована с moodle
        if ( $this->dof->storage('persons')->is_access('edit_timezone') AND ! empty($id) 
                AND $this->dof->storage('persons')->get_field($id, 'sync2moodle') )
        {
            $UTC = dof_get_list_of_timezones();
            $mform->addElement('select', 'timezone', $DOF->get_string('time_zone','persons').':',$UTC);
            $mform->disabledIf('timezone', 'sync2moodle','eq','0');
            if ( isset($id) AND $this->dof->storage('persons')->get_field($id, 'sync2moodle') )
            {// редактирование - проверим, синхронизирована ли персона
                $person = $this->dof->storage('persons')->get($id);
                if ( $this->dof->modlib('ama')->user(false)->is_exists($person->mdluser) )
                {// если пользователя существует - выставим его временную зону
                    $mdluser = $this->dof->modlib('ama')->user($person->mdluser)->get();
                    $mform->setDefault('timezone', $mdluser->timezone);  
                }else
                {// нет - по умолчанию на время на сервере
                    $mform->setDefault('timezone','99');  
                }         
            }else 
            {
                $mform->setDefault('timezone','99');   
            }
        }    
        // Кнопка "сохранить"
        $mform->addElement('submit', 'save');
        $mform->setDefault('save', $DOF->modlib('ig')->igs('save'));
        // применение проверки ко всем элементам
        $mform->applyFilter('__ALL__', 'trim');
    }
    /**
     * Задаем проверку корректности введенных значений
     */
    function validation($data,$files)
    {
        global $DOF;
		$r = array();
		if (isset($data['id']))
		{
			$person = $DOF->storage('persons')->get($data['id']);
		    if (($data['email'] <> $person->email) AND !$DOF->storage('persons')->is_email_unique($data['email']))
		    {
			    $r['email'] = 'Email isn\'t unique';
		    }
		    // проверка на лимит
		    $depid = $this->dof->storage('persons')->get_field($data['id'], 'departmentid');
            if ( ! $this->dof->storage('config')->get_limitobject('persons',$data['departmentid']) AND $depid != $data['departmentid'] )
            {
                $r['departmentid'] = $this->dof->get_string('limit_message','persons');
            }
		}else
		{
	        if (isset($data['email']) AND !$DOF->storage('persons')->is_email_unique($data['email']))
		    {
			    $r['email'] = 'Email isn\'t unique';
		    }
        	// проверка на лимит
            if ( ! $this->dof->storage('config')->get_limitobject('persons',$data['departmentid']) )
            {
                $r['departmentid'] = $this->dof->get_string('limit_message','persons');
            }
		}
		if (($data['passtypeid'] <> '0') AND empty($data['passportnum']))
		{
			$r['passportnum'] = 'Error';
		}
        if (($data['passtypeid'] <> '0') AND empty($data['passportem']))
		{
			$r['passportem'] = 'Error';
		}
        if (!empty($data['streetname']) AND empty($data['streettype']))
		{
			$r['streettype'] = 'Error';
			
		}
		if ($data['gender'] == 'unknown')
		{
			$r['gender'] = 'Error';
		}
		
		return $r;
    }

    protected function get_departments_list()
    {
        global $DOF;
        
        $rez = array();
        
        if ( $dep = $DOF->storage('departments')->departments_list_subordinated(null,'0', null,true))
        {//получили список отделов
            // оставим в списке только те объекты, на использование которых есть право
            $permissions = array(array('plugintype'=>'storage', 'plugincode'=>'departments', 'code'=>'use'));
            $dep = $this->dof_get_acl_filtered_list($dep, $permissions);
            //сливаем массивы
            return $rez + $dep;
        }else
        {//отделов нет
            return $this->dof_get_select_values();
        }
    }
}



class persons_email_edit_form extends dof_modlib_widgets_form
{

    /**
     * @var dof_control
     */
    protected $dof;
    
    function definition()
    {
        global $DOF;
        $this->dof = $DOF;
        $mform =& $this->_form;
        // обьявляем заголовок формы
        $mform->addElement('header','stheader');
        $mform->addElement('hidden','departmentid', optional_param('departmentid', 0, PARAM_INT));
        $mform->setType('departmentid', PARAM_INT);
        // email
        $mform->addElement('textarea', 'emails', $DOF->get_string('email','sel').':', array('cols'=>50, 'rows'=>20));
         // Кнопка "сохранить"
        $mform->addElement('submit', 'save');
        $mform->setDefault('save', $DOF->modlib('ig')->igs('save'));
        // применение проверки ко всем элементам
        $mform->applyFilter('__ALL__', 'trim');
        
    }
    /**
     * Задаем проверку корректности введенных значений
     */
    function validation($data,$files)
    {
		return true;
    }

}

class person_search_form extends dof_modlib_widgets_form
{
    function definition()
    {
        global $DOF;
        $mform =& $this->_form;
        // обьявляем заголовок формы
        $mform->addElement('header','stheader');
        $mform->addElement('hidden','departmentid', optional_param('departmentid', 0, PARAM_INT));
        $mform->setType('departmentid', PARAM_INT);
        //По каким параметрам искать
        $mform->addElement('radio', 'option', '', $DOF->get_string('bylastname', 'persons'), 'bylastname');
        $mform->addElement('radio', 'option', '', $DOF->get_string('byquery', 'persons'), 'byquery');
        $mform->setDefault('option', 'bylastname');
        //Искать ли в дочерних подразделениях
        $mform->addElement('checkbox', 'children', '', $DOF->get_string('children', 'persons'));
        // Значение поля по которому будем искать
        $mform->addElement('text', 'searchstring', '', 'size="20"');
        $mform->setType('searchstring', PARAM_TEXT);
        // Кнопки "сохранить" и "отмена"
        $this->add_action_buttons(true, $DOF->modlib('ig')->igs('search'));
        // применение проверки ко всем элементам
        $mform->applyFilter('__ALL__', 'trim');

    }
    /**
     * Задаем проверку корректности введенных значений
     */
    function validation($data,$files)
    {
		return true;
    }
}
?>