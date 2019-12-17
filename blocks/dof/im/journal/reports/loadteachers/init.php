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

/** Полный отчет о деятельности учинека
 *
 */
class dof_im_journal_report_loadteachers extends dof_storage_reports_basereport
{
    // Параметры для работы с шаблоном
    protected $templatertype = 'im';
    protected $templatercode = 'journal';
    protected $templatertemplatename = 'load_teachers';
    /* Код плагина, объявившего тип приказа
    */
    public function code()
    {
        return 'loadteachers';
    }
    
    /* Имя плагина, объявившего тип приказа
    */ 
    public function name()
    {
        return $this->dof->get_string('loadteachers', 'journal');
    }
    
    /*
     * Тип плагина
     */
    public function plugintype()
    {
        return 'im';
    }
    
    /*
     * Код плагина
     */
    public function plugincode()
    {
        return 'journal';
    }    
    
    
    /**
     * Метод, предусмотренный для расширения логики сохранения
     */
    protected function save_data(object $report)
    {
        $a = new object;
        $a->begindate = dof_userdate($report->begindate,'%d.%m.%Y');
        $a->enddate = dof_userdate($report->enddate,'%d.%m.%Y');
        $report->name = $this->dof->get_string('loadteachers_time', 'journal', $a);
        
        return $report;
    }     
    
 

    /** Метод записывает в отчет все данные по студентам и
     * возвращает уже полный отчет
     * @param object $report - отчет, по который доформировываем )
     * @return object $report - объект 
     */
    public function generate_data($report)
    {
        if ( ! is_object($report) )
        {// не того типа передали даные
            return false;
        }
        // переопределяем данные
        $beginday = dof_usergetdate($report->begindate);   
        $endday = dof_usergetdate($report->enddate); 
        $begindate = mktime(0,0,0,$beginday['mon'],$beginday['mday'],$beginday['year']);
        $enddate = mktime(23,59,59,$endday['mon'],$endday['mday'],$endday['year']);
        $teachers   = array();
        // учтем подразделения
        // тут ОБЪЕКТ имеет ту же величину, что и подразделение
        // он изначально так сохраняется
        if ( $report->objectid )
        {
            $appoits = $this->dof->storage('appointments')->get_records(array('status'=>'active','departmentid'=>$report->objectid));
        }else 
        {
            $appoits = $this->dof->storage('appointments')->get_records(array('status'=>'active'));
        }
        //получаем все активные назначения на должность
        if ( $appoits )
        {
            // для того чтобы отобразить сколько контрактов осталось обработать - посчитаем их количество
            $totalcount   = count($appoits);
            $currentcount = 0;
            foreach ( $appoits as $appoit )
            {// отчет о нагрузке учителей
                // Выводим сообщение о том какой контракт проверяется сейчас, и сколько контрактов осталось
                // (информация отображается при запуске cron.php)
                ++$currentcount;
                $mtracestring = 'Prosessing appointid: '.$appoit->id.' ('.$currentcount.'/'.$totalcount.')';
                $this->dof->mtrace(2, $mtracestring);         
                // собираем данные по учителю
                $teacher = $this->get_string_load($appoit, $begindate, $enddate, 
                         $report->departmentid,(bool)$report->data->forecast);
                $teachers[$appoit->id] = $teacher;
            }
            // сортировка по имени
            uasort($teachers, array('dof_im_journal_report_loadteachers', 'sortapp_by_sortname2'));  
            
            // допол информация
            $report->data->info = $this->dof->get_string('info','journal');
            $report->data->depart = $this->dof->get_string('department','journal');
            if ( $report->departmentid )
            {// отчет по подразделению
                $dep = $this->dof->storage('departments')->get($report->departmentid);
                $report->data->depart_name = $dep->name.'['.$dep->code.']';    
            }else 
            {// все отчеты
                $report->data->depart_name = $this->dof->get_string('all_departs','journal');
            }     
            $report->data->data_complete = $this->dof->get_string('data_complete','journal');
            $report->data->data_begin_name = $this->dof->get_string('data_begin','journal');
            $report->data->data_begin = dof_userdate($report->crondate,'%d.%m.%Y %H:%M');
            $report->data->request_name = $this->dof->get_string('request_name','journal');
            $report->data->requestdate = dof_userdate($report->requestdate,'%d.%m.%Y %H:%M');
            // дозапишем наш объект 
            $report->data->column_persons = $teachers;
            $report->data->column_teacher = $this->dof->get_string('teacher_fio','journal');
            $report->data->column_eagreement = $this->dof->get_string('eagreement','journal');
            $report->data->column_appoint = $this->dof->get_string('appointment','journal');
            $report->data->column_tabelload = $this->dof->get_string('week_tabel_load','journal');
            $report->data->column_fixload = $this->dof->get_string('week_fix_load','journal');
            $report->data->column_planload = $this->dof->get_string('plan_load','journal');
            $report->data->column_executeload = $this->dof->get_string('execute_load','journal');
            $report->data->column_replace = $this->dof->get_string('replace_postpone_events','journal');
            $report->data->column_cancel = $this->dof->get_string('cancel_events','journal');
            $report->data->column_salarypoints = $this->dof->get_string('loadteacher_rhours','journal');
        }
        return $report;
    }
    
