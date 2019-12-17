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


// Подключаем библиотеки
require_once('lib.php');

// Подключаем библиотеку форм
$DOF->modlib('widgets')->webform();
/*
 * Класс формы для ввода данных договора (первая страничка)
 */
class sel_contract_form_one_page extends dof_modlib_widgets_form
{
    /**
     * @var dof_control
     */
    protected $dof;
    
    function definition()
    {
        // служебные переменные
        $mform     =& $this->_form;
        $this->dof =& $this->_customdata->dof;
        // id договора (если он редактируется)
        $contractid = $this->_customdata->contractid;
        
        // скрытые поля
        $mform->addElement('hidden', 'contractid', $contractid);
        $mform->setType('contractid', PARAM_INT);
        // подразделение по умолчанию
        $currentdepartment = $this->_customdata->departmentid;
        $mform->addElement('hidden','departmentid', $currentdepartment);
        $mform->setType('departmentid', PARAM_INT);
        
        ///////////////////////////
        // ИНФОРМАЦИЯ О ДОГОВОРЕ //
        ///////////////////////////
        
        // заголовок формы
        $mform->addElement('header','cldheader', $this->dof->get_string('cldheader', 'sel'));
        if ( isset($this->_customdata->createnumber) AND $this->_customdata->createnumber )
        {// если разрешено указать номер договора вручную - то добавляем дополнительное поле
            $mform->addElement('text', 'num', $this->dof->get_string('num','sel'));
            $mform->setType('num', PARAM_TEXT);
        }
        // дата заключения договора
        $mform->addElement('date_selector', 'date', $this->dof->get_string('date', 'sel'));
        $mform->setType('date', PARAM_INT);
        // Подразделение, в котором создается договор
        $depart = $this->dof->storage('departments')->departments_list_subordinated(null,'0',null,true);
        // оставим в списке только те подразделения, на использование которых у редактирующего есть право
        $permissions = array(array('plugintype'=>'storage', 'plugincode'=>'departments', 'code'=>'use'));
        $depart = $this->dof_get_acl_filtered_list($depart, $permissions);
        $mform->addElement('select', 'department', $this->dof->get_string('department', 'sel').':', $depart);
        $mform->setType('department', PARAM_TEXT);
        // делаем подразделение обязательным полем
        $mform->addRule('department', $this->dof->modlib('ig')->igs('form_err_required'), 'required', null, 'client');
        // заметки о создании договора
        $mform->addElement('textarea', 'notes', $this->dof->get_string('notes', 'sel'), array('style' => 'width:100%'));
        //метаконтракт
        $ajaxparams = $this->autocomplete_params('metacontracts','client', $contractid); //var_dump($ajaxparams);
        $mform->addElement('dof_autocomplete', 'metacontract',
                $this->dof->get_string('metacontract','sel'), array(), $ajaxparams);
        ////////////
        // УЧЕНИК //
        ////////////
        
        // переменная, контроллирующая выключение части формы с данными ученика
        $stoptions = array();
        if ( $this->_customdata->edit_student == false )
        {// если нельзя редактировать ученика - отключаем часть формы
            $stoptions = array('disabled' => 'disabled');
        }
        // Подзаголовок формы
        $mform->addElement('header','stheader', $this->dof->get_string('student', 'sel'));
        
        // выбираем как будем создавать ученика

        // создать новую персону
        $mform->addElement('radio', 'student', null, $this->dof->get_string('new','sel'),'new', $stoptions);
        
        // использовать существующую персону деканата (+ ajax-меню поиска)
        $mform->addElement('radio', 'student', null, $this->dof->get_string('personid','sel'),'personid', $stoptions);
        // получаем параметры для ajax-запроса и персону по умолчанию (если есть)
        $ajaxparams = $this->autocomplete_params('personid', 'student', $contractid);
        $mform->addElement('dof_autocomplete', 'st_person_id',
                $this->dof->modlib('ig')->igs('search'), $stoptions, $ajaxparams);
        // @todo установить тип PARAM_INT для autocomplete (для безопасности). Поскольку меню поиска - это группа, то
        // тут могут понадобится дополнительные действия
        //$mform->setType('st_person_id', PARAM_INT);
        
        // использовать существующего пользователя Moodle (+ ajax-меню поиска).
        if ( ! $contractid )
        {// Показываем только в случае создания нового контракта: при редактировании показывать смысла нет,
            // потому что в созданном договоре уже точно есть ученик.
            // По этой же причине значения по умолчанию в этом поле никогда нет
            $mform->addElement('radio', 'student', null, $this->dof->get_string('mdluser','sel'),'mdluser', $stoptions);
            $ajaxparams = $this->autocomplete_params('mdluser', 'student', $contractid);
            $mform->addElement('dof_autocomplete', 'st_mdluser_id',
                    $this->dof->modlib('ig')->igs('search'), $stoptions, $ajaxparams);
            // @todo установить тип PARAM_INT для autocomplete (для безопасности). Поскольку меню поиска - это группа, то
            // тут могут понадобится дополнительные действия
            //$mform->setType('st_mdluser_id', PARAM_INT);
        }
        
        // правила отключения полей
        if ( $contractid )
        {// договор редактируется
            // поле "пользователь из Moodle" отключается
            // если выбрано создание нового учащегося
            $mform->disabledIf('st_person_id', 'student', 'eq', 'new');
        }else
        {// договор создается, правила отключения полей другие
            // поля "пользователь из Moodle" и "персона деканата" отключаются,
            // если выбрано создание нового учащегося
            $mform->disabledIf('st_person_id', 'student', 'eq', 'new');
            $mform->disabledIf('st_mdluser_id', 'student', 'eq', 'new');
            // поле "персона деканата" отключается,
            // если будет использоваться пользователь Moodle
            $mform->disabledIf('st_person_id', 'student', 'eq', 'mdluser');
            // поле "пользователь из Moodle" отключается,
            // если будет использоваться персона деканата
            $mform->disabledIf('st_mdluser_id', 'student', 'eq', 'personid');
        }
        // на всякий случай отфильтруем данные из radio-кнопки
        $mform->setType('student', PARAM_ALPHANUM);
        
        
        ////////////////////////////
        // ЗАКОННЫЙ ПРЕДСТАВИТЕЛЬ //
        ////////////////////////////
        
        // Подзаголовок
        $mform->addElement('header','clheader', $this->dof->get_string('specimen', 'sel'));
        
        // выбираем как будем создавать законного представителя
        
        // создать новую персону
        $mform->addElement('radio', 'client', null, $this->dof->get_string('new','sel'), 'new');
        // ничего не создавать - законный представитель не нужен, ученик оформляет договор на себя -
        // поэтому и учеником и законным представителем будет один и тот же человек (персона или пользователь Moodle)
        $mform->addElement('radio', 'client', null, $this->dof->get_string('сoincides_with_student','sel'), 'student');
        
        // использовать существующую персону деканата (+ ajax-меню поиска)
        $mform->addElement('radio', 'client', null, $this->dof->get_string('personid', 'sel'), 'personid');
        // получаем параметры для ajax-запроса и персону по умолчанию (если есть)
        $ajaxparams = $this->autocomplete_params('personid', 'client', $contractid);
        $mform->addElement('dof_autocomplete', 'cl_person_id',
                $this->dof->modlib('ig')->igs('search'), null, $ajaxparams);
        // @todo установить тип PARAM_INT для autocomplete (для безопасности). Поскольку меню поиска - это группа, то
        // тут могут понадобится дополнительные действия
        // $mform->setType('cl_person_id', PARAM_INT);
        
        // использовать существующего пользователя Moodle (+ ajax-меню поиска).
        if ( ! $contractid )
        {// Показываем только в случае создания нового контракта: при редактировании показывать смысла нет,
            // потому что в созданном договоре уже точно есть законный представитель, либо он сопрадает с учеником.
            // По этой же причине значения по умолчанию в этом поле никогда нет
            $mform->addElement('radio', 'client', null, $this->dof->get_string('mdluser', 'sel'), 'mdluser');
            $ajaxparams = $this->autocomplete_params('mdluser', 'client', $contractid);
            $mform->addElement('dof_autocomplete', 'cl_mdluser_id',
                    $this->dof->modlib('ig')->igs('search'), null, $ajaxparams);
            // @todo установить тип PARAM_INT для autocomplete (для безопасности). Поскольку меню поиска - это группа, то
            // тут могут понадобится дополнительные действия
            // $mform->setType('cl_mdluser_id', PARAM_INT);
        }
        
        
        // правила отключения полей
        if ( $contractid )
        {// договор редактируется
            // поле "пользователь из Moodle" отключается
            // если выбрано создание нового учащегося или использование ученика в качестве законного представителя
            $mform->disabledIf('cl_person_id', 'client', 'eq', 'new');
            $mform->disabledIf('cl_person_id', 'client', 'eq', 'student');
        }else
        {// договор создается, правила отключения полей другие
            // поля "пользователь из Moodle" и "персона деканата" отключаются,
            // если выбрано создание нового учащегося или использование ученика в качестве законного представителя
            $mform->disabledIf('cl_person_id', 'client', 'eq', 'new');
            $mform->disabledIf('cl_person_id', 'client', 'eq', 'student');
            $mform->disabledIf('cl_mdluser_id', 'client', 'eq', 'new');
            $mform->disabledIf('cl_mdluser_id', 'client', 'eq', 'student');
            // поле "персона деканата" отключается,
            // если будет использоваться пользователь Moodle
            $mform->disabledIf('cl_person_id', 'client', 'eq', 'mdluser');
            // поле "пользователь из Moodle" отключается,
            // если будет использоваться персона деканата
            $mform->disabledIf('cl_mdluser_id', 'client', 'eq', 'personid');
        }
        // на всякий случай отфильтруем данные из radio-кнопки
        $mform->setType('client', PARAM_ALPHANUM);
        
        
        // Кнопка "продолжить" и "отмена"
        $this->add_action_buttons(true, $this->dof->get_string('continue','sel'));
        // обрезаем лишние пробелы слева и справа во всех текстовых полях формы
        $mform->applyFilter('__ALL__', 'trim');
    }
    
