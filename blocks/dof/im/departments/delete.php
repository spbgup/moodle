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
require_once('lib.php');
$DOF->modlib('nvg')->add_level($DOF->get_string('title'), $DOF->url_im('standard','/index.php', $addvars));
$DOF->modlib('nvg')->add_level($DOF->get_string('title', 'departments'), $DOF->url_im('departments','/index.php'),$addvars);
//id подразделения
$departmentid = required_param('departmentid', PARAM_INT);
$id = required_param('id', PARAM_INT);
$delete = optional_param('delete', 0, PARAM_BOOL);
// проверки
// не найдена персона
if ( ! $department  = $DOF->storage('departments')->get($id) )
{// вывод сообщения и ничего не делаем
    $errorlink = $DOF->url_im('departments','',$addvars);
    $DOF->print_error('notfound',$errorlink,$id,'im','departments');
}

// ссылки на подтверждение и непотдверждение сохранения приказа
$linkyes = $DOF->url_im('departments', '/delete.php?departmentid='.$departmentid.'&delete=1&id='.$id);
$linkno = $DOF->url_im('departments', '/list.php',$addvars);
//проверка на ЕСЛИ уже удалено
if ( $department->status == 'deleted' )
{// возврат на список
    redirect($linkno);
}

//проверка прав доступа
$DOF->storage('departments')->require_access('delete');

if ( $delete )
{// Меняем статус подразделения
    //Ищем все подразделения, под удаляемым
    if ( $deps = $DOF->storage('departments')->get_records(array('leaddepid' => $id)) )
    {// если есть дочерние перекинем их на родителя
        foreach($deps as $dep)
        {
            $obj = new object;
            $obj->id = $dep->id; 
            $obj->leaddepid = $department->leaddepid;
            $DOF->storage('departments')->update($obj);
        }
    }
    $obj = new object;
    $obj->id = $id; 
    $obj->status = 'deleted';
    $DOF->storage('departments')->update($obj);
    redirect($linkno);
}else
{
    //вывод на экран
    //печать шапки страницы
    $DOF->modlib('nvg')->add_level($DOF->get_string('deletedepartment', 'departments'),
                                   $DOF->url_im('departments','/delete.php'));
    $DOF->modlib('nvg')->print_header(NVG_MODE_PAGE);
    // вывод названия удаляемого элемента
    echo '<div align="center" style="color:red;font-size:25px;">' . $department->name . '</div><br>';
    // спросим об удалении
    $DOF->modlib('widgets')->notice_yesno($DOF->get_string('confirmation_delete_department','departments'), $linkyes, $linkno);
    //печать подвала
    $DOF->modlib('nvg')->print_footer(NVG_MODE_PAGE);
}

?>