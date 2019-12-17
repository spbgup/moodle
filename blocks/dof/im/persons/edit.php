<?PHP
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
// Подключаем библиотеки
require_once(dirname(realpath(__FILE__)).'/lib.php');
require_once('lib.php');

//добавление уровня навигации
$DOF->modlib('nvg')->add_level($DOF->get_string('listpersons', 'persons'), 
     $DOF->url_im('persons','/list.php'),$addvars);
// Получаем id, переданный через get, если есть
$id = optional_param('id',null,PARAM_INT);
$departmentid = optional_param('departmentid',0,PARAM_INT);
// Проверяем права доступа
if ( $id == 0 )
{// если id нет - персона создается
    $DOF->storage('persons')->require_access('create');
}else
{// id передано - персона редактируется
    $DOF->storage('persons')->require_access('edit',$id);
}
//добавление уровня навигации
$DOF->modlib('nvg')->add_level($DOF->get_string('createeditperson', 'persons'), 
      $DOF->url_im('persons',"/edit.php?id=".$id),$addvars);

$form = new persons_edit_form();
//Устанавливаем значения по умолчанию

if (isset($id))
{
    if ( $person = $DOF->storage('persons')->get($id) )
    {
        if ($address = $DOF->storage('addresses')->get($person->passportaddrid))
        {
            $address->country = array($address->country,$address->region);
        }
        unset($address->region);
        unset($address->id);
        $form->set_data($person);
        $form->set_data($address);
    }     
}else
{
	$default = array();
	$default['dateofbirth'] = 0;
    $default['passportdate'] = 0;
    $default['country'] = array('RU');
    $default['departmentid'] = $departmentid;
    $form->set_data($default);
}

if ($formdata = $form->get_data())
{	// Получили данные формы
	// print_r($formdata);
	// $formdata = (array) $formdata;
	// Добавляем персону
	$student = new object();
	$student->firstname = trim($formdata->firstname);
	$student->middlename = trim($formdata->middlename);
	$student->lastname = trim($formdata->lastname);
	$student->dateofbirth = $formdata->dateofbirth + 3600*12;
	$student->gender = $formdata->gender;
	$student->email = trim($formdata->email);
	$student->phonehome = trim($formdata->phonehome);
	$student->phonework = trim($formdata->phonework);
	$student->phonecell = trim($formdata->phonecell);
	$student->passtypeid = $formdata->passtypeid;
    $student->departmentid = $formdata->departmentid;
	if (!($formdata->passtypeid == '0'))
	{
	    $student->passportserial = trim($formdata->passportserial);
	    $student->passportnum = trim($formdata->passportnum);
	    $student->passportdate = $formdata->passportdate + 3600*12;
	    $student->passportem = trim($formdata->passportem);
	} else
	{
		$student->passportserial = '';
	    $student->passportnum = '';
	    $student->passportdate = '';
	    $student->passportem = '';
	}
	$addres_st = new stdClass();
	$addres_st->postalcode = trim($formdata->postalcode);
	$addres_st->country = $formdata->country[0];
	if (isset($formdata->country[1]))
	{
	    $addres_st->region = $formdata->country[1];
	} else
	{
		$addres_st->region = null;
	}
	$addres_st->county = trim($formdata->county);
	$addres_st->city = trim($formdata->city);
	$addres_st->streetname = trim($formdata->streetname);
	$addres_st->streettype = $formdata->streettype;
	$addres_st->number = trim($formdata->number);
	$addres_st->gate = trim($formdata->gate);
	$addres_st->floor = trim($formdata->floor);
	$addres_st->apartment = trim($formdata->apartment);
	$addres_st->latitude = trim($formdata->latitude);
	$addres_st->longitude = trim($formdata->longitude);
	//$student->departmentid = $formdata->department;
	
	// Если id персоны передано
	if (isset($formdata->id))
	{   
		// редактируем персону
	    if (!empty($person->passportaddrid))
		{
		    $DOF->storage('addresses')->update($addres_st,$person->passportaddrid);
		} else
		{
			$student->passportaddrid = $DOF->storage('addresses')->insert($addres_st);
		}
		// Если пользователь имеет право редактировать поля синхронизации, редактируем их
		if ($DOF->storage('persons')->is_access('edit:sync2moodle'))
		{
		    $student->sync2moodle = $formdata->sync2moodle;
	        $student->mdluser = trim($formdata->mdluser);
		}
		// есть право менять временную зону пользователю - меняем её
	    if ( $DOF->storage('persons')->is_access('edit_timezone') 
	            AND $mdlusid = $DOF->storage('persons')->get_field($formdata->id, 'mdluser') 
	            AND $DOF->storage('persons')->get_field($formdata->id, 'sync2moodle') 
	            AND $formdata->mdluser )
        {
            $obj = new object;
            $obj->id = $mdlusid;
            $obj->timezone = $formdata->timezone;
            if ( $DOF->modlib('ama')->user(false)->is_exists($mdlusid) )
            {// если пользователя не существует - то мы не сможем его вернуть
                $DOF->modlib('ama')->user($mdlusid)->update($obj);
            }          
        }
	    $DOF->storage('persons')->update($student,$person->id);
	    redirect($DOF->url_im('persons',"/view.php?id={$person->id}",$addvars), '', 0);
	} else
	{
		// иначе добавляем ее
		$addres_st->type = '1';
	    // Если пользователь имеет право редактировать поля синхронизации, редактируем их
		if ($DOF->storage('persons')->is_access('edit:sync2moodle'))
		{
		    $student->sync2moodle = $formdata->sync2moodle;
	        $student->mdluser = trim($formdata->mdluser);
		}
		$student->passportaddrid = $DOF->storage('addresses')->insert($addres_st);
		$student_id = $DOF->storage('persons')->insert($student);
		if ($student_id)
		{
			redirect($DOF->url_im('persons',"/view.php?id={$student_id}",$addvars), '', 0);
		}	
	}
}

$DOF->modlib('nvg')->print_header(NVG_MODE_PORTAL);

if ( isset($id) AND ! $DOF->storage('persons')->is_exists($id) )
{
    $errorlink = $DOF->url_im('persons','',$addvars);
    $DOF->print_error('nopersons',$errorlink,null,'im','persons');  
}

// Отображаем форму
$form->display();
$DOF->modlib('nvg')->print_footer(NVG_MODE_PORTAL);




?>