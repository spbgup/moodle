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
 * Отображение списка объектов. 
 * Если соответствующие поля есть в справочнике. 
 * страница принимает параметры departmentid, status, 
 * по необходимости, могут быть другие, 
 * специфичные для справочника. Если они переданы, 
 * то выводится не весь список, а только его часть, 
 * удовлетворяющая условию. 
 * Параметры limitfrom и limitnum предназначены для ограничения количества 
 * выводимых записей. Если limitnum задан, то внизу выводится указатель страниц. 
 * Сам указатель страниц должен быть реализован в виде метода в плагине 
 * modlib widgets, которому передаются значения $code (код плагина im для ссылки), 
 * $adds, $vars, $limitfrom, $limitnum и $count, 
 * на основании которых возвращается html-код указателя. 
 * Ссылки генерируются с помощью $DOF->url_im(). 
 * Если для этого типа записей предусмотрен поиск, 
 * то форма поиска отображается над списком. 
 * Для результатов поиска действуют те же фильтры, что и для вывода списка. 
 */

// Подключаем библиотеки
require_once('lib.php');
require_once('form.php');
$DOF->modlib('nvg')->add_level($DOF->get_string('title'), $DOF->url_im('standard','/index.php', $addvars));
$DOF->modlib('nvg')->add_level($DOF->get_string('title', 'departments'), $DOF->url_im('departments','/index.php',$addvars));
$conds = new object;
// id подразделения в таблице departments

// статус подразделения. Выводятся подразделения
// с любым статусом, если ничего не передано
$conds->status       = optional_param('status', null, PARAM_ALPHA);
// вышестоящее подразделение
$conds->leaddepid    = optional_param('leaddepid', $addvars['departmentid'], PARAM_TEXT);
// ловим номер страницы, если его передали
// какое количество подразделений выводить на экран
// @todo решить, что же все-таки здесь ставить
$limitnum = $DOF->modlib('widgets')->get_limitnum_bydefault();
$limitnum     = optional_param('limitnum', $limitnum, PARAM_INT);
// начиная с какого номера записи показывать ее
$limitfrom    = optional_param('limitfrom', '1', PARAM_INT);
if ( $conds->leaddepid === '0' )
{// добавим соответствующий уровень навигации
    $head = $DOF->get_string('departmentstoplevel', 'departments');
    $DOF->modlib('nvg')->add_level($head, $DOF->url_im('departments','/list.php',$addvars));
}elseif ( ! empty($conds->leaddepid) AND $a =$DOF->storage('departments')->get($conds->leaddepid) )
{// добавим соответствующий уровень навигации
    $head = $DOF->storage('departments')->get_field($conds->leaddepid,'name');
    $DOF->modlib('nvg')->add_level($head, $DOF->url_im('departments','/list.php',$addvars));
    // сформируем массив на тот случай если у подчиненных есть свои подчиненные
    $leaddep = array_keys($DOF->storage('departments')->departments_list_subordinated($conds->leaddepid));
    // добавим сам подчененные массив в массив
    $leaddep[] = $conds->leaddepid;
    $conds->leaddepid = $leaddep;
}
/*elseif ( ! is_null($conds->leaddepid) )
{// каказябра недопустима
    $conds->leaddepid = -1;
}
*/
//вывод на экран
//печать шапки страницы
$DOF->modlib('nvg')->print_header(NVG_MODE_PORTAL);
//проверяем доступ
$DOF->storage('departments')->require_access('view', $addvars['departmentid']);

// подключаем класс для вывода страниц
$pages = $DOF->modlib('widgets')->pages_navigation('departments',null,$limitnum, $limitfrom);
//получаем список подразделений
// массивы в PHP нумеруются с нуля, а наши страницы - с 1, 
//поэтому от стартового значения отнимем единицу.
$list = $DOF->im('departments')->get_listing($conds, $pages->get_current_limitfrom()-1, 
                                      $pages->get_current_limitnum());
                                                                           
// получаем html-код таблицы с подразделениями
$departments = $DOF->im('departments')->showlist($list, $depid);
// покажем ссылку на создание подразделения
if ( $DOF->storage('departments')->is_access('create') )
{// если есть такие права
    if ( $DOF->storage('config')->get_limitobject('departments',$addvars['departmentid']) )
    {
        $link = '<a href="'.$DOF->url_im('departments','/edit.php?departmentid='.$depid).'&id=0">'.
        $DOF->get_string('newdepartment', 'departments').'</a>';
        echo '<br>'.$link.'<br>' ;
    }else 
    {
        $link =  '<span style="color:silver;">'.$DOF->get_string('newdepartment', 'departments').
        	' ('.$DOF->get_string('limit_message','departments').')</span>';
        echo '<br>'.$link.'<br>'; 
    }     
}

if ( ! $departments)
{// не найдено ни одного отдела
    if ( isset($head) )
    {//  у отдела просто нет подчиненных - сообщим об этом
        print('<p align="center"><i>'.$head.'. '.$DOF->get_string('nonesubordinated', 'departments').'</i></p>');
    }else
    {// отдела не найдено вообще
        print('<p align="center">(<i>'.$DOF->get_string('no_department_found', 'departments').'</i>)</p>');
    }
}else
{   
    if ( isset($head) )
    {
        print('<p align="center"><b>'.$head.'</b></p>');
    }
    // выводим таблицу с подразделениями
    print( '<br>'.$departments );
    
    // помещаем в массив все параметры страницы, чтобы навигация по списку проходила корректно
    $vars = array('limitnum'     => $pages->get_current_limitnum(),
                  'limitfrom'    => $pages->get_current_limitfrom(),
                  'departmentid' => $addvars['departmentid']);
    $vars = array_merge($vars, (array)$conds);  
    // составим запрос для извлечения количества записей
    $selectlisting = $DOF->im('departments')->get_select_listing($conds);
    // посчитаем общее количество записей, которые нужно извлечь
    $pages->count = $DOF->storage('departments')->count_records_select($selectlisting);
    // выводим строку со списком страниц
    $pagesstring = $pages->get_navpages_list('/list.php', $vars);
    if ( $pagesstring )
    {// если выводим строку со списком страниц, то выводим и надпись "страницы"
        print '<p align="center"><b>'.$DOF->get_string('pages', 'departments').':</b><br>'.
        $pagesstring.'</p>';
    }
}
            
//печать подвала
$DOF->modlib('nvg')->print_footer(NVG_MODE_PORTAL);

?>