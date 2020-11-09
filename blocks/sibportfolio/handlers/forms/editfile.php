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

$claimId = required_param('claimid', PARAM_INT);	
$returnUrl = optional_param('returnurl', $CFG->wwwroot.'/blocks/sibportfolio/index.php', PARAM_URL);

block_sibportfolio_require_login_system('/blocks/sibportfolio/handlers/forms/editfile.php', array('claimid' => $claimId, 'returnurl' => $returnUrl));

$PAGE->set_title(get_string('key120', 'block_sibportfolio'));

if ($claim = block_sibportfolio_check_access($claimId)) {

    $userId = $claim->userid;

    $action = is_siteadmin() || block_sibportfolio_is_sitecurator();
    
    $accepted = is_siteadmin() || block_sibportfolio_is_curator($userId) || block_sibportfolio_i_am_curator($userId);
    $customData = array('claimid' => $claimId, 'accepted' => $accepted);
    $mform = new editFileForm(null, $customData);
    
    if ($mform->is_cancelled()) {
    
        block_sibportfolio_redirect(new moodle_url($returnUrl));
        
    } else if ($fromform = $mform->get_data()) {
        
        if ($action) {

            $logData = array(
                'userid' 	  => $userId,
                'curatorid'   => $USER->id,
                'description' => $claim->description,
                'filename'    => block_sibportfolio_files::get_file_by_claim_id($claimId)->filename,
                'claimtype'   => 1,
                'claimstatus' => 1,
                'timecreated' => time(),
                'usercomment' => isset($fromform->cause) && trim($fromform->cause) != false ? $fromform->cause : null,
                'comment' 	  => get_string_manager()->get_string('key122', 'block_sibportfolio', null, $CFG->lang),
                'itemid' 	  => $claimId
            );
            block_sibportfolio_claims::write_log($logData); // информация о старом файле
        
        }
    
        $request = new stdClass();
        $request->type = 'edit';
        $request->accepted = $action;
        $request->claimid = $claimId;
        $request->old_description = $claim->description;
        $request->old_category = $claim->category;
        $request->new_description = trim($fromform->descr);
        $request->new_category = $fromform->category;
        $request->comment = isset($fromform->cause) ? trim($fromform->cause) : null;
        block_sibportfolio_claims::register_claim($request);
                    
        if ($action) {
            $event = \block_sibportfolio\event\file_updated::create(array(
                'objectid' 		=> $claimId,
                'relateduserid' => $userId
            ));
            $event->trigger();
        } else {
            $event = \block_sibportfolio\event\claim_edit_created::create(array(
                'objectid' => $claimId
            ));
            $event->trigger();
        }			
        
        block_sibportfolio_redirect(new moodle_url($returnUrl));
        
    } else { 
        
        $userName = null;
        if ($USER->id != $userId) {
            $userInfo = block_sibportfolio_users::get_user_by_id($userId);
            $userName = fullname($userInfo);
        }
        $categoryInfo = block_sibportfolio_files::get_file_category_by_id($claim->category);
        $header = $action ? get_string('key120', 'block_sibportfolio') : get_string('key121', 'block_sibportfolio');
        $PAGE->set_heading($header);
        $PAGE->navbar->ignore_active();
        $PAGE->navbar->add(get_string('key41', 'block_sibportfolio').(isset($userName) ? ': '.$userName : ''), 
                           new moodle_url('/blocks/sibportfolio/index.php', array('userid' => $userId)));
        $PAGE->navbar->add($categoryInfo->name, new moodle_url($returnUrl));
        $PAGE->navbar->add($header, $PAGE->url);
        $mform->set_data(array(
            'claimid' 	=> $claimId, 
            'category'  => $claim->category,
            'descr' 	=> $claim->description,
            'returnurl' => $returnUrl
        ));		
        block_sibportfolio_form_render($mform);
        
    }

}

function block_sibportfolio_check_access($claimId) {
    global $USER, $CFG;
    $claim = block_sibportfolio_claims::get_claim_by_id($claimId);
    $maxClaims = block_sibportfolio_claims::get_max_claims();
    if (!$claim) {
        print_error('key117', 'block_sibportfolio');
        return false;
    } else if (!(is_siteadmin() || block_sibportfolio_is_curator($claim->userid) || $USER->id == $claim->userid)) {	
        print_error('key123', 'block_sibportfolio');
        return false;
    } else if (isset($claim->claimtype)) {
        print_error('key119', 'block_sibportfolio');
        return false;
    } else if (!is_siteadmin() && !block_sibportfolio_is_sitecurator() && block_sibportfolio_claims::get_count_wait_claims_by_user($claim->userid) >= $maxClaims) {
        $message = $maxClaims == 0 ? get_string('key109', 'block_sibportfolio') : get_string('key110', 'block_sibportfolio', $maxClaims);
        $returnUrl = optional_param('returnurl', $CFG->wwwroot.'/blocks/sibportfolio/index.php', PARAM_URL);		
        block_sibportfolio_print_message($message, new moodle_url($returnUrl));
        return false;
    } else {
        return $claim;
    }
}
