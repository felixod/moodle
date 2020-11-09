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

class block_sibportfolio_users {

    public static function get_wait_users($curatorId = null, $sort, $page = null, $limit = 30) // 23.01.16
    {
        global $DB;

        $query = "";
        if (isset($curatorId)) {
            $query = "SELECT f.userid, COUNT(DISTINCT(f.id)) AS count_claims 
                        FROM {sibport_files} f 
                  INNER JOIN {cohort_members} cm ON (cm.userid = f.userid AND f.claimtype IS NOT NULL) 
                  INNER JOIN {sibport_group_data} gd ON (gd.groupid = cm.cohortid AND gd.curatorid = ?) 
                    GROUP BY f.userid"; // получение пользователей с заявками и их числом
        } else {
            $query = "SELECT f.userid, COUNT(f.id) AS count_claims 
                        FROM {sibport_files} f 
                       WHERE f.claimtype IS NOT NULL 
                    GROUP BY f.userid"; // получение пользователей с заявками и их числом
        }

        $select = "SELECT ";
        $table = " FROM {user} u ";
        $join = " INNER JOIN (" . $query . ") t ON (t.userid = u.id) ";
        $order = " ORDER BY fullname ";
        if ($sort == 1) $order = " ORDER BY count_claims DESC, fullname ";

        $result = new stdClass();
        $result->total = null;

        $start = null;
        if (isset($page)) {
            $total = $DB->count_records_sql("SELECT COUNT(*) FROM (" . $select  . "u.id" . $table . $join . ") t2", array($curatorId));
            $result->total = ceil($total / $limit);
            if ($page > $result->total) $page = $result->total;
            if ($page <= 0) $page = 1;
            $start = ($page - 1) * $limit;
        } else $limit = null;

        $result->page = $page;
        $fullname = $DB->sql_fullname('u.lastname', 'u.firstname');
        $result->users = $DB->get_records_sql($select . "u.*, " . $fullname . " AS fullname, t.count_claims"
            . $table . $join . $order, array($curatorId), $start, $limit);

        return $result;
    }

    public static function get_curator_by_group($groupId) // 06.10.15
    {
        global $DB;
        return $DB->get_record_select('sibport_group_data', "groupid = ? AND curatorid IS NOT NULL", array($groupId), 'id, curatorid, groupid');
    }

    public static function get_users_by_group($groupId, $page = null, $limit = 15) // 07.12.15
    {
        global $DB;

        $select = "SELECT ";
        $table = " FROM {user} u ";
        $join = " INNER JOIN {cohort_members} cm ON cm.userid = u.id ";
        $where = " WHERE u.id != 1 AND u.deleted = 0 AND cm.cohortid = ? ";
        $order = " ORDER BY fullname ";

        $result = new stdClass();
        $result->total = null;

        $start = null;
        if (isset($page)) {
            $total = $DB->count_records_sql($select . "COUNT(*)" . $table . $join . $where, array($groupId));
            $result->total = ceil($total / $limit);
            if ($page > $result->total) $page = $result->total;
            if ($page <= 0) $page = 1;
            $start = ($page - 1) * $limit;
        } else $limit = null;

        $result->page = $page;
        $fullname = $DB->sql_fullname('u.lastname', 'u.firstname');
        $result->users = $DB->get_records_sql($select . "u.*, " . $fullname . " AS fullname"
            . $table . $join . $where . $order, array($groupId), $start, $limit);

        return $result;
    }