    /**
     * Строка для вывода одного события
     * @return object $templater - объект с данныим для строчки события
     */
    public function get_string_load($appoit, $begindate, $enddate, $departmentid, $forecast)
    {
        $templater = new object();
        // формируем строку таблицы
        if ( $person = $this->dof->storage('appointments')->get_person_by_appointment($appoit->id) )
        {// если есть есть персона - выведем имя
            $templater->teacher = $this->dof->storage('persons')->get_fullname($person->id);
        }else 
        {
            $templater->teacher = '';
        }
        // отобразим номер договора
        $templater->eagreement = $this->dof->storage('eagreements')->get_field($appoit->eagreementid,'num');
        $templater->appoint = $appoit->enumber;
        $templater->tabelload = round($appoit->worktime, 2);
        // найдем назначенную нагрузку
        $templater->fixload = 0;
        if ( $cstreams = $this->dof->storage('cstreams')->get_records(array('appointmentid'=>$appoit->id,'status'=>'active')) )
        {// если есть потоки
            foreach ( $cstreams as $cstream )
            {// формируем строку для каждого
                $templater->fixload += $cstream->hoursweek;
            }
        }
        // отобразим остальную нагрузку по урокам
        $templater->planload = 0;
        $templater->executeload = 0;
        $templater->prevexecuteload = 0;
        $templater->replace = 0;
        $templater->cancel = 0;
        $salarypoints = 0;
        $prevsalarypoints = 0;
        $totalrhours = 0;
        $templater->prevtotalrhours = 0;
        $templater->events = array();
        $templater->prevevents = array();
        // собираем данные для выборки
        $counds = new stdClass;
        $counds->appointmentid = $appoit->id;
        $counds->date_from = $begindate;
        $counds->date_to = $enddate;
        $select = $this->dof->storage('schevents')->get_select_listing($counds);
        if ( $events = $this->dof->storage('schevents')->get_records_select($select,null,'date') )
        {// если они есть
            foreach ( $events as $event )
            {
                if ( $event->status == 'canceled' )
                {// отмененные уроки
                    $templater->cancel++;
                }
                if ( $event->status == 'completed' OR $event->status == 'implied')
                {// исполненная нагрузка
                    $templater->executeload++;
                    $salarypoints += $event->rhours;
                }
                //$salarypoints += $event->rhours;
                if ( $event->status != 'canceled' AND empty($event->replaceid) )
                {// плановая нагрузка
                    $templater->planload++;
                }
                if ( $event->status == 'replaced' OR $event->status == 'postponed' )
                {// замены
                    $templater->replace++;
                }
                if ( ! empty(unserialize($event->salfactorparts)->vars) AND $event->status != 'canceled' AND empty($event->replaceid))                
                {
                    $templater->events[] = $this->get_string_event($event);
                }
            }
        }
        // сумма баллов
        $templater->totalrhours = $salarypoints;
        if ( $forecast )
        {
            $templater->forecast = round($salarypoints/6,'2');
            $salarypoints += $templater->forecast;
            $dateday = dof_usergetdate($begindate);
            $counds = new stdClass;
            $counds->appointmentid = $appoit->id;
            $counds->date_from = mktime(12,0,0,$dateday['mon']-1,1,$dateday['year']);
            $counds->date_to = mktime(12,0,0,$dateday['mon']-1,25,$dateday['year']);
            $select = $this->dof->storage('schevents')->get_select_listing($counds);
            if ( $events = $this->dof->storage('schevents')->get_records_select($select) )
            {// если они есть
                foreach ( $events as $event )
                {
                    if ( $event->status == 'completed' OR $event->status == 'implied')
                    {// исполненная нагрузка
                        $prevsalarypoints += $event->rhours;
                    }
                }
            }
            $counds->date_from = mktime(12,0,0,$dateday['mon']-1,26,$dateday['year']);
            $counds->date_to = mktime(12,0,0,$dateday['mon'],0,$dateday['year']);
            $select = $this->dof->storage('schevents')->get_select_listing($counds);
            if ( $events = $this->dof->storage('schevents')->get_records_select($select,null,'date') )
            {// если они есть
                foreach ( $events as $event )
                {
                    if ( $event->status == 'completed' OR $event->status == 'implied')
                    {// исполненная нагрузка
                        $templater->prevexecuteload++;
                        $templater->prevtotalrhours += $event->rhours;
                    }
                    $templater->prevevents[] = $this->get_string_event($event);
                }
            }
            $templater->prevforecast = round($prevsalarypoints/6,'2');
            
        }
        // зарплатные баллы
        $templater->salarypoints = $salarypoints;
        $templater->url = '';
        if ( $salarypoints > 0 )
        {
            $url_params = array('id' => $this->id,
                    'appointid' => $appoit->id,
                    'begindate' => $begindate,
                    'enddate' => $enddate,
                    'departmentid' => $departmentid);
            $templater->url = $this->dof->url_im('journal',
                    '/reports/loadteachers/loadteacher.php', $url_params);
            $templater->salarypoints = '<a href='.$templater->url.'>'.$templater->salarypoints.'</a>';     
        }

        return $templater;
    }

