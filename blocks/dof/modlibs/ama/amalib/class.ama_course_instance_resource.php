<?php

//Все в этом файле написано на php5.
//Проверяем совместимость с ПО сервера
if ( 0 > version_compare(PHP_VERSION, '5') )
{
    die('This file was generated for PHP 5');
}

//подключаем библиотеку для работы с экземплярами модулей
require_once('class.ama_course_instance.php');

/** Класс для работы с экземплярами модулей типа ресурс
 * @access public
 */
class ama_course_instance_resource extends ama_course_instance
{
}

?>