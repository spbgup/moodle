<?php

///////////////////////////////////////////////////////////////////////////
//                                                                       //
// NOTICE OF COPYRIGHT                                                   //
//                                                                       //
// Moodle - Modular Object-Oriented Dynamic Learning Environment         //
//          http://moodle.org                                            //
//                                                                       //
// Copyright (C) 1999 onwards Martin Dougiamas  http://dougiamas.com     //
//                                                                       //
// This program is free software; you can redistribute it and/or modify  //
// it under the terms of the GNU General Public License as published by  //
// the Free Software Foundation; either version 2 of the License, or     //
// (at your option) any later version.                                   //
//                                                                       //
// This program is distributed in the hope that it will be useful,       //
// but WITHOUT ANY WARRANTY; without even the implied warranty of        //
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the         //
// GNU General Public License for more details:                          //
//                                                                       //
//          http://www.gnu.org/copyleft/gpl.html                         //
//                                                                       //
///////////////////////////////////////////////////////////////////////////

global $CFG;
require_once($CFG->libdir . '/form/group.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/form/text.php');


/**
 * Класс АВТОКОМПЛИТ. Создает элемент autocomplete, который посредством js
 * выдает в выпадающем меню подходящие по условию данные 
 *  
 * @package formslib
 */
class MoodleQuickForm_dof_autocomplete extends MoodleQuickForm_group
{
    
    /*
     * @var array массив с информацией о том как настраивать AJAX-запрос
     */
    var $_options = array();
    /*
     * строка, имя html-элемента (в js используется для идентификации id элемента)
     */    
    var $_elementName = '';
    /*
     * @var string - строка, содержащая html и js-код необходимый для работы элемента
     */     
    var $_js = '';
    /*
     * @var значение элемента hidden по умолчанию
     */     
    var $_id_for_hidden = 0;
    /*
     * текст в поле по умолчанию
     */     
    var $_text = '';

        
    /** Конструктор класса для совместимости с PHP 5.3
     * 
     * @access public
     * @param  string $elementName Element's name
     * @param  mixed  $elementLabel Label(s) for an element
     * @param  mixed  $attributes Either a typical HTML attribute string or an associative array
     */
    function __construct($elementName = null, $elementLabel = null, $attributes = null, $options=null)
    {
        $this->HTML_QuickForm_element($elementName, $elementLabel, $attributes);
        $this->_persistantFreeze = true;
        $this->_appendName = true;
        $this->_type = 'dof_autocomplete';
        $this->_elementName = $elementName;
    }
   
   /**
    * Class constructor
    *
    * @access public
    * @param  string $elementName Element's name
    * @param  mixed  $elementLabel Label(s) for an element
    * @param  mixed  $attributes Either a typical HTML attribute string or an associative array
    */
    function MoodleQuickForm_dof_autocomplete($elementName = null, $elementLabel = null, $attributes = null, $options=null)
    {
        GLOBAL $DOF;
       
        $this->HTML_QuickForm_element($elementName, $elementLabel, $attributes);
        $this->_persistantFreeze = true;
        $this->_appendName       = true;
        $this->_type             = 'dof_autocomplete';
        $this->_elementName      = $elementName;
        
        // подключаем js-скрипты чтобы работал AJAX
        $DOF->modlib('widgets')->js_init('autocomplete');
        
        // вставка значений по умолчанию
        $this->_do_for_hidden = '**#empty';
        // @todo переименовать во всех обращениях к autocomplete 'option' в 'default'
        // удалить этот фрагмент кода после того как все вызовы будут переделаны
        if ( isset($options['option']) )
        {
            // dof_debugging('dof_autocomplete: incorrect array key for default values use $options["default"] 
            //        instread of $options["option"]');
            $this->_id_for_hidden = key($options['option']);
            $this->_do_for_hidden = '**#choose';
            $this->_text          = current($options['option']);
            // удалим, чтоб не засорять json массвы в дальнейшем
            unset($options['options']);
        }
        // вставка значений по умолчанию (новая, старую удалить)
        if ( isset($options['default']) )
        {
            $this->_id_for_hidden = key($options['default']);
            $this->_do_for_hidden = '**#choose';
            $this->_text          = current($options['default']);
            // удалим, чтоб не засорять json массвы в дальнейшем
            unset($options['default']);
        }
        
        // @todo избавиться от использования optional_param(), после того как все обращения к
        // dof_autocomplete станут передавать подразделение. После этого удалить это условие
        if ( ! isset($options['departmentid']) )
        {
            // dof_debugging('dof_autocomplete: required parameter $options['departmentid'] is missing');
            $options['departmentid'] = optional_param('departmentid',0,PARAM_INT);
        }
        // настройка для расширенного автокомплита с возможностью 
        // создать, переименовать и удалить значение прямо в нем
        if ( empty($options['extoptions']) )
        {
            $options['extoptions'] = new stdClass;
        }
        $this->extoptions = $options['extoptions'];
        unset($options['extoptions']);// удалим, чтоб не засорять json массвы в дальнейшем
        // необходимые параметры для json-массива (чтобы корректно послать AJAX-запрос)
        if ( isset($options['plugintype']) AND
             isset($options['plugincode']) AND
             isset($options['sesskey'])    AND 
             isset($options['querytype'])  AND
             isset($options['departmentid']) ) 
        {
            if ( ! isset($options['type']) )
            {// тип json-запроса чаще всего один и тот же
                $options['type'] = 'autocomplete';
            }
            $this->_options = json_encode($options);
        }else 
        {// Нет обязательных параметров
            dof_debugging('dof_autocomplete required options is missing', DEBUG_DEVELOPER);
            print_error('dof_autocomplete required options is missing');
        }
        
    }
    

