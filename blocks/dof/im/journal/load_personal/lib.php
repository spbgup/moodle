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

//загрузка библиотек верхнего уровня
require_once(dirname(realpath(__FILE__))."/../lib.php");

/** Просмотр персональной нагрузки учителя за месяц
 *
 */
class dof_im_journal_teacher_salfactors
{
    protected $dof;
    private $personid;
    private $begindate;
    private $enddate;
    private $departmentid;

    /** Конструктор
     * @param object $dof
     * @param int $personid
     * @param int $begindate
     * @param int $depid
     */
    public function __construct($dof, $personid, $date, $depid=0)
    {
        $this->dof = $dof;
        $this->personid = $personid;
        $this->departmentid = $depid;

        // устанавливаем временные рамки
        $now = dof_gmgetdate(time());
        list($year, $month) = explode('_', $date);
        $this->begindate = mktime(dof_servertimezone(), 0, 0, intval($month), 1, $year);
        $this->enddate   = mktime(dof_servertimezone(), 0, 0, intval($month), 25, $year);
    }

    /** Таблицы с данными о персональной фактической нагрузке за месяц
     * @return string - html-код таблицы
     */
    public function get_table_salfactors()
    {
        $rez = '';

        // просмотр нагрузки за предыдущий месяц
        $prevdate = dof_userdate($this->get_prev_month(1), '%Y_%m');
        $urlparams = array('personid' => $this->personid,
                'departmentid' => $this->departmentid,
                'date' => $prevdate);
        $prev = "<a href='".$this->dof->url_im('journal', '/load_personal/loadpersonal.php', $urlparams)
                    ."'>".$this->dof->get_string('view_teacher_salfactors_prevmonth','journal')."</a>";
        $now = dof_gmgetdate(time());
        if ( $this->get_prev_month(1) <= mktime(0,0,0,$now['mon']-1,1,$now['year']) AND
             ! $this->dof->im('journal')->is_access('view:salfactors_history') )
        {// права нет - ссылка на более одного месяца недействительна
            $prev = $this->dof->get_string('view_teacher_salfactors_prevmonth','journal');;
        }
        $rez .= "<span style='float: left;'>".$prev."</span>";
        $next = $this->dof->get_string('view_teacher_salfactors_nextmonth','journal');

        if ( $this->access_next_month() )
        {// ссылка нa след. месяц активна
            $urlparams['date'] = dof_userdate($this->get_next_month(1), '%Y_%m');;
            $next = "<a href='".$this->dof->url_im('journal', '/load_personal/loadpersonal.php', $urlparams)
            ."'>".$next."</a>";
        }

        $rez .= "<span style='float: right;'>".$next."</span>";

        $ids = array();
        if ( $appoints = $this->dof->storage('appointments')->get_appointment_by_persons(
                $this->personid) )
        {// получили назначения - сохраним id в массив
            foreach ( $appoints as $appoint )
            {
                $ids[] = $appoint->id;
            }
        }
         
        // данные за текущий
        $rez .= $this->get_salfactors_string($ids);

        return $rez;
    }

    /** Получение данных о персональной нагрузке
     * @param array $appoints - id назначений
     * @return string
     */
    public function get_salfactors_string($appoints)
    {
        $rez = '';

        // события с 26 по 1 пред. месяца
        $begindate = $this->get_prev_month(26);
        $date = dof_gmgetdate($this->begindate);
        $enddate = mktime(dof_servertimezone(),0,0,$date['mon'],0,$date['year']);

        // заголовок
        $rez .= "<br/><div align='center'><b>".$this->dof->get_string('correction_for_previous_month',
                'journal')."</b></div><br/>";

        // таблицa
        $rez .= $this->get_events_table($appoints, $begindate, $enddate);

        // баллы по факту
        $prevfact = $this->factor;

        // прогноз с 1 по 25 пред. месяца
        $begindate = $this->get_prev_month(1);
        $enddate = $this->get_prev_month(25);
        $prevforecast = $this->get_forecast($appoints,$begindate, $enddate);

        // результаты
        $rez .= "<br/><div style='text-decoration: underline;'>".
                $this->dof->get_string('paid_for_previous_month',
                'journal')." = <b>".$prevforecast."</b>";

        $rez .= "<br>".$this->dof->get_string('execute_for_previous_month',
                'journal')." = <b>".$prevfact."</b>";

        $rez .= "<br>".$this->dof->get_string('correction_for_previous_month',
                'journal')." = <b>".($prevfact - $prevforecast)."</b></div>";

        // события с 1 по 25 текущего месяца
        $rez .= "<br/><div align='center'><b>".$this->dof->get_string('salhours_for_1_25_days',
                'journal')."</b></div><br>";
         
        // таблица
        $rez .= $this->get_events_table($appoints, $this->begindate, $this->enddate);
        $curfact = $this->factor;
        $curforecast = round($curfact / 6, 2);

        // итог
        $rez .= "<br/><div align='center'><b>".$this->dof->get_string('total_to_pay',
                'journal')."</b></div>";

        // результаты
        $rez .= "<br/><div style='text-decoration: underline;'><br>".
                $this->dof->get_string('correction_for_previous_month',
                'journal')." = <b>".($prevfact - $prevforecast)."</b>";

        $rez .= "<br>".$this->dof->get_string('salhours_for_1_25_days',
                'journal')." = <b>".$curfact."</b>";

        $rez .= "<br>".$this->dof->get_string('forecast_on_end_month',
                'journal')." = <b>".$curforecast."</b>";

        // поправка
        $amendment = ($prevfact - $prevforecast) + $curfact + $curforecast;
        $rez .= "<br>".$this->dof->get_string('total_to_pay',
                'journal')." = <b>".$amendment."</b></div>";

        return $rez;
    }

