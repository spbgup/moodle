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


// Ищем конфигурационный файл MOODLE
if ( ! file_exists(dirname(realpath(__FILE__)).'/../../config.php') )
{
    header('Location: /install.php');
    exit();
}
// Подключаем конфигурационный файл MOODLE
require_once(dirname(realpath(__FILE__)).'/../../config.php');
global $CFG;

// Загружаем собственные библиотеки
include_once($CFG->dirroot.'/blocks/dof/lib/utils.php');
include_once($CFG->dirroot.'/blocks/dof/lib/dof.php');
include_once($CFG->dirroot.'/blocks/dof/lib/plugin.php');
include_once($CFG->dirroot.'/blocks/dof/lib/im.php');
include_once($CFG->dirroot.'/blocks/dof/lib/modlib.php');
include_once($CFG->dirroot.'/blocks/dof/lib/storage.php');
include_once($CFG->dirroot.'/blocks/dof/lib/storage_base.php');
include_once($CFG->dirroot.'/blocks/dof/lib/sync.php');
include_once($CFG->dirroot.'/blocks/dof/lib/workflow.php');
include_once($CFG->dirroot.'/blocks/dof/lib/events.php');

// Создаем объект контроллера
global $DOF;
$DOF = new dof_control($CFG);



?>