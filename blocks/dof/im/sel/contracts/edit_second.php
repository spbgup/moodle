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
require_once('lib.php');
require_once('form.php');
$contractid = required_param('contractid', PARAM_INT);

// регион по умолчанию берем из настроек
$defaultdepartment = $DOF->storage('contracts')->get_field($contractid, 'departmentid');
$defaultregion = $DOF->storage('config')->get_config('defaultregion', 'im', 'sel', $defaultdepartment);
if ( isset($defaultregion->value) )
{
    $defaultregion = $defaultregion->value;
}else
{
    $defaultregion = 0;
} 

if ( ! $edit_contract = $DOF->storage('contracts')->get($contractid) )
{// объект не найден
   print_error($DOF->get_string('notfound','sel', $contractid));
}
// проверяем права доступа
// на редактирование контракта
$DOF->im('sel')->require_access('editcontract',$contractid);

//добавление уровня навигации
$DOF->modlib('nvg')->add_level($DOF->get_string('contractlist', 'sel'), $DOF->url_im('sel','/contracts/list.php',$addvars));
$DOF->modlib('nvg')->add_level($DOF->get_string('editcontract', 'sel'), $DOF->url_im('sel','/contracts/edit_second.php?contractid='.$contractid,$addvars));
                                                                                                                                                                                                                                                                                                                                                                              
// Устанавливаем значения по умолчанию
$customdata = new stdClass;
$customdata->contractid = $contractid;
$customdata->departmentid = $defaultdepartment;
$customdata->seller = false;
$customdata->edit_client = true;
$customdata->edit_student = true;
if ( $contractid )
{// если id контракта указано
    // по умолчанию адрес законного представителя не указан
    $default['cldateofbirth'] = -1893421800;
    $default['clpassportdate'] = 0;
    $default['claddrcountry'] = array('RU', $defaultregion);
    if ( $edit_contract->clientid <> $edit_contract->studentid )
    {// если студент не является законным представителем
        // установим что законный представитель был выбран
        $customdata->seller = true;
        if ( ($edit_contract->clientid <> 0) AND ! is_null($edit_contract->clientid) )
        {//если представитель указан в договоре
            // проверим законного представителя на права
            if ( $DOF->storage('contracts')->is_personel($edit_contract->clientid, $contractid, 'fdo') )
            {// если он уже учавствует в других контрактах 
                // или является учитилем или админом - редактировать нельзя 
                $customdata->edit_client = false;
            }
            // найдем представителя
            $clperson = get_object_vars($DOF->storage('persons')->get($edit_contract->clientid));
            // установим поля по умолчанию законному представителю
            foreach ($clperson as $key=>$value)
            {// добавим к ним префикс cl
                $default["cl{$key}"] = $value;
            } 
            if ( isset($clperson['passportaddrid']) AND 
                   $addrclient = get_object_vars($DOF->storage('addresses')->get($clperson['passportaddrid'])) )
            {// если существует адрес у представителя - установим по умолчанию и его
                // выставим значения для hierselectа
                $default['claddrcountry'] = array($addrclient['country'],$addrclient['region']);
                // удалим чтобы не конфликтовали с hierselectом
                unset($addrclient['country']);
                unset($addrclient['region']);
                // установим поля адреса по умолчанию законному представителю
                foreach ($addrclient as $key=>$value)
                {// добавим к ним префикс claddr
                    $default["claddr{$key}"] = $value;
                }
            }
        }else
        {// если клиент в договоре не указан - значит он создается
            $customdata->client = 'new';
        }
    }
    //выставим нулевые значения
    $default['stdateofbirth'] = -1893421800;
    $default['stpassportdate'] = 0;
    $default['staddrcountry'] = array('RU', $defaultregion);
    if ( $edit_contract->studentid <> 0 )
    {// если студент указан в договоре
        // проверим ученика на права
        if ( $DOF->storage('contracts')->is_personel($edit_contract->studentid, $contractid, 'fdo') )
        {// если он уже учавствует в других контрактах 
            // или является учитилем или админом - редактировать нельзя 
            $customdata->edit_student = false;
        }
        // найдем студента
        $stperson = get_object_vars ($DOF->storage('persons')->get($edit_contract->studentid));
        // установим значения по умолчанию для студента
        foreach ($stperson as $key=>$value)
        {// добавим к ним префикс st
            $default["st{$key}"] = $value;
        }
        if ( isset($stperson['passportaddrid']) AND 
                   $addrstudent = get_object_vars($DOF->storage('addresses')->get($stperson['passportaddrid'])) )
        {// если существует адрес у студента - установим по умолчанию и его
            // выставим значения для hierselectа
            $default['staddrcountry'] = array($addrstudent['country'],$addrstudent['region']);
            // удалим чтобы не конфликтовали с hierselectом
            unset($addrstudent['country']);
            unset($addrstudent['region']);
            // установим поля адреса по умолчанию студенту
            foreach ($addrstudent as $key=>$value)
            {// добавим к ним префикс staddr
                $default["staddr{$key}"]= $value;
            }
        }
    }else
    {// если студент в договоре не указан - значит он создается
        $customdata->student = 'new';
    }
}else
{// id контракта не указано
    $default->date = time();
    $default->seller = 0;
    $default->cldateofbirth = -1893421800;
    $default->clpassportdate = 0;
    $default->claddrcountry = array('RU', $defaultregion);
    $default->stdateofbirth = -1893421800;
    $default->staddrcountry = array('RU', $defaultregion);
    $default->stpassportdate = 0;
}
// установим значения по умолчанию для подписки
if ( $DOF->storage('programmsbcs')->count_list(array('contractid'=>$contractid)) > 1 )
{// подписок много - просто выведем их списком
    $customdata->countsbc = true;
}else
{// подписка одна или вообще нет - будем создавать/редактировать
   $customdata->countsbc = false;
   if ( $programmsbc = $DOF->storage('programmsbcs')->get_record(array('contractid'=>$contractid)) )
   {// если подписка есть - редактируем ее
       // ставим значения по умолчанию
       $default['programmsbc'] = 1;
       $default['prog_and_agroup'] = array($programmsbc->programmid, 
                                           $programmsbc->agenum, 
                                           $programmsbc->agroupid);
       $default['eduform'] = $programmsbc->eduform;
       $default['freeattendance'] = $programmsbc->freeattendance;
       if ( isset($programmsbc->agestartid) )
       {// периода есть - учтем его
           $default['agestart'] = $programmsbc->agestartid;
       }
       if ( isset($programmsbc->datestart) )
       {// дата есть - учтем ее
           $default['datestart'] = $programmsbc->datestart;
       }
   }else
   {// подписка создавалась
       $programmsbc = new stdClass;
       $programmsbc->id = 0;
   }
}