    /**
     * Проверка данных формы
     */
    function validation($data, $files)
    {
        $errors = array();
        $oldstudentid = $this->_customdata->studentid;
        $oldclientid  = $this->_customdata->clientid;
        if ( ! isset($data['student']) )
        {// не указано каким образом создается/редактируется ученик
            $errors['student'] = $this->dof->get_string('error_choice','sel');
        }
        if ( ! isset($data['client']) )
        {// не указано каким образом создается/редактируется законный представитель
            $errors['client'] = $this->dof->get_string('error_choice','sel');
        }
        if ( ! isset($data['department']) )
        {// не указано в каком подразделении создается договор
            $errors['department'] = $this->dof->modlib('ig')->igs('form_err_required');
        }
        if ( isset($data['st_person_id']['id']) AND $data['student'] == 'personid')
        {
            if ( ($status = $this->dof->storage('persons')->get_field(array(
                    'id' => $data['st_person_id']['id']), 'status')) == 'archived')
            {// студент имеет архивный статус
                $errors['st_person_id'] = $this->dof->get_string('error_archive', 'sel');
            }
        }
        if ( isset($data['cl_person_id']['id']) AND $data['client'] == 'personid')
        {
            if ( ($status = $this->dof->storage('persons')->get_field(array(
                    'id' => $data['cl_person_id']['id']), 'status')) == 'archived')
            {// клиент имеет архивный статус
                $errors['cl_person_id'] = $this->dof->get_string('error_archive', 'sel');
            }
        }
        if ( ! empty($errors) )
        {// не проверяем дальше, пока не исправлены все ошибки
            return $errors;
        }
        
        
        ///////////////////////////////////////////
        // определяем правильно ли указан ученик //
        ///////////////////////////////////////////
        
        $studentid = 0;
        // Определяем id персоны для ученика
        if ( $data['student'] == 'personid' )
        {// ученик создается из персоны Moodle
            $studentid = $data['st_person_id']['id'];
        }elseif ( $data['student'] == 'mdluser' )
        {// ученик создается из пользователя Moodle. Найдем персону у нас по ее id в Moodle
            $studentid = $this->dof->storage('persons')->get_by_moodleid_id($data['st_mdluser_id']['id']);
        }elseif ( $data['student'] == 'new' )
        {// персоны еще нет - она будет создаваться в обработчике
            $studentid = 0;
        }
        
        if ( $this->_customdata->edit_student == false AND $data['contractid']  )
        {// если нельзя менять id ученика в договоре
            if ( ( $data['student'] == 'new' ) OR 
                 ( $data['student'] == 'mdluser' ) OR
                 ( $studentid != $oldstudentid ) )
            {// и при этом ученик был удален или изменен - выведем ошибку
                $errors['st_person_id'] = $this->dof->get_string('error_students', 'sel');
            }
        }
        
        // проверяем, имеет ли право пользователь создавать договор на эту персону
        // или изменять персону в договоре
        if ( $data['contractid'] )
        {// договор редактировался (там уже стоял ученик)
            if ( ! $studentid AND $data['student'] == 'personid' )
            {// ученика удалили из договора, а нового не создали - так нельзя
                $errors['st_person_id'] = $this->dof->modlib('ig')->igs('form_err_required');
            }
            if ( $oldstudentid != $studentid )
            {// если персону поменяли - то проверить, есть ли права на новую персону
                if ( ! $this->dof->storage('persons')->is_access('use', $studentid) )
                {// права нет - нельзя менять id студента
                    $errors['st_person_id'] = $this->dof->modlib('ig')->igs('form_err_no_use_object');
                }
            }
        }else
        {// договор создавался
            if ( $studentid  AND ! $this->dof->storage('persons')->is_access('use', $studentid) )
            {// учеником выбрана конкретная персона - но прав нет
                $errors['st_person_id'] = $this->dof->modlib('ig')->igs('form_err_no_use_object');
            }
        }
        
        if ( ! $studentid AND $data['student'] == 'personid' )
        {// Установили ученика и не указали id
            $errors['st_person_id'] = $this->dof->modlib('ig')->igs('form_err_required');
        }
        
        
        ///////////////////////////////////////////////////////////
        // определяем правильно ли указан законный представитель //
        ///////////////////////////////////////////////////////////
        
        $clientid = 0;
        // Определяем id персоны для ЗП
        if ( $data['client'] == 'personid' )
        {// ЗП создается из персоны Moodle
            $clientid = $data['cl_person_id']['id'];
        }elseif ( $data['client'] == 'mdluser' )
        {// ЗП создается из пользователя Moodle. Найдем персону у нас по ее id в Moodle
            $clientid = $this->dof->storage('persons')->get_by_moodleid_id($data['cl_mdluser_id']['id']);
        }elseif ( $data['client'] == 'new' OR $data['client'] == 'personid' )
        {// персоны еще нет - она будет создаваться в обработчике
            $clientid = 0;
        }
        
        // проверяем, имеет ли право пользователь создавать договор на эту персону
        // или изменять персону в договоре
        if ( $data['contractid'] )
        {// договор редактировался (возможно что там был ЗП)
            if ( $data['client'] != 'person' )
            {// Если ученик - не ЗП 
                if ( $oldclientid != $clientid AND $clientid AND 
                     ! $this->dof->storage('persons')->is_access('use', $clientid) )
                {// и ЗП был изменен без создания нового пользователя - то нужна проверка прав
                    $errors['cl_person_id'] = $this->dof->modlib('ig')->igs('form_err_no_use_object');
                }
            }
        }else
        {// договор создавался
            if ( $studentid  AND ! $this->dof->storage('persons')->is_access('use', $studentid) )
            {// учеником выбрана конкретная персона - но прав нет
                $errors['st_person_id'] = $this->dof->modlib('ig')->igs('form_err_no_use_object');
            }
        }
        
        if ( ! $clientid AND $data['client'] == 'personid' )
        {// Установили ЗП и не указали id
            $errors['cl_person_id'] = $this->dof->modlib('ig')->igs('form_err_required');
        }
        
        ///////////////////////////////////////////
        // проверка правильности данных договора //
        ///////////////////////////////////////////
        
        if ( $contract = $this->dof->storage('contracts')->get($data['contractid']) )
        {// если договор редактировался
            if ( isset($data['num']) AND ($data['num'] <> $contract->num) AND $this->dof->storage('contracts')->
                     get_records(array('num'=>$data['num'])) )
            {// номер договора должен быть уникальным
                $errors['num'] = $this->dof->get_string('err_num_nounique', 'sel');
            }
            // ограничение на количество договоров в подразделении
            $depid = $contract->departmentid;
            if ( ! $this->dof->storage('config')->get_limitobject('contracts',$data['department'] ) AND 
                   $data['department'] != $depid )
            {
                $errors['department'] = $this->dof->get_string('limit_message', 'sel');
            }             
        } else
        {// договор создавался
            if ( isset($data['num']) AND $this->dof->storage('contracts')->
                     get_records(array('num'=>$data['num'])) )
                     //,'status',array('tmp','new','clientsign','wesign','work','frozen','archives')) )
            {// номер договора должен быть уникальным
                $errors['num'] = $this->dof->get_string('err_num_nounique', 'sel');
            }
            // ограничение на количество договоров в подразделении
            if ( ! $this->dof->storage('config')->get_limitobject('contracts',$data['department'] ) )
            {
                $errors['department'] = $this->dof->get_string('limit_message','sel');
            }           
        }
        
        //проверка по метаконтрактам
        if ( isset($data['metacontract']) )
        {
            $value = $this->dof->modlib('widgets')->get_extvalues_autocomplete('metacontract',$data['metacontract']);
            switch ($value['do'])
            {
                case "create"://запись создается
                    if ( $this->dof->storage('metacontracts')->is_exists(array('num'=>$value['name'])) )
                    {// такой метаконтракт уже существует и его нельзя использовать
                        $errors['metacontract'] = $this->dof->get_string('error_use_exists_metacontract','sel');
                    }
                break;
                case "rename":// запись переименовывается
                    if ( $this->dof->storage('metacontracts')->is_exists(array('num'=>$value['name'])) )
                    {// переименовать в уже существующий с таким названием тоже нельзя
                        $errors['metacontract'] = $this->dof->get_string('error_use_exists_metacontract','sel');
                    }
                case "choose":// запись выбрана
                    if ( !$this->dof->storage('metacontracts')->is_exists($value['id']) )
                    {// метаконтракта нет - пичалька
                        $errors['metacontract'] = $this->dof->get_string('metacontract_no_exist','sel',$value['id']);
                    }elseif ( !$this->dof->storage('metacontracts')->is_access('use',$value['id']) )
                    {// прав нет - пичалька
                        $errors['metacontract'] = $this->dof->get_string('error_use_metacontract','sel',$value['id']);
                    }
               break;
            }
        }
        
        return $errors;
    }
    
