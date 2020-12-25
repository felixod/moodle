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

$groupId = required_param('groupid', PARAM_INT);	
$returnUrl = optional_param('returnurl', $CFG->wwwroot.'/blocks/sibportfolio/index.php', PARAM_URL);

block_sibportfolio_require_login_system('/blocks/sibportfolio/handlers/forms/editgroup.php', array('groupid' => $groupId, 'returnurl' => $returnUrl));

$PAGE->set_title(get_string('key201', 'block_sibportfolio'));

if ($groupInfo = block_sibportfolio_check_access($groupId)) {

    $groupData = block_sibportfolio_groups::get_group_data_by_id($groupId);		
    $groupName = $groupInfo ? $groupInfo->name : get_string('key111', 'block_sibportfolio').' ('.$groupId.')';
        
    $PAGE->set_heading(get_string('key201', 'block_sibportfolio').' «'.$groupName.'»');
        
    $mform = new editGroupForm();
    
    if ($mform->is_cancelled()) {
    
        block_sibportfolio_redirect(new moodle_url($returnUrl));
        
    } else if ($fromform = $mform->get_data()) {

        block_sibportfolio_groups::edit_group_data(trim($fromform->speciality), trim($fromform->study), $groupId);
        block_sibportfolio_redirect(new moodle_url($returnUrl));
    
    } else {
            
        $PAGE->navbar->ignore_active();
        $PAGE->navbar->add(get_string('key41', 'block_sibportfolio'), new moodle_url('/blocks/sibportfolio/index.php', array('userid' => $USER->id)));
        $PAGE->navbar->add(get_string('key21', 'block_sibportfolio'), new moodle_url($returnUrl));
        $PAGE->navbar->add(get_string('key201', 'block_sibportfolio'), $PAGE->url);					
        $mform->set_data(array(
            'speciality' => isset($groupData->speciality) ? $groupData->speciality : null,
            'study' 	 => isset($groupData->study) ? $groupData->study : null,
            'returnurl'  => $returnUrl,
            'groupid' 	 => $groupId
        ));
        block_sibportfolio_form_render($mform);
        
    }

}

function block_sibportfolio_check_access($groupId) {
    $groupInfo = block_sibportfolio_groups::get_group_by_id($groupId);
    if (!is_siteadmin()) {	
        print_error('key67', 'block_sibportfolio');
        return false;
    } if (!$groupInfo) {
        print_error('key101', 'block_sibportfolio');
        return false;
    } else {
        return $groupInfo;
    }
}
