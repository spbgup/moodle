<?php
////////////////////////////////////////////////////////////////////////////
//                                                                        //
// NOTICE OF COPYRIGHT                                                    //
//                                                                        //
// Dean`s Office for Moodle                                               //
// Электронный деканат                                                    //
// <http://sourceforge.net/projects/freedeansoffice/>                     //
//                                                                        //
// Copyright (C) 2008-2999                                                //
// Ilia Smirnov (Илья Смирнов)                                            //
// Evgenij Tsygantsov (Евгений Цыганцов)                                  //
// Alex Djachenko (Алексей Дьяченко)  alex-pub@my-site.ru                 //
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

//загрузка библиотек верхнего уровня
require_once(dirname(realpath(__FILE__))."/../lib.php");
$csid = optional_param('csid', 0, PARAM_INT);
$DOF->modlib('nvg')->add_level($DOF->get_string('group_journal', 'journal'), $DOF->url_im('journal','/group_journal/index.php?csid='.$csid,$addvars));

/** Класс отрисовки школьного журнала
 */
class dof_im_journal_tablegrades extends dof_im_journal_rawdata
{
    /** Вывести страницу журнала - просмотр оценок, или редактирование
     * 
     * @return null 
     * @param int $editid - id учебного контрольной точки в тематическом планировании (plans)
     *                      которая будет отредактирована
     * @param int $eventid - id редактируемого учебного события (таблица schevents)
     */
    public function print_texttable($editid = null, $eventid = 0)
    {
        // получаем массив со структурой документов
        $docdata = $this->get_all_form($editid, $eventid);
        // обращаемся к шаблонизатору для вывода таблицы
        $templater_package = $this->dof->modlib('templater')->template('im', 'journal', $docdata, 'group_journal');
        print($templater_package->get_file('html'));
    }
    
    /** Определяем, что для журнала выбираются только события со статусами "plan" и "completed"
     * 
     * @return array
     */
    protected function get_eventstatuses()
    {
        return array('plan', 'completed', 'postponed');
    }

	/** Возвращает объект формы для вставки в templater
	 * @param int $editid - id редактируемого учебного плана
	 * @param int $eventid - id редактируемого учебного события
	 * @return object объект нужной структуры для построения шаблона
	 */
	private function get_all_form($editid, $eventid)
	{
		$result = new object;
		$result->monthdesc = $this->dof->get_string('month', 'journal');
        $result->datedesc  = $this->dof->get_string('date_day', 'journal');
        $result->npp       = $this->dof->get_string('npp', 'journal');
        $result->listtitle = $this->dof->get_string('students_list2', 'journal');
        /*
         * Начинаем со сборки исходных данных
         */
        // создаем объект для итоговых данных
        // собираем все запланированные активные контрольные точки учебного потока
        $plans = $this->get_checkpoints(false);
        /*foreach ( $plans as $plan )
        {
            if ( isset($plan->event->status) AND $plan->event->status == 'postponed' )
            {// если событие отменено, исключим его из списка
                unset($plans[$plan->absdate]);
            }
        }*/
        /*
         * формируем массив для templater'a
         */
        // создадим массивы для названий месяцев
        $result->monthtitle  = array();
        // и  дат
        $result->monthdate   = array();
        // заносим в объект информацию по ученикам
        $result->studentinfo = $this->get_lines_for_students($plans, $editid, $eventid, $info='info');
        // в отдельное поле записывается массив ФИО
        $result->student = $this->get_lines_for_students($plans, $editid, $eventid, $info='grades');  
        
        if ( $plans )
        {// В результирующем массиве формируем строку месяцев и дат
            $datesstring = $this->create_datesstring($plans);
            $result->upper_anchor = $datesstring->upper_anchor;
            $result->monthdate  = $datesstring->monthdate;
            $result->monthtitle = $datesstring->monthtitle;
        }
        if ( $editid )
		{// устанавливаем необходимый код формы
            $anchor = $this->get_anchor($plans, $editid, $eventid);
            if ( $this->dof->im('journal')->is_access('give_grade',$editid) OR 
                 $this->dof->im('journal')->is_access('give_grade/in_own_journal',$editid) )
            {
    			$result->formbegin = $this->get_begin_form($editid, $eventid, $anchor);
    			$result->formend = $this->get_end_form($eventid);
            }
        }
		return $result;
	}
	
	/** Возвращает редактируемую ячейку
	 * @param int $studentid - id студента
	 * @param int $cpassedid - id подписки
	 * @param string $oldgrade - предыдущая оценка, если есть
	 * @param int $eventid - id редактируемого события
	 * @param string $scale - шкала оценок
	 * @return string html-код формы
	 */
	private function get_cell_form($studentid, $cpassedid, $oldgrade=null, $gradeid=0, $eventid,  $scale=null)
	{
        $result  = '';
        // получим данные
        $cpassed = $this->dof->storage('cpassed')->get($cpassedid);
        
        // время начала
        $begindate = $cpassed->begindate;
        if ( empty($cpassed->begindate) )
        {// не указано время
            if ( ! $begindate = $this->dof->storage('cstreams')->get_field($cpassed->cstreamid,'begindate') )
            {// берем время потока
                if ( ! $begindate = $this->dof->storage('ages')->get_field($cpassed->ageid,'begindate') )
                {// время периода, если и его нет, то начало - начало дня
                    $begindate = time();
               }
            }
        }
        $time = dof_usergetdate($begindate);
        $begindate = mktime(0, 0, 0, $time['mon'],$time['mday'],$time['year']); 
    	//время конца
        $enddate = $cpassed->enddate;
        if ( empty($cpassed->enddate) )
        {// не указано время
            if ( ! $enddate = $this->dof->storage('cstreams')->get_field($cpassed->cstreamid,'enddate') )
            {// берем время потока
                if ( ! $enddatee = $this->dof->storage('ages')->get_field($cpassed->ageid,'enddate') )
                {// время периода, если и его нет, то конец - конец дня
                    $enddate = time(); 
                }
            }
        }
        $time = dof_usergetdate($enddate);
        // конец дня
        $enddate = mktime(23, 59, 59, $time['mon'],$time['mday'],$time['year']); 
        $disabled = '';
        if ( $schevent = $this->dof->storage('schevents')->get($eventid) )
        {// если есть событие
            if ( ($schevent->date < $begindate OR $schevent->date > $enddate) 
                    AND ! $this->dof->im('journal')->is_access('remove_not_studied') )
            {// запрет редактирования поля';
                $disabled = ' disabled';    
                $result .= '<input type="hidden" name="noaway['.$cpassedid.']" value="'.$studentid.'">';
                $result .= '<input type="hidden" name="editgrades['.$cpassedid.']" value="'.$studentid.'">';
                $result .= '<input type="hidden" name="away['.$cpassedid.']" value="'.$studentid.'">';
            }
        }
        // всю форму запихиваем в маленькую таблицу
        $result .= '<table callpadding="0" celspacing="0" border="0">'."\n";
        $result .= '<tr><td rowspan="2">'."\n";
        $result .= '<input type="hidden" name="gradeid['.$cpassedid.']" value="'.$gradeid.'">';
        $result .= '<select name="editgrades['.$cpassedid.']"'.$disabled.'>'."\n";
        // получаем все варианты оценок
        $variants = $this->get_grade_variants($scale);
        
        foreach ($variants as $variant)
        {// перебираем все варианты и делаем их элементами формы
            if ( $oldgrade == $variant->value )
            {
                $variant->selected = 'selected';
            }else
            {
                $variant->selected = '';
            }
            $result .= '<option value="'.$variant->value.'" '.$variant->selected.'>'.$variant->name.'</option>'."\n";
        }
        $result .= '</select>'."\n";
        $result .= '</td>'."\n";
        $result .= "\n";
        // событие в рамках нашего cspassee то смотрим посещаемость
        if ( ! empty($schevent) )
        {
            $presence = $this->dof->storage('schpresences')->get_present_status($studentid, $eventid);
        }else 
        {
            $presence = 'noaway';
        }    
        if ($eventid)
        {// устававливаем букву "н", "н/о" только для КТ, для которых есть события
            // Буква "н" для пояснения
            $result .= '<td align="center">'."\n";
            // галочка под буквой "н"
            if ($presence === '0')
            {
            	$check = 'checked';
            }else 
            {
                $check = '';
            }

            $result .= '<input type="checkbox" name="away['.$cpassedid.']" value="'.$studentid.'" '.$check.' '.$disabled.'>';
            $result .= '</td>'."\n";
            $result .= '<td align="center">'."\n";
            $result .= $this->dof->get_string('away_n_small', 'journal');
            $result .= '</td>'."\n";

            // Не обучался - н/о
            
            if ( $presence === false OR $presence == 'noaway' )
            {// добавляем чекбокс для н/o
                $result .= '<td align="center">'."\n";
                if (  $schevent->date < $begindate OR $schevent->date > $enddate OR $cpassed->status != 'active' ) 
                {
                    $result .= '<input type="checkbox" name="noaway['.$cpassedid.']" value="'.$studentid.'" '.$disabled.' checked>';
                }elseif ( $schevent->status == 'completed' )
                {
                    $result .= '<input type="checkbox" name="noaway['.$cpassedid.']" value="'.$studentid.'" checked>';                
                }else 
                {
                    $result .= '<input type="checkbox" name="noaway['.$cpassedid.']" value="'.$studentid.'"';  
                }

                $result .= '</td>'."\n";
                $result .= '<td align="center">'."\n";
                $result .= $this->dof->get_string('away_no_small', 'journal');
                $result .= '</td>'."\n"; 
            }elseif( $schevent->date < $begindate OR $schevent->date > $enddate )
            {// есть запись, но она выходит за рамки 
                if ( $this->dof->im('journal')->is_access('remove_not_studied') )
                {// завуч может поставить "н/о"
                    $result .= '<td align="center">'."\n";
                    $result .= '<input type="checkbox" name="noaway['.$cpassedid.']" value="'.$studentid.'"'; 
                    $result .= '</td>'."\n";
                    $result .= '<td align="center">'."\n";
                    $result .= $this->dof->get_string('away_no_small', 'journal');
                    $result .= '</td>'."\n";
                }
            }
            $result .= '</tr>'."\n"; 
        }
        $result .= '<input type="hidden" name="cpassedid['.$cpassedid.']" value="'.$studentid.'">';
        $result .= '</table>'."\n";
        return $result;
		
	}
    /** Получить текстовое содержимое ячейки оценки
     * 
     * @return string html-код оценки и отметки об отсутствии
     * @param object $student - объект содержащий данные об ученике
     * @param object $plan - контрольная точка с событием  из тем. планирования
     * @param object $gradedata - данные об оценке, либо null
     * @param integer $cpassedid
     */
    private function get_cell_string($studentid, $plan, $cpassedid, $gradedata=null)
    {
        $grades = '';
        $prdate = '';
        // узнаем данные о посещаемости: получаем id события, если оно есть
        if ( isset($plan->event) )
        {// событие есть: узнаем, был ли ученик на занятии
            $params = array();
            $params['personid'] = $studentid;
            $params['eventid'] = $plan->event->id;
            if( $presence = $this->dof->storage('schpresences')->get_record($params) AND ! empty($presence->orderid) )
            {
                $prdate = dof_userdate
                          ($this->dof->storage('orders')->get_field($presence->orderid, 'exdate'),'%d.%m.%Y');
            }
        }
        // выведем отметку
        if ( $gradedata )
        {// если оценка за эту дату есть - выводим ее
            //var_dump($gradedata); //die;
            $data = dof_userdate
                    ($this->dof->storage('orders')->get_field($gradedata->orderid, 'exdate'),'%d.%m.%Y');
            $grades = '<span title='.$data.'>'.$gradedata->grade.'</span>';
            if ( isset($plan->event) AND isset($presence->present) AND $presence->present === '0' )
            {// если ученик отсутствовал на занятии - то поставим "н"
                $grades .= '<span title='.$prdate.'>('.$this->dof->get_string('away_n_small', 'journal').')</span>';
                
            }else
            {// если оценки нет - то выводим символ пробела, чтобы ячейка html-таблицы отобразилась
                $grades .= '<span title='.$prdate.'>&nbsp;&nbsp;</span>';
            }
            
        }else
        {
            if ( isset($plan->event) AND isset($presence->present) AND $presence->present === '0' )
            {// если ученик отсутствовал на занятии - то поставим "Н"
                $grades = '<span title='.$prdate.'>'.$this->dof->get_string('away_n', 'journal').'</span>';
            }else
            {// если оценки нет - то выводим символ пробела, чтобы ячейка html-таблицы отобразилась
                $grades = '<span title='.$prdate.'>&nbsp;&nbsp;&nbsp;</span>';
            }
        }
        // возвращаем получившуюся строку
        return $grades;
    }
    