    /** 
     * Получить массив опций для autocomplete-элемента 
     * @param string $type - тип autocomplete-элемента, для которого получается список параметров
     *                       personid - поиск по персонам
     *                       mdluser - поиск по пользователям Moodle
     *                       metacontracts - метаконтракты
     * @param string $side - сторона, подписывающая договор
     *                       client - законный представитель
     *                       student - ученик
     * @param int $contractid[optional] - id договора в таблице contracts (если договор редактируется)
     * @return array - массив опций
     */
    protected function autocomplete_params($type, $side, $contractid)
    {
        $options = array();
        $options['plugintype']   = "storage";
        $options['plugincode']   = "persons";
        $options['sesskey']      = sesskey();
        $options['type']         = 'autocomplete';
        $options['departmentid'] = $this->_customdata->departmentid;
        
        //получаем контракт
        $contract = $this->dof->storage('contracts')->get($contractid);
        //тип данных для автопоиска
        switch ($type)
        {
            //id персоны
            case 'personid':
                $options['querytype'] = "persons_list";
                
                $personid = 0;
                if ( ! $contractid )
                {// договор создается - значение по умолчанию не устанавливае
                    return $options;
                }else
                {
                    $column = $side.'id';
                    $personid = $contract->$column;
                }
                // если договор редактируется - установим в autocomplete значение по умолчани
                if ( ! $contract = $this->dof->storage('contracts')->get($contractid) )
                {// не получили договор - не можем установить значение по умолчанию
                    // id есть, а договора нет - нестандартная ситуация, сообщим об этом разработчикам
                    dof_debugging(classname($this).'::autocomplete_params() cannot find contract by $contractid',
                            DEBUG_DEVELOPER);
                    return $options;
                }
                
                // законный представитель совпадает с учеником
                if ( ($contract->studentid == $contract->clientid) AND ($side == 'client') )
                {
                    // не ставим значение по умолчанию
                    return $options;
                }
                
                // не получили персону по id
                if ( ! $person = $this->dof->storage('persons')->get($personid) )
                { // ошибка
                    dof_debugging(classname($this).'::autocomplete_params() cannot find person by $personid',
                            DEBUG_DEVELOPER);
                    // возвращаем опции, т.к. значение по умолчанию уже не сможем получить
                    return $options;
                }
                
                // нашли персону - установим ее как значение по умолчанию
                $default = array($personid => $this->dof->storage('persons')->get_fullname($person));
                $options['default'] = $default;
                
                break;
            //пользователь в moodle
            case 'mdluser':
                $options['querytype'] = "mdluser_list";
                
                break;
            //метаконтракты
            case 'metacontracts':
                $options['querytype'] = "metacontracts_list";
                $options['plugincode'] = "metacontracts";
                
                //если не удалось получить контракт
                if ($contract === false)
                {
                    //dof_debugging(classname($this).'::autocomplete_params() cannot find contract by $contractid',
                    //      DEBUG_DEVELOPER);
                    return $options;
                }
                $options['extoptions'] = new stdClass;
                $options['extoptions']->create = true;
                // получили метаконтракт
                if (!empty($contract->metacontractid))
                {//подставляем по умолчанию
                    $options['extoptions']->empty = true;
                    $metacontract = $this->dof->storage('metacontracts')->get($contract->metacontractid,'id,num');
                    $options['default'] = array($contract->metacontractid => 
                            $metacontract->num.' ['.$metacontract->id.']');
                    //$options['extoptions']->rename = $metacontract->id;
                }
                
                break;
        }
        
        return $options;
    }
}


/*
 * Класс формы для ввода данных договора (вторая страничка)
 */
