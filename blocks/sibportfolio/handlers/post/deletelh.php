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

require_once('../../lib.php');
require_once($CFG->dirroot.'/blocks/sibportfolio/libs/savior.php');

$lhId = required_param('id', PARAM_INT);

block_sibportfolio_require_login_system('/blocks/sibportfolio/handlers/post/deletelh.php', array('id' => $lhId));

if ($assignment = block_sibportfolio_check_access($lhId)) {
    
    if (data_submitted() && confirm_sesskey()) {
        block_sibportfolio_learning_history::delete_saved_assignment($lhId);
        block_sibportfolio_redirect(new moodle_url('/blocks/sibportfolio/moodlefiles.php', array('userid' => $assignment->userid)));
    } else {
        block_sibportfolio_display_confirm_form($assignment);
    }
    
} else block_sibportfolio_redirect(new moodle_url('/blocks/sibportfolio/index.php'));

function block_sibportfolio_check_access($lhId) {
    $assignment = block_sibportfolio_learning_history::get_saved_assignment($lhId);
    if (!$assignment) {
        return false;
    } else if (!is_siteadmin()) {
        print_error('key67', 'block_sibportfolio');
        return false;
    } else {
        return $assignment;
    }
}

function block_sibportfolio_display_confirm_form($assignment) {
    global $PAGE, $OUTPUT, $CFG, $USER;
    
    $userId = $assignment->userid;
    $userInfo = block_sibportfolio_users::get_user_by_id($userId);
    $userName = fullname($userInfo);
    
    $PAGE->set_title(get_string('key60', 'block_sibportfolio'));
    $PAGE->set_heading(get_string('key60', 'block_sibportfolio'));
    $PAGE->navbar->ignore_active();
    $PAGE->navbar->add(get_string('key41', 'block_sibportfolio').($USER->id != $userId ? ': '.$userName : ''), 
                       new moodle_url('/blocks/sibportfolio/index.php', array('userid' => $userId)));
    $PAGE->navbar->add(get_string('key40', 'block_sibportfolio'), new moodle_url('/blocks/sibportfolio/moodlefiles.php', array('userid' => $userId)));
    $PAGE->navbar->add(get_string('key231', 'block_sibportfolio'), $PAGE->url);
    
    echo $OUTPUT->header();
    echo '<div id="sibportfolio" class="block_sibportfolio_wrapper">';
    
    $render = new block_sibportfolio_render('views');
    echo $render->html_template('navigator.html', block_sibportfolio_get_navigator_model());
    
    echo '<div class="block_sibportfolio_content"><br />';
    echo $OUTPUT->confirm(get_string('key230', 'block_sibportfolio', $assignment), 
        $PAGE->url, $CFG->wwwroot.'/blocks/sibportfolio/moodlefiles.php?userid='.$userId);
    echo '</div>';
    
    echo '</div>';
    echo $OUTPUT->footer();
}
