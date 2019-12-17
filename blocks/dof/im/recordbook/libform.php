<?php 

// Подключаем библиотеки
require_once('lib.php');

// содключаем библиотеку форм
$DOF->modlib('widgets')->webform();

/** Класс формы отображения событий
 *
 */
class dof_im_recordbook_datepicker_form extends dof_modlib_widgets_form
{
    /**
     * @var dof_control
     */
    protected $dof;

    /*
     * Код плагина
    */
    protected function im_code()
    {
        return 'recordbook';
    }

    function definition()
    {
        // делаем глобальные переменные видимыми
        $this->dof = $this->_customdata->dof;
        $depid = $this->_customdata->depid;

        // создаем ссылку на HTML_QuickForm
        $mform =& $this->_form;

        //создаем заголовок формы
        $mform->addElement('header','formtitle', $this->dof->get_string('choose_week', 'recordbook'));
        $mform->addElement('hidden','departmentid', $depid);
        $mform->setType('departmentid', PARAM_INT);


        // добавляем для совместимость, если вдруг нет js у пользователей
        // если сработал js, то прячем эти элементы
        $options = array();
        $options['startyear'] = dof_userdate(time()-5*365*24*3600,'%Y');
        $options['stopyear']  = dof_userdate(time()+5*365*24*3600,'%Y');
        $options['optional']  = false;
        
        // добавляем для совместимость, если вдруг нет js у пользователей
        // если сработал js, то прячем эти элементы
        $options = array();
        $options['startyear'] = dof_userdate(time()-5*365*24*3600,'%Y');
        $options['stopyear']  = dof_userdate(time()+5*365*24*3600,'%Y');
        $options['optional']  = false;
        
        $mform->addElement('date_selector', 'date_fr',$this->dof->modlib('ig')->igs('from'),$options);
        $mform->setType('date_fr', PARAM_INT);
        $mform->setDefault('date_fr', $this->_customdata->date_from);
        
        // добавим календарь
        $options = array();
        $options['date_from'] = $this->_customdata->date_from;
        // дозапишем значения, которые надо скрывать
        $options[] = 'date_fr';
        $mform->addElement('dof_calendar','calendar', '', $options);
        
        $mform->addElement('html', '<div style="text-align: center">');
        $mform->addElement('submit', 'buttonview',     $this->dof->get_string('button_view','recordbook'));
        $mform->addElement('html', '</div>');
        
        // применение проверки ко всем элементам
        $mform->applyFilter('__ALL__', 'trim');
    }

    /** Проверка данных на стороне сервера
     * @return array
     * @param array $data[optional] - массив с данными из формы
     */
    public function validation($data,$files)
    {// проверка ввода корректной даты
        return true;
    }

     
}

?>