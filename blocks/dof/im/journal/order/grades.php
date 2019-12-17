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



// Подключаем библиотеки
require_once(dirname(realpath(__FILE__))."/../lib.php");
require_once($DOF->plugin_path('storage','orders','/baseorder.php'));
/**
 * Класс для создания приказов 
 * о выставлении текущих оценок
 */
class dof_im_journal_order_set_grade extends dof_storage_orders_baseorder
{
    public function plugintype()
    {
        return 'im';
    }
    
    public function plugincode()
    {
        return 'journal';
    }
    
    public function code()
    {
        return 'set_grade';
    }
    
    protected function execute_actions($order)
    {
        //получили оценки из приказа
        if ( ! isset($order->data) OR ! $order->data )
        {//не получили оценки из приказа
            return false;
        }
        $order->data->orderid = $order->id;//print_object($order);die;
        //сохраняем оценки
        return $this->dof->storage('cpgrades')->save_grade_students($order->data);
    }
}

class dof_im_journal_order_delete_grade extends dof_storage_orders_baseorder
{
    public function plugintype()
    {
        return 'im';
    }
    
    public function plugincode()
    {
        return 'journal';
    }
    
    public function code()
    {
        return 'delete_grade';
    }
    
    protected function execute_actions($order)
    {
        //получили оценки из приказа
        if ( ! isset($order->data) OR ! $order->data )
        {//не получили оценки из приказа
            return false;
        }
        $order->data->orderid = $order->id;
        // удаляем оценки
        $result = true;
        
        foreach ($order->data->grades as $grade)
        {// обрабатываем оценки по одной
            if ( isset($grade['id']) AND $grade['id'] )
            {
                if ( !$this->dof->storage('cpgrades')->delete($grade['id']) )
                {// запомним, что произошла ошибка, и продолжим обрабатывать оценки
                    $result = false;
                }
            }
        }
        return $result;
    }
}
/** Класс для создания приказов об редактировании оценок
 * @todo реализовать обновление оценок
 */
class dof_im_journal_order_update_grade extends dof_storage_orders_baseorder
{
    public function plugintype()
    {
        return 'im';
    }
    
    public function plugincode()
    {
        return 'journal';
    }
    
    public function code()
    {
        return 'update_grade';
    }
    
    protected function execute_actions($order)
    {
        return true;
    }
}
?>