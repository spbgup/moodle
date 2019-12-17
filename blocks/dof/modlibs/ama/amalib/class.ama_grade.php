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
// Copyright (C) 2011-2999  Evgeniy Gorelov (Евгений Горелов)             //
// Copyright (C) 2011-2999  Evgeniy Yaroslavtsev (Евгений Ярославцев)     //
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

//Все в этом файле написано на php5.
//Проверяем совместимость с ПО сервера
if ( 0 > version_compare(PHP_VERSION, '5') )
{
    die('This file was generated for PHP 5');//если ниже php5, то кончаем работу
}

/** Класс для синхронизации успевааемости
 * @access public
 */
class ama_grade extends ama_base
{
    /**
     * id объекта (курса)
     */
    protected $courseid = false; 
    /**
     * id item в курсе, по которому следует брать оценку, если он задан, 
     * если не задан, то брать итоговую
     */
    protected $id = false; 
    
    /** Конструктор
     * @param int $id - id курса, оценки которого синхронизуем
     * @param int $itemid - id item, по которому следует брать оценки, если он задан.
     * Если не задан, то брать итоговую оценку 
     * @access public
     */
    public function __construct($courseid, $id = NULL)
    {
        if ($courseid)
        {
            $this->courseid = $courseid;
        }
        else
        {
            print_error('Course id is not specified.'); // не указан id курса
        }
        if (! is_null($id) )
        {
            $this->itemid = $itemid;
        }
    }
    
    /**
     * Получить оцеку за курс по userid и itemid
     * Если itemid не передан, то получается итоговая оценка курса.
     * 
     * @param int $userid - id пользователя, оценки которого запрашиваем
     * @param int $itemid[optional] - id item, по которому запрашиваем оценки
     * @param int $datestart[optional] - время начала интервала времени, в котором должен быть сдан тест
     * @param int $dateend[optional] - время конца интервала времени, в котором должен быть сдан тест
     * @return float|false оценка или false (если item скрыт или требуется 
     *      пересчет оценок, а пересчет не помогает, т.е. когда ошибка)
     * @author Evgeniy Gorelov
     */
    public function get_last_grade( $userid, $itemid=null, $datestart=null, $dateend=null )
    {
        if ( is_null($itemid) )
        {
            return $this->get_total_grade($userid);
        }
        
        // обновить все оценки курса
        grade_regrade_final_grades($this->courseid);
        
        // return tracking object
        $gpr = new grade_plugin_return(array('type'=>'report', 'plugin'=>'user', 
                'courseid'=>$this->courseid, 'userid'=>$userid));
        $context = get_context_instance(CONTEXT_COURSE, $this->courseid);
        
        // создать объект-отчет по студенту
        $report = new grade_report_user($this->courseid, $gpr, $context, $userid);
        // создать объект для оценок по конкретному itemid и userid 
        if (! $grade_grade = grade_grade::fetch(array('itemid'=>$itemid,'userid'=>$userid))) {
            $grade_grade = new grade_grade();
            $grade_grade->userid = $userid;
            $grade_grade->itemid = $itemid;
        }
        
        // загрузить item, на который ссылается itemid
        $grade_grade->load_grade_item();
        // последнее время изменения оценки
        $timemodified = $grade_grade->timemodified;
        // если заданы и начало, и конец итревала, то вернем null, если оценка вне интервала
        if ( !is_null($datestart) AND !is_null($dateend) )
        {
            if ( !((int)$datestart <= $timemodified AND (int)$timemodified <= $dateend) )
            {
                return null;
            }
        }
        // если задано только начало итревала, то вернем null, если оценка более новая
        if ( !is_null($datestart) AND is_null($dateend) )
        {
            if ( !((int)$datestart <= $timemodified) )
            {
                return null;
            }
        }
        // если задан только конец итревала, то вернем null, если оценка более старая
        if ( is_null($datestart) AND !is_null($dateend) )
        {
            if ( !($timemodified <= (int)$dateend) )
            {
                return null;
            }
        }
        
        
        // взять итоговую оценку (ею является оценка, 
        // которую получил userid по itemid в courseid)
        $gradeval = $grade_grade->finalgrade;
                
        /// Hidden Items
        if ($grade_grade->grade_item->is_hidden()) {
            $hidden = ' hidden';
        }

        // Анализируем item
        
        // (Если needsupdate == 1 для итоговой оценки, это означает, 
        // что еще не создано ни одного теста в этом курсе и не откуда 
        // высчитывать итоговую оценку) OR ( если запрошенный item - скрыт), то false
        if ( $grade_grade->grade_item->needsupdate OR $grade_grade->is_hidden()
        )
        {
            $gradeval = false;
        }
        else 
        {
            $gradeval = $report->blank_hidden_total($this->courseid, $grade_grade->grade_item, $gradeval);
        }
        unset($gpr);
        unset($context);
        unset($report);
        unset($grade_grade);
        
        return $gradeval;
    }
    
