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

/** Обновление таблиц блока dof 
 * 
 * @param int $oldversion
 * @todo сделать drop_enum_field для всех старых полей
 */
function xmldb_block_dof_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2012101000) 
    {

        // Define field personid to be added to block_dof_todo
        $table = new xmldb_table('block_dof_todo');
        $field = new xmldb_field('personid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, '0', 'exdate');

        // Conditionally launch add field personid
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        $index = new xmldb_index('personid', XMLDB_INDEX_NOTUNIQUE, array('personid'));

        // Conditionally launch add index personid
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        
        // dof savepoint reached
        upgrade_block_savepoint(true, 2012101000, 'dof');
    }
    return true;
}