class sel_contract_form_two_page extends dof_modlib_widgets_form
{
    protected $dof;
    function definition()
    {
        global $DOF;
        $mform =& $this->_form;
        $this->dof = $DOF;
        $mform->addElement('hidden', 'contractid',$this->_customdata->contractid);
        $mform->setType('contractid', PARAM_INT);
        $mform->addElement('hidden','departmentid', optional_param('departmentid', 0, PARAM_INT));
        $mform->setType('departmentid', PARAM_INT);
        
        /***************************************
         * часть формы законного представителя *
         ***************************************/
        
        if ( $this->_customdata->seller == true )
        {// если сказано указать законного представителя
            if ( $this->_customdata->edit_client == false )
            {// нельзя редактировать представителя
                // закроем поля
                $cloptions = 'disabled';
                $options = array('optional'=>false);
            }else
            {
                $cloptions = '';
                // выставим дату до 1970 года
                $options = array();
                $options['startyear'] = 1910;
                $options['stopyear']  = dof_userdate(time()-17*365*24*3600,'%Y');
                $options['optional']  = false;
            }
            // обьявляем заголовок формы
            $mform->addElement('header','clheader', $DOF->get_string('specimen', 'sel'));
            // фамилия, имя, отчество
            $mform->addElement('text', 'cllastname', $DOF->get_string('lastname','sel').':', $cloptions);
            $mform->setType('cllastname', PARAM_TEXT);
            $mform->addElement('text', 'clfirstname', $DOF->get_string('firstname','sel').':', $cloptions);
            $mform->setType('clfirstname', PARAM_TEXT);
            $mform->addElement('text', 'clmiddlename', $DOF->get_string('middlename','sel').':', $cloptions);
            $mform->setType('clmiddlename', PARAM_TEXT);
            // дата рождения
            $mform->addElement('date_selector', 'cldateofbirth', $DOF->get_string('dateofbirth', 'sel').':', $options, $cloptions);
            $mform->setType('cldateofbirth', PARAM_INT);
            // пол 
            $displaylist = array();
            $displaylist['unknown'] = $DOF->get_string('unknown', 'sel');
            $displaylist['male'] = $DOF->get_string('male', 'sel');
            $displaylist['female'] = $DOF->get_string('female', 'sel');
        
            $mform->addElement('select', 'clgender', $DOF->get_string('gender', 'sel').':', $displaylist, $cloptions);
            $mform->setType('clgender', PARAM_TEXT);
            // email
            if ( isset($this->_customdata->client) AND $this->_customdata->client == 'new' )
            {// если клиент создается - можно редактировать email
                $mform->addElement('text', 'clemail', $DOF->get_string('email','sel').':', $cloptions);
            }else
            {// редактируется - блокируем поле для редактирования
                $mform->addElement('text', 'clemail', $DOF->get_string('email','sel').':', 'disabled');
            }
            $mform->setType('clemail', PARAM_TEXT);
            // телефоны
            $mform->addElement('text', 'clphonehome', $DOF->get_string('phonehome','sel').':', $cloptions);
            $mform->setType('clphonehome', PARAM_TEXT);
            $mform->addElement('text', 'clphonework', $DOF->get_string('phonework','sel').':', $cloptions);
            $mform->setType('clphonework', PARAM_TEXT);
            $mform->addElement('text', 'clphonecell', $DOF->get_string('phonecell','sel').':', $cloptions);
            $mform->setType('clphonecell', PARAM_TEXT);
            // удостоверение личности
            $pass = $DOF->modlib('refbook')->pasport_type();
            $pass['0'] = $DOF->get_string('nonepasport', 'sel');
            $mform->addElement('select', 'clpasstypeid', $DOF->get_string('passtypeid', 'sel').':', $pass, $cloptions);
            $mform->setType('clpasstypeid', PARAM_TEXT);
            $mform->addElement('text', 'clpassportserial', $DOF->get_string('passportserial','sel').':', $cloptions);
            $mform->setType('clpassportserial', PARAM_TEXT);
            $mform->addElement('text', 'clpassportnum', $DOF->get_string('passportnum','sel').':', $cloptions);
            $mform->setType('clpassportnum', PARAM_TEXT);
            $mform->addElement('date_selector', 'clpassportdate', $DOF->get_string('passportdate', 'sel').':',array('optional'=>false),$cloptions);
            $mform->addElement('text', 'clpassportem', $DOF->get_string('passportem','sel').':', $cloptions);
            $mform->setType('clpassportem', PARAM_TEXT);
            // адрес
            // тип
            // индекс
            $mform->addElement('text', 'claddrpostalcode', $DOF->get_string('addrpostalcode','sel').':',$cloptions);
            $mform->setType('claddrpostalcode', PARAM_INT);
            // страна и регион
            $choices = get_string_manager()->get_list_of_countries();
            $regions = array();
            foreach ($choices as $key => $value)
            {// составляем для каждой страны список регионов
                // первым значением всегда ставим "не указан"
                $countryregions = array($key => array( 0 => $DOF->get_string('unknown', 'sel')));
                // добавляем нулевое значение к списку регионов
                $countryregions = array_merge_recursive($countryregions, $DOF->modlib('refbook')->region($key));
                $regions[$key] = $countryregions[$key];
            }
            $sel =& $mform->addElement('hierselect', 'claddrcountry', $DOF->get_string('addrcountryregion', 'sel').':',$cloptions);
            $sel->setMainOptions($choices);
            $sel->setSecOptions($regions);   
            // округ
            $mform->addElement('text', 'claddrcounty', $DOF->get_string('addrcounty','sel').':', $cloptions);
            $mform->setType('claddrcounty', PARAM_TEXT);
            $mform->addElement('text', 'claddrcity', $DOF->get_string('addrcity','sel').':', $cloptions);
            $mform->setType('claddrcity', PARAM_TEXT);
            $mform->addElement('text', 'claddrstreetname', $DOF->get_string('addrstreetname','sel').':', $cloptions);
            $mform->setType('claddrstreetname', PARAM_TEXT);
            //получим список типов улиц
            if ( ! $street = $DOF->modlib('refbook')->get_street_types() )
            {//не получили
                $street = array();            
            }
            $mform->addElement('select', 'claddrstreettype', $DOF->get_string('addrstreettype','sel').':', $street, $cloptions);
            $mform->setType('claddrstreettype', PARAM_TEXT);
            $mform->addElement('text', 'claddrnumber', $DOF->get_string('addrnumber','sel').':', $cloptions);
            $mform->setType('claddrnumber', PARAM_TEXT);
            $mform->addElement('text', 'claddrgate', $DOF->get_string('addrgate','sel').':', $cloptions);
            $mform->setType('claddrgate', PARAM_TEXT);
            $mform->addElement('text', 'claddrfloor', $DOF->get_string('addrfloor','sel').':', $cloptions);
            $mform->setType('claddrfloor', PARAM_TEXT);
            $mform->addElement('text', 'claddrapartment', $DOF->get_string('addrapartment','sel').':', $cloptions);
            $mform->setType('claddrapartment', PARAM_TEXT);
            $mform->addElement('text', 'claddrlatitude', $DOF->get_string('addrlatitude','sel').':', $cloptions);
            $mform->setType('claddrlatitude', PARAM_TEXT);
            $mform->addElement('text', 'claddrlongitude', $DOF->get_string('addrlongitude','sel').':', $cloptions);
            $mform->setType('claddrlongitude', PARAM_TEXT);
            
            //поля с автопоиском "организация" и "должность"
            $ajaxparams = $this->autocomplete_params('organizations', 'client', $this->_customdata->contractid);
            $mform->addElement('dof_autocomplete', 'clorganization', $DOF->get_string('organization','sel'), null, 
                    $ajaxparams);
            $ajaxparams = $this->autocomplete_params('workplaces', 'client', $this->_customdata->contractid);
            $mform->addElement('dof_autocomplete', 'clworkplace', $DOF->get_string('workplace','sel'), null, 
                    $ajaxparams);          
        }
        
        /***************************
         *      Часть ученика      *
         ***************************/
        
        if ( $this->_customdata->edit_student == false )
        {// нельзя редактировать студента
            // закроем поля
            $stoptions = 'disabled';
            $options = array('optional'=>false);
        }else
        {
            $stoptions = '';
            // выставим дату до 1970 года
            $options = array();
            $options['startyear'] = 1910;
            $options['stopyear']  = dof_userdate(time()-5*365*24*3600,'%Y');
            $options['optional']  = false;
        }
        // обьявляем заголовок формы
        $mform->addElement('header','stheader', $DOF->get_string('student', 'sel'));
        // фамилия, имя, отчество
        $mform->addElement('text', 'stlastname', $DOF->get_string('lastname','sel').':', $stoptions);
        $mform->setType('stlastname', PARAM_TEXT);
        $mform->addElement('text', 'stfirstname', $DOF->get_string('firstname','sel').':', $stoptions);
        $mform->setType('stfirstname', PARAM_TEXT);
        $mform->addElement('text', 'stmiddlename', $DOF->get_string('middlename','sel').':', $stoptions);
        $mform->setType('stmiddlename', PARAM_TEXT);
        // дата рождения
        
        $mform->addElement('date_selector', 'stdateofbirth', $DOF->get_string('dateofbirth', 'sel'), $options, $stoptions);
        $mform->setType('stdateofbirth', PARAM_INT);
        // пол 
        $displaylist = array();
        $displaylist['unknown'] = $DOF->get_string('unknown', 'sel');
        $displaylist['male'] = $DOF->get_string('male', 'sel');
        $displaylist['female'] = $DOF->get_string('female', 'sel');
        $mform->addElement('select', 'stgender', $DOF->get_string('gender', 'sel').':', $displaylist, $stoptions);
        $mform->setType('stgender', PARAM_TEXT);
        // email
        if ( isset($this->_customdata->student) AND $this->_customdata->student == 'new' )
        {// если клиент создается - можно редактировать email
            $mform->addElement('text', 'stemail', $DOF->get_string('email','sel').':', $stoptions);
        }else
        {// редактируется - блокируем поле для редактирования
            $mform->addElement('text', 'stemail', $DOF->get_string('email','sel').':', 'disabled');
        }
        
        $mform->setType('stemail', PARAM_TEXT);
        // страна и регион 
        $choices = get_string_manager()->get_list_of_countries();
        $regions = array();
        foreach ($choices as $key => $value)
        {// составляем для каждой страны список регионов
            // первым значением всегда ставим "не указан"
            $countryregions = array($key => array( 0 => $DOF->get_string('unknown', 'sel')));
            // добавляем нулевое значение к списку регионов
            $countryregions = array_merge_recursive($countryregions, $DOF->modlib('refbook')->region($key));
            $regions[$key] = $countryregions[$key];
        }
        
        $sel =& $mform->addElement('hierselect', 'staddrcountry', $DOF->get_string('addrcountryregion', 'sel').':', $stoptions);
        $sel->setMainOptions($choices);
        $sel->setSecOptions($regions);  
        // телефоны
        $mform->addElement('text', 'stphonehome', $DOF->get_string('phonehome','sel').':', $stoptions);
        $mform->setType('stphonehome', PARAM_TEXT);
        $mform->addElement('text', 'stphonework', $DOF->get_string('phonework','sel').':', $stoptions);
        $mform->setType('stphonework', PARAM_TEXT);
        $mform->addElement('text', 'stphonecell', $DOF->get_string('phonecell','sel').':', $stoptions);
        $mform->setType('stphonecell', PARAM_TEXT);
        // удостоверение личности
        $pass = $DOF->modlib('refbook')->pasport_type();
        $pass['0'] = $DOF->get_string('nonepasport', 'sel');
        $mform->addElement('select', 'stpasstypeid', $DOF->get_string('passtypeid', 'sel').':', $pass, $stoptions);
        $mform->setType('stpasstypeid', PARAM_TEXT);
        // новый договор - установим значсчене по умолчанию
        if ( ! isset($this->_customdata->stpasstypeid) )
        {
            $mform->setDefault('stpasstypeid', 0);
        }
        // серия
        $mform->addElement('text', 'stpassportserial', $DOF->get_string('passportserial','sel').':', $stoptions);
        $mform->setType('stpassportserial', PARAM_TEXT);
        // номер
        $mform->addElement('text', 'stpassportnum', $DOF->get_string('passportnum','sel').':', $stoptions);
        $mform->setType('stpassportnum', PARAM_TEXT);
        // когда выдан
        $mform->addElement('date_selector', 'stpassportdate', $DOF->get_string('passportdate', 'sel').':',array('optional'=>false), $stoptions);
        $mform->setType('stpassportdate', PARAM_INT);
        // кем выдан
        $mform->addElement('text', 'stpassportem',$DOF->get_string('passportem','sel').':', $stoptions);
        $mform->setType('stpassportem', PARAM_TEXT);
        
        if ( $stoptions <> 'disabled' )
        {// студент редактируется, поставим disebled по умолчанию
            $mform->disabledIf('stpassportserial', 'stpasstypeid','eq','0');
            $mform->disabledIf('stpassportnum', 'stpasstypeid','eq','0');
            $mform->disabledIf('stpassportdate', 'stpasstypeid','eq','0');
            $mform->disabledIf('stpassportem', 'stpasstypeid','eq','0');
        }
        // адрес
        // индекс
        $mform->addElement('text', 'staddrpostalcode', $DOF->get_string('addrpostalcode','sel').':', $stoptions);
        $mform->setType('staddrpostalcode', PARAM_TEXT);
        // округ/район
        $mform->addElement('text', 'staddrcounty', $DOF->get_string('addrcounty','sel').':', $stoptions);
        $mform->setType('staddrcounty', PARAM_TEXT);
        // Населенный пункт
        $mform->addElement('text', 'staddrcity', $DOF->get_string('addrcity','sel').':', $stoptions);
        $mform->setType('staddrcity', PARAM_TEXT);
        // $mform->addRule('staddrcity','Error', 'required',null,'client');
        // название улицы
        $mform->addElement('text', 'staddrstreetname', $DOF->get_string('addrstreetname','sel').':', $stoptions);
        $mform->setType('staddrstreetname', PARAM_TEXT);
        //получим список типов улиц
        if ( ! $street = $DOF->modlib('refbook')->get_street_types() )
        {//не получили
            $street = array();            
        }
        $mform->addElement('select', 'staddrstreettype', $DOF->get_string('addrstreettype','sel').':',$street, $stoptions);
        $mform->setType('staddrstreettype', PARAM_TEXT);
        $mform->addElement('text', 'staddrnumber', $DOF->get_string('addrnumber','sel').':', $stoptions);
        $mform->setType('staddrnumber', PARAM_TEXT);
        $mform->addElement('text', 'staddrgate', $DOF->get_string('addrgate','sel').':', $stoptions);
        $mform->setType('staddrgate', PARAM_TEXT);
        $mform->addElement('text', 'staddrfloor', $DOF->get_string('addrfloor','sel').':', $stoptions);
        $mform->setType('staddrfloor', PARAM_TEXT);
        $mform->addElement('text', 'staddrapartment', $DOF->get_string('addrapartment','sel').':', $stoptions);
        $mform->setType('staddrapartment', PARAM_TEXT);
        $mform->addElement('text', 'staddrlatitude', $DOF->get_string('addrlatitude','sel').':', $stoptions);
        $mform->setType('staddrlatitude', PARAM_TEXT);
        $mform->addElement('text', 'staddrlongitude', $DOF->get_string('addrlongitude','sel').':', $stoptions);
        $mform->setType('staddrlongitude', PARAM_TEXT);
        
        //поля с автопоиском "организация" и "должность"
        $ajaxparams = $this->autocomplete_params('organizations', 'student', $this->_customdata->contractid);
        $mform->addElement('dof_autocomplete', 'storganization', $DOF->get_string('organization','sel'), null, 
                    $ajaxparams);
        $ajaxparams = $this->autocomplete_params('workplaces', 'student', $this->_customdata->contractid);
        $mform->addElement('dof_autocomplete', 'stworkplace', $DOF->get_string('workplace','sel'), null, 
                    $ajaxparams);   
        /***********************************
         *      Подписки на программу      *
         ***********************************/
        
        if ( $this->_customdata->countsbc == false )
        {//если подписок нет или она одна 
            //создаем или редактируем подписку на программу
            $mform->addElement('header','header', $DOF->get_string('create_programmsbc', 'sel'));
            $mform->addElement('checkbox', 'programmsbc',null, $DOF->get_string('create_programmsbc', 'sel'));
            $options = $this->get_select_options();
            // при помощи css делаем так, чтобы надписи в форме совпадали с элементами select
            $mform->addElement('html', '<div style=" line-height: 1.9; ">');
            // добавляем новый элемент выбора зависимых вариантов форму
            $myselect =& $mform->addElement('hierselect', 'prog_and_agroup', 
                                            $this->dof->get_string('programm', 'programmsbcs').':<br/>'.
                                            $this->dof->get_string('agenum',   'programmsbcs').':<br/>'.
                                            $this->dof->get_string('agroup',   'programmsbcs').':',
                                            null,'<br/>');
            // закрываем тег выравнивания строк
            $mform->addElement('html', '</div>');
            // устанавливаем для него варианты ответа
            // (значения по умолчанию устанавливаются в методе definition_after_data)
            $myselect->setOptions(array($options->programms, $options->agenums, $options->agroups ));
            $mform->disabledIf('prog_and_agroup', 'programmsbc');
            // получаем все возможные формы обучения
            $eduforms = $this->get_eduforms_list();
            // создаем меню выбора формы обучения
            $mform->addElement('select', 'eduform', $this->dof->get_string('eduform', 'sel'), $eduforms);
            $mform->disabledIf('eduform', 'programmsbc');
            $mform->setType('eduform', PARAM_TEXT);
            // получаем все возможные типы обучения
            $edutypes = $this->get_edutypes_list();
            // создаем меню выбора типа обучения
            $mform->addElement('select', 'edutype', $this->dof->get_string('edutype', 'sel'), $edutypes);
            $mform->disabledIf('edutype', 'programmsbc');
            $mform->setType('edutype', PARAM_TEXT);
            $mform->setDefault('edutype','group');
            // свободное посещение
            $mform->addElement('selectyesno', 'freeattendance', $this->dof->get_string('freeattendance', 'sel'));
            $mform->disabledIf('freeattendance', 'programmsbc');
            $mform->setType('freeattendance', PARAM_INT);
            $ages = $this->get_list_ages();
            $mform->addElement('select', 'agestart', $this->dof->get_string('agestart', 'sel'), $ages);
            $mform->disabledIf('agestart', 'programmsbc');
            $mform->setType('agestart', PARAM_INT);
            $options = array();
            $options['startyear'] = dof_userdate(time()-10*365*24*3600,'%Y');
            $options['stopyear']  = dof_userdate(time()+5*365*24*3600,'%Y');
            $options['optional']  = false;
            $mform->addElement('date_selector', 'datestart', $this->dof->get_string('datestart', 'sel'), $options);
            $mform->disabledIf('datestart', 'programmsbc');
            //$mform->setType('datestart', PARAM_INT);
            // поправочный зарплатный коэффициент
            $mform->addElement('text', 'salfactor', $this->dof->get_string('salfactor','sel').':', 'size="10"');
            $mform->setType('salfactor', PARAM_TEXT);
            $mform->setDefault('salfactor', '0.00');
        }else
        {// если их много - создаем ссылки на подписки
            $mform->addElement('header','header', $DOF->get_string('programmsbcs', 'sel'));
            $programmsbcs = $this->dof->storage('programmsbcs')->get_records(array('contractid'), $this->_customdata->contractid);
            foreach ( $programmsbcs as $sbc )
            {
                $mform->addElement('html', '&nbsp;&nbsp;&nbsp;<a href='.
                       $this->dof->url_im('programmsbcs','/edit.php?programmsbcid='.$sbc->id).'>'.
                       $this->dof->get_string('view_programmsbcs', 'sel', $this->get_programm_name($sbc->programmid)).
                       '</a><br>');
            }
        }
        // Кнопка "сохранить"
        $button = array();
        // Создаем элементы формы
        $button[] =& $mform->createElement('submit', 'save', $this->dof->get_string('save','sel'));
        $button[] =& $mform->createElement('submit', 'return', $this->dof->get_string('return','sel'));
        // добавляем элементы в форму
        $grp =& $mform->addElement('group', 'groupsubmit', null, $button);
        $mform->closeHeaderBefore('groupsubmit');
        // применение проверки ко всем элементам
        $mform->applyFilter('__ALL__', 'trim');
           
    }
    
    
    