    public static function get_users_files_count($sort, $asc, $page, $limit = 30, $mode = 0, $id = null) // 26.01.16
    {
        global $DB, $CFG;

        $usersource = "";
        $sortwhere = $sort > 0 ? " AND f.category = :sort " : "";
        $sortjoin = $sort < 0 ? "" : " LEFT JOIN (SELECT f.userid FROM {sibport_files} f WHERE f.claimtype IS NULL" . $sortwhere . ") sj ON sj.userid = s.userid ";
        $fullname = $DB->sql_fullname('u.lastname', 'u.firstname');

        switch($mode) {
            case 0:
                $usersource = " SELECT f.userid, " . $fullname . " AS fullname FROM {user} u 
                    INNER JOIN (SELECT DISTINCT(f.userid) FROM {sibport_files} f WHERE f.claimtype IS NULL) f 
                    ON f.userid = u.id AND u.id != 1 AND u.deleted = 0 ";
                break;
            case 1:
                $usersource = " SELECT t.userid, " . $fullname . " AS fullname FROM {user} u 
                    INNER JOIN (SELECT DISTINCT(cm.userid) FROM {cohort_members} cm 
                    INNER JOIN {sibport_group_data} gd ON gd.groupid = cm.cohortid AND gd.curatorid = :id) t 
                    ON t.userid = u.id AND u.id != 1 AND u.deleted = 0 ";
                break;
            case 2:
                $usersource = " SELECT cm.userid, " . $fullname . " AS fullname FROM {user} u 
                    INNER JOIN {cohort_members} cm ON cm.userid = u.id AND cm.cohortid = :id AND u.id != 1 AND u.deleted = 0 ";
                break;
        }

        $select = "SELECT ";
        $fields = " s.userid, s.fullname " . ($sort < 0 ? "" : ", COUNT(sj.userid) AS files_count ");
        $table = " FROM (" . $usersource . ") s ";
        $groupby = $sort < 0 ? "" : " GROUP BY s.userid, s.fullname ";
        $order = $sort < 0 ? " ORDER BY s.fullname " . ($asc ? " ASC " : " DESC ") : (" ORDER BY files_count " . ($asc ? " ASC " : " DESC ") . ", s.fullname");

        $filestotal = array();
        $countjoin = " INNER JOIN (SELECT f.userid FROM {sibport_files} f WHERE f.claimtype IS NULL AND f.category = :categoryid) cj ON cj.userid = s.userid ";
        require_once($CFG->dirroot.'/blocks/sibportfolio/libs/files.php');
        foreach	(block_sibportfolio_files::get_file_categories() as $category) {
            $filestotal[$category->id] = $DB->count_records_sql($select . "COUNT(*)" . $table . $countjoin, array('id' => $id, 'categoryid' => $category->id));
        }

        $result = new stdClass();
        $result->total = null;

        $start = null;
        if (isset($page)) {
            $total = $DB->count_records_sql($select . "COUNT(*)" . $table, array('id' => $id));
            $result->total = ceil($total / $limit);
            if ($page > $result->total) $page = $result->total;
            if ($page <= 0) $page = 1;
            $start = ($page - 1) * $limit;
        } else $limit = null;

        $result->page = $page;
        $result->files = $filestotal;
        $result->users = $DB->get_records_sql($select . $fields . $table . $sortjoin . $groupby . $order, array('sort' => $sort, 'id' => $id), $start, $limit);

        return $result;
    }

    public static function get_user_by_id($userId)
    {
        global $DB;
        return $DB->get_record('user', array('id' => $userId));
    }

    public static function user_exist($userInfoOrId)
    {
        global $CFG;

        $userInfo = false;
        if ($userInfoOrId !== false) {
            if (is_object($userInfoOrId)) {
                $userInfo = $userInfoOrId;
                if (!isset($userInfo->deleted)) {
                    $userInfo = self::get_user_by_id($userInfo->id);
                }
            } else {
                $userInfo = self::get_user_by_id($userInfoOrId);
            }
        }

        if (!$userInfo || $userInfo->id == 1) {
            require_once($CFG->dirroot.'/blocks/sibportfolio/libs/system.php');
            block_sibportfolio_notification(get_string('invaliduser', 'error'));
        } else if ($userInfo->deleted == 1) {
            require_once($CFG->dirroot.'/blocks/sibportfolio/libs/system.php');
            block_sibportfolio_notification(get_string('userdeleted'));
        } else return true;
    }

    public static function get_users_by_name($userInfo, $onlyVisible = false, $curatorId = null, $limit = 25) // 08.12.15
    {
        global $DB, $CFG;

        $fullname = $DB->sql_fullname('u.lastname', 'u.firstname');
        $select = "SELECT ";
        $table = " FROM {user} u ";
        $join = " INNER JOIN (SELECT DISTINCT(cm.userid) FROM {cohort_members} cm 
                  INNER JOIN {sibport_group_data} gc ON gc.groupid = cm.cohortid AND gc.curatorid = :curatorid) t
                  ON t.userid = u.id ";
        $where = " WHERE (".$DB->sql_like('u.username', ':username', false)." OR ".$DB->sql_like($fullname, ':fullname', false).") 
                   AND u.id != 1 AND u.deleted = 0 ".($onlyVisible ? " AND u.suspended = 0 " : "");
        $order = " ORDER BY fullname ";

