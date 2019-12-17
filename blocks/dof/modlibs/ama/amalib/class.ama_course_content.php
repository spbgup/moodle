<?php


//Все в этом файле написано на php5.
//Проверяем совместимость с ПО сервера
if ( 0 > version_compare(PHP_VERSION, '5') )
{
    die('This file was generated for PHP 5');
}
//подключаем класс для работы с курсом
require_once('class.ama_course.php');

/** Класс для работы с содержанием курса
 * @access public
 */
interface ama_course_content
{
//    protected $courseid = 0; //id курса
    /** Конструктор класса - создает экземпляр от этого класса
     * @access public
     * @param int $courseid - id курса 
     * @return void
     */
    public function __construct($courseid);

}

?>