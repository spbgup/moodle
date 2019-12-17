<?PHP
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
require_once('form.php');

$DOF->modlib('nvg')->add_level($DOF->get_string('listpersons', 'persons'), $DOF->url_im('persons','/list.php',$addvars));
$DOF->modlib('nvg')->add_level($DOF->get_string('edit_time_zone', 'persons'), $DOF->url_im('persons','/edit_timezone.php',$addvars));

$flag = optional_param('flag',null, PARAM_BOOL);
//вывод на экран
//печать шапки страницы
$DOF->modlib('nvg')->print_header(NVG_MODE_PORTAL);

$customdata = new object;
$customdata->dof = $DOF;
$customdata->depid = $addvars['departmentid'];
$form = new dof_im_persons_edit_timezone($DOF->url_im('persons','/edit_timezone.php',$addvars),$customdata);

$result = $form->process($addvars);

echo $result;

$form->display();

//печать подвала
$DOF->modlib('nvg')->print_footer(NVG_MODE_PORTAL);

?>