    function definition_after_data()
    {
        // создаем ссылку на HTML_QuickForm
        $mform =& $this->_form;
        // создаем проверки на стороне клиента
        if ( $this->_customdata->edit_student <> false )
        {// если можно редактировать ученика
            // для ученика
            $mform->addRule('stlastname', $this->dof->modlib('ig')->igs('form_err_required'), 'required',null,'client');
            $mform->addRule('stfirstname', $this->dof->modlib('ig')->igs('form_err_required'), 'required',null,'client');
            if ( $requiredstmiddlename = $this->dof->storage('config')->get_config('requiredstmiddlename', 'im', 'sel', $this->_customdata->departmentid)
                 AND $requiredstmiddlename->value )
            {// заполнение отчества обязательно
                $mform->addRule('stmiddlename', $this->dof->modlib('ig')->igs('form_err_required'), 'required',null,'client');
            }
            $mform->addRule('stgender', $this->dof->get_string('err_gender', 'sel'), 'required',null,'client');
            if ( isset($this->_customdata->student) AND $this->_customdata->student == 'new' )
            {// если студент создается вставим проверки на емайл
                $mform->addRule('stemail', $this->dof->get_string('err_email', 'sel'), 'required',null,'client');
                $mform->addRule('stemail',$this->dof->get_string('err_email', 'sel'), 'email',null,'client');
            }
            $mform->addRule('staddrcountry', $this->dof->modlib('ig')->igs('err_required'), 'required',null,'client');
        }
        if ( $this->_customdata->seller == true AND $this->_customdata->edit_client <> false  )
        {// если указан законный представитель и его можно редактировать
            // для законного представителя
            $mform->addRule('cllastname', $this->dof->modlib('ig')->igs('form_err_required'), 'required',null,'client');
            $mform->addRule('clfirstname', $this->dof->modlib('ig')->igs('form_err_required'), 'required',null,'client');
            if ( $requiredclmiddlename = $this->dof->storage('config')->get_config('requiredclmiddlename', 'im', 'sel', $this->_customdata->departmentid)
                 AND $requiredclmiddlename->value )
            {
                $mform->addRule('clmiddlename', $this->dof->modlib('ig')->igs('form_err_required'), 'required',null,'client');
            }
            $mform->addRule('clgender', $this->dof->get_string('err_gender', 'sel'), 'required',null,'client');
            if ( isset($this->_customdata->client) AND $this->_customdata->client == 'new' )
            {// если законный представитель создается, вставим проверку на емайл
                if ( $requiredclientemail = $this->dof->storage('config')->get_config('requiredclientemail', 'im', 'sel', $this->_customdata->departmentid)
                     AND $requiredclientemail->value )
                {// заполнение email обязательно
                    $mform->addRule('clemail', $this->dof->modlib('ig')->igs('form_err_required'), 'required',null,'client');
                }
                $mform->addRule('clemail',$this->dof->get_string('err_email', 'sel'), 'email',null,'client');
            }
            if ( $requiredclpasstype = $this->dof->storage('config')->get_config('requiredclpasstype', 'im', 'sel', $this->_customdata->departmentid)
                 AND $requiredclpasstype->value )
            {
                $mform->addRule('clpasstypeid', $this->dof->modlib('ig')->igs('form_err_required'), 'required',null,'client');
                $mform->addRule('clpassportem', $this->dof->modlib('ig')->igs('form_err_required'), 'required',null,'client');
                $mform->addRule('clpassportnum', $this->dof->modlib('ig')->igs('form_err_required'), 'required',null,'client');
                $mform->addRule('claddrpostalcode', $this->dof->modlib('ig')->igs('form_err_required'), 'required',null,'client');
                $mform->addRule('claddrcity', $this->dof->modlib('ig')->igs('form_err_required'), 'required',null,'client');
                $mform->addRule('claddrstreetname', $this->dof->modlib('ig')->igs('form_err_required'), 'required',null,'client');
                $mform->addRule('claddrstreettype', $this->dof->modlib('ig')->igs('form_err_required'), 'required',null,'client');
                $mform->addRule('claddrnumber', $this->dof->modlib('ig')->igs('form_err_required'), 'required',null,'client');
                $mform->addRule('claddrcountry', $this->dof->modlib('ig')->igs('form_err_required'), 'required',null,'client');
            }
            $mform->addRule('claddrpostalcode',$this->dof->get_string('err_numeric', 'sel'), 'numeric',null,'client');
   
            // дополнительные для ученика
            if ( $this->_customdata->edit_student <> false )
            {
                if ( $requiredstpasstype = $this->dof->storage('config')->get_config('requiredcstpasstype', 'im', 'sel', $this->_customdata->departmentid)
                     AND $requiredstpasstype->value ) 
                {
                    $mform->addRule('stpasstypeid', $this->dof->modlib('ig')->igs('form_err_required'), 'required',null,'client');
                    $mform->addRule('stpassportem', $this->dof->modlib('ig')->igs('form_err_required'), 'required',null,'client');
                    $mform->addRule('stpassportnum', $this->dof->modlib('ig')->igs('form_err_required'), 'required',null,'client');
                    $mform->addRule('staddrcity', $this->dof->modlib('ig')->igs('form_err_required'), 'required',null,'client');
                    $mform->addRule('staddrpostalcode', $this->dof->modlib('ig')->igs('form_err_required'), 'required',null,'client');
                    $mform->addRule('staddrstreetname', $this->dof->modlib('ig')->igs('form_err_required'), 'required',null,'client');
                    $mform->addRule('staddrstreettype', $this->dof->modlib('ig')->igs('form_err_required'), 'required',null,'client');
                    $mform->addRule('staddrnumber', $this->dof->modlib('ig')->igs('form_err_required'), 'required',null,'client');
                }
                $mform->addRule('staddrpostalcode',$this->dof->get_string('err_numeric', 'sel'), 'numeric',null,'client');
            }
        }
        
    }

/** Внутренняя функция. Получить параметры для autocomplete-элемента 
     * @param string $type - тип autocomplete-элемента, для которого получается список параметров
     *                       personid - поиск по персонам
     *                       mdluser - поиск по пользователям Moodle
     * @param string $side - сторона, подписывающая договор
     *                       client - законный представитель
     *                       student - ученик
     * @param int $contractid[optional] - id договора в таблице contracts (если договор редактируется)
     * 
     * @return array
     */
    protected function autocomplete_params($type, $side, $contractid)
    {
        $options = array();
        $options['plugintype'] = "storage";
        $options['sesskey'] = sesskey();
        $options['type'] = 'autocomplete';
        
        //получаем контракт
        $contract = $this->dof->storage('contracts')->get($contractid);
        
        // определяем, для какого поля получать значение (ученик или законный представитель)
        $personid = 0;
        if ($contract !== false)
        {
            $column = $side.'id';
            $personid = $contract->$column;
        }
        
        //тип данных для автопоиска
        switch ($type)
        {
            //организация
            case 'organizations':
                $options['plugincode'] = "organizations";
                $options['querytype'] = "organizations_list";
                
                $organizationid = $this->dof->storage('workplaces')
                        ->get_field(array('personid' => $personid,'statuswork' => 'active'),'organizationid');
                
                if (!empty($organizationid))
                {
                    $organization = $this->dof->storage('organizations')->get($organizationid,'id,shortname');
                    //значение по умолчанию
                    $options['default'] = array($organizationid => $organization->shortname);
                }
                
                break;
                
            //должность
            case 'workplaces':
                $options['plugincode'] = "workplaces";
                $options['querytype'] = "workplaces_list";
                
                $workplaceid = $this->dof->storage('workplaces')
                        ->get_field(array('personid' => $personid, 'statuswork' => 'active'),'id');
                
                if (!empty($workplaceid))
                {
                    $workplace = $this->dof->storage('workplaces')->get($workplaceid, 'post');
                    //значение по умолчанию
                    $options['default'] = array($workplaceid => $workplace->post);
                }
                
                break;
        }
 
        return $options;
    }
    
    
    /**
     * Задаем проверку корректности введенных значений
     */
    function validation($data,$files)
    {
        global $DOF;
        $errors = array();
        $reqfield = array();
        if ( $this->_customdata->seller == true AND $this->_customdata->edit_client <> false )       
        {// если законный представитель указан и его можно редактировать
            // email ученика и законного представителя не должен совпадать
            //var_dump($data['clemail']);
            if ( ! empty($data['clemail']) AND $data['clemail']==$data['stemail'] )
            {
                 $errors['clemail'] = $this->dof->get_string('err_email_nounique', 'sel');
            }
            // Уникальность емайлов
            if ( $client = $DOF->storage('persons')->get($DOF->storage('contracts')->get($data['contractid'])->clientid) )
            {// если указан клиент и его адрес менялся
                if ( (! empty($data['clemail']) AND $data['clemail'] <> $client->email) AND !$DOF->storage('persons')->is_email_unique($data['clemail']) )
                {// емайл должен быть уникальным
                    $errors['clemail'] = $this->dof->get_string('err_email_nounique', 'sel');
                }
            } else
            {// если клиента нет
                if ( ! empty($data['clemail']) AND !$DOF->storage('persons')->is_email_unique($data['clemail']) )
                {// емайл должен быть уникальным
                    $errors['clemail'] = $this->dof->get_string('err_email_nounique', 'sel');
                }
            }
            if ( $requiredclientemail = $this->dof->storage('config')->get_config('requiredclientemail', 'im', 'sel', $this->_customdata->departmentid)
                 AND $requiredclientemail->value )
            {
                $reqfield = array_merge($reqfield,array('clemail'));
            }
            if ( $data['clgender'] == 'unknown' )
            {// пол должен быть указан
                $errors['clgender'] = $this->dof->get_string('err_gender', 'sel');
            }
            if ( ! empty($data['claddrstreetname']) AND empty($data['claddrstreettype']) )
            {// если указано имя улицы - необходимо указать и тип
                $errors['claddrstreettype'] = $this->dof->get_string('err_streettype', 'sel');
            
            }
            if ( ($data['cldateofbirth'] <= -1893421800) )
            {// если удостоверение указано - должено быть указано кем выдано
                $errors['cldateofbirth'] = $this->dof->get_string('err_date', 'sel');
            }
            // создадим массив для обязательных полей, чтобы не писать проверку каждому вручную
            $reqfield = array_merge($reqfield,array('cllastname','clfirstname'));
            if ( $requiredclpasstype = $this->dof->storage('config')->get_config('requiredclpasstype', 'im', 'sel', $this->_customdata->departmentid)
                 AND $requiredclpasstype->value AND $data['clpasstypeid'] == 0 )
            {
                $errors['clpasstypeid'] = $this->dof->modlib('ig')->igs('form_err_required');
                
            }
            if ( $data['clpasstypeid'] != 0 )
            {
                $reqfield = array_merge($reqfield,array(
                              'clpassportnum','clpassportem','claddrpostalcode', 
                              'claddrstreettype','claddrcity','claddrstreetname',
                              'claddrnumber'));
            }
            if ( $requiredclmiddlename = $this->dof->storage('config')->get_config('requiredclmiddlename', 'im', 'sel', $this->_customdata->departmentid)
                 AND $requiredclmiddlename->value )
            {
                $reqfield = array_merge($reqfield,array('clmiddlename'));
            }
            if ( $this->_customdata->edit_student <> false )
            {// если указан ученик
                // добавим проверки

            }

            if ( isset($data['claddrcountry'][1]) AND $data['claddrcountry'][0] == 'RU' AND ! $data['claddrcountry'][1] )
            {// не указан регион для законного представителя
                $errors['claddrcountry'] = $DOF->get_string('err_region_not_specified', 'sel');
            }
        }
        if ( $this->_customdata->edit_student <> false )
        {// если указан ученик
            // для ученика
            if ( $student = $DOF->storage('persons')->get($DOF->storage('contracts')->get($data['contractid'])->studentid) )
            {// если указан студен, но его email изменился
                if (($data['stemail'] <> $student->email) AND !$DOF->storage('persons')->is_email_unique($data['stemail']))
                {//емайл должен быть уникальным
                    $errors['stemail'] = $this->dof->get_string('err_email_nounique', 'sel');
                }  
            } else
            {// если студента нет
                if (isset($data['stemail']) AND !$DOF->storage('persons')->is_email_unique($data['stemail']))
                {//емайл должен быть уникальным
                    $errors['stemail'] = $this->dof->get_string('err_email_nounique', 'sel');
                } 
            }
            if ( ($data['stdateofbirth'] <= -1893421800) )
            {// неверно указанная дата
                $errors['stdateofbirth'] = $this->dof->get_string('err_date', 'sel');
            }
            if ( ! empty($data['staddrstreetname']) AND empty($data['staddrstreettype']) )
            {// если указано имя улицы - необходимо указать и тип
                $errors['staddrstreettype'] = $this->dof->get_string('err_streettype', 'sel');
            }
            if ( $data['stgender'] == 'unknown' )
            {// удостоверение у ученика обязательно к заполнению
                $errors['stgender'] = $this->dof->get_string('err_gender', 'sel');
            }
            if ( isset($data['staddrcountry'][1]) AND $data['staddrcountry'][0] == 'RU' AND ! $data['staddrcountry'][1] )
            {// не указан регион для ученика
                $errors['staddrcountry'] = $DOF->get_string('err_region_not_specified', 'sel');
            }
            if ( $requiredstpasstype = $this->dof->storage('config')->get_config('requiredstpasstype', 'im', 'sel', $this->_customdata->departmentid)
                 AND $requiredstpasstype->value AND $data['stpasstypeid'] == 0 )
            {
                $errors['stpasstypeid'] = $this->dof->modlib('ig')->igs('form_err_required');
            }
            if ( $data['stpasstypeid'] != 0 )
            {// удостоверение у ученика обязательно к заполнению
                $reqfield = array_merge($reqfield, array(
                              'stpassportnum','stpassportem', 'staddrpostalcode',
                              'staddrstreettype','staddrcity','staddrstreetname',
                              'staddrnumber'));
            }
        }
        // напишем для каждого обязательную проверку
        foreach ($reqfield as $value)
        {
            if ( empty($data[$value]) )
            {// если такого элемента нет
                $errors[$value] = $this->dof->modlib('ig')->igs('form_err_required');
            }   
        }
        if ( isset($data['programmsbc']) AND ($data['programmsbc'] == 1) )
        {// если создается подписка
            // проверим существование программы  
            if ( ! isset($data['prog_and_agroup'][0]) OR 
                 ! $this->dof->storage('programms')->is_exists($data['prog_and_agroup'][0]) )
            {// такая программа не существует
                $errors['prog_and_agroup'] = $this->dof->get_string('err_required','sel');
            }elseif ( ! isset($data['prog_and_agroup'][2]) AND $data['prog_and_agroup'][2] )
            {// проверяем существование группы
                if ( ! $agroup = $this->dof->storage('agroups')->get($data['prog_and_agroup'][2]) )
                {// если она указана, но ее id не найден - то это ошибка
                    $errors['prog_and_agroup'] = $this->dof->get_string('err_required','sel');
                }elseif ( $agroup->programmid <> $data['prog_and_agroup'][0] )
                {
                    $errors['prog_and_agroup'] = $this->dof->get_string('error_conformity_agroup','sel');
                }elseif ( $agroup->agenum <> $data['prog_and_agroup'][1] AND $agroup->status <> 'plan' )
                {
                    $errors['prog_and_agroup'] = $this->dof->get_string('error_conformity_agenum','sel');
                }
            }
            // проверим существование периода      
            if ( ! isset($data['agestart']) OR 
                 ! $this->dof->storage('ages')->is_exists($data['agestart']) )
            {// такого периода не существует
                $errors['agestart'] = $this->dof->get_string('err_required','sel');
            }
        }
        //для студента
        //проверим, существует ли в форме автокомплит для организаций и передано ли в поле число
        if ( isset($data['storganization']['storganization']) AND
                preg_match("/^[0-9]+$/", $data['storganization']['storganization']) )
        {
            $checkid = $data['storganization']['storganization'];
            //проверим, существует ли такая организация
            if ( !$this->dof->storage('organizations')->is_exists($checkid) )
            {// такой организации не существует
                $errors['storganization'] = $this->dof->get_string('org_no_exist','sel');
            }elseif ( ! $this->dof->storage('organizations')->is_access('use',$checkid) )
            {// нельзя использовать данную организацию
                $errors['storganization'] = $this->dof->get_string('error_use_org','sel',$checkid);
            }
        }elseif ( isset($data['storganization']['storganization']) )
        {
            if ( $checkid = $this->dof->storage('organizations')->get_field($data['storganization']['storganization'],'id') AND 
                 ! $this->dof->storage('organizations')->is_access('use',$checkid) )
            {// такая организация уже существует и ее нельзя использовать
                $errors['storganization'] = $this->dof->get_string('error_use_exists_org','sel',$checkid);
            }
        }elseif ( ! empty($data['storganization']['id']) )
        {// передано id - проверим на использование
            if ( ! $this->dof->storage('organizations')->is_access('use',$data['storganization']['id']) )
            {
                $errors['storganization'] = $this->dof->get_string('error_use_org','sel',$data['storganization']['id']);
            }
        }

        //для ЗП
        //проверим, существует ли в форме автокомплит для организаций и передано ли в поле число
        if ( isset($data['clorganization']['clorganization']) AND
                preg_match("/^[0-9]+$/", $data['clorganization']['clorganization']) )
        {
            $checkid = $data['clorganization']['clorganization'];
            //проверим, существует ли такая организация
            if ( !$this->dof->storage('organizations')->is_exists($checkid) )
            {// такой организации не существует
                $errors['clorganization'] = $this->dof->get_string('org_no_exist','sel');
            }elseif ( ! $this->dof->storage('organizations')->is_access('use',$checkid) )
            {// нельзя использовать данную организацию
                $errors['clorganization'] = $this->dof->get_string('error_use_org','sel',$checkid);
            }
        }elseif ( isset($data['clorganization']['clorganization']) )
        {
            if ( $checkid = $this->dof->storage('organizations')->get_field($data['clorganization']['clorganization'],'id') AND 
                 ! $this->dof->storage('organizations')->is_access('use',$checkid) )
            {// такая организация уже существует и ее нельзя использовать
                $errors['clorganization'] = $this->dof->get_string('error_use_exists_org','sel',$checkid);
            }
        }elseif ( ! empty($data['clorganization']['id']) )
        {// передано id - проверим на использование
            if ( ! $this->dof->storage('organizations')->is_access('use',$data['clorganization']['id']) )
            {
                $errors['clorganization'] = $this->dof->get_string('error_use_org','sel',$data['clorganization']['id']);
            }
        }
        
      
        return $errors;
    }
    /** Получить весь список опций для элемента hierselect
     * @todo переделать эту функцию в рекурсивную процедуру, чтобы сократить объем кода
     * @return stdClass object объект, содержащий данные для элемента hierselect
     */
    function get_select_options()
    {
        $result = new object();
        // получаем список всех учеников
        $programms = $this->get_list_programms();
        // создаем массив для учебных программ
        $agroups  = array();
        // создаем массив для параллелей
        $agenums  = array();
        foreach ( $programms as $progid=>$programm )
        {// для каждой программы составим список возможных академических групп,
            // и тем самым создадим иерархию второго уровня
            $agenums[$progid] = $this->get_list_agenums($progid);
            foreach ($agenums[$progid] as $num=>$agenum)
            {
                $agroups[$progid][$num] = $this->get_list_agroups($progid, $num);
            }
        }
        // записываем в результурующий объект все что мы получили
        $result->programms = $programms;
        $result->agroups   = $agroups;
        $result->agenums   = $agenums;
        //print_object($result);
        // возвращаем все составленные массивы в упорядоченном виде
        return $result;
    }
    
