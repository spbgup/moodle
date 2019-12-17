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

//загрузка библиотек верхнего уровня
require_once(dirname(realpath(__FILE__))."/../../lib.php");

/** Класс подключения к синхронизации
 *  
 */

class dof_storage_sync_connect
{
    // настройки подключения
    private $options;

    /** Конструктор класса
     * @param string downptype - тип внутреннего плагина
     * @param string downpcode - код внутреннего плагина
     * @param string downsubstorage - код внутреннего субсправочника
     * @param string upptype - тип внешнего плагина
     * @param string uppcode - код внешнего плагина
     * @param string upsubstorage - код внешнего субсправочника  
     */
    public function __construct($downptype, $downpcode, $downsubstorage, $upptype, $uppcode, $upsubstorage)
    {
        global $DOF;
        $this->dof = $DOF;
        $this->options = new stdClass();
        $this->options->downptype        = $downptype;
        $this->options->downpcode        = $downpcode;
        $this->options->downsubstorage   = $downsubstorage;
        $this->options->upptype          = $upptype;
        $this->options->uppcode          = $uppcode;
        $this->options->upsubstorage     = $upsubstorage;
    }
    
    /** получение параметров подключения
     * @return object
     */
    public function getOptions()
    {
        return clone $this->options;
    }
    
    /** получение данных о внешних объектах синхронизации
     * @param string $downid - внутренний id синхронизации
     * @param string $downhash[''] - хеш последних загрузенных данных
     * @return array
     */
    public function checkUp($downid, $downhash='')
    {
        $rez = array();
        
        if ( intval($downid) <= 0 )
        {// проверка id
            return array();
        }
        
        $select = " downid = {$downid} AND lastoperation <> 'unsync' AND lastoperation <> 'delelte' ";
        if ( !$synclist = $this->dof->storage('sync')->get_records_select($select) )
        {// данных нет - выходим
            return array();
        }
        
        foreach ($synclist as $sync)
        {// создаем массив с результатом
            $rez[] = $this->checkObjectUp($sync,$downhash);
        } 
        return $rez;
    }
    
    /** получение данных о внешних объектах синхронизации
     * @param string $downid - внутренний id синхронизации
     * @param string $downhash[''] - хеш последних загрузенных данных
     * @return array
     */
    public function checkObjectUp($down, $downhash='')
    {
        $obj = new stdClass();
        $obj->upid = $down->upid;
        
        if ($downhash == $down->downhash)
        {// хеши объектов совпадают - присваиваем актуальный статус
            $obj->status = 'actual';
        }else
        {
            $obj->status = 'old';
        }
        return $obj;
    }
    
   /** получение данных о внутренних объектах синхронизации
    * @param string $upid - внешний id синхронизации
    * @param string $uphash[''] - хеш последних загрузенных данных
    * @return array
    */
    public function checkDown($upid, $uphash='')
    {
        $rez = array();
    
        if ( intval($upid) <= 0 )
        {// проверка id
            return array();
        }
    
        $select = " upid = {$upid} AND lastoperation <> 'unsync' AND lastoperation <> 'delelte' ";
        if ( !$synclist = $this->dof->storage('sync')->get_records_select($select) )
        {// данных нет - выходим
            return array();
        }
    
        foreach ($synclist as $sync)
        {// создаем массив с результатом
            $obj = new stdClass();
            $obj->downid = $sync->downid;
            if ($uphash == $sync->uphash AND $sync->uphash != '')
            {// хеши объектов совпадают - присваиваем актуальный статус
                $obj->status = 'actual';
            }else
            {
                $obj->status = 'old';
            }
            $rez[] = $obj;
        }
        return $rez;
    }
    
