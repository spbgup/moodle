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
/*
 * Хранилище для описания истории подписок в учебных периодах
 */
class dof_storage_learninghistory extends dof_storage
{
    /**
     * @var dof_control
     */
    protected $dof;
    // **********************************************
    // Методы, предусмотренные интерфейсом plugin
    // **********************************************

    /** Метод, реализующий обновление плагина в системе
     * Создает или модифицирует существующие таблицы в БД
     * @param string $old_version - версия установленного в системе плагина
     * @return boolean
     * @access public
     */
    public function upgrade($oldversion)
    {
        return true;
    }
    /** Возвращает версию установленного плагина
     * @return string
     * @access public
     */
    public function version()
    {
		return 2012042500;
    }
    /** Возвращает версии интерфейса Деканата, 
     * с которыми этот плагин может работать
     * @return string
     * @access public
     */
    public function compat_dof()
    {
        return 'aquarium';
    }

    /** Возвращает версии стандарта плагина этого типа, 
     * которым этот плагин соответствует
     * @return string
     * @access public
     */
    public function compat()
    {
        return 'paradusefish';
    }
    
    /** Возвращает тип плагина
     * @return string 
     * @access public
     */
    public function type()
    {
        return 'storage';
    }
    /** Возвращает короткое имя плагина
     * Оно должно быть уникально среди плагинов этого типа
     * @return string
     * @access public
     */
    public function code()
    {
        return 'learninghistory';
    }
    /** Возвращает список плагинов, 
     * без которых этот плагин работать не может
     * @return array
     * @access public
     */
    public function need_plugins()
    {
		return array();
    }
    /** Список обрабатываемых плагином событий 
     * @return array - array(array('plugintype'=>..,'plugincode'=>..,'eventcode'=>..),...)
     * @access public
     */
    public function list_catch_events()
    {
       
        return array(
        //слушаем создание и изменение подписки на программу
                     array('plugintype'=>'storage', 'plugincode'=>'cpassed', 'eventcode'=>'insert'),
                     array('plugintype'=>'storage', 'plugincode'=>'cpassed', 'eventcode'=>'update'),
                     array('plugintype'=>'storage', 'plugincode'=>'cpassed', 'eventcode'=>'delete')
                     );
    }
    /** Требуется ли запуск cron в плагине
     * @return bool
     * @access public
     */
    public function is_cron()
    {
        return false;
    }
    
    /** Проверяет полномочия на совершение действий
     * @param string $do - идентификатор действия, которое должно быть совершено
     * @param int $id_obj - идентификатор экземпляра объекта, 
     * по отношению к которому это действие должно быть применено
     * @param int $user_id - идентификатор пользователя, полномочия которого проверяются
     * @return bool true - можно выполнить указанное действие по отношению к выбранному объекту
     * false - доступ запрещен
     * @access public
     */
    public function is_access($do, $objid = NULL, $userid = NULL)
    {
        // Используем функционал из $DOFFICE
        return $this->dof->is_access($do, NULL, $user_id);
    }
    /** Обработать событие
     * @param string $gentype - тип модуля, сгенерировавшего событие
     * @param string $gencode - код модуля, сгенерировавшего событие
     * @param string $eventcode - код задания
     * @param int $intvar - дополнительный параметр 
     * @param mixed $mixedvar - дополнительные параметры
     * @return bool - true в случае выполнения без ошибок
     * @access public
     */
    public function catch_event($gentype,$gencode,$eventcode,$intvar,$mixedvar)
    {
        if ( $gentype === 'storage' AND $gencode === 'cpassed' )
        {
            switch ($eventcode)
            {
                case 'insert': return $this->add($intvar);
                case 'update': return $this->add($intvar);
                case 'delete': 
            }
        }
        return true;
    }
    /** Запустить обработку периодических процессов
     * @param int $loan - нагрузка (1 - только срочные, 2 - нормальный режим, 3 - ресурсоемкие операции)
     * @param int $messages - количество отображаемых сообщений (0 - не выводить,1 - статистика,
     *  2 - индикатор, 3 - детальная диагностика)
     * @return bool - true в случае выполнения без ошибок
     * @access public
     */
    public function cron($loan,$messages)
    {
        return true;
    }
    /** Обработать задание, отложенное ранее в связи с его длительностью
     * @param string $code - код задания
     * @param int $intvar - дополнительный параметр 
     * @param mixed $mixedvar - дополнительные параметры
     * @return bool - true в случае выполнения без ошибок
     * @access public
     */
    public function todo($code,$intvar,$mixedvar)
    {
        return true;
    }
    /** Конструктор
     * @param dof_control $dof - объект с методами ядра деканата
     * @access public
     */
    public function __construct($dof)
    {
        // Сохраняем ссылку на DOF, чтоб вызывать его через $this->dof
        $this->dof = $dof;
    }

