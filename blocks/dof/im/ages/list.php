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
// создаем объект, который будет содержать будущие условия выборки
$conds = new Object();
// id учебного подразделения в таблице departmrnts
//выводятся периоды с любым departmentid, если ничего не передано
$conds->departmentid = optional_param('departmentid', null, PARAM_INT);
// статус учебного периода. Выводятся периоды 
// с любым статусом, если ничего не передано
$conds->status       = optional_param('status', '', PARAM_ALPHA);
//добавление уровня навигации
$DOF->modlib('nvg')->add_level($DOF->get_string('title', 'ages'), $DOF->url_im('ages','/list.php'), $addvars);
// ловим номер страницы, если его передали
// какое количество периодов выводить на экран
$limitnum = $DOF->modlib('widgets')->get_limitnum_bydefault();
$limitnum     = optional_param('limitnum', $limitnum, PARAM_INT);
// начиная с какого номера записи показывать ее
$limitfrom    = optional_param('limitfrom', '1', PARAM_INT);
//вывод на экран
//печать шапки страницы
$DOF->modlib('nvg')->print_header(NVG_MODE_PORTAL);
//проверяем доступ
$DOF->storage('ages')->require_access('view');

// подключаем класс для вывода страниц
$pages = $DOF->modlib('widgets')->pages_navigation('ages',null,$limitnum, $limitfrom);
//получаем список периодов
// массивы в PHP нумеруются с нуля, а наши страницы - с 1, 
//поэтому от стартового значения отнимем единицу.
$list = $DOF->storage('ages')->get_listing($conds,$pages->get_current_limitfrom()-1, 
                                      $pages->get_current_limitnum());
                                                                           
// получаем html-код таблицы с периодами
$ages = $DOF->im('ages')->showlist($list,$conds);
// ссылка на создание периода
if ( $DOF->storage('ages')->is_access('create') )
{// если есть право создавать период
    if ( $DOF->storage('config')->get_limitobject('ages',$conds->departmentid) )
    {
        $link = '<a href='.$DOF->url_im('ages','/edit.php',$conds).'>'.
        $DOF->get_string('newages', 'ages').'</a>';
        echo '<br>'.$link;
    }else 
    {
        $link =  '<span style="color:silver;">'.$DOF->get_string('newages', 'ages').
        	' ('.$DOF->get_string('limit_message','ages').')</span>';
        echo '<br>'.$link; 
    }    
}
if ( $DOF->im('cstreams')->is_access('viewcurriculum') )
{// если есть право просмотра учебный план учащихся
    $link = '<a href='.$DOF->url_im('cstreams','/by_groups.php',$conds).'>'.
    $DOF->get_string('participants_cstreams', 'ages').'</a>';
    echo '<br>'.$link.'<br>';    
}
if ( ! $ages )
{// не найдено ни одного учебного периода
    print('<p align="center">(<i>'.$DOF->get_string('no_ages_found', 'ages').'</i>)</p>');
	//print_error($DOF->get_string('notfoundage','ages'));
}else
{
    // выводим таблицу с учебными периодами
    echo '<br>'.$ages;
    
    // помещаем в массив все параметры страницы, чтобы навигация по списку проходила корректно
    $vars = array('limitnum'     => $pages->get_current_limitnum(),
                  'limitfrom'    => $pages->get_current_limitfrom());
    // добавляем все необходимые условия фильтрации
    $vars = array_merge($vars, (array)$conds);
    // составим запрос для извлечения количества записей
    $selectlisting = $DOF->storage('ages')->get_select_listing($conds);
    // посчитаем общее количество записей, которые нужно извлечь
    $pages->count = $DOF->storage('ages')->count_records_select($selectlisting);
    // выводим строку со списком страниц
    $pagesstring = $pages->get_navpages_list('/list.php', $vars);
    if ( $pagesstring )
    {// если выводим строку со списком страниц, то выводим и надпись "страницы"
        print '<p align="center"><b>'.$DOF->get_string('pages', 'ages').':</b><br>'.
        $pagesstring.'</p>';
    }
}
//печать подвала
$DOF->modlib('nvg')->print_footer(NVG_MODE_PORTAL);

?>