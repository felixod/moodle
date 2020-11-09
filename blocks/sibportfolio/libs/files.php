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

class block_sibportfolio_files {

    public static function get_sibport_file_link($file, $filename = null, $attributes = null)
    {
        if (!isset($filename)) $filename = $file->filename;
        $url = moodle_url::make_pluginfile_url($file->contextid, $file->component, $file->filearea, $file->itemid, $file->filepath, $file->filename);
        return html_writer::link($url, $filename, $attributes);
    }

    public static function is_file_in_portfolio($claimId)
    {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/blocks/sibportfolio/libs/claims.php');
        $claim = block_sibportfolio_claims::get_claim_by_id($claimId);
        return ($claim && !isset($claim->claimtype));
    }

    public static function get_max_filesize() 
    {
        global $CFG;
        $configSize = get_config('block_sibportfolio', 'max_filesize');
        return empty($configSize) ? $CFG->maxbytes : $configSize;
    }
    
    public static function get_locked_types()
    {
        $configTypes = get_config('block_sibportfolio', 'locked');
        return array_map('block_sibportfolio_to_lower', explode('|', $configTypes));
    }

    public static function get_file_categories() 
    {
        global $DB;
        return $DB->get_records('sibport_category_files', array(), 'id');
    }

    public static function get_file_categories_type($type) 
    {
        global $DB;
        return $DB->get_records_select('sibport_category_files', "type = ? ", array($type));
    }
    
    public static function get_file_category_by_id($categoryId) 
    {
        global $DB;
        return $DB->get_record('sibport_category_files', array('id' => $categoryId));
    }

    public static function get_file_category_by_name($name) 
    {
        global $DB;
        return $DB->get_record('sibport_category_files', array('name' => $name));
    }
    
    public static function add_file_category($name)
    {
        global $DB;
        $category = $DB->get_record('sibport_category_files', array('name' => $name));
        if (!$category) {
            return $DB->insert_record('sibport_category_files', array('name' => $name));
        } else return false;
    }

    public static function edit_file_category($categoryId, $name)
    {
        global $DB;
        $DB->update_record('sibport_category_files', array('id' => $categoryId, 'name' => $name));
    }
    
    public static function del_file_category($categoryId, $newCategoryId)
    {
        global $DB;
        
        $params = array(
            'newcat' => $newCategoryId,
            'oldcat' => $categoryId
        );
        
        $query = "UPDATE {sibport_files} 
                     SET category = :newcat 
                   WHERE category = :oldcat";						
        $DB->execute($query, $params);
        
        $query = "UPDATE {sibport_files} 
                     SET category2 = :newcat 
                   WHERE category2 = :oldcat";						
        $DB->execute($query, $params);
        
        $DB->delete_records('sibport_category_files', array('id' => $categoryId));
    }
    
    public static function get_user_files($userId, $categoryId, $page = null, $limit = 10) // 08.12.2015
    {	
        global $DB;

        $select = 'SELECT ';
        $table = ' FROM {sibport_files} ';
        $where = ' WHERE userid = ? AND category = ? AND claimtype IS NULL ';
        $order = ' ORDER BY timecreated DESC ';
        
        $result = new stdClass();
        $result->total = null;
        
        $start = null;
        if (isset($page)) {
            $total = $DB->count_records_sql($select . 'COUNT(*)' . $table . $where, array($userId, $categoryId));
            $result->total = ceil($total / $limit);
            if ($page > $result->total) $page = $result->total;
            if ($page <= 0) $page = 1;
            $start = ($page - 1) * $limit;
        } else $limit = null;
        
        $result->page = $page;
        $result->files = $DB->get_records_sql($select . '*' . $table . $where . $order, array($userId, $categoryId), $start, $limit);
        
        return $result;
    }
    
    public static function get_count_files($categoryId, $userId = null)
    {
        global $DB;
        return isset($userId) ?
            $DB->count_records_select('sibport_files', "category = ? AND userid = ? AND claimtype IS NULL", array($categoryId, $userId)) :
            $DB->count_records_select('sibport_files', "category = ? AND userid <> 0 AND claimtype IS NULL", array($categoryId));
    }

    public static function get_file_by_claim_id($claimId)
    {
        global $DB;
        $select = "itemid = ? AND component = 'block_sibportfolio' AND filearea = 'sibport_files' AND filename <> '.'";
        return $DB->get_record_select('files', $select, array($claimId));
    }

}
