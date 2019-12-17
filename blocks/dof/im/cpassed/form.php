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

/**
 * Здесь происходит объявление класса формы, 
 * на основе класса формы из плагина modlib/widgets. 
 * Подключается из init.php. 
 */

// Подключаем библиотеки
require_once('lib.php');
// подключаем библиотеку форм
$DOF->modlib('widgets')->webform();

class dof_im_cpassed_edit_form extends dof_modlib_widgets_form
{
    private $cpassed;
    /**
     * @var dof_control 
     */
    protected $dof;
    
    /** Объявление формы
     * 
     * @return null
     */
    function definition()
    {// делаем глобальные переменные видимыми

        $this->cpassed = $this->_customdata->cpassed;
        $this->dof     = $this->_customdata->dof;

        // создаем ссылку на HTML_QuickForm
        $mform =& $this->_form;
        $mform->addElement('hidden','departmentid', optional_param('departmentid', 0, PARAM_INT));
        $mform->setType('departmentid', PARAM_INT);
        $mform->addElement('hidden','cpassedid', $this->cpassed->id);
        $mform->setType('cpassedid', PARAM_INT);
        $mform->addElement('hidden','sesskey', 0);
        $mform->setType('sesskey', PARAM_ALPHANUM);
        // устанавливаем значения как hidden-поля, чтобы потом забрать из них
        // значения при помощи definition_after_data
        $mform->addElement('hidden','studentid', 0);
        $mform->setType('studentid', PARAM_INT);
        $mform->addElement('hidden','programmsbcid', 0);
        $mform->setType('programmsbcid', PARAM_INT);
        $mform->addElement('hidden','programmitemid', 0);
        $mform->setType('programmitemid', PARAM_INT);
        $mform->addElement('hidden','cstreamid', 0);
        $mform->setType('cstreamid', PARAM_INT);
        $mform->addElement('hidden','ageid', 0);
        $mform->setType('ageid', PARAM_INT);
        $mform->addElement('hidden','agroupid', 0);
        $mform->setType('agroupid', PARAM_INT);
        // создаем заголовок формы
        $mform->addElement('header','formtitle', $this->get_form_title($this->cpassed->id));
        // получаем список всех элементов для hierselect
        $students = $this->get_list_students();
        foreach ( $agelist as $ageid=>$age )
        {// составляем список разрешенных периодов
            // создаем иерархию пятого уровня
            $type[$contrid][$progsbcid][$pitemid][$csid][$ageid] = $this->get_type_cpassed($progsbcid);
        }
        $options = $this->get_select_options();
        // выравниваем строки по высоте
        $mform->addElement('html', '<div style=" line-height: 1.9; ">');
        // добавляем новый элемент выбора зависимых вариантов форму
        $myselect =& $mform->addElement('hierselect', 'cpdata', 
                                        $this->dof->get_string('student',      'cpassed').':<br/>'.
                                        $this->dof->get_string('programm',     'cpassed').':<br/>'.
                                        $this->dof->get_string('subject',      'cpassed').':<br/>'.
                                        $this->dof->get_string('cstream',      'cpassed').':<br/>'.
                                        $this->dof->get_string('age',          'cpassed').':<br/>'.
                                        $this->dof->get_string('type_cpassed', 'cpassed').':<br/>',
                                        null,'<br/>');
        // закрываем тег выравнивания строк
        $mform->addElement('html', '</div>');
        // устанавливаем для него варианты ответа
        // (значения по умолчанию устанавливаются в методе definition_after_data)
        $myselect->setOptions(array($options->students, $options->programms, $options->subjects,
        $options->cstreams, $options->ages,$options->type));
        if ( $this->cpassed->id )
        {// выведем поле "статус" если форма редактируется
            $mform->addElement('static', 'status', $this->dof->get_string('status','cpassed').':', 
                                    $this->get_status_name($this->cpassed->status));
        }
        // применение проверки ко всем элементам
        $mform->applyFilter('__ALL__', 'trim');        
        // кнопки сохранить и отмена
        $this->add_action_buttons(true, $this->dof->get_string('to_save','cpassed'));
    }
    
    /** Установка значений по умолчанию для сложных элементов
     * 
     * @return 
     */
    function definition_after_data()
    {
        // создаем ссылку на HTML_QuickForm
        $mform =& $this->_form;
        // получаем id ученика
        $studentid      = $mform->getElementValue('studentid');
        // получаем id подписки на программу
        $programmsbcid  = $mform->getElementValue('programmsbcid');
        // получаем предмет
        $programmitemid = $mform->getElementValue('programmitemid');
        // получаем id потока 
        $cstreamid      = $mform->getElementValue('cstreamid');
        // получаем период
        $ageid          = $mform->getElementValue('ageid');
        // получаем запись контракта
        if ( $progsbc = $this->dof->storage('programmsbcs')->get($programmsbcid) )
        {// если подписка найдена - извлечем из нее номер контракта
            $contractid = $progsbc->contractid;
        }else
        {// если не нашли подписку, то ничего не покажем
            $contractid = 0;
        }
        $agroupid       = $mform->getElementValue('agroupid');
        // устанавливаем значения по умолчанию для всех полей элемента hierselect
        $mform->setDefault('cpdata', array($contractid, $programmsbcid, $programmitemid, $cstreamid, $ageid,$agroupid));
    }
    