    /** Получение таблицы с событиями за указанный период
     * @param array $appoints - массив назначений
     * @param int $begindate - начало периода
     * @param int $enddate - конец периода
     * @return string - html-код таблицы
     */
    private function get_events_table($appoints, $begindate, $enddate)
    {
        $rez = array('table' => '', 'rhours' => 0, 'factload' => 0);
        $table = new stdClass();
        $table->tablealign = "center";
        $table->cellpadding = 2;
        $table->cellspacing = 2;
        $table->align = array ("center","center","center","center","center","center",
                "center","center","center","center","center","center","center");
        $table->head = $this->get_header();
        $table->data = array();
        $this->factor = 0;
        if ( empty($appoints) )
        {// нет назначений - вернем пустую таблицу
            $table->data[] = array($this->dof->get_string('loadteacher_score', 'journal'),'','','',
                               '','','','','','','',$rez['factload'],$rez['rhours']);
            return $this->dof->modlib('widgets')->print_table($table,true);
        }
        // назначения есть - продолжаем
        // собираем данные для выборки
        $counds = new stdClass;
        $counds->appointmentid = $appoints;
        $counds->date_from = $begindate;
        $counds->date_to = $enddate;
        $counds->status = array('plan','completed','implied','postponed');
        $select = $this->dof->storage('schevents')->get_select_listing($counds);
        if ( ! $events = $this->dof->storage('schevents')->get_records_select($select,null,'date') )
        {// нет событий - вернем пустую таблицу
            $table->data[] = array($this->dof->get_string('loadteacher_score', 'journal'),'','','',
                               '','','','','','','',$rez['factload'],$rez['rhours']);
            return $this->dof->modlib('widgets')->print_table($table,true);
        }
        foreach ( $events as $event )
        {
            $data = array();
            $params = new stdClass();
            $rowclass = '';
            $completed = $this->dof->get_string('no', 'journal');
            $data[] = dof_userdate($event->date, '%d/%m/%Y');
            $data[] = dof_userdate($event->date, '%H:%M');
            $data[] = ($event->duration/60).' ' .$this->dof->modlib('ig')->igs('min').'. ';
            $data[] = $event->ahours;
            if ( isset($event->salfactorparts) )
            {// объект с коэф. создан - берем данные
                $params = unserialize($event->salfactorparts);

                $data[] = $params->vars['count_active_cpassed']
                . '/' .$params->vars['config_salfactor_countstudents'];
                $data[] = $params->vars['programmitem_salfactor'];
                unset($params->vars['programmsbcs_salfactors']['all']);
                $obj = array();
                if ( ! empty($params->vars['programmsbcs_salfactors']) )
                {
                    foreach ( $params->vars['programmsbcs_salfactors'] as $id=>$salfactor )
                    {
                        $contractid = $this->dof->storage('programmsbcs')->get_field($id,'contractid');
                        $studentid = $this->dof->storage('contracts')->get_field($contractid,'studentid');
                        $fio = $this->dof->storage('persons')->get_fullname($studentid);
                        $obj[] = '<span title="'.$fio.'">'.$salfactor.'</span>';;
                    }
                }
                $data[] = implode('<br>',$obj);
                unset($params->vars['agroups_salfactor']['all']);
                $obj = array();
                if ( ! empty($params->vars['agroups_salfactor']) )
                {
                    foreach ( $params->vars['agroups_salfactor'] as $id=>$salfactor )
                    {
                        $name = $this->dof->storage('agroups')->get_field($id,'name');
                        $obj[] = '<span title="'.$fio.'">'.$salfactor.'</span>';;
                    }
                }
                $data[] = implode('<br>',$obj);
                $cstream = $params->vars['cstreams_salfactor'];
                if ( $this->dof->storage('cstreams')->is_access('view', $event->cstreamid) )
                {
                   $cstream = "<a href=".$this->dof->url_im('cstreams', '/view.php',array(
                        'cstreamid' => $event->cstreamid,
                        'departmentid' => optional_param('departmentid', 0, PARAM_INT))).">"
                        .$params->vars['cstreams_salfactor'].'</a>';
                }     
                $data[] = $cstream;
                $cstreamsub = $params->vars['cstreams_substsalfactor'];
                if ( $this->dof->storage('cstreams')->is_access('view', $event->cstreamid) )
                {
                   $cstreamsub = "<a href=".$this->dof->url_im('cstreams', '/view.php',array(
                        'cstreamid' => $event->cstreamid,
                        'departmentid' => optional_param('departmentid', 0, PARAM_INT))).">"
                        .$params->vars['cstreams_substsalfactor'].'</a>';
                }     
                $data[] = $cstreamsub;
                $data[] = $params->vars['schtemplates_salfactor'];
                
                $data[] = $params->vars['schevents_completed'];
                $data[] = '<span title="'.$params->formula.'">'.$event->rhours.'</span>';
                $rez['factload'] += $params->vars['schevents_completed'];
            }else
            {// объекта нет - ставим данные по-умолчанию
                $data[] = '';
                $data[] = '';
                $data[] = '';
                $data[] = '';
                $data[] = '';
                $data[] = '';
                $data[] = '';
                $data[] = '';
                $data[] = '';
            }

            $rez['rhours'] += $event->rhours;

            if ( $event->status == 'implied' )
            {// подразумеваемые уроки выделяются красным
                $rowclass = 'implied';
            }
            $table->rowclasses[] = $rowclass;
            $table->data[] = $data;
        }
        $this->factor = $rez['rhours'];
        $table->data[] = array($this->dof->get_string('loadteacher_score', 'journal'),'','','',
                               '','','','','','','',$rez['factload'],$rez['rhours']);
        return $this->dof->modlib('widgets')->print_table($table,true);
    }

