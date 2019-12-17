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

// Подключаем библиотеки
require_once(dirname(realpath(__FILE__)).'/lib.php');
require_once(dirname(realpath(__FILE__)).'/../cfg/contractcfg.php');
require_once(dirname(realpath(__FILE__)).'/form.php');

// получаем id договора (если он редактируется)
$contractid = optional_param('contractid', 0, PARAM_INT);
$edit_contract = new stdClass;

if ( $contractid AND ! $edit_contract = $DOF->storage('contracts')->get($contractid) )
{// объект не найден
   $DOF->print_error($DOF->get_string('notfound','sel', $contractid));
}

$customdata    = new stdClass;
$default       = new stdClass;

// проверяем права доступа
if( $contractid )
{// если id контракта указано - на редактирование контракта
    $DOF->im('sel')->require_access('editcontract',$contractid);
}else
{// если не указано - на создание
    $DOF->im('sel')->require_access('openaccount');
    // Получаем id персоны, если нет - регистрируем текущего пользователя как персону
    if ( ! $seller = $DOF->storage('persons')->get_bu(NULL,true) )
    {
        print_error($DOF->get_string('notfoundperson','sel'));
    }
}
//добавление уровня навигации
$DOF->modlib('nvg')->add_level($DOF->get_string('contractlist', 'sel'), $DOF->url_im('sel','/contracts/list.php',$addvars));

if ( $contractid )
{// если контракт редактируется
    $DOF->modlib('nvg')->add_level($DOF->get_string('editcontract', 'sel'), $DOF->url_im('sel','/contracts/edit_first.php'),$addvars);
}else
{// если контракт создается
    $DOF->modlib('nvg')->add_level($DOF->get_string('newcontract', 'sel'), $DOF->url_im('sel','/contracts/edit_first.php'),$addvars);
}

// установим значение по умолчанию
$default->student = 'new';
$default->client = 'student';
$default->department = $addvars['departmentid'];

// соберем данные для конструктора формы
$customdata->dof          = $DOF;
$customdata->edit_student = true;
$customdata->contractid   = $contractid;
$customdata->departmentid = $addvars['departmentid'];
$customdata->studentid    = 0;
$customdata->clientid     = 0;

if ( isset($im_contracts['createnumber']) AND $im_contracts['createnumber'] )
{// Если в настройках установлена возможность задавать номер договора вручную
    // то разрешим в форме такое поле
    // @todo переписать с использованием плагина config
    $customdata->createnumber = true;
}
if ( $contractid )
{// контракт редактируется
    if ( $DOF->storage('programmsbcs')->is_exists(array('contractid'=>$contractid)) )
    {// если у студента есть подписки id студента менять нельзя
        $customdata->edit_student = false;
    }
    if ( $edit_contract->studentid )
    {// если id студента указанный в контракте не равен 0
        // установим что это пользователь деканата 
        $customdata->studentid = $edit_contract->studentid;
        $default->student      = 'personid';
    } 
    if ( $edit_contract->clientid )
    {// если id законного представителя указан в контракте
        // установим пользователя деканата
        $customdata->clientid  = $edit_contract->studentid;
        
        if ( $edit_contract->studentid != $edit_contract->clientid )
        {// если id не равны - это разные пользователи
            $default->client = 'personid';
        }else
        {// если равны, то они совпадают
            $default->client = 'student';
        }
    }
}

// загрузим форму для первого листа
$form = new sel_contract_form_one_page(null,$customdata);
// занесем значения по умолчанию в форму
$form->set_data($default);
$form->set_data($edit_contract);