    /** Получить список всех возможных программ обучения
     * @return array массив вариантов для элемента hierselect
     */
    private function get_list_programms()
    {
        // извлекаем все учебные программы из базы
        $result = $this->dof->storage('programms')->
            get_records(array('status'=>array('available')),'name');
        $result = $this->dof_get_select_values($result, true, 'id', array('name', 'code'));
        // оставим в списке только те объекты, на использование которых есть право
        $permissions = array(array('plugintype'=>'storage', 'plugincode'=>'programms', 'code'=>'use'));
        $result = $this->dof_get_acl_filtered_list($result, $permissions);
        
        return $result;
    }
    
    /** Получить список академических групп 
     * 
     * @return array
     */
    private function get_list_agroups($programmid, $agenum)
    {
        $result = array();
        // добавляем первый вариант со словом "Индивидуально"
        $result[0] = $this->dof->get_string('no','programmsbcs');
        // получаем все программы
        $agroups = $this->dof->storage('agroups')->get_records(array('programmid'=>$programmid));
        if ( $agroups )
        {// если группы извлеклись - то добавим их в массив
            foreach ( $agroups as $id=>$agroup )
            {// составляем массив нужной для select-элемента структуры
                if ( $agroup->agenum == $agenum OR $agroup->status == 'plan')
                {
                    $result[$id] = $agroup->name.' ['.$agroup->code.']';
                }
            }
        }
        // оставим в списке только те объекты, на использование которых есть право
        $permissions = array(array('plugintype'=>'storage', 'plugincode'=>'agroups', 'code'=>'use'));
        $result = $this->dof_get_acl_filtered_list($result, $permissions);
        
        return $result;
    }
    
