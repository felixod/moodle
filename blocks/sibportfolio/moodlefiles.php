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

require_once('lib.php');
require_once($CFG->dirroot.'/blocks/sibportfolio/libs/savior.php');
require_once($CFG->dirroot . '/comment/lib.php');

$userId = optional_param('userid', $USER->id, PARAM_INT);

block_sibportfolio_require_login_system('/blocks/sibportfolio/moodlefiles.php', array('userid' => $userId));

$userInfo = block_sibportfolio_users::get_user_by_id($userId);
block_sibportfolio_users::user_exist($userInfo);

$viewer = has_capability('block/sibportfolio:viewer', context_system::instance());
//FELIXOD Смотреть могут все!
//if (!($viewer || block_sibportfolio_is_curator($userId) || $USER->id == $userId)) {		
//    $PAGE->set_title(get_string('key60', 'block_sibportfolio'));
//    block_sibportfolio_print_message(get_string('key61', 'block_sibportfolio'), 
//                                     new moodle_url('/blocks/sibportfolio/index.php', array('userid' => $userId)));
//}

$userName = fullname($userInfo);

$learningHistory = block_sibportfolio_learning_history::get_learning_history($userId);
$learningHistory = block_sibportfolio_improvement_tree_courses_moodle($learningHistory);

$refreshUrl = new moodle_url($CFG->wwwroot . '/blocks/sibportfolio/handlers/post/refreshlh.php', array('userid' => $userId));

$render = new block_sibportfolio_render('views');	
$render->add_script('scripts/jquery-1.11.3.min.js');
$render->set_title(get_string('key60', 'block_sibportfolio'));
$render->set_heading(get_string('key60', 'block_sibportfolio'));
$render->add_navigation(get_string('key41', 'block_sibportfolio').($USER->id != $userId ? ': '.$userName : ''), 
                        new moodle_url('/blocks/sibportfolio/index.php', array('userid' => $userId)));
$render->add_navigation(get_string('key40', 'block_sibportfolio'), $PAGE->url);
$render->add_views(array(
    'navigator.html'   => block_sibportfolio_get_navigator_model(),
    'moodlefiles.html' => array (
        'wwwroot'         => $CFG->wwwroot,
        'user_name' 	  => $USER->id != $userId ? '('.$userName.')' : null,
        'moodle_files'    => $learningHistory,
        'find' 			  => count($learningHistory) > 0,
        'is_admin' 		  => is_siteadmin(),
        'sync_enabled' 	  => block_sibportfolio_get_config_sync(),
        'header_label' 	  => get_string('key40', 'block_sibportfolio'),
        'task_label' 	  => get_string('key63', 'block_sibportfolio'),
        'test_label' 	  => get_string('key223', 'block_sibportfolio'),
        'files_label' 	  => get_string('key64', 'block_sibportfolio'),
        'grade_label' 	  => get_string('key65', 'block_sibportfolio'),
        'feedback_label'  => get_string('key66', 'block_sibportfolio'),
        'not_found_label' => get_string('key85', 'block_sibportfolio'),
        'assigns_label'   => get_string('key224', 'block_sibportfolio'),
        'quiz_label' 	  => get_string('key225', 'block_sibportfolio'),
        'assign_icon' 	  => $OUTPUT->pix_icon('icon', '', 'assign', array('class' => 'icon')),
        'quiz_icon' 	  => $OUTPUT->pix_icon('icon', '', 'quiz', array('class' => 'icon')),
        'remove_icon' 	  => $OUTPUT->pix_icon('remove', get_string('key231', 'block_sibportfolio'), 'block_sibportfolio'),
        'refresh_button'  => $OUTPUT->single_button($refreshUrl, get_string('key234', 'block_sibportfolio'), 'post')
    )
));
$render->display();

$event = \block_sibportfolio\event\category_viewed::create(array(
    'objectid' 		=> 0,
    'relateduserid' => $userId
));
$event->trigger();

function block_sibportfolio_improvement_tree_courses_moodle($coursesData) {
    
    foreach ($coursesData as $courseId => $courseData) {
        foreach ($courseData['assigns'] as $assignId => $assignData) {
            $grade = $coursesData[$courseId]['assigns'][$assignId]['finalgrade'];
            if (!$grade) $coursesData[$courseId]['assigns'][$assignId]['finalgrade'] = 
                html_writer::tag('span', get_string('key87', 'block_sibportfolio'), array('style' => 'color: darkred;'));
            foreach ($assignData['files'] as $index => $file) {
                $coursesData[$courseId]['assigns'][$assignId]['files'][$index] = block_sibportfolio_files::get_sibport_file_link($file);
            }
            
            $feedback_comment = $coursesData[$courseId]['assigns'][$assignId]['feedback_comment'];
            $feedback_files = $coursesData[$courseId]['assigns'][$assignId]['feedback_files'];
            if (!$feedback_comment && count($feedback_files) == 0) {
                $coursesData[$courseId]['assigns'][$assignId]['feedback_comment'] = 
                    html_writer::tag('span', get_string('key222', 'block_sibportfolio'), array('style' => 'color: darkred;'));
            } else {
                $coursesData[$courseId]['assigns'][$assignId]['feedback_comment'] = $feedback_comment;
                foreach ($feedback_files as $index => $file) {
                    $coursesData[$courseId]['assigns'][$assignId]['feedback_files'][$index] = block_sibportfolio_files::get_sibport_file_link($file);
                }
            }
        }
    }
    
    return $coursesData;
    
}