    /**
     * Строка для вывода одного события
     * @return object $templater - объект с данныим для строчки события
     */
    public function get_string_event($event)
    {
        // добаваем параметры
        $salfactorparts = unserialize($event->salfactorparts);
        $params = $salfactorparts->vars;
        $obj = new stdClass();
        // время
        $obj->formula = $salfactorparts->formula;
        
        // число
        $obj->date = dof_userdate($event->date, "%d/%m/%Y");
        
        // время
        $obj->time = dof_userdate($event->date, "%H:%M");
        
        // прдолжительность урока
        $obj->duration = ($event->duration/60).' ' .$this->dof->modlib('ig')->igs('min').'. ';
        $obj->individual = $this->dof->get_string('no', 'journal');
        if ($params['schevent_individual'])
        {// индивидуальный урок
            $obj->individual = $this->dof->get_string('yes', 'journal');
        }
        
        // количество студентов
        $obj->countstudents = $params['count_active_cpassed'];
        $obj->countstudents_salfactor = $params['config_salfactor_countstudents'];
        
        // поправочный зарплатный коэффициент предмета
        $obj->salfactor_programmitem = $params['programmitem_salfactor'];
        
        // поправочный зарплатный коэффициент подписок
        $obj->salfactor_programmsbcs = $params['programmsbcs_salfactors']['all'];
        unset($params['programmsbcs_salfactors']['all']);
        $obj->programmsbcs = array();
        foreach ( $params['programmsbcs_salfactors'] as $id=>$salfactor )
        {
            $programmsbc_salfactor = new stdClass;
            $departmentid = $this->dof->storage('programmsbcs')->get_field($id,'departmentid');
            $url_params = array('programmsbcid' => $id,
                'departmentid' => $departmentid);
            $programmsbc_salfactor->salfactor = '<a href='.$this->dof->url_im('programmsbcs',
        '/view.php', $url_params).'>'.$salfactor.'</a>';
            $obj->programmsbcs[] = $programmsbc_salfactor;
        }
        
        // поправочный зарплатный коэффициент групп
        $obj->salfactor_agroups = $params['agroups_salfactors']['all'];
        unset($params['agroups_salfactors']['all']);
        $obj->agroups = array();
        foreach ( $params['agroups_salfactors'] as $id=>$salfactor )
        {
            $agroup_salfactor = new stdClass;
            $departmentid = $this->dof->storage('agroups')->get_field($id,'departmentid');
            $url_params = array('agroupid' => $id,
                'departmentid' => $departmentid);
            $agroup_salfactor->salfactor = '<a href='.$this->dof->url_im('agroups',
        '/view.php', $url_params).'>'.$salfactor.'</a>';
            $obj->agroups[] = $agroup_salfactor;
        }
        $obj->color = 'black';
        if ( $event->status == 'implied')
        {// исполненная нагрузка
            $obj->color = '#A52A2A';
        }
        // поправочный зарплатный коэффициент потока
        $obj->salfactor_cstreams = $params['cstreams_salfactor'];
        
        // замещающий зарплатный коэффициент потока
        $obj->substsalfactor_cstreams = $params['cstreams_substsalfactor'];
        
        // поправочный зарплатный коэффициент шаблона
        $obj->salfactor_schtemplates = $params['schtemplates_salfactor'];

        // академические часы
        $obj->ahours = $params['ahours'];
        
        // оооведение урока по факту
        $obj->complete = $params['schevents_completed'];
        
        // суммарный балл
        $obj->rhours = $event->rhours;
        
        // добавляем строку в таблицу
        return $obj;
    }
    
