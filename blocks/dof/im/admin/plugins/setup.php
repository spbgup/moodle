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

require_once('lib.php');
$DOF->modlib('nvg')->add_level($DOF->get_string('plugin_setup', 'admin'), $DOF->url_im('admin', '/plugins/setup.php'));
// Протоколируем событие
// $DOF->add_to_log('im','admin','plugin_setup',$DOF->modlib('nvg')->get_url(), '');
$DOF->modlib('nvg')->print_header(NVG_MODE_PAGE);
echo "<pre>";
$DOF->plugin_setup();
echo "</pre>";
$DOF->modlib('nvg')->print_footer(NVG_MODE_PAGE);

?>