	/** Возвращает данные в одной клетке 
	 * @param int $studentid - id студента 
	 * @param object $plan - контрольная точка с событием  из тем. планирования
	 * @param object $gradedata - данные об оценке, либо null
	 * @param int $cpassedid - id  подписки
	 * @param int $editid - id редактируемого плана
	 * @param int $eventid - id редактируемого события
	 * @param string $scale - шкала оценок
	 * @return string
	 */
	private function get_one_cell($studentid, $plan, $gradedata, $cpassedid, $editid, $eventid , $scale = null)
	{
       $grades = '';
       // если id КТ из ем. планирования и редактируемой КТ совпадают
       // ячейка редактируется 
       
	   if ( $plan->plan->id == $editid AND ($this->dof->im('journal')->is_access('give_grade',$editid) 
            OR $this->dof->im('journal')->is_access('give_grade/in_own_journal',$editid)) )
       {
           if ( $gradedata )
            {// есть оценка
                $grades = $this->get_cell_form($studentid, $cpassedid, $gradedata->grade, $gradedata->id, $eventid, $scale);
            }else
            {// нет оценки
                $grades = $this->get_cell_form($studentid, $cpassedid, 0, 0, $eventid, $scale);
            }
        }else
        {// это обычная ячейка. Просто покажем оценку
            $grades = $this->get_cell_string($studentid, $plan, $cpassedid, $gradedata);
        }
        // возвращаем код формы
        return $grades;
	}	
	
    /** Возвращает цвет одной клетки 
	 * @param int $studentid - id студента 
	 * @param object $plan - контрольная точка с событием  из тем. планирования
	 * @param object $gradedata - данные об оценке, либо null
	 * @param int $cpassedid - id  подписки
	 * @param int $editid - id редактируемого плана
	 * @param int $eventid - id редактируемого события
	 * @param string $scale - шкала оценок
	 * @return string
	 */
	private function get_color_cell($studentid, $plan, $cpassedid, $editid, $eventid)
	{
        $color = '';
        // если id КТ из ем. планирования и редактируемой КТ совпадают
        // ячейка редактируется 
	    if  ( $plan->plan->id != $editid AND isset($plan->event) AND 
	        ($this->dof->storage('schpresences')->get_present_status($studentid, $plan->event->id) === false ) OR 
            ($this->dof->storage('cpassed')->get_field($cpassedid,'begindate') > time() 
            AND $this->dof->storage('cpassed')->get_field($cpassedid,'enddate') < time())
              )
	    {
            $color = 'bgcolor="#AAAAAA"';
        }
        // возвращаем код формы
        return $color;
	}	
	
	/** возвращает начало формы
	 * @param int $editid - id редактируемого плана
	 * @param int $eventid - id редактируемого события
	 * @return string html-код формы
	 */
	private function get_begin_form($editid, $eventid, $anchor)
	{
		global $USER;
        //запомним идентификатор сессии
        $sesskey = '';
        if ( isset($USER->sesskey) AND $USER->sesskey )
        {//запомним идентификатор сессии
           	$sesskey = $USER->sesskey;
        }
        $depid = optional_param('departmentid', 0, PARAM_INT);
        $addvars = array();
        $addvars['departmentid'] = $depid;
        // выведем начало формы
        $result = '<form name="gradeeditform" method="post" action="'.
        $this->dof->url_im('journal', '/group_journal/process_grades.php', array_merge(array('csid' => $this->csid),$addvars)).'">';
        //$this->dof->url_im('journal', '/group_journal/process_grades.php', array('csid' => $this->csid)).'">';
        $result .= '<!-- Форма объявляется в lib.php -->';
        $result .= '<input type="hidden" name="planid" value="'.$editid.'"/>';
        // добавляем id учителя, который проставил эту оценку
        $result .= '<input type="hidden" name="teacherid" value="'.
        $this->dof->storage('cstreams')->get_field($this->csid, 'teacherid').'"/>';
        //добавим идентификатор сессии
        $result .= '<input type="hidden" name="sesskey" value="'.$sesskey.'">';
        // идентификатор редактируемого события
        $result .= '<input type="hidden" name="eventid" value="'.$eventid.'">';
        // текущая
        $result .= '<input type="hidden" name="csid" value="'.$this->csid.'">';
        //
        $result .= '<input type="hidden" name="departmentid" value="'.$addvars['departmentid'].'">';
        $result .= '<input type="hidden" name="anchor" value="'.$anchor.'">';
        return $result;
	}
	
	/** Возвращает конец формы
	 * $param int $eventid - 
	 * @return string html-код формы
	 */
	private function get_end_form($eventid)
	{		

        // Выводим конец формы:
        // и кнопки
        $result = '';
        $result .= '<br /><b>'.$this->dof->get_string('jornal_edit_warning', 'journal').'</b><br/>';
        // добавляем чекбокс для отмены урока
        $status = $this->dof->storage('schevents')->get_field($eventid,'status');
        if ( $this->dof->im('journal')->is_access('can_complete_lesson',$eventid) OR
             $this->dof->im('journal')->is_access('can_complete_lesson/own',$eventid) )
        {   
            $result .= '<b>'.$this->dof->get_string('jornal_edit_warningtwo', 'journal').'</b><br/>';
            if ( $this->dof->storage('config')->get_config_value('time_limit', 
                'storage', 'schevents',optional_param('departmentid', 0, PARAM_INT)) )
            {// стоит настройка, покажем предупреждение
                $result .= '<br/><b>'.$this->dof->get_string('jornal_edit_warning_limit_time', 'journal').'</b><br/>';
            }
            $result .= '<br/> <b><p >'.$this->dof->get_string('lesson_complete_title', 'journal').'</b>';
            if ( ! $this->dof->workflow('schevents')->limit_time(
                         $this->dof->storage('schevents')->get_field($eventid,'date')) )
            {// если есть ограничения - отметить нельзя
                $result .= '<input type="checkbox" name="nobox" disabled="true"></p>';
            }else 
            {
                $result .= '<input type="checkbox" name="box"></p>';
            }
            
        } 
        $result .= '<br/> <input type="submit" name="save_and_continue" value="'.
        $this->dof->get_string('save_and_continue', 'journal').'"/>';
        $result .= '<input type="submit" name="save" value="'.
        $this->dof->get_string('to_save', 'journal').'"/>';
        $result .= '<input type="submit" name="restore" value="'.
        $this->dof->get_string('restore', 'journal').'"/>';
        $result .= '</form>';
        
        return $result;
	}
	
	/** Возвращает данные для одного студента
	 * @param int $i - порядковый номер
	 * @param object $student - студент
	 * @param array $cpasseds - его подписки
	 * @param array $plans - контрольные точки
	 * @param int $editid - id редактируемого плана
	 * @param int $eventid - id редактируемого события
	 * @param string  $info - показывает иформацию, что нужно вывести, если пусто,
	 * 							то выводить всю информацию
	 * @return object информация о студенте
	 */
	private function get_line_for_student($i,$cpassed,$plans, $editid, $eventid, $info='')
	{
	    global $CFG;
	    $depid = optional_param('departmentid', 0, PARAM_INT);
	    $cstreamid = optional_param('csid', 0, PARAM_INT);
        $addvars = array();
        $addvars['departmentid'] = $depid;
		$curstudent = new object();
        // устанавливаем порядковый номер
        $curstudent->studentnumber = $i;
        $link = '';
        $name = $this->dof->storage('persons')->get_fullname($cpassed->studentid);
        // перечеркнем имя
        if ( $cpassed->status == 'failed' )
        {
            $name = "<span style='text-decoration:line-through;color:gray;'> {$name} </span>";
        }
        // серый цвет
	    if ( $cpassed->status == 'completed' )
        {
            $name = "<span style='color:gray;'> {$name} </span>";
        }
        if ( $this->dof->storage('schtemplates')->is_access('view') )
        {// можно просматривать шаблон - добавим ссылку на просмотр шаблона на неделю
            $ageid = $this->dof->storage('cstreams')->get_field($cstreamid,'ageid');
            $link = '<a href="'.$this->dof->url_im('schedule', '/view_week.php?studentid='.$cpassed->studentid.'&ageid='.$ageid,$addvars).
                    '"><img src="'.$this->dof->url_im('journal', '/icons/show_schedule_week.png').'"
                     alt=  "'.$this->dof->get_string('view_week_template_on_student', 'journal').'" 
                     title="'.$this->dof->get_string('view_week_template_on_student', 'journal').'" /></a>';
        }
        $mdlcourse = $this->dof->storage('programmitems')->get_field($cpassed->programmitemid,'mdlcourse');
        if ( isset($mdlcourse) AND $this->dof->modlib('ama')->course(false)->is_course($mdlcourse) )
        {
            $mdluser = $this->dof->storage('persons')->get_field($cpassed->studentid,'mdluser');
            $link .= $this->dof->modlib('ig')->icon('moodle',$CFG->wwwroot."/course/user.php?id=".$mdlcourse."&user=".$mdluser.
                                                             "&mode=outline");
        }
        // склеиваем в один элемент фамилию и имя
        $curstudent->fio = '<a href="'.$this->dof->url_im('journal', '/person.php?personid='.$cpassed->studentid,$addvars).'">'.
                             $name.
                             '</a><a href="'.$this->dof->url_im('journal', '/show_events/show_events.php?personid='.$cpassed->studentid,$addvars).
                             '&date_to='.time().'&date_from='.time().'">
                             <img src="'.$this->dof->url_im('journal', '/icons/events_student.png').'"
                             alt=  "'.$this->dof->get_string('view_events_student', 'journal').'" 
                             title="'.$this->dof->get_string('view_events_student', 'journal').'" /></a>'.$link;;

        $curstudent->cpassedid = $cpassed->id;
        // вывод информации
        if ( $info == 'info' )
        {
            return $curstudent;
        }
        // объявляем массив для будущих оценок студента
        $curstudent->studentgrades = array(); 
        // собираем ключи массива - id учебных событий
        if ( is_array($plans) )
        {
            foreach ($plans as $plan)
            {// для всех дат проставляем оценки
                // создаем объект оценки для обработки шаблоном
                $grade = new object;
                // получаем оценку за указанную дату
                // нулевая шкала - возьмем из предмета
                if ( IS_NULL($plan->plan->scale) )
                {
                    $csid = $plan->plan->linkid;
                    $pitemid = $this->dof->storage('cstreams')->get_field($csid, 'programmitemid'); 
                    $scale = $this->dof->storage('programmitems')->get_field($pitemid, 'scale');
                    // и в программе не указана - возбмем из настроек по умолчанию
                    if( empty($scale) )
                    {
                        $scale = $this->dof->storage('config')->get_config_value('scale', 'storage', 'plans', $depid);
                        
                    }
                }else 
                {
                    $scale = $plan->plan->scale;
                }
               
                // @todo в будущем передалать для вывода нескольких оценок за одну дату
                $gradedata = $this->dof->storage('cpgrades')->
                        get_grade_student_cpassed($cpassed->id, $plan->plan->id);
                // получем оценку студента
                $grade->grades = $this->get_one_cell($cpassed->studentid, $plan, 
                                $gradedata, $curstudent->cpassedid, $editid, $eventid, $scale);
                // получем оценку студента
                $grade->color = $this->get_color_cell($cpassed->studentid, $plan, $curstudent->cpassedid, $editid, $eventid);
                // добавляем оценку в массив оценок ученика
                $curstudent->studentgrades[] = $grade;
            }
        }
        if ( $info == 'grades' )
        {
            unset($curstudent->fio);
            unset($curstudent->studentnumber);
        }
        // вернем информацию о студенте
        return $curstudent;
	}
	