    /**
     * Получить итоговую оценку за курса
     * 
     * @param int $itemid - id пользователя, оценки которого запрашиваем
     * @param int $datestart[optional] - время начала интервала времени, в котором должен быть сдан тест
     * @param int $dateend[optional] - время конца интервала времени, в котором должен быть сдан тест
     * @return  float|false оценка или false (если item скрыт или требуется 
     *      пересчет оценок, а пересчет не помогает, т.е. когда ошибка)
     * @author Evgeniy Gorelov
     */
    public function get_total_grade( $userid, $datestart=null, $dateend=null, $percentage = true )
    {
        global $CFG;
        $result = false;

        // обновить все оценки курса
        grade_regrade_final_grades($this->courseid);
        
        // тут инициализируется report->gtree !!!
        $courseid = $this->courseid;
        $gpr = new grade_plugin_return(array('type'=>'report', 'plugin'=>'grader', 'courseid'=>$courseid, 'page'=>null));
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        $report = new grade_report_grader($courseid, $gpr, $context);
        // количество пользователей в отчете: 0 - не ограничиваем
        $CFG->grade_report_studentsperpage = 0;
        // final grades MUST be loaded after the processing
        $report->load_users();
        $report->load_final_grades();
        if ( ! $report->grades )
        {
            return false;
        }
        // если пользователь не подписан на курс, то его не будет в объекте
        if (! array_key_exists($userid, $report->grades))
        {
            return false;
        }
        
        // т.к. итоговая оценка не имеет даты изменения и создания, то возьмем самую 
        // последнюю дату из всех itmes, т.к. итоговая оценка пересчитывается после 
        // сдачи хотя бы одного теста
        $timemodified = 0;
        foreach ($report->gtree->items as $itemid=>$unused)
        {
            $item =& $report->gtree->items[$itemid];
            $grade = $report->grades[$userid][$item->id];
            if ( !empty($grade->timemodified) AND ( $timemodified < $grade->timemodified ) )
            {
                $timemodified = $grade->timemodified;
            }
        }
        // если заданы и начало, и конец итревала, то вернем null, если оценка вне интервала
        if ( !is_null($datestart) AND !is_null($dateend) )
        {
            if ( !((int)$datestart <= $timemodified AND (int)$timemodified <= $dateend) )
            {
                return null;
            }
        }
        // если задано только начало итревала, то вернем null, если оценка более новая
        if ( !is_null($datestart) AND is_null($dateend) )
        {
            if ( !((int)$datestart <= $timemodified) )
            {
                return null;
            }
        }
        // если задан только конец итревала, то вернем null, если оценка более старая
        if ( is_null($datestart) AND !is_null($dateend) )
        {
            if ( !($timemodified <= (int)$dateend) )
            {
                return null;
            }
        }
        
        foreach ($report->gtree->items as $itemid=>$unused)
        {
            $item =& $report->gtree->items[$itemid];
            $grade = $report->grades[$userid][$item->id];
            // если берем обычную итоговую оценку курса
            $gradeval = $grade->finalgrade;
            $gradedisplaytype = $item->get_displaytype();

            // Пока не удалять (это аналог, может пригодиться)
            //$res1 = grade_format_gradevalue($gradeval, $item, true, $gradedisplaytype, null);

            $itemtype = 'course'; // тип для получения итога курса
            $itemmodule = null;
            $iteminstance = $item->iteminstance;
            
            // получаем объект с итоговой оценкй по курсу

            // т.к. этот метод получает оценку из курса по iteminstance (это id плагина, которым считается конкретный item), а т.к. 
            // нам надо взять итоговую оценку, а не по конкретному item, то ставим тип оценки 'course' = оценка за курс вцелом, 
            // но она тоже считается с помощью плагина, поэтому для нее существует iteminstance, следовательно надо проверитьвсе item. Для всех item, кроме итоговой, 
            // вглубине возвращаемого объекта в свойстве 'grade' будет null, и только у итоговой - итоговая оценка. Поэтому берем первую не null оценку
            $g = grade_get_grades($courseid, $itemtype, $itemmodule, $iteminstance, $userid);

            // вытягиваем из обекта нужную инф-ю (студента и оценку)
            if ( array_key_exists('items', $g) )
            {
                $data = array_shift($g->items);
            }
            else
            {
                $data = false;
            } 
            //$data = array_shift($g->items);
            if ( empty ($data) OR is_null($data) OR !$data )
            {
                continue;
            }
            $gs = (array_key_exists('grades', $data)) ? (array_shift($data->grades)) : (false);
            //$gs = array_shift($data->grades);
            $result = (array_key_exists('grade', $gs)) ? ($gs->grade) : (false);
            //$result = $gs->grade;
            if ( is_bool($result) AND !$result )
            {
                continue;
            }
            if ( $percentage )
            {
                $result = $this->get_total_grade_percentage($result,$grade->itemid,$userid);
            }
            // если дело дошло досюда, значит итоговая оценка получена,
            // прерываем цикл и возвращаем итоговую оценку,если она не null
            break;
        }

        unset($gpr);
        unset($context);
        unset($report);
        unset($grade);
        unset($g);
        unset($gs);
        // Если оценка = null, то пользователь подписан, 
        // но не сдавал еще ни одного теста
        var_dump($result);
        return $result;
    }

