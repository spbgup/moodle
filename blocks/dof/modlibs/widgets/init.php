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
global $DOF;
// подключаем интерфейс настроек, чтобы в плагине работали настройки
require_once($DOF->plugin_path('storage','config','/config_default.php'));
/** Класс стандартных функций интерфейса
 * 
 */
class dof_modlib_widgets implements dof_plugin_modlib, dof_storage_config_interface
{
    /**
     * @var dof_control
     */
    protected $dof;
    // **********************************************
    // Методы, предусмотренные интерфейсом plugin
    // **********************************************
    /** Метод, реализующий инсталяцию плагина в систему
     * Создает или модифицирует существующие таблицы в БД
     * и заполняет их начальными значениями
     * @return boolean
     * Может надо возвращать массив с названиями таблиц и результатами их создания?
     * чтобы потом можно было распечатать сообщения о результатах обновления
     * @access public
     */
    public function install()
    {
        return true;
    }
    /** Метод, реализующий обновление плагина в системе
     * Создает или модифицирует существующие таблицы в БД
     * @param string $old_version - версия установленного в системе плагина
     * @return boolean
     * Может надо возвращать массив с названиями таблиц и результатами их создания/изменения?
     * чтобы потом можно было распечатать сообщения о результатах обновления
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
        return 2012050500;
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
        return 'neon';
    }
    
    /** Возвращает тип плагина
     * @return string 
     * @access public
     */
    public function type()
    {
        return 'modlib';
    }
    /** Возвращает короткое имя плагина
     * Оно должно быть уникально среди плагинов этого типа
     * @return string
     * @access public
     */
    public function code()
    {
        return 'widgets';
    }
    /** Возвращает список плагинов, 
     * без которых этот плагин работать не может
     * @return array
     * @access public
     */
    public function need_plugins()
    {
        return array('storage' => array('config' => 2011040500));
    }
    /** Определить, возможна ли установка плагина в текущий момент
     * Эта функция одинакова абсолютно для всех плагинов и не содержит в себе каких-либо зависимостей
     * @TODO УДАЛИТЬ эту функцию при рефакторинге. Вместо нее использовать наследование
     * от класса dof_modlib_base_plugin 
     * @see dof_modlib_base_plugin::is_setup_possible()
     * 
     * @param int $oldversion[optional] - старая версия плагина в базе (если плагин обновляется)
     *                                    или 0 если плагин устанавливается
     * 
     * @return bool 
     *              true - если плагин можно устанавливать
     *              false - если плагин устанавливать нельзя
     */
    public function is_setup_possible($oldversion=0)
    {
        return dof_is_plugin_setup_possible($this, $oldversion);
    }
    /** Получить список плагинов, которые уже должны быть установлены в системе,
     * и без которых начать установку или обновление невозможно
     * 
     * @param int $oldversion[optional] - старая версия плагина в базе (если плагин обновляется)
     *                                    или 0 если плагин устанавливается
     * @return array массив плагинов, необходимых для установки
     *      Формат: array('plugintype'=>array('plugincode' => YYYYMMDD00));
     */
    public function is_setup_possible_list($oldversion=0)
    {
        return array('storage'=>array('config'=> 2011080900));
    }
    /** Список обрабатываемых плагином событий 
     * @return array - array(array('plugintype'=>..,'plugincode'=>..,'eventcode'=>..),...)
     * @access public
     */
    public function list_catch_events()
    {
        return array();
    }
    /** Требуется ли запуск cron в плагине
     * если требуется - возвращает количество секунд между запусками
     * если нет - возвращает false
     * @return mixed int или bool false
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
     * @param dof_control $dof - идентификатор действия, которое должно быть совершено
     * @access public
     */
    public function __construct($dof)
    {
        $this->dof = $dof;
    }
    /** Запустить обработку периодических процессов
     * @param int $messages - количество отображаемых сообщений (0 - не выводить, 3 - детальная диагностика)
     * @return bool - true в случае выполнения без ошибок
     * @access public
     */
    // **********************************************
    // Методы, предусмотренные интерфейсом plugin_modlib
    // **********************************************

