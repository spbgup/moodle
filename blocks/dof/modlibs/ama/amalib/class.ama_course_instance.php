<?php

//Все в этом файле написано на php5.
//Проверяем совместимость с ПО сервера
if ( 0 > version_compare(PHP_VERSION, '5') )
{
    die('This file was generated for PHP 5');
}

/** Подключаем класс для работы с секцией
 */
require_once('class.ama_course_section.php');

/** Класс для работы с экземлярами модулей
 */
class ama_course_instance
{
    protected $id = 0;//id экземпляра модуля 
    /**
     * @access public
     * @param int $id - id экземпляра модуля
     * @return void
     */
    public function __construct($id)
    {
    }

    /** Удаляет экземпляр модуля из системы 
     * @access public
     * @return bool true - удаление прошло успешно
     * false - в иных случаях
     */
    public function delete()
    {
        $returnvalue = (bool) false;

        return (bool) $returnvalue;
    }

    /** Сохраняет экземпляр модуля в БД
     * @access public
     * @param string $name - название экземпляра модуля
     * @param array $options - информация, наполняющая экземпляр модуля
     * @return int id модуля в БД или false
     */
    public function save($name, $options = NULL)
    {
        $returnvalue = (int) 0;

        return (int) $returnvalue;
    }

    /** Возвращает информацию "по умолчанию" для наполнения модуля
     * @access public
     * @param array $options - если параметры, заменяющие значения по умолчанию
     * @return array информация, наполняющая экземпляр модуля
     */
    public function template($obj=null)
    {
        $returnvalue = array();

        return (array) $returnvalue;
    }

}

?>