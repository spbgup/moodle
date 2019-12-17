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
//загрузка библиотек верхнего уровня
require_once('lib.php');


// id предмето-потока 
$csid = required_param('csid',PARAM_INT);
// id элемента учебного события, оценки для которого редактируются в данный момент
$dateid  = optional_param('planid', 0, PARAM_INT);
$eventid = optional_param('eventid', 0, PARAM_INT);
if ( ! $cstream = $DOF->storage('cstreams')->get($csid) )
{// не удалось найти поток
	$DOF->print_error($DOF->get_string('not_found_cstream', 'journal', $csid));
}

// подключаем библеиотеки и стили
$DOF->modlib('widgets')->js_init('show_hide');
$DOF->modlib('nvg')->add_css('im', 'journal', '/styles.css');

//вывод на экран
//печать шапки страницы
$DOF->modlib('nvg')->print_header(NVG_MODE_PAGE);
$editjournal = new dof_im_journal_tablegrades($DOF, $csid);
// проверка прав доступа'
if ( ! $DOF->im('journal')->is_access('view_journal/own', $csid) )
{// если нет права видеть журналы, проверим, есть ли право видеть журналы вообще
    $DOF->im('journal')->require_access('view_journal', $csid);
}

$msg = ''; //для вывода сообщений

if ( ($DOF->storage('schevents')->is_access('create')) OR 
     ($DOF->storage('schevents')->is_access('create/in_own_journal',$csid)) ) 
{//покажем ссылку на создание урока только тому, кто имеет на это право
    $msg .= '<br/><br/><a href ='.$DOF->url_im('journal','/group_journal/topic.php?planid=0&eventid=0&csid='.$csid,$addvars).
        '>'.$DOF->get_string('new_lesson','journal').'</a>';
}

// покажем ссылку на выставление итоговой оценки,
// кто имеет на это право

if (  //  право завершать cstream до истечения срока cstream
      (($DOF->im('journal')->is_access('complete_cstream_before_enddate',$csid) AND $cstream->enddate > time()) OR
      // право завершать cstream после истечения срока cstream (пересдача)
      ($DOF->im('journal')->is_access('complete_cstream_after_enddate', $csid) AND $cstream->enddate < time()) OR
      // право  Закрывать итоговую ведомость до завершения cstream 
      // (под завершением имеется в виду cstream в конечном статусе)
      ($DOF->im('journal')->is_access('close_journal_before_closing_cstream', $csid) AND $cstream->status != 'completed' ) OR
      // право Закрывать итоговую ведомость до истечения даты cstream
      ($DOF->im('journal')->is_access('close_journal_before_cstream_enddate', $csid) AND $cstream->enddate > time() ) OR
      //  право Закрывать итоговую ведомость после истечения даты cstream, но до завершения cstream
      ($DOF->im('journal')->is_access('close_journal_after_active_cstream_enddate', $csid) 
          AND $cstream->status != 'completed' AND time() > $cstream->enddate )) 
      AND 
      ( $DOF->storage('cpassed')->is_access('edit:grade/own',$csid) OR 
              $DOF->storage('cpassed')->is_access('edit:grade/auto',$csid) OR 
        $DOF->storage('cpassed')->is_access('edit:grade',$csid) )
   )
{
    $msg .= '<br/><br/><a href ='.$DOF->url_im('journal','/itog_grades/edit.php?id='.$csid,$addvars).
        '>'.$DOF->get_string('itog_grades','journal').'</a>';
}



if ( $DOF->im('plans')->is_access('viewthemeplan',$csid) OR $DOF->im('plans')->is_access('viewthemeplan/my',$csid)  )
{//покажем ссылку на редактирование темпланов,
    //кто имеет на это право
    $msg .= '<br/><br/><a href ='.$DOF->url_im('plans','/themeplan/viewthemeplan.php?linktype=cstreams&linkid='.$csid,$addvars).
        '>'.$DOF->get_string('view_plancstream','journal').'</a>';
    $msg .= '<br/><br/><a href ='.$DOF->url_im('plans','/themeplan/viewthemeplan.php?linktype=plan&linkid='.$csid,$addvars).
        '>'.$DOF->get_string('view_iutp','journal').'</a>';
}

//выводим информацию о потоке';
print '<table style="border-style:none;width:100%;"  cellpadding="10">';
print '<tr><td style="width:50%;">';
print $DOF->im('journal')->get_cstream_info($csid);
print '</td><td style="width:50%;text-align:left;vertical-align:bottom;">';
print '<div>'.$msg.'</div>';
print '</td></tr></table>';

//Выводим разворот журнала
print '<table style="border-style:none;width:100%;vertical-align:top;" cellpadding="5">';
print '<tr><td style="width:50%;vertical-align:top;">';
//включаем полосу прокрутки таблицы правой страницы
//print '<div style="height: 300px; width: 450px; overflow: scroll;">';
//выводим левую страницу журнала
//print_heading($DOF->get_string('grades_table','journal'));
//подключаем таблицу оценок
$editjournal->print_texttable($dateid, $eventid);
//print '</div>';//выключаем полосу прокрутки
print '</td><td style="width:50%;max-width:50%;vertical-align:top;">';
//включаем полосу прокрутки таблицы правой страницы
print '<div style="overflow-x: scroll;">';
//выводим правую страницу журнала
//print_heading($DOF->get_string('plans_table','journal'));
//подключаем таблицу тематического планирования
include('page_right.php');
print '</div>';//выключаем полосу прокрутки
print '</td></tr></table>';

//печать подвала
$DOF->modlib('nvg')->print_footer(NVG_MODE_PAGE);


?>