	/** Возвращает данные для всех студентов
	 * @param array $plans - контрольные точки 
	 * @param int $editid - id редактируемого плана
	 * @param int $eventid - id редактируемого события
	 * @param string  $info - показывает иформацию, что нужно вывести, если пусто,
	 * 							то выводить всю информацию
	 * @return array информация о всех студентах данного потока
	 */
	private function get_lines_for_students($plans, $editid, $eventid, $info='')
	{
		
        // получим все подписки учебного потока, чтобы потом установить связи с оценками
        $cpasseds = $this->get_cpassed();
        
        $result = array();
        if ( $cpasseds )
        {// порядковый номер ученика
            $i = 0;
            foreach ($cpasseds as $cpassed)
            {
        	    // задаем порядковый номер
                ++$i;
                // добавляем информацию о студенте в массив
                $result[$cpassed->id] = 
                    $this->get_line_for_student($i, $cpassed, $plans, $editid, $eventid, $info);
            }
        }      
        return $result;
    }
    /** Создает строку дат для вывода журнала 
     * 
     * @return object объект, содержащий массив с данными
     * @param object $plans - массив контрольных  точек учебного потока или false в случае неудачи
     */
    private function create_datesstring($plans)
    {
        $result = new object;
        // создаем счетчик месяцев
        $monthcount = 0;
        $oldmname   = '';
        if ( ! $plans )
        {// не переданно ни одной темы планирования - построить строку дат не удастся
            return false;
        }
        // получаем строку дат
        $dates = $this->generate_all_dates($plans);
        foreach($dates as $date)
        {// перебираем все события и собираем массивы дат и названий месяцев
            // создаем якорь
            $anchor = new object;
            $anchor->anchornum      = $date->date;
            $result->upper_anchor[] = $anchor;
            // вычисляем название текущего месяца
            $mname = dof_im_journal_format_date($date->date, 'm');
            
            // если про просматриваемая дата не находится в том же месяце, что и предыдущая,
            // то дополняем список месяцев
            if ( $oldmname != $mname )
            {
                $monthcount++;
                // создаем объект месяца
                $result->monthtitle[$monthcount] = new object();
                // заполняем название месяца
                $result->monthtitle[$monthcount]->mtitle = $mname;
                $oldmname = $mname;
            }
            // прибавляем счетчик дат в месяце
            $result->monthtitle[$monthcount]->mcolspan++;
            // записываем новую дату в журнал
            $result->monthdate[] = $date;
        }
        return $result;
    }
   /** Вызывается из generate_all_dates Создает один объект даты для журнала.
     * 
     * @return object дата в нужном для templater'a формате
     * @param object $plan
     * @param object $event[optional]
     */
    private function generate_single_date($plan, $date, $event = null)
    {
        $depid = optional_param('departmentid', 0, PARAM_INT);
        $addvars = array();
        $addvars['departmentid'] = $depid;
        // устанавливаем путь к теме в планировании
        $dayurl = '#'.$date;
        if ( $event AND is_object($event) )
        {
            $eventid = $event->id;
        }else
        {
            $eventid = 0;
        }
        $editurl = $this->dof->url_im('journal', '/group_journal/index.php', 
                   array_merge(array('csid' => $this->csid, 'planid' => $plan->id, 'eventid' => $eventid),$addvars))
                              .'#jm'.$date;// добавляем ссылку на якорь, чтобы страница проматывалась 
                              // горизонтально до нужного места
        // переходим к составлению ссылки на редактирование
        $dateobject = new object();
        if ( ! $event )
        {// если это четвертная или годовая оценка - выведем только ее название
            $dateobject->datecode = $plan->name;
            if ( $this->dof->im('journal')->is_access('give_grade',$plan->id) OR 
                 $this->dof->im('journal')->is_access('give_grade/in_own_journal',$plan->id) )
            {
                $dateobject->datecode .= dof_im_journal_date_edit(null, 'd', $dayurl, $editurl);
            }else
            {
                 $dateobject->datecode .= dof_im_journal_date_edit(null, 'd', $dayurl);
            }
        }else
        {// если это обычная дата - выведем ее
            if ( $this->dof->im('journal')->is_access('give_grade',$plan->id) OR 
                 $this->dof->im('journal')->is_access('give_grade/in_own_journal',$plan->id) )
            {// если статус неактивный выведем просто даты
                $dateobject->datecode = dof_im_journal_date_edit($date, 'd', $dayurl, $editurl);
            }else
            {// если активный, то выведем значек редактирования
                $dateobject->datecode = dof_im_journal_date_edit($date, 'd', $dayurl);
            }
            // сделаем дату жирной на текущее время
            $evdate = dof_usergetdate($date);
            $tmdate = dof_usergetdate(time());
            if ( $evdate['mon'] == $tmdate['mon'] 
                     AND $evdate['mday'] == $tmdate['mday'] 
                         AND $evdate['year'] == $tmdate['year'] )
            {
                $dateobject->datecode = '<b>'.$dateobject->datecode.'</b>';
                if ( ($date < time()) AND (($date+$event->duration) > time()) )
                {
                    $dateobject->datecode = '<div id="menu">'.$dateobject->datecode.'</div>';
                }
            }
        }
        
        // устанавливаем якорь как метку времени независимо от 
        
        $dateobject->date = $date;
        return $dateobject;
    }
    
    /** Вызывается из generate_datesstring. Получить строку со всеми датами для журнала
     * 
     * @return array - даты для вывода в журнал
     * @param array $plans - массив контрольных точек учебного потока
     */
    private function generate_all_dates($plans)
    {
        $result = array();
        // собираем даты
        foreach ($plans as $plan)
        {// получим событие, которое относится к данной теме тематического планирования
            if ( isset($plan->event) AND is_object($plan->event) )
            {// если событие есть - то покажем дату
                $result[] = $this->generate_single_date($plan->plan, $plan->date, $plan->event);
            }else
            {// если события нет - только название
                $result[] = $this->generate_single_date($plan->plan, $plan->date);
            }
        }
        return $result;
    }    
    /** Возвращает масив оценок нужной структуры, для использования в форме.
     * @return array массив объектов вида
     *         value->'значение оценки'
     *         name->'отображаемое в форме имя оценки'
     *         selected->'selected', если вы хотите видеть этот пункт выбранным по умолчанию или null,
     *         в противном случае  
     * @param string $scale - тип используемой шкалы
     */
    public function get_grade_variants($scale=null)
    {
        
        $fromplan = $this->dof->storage('plans')->get_grades_scale_str($scale);
        $variants = array();
        foreach ( $fromplan as $gradevariant )
        {
            $variant        = new object();
            $variant->name  = $gradevariant;
            $variant->value = $gradevariant;
            $variants[]     = $variant;
        }
        // по умолчанию к любой шкале добавляем "нулевую оценку" - для того, чтобы ее можно было удалить
        $variant    = new object();
        $variant->name     = ' ';
        $variant->value    = '';
        $variants[] = $variant;
        // возвращаем шкалу оценок в указанном виде
        return $variants;
    }
    
    /** Получить id html-якоря для редактированияячейки
     * 
     * @return 
     * @param object $plans
     */
    private function get_anchor($plans, $planid, $eventid)
    {
        if ( ! is_array($plans) )
        {// неверный формат исходных данных
            return 0;
        }
        //print_object($plans);
        foreach ( $plans as $anchor=>$plan )
        {// ищем нужный id в массиве
            if ( isset($plan->event) )
            {//если есть событие
                if ( $plan->plan->id == $planid AND $plan->event->id == $eventid )
                {//проверяем и КТ и событие
                    return $anchor;
                }
            }else
            {//события нет
                if ( $plan->plan->id == $planid )
                {//проверяем только КТ
                    return $anchor;
                }
            }
        }
        // если ничего не нашли
        return 0;
    }
}
/**
 * Возвращает отформатированную дату
 * @param int $date - метка времени которую надо вывести
 * @param $format - тип форматирования даты:
 * dmy: выводит дд.мм.гг
 *  dm: выводит дд.мм
 *  my: выводит ммм гг, ммм - название месяца из трех букв
 *   m: выводит полное название месяца
 *   d: выводит дд
 * @param string $url - путь, по которому надо перейти, если дату надо сделать ссылкой
 * @return string
 */
function dof_im_journal_format_date($date, $format = 'dmy', $url = NULL)
{
    global $DOF;
    //получаем путь с нужной функцией
    $amapath = $DOF->plugin_path('modlib','ama','/amalib/utils.php');
    //подключаем путь с нужной функцией
    require_once($amapath);
    if ( ama_utils_is_intstring($date) )
    {//получена дата - форматируем
        switch ($format)
        {
            case 'dmy': $rez = dof_userdate($date,'%d.%m.%y'); break;
            case 'dm': $rez = dof_userdate($date,'%d.%m'); break;
            case 'my': $rez = dof_userdate($date,'%b %y'); break;
            case 'm': $rez = dof_userdate($date,'%B'); break;
            case 'd': $rez = dof_userdate($date,'%d'); break;
            default: $rez = $date;
        }
        // strftime в win32 возвращает результат в cp1251 - исправим это
        if ( stristr(PHP_OS, 'win') AND ! stristr(PHP_OS, 'darwin') )
        {//это виндовый сервер
            //if ( $localewincharset = get_string('localewincharset') )
            //{//изменим кодировку символов из виндовой в utf-8
            //    $textlib = textlib_get_instance();
            //    $rez = $textlib->convert($rez, $localewincharset, 'utf-8');
            //}
        }
    }else
    {//получена строка - вставим ее
        $rez = trim($date);
    }
    if ( ! is_null($url) AND is_string($url) )
    {//делаем дату ссылкой
        $rez = "<a href=\"{$url}\">".$rez.'</a>';
    }
    return $rez;
}
/**
 * Возвращает отформатироанную дату и 
 * значок редактирования как ссылку
 * @param int $date метка времени
 * @param string $format - см. описание к dof_im_journal_format_date 
 * @param string $durl - путь ссылки для даты,
 * если не указана - дата выводится как просто строка
 * @param string $eurl - путь ссылки для значка, 
 * если не указана значок не показывается
 * @param bool $imgsubdate - вывести значок под датой или рядом 
 * по умолчанию выводит значок под датой
 * @return string 
 */
function dof_im_journal_date_edit($date, $format = 'dmy', $durl = null, $eurl = null, $imgsubdate = true)
{
    global $DOF;
    //получаем форматированную дату
    $rez = dof_im_journal_format_date($date, $format, $durl);
    //добавляем значок форматирования
    if ( ! is_null($eurl) AND is_string($eurl) )
    {//передана строка - делаем ссылку
        //рисуем картинку
        $imgedit = '<img src="'.$DOF->url_im('journal', '/icons/edit.png').'">';
        //делаем ее ссылкой        
        $imglink = "<a href=\"{$eurl}\">".$imgedit.'</a>';
    }else
    {//ссылка не передана - не показываем значок
        $imglink = '';
    }
    if ( is_bool($imgsubdate) AND $imgsubdate)
    {
        return $rez.'<br />'.$imglink;
    }
    return $rez.'&nbsp;'.$imglink;
}

/**
 * Показывает можно редактировать дату 
 * элемента темплана или нельзя.
 * @param int $planid - id элемента темплана
 * @param int $csid - id потока
 * @return bool true - можно изменять дату события, 
 * false - нельзя изменять дату события
 */
function dof_im_journal_is_editdate($planid, $csid)
{
    global $DOF;
    if ( ! $DOF->im('journal')->get_cfg('teacher_can_change_lessondate') )
    {// если прав у учителя нет - значит редактировать дату нельзя
        return false;
    }
    require_once($DOF->plugin_path('modlib','ama','/amalib/utils.php'));
    if ( ! ama_utils_is_intstring($planid) OR 
         ! ama_utils_is_intstring($csid) )
    {//передано непонятно что - нельзя дату менять
        return false;
    }
    if ( ! $planid )
    {//для нового элемента темплана 
        //дату можно редактировать
        return true;
    }
    if ( ! $DOF->storage('schevents')->get_records(array(
                'cstreamid'=>$csid, 'planid'=>$planid,
                'status'=>array('plan', 'completed','postponed','replaced'))) )
    {//для данного элемента темплана события нет';
        //дату редактировать нельзя
        return false;
    }
    return true;
}
/**
 * Класс для отрисовки 
 * таблицы тематического планирования
 * в классном журнале
 */
class dof_im_journal_templans_table extends dof_im_journal_rawdata
{

