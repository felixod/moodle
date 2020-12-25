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

class block_sibportfolio_groups {

    public static function get_groups_by_name($groupName = null, $curatorId = null, $limit = 25) // 08.12.15
    {		
        global $DB;

        $select = 'SELECT ';
        $table = ' FROM {cohort} c ';
        $join = ' INNER JOIN {sibport_group_data} gd ON (gd.groupid = c.id AND gd.curatorid = :curatorid) ';
        $where = ' WHERE ' . $DB->sql_like('c.name', ':groupname', false) . ' ';
        $order = ' ORDER BY c.name ';
        
        $addition = isset($curatorId) ? $table . $join : $table;
        
        $params = array(
            'groupname' => '%'.$groupName.'%', 
            'curatorid' => $curatorId
        );
        
        if (isset($groupName)) {
            $addition .= $where;
            $total = $DB->count_records_sql($select . 'COUNT(*)' . $addition, $params);
            if ($total > $limit) return 0;
        }
        
        return $DB->get_records_sql($select . 'c.id, c.name, c.visible' . $addition . $order, $params);
    }

    public static function get_group_by_id($groupId) 
    {
        global $DB;
        return $DB->get_record('cohort', array('id' => $groupId));
    }

    public static function get_groups_by_user($userId, $onlyVisible = false) // 16.10.15
    {
        global $DB;
        $query = "SELECT c.id, c.name, c.visible 
                    FROM {cohort} c 
              INNER JOIN {cohort_members} cm ON (cm.cohortid = c.id AND cm.userid = ?) ";
        if ($onlyVisible) $query .= "WHERE c.visible = 1";
        return $DB->get_records_sql($query, array($userId));
    }
    
    public static function get_group_files($groupId) // 27.11.2015
    {
        global $DB;
        $result = new stdClass();
        $result->count_members = $DB->count_records('cohort_members', array('cohortid' => $groupId));
        $query = "SELECT COUNT(DISTINCT(f.userid)) 
                    FROM {sibport_files} f 
              INNER JOIN {cohort_members} cm ON (f.claimtype IS NULL AND cm.cohortid = ? AND cm.userid = f.userid)";
        $result->count_filled = $DB->count_records_sql($query, array($groupId));
        return $result;
    }

    public static function get_groups_by_curator($curatorId)
    {
        global $DB;
        $query = "SELECT c.id, c.name, c.visible 
                    FROM {cohort} c 
              INNER JOIN {sibport_group_data} gc ON (gc.groupid = c.id AND gc.curatorid = ?) 
                ORDER BY c.name";
        return $DB->get_records_sql($query, array($curatorId));
    }
    
    public static function get_group_data_by_id($groupId)
    {
        global $DB;
        return $DB->get_record('sibport_group_data', array('groupid' => $groupId));
    }
    
    public static function edit_group_data($speciality, $study, $groupId)
    {
        global $DB;
        $groupsCount = $DB->count_records('sibport_group_data', array('groupid' => $groupId));
        if ($groupsCount > 0) {
            $DB->execute('UPDATE {sibport_group_data} SET speciality = ?, study = ? WHERE groupid = ?', array($speciality, $study, $groupId));
        } else {
            $DB->execute('INSERT INTO {sibport_group_data} (groupid, speciality, study) VALUES (?, ?, ?)', array($groupId, $speciality, $study));
        }
    }
    
    public static function get_user_group_data($userId, $groupId)
    {
        global $DB;
        return $DB->get_record('sibport_users', array('userid' => $userId, 'groupid' => $groupId));
    }
    
    public static function edit_user_group_data($number, $speciality, $study, $userId, $groupId)
    {
        global $DB;
        $recordsCount = $DB->count_records('sibport_users', array('userid' => $userId, 'groupid' => $groupId));
        if ($recordsCount > 0) {
            $DB->execute('UPDATE {sibport_users} SET number = ?, speciality = ?, study = ? WHERE userid = ? AND groupid = ?', array($number, $speciality, $study, $userId, $groupId));
        } else {
            $DB->execute('INSERT INTO {sibport_users} (userid, groupid, number, speciality, study) VALUES (?, ?, ?, ?, ?)', array($userId, $groupId, $number, $speciality, $study));
        }
    }
    
    public static function get_group_data($groupName, $sort = 0, $page = null, $limit = 30) // 09.12.2015
    {
        global $DB;

        $select = "SELECT ";
        $table = " FROM {cohort} c ";
        $join = " LEFT JOIN {sibport_group_data} gd ON gd.groupid = c.id 
                  LEFT JOIN {user} u ON u.id = gd.curatorid ";
        $where = " WHERE " . $DB->sql_like('name', '?', false) . " ";
        $order = " ORDER BY name ";
        if ($sort == 1) $order = " ORDER BY curator, name ";
        
        $addition = isset($groupName) ? $table . $join . $where : $table . $join;
        
        $result = new stdClass();
        $result->total = null;
        
        $start = null;
        if (isset($page)) {
            $total = $DB->count_records_sql($select . "COUNT(*)" . $addition, array($groupName.'%'));
            $result->total = ceil($total / $limit);
            if ($page > $result->total) $page = $result->total;
            if ($page <= 0) $page = 1;
            $start = ($page - 1) * $limit;
        } else $limit = null;
        
        $result->page = $page;
        $fullname = $DB->sql_fullname('u.lastname', 'u.firstname');
        $result->groups = $DB->get_records_sql($select . "c.id, c.name, c.visible, gd.speciality, gd.study, gd.curatorid, " . $fullname . " AS curator"
            . $addition . $order, array($groupName.'%'), $start, $limit);
        
        return $result;
    }

}