    /** Проверка данных на стороне сервера
     * @return 
     * @param object $data[optional] - массив с данными из формы
     * @param object $files[optional] - массив отправленнных в форму файлов (если они есть)
     */
    public function validation($data,$files)
    {
        $errors = array();
        
        // проверим существование учителя
        if ( ! isset($data['cpdata'][0]) OR ! $contract = $this->dof->storage('contracts')->get($data['cpdata'][0]) )
        {// такой контракт не зарегестрировван
            $errors['cpdata'] = $this->dof->get_string('err_required_multi','cpassed');
        }elseif( ! $this->dof->storage('persons')->is_exists($contract->studentid) )
        {// контракт существует, проверим существует ли ученик
            $errors['cpdata'] = $this->dof->get_string('err_student_notexists','cpassed');
        }
        
        // проверим существование периода
        if ( ! isset($data['cpdata'][1]) OR ! $progsbc = $this->dof->storage('programmsbcs')->get($data['cpdata'][1]) )
        {// подписка на программу не существует
            $errors['cpdata'] = $this->dof->get_string('err_required_multi','cpassed');
        }elseif( $progsbc->contractid != $data['cpdata'][0] )
        {// если подписка существует, проверим соответствие контракта подписке
            $errors['cpdata'] = $this->dof->get_string('err_required_multi','cpassed');
        }
        
        // проверим существование предмета
        if ( ! isset($data['cpdata'][2]) OR ! $subject = $this->dof->storage('programmitems')->get($data['cpdata'][2]) )
        {// предмет не существует
            $errors['cpdata'] = $this->dof->get_string('err_required_multi','cpassed');
        }elseif ( $subject->programmid != $progsbc->programmid )
        {// если предмет существует, то проверим соответствие его с программой
            $errors['cpdata'] = $this->dof->get_string('err_required_multi','cpassed');
        }
        
        // проверим существование потока, если он указан
        if ( $data['cpdata'][3] AND ! $cstream = $this->dof->storage('cstreams')->get($data['cpdata'][3]) )
        {// поток не существует, сообщим об этом
            $errors['cpdata'] = $this->dof->get_string('cstream_not_exists','cpassed');
        }elseif ( $data['cpdata'][3] )
        {// если поток существует - проверим правильность его привязки к программе
            if ( ! $pitem = $this->dof->storage('programmitems')->get($cstream->programmitemid) )
            {// если не найден элемент учебной программы - это ошибка
                $errors['cpdata'] = $this->dof->get_string('wrong_cstream_and_programm','cpassed');
            }elseif ( $pitem->programmid != $progsbc->programmid )
            {// если элемент программы найден, про принадлежит к другому потоку - это ошибка
                $errors['cpdata'] = $this->dof->get_string('wrong_cstream_and_programm','cpassed');
            }
        }
        
        // проверим существование периода
        if ( ! isset($data['cpdata'][4]) OR ! $this->dof->storage('ages')->is_exists($data['cpdata'][4]) )
        {// периода не существует
            $errors['cpdata'] = $this->dof->get_string('err_required_multi','cpassed');
        }elseif( $data['cpdata'][3] )
        {// если поток выбран и существует, то проверим соответствие id потока с id периода
            if ( $cstream->ageid != $data['cpdata'][4] )
            {// если период не соответствеет выбранному потоку, то не даем сохранить данные
                // и сообщаем об ошибке
                $errors['cpdata'] = $this->dof->get_string('wrong_cstream_and_age','cpassed');
            }
        }
        // если ошибки есть - то пользователь вернется на страницу редактирования и увидит их
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
       
        // создаем массив для учебных программ
        $programms = array();
        $cstreams  = array();
        foreach ( $students as $contrid=>$student )
        {// для каждого ученика определяем список программ на которые он подписан
            $plist = $this->get_student_programs($contrid);
            // создадим иерархию второго уровня
            $programms[$contrid] = $plist;
            foreach ( $plist as $progsbcid=>$programm )
            {// составляем список разрешенных предметов
                $subjlist = $this->get_list_subjects($progsbcid);
                // создаем иерархию третьего уровня
                $subjects[$contrid][$progsbcid] = $subjlist;
                foreach ( $subjlist as $pitemid=>$subject )
                {// составляем список разрешенных учебных потоков
                    $cstreamlist = $this->get_list_cstreams($pitemid);
                    // создаем иерархию четвертого уровня
                    $cstreams[$contrid][$progsbcid][$pitemid] = $cstreamlist;
                    foreach ( $cstreamlist as $csid=>$cstream )
                    {// составляем список разрешенных периодов
                        $agelist = $this->get_list_ages($progsbcid, $csid);
                        // создаем иерархию пятого уровня
                        $ages[$contrid][$progsbcid][$pitemid][$csid] = $agelist;
                        
                    }
                }
            }
        }
        // записываем в результурующий объект все что мы получили
        $result->students  = $students;
        $result->programms = $programms;
        $result->subjects  = $subjects;
        $result->cstreams  = $cstreams;
        $result->ages      = $ages;
        $result->type      = $type;
        //print_object($result);
        // возвращаем все составленные массивы в упорядоченном виде
        return $result;
    }
    