    /** Получить список доступных учебных периодов для этой программы
     * 
     * @return array массив элементов для hierselect
     * @param int $programmid - id учебной программы из таблицы programms
     */
    private function get_list_agenums($programmid)
    {
        $result = array();
        // добавляем первый вариант со словом "Индивидуально"
        $result[0] = $this->dof->get_string('no','programmsbcs');
        if ( ! $programm = $this->dof->storage('programms')->get($programmid) )
        {// переданная учебная программа не найдена
            return $result;
        }
        // заполняем массив данными
        for ( $i=1; $i<=$programm->agenums; $i++ )
        {
            $result[$i] = $i.' '; // пустой пробел в конце обязателен
        } 
         
        return $result;
    }
    /** Возвращает массив периодов 
     * @return array список периодов, массив(id периода=>название)
     */
    private function get_list_ages()
    {
        $rez = $this->dof->storage('ages')->get_records(array('status'=>array('plan',
                                                                            'createstreams',
                                                                            'createsbc',
                                                                            'createschelude',
                                                                            'active')));
        $rez = $this->dof_get_select_values($rez, false);
        // оставим в списке только те объекты, на использование которых есть право
        $permissions = array(array('plugintype'=>'storage', 'plugincode'=>'ages', 'code'=>'use'));
        $rez = $this->dof_get_acl_filtered_list($rez, $permissions);
        
        return $rez;
    }
    /** Получить список всех возможных форм обучения для элемента select
     * 
     * @return array
     */
    private function get_eduforms_list()
    {
        return $this->dof->storage('programmsbcs')->get_eduforms_list();
    }
    /** Получить список всех возможных типов обучения для элемента select
     * 
     * @return array
     */
    private function get_edutypes_list()
    {
        return $this->dof->storage('programmsbcs')->get_edutypes_list();
    }
    /** Получить название программы
     * @param int $programmid - id программы
     * @return string
     */
    private function get_programm_name($programmid)
    {
        if ( ! $programmname = $this->dof->storage('programms')->get_field($programmid, 'name') )
        {//программа не указана - выведем пустую строчку
            $programmname = '&nbsp;';
        }
        if ( ! $programmcode = $this->dof->storage('programms')->get_field($programmid, 'code') )
        {//код программы не указан - выведем пустую строчку
            $programmcode = '&nbsp;';
        }
        if ( ($programmname <> '&nbsp;') OR ($programmcode <> '&nbsp;') )
        {// если код группы или имя были найдены - выведем их вместе 
            $programm = $programmname.' ['.$programmcode.']';
        }else
        {// не найдены - пустую строчку
            $programm = '&nbsp;';
        }
        return $programm;
    }
}


