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

class block_sibportfolio_learning_history {

    public static function synchronize($limit = 1, $ago = 15768000) {
        $users = self::get_users($limit, $ago);
        foreach ($users as $userid => $user) {
            self::synchronize_user($userid);
        }
    }

    public static function synchronize_user($userid) {
        global $CFG, $DB;			
        require_once("$CFG->libdir/gradelib.php");
        
        $resource = 'all_users';
        $lockfactory = \core\lock\lock_config::get_lock_factory('block_sibportfolio_learning_history_synchronize_user');
        $lock = $lockfactory->get_lock($resource, 10);			
        if (!$lock) return false;
        
        $assignsubmission_plugins = core_component::get_plugin_list('assignsubmission');
        $assignfeedback_plugins = core_component::get_plugin_list('assignfeedback');
        $courses = enrol_get_users_courses($userid, true, 'id, fullname', 'fullname ASC');
        foreach ($courses as $course)
        {
            $pcourseid = self::convert_mcourse_to_pcourse($course);
            $courseinfo = get_fast_modinfo($course);
            
            $modules = $courseinfo->get_instances_of('assign');
            foreach ($modules as $module)
            {
                $pcmid = self::convert_mcoursemodule_to_pcoursemodule($pcourseid, $module);
                $pmodinfo = self::get_cmodinfo($userid, $pcmid);
                if ($pmodinfo && $pmodinfo->lastupdate >= time() - 3600) continue;
                
                $modjson = new stdClass();
                $cm = context_module::instance($module->id);
                if (!has_capability('mod/assign:submit', $cm, $userid, false)) continue;
                if ($pmodinfo && $pmodinfo->modjson) $modjson = json_decode($pmodinfo->modjson);
                $params = array('assignment' => $module->instance, 'userid' => $userid);
                
                $response = false;
                $submission_files = null;
                $submissions = $DB->get_records('assign_submission', $params, 'attemptnumber DESC', '*');
                foreach ($submissions as $submission) { // попытки внутри задания
                    if (isset($assignsubmission_plugins['file'])) { // если плагин "ответ в виде файла" установлен
                        $files = self::get_modfiles($cm->id, 'assignsubmission_file', 'submission_files', $submission->id);
                        if (count($files) > 0) {
                            if (!isset($modjson->submissiontime) || $submission->timemodified <> $modjson->submissiontime) {
                                $submission_files = $files; // обновить файлы-попытки задания
                                $modjson->submissiontime = $submission->timemodified; // установить дату обновления
                            }
                            $response = true;
                            break;
                        }
                    }
                }
                
                if (!$response) continue; // задание без ответов - продолжать бессмысленно
                
                $gradefeedback_files = $gradefeedback_doc = array();
                $feedback_files = $feedback_doc = $comment_text = null;
                $grades = $DB->get_records('assign_grades', $params, 'attemptnumber DESC', '*');
                foreach($grades as $grade) {
                    if (isset($assignfeedback_plugins['file'])) {
                        $gradefeedback_files = self::get_modfiles($cm->id, 'assignfeedback_file', 'feedback_files', $grade->id);
                    }
                    if (isset($assignfeedback_plugins['doc'])) {
                        $gradefeedback_doc = self::get_modfiles($cm->id, 'assignfeedback_doc', 'feedback_doc', $grade->id);
                    }
                    if (isset($assignfeedback_plugins['comments'])) {
                        $comment = $DB->get_record('assignfeedback_comments', array('grade' => $grade->id));
                        if ($comment && trim($comment->commenttext) != false) {			
                            $comment_text = mb_strlen($comment->commenttext, 'UTF-8') > 100000 ? 
                                strip_tags(mb_substr($comment->commenttext, 0, 99997, 'UTF-8').'...') : $comment->commenttext; 
                        } else $comment_text = null;
                    }										
                    if (count($gradefeedback_files) > 0 || count($gradefeedback_doc) > 0 || isset($comment_text)) { // если есть какой-либо отзыв
                        if (!isset($modjson->feedbacktime) || $grade->timemodified > $modjson->feedbacktime) { // если отзывы новее
                            $feedback_files = $gradefeedback_files; // обновить отзыв-файл (даже если его нет)
                            $feedback_doc = $gradefeedback_doc; // обновить отзыв-документ (даже если его нет)
                            $modjson->feedbacktext = $comment_text; // обновить отзыв-комментарий (даже если его нет)
                            $modjson->feedbacktime = $grade->timemodified; // установить дату обновления
                        }
                        break;
                    }
                }
                
                $gradegrades = grade_get_grades($course->id, 'mod', 'assign', $module->instance, $userid);
                if (isset($gradegrades->items[0]->grades[$userid])) {
                    $gradeinfo = $gradegrades->items[0]->grades[$userid];
                    $modjson->grade = isset($gradeinfo->grade) ? $gradeinfo->str_long_grade : null;
                }
                
                $lhid = 0;
                $record = new stdClass();
                $record->lastupdate = time();
                $record->modjson = json_encode($modjson);
                if ($pmodinfo) { // обновление сведений о задании
                    $record->id = $lhid = $pmodinfo->id;
                    $DB->update_record('sibport_learning_history', $record);
                } else if (isset($submission_files)) { // иначе сохранение сведений о новом задании
                    $record->userid = $userid;
                    $record->moduleid = $pcmid;
                    $lhid = $DB->insert_record('sibport_learning_history', $record);
                } else continue;
                
                if (isset($submission_files)) self::save_modfiles($userid, 'sibport_assignsubmission', $lhid, $submission_files);
                if (isset($feedback_files)) self::save_modfiles($userid, 'sibport_assignfeedback', $lhid, $feedback_files);
                if (isset($feedback_doc)) self::save_modfiles($userid, 'sibport_assignfeedback_2', $lhid, $feedback_doc);
            }
            
            $modules = $courseinfo->get_instances_of('quiz');
            foreach ($modules as $module) 
            {
                $pcmid = self::convert_mcoursemodule_to_pcoursemodule($pcourseid, $module);
                $pmodinfo = self::get_cmodinfo($userid, $pcmid);
                if ($pmodinfo && $pmodinfo->lastupdate >= time() - 3600) continue;
                
                $modjson = new stdClass();
                $cm = context_module::instance($module->id);
                if (!has_capability('mod/quiz:attempt', $cm, $userid, false)) continue;
                if ($pmodinfo && $pmodinfo->modjson) $modjson = json_decode($pmodinfo->modjson);
                
                $gradegrades = grade_get_grades($course->id, 'mod', 'quiz', $module->instance, $userid);
                if (isset($gradegrades->items[0]->grades[$userid])) {
                    $gradeinfo = $gradegrades->items[0]->grades[$userid];
                    if (isset($gradeinfo->grade)) $modjson->grade = $gradeinfo->str_long_grade;
                }
                
                if (!isset($modjson->grade)) continue; // если тест не выполнен, перейти к следующему
                
                $record = new stdClass();
                $record->lastupdate = time();
                $record->modjson = json_encode($modjson);
                if ($pmodinfo) { // обновление сведений о тесте
                    $record->id = $pmodinfo->id;
                    $DB->update_record('sibport_learning_history', $record);
                } else { // иначе сохранение сведений о новом тесте
                    $record->userid = $userid;
                    $record->moduleid = $pcmid;
                    $DB->insert_record('sibport_learning_history', $record);
                }
            }
        }
        
        $lock->release();
        return true;
    }
    