    /** обновляем статус синхронизации внешних объектов
     * @param int    $downid - внутренний id синхронизации
     * @param string $operation - опреция синхронизации
     * @param string $downhash - внутренний хеш обекта синхронизации
     * @param int    $upid[null] - внешний id синхронизации
     * @param int    $textlog - текст лога синхронизации
     * @param object $opt -дополнительные параметры лога синхронизации
     * @param bool   $error - есть ли ошибка при синхронизации
     * @return bool
     */
    public function updateUp($downid,$operation,$downhash,$upid=null,$textlog='',$opt=null,$error=false)
    {
        // получаем список доступных синхронизаций
        $select = " downptype = '{$this->options->downptype}' AND "
                ." downpcode = '{$this->options->downpcode}' AND "
                ." downsubstorage = '{$this->options->downsubstorage}' AND "
                ." upptype = '{$this->options->upptype}' AND "
                ." uppcode = '{$this->options->uppcode}' AND "
                ." upsubstorage = '{$this->options->upsubstorage}' AND "
                ." downid = '{$downid}' AND lastoperation != 'unsync' AND lastoperation != 'delete' ";         
        if ($operation == 'create' OR $operation == 'connect')
        {// создаем новую запись
            if ( $error )
            {// вернулась ошибка - фиксируем ее
                $this->dof->storage('synclogs')->add_log($operation,'up',0,$textlog,$opt,$error);
                return false;
            }
            if ( empty($upid) )
            {// ошибка входного параметра
                $textlog = 'updateUp. Empty upid';
                $this->dof->storage('synclogs')->add_log($operation,'up',0,$textlog,$opt,true);
                return false;
            }
            $select .= " AND upid = '{$upid}' ";
            if ( $this->dof->storage('sync')->is_exists_select($select) )
            {// такая запись уже существует - ошибка, выходим
                $textlog = 'updateUp. Record is exist';
                $this->dof->storage('synclogs')->add_log($operation,'up',0,$textlog,$opt,true);
                return false;
            }

            // совпадений не найдено - создаем новую запись синхронизации
            $obj = new stdClass();
            $obj = $this->getOptions();
            $obj->downid = $downid;
            $obj->upid = $upid;
            $obj->downhash = $downhash;
            $obj->lastoperation = $operation;
            $obj->lasttime = time();
            $obj->direct = 'up';
            if ( $syncid = $this->dof->storage('sync')->insert($obj) )
            {// добавление прошло без ошибок
                $this->dof->storage('synclogs')->add_log($operation,'up',$syncid,$textlog,$opt);
                return $syncid;       
            }else
            {// не смогли вставить запись - добавляем лог
                $opt->insert = $obj;
                $textlog = 'updateUp. Insert record has been failed';
                $this->dof->storage('synclogs')->add_log($operation,'up',0,$textlog,$opt,true); 
                return false;
            }
        }else
        {// проводим обновление
            if ( ! empty($upid) )
            {// найдем записис с таким upid
                $select .= "AND upid = '{$upid}'";
            }
            if ( !$records = $this->dof->storage('sync')->get_records_select($select) )
            {// обновлять нечего - выходим
                return true;
            }
            $result = true;
            foreach ($records as $record)
            {
                if ( $error )
                {// вернулась ошибка - фиксируем ее
                    $this->dof->storage('synclogs')->add_log($operation,'up',$record->id,$textlog,$opt,$error,$record->lastoperation);
                    return false;
                }
                $obj = new stdClass();
                $obj->id = $record->id;
                $obj->lastoperation = $operation;
                $obj->lasttime = time();
                $obj->downhash = $downhash;
                if ( $update = $this->dof->storage('sync')->update($obj) )
                {// обновление прошло успешно 
                    $this->dof->storage('synclogs')->add_log($operation,'up',$record->id,$textlog,$opt,false,$record->lastoperation);
                }else
                {// не смогли обновить - логируем
                    $opt->update = $obj;
                    $textlog = 'updateUp. Update record has been failed';
                    $this->dof->storage('synclogs')->add_log($operation,'up',$record->id,$textlog,$opt,true,$record->lastoperation);
                }
                $result = $update && $result;
            }
            return $result;
        }
    }
    