    public function __construct($dof, $csid)
    {
        parent::__construct($dof, $csid);
    }
    /** Определяем, что для страницы списка уроков в журнале нам нужны только уроки 
     * со статусами: 'plan', 'completed', 'postponed', 'replaced'
     * 
     * @return array
     */
    protected function get_eventstatuses()
    {
        return array('plan', 'completed', 'postponed', 'replaced');
    }
    /**
     * Возвращает массив объектов c необходимыми свойствами
     * для вставки в таблицу тем уроков
     * @return mixed array или bool false
     */
    private function get_topics()
    {
        if ( ! $checkpoints = $this->get_checkpoints() )
        {//не получили события потока
            return false;
        }
        //print_object($checkpoints);
        //формируем объект с нужными полями
        $topics = array();
        foreach ( $checkpoints as $point )
        {
            $topic = new object;
            if ( ! empty($point->plan) AND ($point->plan->status != 'canceled') )
            {// КТ указана - выведем информацию о ней
                $topic->planid = $point->plan->id;
                $topic->name = $point->plan->name;
                $topic->homework = $point->plan->homework;
                $topic->homeworkhours = $point->plan->homeworkhours;
                $topic->replace = ' ';
                $topic->note = ' ';
            }else
            {// КТ нет
                $topic->planid = 0;
                $topic->name = '';
                $topic->homework = '';
                $topic->homeworkhours = '';
                $topic->replace = ' ';
                $topic->note = ' ';
            }
            if ( ! empty($point->event) )
            {//событие есть - покажем его дату и id
                $topic->eventid = $point->event->id;
                $topic->date    = $point->event->date;
            }else
            {//события нет - покажем пустую строку';
                $topic->eventid = 0;
                $topic->date = ' ';
            }
            $topics[] = $topic;
        }
        return $topics;
    }
    /**
     * Возвращает "пустой" объект отчета об уроке
     * @return object
     */
    private function get_empty_topic()
    {
        $topic = new object;
        $topic->planid = 0;
        $topic->date = time();
        $topic->name = ' ';
        $topic->homework = ' ';
        $topic->replace = ' ';
        $topic->note = ' ';
        return $topic;
    }
    /**
     * Возвращает массив строки заголовка таблицы
     * @return array
     */
    protected function table_head()
    {
        return array($this->dof->get_string('N', 'journal'),
                     $this->dof->get_string('date', 'journal'),
                     $this->dof->get_string('what_passed_on_lesson', 'journal'),
                     // @todo нету ни замены, ни заметок
                     $this->dof->get_string('homework', 'journal'),
                     $this->dof->get_string('hwhours', 'journal'),
                     //$this->dof->get_string('replacement', 'journal'),
                     //$this->dof->get_string('notes', 'journal')
                     $this->dof->get_string('status', 'journal')
                    );
    }
    /**
     * Возвращает массив строк данных
     * отформатированных для вывода 
     * с помощью moodle-функции print_table()
     * @return array
     */
    protected function table_data()
    {
        $rez = array();
        if ( ! $topics = $this->get_checkpoints() )
        {//тематического планирования нет
            $topics = array();
        }
        $depid = optional_param('departmentid', 0, PARAM_INT);
        $addvars = array();
        $addvars['departmentid'] = $depid;
        $previosdates = array();
        $i=1;
        foreach ( $topics as $one )
        {//формируем массив строк таблицы
            $linkdate = $one->date;
            while ( in_array($linkdate, $previosdates) )
            {// устанавливаем соответствие между датой здесь и на странице оценок
                $linkdate = $linkdate + 1;
            }
            // установим начальные значения
            $eurl = null;
            $statusname = '';   
            // если нет КТ, то одно право, иначе другое    
            if ( ! empty($one->plan) )
            {
                $rezylt = ($this->dof->storage('plans')->is_access('edit', $one->plan->id) OR 
                           $this->dof->storage('plans')->is_access('edit/in_own_journal', $one->plan->id));
            }else 
            {
                $rezylt = ($this->dof->im('journal')->is_access('give_theme_event', $one->event->id) OR 
                           $this->dof->im('journal')->is_access('give_theme_event/own_event', $one->event->id)) ; 
            }
            if ( ! empty($one->event) )
            {//событие есть - покажем его дату и id
                if ( $rezylt )
                {// нет прав на редактирование - не показываем иконку редактирования
                    $eurl = $this->dof->url_im('journal',
                           '/group_journal/topic.php?planid='.$one->event->planid.'&csid='.$this->csid.'&eventid='.$one->event->id,$addvars);                    
                }
                // дата смены статуса
                $datechangestatus = '';
                // найдем записи о смене статуса
                $status = new object();
                $status->plugintype = 'storage';
                $status->plugincode = 'schevents';
                $status->objectid = $one->event->id;
                $status->prevstatus = 'plan';
                $sqlstatus = $this->dof->storage('statushistory')->get_select_listing($status);
                if ( $statuses = $this->dof->storage('statushistory')->get_records_select($sqlstatus,null,'statusdate DESC') )
                {
                    $statusdate = current($statuses)->statusdate;
                    $datechangestatus = dof_userdate($statusdate,'%d.%m.%Y');
                }
                
                $status = $this->dof->storage('schevents')->get_field($one->event->id,'status');
                $statusname = $this->dof->workflow('schevents')->get_name($status);
                if ( $status ==  'replaced' )
                {// если урок заменен - то покажем, какой именно учитель заменил
                    $replaceid = $this->dof->storage('schevents')->get_field($one->event->id,'replaceid');
                    if ( $newschevent = $this->dof->storage('schevents')->get($replaceid) )
                    {
                        $statusname = '<span title='.$datechangestatus.'>'.$statusname.
                                      '<br>['.trim($this->dof->storage('persons')->get_fullname($newschevent->teacherid)).']</span>';
                    }
                }else
                {// в остальных случаях
                    // покажем кто провел, или должен был провести занятие
                    $teacherid = $this->dof->storage('schevents')->get_field($one->event->id,'teacherid');
                    $statusname = '<span title='.$datechangestatus.'>'.$statusname.
                                  '<br>['.trim($this->dof->storage('persons')->get_fullname($teacherid)).']</span>';
                }
            }elseif ( $this->dof->storage('plans')->get($one->plan->id)->linktype == 'cstreams' )
            {// события нет, но есть КТ в с linktype=cstreams
                if ( $this->dof->storage('plans')->is_access('edit', $one->plan->id) OR 
                     $this->dof->storage('plans')->is_access('edit/in_own_journal', $one->plan->id) )
                {// нет прав на редактирование - не показываем иконку редактирования
                   $eurl = $this->dof->url_im('journal',
                        '/group_journal/topic.php?planid='.$one->plan->id.'&csid='.$this->csid.'&eventid=0',$addvars);
                }
                $statusname = '';
            }
            
            //вставляем якорь и форматируем дату в нужный вид
            $one->date = '<a name = '.$linkdate.'></a>'.
                dof_im_journal_date_edit($one->date,'dmy',NULL,$eurl);
            //добавляем в результирующий массив
            if ( empty($one->plan) AND ! empty($one->event) )
            {
                $one->planid = $one->event->planid;
                $one->plan = $this->dof->storage('plans')->get($one->planid);
            }
            if ( ! empty($one->plan) AND $one->plan->status != 'canceled' )
            {// КТ указана - выведем информацию о ней
                if ( ! empty($one->plan->homeworkhours) )
                {// есть время  - покажем его
                    $homeworkhours = ($one->plan->homeworkhours/60).' ' .$this->dof->modlib('ig')->igs('min').'. ';
                }else 
                {// нет времени - значит нет
                    $homeworkhours = '';
                }
                // спрячем задание, которое больше 100 символов
                $lengstr = mb_strlen($one->plan->homework,'utf-8'); 
                if ( $lengstr > 100 )
                {
                    // видимая часть
                    $text1 = mb_substr($one->plan->homework,0,100,'utf-8');
                    // скрытая часть
                    $text2 = '<span class="red '.$one->plan->id.'_Btn"><a href="" onClick="return dof_modlib_widgets_js_hide_show(\''.$one->plan->id.'_homework\',\''.$one->plan->id.'_Btn\');">...</a></span>';
                    // ссылка для нажатия
                    $text3 = '<span id="hideCont" class="'.$one->plan->id.'_homework">'.mb_substr($one->plan->homework,100,$lengstr-100,'utf-8').'</span>';
                    $one->plan->homework = $text1.$text3.$text2;
                }
                $rez[] = $this->get_table_row($i,$one->date,$one->plan->name, $one->plan->homework,$homeworkhours,$statusname); 
            }else
            {// КТ нет
                $rez[] = $this->get_table_row($i, $one->date, '', '','',$statusname); 
            }
            $i++;
            $previosdates[] = $linkdate;
        }//print_object($previosdates);
        return $rez;
    }
    /** Создать массив ячеек в строке таблицы, используя переданные данные
     * Эта функция создана для того чтобы было проще переопределить класс
     * отрисовки таблицы, и добавлять или удалять туда нужны е столбцы
     * 
     * @return array
     * @param string $strnum - номер строки
     * @param string $date - дата события
     * @param string $name - что пройдено на уроке
     * @param string $homework - домашнее задание
     * @param string $statusname - название статуса русскими буквами
     */
    protected function get_table_row($strnum, $date, $name, $homework,$homeworkhours, $statusname)
    {
        return array($strnum, $date, $name, $homework,$homeworkhours, $statusname);
    }
    /**
     * Возвращает массив выравнивания полей таблицы
     * @return array
     */
    protected function table_align()
    {
        return array('center','center','center','center','center');
    }
    /**
     * Возвращает таблицу как строку html-кода
     * @return string
     */
    public function print_table()
    {
        $table = new object;
        $table->tablealign = 'center';
        $table->head  = $this->table_head();
        //$table->wrap = array('wrap', 'wrap', 'wrap', 'wrap', 'wrap', 'wrap');
        $table->width = '50%';
        $table->data  = $this->table_data();
        $table->align = $this->table_align();
        return $this->dof->modlib('widgets')->print_table($table, true);
    }
}

/** Класс для создания таблицы планирования на странице создания/редактирования темы
 * 
 */
class dof_im_journal_topic_page_table extends dof_im_journal_templans_table
{
    protected function table_head()
    {
        return array(//$this->dof->get_string('N', 'journal'),
                     $this->dof->get_string('date', 'journal'),
                     $this->dof->get_string('what_passed_on_lesson', 'journal'),
                     $this->dof->get_string('homework', 'journal'),
                     //$this->dof->get_string('replacement', 'journal'),
                     //$this->dof->get_string('notes', 'journal')
                     //$this->dof->get_string('hwhours', 'journal')
                    );
    }
    /** Определяем, что для страницы списка уроков в журнале нам нужны только уроки 
     * со статусами: 'plan', 'completed', 'postponed', 'replaced'
     * 
     * @return array
     */
    protected function get_eventstatuses()
    {
        return array('plan', 'completed', 'postponed', 'replaced');
    }
    
    /** Создать массив ячеек в строке таблицы, используя переданные данные
     * Эта функция создана для того чтобы было проще переопределить класс
     * отрисовки таблицы, и добавлять или удалять туда нужные столбцы
     * 
     * @return array
     * @param string $strnum - номер строки
     * @param string $date - дата события
     * @param string $name - что пройдено на уроке
     * @param string $homework - домашнее задание
     * @param string $statusname - название статуса русскими буквами
     */
    protected function get_table_row($strnum, $date, $name, $homework, $homeworkhours=null, $statusname=null)
    {
        return array(strip_tags($date), $name, $homework);
    }
    
}

/**
 * Класс для создания или редактирования
 * одной темы и события на странице планирования уроков
 * @todo Удалить неиспользуемые методы
 */
class dof_im_journal_edittopic extends dof_im_journal_rawdata
{
    private $planid;
    private $eventid;
    public $rez;
    