    // **********************************************
    // Собственные методы
    // **********************************************

    /**
     * Функция необходимая для использования Moodle QuickForm.
     * Подключает класс moodleform и создает класс otech_moodleform
     * не требует никаких аргументов и не возвращает значений
     */
    public function webform()
    {
        require_once($this->dof->plugin_path('modlib','widgets','/form/lib.php'));
        return 'dof_modlib_widgets_form';
    }
    /**
     * Функция необходимая для использования Moodle QuickForm.
     * Подключает класс moodleform и создает класс otech_moodleform
     * не требует никаких аргументов и не возвращает значений
     * 
     * @deprecated оставлена для совместимости. При разработке используйте функцию webform()
     */
    public function form_classname()
    {
        dof_debugging('Using deprecated method dof_modlib_widgets_form::form_classname().
                       Use webform() method instread', DEBUG_DEVELOPER);
        return $this->webform();
    }
    /** Подключить класс dof_html_writer, отвечающий за отрисовку базовых методов интерфейса
     * 
     * @return null
     * @access public
     */
    public function html_writer()
    {
        include_once($this->dof->plugin_path('modlib','widgets','/html_writer/lib.php'));
    }
    /** Получить список часов (от 00 до 23) для select-элемента формы
     * 
     * @todo пометить как deprecated после того как будет усоверешенствован элемент dof_duration
     * 
     * @return array 
     */
    public function get_hours_list_for_select()
    {
        return 
            array(
                0  => '00',
                1  => '01',
                2  => '02',
                3  => '03',
                4  => '04',
                5  => '05',
                6  => '06',
                7  => '07',
                8  => '08',
                9  => '09',
                10 => '10',
                11 => '11',
                12 => '12',
                13 => '13',
                14 => '14',
                15 => '15',
                16 => '16',
                17 => '17',
                18 => '18',
                19 => '19',
                20 => '20',
                21 => '21',
                22 => '22',
                23 => '23');
    }
    
    /** Получить список минут (от 00 до 55 с шагом в 5 минут) для select-элемента формы
     * 
     * @todo пометить как deprecated после того как будет усоверешенствован элемент dof_duration
     * 
     * @return array 
     */
    public function get_minutes_list_for_select()
    {
        return 
            array(
                0  => '00',
                5  => '05',
                10 => '10',
                15 => '15',
                20 => '20',
                25 => '25',
                30 => '30',
                35 => '35',
                40 => '40',
                45 => '45',
                50 => '50',
                55 => '55');
    }
    
    /** Подключает класс pages_navigation для отображения страниц
     * @param string $code - код плагина
     * @param int $recordscount - количество записей
     * @param int $limitnum - максимальное кол-во записей, отображенное на странице
     * @param int $limitfrom - номер записи, с которой начинается просмотр
     * @return dof_modlib_widgets_pages_navigation обьект класса "pages_navigation" с указанными параметрами
     */
    public function pages_navigation($code, $recordscount, $limitfrom, $limitnum = NULL)
    {
        require_once($this->dof->plugin_path('modlib', 'widgets','/pages_navigation/lib.php'));
        $pages = new dof_modlib_widgets_pages_navigation($this->dof, $code, $recordscount, $limitnum, $limitfrom);
        return $pages;
    }
    
    /**
     * Возвращает количество записей на странице по умолчанию
     * Для виджета dof_modlib_widgets_pages_navigation
     * @return integer
     */
    public function get_limitnum_bydefault()
    {
        return 30;
    }
    
    /** Распечатывает хорошо отформатированную таблицу, использующую текущую тему moodle
     * 
     *
     * @param object $table - стандартный объект со следующими свойствами.
     * <ul>
     *     <li>$table->head - Массив заголовков таблицы. (если не задан - выведутся только данные)
     *     <li>$table->align - Массив, который хранит параметры горизонтального выравнивания текста в колонках
     *     <li>$table->size  - Массив размеров колонок
     *     <li>$table->wrap - Массив, отвечающий за возможность переноса текста внутри колонки.
     *                        Возможные значения:
     *                            * true (переносить)
     *                            * false (не переносить)
     *     <li>$table->data[] - Массив, каждый элемент которого является массивом значений строки таблицы
     *     <li>$table->width  - ширина таблицы в пикселях или процентах
     *     <li>$table->tablealign  - Расположение всей таблицы
     *     <li>$table->cellpadding  - html-параметр cellpadding
     *     <li>$table->cellspacing  - html-параметр cellspacing
     *     <li>$table->class - html-параметр "class", отвечающий за то, какой 
     *                         стиль должен быть сопоставлен этой таблице
     *     <li>$table->id - html-параметр "id" для использования getElementById
     *     <li>$table->rowclass[] - массив названий css-классов для добавления их к специальным рядам
     *     <li>$table->summary - общее описание содержимого таблицы.
     * </ul>
     * @param bool $return - если true, то таблица не будет распечатана, а будет возвращен только ее код
     * @return boolean|string
     */
    public function print_table($table, $return=false)
    {
        $newtable = new html_table();
        foreach ($table as $property => $value)
        {
            if (property_exists($newtable, $property))
            {
                $newtable->{$property} = $value;
            }
        }
        if (isset($table->class))
        {
            $newtable->attributes['class'] = $table->class;
        }
        if ( isset($table->rowclass) AND is_array($table->rowclass) )
        {
            $this->dof->debugging('rowclass[] has been deprecated for html_table and should be replaced by rowclasses[]. please fix the code.');
            $newtable->rowclasses = $table->rowclass;
        }
        $output = html_writer::table($newtable);
        if ($return)
        {
            return $output;
        }else
        {
            echo $output;
            return true;
        }
    }
    
    /** Получить строку, содержащую html-код строки со вкладками, отформатированную
     * при помощи css-стилей текущей темы moodle
     *
     * @param array $tabrows массив массивов, каждый из которых содержит 
     *              массив объектов вкладок (dof_modlib_widgets_tabobject)
     *              Пример: array($level1tabs, $level2tabs)
     *              $level1tabs - содержит верхний ряд вкладок
     *              $level2tabs - содержит нижний ряд вкладок
     * @param string $selected  id текущей выбранной вкладки (вне зависимости от того какой ряд отображается)
     * @param array  $inactive  Массив id неактивных вкладок, на которые нельзя нажать
     * @param array  $activated Массив id активных вкладок, на которые можно нажимать
     * @param bool   $return вернуть код или сразу распечатать вкладки
     *                true - вернуть код
     *                false - распечатать
     */
    public function print_tabs($tabrows, $selected=NULL, $inactive=NULL, $activated=NULL, $return=false)
    {
        global $OUTPUT;
        // Вызываем функцию moodle
        if (method_exists($OUTPUT, 'tabtree'))
        {// для moodle 2.5 используем новый метод
            return $OUTPUT->tabtree($tabrows, $selected, $inactive, $activated, $return);
        }
        
        if (!is_array($tabrows[0]))
        {
            $tabrows = array('0' => $tabrows);
        }
        return print_tabs($tabrows, $selected, $inactive, $activated, $return);
    }
    
   
    /** Создает объект вкладки
     *
     * @param string $id - уникальное имя вкладки в строке. Только латинские буквы
     * @param string $link[optional] - Ссылка, куда ведет вкладка
     * @param string $text[optional] - Название вкладки 
     * @param string $title[optional] - Всплывающая подсказка, отображается при наведении мыши на вкладку
     * @param bool $linkedwhenselected[optional] - показывать ссылку на вкладку, если она уже выбрана.
     *             true - показывать
     *             false - не показывать
     * @return dof_modlib_widgets_tabobject
     */
    public function create_tab($id, $link='', $text='', $title='', $linkedwhenselected=false)
    {
        // подключаем класс для работы со вкладками
        require_once($this->dof->plugin_path('modlib', 'widgets','/tabs/lib.php'));
        // создаем и возвращаем новую вкладку
        return dof_modlib_widgets_tabobject::get_tabobject($id, $link, $text, $title, $linkedwhenselected);
    }
    
    /** Получить объект, колторый реализует стандартную форму "добавить/удалить".
     * @param string $formid - id формы на странице. Используется javascript-функцией getElementById
     * 
     * @todo добавить возможность указывать дополнительные элементы
     * @todo добавить возможность указывать группы в меню для удаления
     * @todo добавить поиск
     * @todo добавить документацию в wiki
     * 
     * @return dof_modlib_widgets_addremove
     */
    public function addremove($action=null, $formid='dof_modlib_widgets_adddremove')
    {
        require_once($this->dof->plugin_path('modlib', 'widgets','/form/addremove.php'));
        $addremove = new dof_modlib_widgets_addremove($this->dof, $action, $formid);
        return $addremove;
    }
    
    /** Получить объект для inline-редактирования данных. При вызове метода также автоматически подключаются
     * Все нужные для работы элемента js-библиотеки
     * 
     * @param string $plugintype - тип плагина, который будет предоставлять и сохранять данные
     * @param string $plugincode - код плагина, который будет предоставлять и сохранять данные
     * @param string $queryname - тип запроса (тип запроса - как правило имя поля)
     * @param int $objectid - id объекта, который редактируется
     * @param string $type - тип элемента. Это параметра type для тега input. Допустимые значения: text, textarea, select
     * @param string $text - текст, который отображается на элементе до редактирования
     * @param string|array $options - массив или строка дополнительных html-параметров для div-элемента, содержащего поле редактирования. По умолчанию null.
     * 
     * @return dof_modlib_widgets_ifield
     */
    public function ifield($plugintype, $plugincode, $queryname, $objectid, $type, $text, $options=null)
    {
        require_once($this->dof->plugin_path($this->type(), $this->code(), '/form/ifield.php'));
        return new dof_modlib_widgets_ifield($this->dof, $plugintype, $plugincode, $queryname, 
                                             $objectid, $type, $text, $options);
    }
    
    /** Функция получения настроек для плагина
     *  
     */
    public function config_default($code=null)
    {
        // стандартная настройка "плагин включен"
        $config = array();
        $obj = new object();
        $obj->type  = 'checkbox';
        $obj->code  = 'enabled';
        $obj->value = '1';
        $config[$obj->code] = $obj;
        
        return $config;
    }
    
    /** Подключить js-библиотеку (вместе со стилями) или набор скриптов, по переданному коду
     * 
     * @param string|array $codes - код библиотеки или набора скриптов, которые надо подключить
     *                             или список таких библиотек
     */
    public function js_init($codes)
    {
        $result = true;
        
        if ( ! is_array($codes) )
        {
            $codes = array($codes);
        }
        
        foreach ( $codes as $code )
        {
            $result = $result & $this->js_init_one_lib($code);
        }
        
        return $result;
    }
    
    /** Подключить одну библиотеку или один набор скриптов
     * @param string $code - код библиотеки или набора стилей, которые нужно подключить
     * 
     * @todo перенести jeditable в плагин modlib/jquery и сделать там специальную функцию для подключения плагинов
     * 
     */
    protected function js_init_one_lib($code)
    {
        switch ($code)
        {
            case 'jquery': return $this->dof->modlib('jquery')->jquery_init(); break;
            // inline-редактирование текста
            case 'ifield':
                $this->js_init('jquery'); 
                $this->dof->modlib('jquery')->jquery_plugin_init('jeditable');
                $this->dof->modlib('nvg')->add_js($this->type(), $this->code(), '/js/ifield.js');
            break;
            // ajax-подгрузка автозаполнения (элемент формы)
            case 'autocomplete':
                $this->js_init('jquery');
                //$this->dof->modlib('nvg')->add_js($this->type(), $this->code(), '/js/autocomplete.js'); 
            break;
            // Иерархическая цепочка select-списокв с AJAX-подгрузкой элементов
            case 'ajaxselect':
                $this->js_init('jquery');
                $this->dof->modlib('nvg')->add_js($this->type(), $this->code(), '/form/elements/dof_ajaxselect/dof_ajaxselect.js');
            break;
            case 'show_hide':
                $this->js_init('jquery');
                $this->dof->modlib('nvg')->add_css($this->type(), $this->code(), '/css/show_hide.css');
                $this->dof->modlib('nvg')->add_js($this->type(), $this->code(), '/js/show_hide.js');
            break;
            case 'calendar':
                $this->js_init('jquery');
                $this->dof->modlib('nvg')->add_js($this->type(), $this->code(), '/form/elements/dof_calendar/dof_calendar.js');
                $this->dof->modlib('nvg')->add_js($this->type(), $this->code(), '/form/elements/dof_calendar/jquery.ui.datepicer-ru.js');
            break;
        }
        
        return true;
    }

    /** Открыть блок 
     * 
     * @param string $classes - имена css-классов через пробел(по умолчанию generalbox)
     * @param string $ids - id элементов через пробел
     * @param bool $return - вернет html-код блока если true
     * @return теги для открытия блока с содержимым или null
     */
    public function print_box_start($classes='generalbox', $ids='', $return=false)
    {
        global $OUTPUT;
        // обращаемся к функции moodle для открытия блока
        $output = $OUTPUT->box_start($classes, $ids);
        if ($return)
        {
            return $output;
        }else
        {
            echo $output;
        }
    }    
    
    /** Закрыть блок
     * 
     * @param bool $return - возвращает как строку или просто распечатывает его
     * @return код закрывающих тегов блока или null
     */
    public function print_box_end($return=false)
    {
        global $OUTPUT;
        // обращаемся к функции moodle для закрытия блока
        $output = $OUTPUT->box_end();
        if ($return)
        {
            return $output;
        }else
        {
            echo $output;
        }
    }
    
    /** Вывести на страницу блок с указанным содержимым
     *
     * @param string $message - сообщение выводимое в блоке
     * @param string $classes - имена css-классов через пробел(по умолчанию generalbox)
     * @param string $ids - id элементов через пробел
     * @param bool $return - вернет html-код блока если true
     * @return html-код блока с содержимым или null
     */
    public function print_box($message, $classes='generalbox', $ids='', $return=false) 
    {
        global $OUTPUT;
        // обращаемся к функции moodle 
        $output = $OUTPUT->box($message, $classes, $ids);
        if ($return)
        {
            return $output;
        }else
        {
            echo $output;
        }
    }
    
    /** Вывести заголовок страницы
     *
     * @param string $text - текст заголовока выводимый на экран
     * @param string $align - выравнивание заголовка (НЕ ИСПОЛЬЗУЕТСЯ)
     * @param int $size - размер заголовка 
     * @param string $class
     * @param boolean $return - возвращает как строку или просто распечатывает его
     * @return смешанную строку или ничего
     */
    function print_heading($text, $deprecated='', $size=2, $class='main', $return=false) 
    {
        global $OUTPUT;
        $output = $OUTPUT->heading($text, $size, $class);
        if ($return) {
            return $output;
        } else {
            echo $output;
        }
    }

    /** Метод получения данных для autocomplete
     * 
     * @param string $plugintype - тип плагина(по умолчанию 'storage')
     * @param string $plugincode - код плагина
     * @param string $querytype (код запроса, по которому плагин определяет какие именно данные возвращать)
     * @param string $data - строка, с данными после декодирования json
     * @param integer $depid - id подразделения 
     * @param int    $objectid - id объекта, с которым работаем(используем)
     * 
     * @return array or false - запись, если есть или false, если нет
     */
    public function get_list_autocomplete($plugintype, $plugincode, $querytype, $depid, $data, $objectid)
    {
        // проверка на сущ метода автокомплит в классе
        if ( ! method_exists($this->dof->{$plugintype}($plugincode),'widgets_field_variants_list') )
        {         
            return false;
        }

        // очищаем переданные данные
        $data = $data;
        // в зависимости от типа выберем нужные даные\поля(у каждого плагина - СВОЁ)
        // метод widgets_field_variants_list САМ уже возвращает данные в виде ключ-значение
        return $this->dof->{$plugintype}($plugincode)->widgets_field_variants_list($querytype, $depid, $data, $objectid); 
    }  
    
    /** Метод получения данных для autocomplete
     * 
     * @param string $plugintype - тип плагина(по умолчанию 'storage')
     * @param string $plugincode - код плагина
     * @param string $querytype (код запроса, по которому плагин определяет какие именно данные возвращать)
     * @param string $data - строка, с данными после декодирования json
     * @param integer $depid - id подразделения 
     * @param int    $objectid - id объекта, с которым работаем(используем)
     * 
     * @return array or false - запись, если есть или false, если нет
     */
    public function get_extvalues_autocomplete($name,$autocomplite)
    {
        $param = array();
        //print_object($autocomplite);
        switch ($autocomplite['do'])
        {   // пустое значение
            case "**#empty":
                $param['do'] = 'empty';
                $param['id'] = null;
                $param['name'] = null;
                break;
            //создать 
            case "**#create":
                $param['do'] = 'create';
                $param['id'] = null;
                $param['name'] = trim($autocomplite[$name]);
                break;
            // переименовать
            case "**#rename":
                $param['do'] = 'rename';
                $param['id'] = $autocomplite['id'];
                $param['name'] = trim($autocomplite[$name]);
                if ( $existid = strripos($autocomplite[$name],'[') )
                {// если в записи присутствует id - его не учитываем
                    $param['name'] = trim(mb_substr($autocomplite[$name],0,$existid));
                }
                break;
            // выбрать 
            case "**#choose":
                $param['do'] = 'choose';
                $param['id'] = $autocomplite['id'];
                $param['name'] = trim($autocomplite[$name]);
                if ( $existid = strripos($autocomplite[$name],'[') )
                {// если в записи присутствует id - его не учитываем
                    $param['name'] = trim(mb_substr($autocomplite[$name],0,$existid));
                }
                break;
            // не распознано - ошибка
            default: 
                $param['do'] = 'error';
                $param['id'] = null;
                $param['name'] = null;
        }
        return $param;
    }
   
    
    /** Обновить данные одного поля при inline-редактировании
     * @param string $plugintype - тип плагина, который отвечает за редактироване поля
     * @param string $plugincode - код плагина, который отвечает за редактироване поля
     * @param string $querytype - уникальный код запроса внутри плагина. Как правило - имя сохраняемого поля
     * @param int    $objectid - id объекта, поля которого редактируются
     * @param string $data - данные в формате json (или просто строка), пришедшие из формы редактированя объекта. 
     * 
     * @return string новое, обновленное значение параметра из базы, или html-код ошибки 
     */
    public function save_ifield($plugintype, $plugincode, $querytype, $objectid, $data)
    {
        if ( ! method_exists($this->dof->{$plugintype}($plugincode), 'widgets_save_field') )
        {         
            return $this->ajax_function_error_text($plugintype, $plugincode, 'widgets_save_field');
        }
        // очищаем переданные данные
        $data      = htmlspecialchars($data);
        $querytype = htmlspecialchars($querytype);
        
        // перенаправляем запрос на редактирование поля нужному плагину
        $result = $this->dof->{$plugintype}($plugincode)->widgets_save_field($querytype, $objectid, $data);
        
        return $result;
    }
    
    /** Обновить данные одного поля при inline-редактировании
     * @param string $plugintype - тип плагина, который отвечает за редактироване поля
     * @param string $plugincode - код плагина, который отвечает за редактироване поля
     * @param string $fieldname - поле объекта, которое нужно запросить
     * @param int    $objectid - id объекта, который редактируется
     * @param string $data - дополнительные данные для загрузки объекта
     * 
     * @return string значение указанного параметра из базы или html-код ошибки
     */
    public function load_ifield($plugintype, $plugincode, $fieldname, $objectid, $data=null)
    {
        if ( ! method_exists($this->dof->{$plugintype}($plugincode), 'widgets_load_field') )
        {         
            return $this->ajax_function_error_text($plugintype, $plugincode, 'widgets_load_field');
        }
        // очищаем переданные данные
        $data      = htmlspecialchars($data);
        $fieldname = htmlspecialchars($fieldname);
        
        $result = $this->dof->{$plugintype}($plugincode)->widgets_load_field($fieldname, $objectid, $data);
        
        return htmlspecialchars_decode($result);
    }
    
    /** Получить строку с ошибкой, узазывающей на отсутствие в плагине функции для получения
     * или сохранения данных
     * 
     * @param string $plugintype
     * @param string $plugincode
     * @param string $function
     * 
     * @return string
     */
    protected function ajax_function_error_text($plugintype, $plugincode, $function)
    {
        $a = new object;
        $a->plugin   = $plugintype.'/'.$plugincode;
        $a->function = $function;
        
        return $this->dof->get_string('error:required_function_not_exists', 'widgets', $a, 'modlib');
    }
    
    /** Оформить текст сообщения об ошибке (красный div-блок)
     * @param string $text - текст сообщения
     * 
     * @return string - html-код сообщения об ошибке
     */
    public function error_message($text)
    {
        return $this->style_info_message($text, 'error');
    }
    
    /** Оформить текст сообщения об успешной операции (зеленый div-блок)
     * @param string $text - текст сообщения
     * 
     * @return string - html-код сообщения об ошибке
     */
    public function success_message($text)
    {
        return $this->style_info_message($text, 'success');
    }
    
    /** Оформить текст информационного сообщения (серый div-блок)
     * @param string $text - текст сообщения
     * 
     * @return string - html-код сообщения об ошибке
     */
    public function notice_message($text)
    {
        return $this->style_info_message($text, 'notice');
    }
    
    /** Оформить текст предупреждающего сообщения (желтый div-блок)
     * @param string $text - текст сообщения
     * 
     * @return string - html-код сообщения об ошибке
     */
    public function warning_message($text)
    {
        return $this->style_info_message($text, 'warning');
    }
    
    /** Оформить текст сообщения об ошибке, успехе или просто с информацией, 
     * в зависимости от типа
     * 
     */
    protected function style_info_message($text, $type)
    {
        // определяем, какой css-класс использовать для сообщений
        $class = 'block_dof_'.$type.'_message';
        $result = '<div class="'.$class.'">'.$text.'</div>';
        return $result;
    }
    
    /**
     * Выводит сообщение да/нет для дальнейшей работы с пользователем
     * Обертка для API Moodle
     * @param string $message - сообщение при выборе действия
     * @param string $linkyes - ссылка для перехода при выборе ДА
     * @param string $linkno  - ссылка для перехода при выборе НЕТ
     * @param string $optionsyes - 
     * @param string $optionsno - 
     * @param string $methodyes - метод отправки данных при выборе ДА
     * @param string $methodno  - метод отправки данных при выборе НЕТ
     * 
     **/
    public function notice_yesno($message, $linkyes, $linkno, $optionsyes=NULL, $optionsno=NULL, $methodyes='post', $methodno='post')
    {
        GLOBAL $OUTPUT;
        
        $buttoncontinue = new single_button(new moodle_url($linkyes, $optionsyes), $this->dof->modlib('ig')->igs('yes'), $methodyes);
        $buttoncancel   = new single_button(new moodle_url($linkno, $optionsno), $this->dof->modlib('ig')->igs('no'), $methodno);
        echo $OUTPUT->confirm($message, $buttoncontinue, $buttoncancel);
    }
    /*********************/
    /* Устаревшие методы */
    /*********************/
    /**
     * Создает progressbar c указанными параметрами
     * $name    - Название (html-атрибут name) html-элемента, представляющего прогрессбар (например картинка).
     * $percent - Начальное процентное значение
     * $width   - длина в пикселях.
     * $process - азвание выполняемого процесса (сохранение... загрузка... и т. д.)
     * $auto_create если TRUE то функция create() будет вызвана сразу же после создания обьекта,
     * (то есть сразу после обращения к этой функции прогрессбар появится на экране)
     * @deprecated после появления библиотеки jquery этот метод стал неактуален.
     *             Он оставлен здесь ТОЛЬКО для совместимости 
     * 
     * @return object обьект класса "progressbar" с указанными параметрами
     */
    public function progressbar($name, $percent, $width, $process, $auto_create)
    {
        require_once($this->dof->plugin_path('modlib', 'widgets','/progressbar/lib.php'));
        return new dof_modlib_widgets_progressbar($name, $percent, $width, $process, $auto_create);
    }
}
?>