/** Класс формы для поиска контрактов
 *  по состояниям
 */
class sel_contract_form_search_status extends dof_modlib_widgets_form
{
    /**
     * @var dof_control
     */
    protected $dof;
    
    function definition()
    {
        $this->dof = $this->_customdata->dof;
        // создаем ссылку на HTML_QuickForm
        $mform =& $this->_form;
        //создаем заголовок формы
        $mform->addElement('header','formtitle', $this->dof->modlib('ig')->igs('search'));
        //поле подразделений
        $statuses = $this->dof->workflow('contracts')->get_list();
        foreach($statuses as $key => $status)
        {
            $statuses[$key] = $this->dof->get_string('status:'.$key,'contracts',NULL,'workflow');
        }
        $statuses = array('my_contracts' => $this->dof->get_string('my_contracts','sel'),
                          'all_statuses' => $this->dof->get_string('all_statuses','sel')) + $statuses;        
        $mform->addElement('select', 'status', $this->dof->get_string('status','sel').':', $statuses);        
        $mform->setdefault('status', $this->_customdata->search);
        $mform->addElement('submit', 'search', $this->dof->get_string('to_find','sel'));  
        $mform->closeHeaderBefore('formtitle');
        // применение проверки ко всем элементам
        $mform->applyFilter('__ALL__', 'trim');
    }
}
?>