    public function __construct(dof_control $dof, $planid, $csid, $eventid)
    {
        $depid = optional_param('departmentid', 0, PARAM_INT);
        $addvars = array();
        $addvars['departmentid'] = $depid;
        $this->planid = $planid;
        $this->eventid = $eventid;
        $path = $dof->url_im('journal', '/group_journal/index.php?csid='.$csid.'&planid='.$planid.'&eventid='.$eventid,$addvars);
        if ( $eventid )
        {
            $path = $dof->url_im('journal', '/group_journal/index.php?csid='.$csid.'&planid='.$planid.
                      '&eventid='.$eventid.'&departmentid='.$addvars['departmentid'].'#jm'.$dof->storage('schevents')->get_field($eventid,'date'));
        }elseif( $planid ) 
        {    
            $plan = $dof->storage('plans')->get($planid);
            $time = $plan->datetheme + $plan->reldate; 
            $path = $dof->url_im('journal', '/group_journal/index.php?csid='.$csid.'&planid='.$planid.
                      '&eventid='.$eventid.'&departmentid='.$addvars['departmentid'].'#jm'.$time);            
        }
        $link = '<a href="'.$path.'">'.$dof->get_string('backward','journal').'</a>';
        $strlink = '<br />'.$link;

        //печатаем результат работы
        $this->rez = '<p style="text-align:center; color:green;"><b>'.
                $dof->get_string('save_true','journal').'</b>'.$strlink.'</p>';
        parent::__construct($dof, $csid);
    }
    /** Определяем, что для страницы списка уроков в журнале нам нужны только уроки 
     * со статусами: 'plan', 'completed', 'postponed', 'replaced'
     * 
     * @return array
     */
    protected function get_eventstatuses()
    {
        return array('plan', 'completed', 'postponed', 'replaced');
    }
    /** Возвращает объект с полями, необходимыми для заполнения формы
     * 
     * отчета об уроке
     * @return mixed object - объект с полями для вставки в форму
     * или bool false
     */
    public function get_topic()
    {
        if ( ! $this->planid )
        {
            return $this->get_empty_topic();
        }
        $topic = new object;
        if ( ! $rectopic = $this->dof->storage('plans')->get($this->planid) )
        {//не получили запись
            return false; 
        }//возвращам заполненный отчет об уроке
            $topic->csid    = $this->csid;
            $topic->planid  = $rectopic->id;
            $topic->eventid = $this->eventid;
            if ( $sh = $this->dof->storage('schevents')->get($this->eventid) )
            {
                $topic->reldate = $sh->date;
                $topic->form = $sh->form;
            }else
            {
                $topic->reldate = $rectopic->reldate;
            }
            $topic->name          = $rectopic->name;
            $topic->homework      = $rectopic->homework;
            $topic->homeworkhours = $rectopic->homeworkhours;
            $topic->replacement   = $this->get_teacherid();
            $topic->note          = $rectopic->note;
            return $topic;
    }
    /**
     * Возвращает "пустой" объект отчета об уроке
     * @return object
     */
    private function get_empty_topic()
    {
        $topic = new object;
        $topic->csid = $this->csid;
        $topic->planid = 0;
        $topic->eventid = 0;
        $topic->reldate = time();
        $topic->topic = '';
        $topic->homework = '';
        $topic->replacement = $this->get_teacherid();
        $topic->note = '';
        return $topic;
    }
    /**@deprecated
     * Обновляет существующий или создает новый 
     * элемент тематического планирования
     * @param object $checkpoint - объект для обновления 
     * или создания записи в таблице plans 
     * @param object $event - объект для обновления 
     * или создания записи в таблице schevents 
     * @return mixed - bool true - если записи обновлены
     * или int id новой записи из таблицы schevents,
     * bool false в иных случаях
     */
    public function save_topic($checkpoint, $event)
    {
        global $DOF;
        if ( ! is_object($event) )
        {//нет данных для создания или обновления события
            return false;
        }
        if (isset($event->planid) AND $event->planid )
        {//надо создать новое событие для существующей КТ';
            return $this->create_event($event);
        }
        elseif (  ! isset($checkpoint->id) OR ! $checkpoint->id )
        {//надо создать новые КТ и событие';
            return $this->create_topic($checkpoint, $event);
        }else
        {//надо обновить КТ и событие';
            $plan = $DOF->storage('plans')->get($checkpoint->id);
            // вычисляем новую относительную дату
            $checkpoint->reldate = $plan->reldate;
            // обновляем записи
            $rez1 = $DOF->storage('plans')->update($checkpoint);
            $rez2 = $DOF->storage('schevents')->update($event);
            return $rez1 AND $rez2;
        }
        //если для существующей КТ выбрана новая тема?
        //если для существующей КТ выбрана другая КТ?
        return false;
    }
        
    /**@deprecated
     * Создает новый элемент тематического планирования
     * @param object $checkpoint - объект для 
     * создания записи в таблице plans 
     * @param object $event - объект для 
     * создания записи в таблице schevents 
     * @return mixed - int id новой записи из таблицы schevents,
     * bool false в иных случаях
     */
    public function create_topic($checkpoint, $event)
    {
        if ( ! $checkpointid = $this->create_checkpoint($checkpoint) )
        {//не удалось создать элемент тематического планирования
            return false;
        }
        //добавляем в событие id контрольной точки
        $event->planid = $checkpointid;
        //создаем событие
        if ( ! $scheventid = $this->create_event($event) )
        {//событие не создано - удаляем элемент темплана
            $this->dof->storage('plans')->delete($event->planid);
            return false;
        }
        //возвращаем id события
        return $scheventid;
    }
    /**
     * Создает и сохраняет запись в таблице plans
     * @param object $point - данные для сохранения
     * @return mixed int id новой записи или bool false
     */
    private function create_checkpoint($point)
    {
        global $DOF;
        if ( ! is_object($point) OR ! isset($point->name) OR 
             ! isset($point->reldate) )
        {//нет необходимых входных данных
            return false;
        }
        if ( ! $this->csid )
        {//не получили id потока
            return false;
        }
        if ( ! $begindate = $DOF->storage('cstreams')->get_field($this->csid,'begindate') )
        {//не получили дату начала учебы
            return false;
        }
        //создаем заготовку КТ
        $checkpoint = new object;
        $checkpoint->linkid = $this->csid;
        $checkpoint->linktype = 'cstreams';
        $checkpoint->name = $point->name;
        $checkpoint->reldate = $point->reldate;
        $checkpoint->homework = $point->homework;
        $checkpoint->homeworkhours = $point->homeworkhours;
        //вычисляем относительную дату контрольной точки
        $checkpoint->reldate = $point->reldate;
        $checkpoint->status = 'active';
        $checkpoint->directmap = $point->directmap;
        //заносим в БД
        return $this->dof->storage('plans')->insert($checkpoint);
    }
    /**
     * Создает объект события и сохраняет его в schevents
     * @param object $event - данные для сохранения
     * @return mixed int id новой записи или bool false
     */
    private function create_event($event)
    {
        if ( ! is_object($event) OR ! isset($event->date) OR 
             ! isset($event->planid) )
        {//нет необходимых данных
            return false;
        }
        if ( ! $this->csid )
        {//не получили id потока
            return false;
        }
        if ( ! $teacherid = $this->get_teacherid($this->csid) )
        {//не нашли преподавателя
            return false;
        }
        //создаем заготовку события
        $schevent = new object;
        $schevent->planid = $event->planid;
        $schevent->type = 'normal';
        $schevent->join = 0;
        $schevent->cstreamid = $this->csid;
        $schevent->teacherid = $teacherid;
        $schevent->date = $event->date;
        $schevent->form = $event->form;
        // @todo - в будущем брать из шаблона, а пока 45мин
        $schevent->duration = 2700;
        $schevent->status = 'plan';
        //заносим в БД
        return $this->dof->storage('schevents')->insert($schevent);
    }
    /** Формирует табличку тем в правой части экрана
     * @return string
     */
    public function print_table()
    {
        // распечатываем таблицу уроков из журнала, слегка ее модифицировав
        // $this->dof передается по ссылке для экономии ресурсов
        $table = new dof_im_journal_topic_page_table($this->dof, $this->csid);
        return $table->print_table();
    }
    
    /** Возвращает метку времени, которая будет создана для нового урока, 
     * чтобы осуществить корректное перенаправление на страницу журнала
     * 
     * @return int метка времени
     * @param  int время создания нового события
     */
    public function get_anchor_id($time)
    {
        $checkpoints = $this->get_checkpoints();
        if ( ! is_array($checkpoints) )
        {// проверим, получи ли мы нужный тип данных 
            return 0;
        }
        while ( array_key_exists($time, $checkpoints) )
        {// получим уникальное значение
            $time = $time+1;
        }
        // возвращаем уникальное значение ключа
        return $time-1;
    }
    /** Сохранить данные из формы проведения урока
     * @todo вставить проверку прав при создании и обновлении всех объектов
     *
     * @return bool
     * @param object $formdata - объект из формы класса dof_im_journal_formtopic_teacher
     * @param boolean $redirect - true/false(перенапрвлять пользователя или нет)(по умолчанию true-да)
     */
    public function save_complete_lesson_form($formdata, $redirect = true)
    {
        //print_object($formdata); die;
        $success = true;
        $eventid = $formdata->eventid;
        if ( isset($formdata->create_event) AND $formdata->create_event )
        {// нужно сохранить событие
            $eventid = $this->save_journal_form_event($formdata);
            $success = (bool)$eventid && $success;
        }elseif ( $formdata->eventid )
        {// обновляем событие
            $success = $this->update_journal_form_event($formdata) && $success;
        }

        // нет имени темы - складываем ее из род.тем
        $name = trim($formdata->name);
        // @todo когда появится возможность задавать неограниченное количество родительских тем -
        // изменить алгоритм сохранения
        $parentids   = array();
        if ( isset($formdata->parentid3) )
        {// если форма с парент активна
            if ( $formdata->parentid1 OR $formdata->parentid2 OR $formdata->parentid3 )
            {// если указана одна или несколько родительских тем
                $pointnames = array();
                if ( $formdata->parentid1 )
                {
                    if ( ! $name )
                    {// если название темы не было указано - то составим его из родительских тем
                        $pointnames[] = $this->dof->storage('plans')->get_field($formdata->parentid1, 'name');
                    }
                    $parentids[]   = $formdata->parentid1;
                }
                if ( $formdata->parentid2 )
                {
                    if ( ! $name )
                    {// если название темы не было указано - то составим его из родительских тем
                        $pointnames[] = $this->dof->storage('plans')->get_field($formdata->parentid2, 'name');
                    }
                    $parentids[]   = $formdata->parentid2;
                }
                if ( $formdata->parentid3 )
                {
                    if ( ! $name )
                    {// если название темы не было указано - то составим его из родительских тем
                        $pointnames[] = $this->dof->storage('plans')->get_field($formdata->parentid3, 'name');
                    }
                    $parentids[]   = $formdata->parentid3;
                }
                if ( ! $name )
                {
                    $formdata->name = implode($pointnames, '. ');
                }
            }
        }

        $planid = $formdata->planid;
        if ( ! $formdata->planid )
        {// контрольной точки нет
            if ( $formdata->plan_creation_type == 'create' )
            {// нужно создать новую контрольную точку
                $planid = $this->save_journal_form_plan($formdata, $parentids);
                if ( $eventid )
                {// если событие есть или было создано привязываем событие к контрольной точке
                    $success = $success & (bool)$this->set_journal_form_event_link($formdata, $eventid, $planid);
                }
            }elseif ( $formdata->plan_creation_type == 'select' )
            {// нужно просто привязать событие к уже существующей контрольной точке
                if ( $eventid )
                {// если событие есть или было создано привязываем событие к контрольной точке
                    $success = $success & (bool)$this->set_journal_form_event_link($formdata, $eventid, $formdata->existing_point);
                }
            }
        }else
        {// контрольная точка есть - и она редактируется
            $success = $success & (bool)$this->update_journal_form_plan($formdata, $parentids);
        }
        //если редирект требуется
        if ( $redirect === true )
        {// перенаправляем пользователя обратно на страницу журнала
            $this->apply_redirect_after_topic_save($formdata, $eventid, $planid, $success);
        }
    }

    /** Собрать данные из формы формы отчета об уроке в журнале
     * и создать из них учебное событие
     *
     * @return int - id нового созданного события в таблице schevents
     * @param object $formdata - объект данных из формы отчета об уроке в журнале
     */
    protected function save_journal_form_event($formdata)
    {
        // получаем объект события из формы
        $event = $this->get_event_object_from_form($formdata);
        // сохраняем событие в базу
        return $this->dof->storage('schevents')->insert($event,$formdata->eventid);
    }
    
    /** Собрать данные из формы формы отчета об уроке в журнале
     * и обновить событие
     *
     * @return bool - статус обновления в таблице schevents
     * @param object $formdata - объект данных из формы отчета об уроке в журнале
     */
    protected function update_journal_form_event($formdata)
    {
        $event = new stdClass;
        $event->ahours         = $formdata->event_ahours;
        $event->salfactor      = $this->dof->workflow('schevents')->calculation_salfactor(
                                 $formdata->eventid,true); 
                                // применяемый итоговый коэффициент
        $event->salfactorparts = serialize($this->dof->workflow('schevents')->calculation_salfactor(
                                 $formdata->eventid, true,true)); 
                                // сериализованный объект
        $event->rhours         = $this->dof->workflow('schevents')->calculation_salfactor(
                                 $formdata->eventid); 
                                // продолжительность в условных часах
        // обновляем событие в базе
        return $this->dof->storage('schevents')->update($event,$formdata->eventid);
    }

    /** Привязать событие к контрольной точке
     * @todo больше комментариев в коде функции
     *
     * @return bool
     * @param object $formdata - объект данных из формы отчета об уроке в журнале
     * @param int $neweventid[optional] - если событие создавалось из формы - то
     *                                       id только что созданного события
     * @param int $newplanid[optional] - id новой контрольной точки, если она была создана
     */
    protected function set_journal_form_event_link($formdata, $neweventid=false, $newplanid=false)
    {
        $event = new Object();
        if ( $neweventid )
        {
            $event->id = $neweventid;
        }else
        {
            $event->id = $formdata->eventid;
        }
        if ( $newplanid )
        {
            $event->planid = $newplanid;
        }else
        {
            $event->planid = $formdata->planid;
        }

        return $this->dof->storage('schevents')->update($event);
    }