    /** Получить список программ, на которые подписан ученик
     * @todo добавить в зависимости плагин контрактов, программ, и подписок на программы
     * @return array массив вариантов для элемента hierselect
     * @param int $contractid - id контракта ученика для которого получается список программ. Таблица contracts.
     */
    private function get_student_programs($contractid)
    {
        $result = array();
        // получаем id ученика
        $studentid = $this->dof->storage('contracts')->get_field($contractid, 'studentid');
        if ( ! $studentid )
        {// для нулевого элемента покажем простто пункт "выбрать"
            return $result;
        }
        // извлекаем все контракты ученика
        if ( $contracts = $this->dof->storage('contracts')->
                get_records(array('studentid'=>$studentid), '', 'id, num') )
        {// удалось извлечь контракты
            foreach ( $contracts as $cntrid=>$contract )
            {// перебираем все контракты и извлекаем для каждого учебную программу
                // на которую подписан ученик
                // @todo контракт выбираем только пока 1
                if ( $programmsbcs = $this->dof->storage('programmsbcs')->
                        get_records(array('contractid'=>$contractid), '', 'id, programmid') )
                {// получили id программ - теперь получим их названия
                    foreach ( $programmsbcs as $psid=>$programmsbc )
                    {// и запишем из в результирующий массив
                        if ( $progname = $this->dof->storage('programms')->
                                 get_field($programmsbc->programmid, 'name') )
                        {// если название программы корректно извлеклось - вставим его
                         // в результат
                            $result[$psid] = $progname.' ['.$this->dof->storage('programms')->
                                 get_field($programmsbc->programmid, 'code').']';
                        }
                    }
                }
            }
        }
        // возвращаем то, что набрали
        return array(0 => '--- '.$this->dof->get_string('to_select','cpassed').' ---') + $result;
    }
    
    /** Получить список учебных потоков, разрешенных данной учебной программой
     * @return array массив допустимых учебных потоков
     * @param object $progitemid - id изучаемого предмета в таблице programmitems
     */
    private function get_list_cstreams($progitemid)
    {
        $result = array();
        // получаем все учебные потоки для текущего периода и подписки
        if ( $cstreams = $this->dof->storage('cstreams')->get_records(array('programmitemid'=>$progitemid)) )
        {// если получили потоки, то приведем их к виду, нужному в форме
            foreach ( $cstreams as $id=>$cstream )
            {
                // формируем пункт меню
                $result[$id] = $cstream->name;
            }
        }
        return array(0 => '--- '.$this->dof->get_string('to_select','cpassed').' ---') + $result;
    }
    
    /** Получить список периодов для элемента hierselect
     * 
     * @return array массив пригодный для составления html-элемента select
     * @param int $progsbcid - id подписки на учебную программу в таблице programmsbcs
     * @param int $cstreamid - id потока в таблице cstreams
     */
    private function get_list_ages($progsbcid, $cstreamid=0)
    {
        // объявляем итоговый массив
        $result = array();
        // получаем все данные по подписке
        if ( ! $progsbc = $this->dof->storage('programmsbcs')->get($progsbcid) )
        {// не получили подписку на программу  - не имеет смысла выполнять
            // действия дальше, вернем пустой массив
            $result[0] = '--- '.$this->dof->get_string('to_select','cpassed').' ---';
            return $result;
        }
        if ( ! $programm = $this->dof->storage('programms')->get($progsbc->programmid) )
        {// не получили учебную программу  - не имеет смысла выполнять
            // действия дальше, вернем пустой массив
            $result[0] = '--- '.$this->dof->get_string('to_select','cpassed').' ---';
            return $result;
        }
        if ( ! $cstreamid )
        {// если поток не указан - то можно выбрать период
            // добавляем все допустимые варианты периодов:
            // для этого выясним минимальный и максимальный ageid
            $minageid = $progsbc->agestartid;
            $maxageid = $this->dof->storage('ages')->get_next_ageid($progsbc->agestartid, $programm->agenums);
            // после того как выяснили - извлечем из таблицы все подходящие по критериям записи
            if ( $ageslist = $this->dof->storage('ages')->get_ages_by_idrange($minageid, $maxageid) )
            {// если мы получили список периодов - сформируем из него массив
                // добавляем первый вариант со словом "выбрать"
                $result[0] = '--- '.$this->dof->get_string('to_select','cpassed').' ---';
                foreach ( $ageslist as $age )
                {// перебираем все периоды и составляем массив для select'а
                    $result[$age->id] = $age->name;
                }
            }else
            {// если мы не получили ни одного периода - сообщим об этом
                $result[0] = '('.$this->dof->get_string('not_found', 'cpassed').')';
            }
        }else
        {// если поток указан - то период указывается единственным образом (берется из потока)
            if ( ! $cstream = $this->dof->storage('cstreams')->get($cstreamid) )
            {// поток не найден - нет смысла выполнять дальнейшие действия. Сообщим об ошибке.
                $result[0] = '('.$this->dof->get_string('not_found', 'cpassed').')';
                return $result;
            }
            if ( $age = $this->dof->storage('ages')->get($cstream->ageid) )
            {// если период есть - у нас должен быть единственный вариант выбора
                $result[$age->id] = $age->name;
            }else
            {// если периода нет - сообщим об этом
                $result[0] = '('.$this->dof->get_string('not_found', 'cpassed').')';
            }
        }
        return $result;
    }
    