    /** Получить прогноз за указанный период
     * @param array $appoints -
     * @param int $begindate -
     * @param int $enddate -
     * @return int
     */
    private function get_forecast($appoints, $begindate, $enddate)
    {
        $rhours = 0;
        if ( ! empty($appoints) )
        {// назначения есть - продолжаем
            // собираем данные для выборки
            $counds = new stdClass;
            $counds->appointmentid = $appoints;
            $counds->date_from = $begindate;
            $counds->date_to = $enddate;
            $counds->status = array('plan','completed','implied','postponed');
            $select = $this->dof->storage('schevents')->get_select_listing($counds);
            if ( $events = $this->dof->storage('schevents')->get_records_select($select,null,'date') )
            {// есть события - ачинаем подсчет
                foreach ( $events as $event )
                {
                    $rhours += $event->rhours;
                }
            }
        }
        return round($rhours/6, 2);
    }

    /** Получение timestamp указанного числа следующего месяца
     * @param int day
     * @return int
     */
    private function get_next_month($day)
    {
        $date = dof_gmgetdate($this->begindate);
        return mktime(dof_servertimezone(),0,0,$date['mon']+1,$day,$date['year']);
    }

    /** Получение timestamp указанного числа прошлого месяца
     * @param int day
     * @return int
     */
    private function get_prev_month($day)
    {
        $date = dof_gmgetdate($this->begindate);
        return mktime(dof_servertimezone(),0,0,$date['mon']-1,$day,$date['year']);
    }

    /** Проверяет, является ли след. месяц активным
     * @return bool
     */
    private function access_next_month()
    {
        // первое число текущего  месяца
        $now = dof_gmgetdate(time());
        $now = mktime(dof_servertimezone(),0,0,$now['mon'],1,$now['year']);
        $now = dof_gmgetdate($now);
        $begin = dof_gmgetdate($this->begindate);

        // возьмем след. месяц
        $next = dof_gmgetdate($this->get_next_month(1));

        if ( $next[0] > $now[0] )
        {// след. месяц больше текущего - выходим
            return false;
        }
        return true;
    }

    /** Получение заголовокв таблицы
     *  @return array
     */
    private function get_header()
    {
        return array(
                $this->dof->get_string('loadteacher_date_alt', 'journal'),
                $this->dof->get_string('loadteacher_time', 'journal'),
                $this->dof->get_string('loadteacher_duration', 'journal'),
                '<span title="ahours">'.
                $this->dof->get_string('loadteacher_ahours', 'journal'),
                '<span title="config_salfactor_countstudents">'.
                $this->dof->get_string('loadteacher_countstudents', 'journal').'</span>',
                '<span title="programmitem_salfactor">'.
                $this->dof->get_string('loadteacher_programmitem', 'journal').'</span>',
                '<span title="programmsbcs_salfactors">'.
                $this->dof->get_string('loadteacher_students', 'journal').'</span>',
                '<span title="agroups_salfactors">'.
                $this->dof->get_string('loadteacher_agroup', 'journal').'</span>',
                '<span title="agroups_salfactors">'.
                $this->dof->get_string('loadteacher_cstream', 'journal').'</span>',
                '<span title="cstreams_subsalfactor">'.
                $this->dof->get_string('loadteacher_cstreamsub', 'journal').'</span>',
                '<span title="schtemplates_salfactor">'.
                $this->dof->get_string('loadteacher_schtemplate', 'journal').'</span>',
                '<span title="schevents_completed">'.
                $this->dof->get_string('loadteacher_complete', 'journal').'</span>',
                $this->dof->get_string('loadteacher_rhours', 'journal'));
    }
}

?>