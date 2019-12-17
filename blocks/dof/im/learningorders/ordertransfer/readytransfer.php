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

// подключаем библиотеку
require_once 'lib.php';
// класс ордера
require($DOF->plugin_path('im','learningorders','/order/transfer.php'));
// ордер
$id = required_param('orderid', PARAM_INT);
// подтверждение на вопрос "вы уверены"
$confirm = optional_param('confirm', 0, PARAM_INT);
// права
$DOF->im('learningorders')->require_access('order');

if ( ! $confirm )
{// формируем предупреждение "вы уверены что хотите подписать приказ?"
    $paramsyes = array('orderid' => $id, 'confirm' => 1);
    $linkyes   = $DOF->url_im('learningorders', '/ordertransfer/readytransfer.php',array_merge($addvars,$paramsyes));
    $linkno    = $DOF->url_im('learningorders', '/list.php',$addvars);
    $confirmmessage = $DOF->get_string('are_yore_sure_you_want_execute_the_order', 'learningorders');
    
    //печать шапки страницы
    $DOF->modlib('nvg')->print_header(NVG_MODE_PORTAL);
    // сообщение с просьбой подтвердить выбор
    $DOF->modlib('widgets')->notice_yesno($confirmmessage, $linkyes, $linkno);
    //печать подвала
    $DOF->modlib('nvg')->print_footer(NVG_MODE_PORTAL);
}else
{
    $backurl = '<a href="'.$DOF->url_im('learningorders','/list.php',$addvars).'">'.$DOF->modlib('ig')->igs('back').'</a>';
    //
    $ready = new dof_im_learningorders_ordertransfer($DOF, $id);
    
    if ( ! $ready->check_order_data() )
    {// устаревший приказ
        //печать шапки страницы
        $DOF->modlib('nvg')->print_header(NVG_MODE_PORTAL);
        // сообщение с просьбой подтвердить выбор
        echo '<p style=" color:red; text-align:center"><b>'.$DOF->get_string('old_order', 'learningorders').'</b></p>';
        echo '<p style=" text-align:center">'.$backurl.'</p>';
        //печать подвала
        $DOF->modlib('nvg')->print_footer(NVG_MODE_PORTAL);
    }elseif ( ! $ready->order->check_order_data($ready->get_order_data()) )
    {// устаревший приказ
        //печать шапки страницы
        $DOF->modlib('nvg')->print_header(NVG_MODE_PORTAL);
        // сообщение с просьбой подтвердить выбор
        echo '<p style=" color:red; text-align:center"><b>'.$DOF->get_string('error_ready_data_order', 'learningorders').'</b></p>';
        echo '<p style=" text-align:center">'.$backurl.'</p>';
        //печать подвала
        $DOF->modlib('nvg')->print_footer(NVG_MODE_PORTAL);
    }elseif ( $a = $ready->order->execute() )
    {// подписан успешно
        redirect($DOF->url_im('learningorders','/list.php',$addvars));
    }else
    {// не подписан
        $DOF->modlib('nvg')->print_header(NVG_MODE_PORTAL);
        // сообщение с просьбой подтвердить выбор
        echo '<p style=" color:red; text-align:center"><b>'.$DOF->get_string('order_nowready', 'learningorders').'</b></p>';
        echo '<p style=" text-align:center">'.$backurl.'</p>';
        //печать подвала
        $DOF->modlib('nvg')->print_footer(NVG_MODE_PORTAL);
    }
}

?>