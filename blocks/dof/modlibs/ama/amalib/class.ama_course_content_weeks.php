<?php

//Все в этом файле написано на php5.
//Проверяем совместимость с ПО сервера
if ( 0 > version_compare(PHP_VERSION, '5') )
{
    die('This file was generated for PHP 5');
}
//подключаем библиотеку для работы с курсом формата структура
require_once('class.ama_course_content_topics.php');
/** Класс для работы с курсом формата календарь
 * @access public
 */
class ama_course_content_weeks
    extends ama_course_content_topics
{

}

?>