    public static function get_learning_history($userid) {
        global $DB;
        
        $coursesinfo = array();
        $query = "SELECT lh.id, lh.modjson, cm.id AS modid, cm.type, cm.name AS modname, cm.active AS modactive, 
                         c.id AS cid, c.name AS cname, c.active AS cactive 
                    FROM {sibport_learning_history} lh 
              INNER JOIN {sibport_course_modules} cm ON (cm.id = lh.moduleid AND lh.userid = ?) 
              INNER JOIN {sibport_courses} c ON (c.id = cm.courseid) 
                ORDER BY cname, modname";
        $learning_history = $DB->get_records_sql($query, array('userid' => $userid));
        foreach ($learning_history as $record) {
            $modjson = json_decode($record->modjson);
            
            $courseid = $record->cid;
            if (!isset($coursesinfo[$courseid])) {
                $coursesinfo[$courseid] = array();
                $coursesinfo[$courseid]['fullname'] = $record->cname;
                $coursesinfo[$courseid]['active'] = $record->cactive;
                $coursesinfo[$courseid]['assigns'] = array();
                $coursesinfo[$courseid]['quiz'] = array(); 
            }
            
            $moduleid = $record->modid;
            switch ($record->type) {
                
                case 'assign':
                    $coursesinfo[$courseid]['assigns'][$moduleid] = array();
                    $coursesinfo[$courseid]['assigns'][$moduleid]['id'] = $record->id;
                    $coursesinfo[$courseid]['assigns'][$moduleid]['name'] = $record->modname;
                    $coursesinfo[$courseid]['assigns'][$moduleid]['active'] = $record->modactive;
                    $coursesinfo[$courseid]['assigns'][$moduleid]['finalgrade'] = isset($modjson->grade) ? $modjson->grade : null;
                    $coursesinfo[$courseid]['assigns'][$moduleid]['files'] = null;
                    $coursesinfo[$courseid]['assigns'][$moduleid]['feedback_files'] = null;
                    $coursesinfo[$courseid]['assigns'][$moduleid]['feedback_comment'] = null;
                    
                    $contextid = context_user::instance($userid)->id;
                    $submission_files = self::get_modfiles($contextid, 'block_sibportfolio', 'sibport_assignsubmission', $record->id);
                    $coursesinfo[$courseid]['assigns'][$moduleid]['files'] = $submission_files;
                    $feedback_doc = self::get_modfiles($contextid, 'block_sibportfolio', 'sibport_assignfeedback_2', $record->id);
                    $feedback_files = self::get_modfiles($contextid, 'block_sibportfolio', 'sibport_assignfeedback', $record->id);
                    $coursesinfo[$courseid]['assigns'][$moduleid]['feedback_files'] = array_merge($feedback_doc, $feedback_files);
                    $coursesinfo[$courseid]['assigns'][$moduleid]['feedback_comment'] = isset($modjson->feedbacktext) ? $modjson->feedbacktext : null;
                    break;
                    
                case 'quiz':
                    $coursesinfo[$courseid]['quiz'][$moduleid] = array();
                    $coursesinfo[$courseid]['quiz'][$moduleid]['id'] = $record->id;
                    $coursesinfo[$courseid]['quiz'][$moduleid]['name'] = $record->modname;
                    $coursesinfo[$courseid]['quiz'][$moduleid]['active'] = $record->modactive;
                    $coursesinfo[$courseid]['quiz'][$moduleid]['finalgrade'] = $modjson->grade;
                    break;
                    
            }
        }
        
        return $coursesinfo;
    }
    