        $addition = isset($curatorId) ? $table . $join . $where : $table . $where;

        $params = array(
            'username'  => '%'.$userInfo.'%',
            'fullname'  => '%'.$userInfo.'%',
            'curatorid' => $curatorId
        );

        $total = $DB->count_records_sql($select . "COUNT(u.id)" . $addition, $params);

        if ($total > $limit) return 0;

        $result = $DB->get_records_sql($select . "u.*, " . $fullname . " AS fullname" . $addition . $order, $params);

        require_once($CFG->dirroot.'/blocks/sibportfolio/libs/groups.php');
        foreach ($result as $key => $user) {
            $groupNames = array();
            $groups = block_sibportfolio_groups::get_groups_by_user($key, true);
            foreach ($groups as $group) {
                $groupNames[] = $group->name;
            }
            $result[$key]->group_name = count($groupNames) > 0 ? implode(', ', $groupNames) : null;
        }
        return $result;
    }

    public static function add_curator($curatorId, $groupId) // 06.10.15
    {
        global $DB;
        $groupsCount = $DB->count_records('sibport_group_data', array('groupid' => $groupId));
        if ($groupsCount > 0) {
            $DB->execute('UPDATE {sibport_group_data} SET curatorid = ? WHERE groupid = ?', array($curatorId, $groupId));
        } else {
            $DB->execute('INSERT INTO {sibport_group_data} (groupid, curatorid) VALUES (?, ?)', array($groupId, $curatorId));
        }
    }

    public static function delete_curator($groupId) // 06.10.15
    {
        global $DB;
        $DB->execute('UPDATE {sibport_group_data} SET curatorid = NULL WHERE groupid = ?', array($groupId));
    }

    public static function get_curators_data($curatorName, $sort, $page = null, $limit = 30) // 23.01.16
    {
        global $DB;

        $query1 = "SELECT gd.curatorid, COUNT(DISTINCT(f.id)) AS count_claims 
                     FROM {sibport_group_data} gd 
                LEFT JOIN {cohort_members} cm ON cm.cohortid = gd.groupid 
                LEFT JOIN {sibport_files} f ON (f.userid = cm.userid AND f.claimtype IS NOT NULL) 
                 GROUP BY gd.curatorid"; // получает список кураторов с числом заявок для каждого

        $fullname = $DB->sql_fullname('u.lastname', 'u.firstname');
        $select = "SELECT ";
        $table = " FROM {user} u ";
        $join = " INNER JOIN (" . $query1 . ") t ON (t.curatorid = u.id) ";
        $where = " WHERE " . $DB->sql_like($fullname, '?', false) . " ";
        $order = " ORDER BY fullname ";
        if ($sort == 1)
            $order = " ORDER BY u.lastaccess, fullname ";
        else if ($sort == 2)
            $order = " ORDER BY count_claims DESC, fullname ";

        $addition = isset($curatorName) ? $table . $join . $where : $table . $join;

        $result = new stdClass();
        $result->total = null;

        $start = null;
        if (isset($page)) {
            $query2 = "SELECT COUNT(DISTINCT(curatorid)) FROM {user} u INNER JOIN {sibport_group_data} gd ON (gd.curatorid = u.id) ";
            if (isset($curatorName)) $query2 .= $where;
            $total = $DB->count_records_sql($query2, array('%'.$curatorName.'%'));
            $result->total = ceil($total / $limit);
            if ($page > $result->total) $page = $result->total;
            if ($page <= 0) $page = 1;
            $start = ($page - 1) * $limit;
        } else $limit = null;

        $result->page = $page;
        $result->curators = $DB->get_records_sql($select . "u.*, " . $fullname . " AS fullname, t.count_claims"
            . $addition . $order, array('%'.$curatorName.'%'), $start, $limit);

        return $result;
    }

}
