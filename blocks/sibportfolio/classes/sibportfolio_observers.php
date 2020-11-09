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

namespace block_sibportfolio;
defined('MOODLE_INTERNAL') || die();

class sibportfolio_observers {

    public static function cohort_deleted($event) {
        global $DB;
        
        $cohortId = $event->objectid;
        $DB->delete_records('sibport_group_data', array('groupid' => $cohortId));
        $DB->delete_records('sibport_users', array('groupid' => $cohortId));
    }
    
    public static function user_deleted($event) {
        global $DB;
        
        $userId = $event->objectid;
        $DB->execute('UPDATE {sibport_group_data} SET curatorid = NULL WHERE curatorid = ?', array($userId));
        $DB->delete_records('sibport_users', array('userid' => $userId));			
        $DB->delete_records('sibport_files', array('userid' => $userId));
        $DB->delete_records('sibport_claim_log', array('userid' => $userId));
        $DB->delete_records('sibport_learning_history', array('userid' => $userId));
    }
    
    public static function course_deleted($event) {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/sibportfolio/libs/savior.php');

        \block_sibportfolio_learning_history::deactivate_course($event->objectid);
    }
    
    public static function course_content_deleted($event) {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/sibportfolio/libs/savior.php');

        \block_sibportfolio_learning_history::deactivate_course_modules($event->objectid);
    }
    
    public static function course_module_deleted($event) {
        global $CFG;
        require_once($CFG->dirroot.'/blocks/sibportfolio/libs/savior.php');

        \block_sibportfolio_learning_history::deactivate_course_module($event->objectid);
    }

}