    public static function get_saved_assignment($lhid) {
        global $DB;
        
        $query = "SELECT lh.id, lh.userid, lh.modjson, cm.type, cm.name AS modulename, 
                         c.name AS coursename, cm.active 
                    FROM {sibport_learning_history} lh 
              INNER JOIN {sibport_course_modules} cm ON (cm.id = lh.moduleid AND lh.id = ?) 
              INNER JOIN {sibport_courses} c ON (c.id = cm.courseid)";
        return $DB->get_record_sql($query, array('id' => $lhid));
    }
    
    public static function delete_saved_assignment($lhid) {
        global $DB;
        
        $assignment = $DB->get_record('sibport_learning_history', array('id' => $lhid));
        if ($assignment) {
            $fs = get_file_storage();
            $contextid = context_user::instance($assignment->userid)->id;
            $fs->delete_area_files($contextid, 'block_sibportfolio', 'sibport_assignfeedback', $lhid);
            $fs->delete_area_files($contextid, 'block_sibportfolio', 'sibport_assignfeedback_2', $lhid);
            $fs->delete_area_files($contextid, 'block_sibportfolio', 'sibport_assignsubmission', $lhid);
            $DB->delete_records('sibport_learning_history', array('id' => $lhid));
        }
    }

    public static function deactivate_course($courseid) {
        global $DB;
        
        $pcourse = $DB->get_record('sibport_courses', array('courseid' => $courseid, 'active' => 1));
        if ($pcourse) {
            $DB->execute('UPDATE {sibport_course_modules} SET active = 0 WHERE courseid = ? AND active = 1', array($pcourse->id));
            $DB->set_field('sibport_courses', 'active', 0, array('courseid' => $courseid, 'active' => 1));
        }
    }

