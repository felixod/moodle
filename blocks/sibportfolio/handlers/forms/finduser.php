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

require_once ('../../lib.php');
require_once($CFG->dirroot.'/blocks/sibportfolio/handlers/forms/forms.php');

$returnUrl = optional_param('returnurl', $CFG->wwwroot.'/blocks/sibportfolio/index.php', PARAM_URL);

block_sibportfolio_require_login_system('/blocks/sibportfolio/handlers/forms/finduser.php', array('returnurl' => $returnUrl));

$PAGE->set_title(get_string('key19', 'block_sibportfolio'));
$PAGE->set_heading(get_string('key19', 'block_sibportfolio'));
$PAGE->navbar->ignore_active();
$PAGE->navbar->add(get_string('key41', 'block_sibportfolio'), new moodle_url('/blocks/sibportfolio/index.php', array('userid' => $USER->id)));
$PAGE->navbar->add(get_string('key19', 'block_sibportfolio'), $PAGE->url);	
    
$customData = array('findparam' => optional_param('findparam', '', PARAM_RAW_TRIMMED),
                    'myusers'   => optional_param('myusers', false, PARAM_BOOL),
                    'enfilter'	=> !is_siteadmin() && block_sibportfolio_is_sitecurator());
$mform = new findUserForm(null, $customData);

if ($mform->is_cancelled()) {

    block_sibportfolio_redirect(new moodle_url($returnUrl));
    
} else if ($fromform = $mform->get_data()) {
    
    if (isset($fromform->myusers)) $SESSION->block_sibportfolio_finduser = $fromform->myusers;
    block_sibportfolio_form_render($mform);
    
} else {

    $option = isset($SESSION->block_sibportfolio_finduser) ? $SESSION->block_sibportfolio_finduser : 0;
    $mform->set_data(array('returnurl' => $returnUrl, 'myusers' => $option));
    block_sibportfolio_form_render($mform);
    
}