    /** Получить список предметов для элемента hierselect
     * 
     * @return array
     * @param int $progsbcid - id подписки ученика на поток
     */
    private function get_list_subjects($progsbcid)
    {
        // объявляем итоговый массив
        $result = array();
        // получаем запись подписки на программу по ее id
        if ( $progsbc = $this->dof->storage('programmsbcs')->get($progsbcid) )
        {// если получили программу - получаем список доступных предметов
            if ( $subjlist = $this->dof->storage('programmitems')->get_records(array('programmid'=>$progsbc->programmid)) )
            {// если получили список - то составляем массив  
                foreach ( $subjlist as $id=>$subject )
                {
                    $result[$id] = $subject->name.' ['.$subject->code.']';
                }
            }
        }
        // возвращаем массив, пригодный для формирования html-элемента select
        return array(0 => '--- '.$this->dof->get_string('to_select','cpassed').' ---') + $result;
    }

    
    /** Получить список учеников для добавления элемента select в форму
     * @todo определить, при помощи какой функции можно выяснить кто является учеником, а кто нет
     * @return array
     */
    private function get_list_students()
    {
        // добавляем первый вариант со словом "выбрать"
        $students = array();
        // извлекаем из базы все контракты
        $select = $this->dof->storage('contracts')->get_records(array('status'=>array('wesign', 'work', 'frozen')), 
                    'id, num, studentid, clientid');
        
        // заводим отдельный массив для учеников, чтобы потом сортировать его
        if ( $select )
        {// данные удалось извлечь
            foreach ($select as $id=>$record)
            {// составляем массив вида id=>ФИО
                // для этого извлекаем всех учеников из базы
                if ( ! empty($record->studentid) AND $this->dof->storage('programmsbcs')->count_list(array('contractid'=>$id)) )
                {// если они присутствуют в контракте и у них есть подписки на программу
                    $students[$id] = 
                        $this->dof->storage('persons')->get_fullname($record->studentid).' ('.$record->num.')';
                }
            }
            // оставим в списке только те объекты, на использование которых есть право
            $permissions = array(array('plugintype'=>'storage', 'plugincode'=>'contracts', 'code'=>'use'));
            $students    = $this->dof_get_acl_filtered_list($students, $permissions);
        }
        
        // сортируем массив по фамилиям учеников, чтобы было удобнее искать
        asort($students);
        // возвращаем результат вместе с надписью "выбрать"
        return array(0 => '--- '.$this->dof->get_string('to_select','cpassed').' ---') + $students;
    }
    
    private function get_type_cpassed($progsbcid)
    {
        // добавляем первый вариант со словом "индивидуальная"
        $result = array(0 => $this->dof->get_string('individual','cpassed'));
        if ( $progsbc = $this->dof->storage('programmsbcs')->get($progsbcid) )
        {// если подписка на программу указана
            if ( isset($progsbc->agroupid) AND ! empty($progsbc->agroupid) )
            {// и у ученика имеется группа - добавим ее в список
                if ( ! $agroupname = $this->dof->storage('agroups')->get_field($progsbc->agroupid,'name') )
                {
                    $agroupname = '-';
                }
                if ( ! $agroupcode = $this->dof->storage('agroups')->get_field($progsbc->agroupid,'code') )
                {
                    $agroupcode = '-';
                }
                $result[$progsbc->agroupid] = $agroupname.' ['.$agroupcode.']';
            }
        }
        // возвращаем результат вместе с надписью "индивидуальная"
        return $result;
    }   
    
    
    /**
     * Возвращает строку заголовка формы
     * @param int $cpassedid
     * @return string
     */
    private function get_form_title($cpassedid)
    {
        if ( ! $cpassedid )
        {//заголовок создания формы
            return $this->dof->get_string('newcpassed','cpassed');
        }else 
        {//заголовок редактирования формы
            return $this->dof->get_string('editcpassed','cpassed');
        }
    }
    
    /**
     * Возврашает название статуса
     * @return unknown_type
     */
    private function get_status_name($status)
    {
        return $this->dof->workflow('cpassed')->get_name($status);
    }
}

/** Класс формы для поиска подписки на курс
 * 
 */
class dof_im_cpassed_search_form extends dof_modlib_widgets_form
{
    /**
     * @var dof_control
     */
    protected $dof;
    
    /** Объявление всех элементов формы
     * 
     */
    function definition()
    {
        $this->dof = $this->_customdata->dof;
        // создаем ссылку на HTML_QuickForm
        $mform =& $this->_form;
        $mform->addElement('hidden','departmentid', optional_param('departmentid', 0, PARAM_INT));
        $mform->setType('departmentid', PARAM_INT);
        //создаем заголовок формы
        $mform->addElement('header','formtitle', $this->dof->get_string('search','cpassed'));
        
        // поле "период"
        $ages = $this->get_list_ages();
        $mform->addElement('select', 'ageid', $this->dof->get_string('age','cpassed').':', $ages);
        $mform->setType('ageid', PARAM_INT);
        // поле "предмет"
        $pitems = $this->get_list_pitems();
        $mform->addElement('select', 'programmitemid', $this->dof->get_string('subject','cpassed').':', $pitems);
        $mform->setType('programmitemid', PARAM_INT);
        // поле "учитель"
       /* $teachers = $this->get_list_teachers();
        $mform->addElement('select', 'teacherid', $this->dof->get_string('teacher','cpassed').':', $teachers);
        $mform->setType('teacherid', PARAM_INT);*/
        // поле "ученик"
        //$students = $this->get_list_students();
        //$mform->addElement('select', 'studentid', $this->dof->get_string('student','cpassed').':', $students);
        //$mform->setType('studentid', PARAM_INT);
        // поле "статус"
        $statuses = $this->get_list_statuses();
        $mform->addElement('select', 'status', $this->dof->get_string('status','cpassed').':', $statuses);
        $mform->setType('status', PARAM_TEXT);
        // кнопка "поиск"
        $this->add_action_buttons(false, $this->dof->get_string('to_find','cpassed'));
        // применение проверки ко всем элементам
        $mform->applyFilter('__ALL__', 'trim');        
    }
    
    /** Получить список всех учебных программ в нужном для формы поиска формате 
     * 
     * @return array массив для создания select-элемента
     */
    private function get_list_pitems()
    {
        // извлекаем все предметы
        $result = $this->dof->storage('programmitems')->
                    get_records(array('status'=>array('active')), 'name ASC', 'id, name, code');
        // преобразуем для использования в select
        $result = $this->dof_get_select_values($result);
        // оставим в списке только те объекты, на использование которых есть право
        $permissions = array(array('plugintype'=>'storage', 'plugincode'=>'programmitems', 'code'=>'use'));
        $result      = $this->dof_get_acl_filtered_list($result, $permissions);
        
        return $result;
    }
    
