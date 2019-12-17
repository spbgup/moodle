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

// Подключаем библиотеки верхнего уровня. Одновременно с этим moodle проверяет cookies
// и если пользователь авторизован - узнает его, а если нет - то заходит гостем
require_once("lib.php");
// @todo нужно будет сделать обертку для этой мудловской функции, но пока непонятно куда ее поместить
require_login(0, true);

// тип запроса. Что именно будет нужно делать?
// возможные варианты: autocomplete, savefireld
$type       = required_param('type', PARAM_ALPHANUM);
// тип плагина, к которому выполняется запрос
$plugincode = required_param('plugincode', PARAM_ALPHANUM);
// код плагина к которому выполняется запрос
$plugintype = required_param('plugintype', PARAM_ALPHANUM);
// уникальное имя запроса внутри плагина. Это может быть название класса формы,
// или просто произвольное название, по которому плагин определяет что ему делать с этим запросом
$querytype      = required_param('querytype', PARAM_TEXT);
// ключ сессии
$sesskey    = required_param('sesskey', PARAM_ALPHANUM);
if ( ! confirm_sesskey($sesskey) )
{// при несовпадении ключа сессии ничего не делаем
    // @todo возможно следует вернуть ошибку в формате json 
    die('sesskey not confirmed');
}
// id объекта, который редактируется или сохраняется
$objectid = optional_param('objectid', 0, PARAM_INT);

// Данные в формате JSON. Не производим проверку здесь - она будет производится в том плагине, 
// которому адресованы данные

//$data = optional_param('data', null, PARAM_RAW);
if ( isset($_GET['data']) )
{
    $data = $_GET['data'];
}elseif(isset($_POST['data']))
{
    $data = $_POST['data'];
}else
{
    $data = null;
}
// подразделение
$depid = optional_param('departmentid', 0, PARAM_INT);


switch ( $type )
{
    // получаем данные для автозаполнения
    case 'ajaxselect':
    case 'autocomplete': 
        $result = $DOF->modlib('widgets')->get_list_autocomplete($plugintype, $plugincode, $querytype, $depid, $data, $objectid);
        // кодируем ответ обратно в json и отправляем
        echo json_encode($result);
    break;
    // inline-редактирование одного поля
    case 'savefield': 
        echo $DOF->modlib('widgets')->save_ifield($plugintype, $plugincode, $querytype, $objectid, $data);
    break;
    // получение значения для inline-редактирования одного поля 
    case 'loadfield':
        echo $DOF->modlib('widgets')->load_ifield($plugintype, $plugincode, $querytype, $objectid, $data);
    break;
}
?>