    /** Создаем элементы 
     * -text  : элемент текс с значениями по умолчанию(если есть)
     * -hidden: хранит в себе id выбранного элемента
     * -html  : вспомогат элемент, для вставки js-кода
     * 
     **/
    function _createElements() {
        // получим атрибуты            
        $attributes = $this->getAttributes();
        // дополним их СВОИИ обязательными
        $attributes['id'] = "id_".$this->_elementName;
        $attributes['value'] = $this->_text;
        $this->_elements = array();
        $this->_elements[] = @MoodleQuickForm::createElement('text',$this->_elementName,null,$attributes);
        // @todo удалить старый элемент 'id_autocomplete' после переработки всех форм, использующих dof_autocomplete
        // оставить только один hidden, который называется 'id'
        $this->_elements[] = @MoodleQuickForm::createElement('hidden', 'id_autocomplete', $this->_id_for_hidden,
                    array('id'=> $this->_elementName.'_old_hidden_id'));
        $this->_elements[] = @MoodleQuickForm::createElement('hidden', 'id', $this->_id_for_hidden,
                    array('id'=> $this->_elementName.'_hidden_id'));
        $this->_elements[] = @MoodleQuickForm::createElement('hidden', 'do', $this->_do_for_hidden,
                    array('id'=> $this->_elementName.'_hidden_do'));
        $this->_elements[] = @MoodleQuickForm::createElement('html', $this->get_js());

        foreach ($this->_elements as $element){
            if (method_exists($element, 'setHiddenLabel')){
                $element->setHiddenLabel(true);
            }
        }
    }   
    
    
    /**
	 *	Отрисовка элементов
     */
    function toHtml() {
        include_once('HTML/QuickForm/Renderer/Default.php');
        $renderer = new HTML_QuickForm_Renderer_Default();
        $renderer->setElementTemplate('{element}');
        parent::accept($renderer);
        return $renderer->toHtml();
    }
    
    /**
     * Формирует js-скрипт и возвращает его
     */
    function get_js()
    {
        global $DOF;
        $js = "<script type=\"text/javascript\">\n//<![CDATA[\n";
        $js .= '
        $(document).ready( 
        	function()
       		{
				// функция возвращает данные в формате, пригодном для автокомплит
             	function process(data)
             	{
    				// инициализируем переменную json 
            		var json = $.parseJSON( data );
            		// переменнаяч для выборки значений(выпадающий список) 
            		var variants = new Array();
                    // преобразуем к нужному виду для выборки выриантов
                    '.$this->get_ext_values($this->extoptions).'
        			for( var key in json )
                    {
                    	// создаем массив объектов
                    	variants.push(
                    	{  
                    		value: json[key]["name"],
                    		label: json[key]["name"],
                    		id: json[key]["id"],
                    		do: \'**#choose\'
    					});
                    } 
    				return variants;
				}
           
				// Добавляем метод автозаполнения и в хидден поля ставим id значения 
                $("#id_'.$this->_elementName.'").autocomplete(
                {
                	source: function(request, response){
                      	var jsondat = '.$this->_options.';	
                     	jsondat["data"] = request.term;
                     	var number = parseInt(request.term);
                     	// ввели или строку больше 2 чимволов или id персоны
                     	if ( request.term.length > 2 || (! isNaN(number) && number > 0) )
                     	{
                     		$.post("'.$DOF->url_modlib("widgets","/json.php").'", jsondat,function(data)
                         	{
                         		var a = process(data);
                         		response( a );return;
                         	 });
                        } 	 
                     	response("");return;
					
                 	},
                 	// задержка 0.4 сек
                 	delay : 400,
					// обработка события выбора варианта 
                    select: function(event,ui) 
                    { 
                        // сохраняем значение выбранного варианта 
                        $("#'.$this->_elementName.'_hidden_id").val(ui.item.id);
                        // сохраняем значение выбранного варианта 
                        $("#'.$this->_elementName.'_hidden_do").val(ui.item.do);
                        // @todo удалить старый hidden после переработки всех форм
                        $("#'.$this->_elementName.'_old_hidden_id").val(ui.item.id);
					}
                });
        	}
        )';
        $js .= "\n //]]>\n</script>";        
        
        return $js;
    }
    
    
    function accept(&$renderer, $required = false, $error = null) {
        $renderer->renderElement($this, $required, $error);
    }
    
    protected function get_ext_values($options)
    {
        global $DOF;
        $values = '';  
        $el_value = '$("#id_'.$this->_elementName.'").val()';
        if ( isset($options->empty) )
        {
            $values .= ' variants.push( 
                     { 
                         value: \'\',
                         label: \''.$DOF->get_string('autocomplete_empty', 'widgets', null, 'modlib').'\',
                         id: \'\',
                         do: \'**#empty\'
                     });';
        }
        if ( isset($options->create) )
        {
            $values .= ' variants.push( 
                     { 
                         value: '.$el_value.',
        
                         label: \''.$DOF->get_string('autocomplete_create', 'widgets', null, 'modlib').'\'+\' \'+ '.$el_value.',
                         id: \'\', 
                         do: \'**#create\'
                     });';
        }
        if ( isset($options->rename) )
        {
            $values .= ' variants.push( 
                     { 
                         value: '.$el_value.',
                         label: \''.$DOF->get_string('autocomplete_rename', 'widgets', null, 'modlib').'\'+\' \'+ '.$el_value.',
                         id: \''.$options->rename.'\', 
                         do: \'**#rename\',
                     });';
        }
        return $values;
    }
    
}
 