    /** Отобразить отчет в формате HTML
     * 
     */
    public function show_report_html($addvars=null)
    {
        $error = '';
        $table = '';
        if ( ! $this->is_generate($this->load()) )
        {//  отчет еще не сгенерирован
            $error = $this->dof->get_string('report_no_generate','journal');
        }else
        {// загружаем шаблон
            // достаем данные из файла
            $template = $this->load_file();
            
            // подгружаем методы работы с шаблоном
            if ( isset($template->column_persons) )
            {
                $templater = $this->template();                
                if ( ! $templater )
                {//не смогли
                    $error = $this->dof->get_string('report_no_get_template','journal');
                }elseif ( ! $table = $templater->get_file('html') )
                {// не смогли загрузить html-таблицу
                    $error = $this->dof->get_string('report_no_get_table','journal');
                }
            }else 
            {
                $error = $this->dof->get_string('no_data','journal','<br>');
            }
        }

        // вывод ошибок
        print '<p style=" color:red; text-align:center; "><b>'.$error.'</b></p>';
        echo $table;
        
        if ( ! $error )
        {// вывод легенды
            print '<b>'.$this->dof->get_string('legend', 'journal').':</b><br>
               - '.$this->dof->get_string('legend_week_tabel_load', 'journal').';<br>
               - '.$this->dof->get_string('legend_week_fix_load', 'journal').';<br>
               - '.$this->dof->get_string('legend_plan_load', 'journal').';<br>
               - '.$this->dof->get_string('legend_execute_load', 'journal').';<br>
               - '.$this->dof->get_string('legend_replace_postpone_events', 'journal').';<br>
               - '.$this->dof->get_string('legend_cancel_events', 'journal').';<br>
               - '.$this->dof->get_string('legend_salfactors', 'journal').'.<br><br>';
            // скачать в формате csv
            print '<a href="'.$this->dof->url_im('journal',
                '/reports/import.php?reportid='.$this->id.'&type=loadteachers',$addvars).'">'.
                $this->dof->get_string('download_excel','journal','csv').'</a>';
            echo '<br>';
            // скачать в формате xml
            print '<a href="'.$this->dof->url_im('journal',
                '/reports/import.php?reportid='.$this->id.'&type=loadteachers&format=xls',$addvars).'">'.
                $this->dof->get_string('download_excel','journal','xls').'</a>';
        }
    } 

    protected function template_data($template)
    {
        if ( $template AND ! isset($template->column_salarypoints) )
        {// установлена старая структура отчета - добавим зарплатные баллы
            $template->column_salarypoints = $this->dof->get_string('salary_points', 'journal');
            foreach ($template->column_persons as $key=>$person)
            {
                $person->salarypoints = 0;
                $template->column_persons[$key] = $person;
            }
        }
        if ( isset($template->forecast) AND $template->forecast )
        {
            $template->column_correction = '<th class="header c0">'.
                $this->dof->get_string('correction_for_previous_month', 'journal').'</th>';
            foreach ($template->column_persons as $key=>$person)
            {
                $person->correction = '<td style="text-align:center;" class="cell c0">'.
                    ($person->prevtotalrhours - $person->prevforecast).'</td>';
                $template->column_persons[$key] = $person;
            }
        }
        return $template;
    }  
    
    /**
     * Функция сравнения двух объектов 
     * из таблицы persons по полю sortname
     * @param object $person1 - запись из таблицы persons
     * @param object $person2 - другая запись из таблицы persons
     * @return -1, 0, 1 в зависимости от результата сравнения
     */
    private function sortapp_by_sortname2($person1,$person2)
    {
        return strnatcmp($person1->teacher, $person2->teacher);
    }  
    