    public static function deactivate_course_modules($courseid) {
        global $DB;
        
        $pcourse = $DB->get_record('sibport_courses', array('courseid' => $courseid, 'active' => 1));
        if ($pcourse) {
            $DB->execute('UPDATE {sibport_course_modules} SET active = 0 WHERE courseid = ? AND active = 1', array($pcourse->id));
        }
    }
    
    public static function deactivate_course_module($moduleid) {
        global $DB;
        
        $DB->set_field('sibport_course_modules', 'active', 0, array('moduleid' => $moduleid, 'active' => 1));
    }

    private static function get_users($limit = 1, $ago = 15768000) {
        global $DB;
        
        $time = time() - $ago;
        $start = get_config('block_sibportfolio', 'last_userid');
        
        $query = "SELECT id 
                    FROM {user} 
                   WHERE suspended = 0 AND deleted = 0 AND lastaccess > ? AND id > ? 
                ORDER BY id";
        $users = $DB->get_records_sql($query, array($time, $start), 0, $limit);
        if (!$users) $users = $DB->get_records_sql($query, array($time, 0), 0, $limit);
        
        set_config('last_userid', end($users)->id, 'block_sibportfolio');
        
        return $users;
    }
    
    private static function convert_mcourse_to_pcourse($course) {
        global $DB;
        
        $pcourse = $DB->get_record('sibport_courses', array('courseid' => $course->id, 'active' => 1));
        $pcourseid = $pcourse ? $pcourse->id : 0;
        if (!$pcourse) {
            $record = new stdClass();
            $record->courseid = $course->id;
            $record->name = $course->fullname;
            $record->active = 1;
            $pcourseid = $DB->insert_record('sibport_courses', $record);				
        } else if ($pcourse->name != $course->fullname) {
            $record = new stdClass();
            $record->id = $pcourse->id;
            $record->name = $course->fullname;
            $DB->update_record('sibport_courses', $record);				
        }
        
        return $pcourseid;
    }
    
    private static function convert_mcoursemodule_to_pcoursemodule($pcourseid, $cm) {
        global $DB;
        
        $params = array('courseid' => $pcourseid, 'moduleid' => $cm->id, 'type' => $cm->modname, 'active' => 1);
        $pcm = $DB->get_record('sibport_course_modules', $params);
        $pcmid = $pcm ? $pcm->id : 0;
        if (!$pcm) {
            self::deactivate_course_module($cm->id);
            $record = new stdClass();
            $record->courseid = $pcourseid;
            $record->moduleid = $cm->id;
            $record->type = $cm->modname;
            $record->name = $cm->name;
            $record->active = 1;
            $pcmid = $DB->insert_record('sibport_course_modules', $record);				
        } else if ($pcm->name != $cm->name) {
            $record = new stdClass();
            $record->id = $pcm->id;
            $record->name = $cm->name;
            $DB->update_record('sibport_course_modules', $record);				
        }
        
        return $pcmid;
    }
    
    private static function get_cmodinfo($userid, $pcmid) {
        global $DB;
        
        return $DB->get_record('sibport_learning_history', array('userid' => $userid, 'moduleid' => $pcmid));
    }
    
    private static function get_modfiles($contextid, $component, $filearea, $itemid) {
        global $DB;
        
        $select = "contextid = ? AND component = ? AND filearea = ? AND itemid = ? AND filename <> '.'";
        $params = array('contextid' => $contextid, 'component' => $component, 'filearea' => $filearea, 'itemid' => $itemid);
        return $DB->get_records_select('files', $select, $params, 'timecreated ASC');
    }
    
    private static function save_modfiles($userid, $filearea, $itemid, $files) {
        $fs = get_file_storage();
        $cu = context_user::instance($userid);
        $fs->delete_area_files($cu->id, 'block_sibportfolio', $filearea, $itemid);
        foreach ($files as $file) {
            $fileinfo = array(
                'contextid' => $cu->id, 
                'component' => 'block_sibportfolio',  
                'filearea' 	=> $filearea,    
                'itemid' 	=> $itemid,
                'filepath' 	=> $file->filepath, 
                'filename' 	=> $file->filename,
                'userid' 	=> $userid,
                'author' 	=> $file->author	
            );	
            $fs->create_file_from_storedfile($fileinfo, $file->id);
        }
    }

}