// загружаем форму
$form = new sel_contract_form_two_page(null,$customdata);
// занесем значения по умолчанию в форму
$form->set_data($default);

$error = '';
if ( $form->is_submitted() AND $formdata = $form->get_data() )
{   // Получили данные формы
    // print_object($formdata);die;
    // $formdata = (array) $form
    // Обновляем ученика ученика
    $contract = new object();
// Обновляем ученика ученика
    if ( $customdata->edit_student === true )
    {// если мы имели возможность его редактировать
        $student = new object();
        $student->firstname = trim($formdata->stfirstname);
        $student->middlename = trim($formdata->stmiddlename);
        $student->lastname = trim($formdata->stlastname);
        $student->dateofbirth = $formdata->stdateofbirth + 3600*12;
        $student->gender = $formdata->stgender;
        $student->email = trim($formdata->stemail);
        $student->phonehome = trim($formdata->stphonehome);
        $student->phonework = trim($formdata->stphonework);
        $student->phonecell = trim($formdata->stphonecell);
        $student->passtypeid = $formdata->stpasstypeid;
        if ( ! ($formdata->stpasstypeid == '0') )
        {// если удостоверение личности указано - добавим его
            $student->passportserial = trim($formdata->stpassportserial);
            $student->passportnum = trim($formdata->stpassportnum);
            $student->passportdate = $formdata->stpassportdate + 3600*12;
            $student->passportem = trim($formdata->stpassportem);
        } else
        {// если нет - обнулим значения
            $student->passportserial = '';
            $student->passportnum = '';
            $student->passportdate = '';
            $student->passportem = '';
        }//var_dump($contract->studentid);die;
        
        // добавляем адрес студента
        $addres_st = new stdClass;
        $addres_st->postalcode = trim($formdata->staddrpostalcode);
        $addres_st->country = $formdata->staddrcountry[0];
        if ( isset($formdata->staddrcountry[1]) )
        {// если регион был  указан - добавим его
            $addres_st->region = $formdata->staddrcountry[1];
        } else
        {// если нет - обнулим значение
            $addres_st->region = null;
        }
        $addres_st->county = trim($formdata->staddrcounty);
        $addres_st->city = trim($formdata->staddrcity);
        $addres_st->streetname = trim($formdata->staddrstreetname);
        if ( ! ($formdata->staddrstreetname == '') )
        {// если указано имя улицы - добавим ее тип
            $addres_st->streettype = trim($formdata->staddrstreettype);
        }
        $addres_st->number = trim($formdata->staddrnumber);
        $addres_st->gate = trim($formdata->staddrgate);
        $addres_st->floor = trim($formdata->staddrfloor);
        $addres_st->apartment = trim($formdata->staddrapartment);
        $addres_st->latitude = trim($formdata->staddrlatitude);
        $addres_st->longitude = trim($formdata->staddrlongitude);
        if ( isset($stperson['passportaddrid']) )
        {// если адрес был указан - обновим его
            if ( ! $DOF->storage('addresses')->update($addres_st,$stperson['passportaddrid']) )
            {// не сохранился - сообщим об этом
                $error .= $DOF->get_string('error_save', 'sel', $DOF->get_string('m_address_student', 'sel')).'<br>';
            }
        } else
        {// нет - добавим
            if ( ! $student->passportaddrid = $DOF->storage('addresses')->insert($addres_st) )
            {// не сохранился - сообщим об этом
                $error .= $DOF->get_string('error_save', 'sel', $DOF->get_string('m_address_student', 'sel')).'<br>';
            }
        }
        $student->departmentid = $edit_contract->departmentid;
        if ( $edit_contract->studentid <> 0 )
        {// если id ученика указано - редактируем студента
            if ( ! $DOF->storage('persons')->update($student,$edit_contract->studentid) )
            {// не сохранился - сообщим об этом
                $error .= $DOF->get_string('error_save', 'sel', $DOF->get_string('m_student', 'sel')).'<br>';
            }
            $contract->studentid = $edit_contract->studentid;
        }else
        {// если нет - добавляем
            // Пока не требуется регистрация в Moodle
            $student->sync2moodle = 0;
            if ( ! $student_id = $DOF->storage('persons')->insert($student) )
            {// не сохранился - сообщим об этом
                $error .= $DOF->get_string('error_save', 'sel', $DOF->get_string('m_student', 'sel')).'<br>';
            }
            $contract->studentid = $student_id;
        }
    }else
    {
        $contract->studentid = $edit_contract->studentid;
    }
    if ( empty($formdata->stworkplace['stworkplace']) )
    {// должности нет - пишем, что не указана
        $formdata->stworkplace['stworkplace'] = $DOF->get_string('empty_workplace', 'sel');
    }
    
    //если заполнено поле "Организация"
    if ( !empty($formdata->storganization['storganization']) )
    {
        //обрабатываем добавление организации
        $orgid = $DOF->storage('organizations')->handle_organization($formdata->storganization['id'], 
                $formdata->storganization['storganization']);
        
        //если обработка организации прошла успешно
        if ($orgid !== false)
        {//обрабатываем добавление должности
            //добавляем в метаконтракт поле organizationid
            $obj = new stdClass();
            $obj->organizationid = $orgid;
            $DOF->storage('metacontracts')->update($obj, $edit_contract->metacontractid);
            
            $DOF->storage('workplaces')->handle_workplace($contract->studentid, $orgid, 
                    $formdata->stworkplace['stworkplace']);
        }
    }else
    {//если "Организация" не заполнено, установим organizationid=0 и должность "не указана"
        $DOF->storage('workplaces')->handle_workplace($contract->studentid);    
    }
  
    // Добавляем законного представителя
    if ( $edit_contract->clientid <> $edit_contract->studentid )
    {
       
        // если ученик и представитель разные личности 
        if ( $customdata->edit_client == true )
        {// и мы имели возможность его редактировать
            $client = new object();
            $client->firstname = trim($formdata->clfirstname);
            $client->middlename = trim($formdata->clmiddlename);
            $client->lastname = trim($formdata->cllastname);
            $client->dateofbirth = $formdata->cldateofbirth + 3600*12;
            $client->gender = $formdata->clgender;
            $client->email = trim($formdata->clemail);
            $client->phonehome = trim($formdata->clphonehome);
            $client->phonework = trim($formdata->clphonework);
            $client->phonecell = trim($formdata->clphonecell);
            $client->passtypeid = $formdata->clpasstypeid;
            $client->passportserial = trim($formdata->clpassportserial);
            $client->passportnum = trim($formdata->clpassportnum);
            $client->passportdate = $formdata->clpassportdate + 3600*12;
            $client->passportem = trim($formdata->clpassportem);
            
        
            // добавим адрес законного представителя
            $addres_cl = new stdClass;
            $addres_cl->postalcode = trim($formdata->claddrpostalcode);
            $addres_cl->country = $formdata->claddrcountry[0];
            if (isset($formdata->claddrcountry[1]))
            {// если регион был  указан - добавим его
                $addres_cl->region = $formdata->claddrcountry[1];
            } else
            {// если нет - обнулим значение
                $addres_cl->region = null;
            }
            $addres_cl->county = trim($formdata->claddrcounty);
            $addres_cl->city = trim($formdata->claddrcity);
            $addres_cl->streetname = trim($formdata->claddrstreetname);
            $addres_cl->streettype = trim($formdata->claddrstreettype);
            $addres_cl->number = trim($formdata->claddrnumber);
            $addres_cl->gate = trim($formdata->claddrgate);
            $addres_cl->floor = trim($formdata->claddrfloor);
            $addres_cl->apartment = trim($formdata->claddrapartment);
            $addres_cl->latitude = trim($formdata->claddrlatitude);
            $addres_cl->longitude = trim($formdata->claddrlongitude);
            $client->departmentid = $edit_contract->departmentid;
            if ( isset($clperson['passportaddrid']) )
            {// если адрес был указан - обновим его
                if ( ! $DOF->storage('addresses')->update($addres_cl,$clperson['passportaddrid']) )
                {// не сохранился - сообщим об этом
                    $error .= $DOF->get_string('error_save', 'sel', $DOF->get_string('m_address_client', 'sel')).'<br>';
                }
            }else
            {// нет - добывим
                if ( ! $client->passportaddrid = $DOF->storage('addresses')->insert($addres_cl) )
                {// не сохранился - сообщим об этом
                    $error .= $DOF->get_string('error_save', 'sel', $DOF->get_string('m_address_client', 'sel')).'<br>';
                }
                
            }
            if ( ($edit_contract->clientid <> 0) AND ! is_null($edit_contract->clientid) )
            {// если законный представитель был указан
                // редактируем его
                $contract->clientid = $edit_contract->clientid;
                if ( ! $DOF->storage('persons')->update($client,$edit_contract->clientid) )
                {// не сохранился - сообщим об этом
                    $error .= $DOF->get_string('error_save', 'sel', $DOF->get_string('m_client', 'sel')).'<br>';
                }
            } else
            {   // иначе добавляем
                $client->sync2moodle = 0;
                if ( ! $contract->clientid = $DOF->storage('persons')->insert($client) )
                {// не сохранился - сообщим об этом
                    $error .= $DOF->get_string('error_save', 'sel', $DOF->get_string('m_client', 'sel')).'<br>';
                }
            }
        }else
        {
            $contract->clientid = $edit_contract->clientid;
        }
    } else
    {// ученик и представитель одно лицо
        $contract->clientid = $contract->studentid;
    }
    
    //установим персону законного представителя
    $personid = $contract->clientid;
    if ( empty($formdata->clworkplace['clworkplace']) )
    {// должности нет - пишем, что не указана
        $formdata->clworkplace['clworkplace'] = $DOF->get_string('empty_workplace', 'sel');
    }
    
    //если заполнено поле "Организация"
    if ( !empty($formdata->clorganization['clorganization']) )
    {//обрабатываем добавление организации
        $orgid = $DOF->storage('organizations')->handle_organization($formdata->clorganization['id'], 
                $formdata->clorganization['clorganization']);
        
        //если обработка организации прошла успешно
        if ( $orgid !== false )
        {//обрабатываем добавление должности
            
            //добавляем в метаконтракт поле organizationid
            $obj = new stdClass();
            $obj->organizationid = $orgid;
            $DOF->storage('metacontracts')->update($obj, $edit_contract->metacontractid);
            
            $DOF->storage('workplaces')->handle_workplace($personid, $orgid, 
                    $formdata->clworkplace['clworkplace']); 
        }
    }elseif ($edit_contract->clientid <> $edit_contract->studentid)
    {//если "Организация" не заполнено, и зак. пр. не совпадает с учащимся
        //установим organizationid=0 и должность "не указана"
        $DOF->storage('workplaces')->handle_workplace($personid);    
    }
       
    // обновляем контракт
    if ( ! $DOF->storage('contracts')->update($contract, $contractid) )
    {// не сохранился - сообщим об этом
        $error .= $DOF->get_string('error_save', 'sel', $DOF->get_string('m_contract', 'sel')).'<br>';
    }
    if ( isset( $formdata->programmsbc ) )
    {
        // сохраняем подписку
        $sbc = new object;
        $sbc->contractid = $contractid;
        $sbc->programmid = $formdata->prog_and_agroup[0]; // id программы
        $sbc->agenum = $formdata->prog_and_agroup[1]; //парралель
        if ( isset($formdata->prog_and_agroup[2]) AND ($formdata->prog_and_agroup[2] <> 0) )
        {// и если указана группа - сохраняем группу
            $sbc->agroupid = $formdata->prog_and_agroup[2]; // id группы
        }else
        {// иначе - индивидуальный
            $sbc->agroupid = null;
        }
        $sbc->edutype = $formdata->edutype; // тип обучения
        $sbc->eduform = $formdata->eduform; // форма обучения
        $sbc->freeattendance = $formdata->freeattendance; // свободное посещение
        // @todo - создавать вручную
        $sbc->agestartid = $formdata->agestart;
        $sbc->datestart = $formdata->datestart;
        $sbc->salfactor = $formdata->salfactor;
        //print_object($formdata);
        // сохраним подписку
        if ( ! $sbc->departmentid = $DOF->storage('contracts')->get_field($sbc->contractid, 'departmentid') )
        {//не удалось получить id подразделения';
            $error .=  '<br>'.$DOF->get_string('errorsaveprogrammsbcs','sel').'<br>';
        }elseif ( $DOF->storage('programmsbcs')->is_programmsbc($sbc->contractid,$sbc->programmid,
                            $sbc->agroupid, $sbc->datestart, $sbc->agestartid, $programmsbc->id) )
        {// если такая подписка уже существует - сохранять нельзя
            $error .= '<br>'.$DOF->get_string('programmsbc_exists','sel').'<br>';
        } else
        {//можно сохранять
            if ( isset($programmsbc->id) AND $programmsbc->id )
            {// подписка на курс редактировалась - обновим запись в БД
                if ( ! $DOF->storage('programmsbcs')->update($sbc, $programmsbc->id) )
                {// не удалось произвести редактирование - выводим ошибку
                    $error .= '<br>'.$DOF->get_string('errorsaveprogrammsbcs','sel').'<br>';
                }
            }else
            {// подписка на курс создавалась        
                // сохраняем запись в БД
                $sbc->status = 'application';
                if( $id = $DOF->storage('programmsbcs')->sign($sbc) )
                {// все в порядке - сохраняем статус и возвращаем на страниу просмотра подписки
                    $DOF->workflow('programmsbcs')->init($id);
                }else
                {// подписка на курс выбрана неверно - сообщаем об ошибке
                    $error .=  '<br>'.$DOF->get_string('errorsaveprogrammsbcs','sel').'<br>';
                }
            }
        }
    }
    if ( '' == $error )
    {// если ошибок нет
        if ( isset($formdata->groupsubmit['return']) )
        {// нажата кнопка вернуться - возвращаемся на первый лист
            redirect($DOF->url_im('sel',"/contracts/edit_first.php?contractid={$contractid}",$addvars), '', 0);
        }
        if ( isset($formdata->groupsubmit['save']) )
        {// нажата кнопка сохранить - возвращаем на страниу просмотра подписки
            redirect($DOF->url_im('sel',"/contracts/view.php?id={$contractid}",$addvars), '', 0);
        }
    }

}
$DOF->modlib('nvg')->print_header(NVG_MODE_PORTAL);

echo $error;
// Отображаем форму
$form->display();
$DOF->modlib('nvg')->print_footer(NVG_MODE_PORTAL);
?>