    /** Получить список всех периодов в нужном для формы поиска формате
     * 
     * @return array массив 
     */
    private function get_list_ages()
    {
        // извлекаем периоды
        $result = $this->dof->storage('ages')->
            get_records(array('status'=>array('plan','createstreams','createsbc','createschedule','active','completed')),
                            'name ASC', 'id, name');
        // преобразуем для использования в select
        $result = $this->dof_get_select_values($result);
        // оставим в списке только те объекты, на использование которых есть право
        $permissions = array(array('plugintype'=>'storage', 'plugincode'=>'ages', 'code'=>'use'));
        $result      = $this->dof_get_acl_filtered_list($result, $permissions);
        
        return $result;
    }
    
    
    /** Получить список учителей для добавления элемента select в форму
     * @todo определить, при помощи какой функции можно выяснить кто является учителем, а кто нет
     * @deprecated пока что не используется
     * @return array
     */
    private function get_list_teachers()
    {
        
    }
    
    /** Получить список учеников для добавления элемента select в форму
     * @todo определить, при помощи какой функции можно выяснить кто является учеником, а кто нет
     * @return array
     */
    private function get_list_students()
    {
        // добавляем первый вариант со словом "выбрать"
        $students = array();
        // извлекаем из базы все контракты
        $select = $this->dof->storage('contracts')->get_records(array('status'=>array('wesign', 'work', 'frozen')), 
                    'id, num, studentid, clientid');
        
        // заводим отдельный массив для учеников, чтобы потом сортировать его
        if ( $select )
        {// данные удалось извлечь
            foreach ($select as $id=>$record)
            {// составляем массив вида id=>ФИО
                // для этого извлекаем всех учеников из базы
                if ( ! empty($record->studentid) AND $this->dof->storage('programmsbcs')->count_list(array('contractid'=>$id)) )
                {// если они присутствуют в контракте и у них есть подписки на программу
                    $students[$id] = 
                        $this->dof->storage('persons')->get_fullname($record->studentid).' ('.$record->num.')';
                }
            }
            // оставим в списке только те объекты, на использование которых есть право
            $permissions = array(array('plugintype'=>'storage', 'plugincode'=>'contracts', 'code'=>'use'));
            $students    = $this->dof_get_acl_filtered_list($students, $permissions);
        }
        
        // сортируем массив по фамилиям учеников, чтобы было удобнее искать
        asort($students);
        // возвращаем результат вместе с надписью "выбрать"
        return array(0 => '--- '.$this->dof->get_string('to_select','cpassed').' ---') + $students;
    }
    
    /** Получить список всех доступных статусов учебного потока
     * 
     * @return array
     */
    private function get_list_statuses()
    {
        $statuses    = array();
        // добавляем значение, на случай, если по статусу искать не нужно
        $statuses[0] = '--- '.$this->dof->get_string('any_mr','cpassed').' ---';
        // получаем весь список статусов через workflow
        $statuses    = array_merge($statuses, $this->dof->workflow('cpassed')->get_list());
        // возвращаем список всех статусов
        return $statuses;
    }
}


/** Класс формы для создания подписки
 *
 */
class dof_im_cpassed_addpass_form extends dof_modlib_widgets_form
{
    private $agroupid;
    private $cstreamid;
    /**
     * @var dof_control
     */
    protected $dof;
    
    function definition()
    {
        $this->agroupid = $this->_customdata->agroupid;
        $this->cstreamid = $this->_customdata->cstreamid;
        $this->dof     = $this->_customdata->dof;
        // создаем ссылку на HTML_QuickForm
        $mform =& $this->_form;
        // скрытые поля
        $mform->addElement('hidden','cstreamid', $this->cstreamid);
        $mform->setType('cstreamid', PARAM_INT);
        $mform->addElement('hidden','agroupid', $this->agroupid);
        $mform->setType('agroupid', PARAM_INT);
        // находим список студентов группы не имеющие подписки
        if ( $contracts = $this->dof->storage('programmsbcs')->get_contracts_without_cpassed($this->agroupid, $this->cstreamid) )
        {// ученики найдены
        
            // оставим в списке только те объекты, на использование которых есть право
            $permissions = array(array('plugintype'=>'storage', 'plugincode'=>'contracts', 'code'=>'use'));
            $contracts = $this->dof_get_acl_filtered_list($contracts, $permissions);
            
            foreach ($contracts as $contract)
            {// создадим для каждого поле
                $studentname = $this->dof->storage('persons')->get_field($contract->studentid,'sortname');
                $mform->addElement('checkbox','addpass['.$contract->id.']', $studentname , $this->dof->get_string('to_sing','cpassed'));
            }
        }
        // кнопка создания
        $mform->addElement('submit', 'save', $this->dof->get_string('save_cpassed','cpassed'));
        // применение проверки ко всем элементам
        $mform->applyFilter('__ALL__', 'trim');
    }
}

/** Класс, отвечающий за форму смену статуса подписки на дисциплину вручную
 * 
 */
class dof_im_cpassed_changestatus_form extends dof_modlib_widgets_changestatus_form
{
    /**
     * @var dof_control
     */
    protected $dof;
    
    protected function im_code()
    {
        return 'cpassed';
    }
    
    protected function workflow_code()
    {
        return 'cpassed';
    }
}



class dof_im_cpassed_edit_pitem_form extends dof_modlib_widgets_form
{
    private $cpassed;
    /**
     * @var dof_control 
     */
    public $dof;
    