    public function get_total_grade_percentage($value, $itemid, $userid, $decimals = null, $localized = true) 
    {
        global $DB;
        print $value.'_'.$itemid;
        if ( ! $grade_grade = grade_grade::fetch(array('itemid'=>$itemid,'userid'=>$userid)) )
        {
            return 0;
        }
        $grade_grade->load_grade_item();
        $min = $grade_grade->grade_item->grademin;
        $max = $grade_grade->grade_item->grademax;
        if ($min == $max) {
            return 0;
        }
        $value = $grade_grade->grade_item->bounded_grade($value);
        $percentage = (($value-$min)*100)/($max-$min);
        return format_float($percentage, $decimals, $localized);
    }    
    
/*object(grade_grade)347 (25) { ["table"]=> string(12) "grade_grades" ["required_fields"]=> array(17) 
{ 
[0]=> string(2) "id" [1]=> string(6) "itemid" [2]=> string(6) "userid" [3]=> string(8) "rawgrade" [4]=> string(11) "rawgrademax" [5]=> string(11) "rawgrademin" [6]=> string(10) 
"rawscaleid" [7]=> string(12) "usermodified" [8]=> string(10) "finalgrade" [9]=> string(6) "hidden" [10]=> string(6) "locked" [11]=> string(8) "locktime" [12]=> string(8) "exported" 
[13]=> string(10) "overridden" [14]=> string(8) "excluded" [15]=> string(11) "timecreated" [16]=> string(12) "timemodified" } ["optional_fields"]=> array(4) { ["feedback"]=> NULL 
["feedbackformat"]=> int(0) ["information"]=> NULL ["informationformat"]=> int(0) } ["itemid"]=> string(2) "81" ["grade_item"]=> &object(grade_item)377 (39) 
{ 
    ["table"]=> string(11) "grade_items" ["required_fields"]=> array(29) { [0]=> string(2) "id" [1]=> string(8) "courseid" [2]=> string(10) "categoryid" [3]=> string(8) "itemname"
    [4]=> string(8) "itemtype" [5]=> string(10) "itemmodule" [6]=> string(12) "iteminstance" [7]=> string(10) "itemnumber" [8]=> string(8) "iteminfo" [9]=> string(8) "idnumber" 
    [10]=> string(11) "calculation" [11]=> string(9) "gradetype" [12]=> string(8) "grademax" [13]=> string(8) "grademin" [14]=> string(7) "scaleid" [15]=> string(9) "outcomeid" 
    [16]=> string(9) "gradepass" [17]=> string(10) "multfactor" [18]=> string(10) "plusfactor" [19]=> string(15) "aggregationcoef" [20]=> string(9) "sortorder" [21]=> string(7) "display" 
    [22]=> string(8) "decimals" [23]=> string(6) "hidden" [24]=> string(6) "locked" [25]=> string(8) "locktime" [26]=> string(11) "needsupdate" [27]=> string(11) "timecreated" 
    [28]=> string(12) "timemodified" } ["courseid"]=> string(2) "15" ["categoryid"]=> string(2) "17" ["item_category"]=> NULL ["parent_category"]=> NULL 
    ["itemname"]=> string(96) "Тест на повышении категории по кондитерским товарам" ["itemtype"]=> string(3) "mod" ["itemmodule"]=> string(4) "quiz" ["iteminstance"]=> string(2) "40" 
    ["itemnumber"]=> string(1) "0" ["iteminfo"]=> NULL ["idnumber"]=> string(0) "" ["calculation"]=> NULL ["calculation_normalized"]=> NULL ["formula"]=> NULL ["gradetype"]=> string(1) "1" 
    ["grademax"]=> string(8) "13.00000" ["grademin"]=> string(7) "0.00000" ["scaleid"]=> NULL ["scale"]=> NULL ["outcomeid"]=> NULL ["outcome"]=> NULL ["gradepass"]=> string(7) "0.00000" 
    ["multfactor"]=> string(7) "1.00000" ["plusfactor"]=> string(7) "0.00000" ["aggregationcoef"]=> string(7) "0.00000" ["sortorder"]=> string(1) "2" ["display"]=> string(1) "0" 
    ["decimals"]=> NULL ["hidden"]=> string(1) "0" ["locked"]=> string(1) "0" ["locktime"]=> string(1) "0" ["needsupdate"]=> string(1) "0" ["dependson_cache"]=> NULL 
    ["optional_fields"]=> array(0) { } 
    ["id"]=> int(81) ["timecreated"]=> string(10) "1299760453" ["timemodified"]=> string(10) "1301473072" 
} 
["userid"]=> string(4) "1068" ["rawgrade"]=> string(7) "1.00000" 
["rawgrademax"]=> string(8) "13.00000" ["rawgrademin"]=> string(7) "0.00000" ["rawscaleid"]=> NULL ["usermodified"]=> string(4) "1068" 
["finalgrade"]=> string(7) "1.00000" ["hidden"]=> string(1) "0" ["locked"]=> string(1) "0" ["locktime"]=> string(1) "0" ["exported"]=> string(1) "0" 
["overridden"]=> string(1) "0" ["excluded"]=> string(1) "0" ["timecreated"]=> string(10) "1301487130" ["timemodified"]=> string(10) "1301487130" 
["id"]=> int(93) ["feedback"]=> NULL ["feedbackformat"]=> string(1) "0" ["information"]=> NULL ["informationformat"]=> string(1) "0" 
}*/ 
    
    
    
    
    //-------------------------------------------------
    // Обязательное переопределение абстрактных методов (их 6 шт.)
    
