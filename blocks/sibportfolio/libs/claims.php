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

class block_sibportfolio_claims {

    public static function get_claim_by_id($claimId)
    {
        global $DB;
        return $DB->get_record_select('sibport_files', "id = ? AND userid <> 0", array($claimId));
    }
    
    public static function get_max_claims()
    {
        $configClaims = get_config('block_sibportfolio', 'max_claims');
        if (isset($configClaims) && is_numeric($configClaims))
        {
            if ($configClaims < 0) $configClaims = 0;
            return $configClaims;
        }
        else return 10;
    }
    
    public static function get_wait_claims($userId, $claimType = null, $sortType = null, $page = null, $limit = 30) // 08.12.2015
    {	
        global $DB;

        $select = 'SELECT ';
        $table = ' FROM {sibport_files} ';
        $where = isset($claimType) ? ' WHERE userid = ? AND claimtype = ? AND claimtype IS NOT NULL ' : ' WHERE userid = ? AND claimtype IS NOT NULL ';
        $order = $sortType == 1 ? ' ORDER BY claimtype, timemodified ' : ' ORDER BY timemodified ';
        
        $result = new stdClass();
        $result->total = null;
        
        $start = null;
        if (isset($page)) {
            $total = $DB->count_records_sql($select . 'COUNT(*)' . $table . $where, array($userId, $claimType));
            $result->total = ceil($total / $limit);
            if ($page > $result->total) $page = $result->total;
            if ($page <= 0) $page = 1;
            $start = ($page - 1) * $limit;
        } else $limit = null;
        
        $result->page = $page;
        $result->claims = $DB->get_records_sql($select . '*' . $table . $where . $order, array($userId, $claimType), $start, $limit);
        
        return $result;
    }
    
    public static function get_count_wait_claims_by_user($userId, $claimType = null) // 08.12.2015
    {
        global $DB;
        if (!isset($claimType)) {
            return $DB->count_records_select('sibport_files', "userid = ? AND claimtype IS NOT NULL", array($userId));
        } else {
            return $DB->count_records_select('sibport_files', "userid = ? AND claimtype = ? AND claimtype IS NOT NULL", array($userId, $claimType));
        }
    }
    
    public static function get_count_wait_claims($curatorId = null) // 03.10.15
    {
        global $DB;
        if (isset($curatorId)) {
            $query = "SELECT COUNT(DISTINCT(fa.id)) 
                        FROM {sibport_files} fa 
                  INNER JOIN {cohort_members} cm ON cm.userid = fa.userid 
                  INNER JOIN {sibport_group_data} gc ON (gc.groupid = cm.cohortid AND gc.curatorid = ?) 
                       WHERE fa.claimtype IS NOT NULL";
            return $DB->count_records_sql($query, array($curatorId));
        } else {
            return $DB->count_records_select('sibport_files', "claimtype IS NOT NULL");
        }
    }
    
    public static function register_claim($request)
    {
        global $DB;
        switch ($request->type) {
        
            case 'add':
                $record = new stdClass();
                $record->userid = $request->userid;
                $record->timecreated = time();
                $record->timemodified = $record->timecreated;
                if ($request->accepted) {
                    $record->description = $request->description;
                    $record->category = $request->category;
                } else {
                    $record->claimtype = 0;
                    $record->description2 = $request->description;
                    $record->category2 = $request->category;
                }
                
                return $DB->insert_record('sibport_files', $record);
            
            case 'edit':
                $record = new stdClass();
                $record->id = $request->claimid;
                $record->timemodified = time();
                if ($request->accepted) {
                    $record->description = $request->new_description;
                    $record->category = $request->new_category;
                    $record->claimtype = NULL;
                } else {
                    $record->description = $request->old_description;
                    $record->category = $request->old_category;
                    $record->description2 = $request->new_description;
                    $record->category2 = $request->new_category;
                    $record->claimtype = 1;
                    $record->usercomment = $request->comment;
                }
            
                $DB->update_record('sibport_files', $record);
                break;
            
            case 'del':
                $record = new stdClass();
                $record->id = $request->claimid;
                $record->timemodified = time();
                if ($request->accepted) {
                    $record->userid = 0;
                    $record->claimtype = NULL;
                } else {
                    $record->description = NULL;
                    $record->category = NULL;
                    $record->description2 = $request->description;
                    $record->category2 = $request->category;
                    $record->claimtype = 2;
                    $record->usercomment = $request->comment;
                }				
                
                $DB->update_record('sibport_files', $record);
                break;
                
        }
    }
    
    public static function handle_claim($claim, $description = null, $category = null)
    {
        global $DB;
        switch ($claim->claimtype) {
        
            case 0:
            case 1:
                $record = new stdClass();
                $record->id = $claim->id;
                $record->description = isset($description) ? $description : $claim->description2;
                $record->category = isset($category) ? $category : $claim->category2;
                $record->timemodified = time();
                $record->description2 = $record->category2 = $record->usercomment = $record->claimtype = NULL;
                
                $DB->update_record('sibport_files', $record);
                break;
                
            case 2:
                $record = new stdClass();
                $record->id = $claim->id;
                $record->timemodified = time();
                $record->userid = 0;
                $record->claimtype = NULL;
            
                $DB->update_record('sibport_files', $record);
                break;
                                
        }
    }
    
    public static function reject_claim($claim)
    {
        global $DB;
        switch ($claim->claimtype) {
        
            case 0:
                $record = new stdClass();
                $record->id = $claim->id;
                $record->timemodified = time();
                $record->userid = 0;
                $record->claimtype = NULL;
            
                $DB->update_record('sibport_files', $record);
                break;
            
            case 1:
                $record = new stdClass();
                $record->id = $claim->id;
                $record->timemodified = time();
                $record->description2 = $record->category2 = $record->usercomment = $record->claimtype = NULL;
            
                $DB->update_record('sibport_files', $record);
                break;
                
            case 2:
                $record = new stdClass();
                $record->id = $claim->id;
                $record->description = $claim->description2;
                $record->category = $claim->category2;
                $record->timemodified = time();
                $record->description2 = $record->category2 = $record->usercomment = $record->claimtype = NULL;
                
                $DB->update_record('sibport_files', $record);
                break;
                                
        }
    }
    
    public static function get_logclaim($userId, $page = null, $limit = 30) // 07.12.2015
    {
        global $DB;

        $select = 'SELECT ';
        $table = ' FROM {sibport_claim_log} ';
        $where = ' WHERE userid = ? ';
        $order = ' ORDER BY timecreated DESC ';
        
        $result = new stdClass();
        $result->total = null;
        
        $start = null;
        if (isset($page)) {
            $total = $DB->count_records_sql($select . 'COUNT(*)' . $table . $where, array($userId));
            $result->total = ceil($total / $limit);
            if ($page > $result->total) $page = $result->total;
            if ($page <= 0) $page = 1;
            $start = ($page - 1) * $limit;
        } else $limit = null;
        
        $result->page = $page;
        $result->log = $DB->get_records_sql($select . '*' . $table . $where . $order, array($userId), $start, $limit);
        
        return $result;
    }
    
    public static function write_log($logData)
    {
        global $DB;
        return $DB->insert_record('sibport_claim_log', $logData);
    }

}
