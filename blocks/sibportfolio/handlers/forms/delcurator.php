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

block_sibportfolio_require_login_system('/blocks/sibportfolio/handlers/forms/delcurator.php', array('groupid' => $groupId, 'returnurl' => $returnUrl));

$PAGE->set_title(get_string('key168', 'block_sibportfolio'));

if (block_sibportfolio_check_access($groupId)) {
    
    $curatorId = block_sibportfolio_users::get_curator_by_group($groupId)->curatorid;
    
    $groupInfo = block_sibportfolio_groups::get_group_by_id($groupId);
    $curatorInfo = block_sibportfolio_users::get_user_by_id($curatorId);
    $name = $curatorInfo ? fullname($curatorInfo) : get_string('key111', 'block_sibportfolio').' ('.$curatorId.')';
    $groupName = $groupInfo ? $groupInfo->name : get_string('key111', 'block_sibportfolio').' ('.$groupId.')';
        
    $PAGE->set_heading(get_string('key168', 'block_sibportfolio'));
    
    $formData = array('userName' => $name, 'groupName' => $groupName);
    $customData = array('formdata' => $formData);
    $mform = new delCuratorForm(null, $customData);
    
    if ($mform->is_cancelled()) {
    
        block_sibportfolio_redirect(new moodle_url($returnUrl));
        
    } else if ($mform->get_data()) {

        block_sibportfolio_users::delete_curator($groupId);
        block_sibportfolio_redirect(new moodle_url($returnUrl));
    
    } else {
                    
        $PAGE->navbar->ignore_active();
        $PAGE->navbar->add(get_string('key41', 'block_sibportfolio'), new moodle_url('/blocks/sibportfolio/index.php', array('userid' => $USER->id)));
        $PAGE->navbar->add(get_string('key21', 'block_sibportfolio'), new moodle_url($returnUrl));
        $PAGE->navbar->add(get_string('key168', 'block_sibportfolio'), $PAGE->url);	
        $mform->set_data(array(
            'returnurl' => $returnUrl,
            'groupid'   => $groupId
        ));
        block_sibportfolio_form_render($mform);
        
    }

}

function block_sibportfolio_check_access($groupId) {
    $groupInfo = block_sibportfolio_groups::get_group_by_id($groupId);
    $curator = block_sibportfolio_users::get_curator_by_group($groupId);
    if (!is_siteadmin()) {	
        print_error('key67', 'block_sibportfolio');
        return false;
    } if (!$groupInfo) {
        print_error('key101', 'block_sibportfolio');
        return false;
    } else if (!$curator) {
        print_error('key113', 'block_sibportfolio');
        return false;
    } else {
        return true;
    }
}
