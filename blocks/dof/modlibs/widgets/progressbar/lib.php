<?php
/*
 * базовый класс для всех прогрессбаров
 */
class dof_modlib_widgets_progressbar
{
    var $name;      // Название (html-атрибут name) html-элемента, представляющего прогрессбар (например картинка).
    var $percent;   // Текущее процентное значение.
    var $width;     // Максимальная длина в пикселях.
    var $process;   // Название выполняемого процесса (сохранение... загрузка... и т. д.)

    /** 
     * Function progress_bar - Конструктор класса
     * Параметры:
     * $name    - Название (html-атрибут name) html-элемента, представляющего прогрессбар (например картинка).
     * $percent - Начальное процентное значение
     * $width   - длина в пикселях.
     * $process - название выполняемого процесса (сохранение... загрузка... и т. д.)
     * $auto_create если TRUE то функция create() будет вызвана сразу же после создания обьекта
     */
    function __construct($name = 'pbar',$percent = 1,$width = 100, $process = '', $auto_create = true)
    {
        // задаем html-имя картинки
        $this->name    = $name;
        // задаем начальное процентное значение
        $this->percent = $percent;
        // задаем длину в пикселях
        $this->width   = $width;
        // устанавливаем название процесса
        $this->process = $process;
        if($auto_create)
        {
            $this->create();
        }
    }
    
    /** 
     * Конструктор класса для старых версий php
     * Параметры:
     * $name    - Название (html-атрибут name) html-элемента, представляющего прогрессбар (например картинка).
     * $percent - Начальное процентное значение
     * $width   - длина в пикселях.
     * $process - название выполняемого процесса (сохранение... загрузка... и т. д.)
     * $auto_create если TRUE то функция create() будет вызвана сразу же после создания обьекта
     */
    function dof_modlib_widgets_progressbar($name = 'pbar',$percent = 1,$width = 100, $process = '', $auto_create = true)
    {
        return $this->__construct($name, $percent,$width,$process,$auto_create);
    }
    /** 
     * Function create() - выводит прогрессбар в виде html элемента.
     * (Внимание: не вызывайте эту функцию, если $auto_create 
     * в конструкторе стоит TRUE)
     */
    function create()
    {
        global $DOF, $CFG;
        ?>
        <div align="left">
          <!-- center -->
          <table height="20" name="<?php echo('table_' . $this->name);?>" border="0" cellpadding="0" cellspacing="0">
            <tr>
              <?php // в этой ячейке можно указать, что за процесс выполняется ?> 
              <td width="200"><?php print($this->process); ?> </td>
              <td width="4" valign="top" align="left"></td>
              <td>
                <table cellpadding="0" cellspacing="0" border="0" height="20">
                    <td name="<?php echo('cell_' . $this->name);?>" align="left" valign="middle" width="<?php echo($this->width);?>" height="20" bgcolor="#c0c0c0"><img name="<?php echo($this->name)?>" border="0" src="<?php print($DOF->url_modlib('widgets', '/progressbar/pic/fill.png')); ?>" width="<?php echo(($this->percent * .01) * $this->width);?>" height="10"></td>
                </table>
              </td>
              <td width="4"  height="20" valign="top" align="left"></td>
            </tr>
          </table>
          <!-- /center -->
        </div>
        <?
    }

    /** 
     * Function set_name() - устанавливает $name - имя html-элемента прогрессбара
     * 
     * Параметры:
     * $name - имя html-элемента
     * (эта функция бесполезна после вызома метода create())
     */
    private function set_name($name)
    {
        $this->name = $name;
    }

    /**
     * Function set_percent() - Устанавливает начальное процентное значение
     * (поле $percent) для прогрессбара
     * 
     * Параметры:
     * $percent - начальное процентное значение
     */
    function set_percent($percent)
    {
        $this->percent = $percent;
        echo('<script>document.images.' . $this->name . '.width = ' . ($this->percent / 100) * $this->width . '</script>');
    }

    /**
     * Function set_percent_adv() - Увеличивает значение прогрессбара, на небольшое значение,
     * основываясь на номере текущей задачи, и общем количеством задач.
     * Эта функция выводит на страницу кототкую строку javascript, которая тут же исполняется
     * 
     * Параметры:
     * $cur_amount номер выполняемой задачи
     * $max_amount общее количество задач, которые надо выполнить
     */
    function set_percent_adv($cur_amount,$max_amount)
    {
        $this->percent = ($cur_amount / $max_amount) * 100;
        echo('<script>document.images.' . $this->name . '.width = ' . ($this->percent / 100) * $this->width . '</script>');
        flush();
    }

    /**
     * Function set_width() - устанавливает максимальную длинну прогрессбара.
     * 
     * parameters:
     * $width - длина в прикселях
     */
    private function set_width($width)
    {
        $this->width = $width;
    }
}
?>