    /** Объявление формы
     * 
     * @return null
     */
    function definition()
    {// делаем глобальные переменные видимыми

        $this->cpassed = $this->_customdata->cpassed;
        $this->dof     = $this->_customdata->dof;

        // создаем ссылку на HTML_QuickForm
        $mform =& $this->_form;
        $mform->addElement('hidden','departmentid', optional_param('departmentid', 0, PARAM_INT));
        $mform->setType('departmentid', PARAM_INT);
        $mform->addElement('hidden','cpassedid', $this->cpassed->id);
        $mform->setType('cpassedid', PARAM_INT);
        $mform->addElement('hidden','sesskey', 0);
        $mform->setType('sesskey', PARAM_ALPHANUM);
        // устанавливаем значения как hidden-поля, чтобы потом забрать из них
        // значения при помощи definition_after_data
        $mform->addElement('hidden','studentid', 0);
        $mform->setType('studentid', PARAM_INT);
        $mform->addElement('hidden','programmsbcid', 0);
        $mform->setType('programmsbcid', PARAM_INT);
        $mform->addElement('hidden','programmitemid', 0);
        $mform->setType('programmitemid', PARAM_INT);
        $mform->addElement('hidden','cstreamid', 0);
        $mform->setType('cstreamid', PARAM_INT);
        $mform->addElement('hidden','ageid', 0);
        $mform->setType('ageid', PARAM_INT);
        $mform->addElement('hidden','agroupid', 0);
        $mform->setType('agroupid', PARAM_INT);
        
        // создаем заголовок формы
        $mform->addElement('header','formtitle', $this->get_form_title($this->cpassed->id));
        // получаем список всех элементов для hierselect
        $options = $this->get_select_options();
        // выравниваем строки по высоте
        $mform->addElement('html', '<div style=" line-height: 1.9; ">');
        // добавляем новый элемент выбора зависимых вариантов форму
        $mform->addElement('static', 'programm',  $this->dof->get_string('programm', 'cpassed').':');
        $mform->addElement('static', 'subject', $this->dof->get_string('subject', 'cpassed').':');
        $mform->addElement('static', 'cstream', $this->dof->get_string('cstream', 'cpassed').':');
        $mform->addElement('static', 'age', $this->dof->get_string('age', 'cpassed').':');
        // устанавливаем для него варианты ответа
        $myselectst =& $mform->addElement('hierselect', 'pidata', 
                                        $this->dof->get_string('student',      'cpassed').':<br/>'.
                                        $this->dof->get_string('type_cpassed', 'cpassed').':<br/>',
                                        null,'<br/>');
        $programmid = $this->dof->storage('programmitems')->get_field($this->cpassed->programmitemid,'programmid');
        $students = $this->get_list_students($programmid,$this->cpassed->programmitemid,$this->cpassed->ageid);
        foreach ( $students as $sbcid=>$student )
        {
            $type[$sbcid] = $this->get_type_cpassed($sbcid);
        }
        if ( isset($this->cpassed->status) )
        {    
            if ( $this->cpassed->status != 'plan' AND ! $this->dof->storage('cpassed')->is_access('edit:studentid') )
            {// если статус не "plan" то нельзя редактировать студента
                // найдем контракт
                $sbcs = $this->dof->storage('programmsbcs')->get($this->cpassed->programmsbcid);
                $contrac = $this->dof->storage('contracts')->get($sbcs->contractid);
                // полное имя + контракт 
                $students = array();
                $students[$sbcs->id] = $this->dof->storage('persons')->get_fullname($this->cpassed->studentid).'('.$contrac->num.')';
                $type[$sbcs->id] = $this->get_type_cpassed($sbcid);              
            }
        }
        $myselectst->setOptions(array($students,$type));
        // закрываем тег выравнивания строк
        $mform->addElement('html', '</div>');
        if ( $this->cpassed->id )
        {// выведем поле "статус" если форма редактируется
            $mform->addElement('static', 'status', $this->dof->get_string('status','cpassed').':', 
                                    $this->get_status_name($this->cpassed->status));
        }
        
        // кнопки сохранить и отмена
        $this->add_action_buttons(true, $this->dof->get_string('to_save','cpassed'));
        // применение проверки ко всем элементам
        $mform->applyFilter('__ALL__', 'trim');
    }
    
    /** Установка значений по умолчанию для сложных элементов
     * 
     * @return 
     */
    function definition_after_data()
    {
        // создаем ссылку на HTML_QuickForm
        $mform =& $this->_form;
        // получаем id ученика
        $studentid      = $mform->getElementValue('studentid');
        
        // получаем предмет
        $programmitemid = $mform->getElementValue('programmitemid');
        $item = $this->dof->storage('programmitems')->get($programmitemid);
        $itemname = $item->name.' ['.$item->code.']';
        // получаем программу
        $programm = $this->dof->storage('programms')->get($item->programmid);
        $programmname = $programm->name.' ['.$programm->code.']';
        // получаем id потока 
        $cstreamid = $mform->getElementValue('cstreamid');
        $cstreamname = $this->dof->storage('cstreams')->get_field($cstreamid,'name');
        // получаем id подписки на программу
        $programmsbcid  = $mform->getElementValue('programmsbcid');
        // получаем период
        $ageid = $mform->getElementValue('ageid');
        $agename = $this->dof->storage('ages')->get_field($ageid,'name');
        $agroupid = $mform->getElementValue('agroupid');

        // устанавливаем значения по умолчанию для всех полей элемента hierselect
        $mform->setDefault('programm', $programmname);
        $mform->setDefault('subject', $itemname);
        $mform->setDefault('cstream', $cstreamname);
        $mform->setDefault('age', $agename);
        $mform->setDefault('pidata', array($programmsbcid,$agroupid));
        // применение проверки ко всем элементам
        $mform->applyFilter('__ALL__', 'trim');
        
    }
    