    /** Собрать данные из формы формы отчета об уроке в журнале
     * и создать из них точку тематического планирования на поток
     *
     * @return int - id новой точки тематического планирования в таблице plans
     * @param object $formdata - объект данных из формы отчета об уроке в журнале
     */
    protected function save_journal_form_plan($formdata, $parentids=NULL)
    {
        // получаем объект тематического планироания из формы
        $plan = $this->get_plan_object_from_form($formdata);
        // вставляем собранный объект в базу и возвращаем его id
        if ( $id = $this->dof->storage('plans')->insert($plan) )
        {// обновим род темы
            if ( $this->dof->storage('planinh')->create_point_links($id, $parentids) )
            {
                return $id;
            }else
            {
                return false;
            }
        }else
        {
            return false;
        }
    }

    /** Собрать данные из формы формы отчета об уроке в журнале
     * и обновить данные о точке тематического планирования
     *
     * @return bool
     * @param object $formdata - объект данных из формы отчета об уроке в журнале
     */
    protected function update_journal_form_plan($formdata, $parentids=NULL)
    {
        // получаем объект тематического планироания из формы
        $plan = $this->get_plan_object_from_form($formdata);
        // обновляем существующую запись и возвращаем результат
        if ( $this->dof->storage('plans')->update($plan) )
        {// обновим род темы
            if ( $this->dof->storage('planinh')->upgrade_point_links($plan->id, $parentids) )
            {
                return true;
            }else
            {
                return false;
            }
        }else
        {
            return false;
        }
    }

    /** Создать объект точки тематического планирования из данных формы
     *
     * @return object
     * @param object $formdata
     */
    protected function get_plan_object_from_form($formdata)
    {
        $plan = new Object();
        $cstream = $this->dof->storage('cstreams')->get($formdata->csid);

        $plan->id             = $formdata->planid;
        $plan->linkid         = $formdata->linkid;
        $plan->linktype       = $formdata->linktype;
        $plan->name           = $formdata->name;
        // относительная дата начала
        // из даты начала потока вычитаем дату начала занятия
        if( isset($formdata->event_date) AND $formdata->event_date AND
            ( ( isset($formdata->create_event) AND $formdata->create_event )
            OR $formdata->eventid ) )
        {
            $plan->reldate    = $formdata->event_date - $formdata->begindate;
        }elseif( isset($formdata->eventid) AND $formdata->eventid AND
            $event = $this->dof->storage('schevents')->get($formdata->eventid) )
        {
            $plan->reldate    = $event->date - $formdata->begindate;
        }elseif( isset($formdata->pinpoint_date) AND $formdata->pinpoint_date )
        {
            $plan->reldate    = $formdata->pinpoint_date - $formdata->begindate;
        }
        $plan->type           = $formdata->type;
        $plan->homework       = $formdata->homework;
        // время на домашнюю работу - переводим из часов и минут в секунды
        // переводим часы и минуты в секунды
        // @todo сейчас время на домашнее задание задается только в минутах.
        //       если такое решение приживется 
        $homeworkhours = 0;
        $hoursname     = 'homeworkhoursgroup[hours]';
        $minutesname   = 'homeworkhoursgroup[minutes]';
        //if ( isset($formdata->$hoursname) )
        //{// собираем часы
        //    $homeworkhours += $formdata->$hoursname;
        //}
        if ( isset($formdata->$minutesname) )
        {// собираем минуты
            $homeworkhours += $formdata->$minutesname;
        }
        $plan->homeworkhours  = $homeworkhours;
        // темы созданные из журнала всегда имеют directmap=1
        $plan->directmap      = $formdata->directmap;
        // точная дата начала темы
        $plan->datetheme      = $formdata->begindate;
        $plan->plansectionsid = $formdata->plansectionsid;
        $plan->note           = $formdata->note;
        // шкала наследуется из предмета
        // @todo крайний срок сдачи в этой форме не указывается - возможно в будущем это следует изменить

        // @todo раскоментировать эту строку когда появится возможность указывать номер темы в плане
        // $plan->number

        // @todo когда появится возможность указывать в плане id moodle для синхронизации -
        // раскомментировать это поле

        // $plan->mdlinstance
        // $plan->typesync       =

        return $plan;
    }

    /** Создать объект учебного события из данных формы
     * @todo возможно следует передавать еще один параметр - $planid - если
     * событие одновременно создается и привязывается к контрольной точке. Узнать, возможно ли одновременно
     * @todo когда появится возможность задавать место события - создать переменную place
     *
     * @return object - нужной структуры для таблицы plans
     * @param object $formdata
     */
    protected function get_event_object_from_form($formdata)
    {
        $event = new Object();

        $event->form           = $formdata->event_form;
        $event->date           = $formdata->event_date;
        $event->type           = $formdata->event_type;
        $event->teacherid      = $formdata->event_teacherid;
        $event->duration       = $formdata->event_duration;
        $event->appointmentid  = $formdata->event_appointmentid;
        
        if ( isset($formdata->event_appointmentid) AND $formdata->event_appointmentid )
        {// назначение существует
            $status = $this->dof->storage('appointments')->get_field($formdata->event_appointmentid, 'status');
            if ( $status == 'patient' )
            {// учитель на больничном не может быть назначен событию
                $event->teacherid = 0;
                $event->appointmentid  = 0;
            }    
        }
        
        $event->ahours        = $formdata->event_ahours;
        $event->cstreamid     = $formdata->csid;
        if(isset($formdata->planid) AND $formdata->planid )
        {//Если КТ уже существует, то привязываем событие к нему
            $event->planid = $formdata->planid;
        }
        // $event->place         = ???

        return $event;
    }

    /** Перенаправить пользователя обратно после сохранения данных из формы журнала
     *
     * @return
     * @param object $eventid
     * @param object $planid
     * @param object $error[optional]
     */
    protected function apply_redirect_after_topic_save($formdata, $eventid, $planid, $success=true)
    {
        $depid = optional_param('departmentid', 0, PARAM_INT);
        $addvars = array();
        $addvars['departmentid'] = $depid;
        //die('function apply_redirect_after_topic_save() IS NOT COMPLETED!');
        //создадим ссылку на разворот журнала
        if ( $success )
        {// успех -  будем выводить ЭТО
            $message = 1;
        }
        $path = $this->dof->url_im('journal', '/group_journal/index.php#jm'.
            $this->dof->storage('schevents')->get_field($eventid,'date'),
        array('csid' => $formdata->csid,
                      'planid' => $planid,
                      'eventid' => $eventid,
                      'departmentid' => $addvars['departmentid']));
        $path2 = $this->dof->url_im('journal', '/group_journal/topic.php',
        array_merge(array('csid' => $formdata->csid,
                      'planid' => $planid,
                      'eventid' => $eventid,
                      'message' => $message),$addvars));
  

        $link = '<a href="'.$path.'">'.$this->dof->get_string('backward','journal').'</a>';
        $strlink = '<br />'.$link;

        //печатаем результат работы
        $this->rez = '<p style="text-align:center; color:green;"><b>'.
                $this->dof->get_string('save_true','journal').'</b>'.$strlink.'</p>';

        redirect($path2);
    }
}

/** Класс для подготовки сырых исходных данных для формирования 
 * школьного журнала и ему подобных документов
 */
class dof_im_journal_rawdata
{
    /**
     * @var dof_control
     * @type dof_control
     */
    var $dof;
    var $csid; // id учебного потока
    /** Конструктор - определяет с каким учебным потоком будет вестись работа
     * @param dof_control - глобальный объект $DOF 
     * @param int $csid - id связки учебного потока с образовательным учреждением
     */
    function __construct(dof_control $dof, $csid)
    {
        $this->dof   = $dof;
        $this->csid  = $csid;
    }
    
    /** Получить cstreamid (id учебного потока) по cstreamlinkid из таблицы cstreamlinks
     * @return mixed int - id учебного потока или bool false если такого потока нет
     */
    protected function get_cstreamid()
    {
         // получаем id учебного потока
        //return $this->dof->storage('cstreamlinks')->get_field($this->cslid, 'cstreamid');
        return $this->csid;
    }
    
    /** Получить список статусов с которыми будут извлекаться контрольные точки из таблицы plans
     * По умолчанию получаем все кроме удаленных
     * @return array  
     */
    protected function get_planstatuses()
    {
        return array('active', 'fixed','checked', 'completed');
    }
    
    /** Получить список статусов с которыми будут извлекаться события из таблицы schevents
     * По умолчанию получаем все события
     * 
     * @return array|null
     */
    protected function get_eventstatuses()
    {
        return null;
    }
    
    /** Получить все контрольные точки одного учебного потока
     * @return mixed array - массив объектов из таблицы plans
     * или bool false 
     * @return array - массив объектов из таблицы plans или false 
     */
    protected function get_checkpoints($emevent = true)
    {
        if ( ! $streamid = $this->csid )
        {// не получили id потока';
            return false;
        }
        // нужны не удаленные контрольные точки
        $planstatuses  = $this->get_planstatuses();
        // нужны только события в статусе plan или completed
        $eventstatuses = $this->get_eventstatuses();
        //выбираем записи 
        $checkpoints = $this->dof->storage('schevents')->
                         get_mass_date($streamid, $eventstatuses,$planstatuses,$emevent);
        return $checkpoints;
    }
    
    /** Получить все подписки на дисциплину для указанного учебного потока
     * 
     * @return array массив записей из таблицы cpassed или false
     */
    protected function get_cpassed()
    {
        if ( ! $list = $this->dof->storage('cpassed')->get_records(array('cstreamid'=>$this->csid,
                      'status'=>array_keys($this->dof->workflow('cpassed')->get_register_statuses()))) )
        {
            return false;
        }
        // сортировка по имени
        usort($list, array('dof_im_journal_rawdata', 'sortapp_by_sortname2'));
        
        return $list;
    }
    
    /** Получить всех студентов указанного учебного потока
     * 
     * @return array массив записей из таблицы persons или false
     */
    protected function get_students()
    {
        $studentids = array();
        // получаем список пройденных дисциплин
        $listcpassed = $this->get_cpassed();
        if ( ! $listcpassed )
        {
            return false;
        }
        // перебираем все подписки и создаем из них строку
        foreach ($listcpassed as $cpassed)
        {
            //$successorid = $this->dof->storage('cpassed')->get_last_successor($cpass->id);
            $studentids[] = $cpassed->studentid;
        }
        $studentids = implode($studentids, ', ');
        // return $this->dof->storage('persons')->get_list_by_list($listcpassed, 'studentid');
        return $this->dof->storage('persons')->get_records_select('id IN ('.$studentids.')',null,'lastname');
    }
    
    /** Получить имя указанной контрольной точки
     * 
     * @return string, если имя есть, или false, если оно не указано
     * @param int $planid - id записи в таблице plans или false
     */
    protected function get_checkpoint_name($planid)
    {
        return '';
    }
    
    /** Получить id преподавателя учебного потока
     * 
     * @return int - id преподавателя или false в случае неудачи
     */
    protected function get_teacherid()
    {
//        if ( ! $cstreamlink = $this->dof->storage('cstreamlinks')->get($this->cslid) )
//        {//не получили запись о потоко-классе';
//            return false;
//        }
        if ( ! $cstream = $this->dof->storage('cstreams')->get($this->csid) )
        {//не получили запись о потоке';
            return false;
        }
        return $cstream->teacherid;
    }
    
    /**
     * Функция сравнения двух объектов 
     * из таблицы persons по полю sortname
     * @param object $person1 - запись из таблицы persons
     * @param object $person2 - другая запись из таблицы persons
     * @return -1, 0, 1 в зависимости от результата сравнения
     */
    public function sortapp_by_sortname2($person1,$person2)
    {
        return strnatcmp($this->dof->storage('persons')->get_field($person1->studentid,'sortname'), 
                         $this->dof->storage('persons')->get_field($person2->studentid,'sortname') );
    }  
    
}

/*
 * Класс для проверки и обработки оценок из формы
 */
