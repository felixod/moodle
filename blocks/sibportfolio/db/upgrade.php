<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * block_sibportfolio
 *
 * @package    block_sibportfolio
 * @copyright  2017 Aleksandr Raetskiy, SibSIU <ksenon3@mail.ru>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_block_sibportfolio_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();
    
    if ($oldversion < 2015093018) {
        $table = new xmldb_table('sibport_claim_log');
        $field = new xmldb_field('description', XMLDB_TYPE_CHAR, '500');
        $dbman->change_field_type($table, $field);
    }
    
    if ($oldversion < 2015100622) {			
        $table = new xmldb_table('sibport_group_data');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL);
        $table->add_field('curatorid', XMLDB_TYPE_INTEGER, '11');
        $table->add_field('study', XMLDB_TYPE_CHAR, '250');
        $table->add_field('speciality', XMLDB_TYPE_CHAR, '250');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id')); 
        $table->add_key('foreign1', XMLDB_KEY_FOREIGN_UNIQUE, array('groupid'), 'cohort', array('id'));
        $table->add_key('foreign2', XMLDB_KEY_FOREIGN, array('curatorid'), 'user', array('id'));
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        
        $table = new xmldb_table('sibport_groups_curators');
        if ($dbman->table_exists($table)) {
            $records = $DB->get_records('sibport_groups_curators');
            foreach ($records as $record) {
                $groupData = new stdClass();
                $groupData->groupid = $record->groupid;
                $groupData->curatorid = $record->curatorid;
                $DB->insert_record('sibport_group_data', $groupData);
            }
            $dbman->drop_table($table);
        }
    }
    
    if ($oldversion < 2015100820) {
        $table = new xmldb_table('sibport_claim_log');
        $field = new xmldb_field('description', XMLDB_TYPE_CHAR, '1000');
        $dbman->change_field_type($table, $field);
        $field = new xmldb_field('comment', XMLDB_TYPE_CHAR, '500');
        $dbman->change_field_type($table, $field);
        $field = new xmldb_field('usercomment', XMLDB_TYPE_CHAR, '500');
        $dbman->change_field_type($table, $field);
        
        $table = new xmldb_table('sibport_files');
        $field = new xmldb_field('description', XMLDB_TYPE_CHAR, '1000');
        $dbman->change_field_type($table, $field);
        $field = new xmldb_field('description2', XMLDB_TYPE_CHAR, '1000');
        $dbman->change_field_type($table, $field);
        $field = new xmldb_field('usercomment', XMLDB_TYPE_CHAR, '500');
        $dbman->change_field_type($table, $field);				
    }
    
    if ($oldversion < 2015100918) {			
        $table = new xmldb_table('sibport_record_books');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL);
        $table->add_field('number', XMLDB_TYPE_INTEGER, '11');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id')); 
        $table->add_key('foreign1', XMLDB_KEY_FOREIGN, array('groupid'), 'cohort', array('id'));
        $table->add_key('foreign2', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $index = new xmldb_index('groupuser');
        $index->set_attributes(XMLDB_INDEX_UNIQUE, array('groupid', 'userid'));
        $table->addIndex($index);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
    }
    
    if ($oldversion < 2015101921) {
        $table = new xmldb_table('sibport_record_books');
        $field = new xmldb_field('number', XMLDB_TYPE_CHAR, '250');
        $dbman->change_field_type($table, $field);
    }
    
    if ($oldversion < 2015110519) {
        $table = new xmldb_table('sibport_record_books');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('speciality', XMLDB_TYPE_CHAR, '250');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
            $field = new xmldb_field('study', XMLDB_TYPE_CHAR, '250');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
            $dbman->rename_table($table, 'sibport_users');
        }
    }
    
    if ($oldversion < 2015111416) {
        $table = new xmldb_table('sibport_claim_log');
        if ($dbman->table_exists($table)) {
            $field = new xmldb_field('itemid', XMLDB_TYPE_INTEGER, '11');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
    }
    
    if ($oldversion < 2015121222) {
        $table = new xmldb_table('sibport_files');
        if ($dbman->table_exists($table)) {					
            $DB->execute('UPDATE {sibport_files} SET claimtype = NULL WHERE itemid2 IS NULL');
            $DB->execute('UPDATE {sibport_files} SET itemid = itemid2 WHERE (itemid2 IS NOT NULL AND itemid2 <> -1)');
            $field = new xmldb_field('itemid2', XMLDB_TYPE_INTEGER, '11');
            if ($dbman->field_exists($table, $field)) {
                $dbman->drop_field($table, $field);
            }
        }
    }
    
    if ($oldversion < 2015122620) {
        $oldtable = new xmldb_table('sibport_files');		
        if ($dbman->table_exists($oldtable)) {		
            $dbman->rename_table($oldtable, 'sibport_files_old');			
        }
        $oldtable = new xmldb_table('sibport_files_old');
        
        $newtable = new xmldb_table('sibport_files');
        $newtable->add_field('id', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $newtable->add_field('userid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL);
        $newtable->add_field('description', XMLDB_TYPE_CHAR, '1000');
        $newtable->add_field('category', XMLDB_TYPE_INTEGER, '11');
        $newtable->add_field('claimtype', XMLDB_TYPE_INTEGER, '11');
        $newtable->add_field('description2', XMLDB_TYPE_CHAR, '1000');
        $newtable->add_field('category2', XMLDB_TYPE_INTEGER, '11');
        $newtable->add_field('usercomment', XMLDB_TYPE_CHAR, '500');
        $newtable->add_field('timecreated', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, 0);
        $newtable->add_field('timemodified', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, 0);
        
        $newtable->add_key('primary', XMLDB_KEY_PRIMARY, array('id')); 
        $newtable->add_key('foreign1', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $newtable->add_key('foreign2', XMLDB_KEY_FOREIGN, array('category'), 'sibport_category_files', array('id'));
        $newtable->add_key('foreign3', XMLDB_KEY_FOREIGN, array('category2'), 'sibport_category_files', array('id'));
        
        if (!$dbman->table_exists($newtable)) {		
            $dbman->create_table($newtable);		
        }
    
        $logtable = new xmldb_table('sibport_claim_log');
        $field = new xmldb_field('itemid', XMLDB_TYPE_INTEGER, '11');
        if ($dbman->field_exists($logtable, $field)) {
            $dbman->rename_field($logtable, $field, 'itemid2');
            $dbman->add_field($logtable, $field);
        }
        
        $fs = get_file_storage();
        $claims = $DB->get_records('sibport_files_old');
        foreach ($claims as $claim) {
        
            $claimid = $DB->insert_record('sibport_files', $claim);
            $DB->execute("UPDATE {sibport_claim_log} SET itemid = ? WHERE userid = ? AND itemid2 = ?", array($claimid, $claim->userid, $claim->itemid));
        
            $fileid = null;
            $contextId = context_user::instance($claim->userid)->id;	
            $files = $DB->get_records('files', array(
                'contextid' => $contextId, 
                'itemid' => $claim->itemid,
                'component' => 'block_sibportfolio',
                'filearea' => 'sibport_item'
            ));
            foreach ($files as $file) {
                if ($file->filename != '.') {
                    $fileid = $file->id;
                    break;
                }
            }				
            $fileinfo = array(
                'contextid' => $contextId, 
                'component' => 'block_sibportfolio',  
                'filearea' => 'sibport_files',    
                'itemid' => $claimid,
                'filepath' => '/', 
                'filename' => $file->filename,
                'userid' => $claim->userid,
                'author' => $file->author		
            ); 
        
            $fs->create_file_from_storedfile($fileinfo, $fileid);
        }
        
        $del_files = $DB->get_records('files', array(
            'component' => 'block_sibportfolio',
            'filearea' => 'sibport_item'
        ));
        foreach ($del_files as $del_file) {
            if ($del_file->filename != '.') {
                $fs->delete_area_files($del_file->contextid, 'block_sibportfolio', 'sibport_item', $del_file->itemid);
            }					
        }
        
        $field = new xmldb_field('itemid2', XMLDB_TYPE_INTEGER, '11');
        if ($dbman->field_exists($logtable, $field)) {
            $dbman->drop_field($logtable, $field);
        }
        
        if ($dbman->table_exists($oldtable)) {
            $dbman->drop_table($oldtable);
        }		
    }
    
    if ($oldversion < 2016010516) { // вызов cron.php для удаления текущих файлов по старому алгоритму
        $fs = get_file_storage();
        $query = "SELECT id, userid, itemid FROM {sibport_claim_log} 
            WHERE (((claimtype = 2 AND claimstatus = 1) OR (claimtype = 0 AND claimstatus = 0)) AND itemid IS NOT NULL) 
            GROUP BY userid, itemid";
        $records = $DB->get_records_sql($query);
        foreach ($records as $record) {
            $DB->execute("UPDATE {sibport_claim_log} SET itemid = NULL WHERE itemid = ?", array($record->itemid));
            $fs->delete_area_files(context_user::instance($record->userid)->id, 'block_sibportfolio', 'sibport_files', $record->itemid);				
        }
    }
    
    if ($oldversion < 2016030421) {
        set_config('last_userid', 0, 'block_sibportfolio');
    
        $table = new xmldb_table('sibport_courses');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 1);
        
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id')); 

        if (!$dbman->table_exists($table)) {		
            $dbman->create_table($table);		
        }
    
        $table = new xmldb_table('sibport_course_modules');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL);
        $table->add_field('moduleid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL);
        $table->add_field('type', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL);
        $table->add_field('name', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('active', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, 1);
        
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id')); 
        $table->add_key('foreign', XMLDB_KEY_FOREIGN, array('courseid'), 'sibport_courses', array('id'));

        if (!$dbman->table_exists($table)) {		
            $dbman->create_table($table);		
        }
    
        $table = new xmldb_table('sibport_learning_history');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL);
        $table->add_field('moduleid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL);
        $table->add_field('modjson', XMLDB_TYPE_TEXT, 'long');
        $table->add_field('lastupdate', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, 0);
        
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('foreign1', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));			
        $table->add_key('foreign2', XMLDB_KEY_FOREIGN, array('moduleid'), 'sibport_course_modules', array('id'));

        $table->add_index('cmindex', XMLDB_INDEX_UNIQUE, array('userid', 'moduleid'));
        
        if (!$dbman->table_exists($table)) {		
            $dbman->create_table($table);		
        }
    }
    
    return true;
}