    /** обновляем статус синхронизации внутренних объектов
     * @param int $upid - внешний id синхронизации
     * @param string $operation - опреция синхронизации
     * @param string $uphash - внешний хеш обекта синхронизации
     * @param int $downid[null] - внутренний id синхронизации
     * @return bool
     */
    public function updateDown($upid,$operation,$uphash,$downid=null,$textlog='',$opt=null,$error=false)
    {
        // получаем список доступных синхронизаций
        $select = " downptype = '{$this->options->downptype}' AND "
                ." downpcode = '{$this->options->downpcode}' AND "
                ." downsubstorage = '{$this->options->downsubstorage}' AND "
                ." upptype = '{$this->options->upptype}' AND "
                ." uppcode = '{$this->options->uppcode}' AND "
                ." upsubstorage = '{$this->options->upsubstorage}' AND "
                ." upid = '{$upid}' AND lastoperation <> 'unsync' AND lastoperation <> 'delete' ";
       
    
        if ($operation == 'create' OR $operation == 'connect')
        {// создаем новую запись
            if ( $error )
            {// вернулась ошибка - фиксируем ее
                $this->dof->storage('synclogs')->add_log($operation,'down',0,$textlog,$opt,$error);
                return false;
            }
            if ( empty($downid) OR intval($downid) <= 0)
            {// ошибка входного параметра
                $textlog = 'updateDown. Empty downid';
                $this->dof->storage('synclogs')->add_log($operation,'down',0,$textlog,$opt,true);
                return false;
            }
            $select .= "AND downid = '{$downid}'";
            if ( $this->dof->storage('sync')->is_exists_select($select) )
            {// обновлять нечего - выходим
                $textlog = 'updateDown. Record is exist';
                $this->dof->storage('synclogs')->add_log($operation,'down',0,$textlog,$opt,true);
                return false;
            }
            // совпадений не найдено - создаем новую запись синхронизации
            $obj = new stdClass();
            $obj = $this->getOptions();
            $obj->downid = $downid;
            $obj->upid = $upid;
            $obj->uphash = $uphash;
            $obj->lastoperation = $operation;
            $obj->lasttime = time();
            $obj->direct = 'down';
            if ( $syncid = $this->dof->storage('sync')->insert($obj) )
            {// добавление прошло без ошибок
                $this->dof->storage('synclogs')->add_log($operation,'down',$syncid,$textlog,$opt);
                return $syncid;       
            }else
            {// не смогли вставить запись - добавляем лог
                $opt->insert = $obj;
                $textlog = 'updateDown. Insert record has been failed';
                $this->dof->storage('synclogs')->add_log($operation,'down',0,$textlog,$opt,true); 
                return false;
            }
        }else
        {// проводим обновление
            if ( ! empty($downid) )
            {// ошибка входного параметра
                $select .= "AND downid = '{$downid}'";
            }
            if ( !$records = $this->dof->storage('sync')->get_records_select($select) )
            {// обновлять нечего - выходим
                return true;
            }
            $result = true;
            foreach ($records as $record)
            {
                if ( $error )
                {// вернулась ошибка - фиксируем ее
                    $this->dof->storage('synclogs')->add_log($operation,'down',$record->id,$textlog,$opt,$error,$record->lastoperation);
                    return false;
                }
                $obj = new stdClass();
                $obj->id = $record->id;
                $obj->lastoperation = $operation;
                $obj->lasttime = time();
                $obj->uphash = $uphash;
                if ( $update = $this->dof->storage('sync')->update($obj) )
                {// обновление прошло успешно 
                    $this->dof->storage('synclogs')->add_log($operation,'down',$record->id,$textlog,$opt,false,$record->lastoperation);
                }else
                {// не смогли обновить - логируем
                    $opt->update = $obj;
                    $textlog = 'updateDown. Update record has been failed';
                    $this->dof->storage('synclogs')->add_log($operation,'down',$record->id,$textlog,$opt,true,$record->lastoperation);
                }
                $result = $update && $result;
            }
            return $result;
        }
    }
    
    /** Получаем список доступных синхронизаций для данного подключения
     *
     * @return array
     */
    public function listSync()
    {
        $select = " downptype = '{$this->options->downptype}' AND "
                ." downpcode = '{$this->options->downpcode}' AND "
                ." downsubstorage = '{$this->options->downsubstorage}' AND "
                ." upptype = '{$this->options->upptype}' AND "
                ." uppcode = '{$this->options->uppcode}' AND "
                ." upsubstorage = '{$this->options->upsubstorage}' AND "
                ." lastoperation <> 'unsync' AND lastoperation <> 'delete' ";
        
        if ( $records = $this->dof->storage('sync')->get_records_select($select) )
        {// возвращаем записи
            return $records;
            
        }
        return array();
    }   
    
    /** Получаем запись синхронизации для данного подключения
     *
     * @return array
     */
    public function getSync($param=array())
    {
        $select = " downptype = '{$this->options->downptype}' AND "
                ." downpcode = '{$this->options->downpcode}' AND "
                ." downsubstorage = '{$this->options->downsubstorage}' AND "
                ." upptype = '{$this->options->upptype}' AND "
                ." uppcode = '{$this->options->uppcode}' AND "
                ." upsubstorage = '{$this->options->upsubstorage}' AND "
                ." lastoperation <> 'unsync' AND lastoperation <> 'delete' ";
        foreach ( $param as $name=>$field )
        {
            if ( $field )
            {// если условие не пустое, то для каждого поля получим фрагмент запроса
                $select .= 'AND '.$this->dof->storage('sync')->query_part_select($name,$field);
            }
        } 
        if ( $records = $this->dof->storage('sync')->get_records_select($select) )
        {// возвращаем только одну запись
            return current($records);
        }
        return array();
    } 
}




?>