class dof_im_journal_process_gradesform extends dof_im_journal_rawdata
{
    /**
     * @var dof_control object
     */
    //var $dof;
    /**
     * @var stdClass object все оценки и сопутствующая им информация
     */
    protected $gradedata;
  //  protected $gradeactions;
    /**
     * @var array непроверенные данные пришедшие из массива $_POST.
     * Используются только для составления повторного запроса на сохранение данных.
     */
    private $mypost;
    /** Конструктор класса. Осуществляет все проверки и записывает данные во внутреннее поле.
     * 
     * @param dof_control $dof - объект $DOF
     * @param object $gradedata - массив $_POST из формы
     */
    function __construct($dof, $gradedata)
    {
        // создаем объект в котором будут храниться все проверенные данные
        $result = new object;
       
        // начинаем проверку скалярных данных
        $scalars = $this->check_scalar_data($gradedata);
        $result->teacherid = $scalars->teacherid;
        $result->eventid   = $scalars->eventid;
        $result->planid    = $scalars->planid;
        $result->csid     = $scalars->csid;
        $result->anchor    = $scalars->anchor;
        $result->conflictsresolved = $scalars->conflictsresolved;
        // вызываем родительский конструктор
        parent::__construct($dof, $result->csid);
        // проверяем массивы
        // проверим данные об отсутствующих учениках
        $result->away      = $this->check_away_array($gradedata);
        // проверим массив оценок 
        $result->grades    = $this->check_grades_array($gradedata);
        // проверим массив идентификаторов cpassed
        $result->cpassedid = $this->check_cpassed_array($gradedata);
        // узнаем id оценок для изменения их статуса
        $result->gradeid   = $this->check_gradeid_array($gradedata);
        // определяем тип действия, которое надо совершить
        if ( isset($gradedata['save']) )
        {// сохраняем данные
            $result->action = 'save';
        }elseif ( isset($gradedata['save_and_continue']) )
        {// сохранить и продолжить
            $result->action = 'save_and_continue';
        }elseif ( isset($gradedata['restore']) )
        {// восстановить исходные значения
            $result->action = 'restore';
        }else
        {// хз
            $result->action = 'ERROR';
        }
        // форма чексбоксa
        if ( isset($gradedata['box']) )
        {
            $result->box = 'true';
        }
        
        // запишем в итоговую переменную результат после проверок
        $this->gradedata = $result;
      
        // запишем исходный массив в поле объекта, если потом понадобится еще раз 
        // отправить данные после подтверждения
        $this->mypost    = $gradedata;
    }
    
    /** Обработать все данные, пришедшие из формы: 
     * установить посещаемость, выставить оценки 
     * и сформировать приказы
     * @return false в случае неудачи. 
     * В случае успеха производит редирект на страницу журнала
     * @todo сделать обработку ошибок через exceptions
     */
    public function process_form()
    {
        if ($this->gradedata->action == 'restore')
        {// если нажата кнопка "восстановить", то не переходим к сохранению оценок
            $this->do_redirect();
        }
        if ( ! $this->gradedata->teacherid )
        {// нет события - значит данные о посещаемости вообще не посылались
            return false;
        }
        // проверяем, установлено ли у кого-нибудь из учеников одновременно "н" и оценка
        $notices = $this->check_double_marks();
        
        if ( $notices )
        {// неточности есть - составим сообщение и ссылки
            $this->show_full_notice($notices);
            //типа все хорошо
            return true;
        }else
        {// выполняем остальные действия только если нет предупреждений 
            // формируем приказ';
            if ( ! empty($this->gradedata->grades) )
            {
                if ( ! $this->generate_order_grades() )
                {// ошибка  при создании приказа оценок';
                    return false;
                }
            }
            if ( ! $this->generate_order_presences() )
            {// ошибка  при создании приказа присутствия';
                return false;
            }
            // производим редирект, если все успешно
            $this->do_redirect();
        }
    }
      
    
    /** Сформировать приказ об изменении состояния учебного потока
     * 
     * @return true or false
     */
    protected function generate_order_grades()
    {
        $result = true;
        if ( ! $departmentid = $this->dof->storage('cstreams')->
                get_field($this->csid,'departmentid') )
        {//не получили id подразделения';
            return false;
        }
        
        //print_object($this->gradedata);
        $actions = array_unique($this->gradeactions);
        
        // определяем тип действия, которое нужно совершить с оценкой
        foreach ( $actions as $action )
        {
            switch ($action)
            {// @todo предусмотреть возможность обновления оценки
                case 'set_grade':    
                    if ( ! $this->order_set_grade($departmentid) )
                    {// не удалось выполнить одну из операций  - запомним это
                        $result = false;
                    }
                break;
                case 'delete_grade': 
                    if ( ! $this->order_delete_grade($departmentid) )
                    {// не удалось выполнить одну из опероаций  - запомним это
                        $result = false;
                    } 
                break; 
            }
        }
        
        return $result;
    }
    
    /** Формирует приказ - установить оценку
     * 
     * @return bool true в случае успеха и false в случае неудачи
     * @param int $departmentid
     */
    private function order_set_grade($departmentid)
    {
        //подключаем методы работы с приказом
        $order = $this->dof->im('journal')->order('set_grade');
        //создаем объект для записи
        $orderobj = new object;
        //сохраняем автора приказа
        $orderobj->ownerid = $this->gradedata->teacherid;
        //подразделение, к которому он относится        
        $orderobj->departmentid = $departmentid;
        //дата создания приказа
        $orderobj->date = dof_im_journal_get_date(time());
        //добавляем данные, о которых приказ
        $orderobj->data = $this->get_grades_fororder('set_grade');
        // сохраняем приказ в БД и привязываем экземпляр приказа к id
        $order->save($orderobj);
        // подписываем приказ
        $order->sign($this->gradedata->teacherid);

        //проверяем подписан ли приказ
        if ( ! $order->is_signed() )
        {//приказ не подписан
            return false;
        }
        //print 'исполняем приказ';
        if ( ! $order->execute() )
        {//не удалось исполнить приказ
            return false;
        }
        return true;
    }
    
    /** Формирует приказ - удалить оценку
     * 
     * @return bool true в случае успеха и false в случае неудачи 
     * @param int $departmentid
     */
    private function order_delete_grade($departmentid)
    {
        //подключаем методы работы с приказом
        $order = $this->dof->im('journal')->order('delete_grade');
        //создаем объект для записи
        $orderobj = new object;
        //сохраняем автора приказа
        $orderobj->ownerid = $this->gradedata->teacherid;
        //подразделение, к которому он относится        
        $orderobj->departmentid = $departmentid;
        //дата создания приказа
        $orderobj->date = dof_im_journal_get_date(time());
        //добавляем данные, о которых приказ
        $orderobj->data = $this->get_grades_fororder('delete_grade');
        // сохраняем приказ в БД и привязываем экземпляр приказа к id
        $order->save($orderobj);
        // подписываем приказ
        $order->sign($this->gradedata->teacherid);
        //проверяем подписан ли приказ
        if ( ! $order->is_signed() )
        {//приказ не подписан
            return false;
        }
        //исполняем приказ';
        if ( ! $order->execute() )
        {//не удалось исполнить приказ
            return false;
        }
        return true;
    }
    /** Формирует приказ - изменить оценку
     * 
     * @return bool true в случае успеха и false в случае неудачи
     * @param int $departmentid
     */
    private function order_update_grade($departmentid)
    {
        
    }
    /** Сформировать приказ об изменении состояния учебного потока
     * 
     * @return true or false
     */
    protected function generate_order_presences()
    {
        if ( ! $departmentid = $this->dof->storage('cstreams')->
                get_field($this->csid,'departmentid') )
        {//не получили id подразделения
            return false;
        }
        //подключаем методы работы с приказом
        $order = $this->dof->im('journal')->order('presence');
        //создаем объект для записи
        $orderobj = new object;
        //сохраняем автора приказа
        $orderobj->ownerid = $this->gradedata->teacherid;
        //подразделение, к которому он относится        
        $orderobj->departmentid = $departmentid;
        //дата создания приказа
        $orderobj->date = dof_im_journal_get_date(time());
        //добавляем данные, о которых приказ
        $orderobj->data = $this->get_presents_fororder();
        // сохраняем приказ в БД и привязываем экземпляр приказа к id
        $order->save($orderobj);
        // подписываем приказ
        $order->sign($this->gradedata->teacherid);
        //проверяем подписан ли приказ
        if ( ! $order->is_signed() )
        {//приказ не подписан
            return false;
        }
        //исполняем приказ
        if ( ! $order->execute() )
        {//не удалось исполнить приказ
            return false;
        }
        return true;
    }
    /** Отметить посещаемость. Заносит в базу данных всю информацию о посещаемости
     * для текущего события. 
     * 
     * @return true or false
     */
    protected function store_attendance()
    {
        if ( ! $this->gradedata->eventid )
        {// если обрабатываемая контрольная точка не привязана к конкретной дате, то 
            // нет необходимости в отметках посещаемости
            return true;
        }
        return $this->dof->storage('schpresences')->save_present_students($this->gradedata->eventid,null,$this->gradedata->away);
    }
    
    /** Обновить страницу после изменения оценок. Перенаправляет пользователя на страницу 
     * редактирования/просмотра журнала, в зависимости от нажатой кнопки
     * 
     * @return Эта функция не возвращает значений 
     */
    private function do_redirect()
    {
        $depid = optional_param('departmentid', 0, PARAM_INT);
        $addvars = array();
        $addvars['departmentid'] = $depid;
        switch ($this->gradedata->action)
        {// в зависимости от переданного значения перенаправляем на разные страницы
            case 'save': // сохранить
                redirect($this->dof->url_im('journal','/group_journal/index.php?csid='.$this->gradedata->csid
                .'&departmentid='.$addvars['departmentid'].'#jm'.$this->gradedata->anchor), '', 0); 
            break;
            case 'save_and_continue': // сохранить и продолжить
                redirect($this->dof->url_im('journal','/group_journal/index.php?csid='.$this->gradedata->csid.
                '&planid='.$this->gradedata->planid.'&eventid='.$this->gradedata->eventid
                .'&departmentid='.$addvars['departmentid'].'#jm'.$this->gradedata->anchor), '', 0); 
            break;
            case 'restore': // восстановить
                redirect($this->dof->url_im('journal','/group_journal/index.php?csid='.$this->gradedata->csid.
                '&planid='.$this->gradedata->planid.'&eventid='.$this->gradedata->eventid
                .'&departmentid='.$addvars['departmentid'].'#jm'.$this->gradedata->anchor), '', 0); 
            break;
            default: // по умолчанию возвращаемся на страницу журнала
                redirect($this->dof->url_im('journal','/group_journal/index.php?csid='.$this->gradedata->csid
                .'&departmentid='.$addvars['departmentid'].'#jm'.$this->gradedata->ancho), '', 0); 
            break;
        }
        
    }
    
    /**
     * Функции проверки данных
     */
    
    /** Вызывается из конструктора. проверяет массив на соответствие стандарту передачи данных
     * 
     * @param array $gradedata - массив с данными из формы
     * @return array - проверенный массив или false а случае неучачи
     */
    private function check_away_array($gradedata)
    {
        $away = array();
        
        $cpasseds = $this->get_cpassed();
        //var_dump($gradedata);die;
        if ( is_array($cpasseds) AND isset($gradedata['eventid']) AND $gradedata['eventid'] )
        {// если получили список учеников, и есть событие - начинаем его обработку
            $date = $this->dof->storage('schevents')->get_field($gradedata['eventid'], 'date');
            foreach ($cpasseds as $cpassed)
            {// перебираем студентов
                if ( isset($gradedata['noaway']) AND array_key_exists($cpassed->id, $gradedata['noaway']) )
                {// если н/о и есть запись то удаляем её
                    $params = array();
                    $params['eventid'] = $gradedata['eventid'];
                    $params['personid'] = $cpassed->studentid;
                    if ( $schpresent = $this->dof->storage('schpresences')->get_record($params) )
                    {// нашли запись - удаляем 
                        $this->dof->storage('schpresences')->delete($schpresent->id);
                    }
                    continue;
                }
                if ( isset($gradedata['away']) AND array_key_exists($cpassed->id, $gradedata['away'])) 
                    //OR  (isset($gradedata['noaway']) AND array_key_exists($student->id, $gradedata['noaway'])) )
                {// если id ученика есть в массиве отстутствующих - запишем, что его не было
                    $away[$cpassed->id] = 0;
                }else
                {// в противном случае считаем, что он был
                    $away[$cpassed->id] = 1;
                }
            }
        }
        // возвращаем массив нужной для структуры (для таблицы с посещаемостью)
        return $away;
    }
    /** Проверяет переданный массив оценок
     * 
     * @return проверенный массив с оценками вида [id_ученика] => Оценка
     * @param array $gradedata - массив $_POST из формы
     */
    private function check_grades_array($gradedata)
    {
        $grades = array();
        
        $cpasseds = $this->get_cpassed();
        if ( is_array($cpasseds) )
        {// начинаем егог обработку
            foreach ($cpasseds as $cpassed )
            {// записываем данные с проверкой
                if ( isset($gradedata['editgrades'][$cpassed->id]) )
                {// оценка есть
                    if ( isset($gradedata['noaway']) AND array_key_exists($cpassed->id, $gradedata['noaway']) )
                    {// есть "н/о" удаляем оценку тогда 
                        $grades[$cpassed->id] = addslashes('0');
                        $this->gradeactions[$cpassed->id] = 'delete_grade';
                    }elseif ( ($gradedata['editgrades'][$cpassed->id] <> '')  
                        AND ( (isset($gradedata['noaway']) AND ! array_key_exists($cpassed->id, $gradedata['noaway'])) 
                        OR     ( ! isset($gradedata['noaway']))) )
                    {// запомним, что оценку надо установить
                        $grades[$cpassed->id] = addslashes($gradedata['editgrades'][$cpassed->id]);
                        // @todo предусмотреть вариант обновления оценки
                        $this->gradeactions[$cpassed->id] = 'set_grade';
                    }else
                    {// запомним, что оценку надо удалить
                        $grades[$cpassed->id] = addslashes($gradedata['editgrades'][$cpassed->id]);
                        $this->gradeactions[$cpassed->id] = 'delete_grade';
                    }
                    
                }
            }
        }
        return $grades;
    }
    
