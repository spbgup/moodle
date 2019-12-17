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
 * о выставлении текущей посещаемости
 */

class dof_im_journal_order_transfer extends dof_storage_orders_baseorder
{
    public function plugintype()
    {
        return 'im';
    }
    
    public function plugincode()
    {
        return 'learningorders';
    }
    
    public function code()
    {
        return 'transfer';
    }
    
    protected function execute_actions($order)
    {// приказ пока не исполняется
        
        // перед проверкой данных и исполнением приказа учеличим лимиты памяти
        // чтобы скрипт не закончился по таймауту на середине
        dof_hugeprocess();
        
        //получили оценки из приказа
        if ( ! $this->check_order_data($order) )
        {//не получили оценки из приказа
            return false;
        }
        $order->data->orderid = $order->id;
        // удаляем оценки
        $rez = true;
        if ( isset($order->data->groups) )
        {
            foreach ($order->data->groups as $id=>$group)
            {
                if ( $group->oldagenum != $group->newagenum  )
                {// если параллель не совпадает - переформировываем группу
                    if ( $this->dof->storage('agroups')->get_field($id,'status') != 'plan' )
                    {// меняем статус группы на формирующуюся
                        $rez = $rez AND $this->dof->workflow('agroups')->change($id,'plan');
                    }
                }
                $agroup = new object;
                $agroup->agenum = $group->newagenum;
                $rez = $rez AND $this->dof->storage('agroups')->update($agroup,$id);
                $object = new object;
                $object->agroupid = $id;
                $object->ageid = $group->newageid;
                $object->agenum = $group->newagenum;
                $object->changedate = time();
                if ( ! $this->dof->storage('agrouphistory')->is_exists(array('agroupid'=>$object->agroupid, 
                                             'agenum'=>$object->agenum, 'ageid'=>$object->ageid)) )
                {// если такая история уже есть - все в порядке
                    $rez = $rez AND $this->dof->storage('agrouphistory')->insert($object);
                }
            }
        }
        $student = $order->data->student;
        if ( isset($student->transfer) )
        {// массив transfer
            foreach ($student->transfer as $agenum=>$groups)
            {// массив групп
                foreach ($groups as $agroupid=>$sbcids)
                {// массив подписок 
                    foreach ($sbcids as $id=>$sbcdata)
                    {// подписка - объект
                        $obj = new object;
                        $obj->agenum = $sbcdata->newagenum;
                        $obj->agroupid = $sbcdata->newagroupid;
                        $rez = $rez AND $this->dof->storage('programmsbcs')->update($obj,$id);
                        if ( $sbcdata->oldstatus != 'active' )
                        {// меняем статус подписки, если она не было активной
                            $rez = $rez AND $this->dof->workflow('programmsbcs')->change($id,$sbcdata->newstatus);
                        }
                        // имитируем cpassed
                        $cpassed= new object;
                        $cpassed->programmsbcid = $id;
                        $cpassed->ageid         = $sbcdata->newageid;
                        $cpassed->status        = 'active';
                        $rez = $rez AND $this->dof->storage('learninghistory')->add($cpassed);
                    }
                }
            }
        }
        if ( isset($student->condtransfer) )
        {// массив condtransfer
            foreach ($student->condtransfer as $agenum=>$groups)
            {// массив групп
                foreach ($groups as $agroupid=>$sbcids)
                {// массив подписок 
                    foreach ($sbcids as $id=>$sbcdata)
                    {// подписка - объект
                        $obj = new object;
                        $obj->agenum = $sbcdata->newagenum;
                        $obj->agroupid = $sbcdata->newagroupid;                     
                        $rez = $rez AND $this->dof->storage('programmsbcs')->update($obj,$id);
                        if ( $sbcdata->oldstatus != 'condactive' )
                        {// меняем статус подписки, если она не было условно активной
                            $rez = $rez AND $this->dof->workflow('programmsbcs')->change($id,$sbcdata->newstatus);
                        }
                        // имитируем cpassed
                        $cpassed= new object;
                        $cpassed->programmsbcid = $id;
                        $cpassed->ageid         = $sbcdata->newageid;
                        $cpassed->status        = 'active';
                        $rez = $rez AND $this->dof->storage('learninghistory')->add($cpassed);
                    }
                }
            }
        }
        if ( isset($student->notransfer) )
        {// массив notransfer
            foreach ($student->notransfer as $agenum=>$groups)
            {// массив групп
                foreach ($groups as $agroupid=>$sbcids)
                {// массив подписок 
                    foreach ($sbcids as $id=>$sbcdata)
                    {// подписка - объект
                        $obj = new object;
                        $obj->agroupid = $agroupid;     
                        $rez = $rez AND $this->dof->storage('programmsbcs')->update($obj,$id);
                        // имитируем cpassed
                        $cpassed= new object;
                        $cpassed->programmsbcid = $id;
                        $cpassed->ageid         = $sbcdata->newageid;
                        $cpassed->status        = 'active';
                        $rez = $rez AND $this->dof->storage('learninghistory')->add($cpassed);
                    }
                }
            }
        }
        if ( isset($student->restore) )
        {// массив restore
            foreach ($student->restore as $agenum=>$groups)
            {// массив групп
                foreach ($groups as $agroupid=>$sbcids)
                {// массив подписок 
                    foreach ($sbcids as $id=>$sbcdata)
                    {// подписка - объект
                        $obj = new object;
                        $obj->agroupid = $agroupid;
                        $rez = $rez AND $this->dof->storage('programmsbcs')->update($obj,$id);
                        // меняем статус
                        $rez = $rez AND $this->dof->workflow('programmsbcs')->change($id,$sbcdata->newstatus);
                    }
                }
            }
        }
        if ( isset($student->academ) )
        {// массив academ
            foreach ($student->academ as $agenum=>$groups)
            {// массив групп
                foreach ($groups as $agroupid=>$sbcids)
                {// массив подписок 
                    foreach ($sbcids as $id=>$sbcdata)
                    {// подписка - объект
                        $obj = new object;
                        $obj->agroupid = $sbcdata->newagroupid;
                        $rez = $rez AND $this->dof->storage('programmsbcs')->update($obj,$id);
                        // меняем статус подписки
                        $rez = $rez AND $this->dof->workflow('programmsbcs')->change($id,$sbcdata->newstatus);
                    }
                }
            }
        }
        return $rez;
    }
    
    
    public function check_order_data($order)
    {
        if ( empty($order->data) AND empty($order->data->student) )
        {//приказа нет - исполнять нечего
            return false;
        }
        if ( isset($order->data->groups) )
        {
            foreach ($order->data->groups as $agroupid=>$group)
            {
                if ( $group->oldagenum != $group->newagenum )
                {// если периоды не совпадают - надо переформировать группу
                    if ( $this->dof->workflow('agroups')->has_active_or_suspend_cstreams($agroupid) )
                    {// если есть активные потоки группы - исполнять приказ нельзя
                        return false;
                    }
                    $status = $this->dof->storage('agroups')->get_field($agroupid,'status');
                    if ( $status != 'plan' AND $status != 'active' AND $status != 'formed' )
                    {// если группа не в активном запланированном или сформерованном статусе - переводить нельзя
                        return false;
                    }
                }
            }
        }
        foreach($order->data->student as $type => $transfertype)
        {//перебераем по типам перевода
            foreach ($transfertype as $agenum=>$groups)
            {// массив групп
                foreach ($groups as $agroupid=>$sbcids)
                {// массив подписок
                    foreach ($sbcids as $id=>$sbcdata)
                    {// подписка - объект
                        switch( $type )
                        {
                            case 'transfer':
                            case 'condtransfer':
                                if ( $sbcdata->oldstatus != 'active' AND $sbcdata->oldstatus != 'condactive' )
                                {//ученик не в активном статусе - переводить нельзя
                                    return false;
                                }
                            break;
                            case 'notransfer':
                            case 'academ':
                            	if ( $sbcdata->oldstatus != 'active' AND $sbcdata->oldstatus != 'condactive' 
                                    AND $sbcdata->oldstatus != 'suspend')
                                {//ученик не в активном или преостановленном статусе - переводить нельзя
                                    return false;
                                }
                            break;
                            case 'restore':
                                if ( $sbcdata->oldstatus != 'onleave' )
                                {//ученик не в статусе академического отпуска - переводить нельзя
                                    return false;
                                }
                            break;
                        }
                    }
                }
            }
        }
        
        return true;
    }
    



}
?>