    /** Проверяет существование объекта
     * Проверяет существование в таблице записи с указанным id 
     * и возвращает true или false
     * @return bool
     */
    public function is_exists($id=null)
    {
        return true;
    }
    /** Создает объект и возвращает его id
     * @param mixed $obj - параметры объекта или null для параметров по умолчанию 
     * @return mixed
     */
    public function create($obj=null)
    {
        return true;
    }
    /** Возвращает шаблон нового объекта
     * @param mixed $obj - параметры объекта или null для параметров по умолчанию 
     * @return object
     */
     public function template($obj=null)
     {
         return true;
     }
     /** Возвращает информацию об объекте из БД
      * @access public
      * @return object объект типа параметр=>значение
      */
     public function get()
     {
         return true;
     }
     /** Обновляет информацию об объекте в БД
      * @access public
      * @param object $obj - объект с информацией 
      * @param bool $replace - false - надо обновить запись курс
      * true - записать новую информацию в курс
      * @return mixed id объекта или false
      */
     public function update($obj, $replace = false)
     {
         return true;
     }
     /** Удаляет объект из БД
      * @access public
      * @return bool true - удаление прошло успешно 
      * false в противном случае
      */
     public function delete()
     {
         return true;
     }
     
     // Абстрактные методы закончились
     //-------------------------------------------------
    
}

require_once(dirname(realpath(__FILE__))."/../../../../../config.php");

global $CFG;

// TODO просеять
require_once $CFG->libdir.'/gradelib.php';
require_once($CFG->libdir.'/tablelib.php');
require_once $CFG->dirroot.'/grade/lib.php';
require_once $CFG->dirroot.'/grade/report/grader/lib.php';
require_once($CFG->dirroot.'/grade/report/lib.php');
require_once $CFG->dirroot.'/grade/report/user/lib.php';

?>