    public function dof_im_journal_get_loadteacher($appointmentid, $begindate, $enddate)
    {
        $template = $this->load_file();
        $teacher = new stdClass();
        //print_object($template);

        $teacher->title = $template->_name;
        if ( isset($template->forecast) AND $template->forecast )
        {
            $teacher->name_onetable = $this->dof->get_string('correction_for_previous_month', 'journal');
            $teacher->name_twotable = $this->dof->get_string('salhours_for_1_25_days', 'journal');
            $teacher->name_treetable = $this->dof->get_string('total_to_pay', 'journal');
            $teacher->prevloaddata = $template->column_persons[$appointmentid]->prevevents;
            if ( ! empty($teacher->prevloaddata) )
            {// уроки не пустые
                foreach ( $teacher->prevloaddata as $id=>$key )
                {
                    $teacher->prevloaddata[$id]->prevprogrammsbcs = 
                          $template->column_persons[$appointmentid]->prevevents[$id]->programmsbcs;
                    $teacher->prevloaddata[$id]->prevagroups = 
                          $template->column_persons[$appointmentid]->prevevents[$id]->agroups;
                }
            }
            $teacher->score = $this->dof->get_string('loadteacher_score', 'journal');
            // фактические часы
            $teacher->prevexecuteload = $template->column_persons[$appointmentid]->prevexecuteload;
            // сумма баллов
            $teacher->prevtotalrhours = $template->column_persons[$appointmentid]->prevtotalrhours;
            $teacher->prevforecast = $this->dof->get_string('paid_for_previous_month', 'journal').
                      ' = <b>'.$template->column_persons[$appointmentid]->prevforecast.'</b>';
            $teacher->prevrhours = $this->dof->get_string('execute_for_previous_month', 'journal').
                      ' = <b>'.$template->column_persons[$appointmentid]->prevtotalrhours.'</b>';
            $teacher->correction = $this->dof->get_string('correction_for_previous_month', 'journal').
                      ' = <b>'.($template->column_persons[$appointmentid]->prevtotalrhours-
                                $template->column_persons[$appointmentid]->prevforecast).'</b>';
            $teacher->allrhours = $this->dof->get_string('salhours_for_1_25_days', 'journal').
                      ' = <b>'.$template->column_persons[$appointmentid]->totalrhours.'</b>';
            $teacher->forecast = $this->dof->get_string('forecast_on_end_month', 'journal').
                      ' = <b>'.$template->column_persons[$appointmentid]->forecast.'</b>';
            $teacher->totalpay = $this->dof->get_string('total_to_pay', 'journal').
                      ' = <b>'.($template->column_persons[$appointmentid]->prevtotalrhours-
                                $template->column_persons[$appointmentid]->prevforecast+
                                $template->column_persons[$appointmentid]->totalrhours+
                                $template->column_persons[$appointmentid]->forecast).'</b>';
        }
        if ( $person = $this->dof->storage('appointments')->get_person_by_appointment($appointmentid) )
        {// если есть есть персона - выведем имя
            $teacher->fullname = $this->dof->storage('persons')->get_fullname($person->id);
        }else
        {
            $teacher->fullname = '';
        }
        
        $teacher->column_date = $this->dof->get_string('loadteacher_date', 'journal');
        $teacher->column_time = $this->dof->get_string('loadteacher_time', 'journal');
        $teacher->column_duration = $this->dof->get_string('loadteacher_duration', 'journal');
        $teacher->column_individual = $this->dof->get_string('loadteacher_individual', 'journal');
        $teacher->column_countstudents = $this->dof->get_string('loadteacher_countstudents', 'journal');
        $teacher->column_salfactor_programmitem = $this->dof->get_string('loadteacher_programmitem', 'journal');
        $teacher->column_salfactor_students = $this->dof->get_string('loadteacher_students', 'journal');
        $teacher->column_salfactor_cstream  = $this->dof->get_string('loadteacher_cstream', 'journal');
        $teacher->column_substsalfactor_cstream  = $this->dof->get_string('loadteacher_cstreamsub', 'journal');
        $teacher->column_salfactor_schtemplate = $this->dof->get_string('loadteacher_schtemplate', 'journal');
        $teacher->column_salfactor_agroup = $this->dof->get_string('loadteacher_agroup', 'journal');
        $teacher->column_complete = $this->dof->get_string('loadteacher_complete', 'journal');
        $teacher->column_ahours = $this->dof->get_string('loadteacher_ahours', 'journal');
        
        // суммарный коэффициент и формула расчета
        $teacher->column_rhours = $this->dof->get_string('loadteacher_rhours', 'journal');
        $teacher->loaddata = $template->column_persons[$appointmentid]->events;
        $teacher->score = $this->dof->get_string('loadteacher_score', 'journal');
        // фактические часы
        $teacher->executeload = $template->column_persons[$appointmentid]->executeload;
        // сумма баллов
        $teacher->totalrhours = $template->column_persons[$appointmentid]->totalrhours;
        // выводим нагрузку учителя
        if ( isset($template->forecast) AND $template->forecast )
        {
           $templater_package = $this->dof->modlib('templater')->template('im', 'journal', $teacher, 'load_forecast');
           print($templater_package->get_file('html'));
        }else
        {
            $templater_package = $this->dof->modlib('templater')->template('im', 'journal', $teacher, 'load_salfactors');
            print($templater_package->get_file('html'));
        }
        
    }  
    
}

?>