    /** Проверить переданный из формы массив идентификаторов cpassed
     * 
     * @return array проверенный массив идентификаторов
     * @param array $gradedata - массив $_POST
     */
    private function check_cpassed_array($gradedata)
    {
        $cp = array();
        $cpasseds = $this->get_cpassed(); 
        if ( is_array($cpasseds) )
        {// собираем в массив ключи - id учеников
            foreach ($cpasseds as $cpassed)
            {// перебираем всех учеников 
                if (isset($gradedata['cpassedid'][$cpassed->id]) AND 
                    is_numeric($gradedata['cpassedid'][$cpassed->id]) )
                {// проверяем тип ключа и значение
                    $cp[$cpassed->id] = $gradedata['cpassedid'][$cpassed->id];
                }
            }
        }
        // возвращаем проверенный массив идентификаторов,
        // или пустой массив в случае былинного отказа
        return $cp;
    }
    
    /** Проверить массив идентификаторов оценок
     * 
     * @return  array проверенный массив идентификаторов
     * @param array $gradedata - массив $_POST
     */
    private function check_gradeid_array($gradedata)
    {
        $gradeid = array();
        $cpasseds = $this->get_cpassed(); 
        if ( is_array($cpasseds) )
        {// собираем в массив ключи - id учеников
            foreach ($cpasseds as $cpassed)
            {// перебираем всех учеников 
                if ( isset($gradedata['noaway']) AND array_key_exists($cpassed->id, $gradedata['noaway']) )
                {// есть "н/о" удаляем оценку тогда 
                    $gradeid[$cpassed->id] = $gradedata['gradeid'][$cpassed->id];
                }elseif ( (isset($gradedata['gradeid'][$cpassed->id]) AND is_numeric($gradedata['gradeid'][$cpassed->id])) )
                {// проверяем тип ключа и значение
                    $gradeid[$cpassed->id] = $gradedata['gradeid'][$cpassed->id];
                }else
                {
                    $gradeid[$cpassed->id] = 0;
                }
            }
        }
        // возвращаем проверенный массив идентификаторов,
        // или пустой массив в случае ошибки
        return $gradeid;
    }
    
    /** Проверяет все идентификаторы, пришедшие из формы
     * @todo оптимизировать работу этой функции
     * @return object - Проверенные скалярные данные
     * @param array $gradedata - массив $_POST
     */
    private function check_scalar_data($gradedata)
    {
        // создаем объект для итоговых данных
        $result = new object;
        // перечисляем все параметры, которые нужно проверить
        $vars = array('planid', 'eventid', 'teacherid', 'csid', 'conflictsresolved', 'anchor');
        // проверяем переменную
        foreach ( $vars as $var )
        {
            if ( isset($gradedata[$var]) AND is_numeric($gradedata[$var]) )
            {// не получили planid
                $result->$var = $gradedata[$var];
            }else
            {
                $result->$var = false;
            }
        }
        // возвращаем объект с данными
        return $result;
    }
    
    /** Получить массив оценок для формирования приказа
     * 
     * @return 
     * @param object $type
     */
    private function get_grades_fororder($type)
    {
        $obj = new stdClass;
        $obj->date = dof_im_journal_get_date(time());
        $obj->planid = $this->gradedata->planid;
        $obj->teacherid = $this->gradedata->teacherid;
        
        foreach ($this->gradedata->grades as $stid=>$grade)
        {
            if ( $type == $this->gradeactions[$stid] )
            {// если тип действия над проверяемой оценкой совпадает с заявленной
             // то добавляем ее в массив
                switch ($type)
                {// тип объекта в массиве зависит от действия, которое будет произведено над оценкой в приказе
                    case 'set_grade':  // выставить оценку
                    $mas[$stid] = array('grade'     => $grade,
                                        'cpassedid' => $stid,
                                        'status'    => 'tmp');
                    break;
                    case 'delete_grade': // удалить оценку
                    $mas[$stid] = array('grade'     => $grade,
                                        'cpassedid' => $stid,
                                        'id'        => $this->gradedata->gradeid[$stid]);
                    break;
                }
            }
        }
        ksort($mas);
        $obj->grades = $mas;
        return $obj;
    }
    private function get_presents_fororder()
    {
        $obj = new stdClass;
        $obj->eventid  = $this->gradedata->eventid;
        $obj->presents = $this->gradedata->away;
           if ( isset($this->gradedata->box) )
        {
            $obj->box  = $this->gradedata->box;
        }
        return $obj;
    }
    
    /** Проверяет наличие одновременно отмеченной буквы "н" и оценки для ученика
     * 
     * @return array массив id учеников, для которых одновременно выставлена оценка и статуст "отсутствовал"
     * или false, если таких учеников нет
     */
    private function check_double_marks()
    {
        $result = array();
        if ( ! $this->gradedata->eventid )
        {// нет события - значит данные о посещаемости вообще не посылались
            return false;
        }
        if ( isset($this->gradedata->conflictsresolved) AND $this->gradedata->conflictsresolved == 1 )
        {// если мы просто второй раз получаем данные с уже разрешенными конфликтами
            return false;
        }
        // собираем все id в массив
        $cpasseds = $this->get_cpassed();
        if ( is_array($cpasseds) )
        {
            foreach ( $cpasseds as $cpassed )
            {// просматриваем все выставленные оценки
                if (       $this->gradedata->grades[$cpassed->id]      AND
                     (bool)$this->gradedata->grades[$cpassed->id]      AND
                     isset($this->gradedata->away[$cpassed->id])       AND 
                           $this->gradedata->away[$cpassed->id]   == 0    )
                {// если есть отметка о посещаемости и оценка то помещаем такого ученика в массив 
                    $result[] = $cpassed->id;
                }
            }
        }
        if ( empty($result) )
        {
            return false;
        }else
        {
            return $result;
        }
    }
    /** Создает сообщения для случая, когда одновременно проставлен статус "отсутствовал" и оценка
     * 
     * @return string html-код сообщения, или false в случае ошибки 
     * @param object $notices
     */
    private function create_notice_message($cpasseds)
    {
        $result = '';
        if ( ! is_array($cpasseds) )
        {// неверный формаи исходных данных
            return false;
        }
        $result .= $this->dof->get_string('doble_marks_notice', 'journal');
        $result .= "<ul>\n";
        foreach ( $cpasseds as $cpid )
        {// перебираем массив учеников для составления сообщения
            $stid = $this->dof->storage('cpassed')->get_field($cpid, 'studentid');
            $student = $this->dof->storage('persons')->get($stid);
            if ($student)
            {// выводим фамилию и имя ученика как элемент списка
                $result .= '<li><b>'.$student->lastname.' '.$student->firstname."</b></li>\n";
            }else
            {// нарушена целостности БД - ошибка
                error($this->dof->get_string('no_student_in_base_with_id', 'journal').'='.$stid);
                break;
            }
        }
        $result .= "</ul>\n";
        $result .= $this->dof->get_string('save_data_question', 'journal');
        return $result;
    }
    
    /** Используется для составления массива $_POST в случае с подтверждением данных 
     * 
     * @return array массив для корректного выставления оценок после переадресации
     */
    private function create_post_again()
    {
        $result = array();
        // dirty hack для функции moodle, отображающей сообщения: она некорректно работает с многомерным $_POST
        // приводим значения элементов к нужному виду 
        foreach ( $this->mypost as $postkey=>$postvalue )
        {
            if ( is_array($postvalue) )
            {
                foreach ( $postvalue as $elkey=>$elvalue )
                {// делаем из двумерного массива одномерный, иначе не обработается
                    $result[$postkey.'['.$elkey.']'] = $elvalue;
                }
            }else
            {// скалярные данные записываем как есть
                $result[$postkey] = $postvalue;
            }
        }
        // делаем пометку о том, что учитель дал подтверждение своему выбору
        $result['conflictsresolved'] = 1;
        
        return $result;
    }
    
    /** Отображает предупреждение с заголовком и оформлением
     * 
     * @return null эта функция не возвращает значений
     * @param array $notices - id учеников, для которых нужно сформировать замечания
     */
    private function show_full_notice($notices)
    {
        $depid = optional_param('departmentid', 0, PARAM_INT);
        $addvars = array();
        $addvars['departmentid'] = $depid;
        $message = $this->create_notice_message($notices);
        // ссылка при нажатии на "да"
        $linkyes = $this->dof->url_im('journal', '/group_journal/process_grades.php', 
                array_merge(array('csid' => $this->csid, 
                      'planid' => $this->gradedata->planid, 
                      'eventid' => $this->gradedata->eventid),$addvars));
        // ссылка при нажатии на "нет" - возвращаем ся на страницу с оценками
        $linkno  = $this->dof->url_im('journal','/group_journal/index.php?
                   csid='.$this->gradedata->csid.
                   '&planid='.$this->gradedata->planid.
                   '&eventid='.$this->gradedata->eventid.
                   '#jm'.$this->gradedata->anchor,$addvars);
        // формируем массив для корректной передачи данных
        $optionsyes = $this->create_post_again();
        // распечатвем заголовок
//        $this->dof->modlib('nvg')->print_header(NVG_MODE_PAGE);
        // @todo предусмотреть возможность сохранения оценок после нажатия на кнопку "нет"
        $this->dof->modlib('widgets')->notice_yesno($message, $linkyes, $linkno, $optionsyes);
        // выводим нижнюю часть страницы
//        $this->dof->modlib('nvg')->print_footer(NVG_MODE_PAGE);
    }
}
/*
 * Класс для обработки посещаемости 
 */
class dof_im_journal_presence
{
    /**
     * @var dof_control
     */
    protected $dof;
    private $csid;
    protected $mas;

    function __construct($dof, $csid)
    {
        $this->dof = $dof;
        $this->csid = $csid;
    }
    /** Получить cstreamid (id учебного потока) по cstreamlinkid из таблицы cstreamlinks
     * @return mixed int - id учебного потока или bool false если такого потока нет
     */
    protected function get_cstreamid()
    {
         // получаем id учебного потока
        //return $this->dof->storage('cstreamlinks')->get_field($this->cslid, 'cstreamid');
        return $this->csid;
    }
    /** Находит всех студентов учебного процесса
     * @return array - список студентов
     */
    private function get_students()
    {
        if ( ! $csid = $this->get_cstreamid() )
        {
            return false;
        }
        $cstreams = $this->dof->storage('cpassed')->get_cstream_students($csid,'active');
        //print_object($cstreams);die;
        return $this->dof->storage('persons')->get_list_by_list($cstreams,'studentid');
    }
    /** Проверяет принадлежность студента к учебному процессу
     * @param int $stid - id студента
     * @return bool
     */
    private function check_student($stid)
    {
        $students = $this->get_students();
        $presence = false;
        foreach ($students as $student)
        {
            if ($student->id == $stid)
            {
                $presence = true;
            }    
        }
        return $presence;
    }
    /** Формирует массив присутствия студентов
     * @return array
     */
    public function presence_students()
    {
        $students = $this->get_students();
        foreach ($students as $student)
        {
            $this->mas[$student->id] = 1;
        }
        return $this->mas;
    }
    /** Формирует массив отсутствующих студентов
     * @param array $away - отсутствующие студенты
     * @return array
     */
    public function absence_students($away)
    {
        $away = array_keys($away);
        foreach ($away as $stid)
        {
            if ($this->check_student($stid))
            {
                $this->mas[$stid] = 0;
            }
        }
        return $this->mas;
    }
}
?>