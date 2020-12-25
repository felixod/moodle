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

block_sibportfolio_require_login_system('/blocks/sibportfolio/handlers/forms/addcurator.php', array('groupid' => $groupId, 'returnurl' => $returnUrl));

$PAGE->set_title(get_string('key99', 'block_sibportfolio'));
//$PAGE->requires->js('/blocks/sibportfolio/scripts/jquery-1.11.3.min.js', true);

if (block_sibportfolio_check_access($groupId)) {

    $groupInfo = block_sibportfolio_groups::get_group_by_id($groupId);
    
    $PAGE->set_heading(get_string('key100', 'block_sibportfolio').$groupInfo->name);
    
    $customData = array('user' => optional_param('user', '', PARAM_RAW_TRIMMED));
    $mform = new addCuratorForm(null, $customData);
    if ($mform->is_cancelled()) {

        block_sibportfolio_redirect(new moodle_url($returnUrl));
        
    } else if (optional_param('findbutton', false, PARAM_BOOL) && data_submitted()) {

        $mform->get_data();
        block_sibportfolio_show_form($mform, $returnUrl);
        
    } else if (optional_param('submitbutton', false, PARAM_BOOL) && $mform->get_data()) {
    
        $curatorId = required_param('curator', PARAM_INT);
        block_sibportfolio_users::add_curator($curatorId, $groupId);
        $claims = block_sibportfolio_claims::get_wait_claims($curatorId)->claims;
        foreach ($claims as $claim) {
            $logData = array(
                'userid' 	  => $curatorId,
                'curatorid'   => $curatorId,
                'description' => $claim->claimtype == 1 ? $claim->description : $claim->description2,
                'filename'    => block_sibportfolio_files::get_file_by_claim_id($claim->id)->filename,
                'claimtype'   => $claim->claimtype,
                'claimstatus' => 1,
                'timecreated' => time(),
                'usercomment' => $claim->usercomment,
                'comment' 	  => get_string_manager()->get_string('key97', 'block_sibportfolio', null, $CFG->lang),
                'itemid'	  => $claim->id
            );
            block_sibportfolio_claims::write_log($logData);
        
            block_sibportfolio_claims::handle_claim($claim);
        }
        block_sibportfolio_redirect(new moodle_url($returnUrl));
    
    } else {

        $mform->set_data(array(
            'returnurl' => $returnUrl,
            'groupid'   => $groupId
        ));
        block_sibportfolio_show_form($mform, $returnUrl);
        
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
    } else if ($curator) {
        print_error('key102', 'block_sibportfolio');
        return false;
    } else {
        return true;
    }
}

function block_sibportfolio_show_form($form, $returnUrl) {
    global $PAGE, $CFG, $USER;
    $PAGE->navbar->ignore_active();
    $PAGE->navbar->add(get_string('key41', 'block_sibportfolio'), new moodle_url('/blocks/sibportfolio/index.php', array('userid' => $USER->id)));
    $PAGE->navbar->add(get_string('key21', 'block_sibportfolio'), new moodle_url($returnUrl));
    $PAGE->navbar->add(get_string('key99', 'block_sibportfolio'), $PAGE->url);
    $render = new block_sibportfolio_render('views');
    $html = $render->html_template('addcurator-ajax.html', array('wwwroot' => $CFG->wwwroot));
    block_sibportfolio_form_render($form, $html);
}