    /** Возвращает название таблицы без префикса (mdl_)
     * @return text
     * @access public
     */
    public function tablename()
    {
        // Имя таблицы, с которой работаем
        return 'block_dof_s_learninghistory';
    }
    
    // **********************************************
    //              Собственные методы
    // **********************************************
    
    /** Получить первую запись на момент начала обучения 
     * 
     * @return object|bool - объект из таблицы learninghistory, или false
     * @param int $programmsbcid - id подписки ученика на учебную программу (таблица programmsbcs), 
     * по которой запрашивается история
     */
    public function get_first_learning_data($programmsbcid) 
    {
    	$list = $this->get_records(array('programmsbcid'=>$programmsbcid), 'changedate ASC, id ASC', '*', 0, 1);
        if ( is_array($list) )
        {// если получили массив - значит вернем его единственный элемент
            return current($list);
        }else
        {// если нет - значит ничего не нашлось
            return false;
        }
    }
    
    /** Получить текущую информацию об учебных подписках и периодах
     * 
     * @return object - объект из таблицы learninghistory, или false
     * @param int $programmsbcid - id подписки ученика на учебную программу (таблица programmsbcs), 
     * по которой запрашивается история
     */
    public function get_actual_learning_data($programmsbcid)
    {
        $list = $this->get_records(array('programmsbcid'=>$programmsbcid), 'changedate DESC, id DESC', '*', 0, 1);
        if ( is_array($list) )
        {// если получили массив - значит вернем его единственный элемент
            return current($list);
        }else
        {// если нет - значит ничего не нашлось
            return false;
        }
    }
    
    /** Получить историю изменений подписок по времени 
     * 
     * @return array - массив объектов из таблицы learninghistory, или false
     * @param int $programmsbcid - id подписки ученика на учебную программу (таблица programmsbcs), 
     * по которой запрашивается история 
     * @param int $timefrom[optional] - начало временного периода, 
     * за который запрашивается история изменения подписок (если указано) 
     * @param int $timeto[optional] - конец временного периода, 
     * за который запрашивается история изменения подписок (если указано) 
     */
    public function get_history($programmsbcid, $timefrom=null, $timeto=null)
    {
        $select = 'programmsbcid = '.$programmsbcid;
        if ( $timefrom )
        {
            $select .= ' AND changedate >= '.$timefrom;
        }
        if ( $timeto )
        {
            $select .= ' AND changedate <= '.$timeto;
        }
        return $this->get_records_select($select, null,'changedate ASC, id ASC');
    }
    
    
    /** Вернуть все учебные периоды, в которых проходила указанная учебная программа
     * (без повторений)
     * @return array массив записей из таблицы learninghistory
     * @param int $programmsbcid - id подписки на учебную программу в таблице programmsbcs 
     */
    function get_subscribe_ages($programmsbcid)
    {
        $result = array();
        /*
        $field = 'DISTINCT ageid,agenum    ageid,agenum,programmsbcid, changedate, orderid';
        $select = 'programmsbcid='.$programmsbcid;
        $result = $this->get_list_select($select, '', $field);
        return $result;*/
        // получаем все записи для подписки
        $records = $this->get_records(array('programmsbcid'=>$programmsbcid));
        $dubles = array();
        if ( $records )
        {// если записи для такой подписки есть
            foreach ( $records as $record )
            {// перебираем все полученные записи
                $key = $record->ageid.'-'.$record->agenum;
                if ( ! in_array($key, $dubles) )
                {// если запись с таким ageid и agenum еще нам не встречалась - то добавим ее в итоговый результат
                    $dubles[]            = $key;
                    $result[$record->id] = $record;
                }
            }
        }
        return $result;
    }
    
    /**Добавляет запись в таблицу
     * 
     * @param $cpassed - изучаемый или пройденный курс
     * @return 
     */
    public function add($cpassed)
    {
        if ( ! is_object($cpassed) )
        {//если передан не курс, а его id
            $cpassed = $this->dof->storage('cpassed')->get($cpassed);
            if ( ! $cpassed )
            {//не получили курс
                return false;
            }
        }
        if ( $cpassed->status != 'active' )
        {// если cpassed не активен - создавать learninghistory нельзя
            // вернем что все в порядке 
            return true;
        }
        $programmsbc = $this->dof->storage('programmsbcs')->get($cpassed->programmsbcid);
        if ( ! $programmsbc )
        {//не получили подписку на программу
            return false;
        }
        //формируем объект для вставки
        $forinsert = new object();
        $forinsert->programmsbcid = $programmsbc->id;
        $forinsert->agenum = $programmsbc->agenum;
        $forinsert->changedate = time();
        //$forinsert->orderid =
        $forinsert->ageid = $cpassed->ageid;
        if ( $this->is_exists(array('programmsbcid'=>$forinsert->programmsbcid, 
                                     'agenum'=>$forinsert->agenum, 'ageid'=>$forinsert->ageid)) )
        {// если такая история уже есть - все в порядке
            return true;
        }
        return $this->insert($forinsert);
    }
}