    /** Проверка данных на стороне сервера
     * @return 
     * @param object $data[optional] - массив с данными из формы
     * @param object $files[optional] - массив отправленнных в форму файлов (если они есть)
     */
    public function validation($data,$files)
    {
        $errors = array();
        //print_object($data);
        // проверим существование периода
        if ( empty($data['pidata'][0]) AND ! $progsbc = $this->dof->storage('programmsbcs')->get($data['pidata'][0]) )
        {// подписка на программу не существует
            $errors['pidata'] = $this->dof->get_string('err_required_multi','cpassed');
        }
        // проверим существование потока, если он указан
        if ( $data['cstreamid'] AND ! $cstream = $this->dof->storage('cstreams')->get($data['cstreamid']) )
        {// поток не существует, сообщим об этом
            $errors['pidata'] = $this->dof->get_string('cstream_not_exists','cpassed');
        }elseif ( $data['cstreamid'] )
        {// если поток существует - проверим правильность его привязки к программе
            if ( ! $pitem = $this->dof->storage('programmitems')->get($cstream->programmitemid) )
            {// если не найден элемент учебной программы - это ошибка
                $errors['pidata'] = $this->dof->get_string('wrong_cstream_and_programsbc','cpassed');
            }elseif ( isset($progsbc->programmid) AND $pitem->programmid != $progsbc->programmid )
            {// если элемент программы найден, про принадлежит к другому потоку - это ошибка
                $errors['pidata'] = $this->dof->get_string('wrong_cstream_and_programsbc','cpassed');
            }
        }
        // если ошибки есть - то пользователь вернется на страницу редактирования и увидит их
        return $errors;
    }
    
    /** Получить весь список опций для элемента hierselect
     * @todo переделать эту функцию в рекурсивную процедуру, чтобы сократить объем кода
     * @return stdClass object объект, содержащий данные для элемента hierselect
     */
    function get_select_options()
    {
        $result = new object();
        // создаем массив для учебных программ
        $programms = array();
        $cstreams  = array();
        // для каждого ученика определяем список программ на которые он подписан
        $plist = $this->get_programms();
        // создадим иерархию второго уровня
        $programms = $plist;
        foreach ( $plist as $progid=>$programm )
        {// составляем список разрешенных предметов
            $subjlist = $this->get_list_subjects($progid);
            // создаем иерархию третьего уровня
            $subjects[$progid] = $subjlist;
            foreach ( $subjlist as $pitemid=>$subject )
            {// составляем список разрешенных учебных потоков
                $cstreamlist = $this->get_list_cstreams($pitemid);
                // создаем иерархию четвертого уровня
                $cstreams[$progid][$pitemid] = $cstreamlist;
                foreach ( $cstreamlist as $csid=>$cstream )
                {// составляем список разрешенных периодов
                    $agelist = $this->get_list_ages($csid);
                    // создаем иерархию пятого уровня
                    $ages[$progid][$pitemid][$csid] = $agelist;
                    
                }
            }
        }
        // записываем в результурующий объект все что мы получили
        $result->programms = $programms;
        $result->subjects  = $subjects;
        $result->cstreams  = $cstreams;
        $result->ages      = $ages;
        //print_object($result);
        // возвращаем все составленные массивы в упорядоченном виде
        return $result;
    }
    
    /** Получить список программ, на которые подписан ученик
     * @todo добавить в зависимости плагин контрактов, программ, и подписок на программы
     * @todo убрать из списка программы с неправильными статусами
     * @todo при редактировании всегда включать программу ученика, вне зависимости от статуса и прав
     * @return array массив вариантов для элемента hierselect
     * @param int $contractid - id контракта ученика для которого получается список программ. Таблица contracts.
     */
    private function get_programms()
    {
        $result = array();
        if ( $programms = $this->dof->storage('programms')->get_records(array()) )
        {// получили id программ - теперь получим их названия
            // оставим в списке только те объекты, на использование которых есть право
            $permissions = array(array('plugintype'=>'storage', 'plugincode'=>'programms', 'code'=>'use'));
            $programms   = $this->dof_get_acl_filtered_list($programms, $permissions);
            
            $result = $this->dof_get_select_values($programms,true,null,array('name','code'));
        }
        // возвращаем то, что набрали
        return $result;
    }
    
    /** Получить список учебных потоков, разрешенных данной учебной программой
     * @return array массив допустимых учебных потоков
     * @todo убрать объекты с неправильным статусом
     * @param object $progitemid - id изучаемого предмета в таблице programmitems
     */
    private function get_list_cstreams($progitemid)
    {
        $result = array();
        // получаем все учебные потоки для предмета
        $result = $this->dof->storage('cstreams')->get_records(array('programmitemid'=>$progitemid));
        $result = $this->dof_get_select_values($result);
        // оставим в списке только те объекты, на использование которых есть право
        $permissions = array(array('plugintype'=>'storage', 'plugincode'=>'cstreams', 'code'=>'use'));
        $result = $this->dof_get_acl_filtered_list($result, $permissions);
        
        return $result;
    }
    
