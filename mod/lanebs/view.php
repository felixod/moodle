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
 * Prints an instance of mod_lanebs.
 *
 * @package     mod_lanebs
 * @copyright   2020 Senin Yurii <katorsi@mail.ru>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

// Course_module ID, or
$id = optional_param('id', 0, PARAM_INT);

// ... module instance id.
$l  = optional_param('l', 0, PARAM_INT);

if ($id) {
    $cm             = get_coursemodule_from_id('lanebs', $id, 0, false, MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('lanebs', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($l) {
    $moduleinstance = $DB->get_record('lanebs', array('id' => $n), '*', MUST_EXIST);
    $course         = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm             = get_coursemodule_from_instance('lanebs', $moduleinstance->id, $course->id, false, MUST_EXIST);
} else {
    print_error(get_string('missingidandcmid', 'mod_lanebs'));
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

$event = \mod_lanebs\event\course_module_viewed::create(array(
    'objectid' => $moduleinstance->id,
    'context' => $modulecontext
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('lanebs', $moduleinstance);
$event->trigger();

$settings = get_config("lanebs");
if (isset($settings->token) && !empty($settings->token)) {
    $_SESSION['subscriberToken'] = $settings->token;
}
else if (isset($USER->profile['mod_lanebs_token']) && !empty($USER->profile['mod_lanebs_token'])) {
    $_SESSION['subscriberToken'] = $USER->profile['mod_lanebs_token'];
}
$PAGE->requires->js_call_amd('mod_lanebs/modal_search_handle', 'init');

$PAGE->requires->js_call_amd('mod_lanebs/view_button', 'init', array('title' => 'Просмотр'));

$PAGE->set_url('/mod/lanebs/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();

echo
        '<div class="d-flex">'.
            '<div style="flex:0.3;">'.$moduleinstance->intro.'</div>'.
            '<div class="item mt-5" style="flex:0.5;" data-id="'.$moduleinstance->content.'">'.
                '<div class="row d-flex">'.
                    '<div style="margin-left:auto;margin-right:auto;flex:0.3;">'.
                        '<button style="color:#174c8d;background-color:white;border-color:#4285f4;" class="btn btn-lg btn-info" data-action="book_modal">Открыть</button>'.
                    '</div>'.
                '</div>'.
                '<div class="row d-flex justify-content-center">'.
                    '<div class="justify-content-center d-flex" style="margin-right:auto;flex:0.6;margin-left:140px;">'.
                        '<p class="mt-4" style="/*margin-right:113px;*/">'.$moduleinstance->content_name.'</p>'.
                    '</div>'.
                '</div>'.
            '</div>'.
         '</div>';

echo $OUTPUT->footer();