// @todo перенести обработчик в форму договора
// установим переменную для вывода сообщений
$message = '';
if ( $form->is_cancelled() )
{//ввод данных отменен - возвращаем на страницу просмотра договоров
    redirect($DOF->url_im('sel','/contracts/list.php?byseller=1', $addvars));
}
if ( $form->is_submitted() AND $formdata = $form->get_data() )
{   // Получили данные формы
    // Обновляем/создаем контракт
    $contract = new object();
     //Вызов обработчика поля "метаконтракт"
     $contract->metacontractid = $DOF->storage('metacontracts')
            ->handle_metacontract($formdata->metacontract,$formdata->department);
    // ученик
    switch ($formdata->student)
    {   
        case 'new':
            // если указано что ученик создается с нуля - занесем в контракт 0
            $contract->studentid = 0;
        break;
        case 'personid':
            // если ученик это пользователь деканата 
            if ( $DOF->storage('persons')->is_exists($formdata->st_person_id['id']) )
            {// если пользователь найден - запишем его как ученика контракта
                $contract->studentid = $formdata->st_person_id['id'];
            }else
            {// не найден - сообщение об ошибке
                $message .= $DOF->get_string('error_persons', 'sel', $formdata->stid).'<br>';
            }
        break;
        case 'mdluser':
            // если ученик указан как пользователь Moodle
            if ( ! empty($formdata->st_mdluser_id['id']) AND ($formdata->st_mdluser_id['id'] != 1) AND 
                 $DOF->modlib('ama')->user(false)->is_exists($formdata->st_mdluser_id['id']) AND
                         $user = $DOF->modlib('ama')->user($formdata->st_mdluser_id['id'])->get()  )
            {// если пользователь Moodle найден и его id не равно 1
                if ( $personid = $DOF->storage('persons')->get_by_moodleid_id($formdata->st_mdluser_id['id']) )
                {// персона уже зарегестрирована - записываем как ученика контракта
                    $contract->studentid = $personid;
                }elseif ( $personid = $DOF->storage('persons')->reg_moodleuser($user) )
                {// регистрируем персону и записываем как ученика контракта
                    $contract->studentid = $personid;
                }else
                {// не удалось зарегестрировать - сообщим об ошибке
                    $message .= $DOF->get_string('error_save_persons', 'sel', $formdata->st_mdluser_id['id']).'<br>';
                }
            }else
            {// пользователь не найден - сообщим об этом
                $message .= $DOF->get_string('error_mdluser', 'sel', $formdata->st_mdluser_id['id']).'<br>';
            }
        break;
        default:
            // ничего не выбрано - это ошибка    
            $message .= $DOF->get_string('error_choice', 'sel').'<br>';
        break;
    }
    switch ($formdata->client)
    {
        case 'new':
            // если указано что клиент создается с нуля - занесем в контракт null
            $contract->clientid = null;
            // укажем чтоб клиент добавлялся
            $clientid = true;
        break;
        case 'student':
            // если указано, что клиент это ученик
            if ( isset($contract->studentid) )
            {// если вверно введен id ученика - сохраним клиента как ученика
                $contract->clientid = $contract->studentid;
            }
        break;
        case 'personid':
            // если клиент это пользователь деканата
            if ( $DOF->storage('persons')->is_exists($formdata->cl_person_id['id']) )
            {// если пользователь найден - запишем его как клиента контракта
                $contract->clientid = $formdata->cl_person_id['id'];
            }else
            {// не найден - сообщение об ошибке
                $message .= $DOF->get_string('error_persons', 'sel', $formdata->cl_person_id['id']).'<br>';
            }
        break;
        case 'mdluser':
            // если клиент указан как пользователь Moodle
            if ( ! empty($formdata->cl_mdluser_id['id']) AND ($formdata->cl_mdluser_id['id'] != 1) AND 
                 $DOF->modlib('ama')->user(false)->is_exists($formdata->cl_mdluser_id['id']) AND
                         $user = $DOF->modlib('ama')->user($formdata->cl_mdluser_id['id'])->get()  )
            {// если пользователь Moodle найден и его id не равно 1
                if ( $personid = $DOF->storage('persons')->get_by_moodleid_id($formdata->cl_mdluser_id['id']) )
                {// персона уже зарегестрирована - записываем как клиента контракта
                    $contract->clientid = $personid;
                }elseif ( $personid = $DOF->storage('persons')->reg_moodleuser($user) )
                {// регестрируем персону и записываем как клиента контракта
                    $contract->studentid = $personid;
                }else
                {// не удалось зарегестрировать - сообщим об ошибке
                    $message .= $DOF->get_string('error_save_persons', 'sel', $formdata->cl_mdluser_id['id']).'<br>';
                }
            }else
            {// пользователь не найден - сообщим об этом
                $message .= $DOF->get_string('error_mdluser', 'sel', $formdata->cl_mdluser_id['id']).'<br>';
            }
        break;
        default:
            // ничего не выбрано - это ошибка       
            $message .= $DOF->get_string('error_choice', 'sel').'<br>';
        break;
    }
    // print_object($contract);
    // сохраняем контракт
    if ( isset($contract->studentid) AND ( isset($contract->clientid) OR isset($clientid) ) )
    {// если id студента и клиента введены верно
        $contract->departmentid = $formdata->department;
        $contract->notes        = $formdata->notes;
        $contract->date         = $formdata->date + 3600*12;
        
        if ( isset($im_contracts['createnumber']) AND $im_contracts['createnumber'] 
                        AND isset($formdata->num) AND !empty($formdata->num) )
        {
            $contract->num = $formdata->num;
        }
        if ( $contractid )
        {// id контракта указано - редактируем договор
            if ( $DOF->storage('contracts')->update($contract,$contractid) )
            {// все в порядке - переходим ко второй странице
                 redirect($DOF->url_im('sel',"/contracts/edit_second.php?contractid={$contractid}",$addvars), '', 0);
            }else
            {// ошибка сохранения
                $message .= $DOF->get_string('error_save', 'sel', $DOF->get_string('m_contracts', 'sel')).'<br>';
            }
        }else
        {// добавляем договор
            $contract->sellerid = $seller->id;
            $contract->status = 'tmp';
            if ( $contract_id = $DOF->storage('contracts')->insert($contract) )
            {// все в порядке - переходим ко второй странице
                
                if ( isset($im_contracts['createnumber']) AND $im_contracts['createnumber'] 
                        AND isset($formdata->num) AND !empty($formdata->num) )
                {
                    $contract->num = $formdata->num;
                    $DOF->storage('contracts')->update($contract,$contract_id);
                }
                redirect($DOF->url_im('sel',"/contracts/edit_second.php?contractid={$contract_id}",$addvars), '', 0);
            }else
            {// ошибка сохранения
                $message .= $DOF->get_string('error_save', 'sel', $DOF->get_string('m_contracts', 'sel')).'<br>';
            }
        }
    }
}
// шапка страницы
$DOF->modlib('nvg')->print_header(NVG_MODE_PORTAL);

// выводим все сообщения и предупреждения если они есть
echo '<br>'.$message.'<br>';
// Отображаем форму договора
$form->display();

// подвал
$DOF->modlib('nvg')->print_footer(NVG_MODE_PORTAL);
?>