    /** Получить список периодов для элемента hierselect
     * 
     * @return array массив пригодный для составления html-элемента select
     * @param int $progsbcid - id подписки на учебную программу в таблице programmsbcs
     * @param int $cstreamid - id потока в таблице cstreams
     */
    private function get_list_ages($cstreamid)
    {
        // объявляем итоговый массив
        $result = array();
        // период указывается единственным образом (берется из потока)
        if ( ! $cstream = $this->dof->storage('cstreams')->get($cstreamid) )
        {// поток не найден - нет смысла выполнять дальнейшие действия. Сообщим об ошибке.
            $result[0] = '('.$this->dof->get_string('not_found', 'cpassed').')';
            return $result;
        }
        if ( $age = $this->dof->storage('ages')->get($cstream->ageid) )
        {// если период есть - у нас должен быть единственный вариант выбора
            $result[$age->id] = $age->name.' ';
        }else
        {// если периода нет - сообщим об этом
            $result[0] = '('.$this->dof->get_string('not_found', 'cpassed').')';
        }
        return $result;
    }
    
    /** Получить список предметов для элемента hierselect
     * 
     * @return array
     * @todo убрать объекты с неправильным статусом
     * @param int $progsbcid - id подписки ученика на поток
     */
    private function get_list_subjects($progid)
    {
        // объявляем итоговый массив
        $result = array();
        // добавляем первый вариант со словом "выбрать"
        $result[0] = '--- '.$this->dof->get_string('to_select','cpassed').' ---';
        if ( $subjlist = $this->dof->storage('programmitems')->get_records(array('programmid'=>$progid)) )
        {// если получили список - то составляем массив  
            $result = $this->dof_get_select_values($subjlist,true,null,array('name','code'));
        }
        // оставим в списке только те объекты, на использование которых есть право
        $permissions = array(array('plugintype'=>'storage', 'plugincode'=>'programmitems', 'code'=>'use'));
        $result = $this->dof_get_acl_filtered_list($result, $permissions);
        // возвращаем массив, пригодный для формирования html-элемента select
        return $result;
    }

    
    /** Получить список учеников для добавления элемента select в форму
     * @todo определить, при помощи какой функции можно выяснить кто является учеником, а кто нет
     * @return array
     */
    private function get_list_students($programmid,$pitemid,$ageid)
    {
        // добавляем первый вариант со словом "выбрать"
        $students = array();
        if ( $agenum = $this->dof->storage('programmitems')->get_field($pitemid,'agenum') AND $agenum != 0 )
        {// если предмет отнесен к конкретной параллели
            // подписки отображаем только этой параллели';
            $sbcs = $this->dof->storage('programmsbcs')->get_records(array('programmid'=>$programmid,'agenum'=>$agenum));
        }else
        {   // иначе найдем все для программы';
            $sbcs = $this->dof->storage('programmsbcs')->get_records(array('programmid'=>$programmid));
        }
        if ( ! $sbcs )
        {// данные не удалось извлечь
            return array(0 => '--- '.$this->dof->get_string('to_select','cpassed').' ---');
        }
        foreach ($sbcs as $id=>$record)
        {// составляем массив вида id=>ФИО
            //$sbcageid = $this->dof->storage('ages')->get_next_ageid($record->agestartid, $record->agenum);
            //if ( $sbcageid != $ageid )
            //{// период подписки не совпадает с периодом потока - пропускаем подписку
            //    continue;
            //}
            if ( isset($record->contractid) AND ! $contract = $this->dof->storage('contracts')->get($record->contractid) )
            {// если контракт не удалось извлечь - это ошибка
                continue;
            }
            if ( ! empty($contract->studentid) )
            {// если ученик присутствует в контракте
                $students[$id] =
                     $this->dof->storage('persons')->get_fullname($contract->studentid).' ('.$contract->num.')';
            }
        }
        // оставим в списке только те объекты, на использование которых есть право
        $permissions = array(array('plugintype'=>'storage', 'plugincode'=>'persons', 'code'=>'use'));
        $students = $this->dof_get_acl_filtered_list($students, $permissions);
        // сортируем массив по фамилиям учеников, чтобы было удобнее искать
        asort($students);
        // возвращаем результат вместе с надписью "выбрать"
        return array(0 => '--- '.$this->dof->get_string('to_select','cpassed').' ---') + $students;
    }

    /** Получить список учеников для добавления элемента select в форму
     * @todo определить, при помощи какой функции можно выяснить кто является учеником, а кто нет
     * @return array
     */
    private function get_type_cpassed($progsbcid)
    {
        // добавляем первый вариант со словом "индивидуальная"
        $result = array(0 => $this->dof->get_string('individual','cpassed'));
        if ( $progsbc = $this->dof->storage('programmsbcs')->get($progsbcid) )
        {// если подписка на программу указана
            if ( isset($progsbc->agroupid) AND ! empty($progsbc->agroupid) )
            {// и у ученика имеется группа - добавим ее в список
                $agroupname = $this->dof->storage('agroups')->get_field($progsbc->agroupid,'name');
                $agroupcode = $this->dof->storage('agroups')->get_field($progsbc->agroupid,'code');
                $result[$progsbc->agroupid] = $agroupname.' ['.$agroupcode.']';
            }
        }
        // возвращаем результат вместе с надписью "индивидуальная"
        return $result;
    }   
    
    
    /**
     * Возвращает строку заголовка формы
     * @param int $cpassedid
     * @return string
     */
    private function get_form_title($cpassedid)
    {
        if ( ! $cpassedid )
        {//заголовок создания формы
            return $this->dof->get_string('newcpassed','cpassed');
        }else 
        {//заголовок редактирования формы
            return $this->dof->get_string('editcpassed','cpassed');
        }
    }
    
    /**
     * Возврашает название статуса
     * @return unknown_type
     */
    private function get_status_name($status)
    {
        return $this->dof->workflow('cpassed')->get_name($status);
    }
}
?>