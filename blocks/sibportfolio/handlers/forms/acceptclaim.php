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

block_sibportfolio_require_login_system('/blocks/sibportfolio/handlers/forms/acceptclaim.php', array('claimid' => $claimId, 'returnurl' => $returnUrl));

$PAGE->set_title(get_string('key96', 'block_sibportfolio'));

if ($claim = block_sibportfolio_check_access($claimId)) {

    $userId = $claim->userid;
    
    $header = '';
    switch ($claim->claimtype) {
        case 0:
            $header = get_string('key2', 'block_sibportfolio');
            break;
        case 1:
            $header = get_string('key6', 'block_sibportfolio');
            break;
        case 2:
            $header = get_string('key15', 'block_sibportfolio');
            break;
    }

    $PAGE->set_heading($header);
        
    $customData = array('claim' => $claim);	
    $mform = new acceptClaimForm(null, $customData);
    
    if ($mform->is_cancelled()) {

        block_sibportfolio_fredirect($userId);	
        
    } else if ($fromform = $mform->get_data()) {
        
        $logData = array(
            'userid'	  => $userId,
            'curatorid'   => $USER->id,
            'filename'    => block_sibportfolio_files::get_file_by_claim_id($claimId)->filename,
            'claimtype'   => $claim->claimtype,
            'claimstatus' => 1,
            'timecreated' => time(),
            'usercomment' => $claim->usercomment,
            'comment' 	  => get_string_manager()->get_string('key97', 'block_sibportfolio', null, $CFG->lang),
            'itemid' 	  => $claimId
        );
        
        switch ($claim->claimtype) {
        
            case 0:
                $description = trim($fromform->descr);
                $logData['description'] = $description;
                block_sibportfolio_claims::write_log($logData); // информация о добавляемом файле
                
                block_sibportfolio_claims::handle_claim($claim, $description, $fromform->category);
                break;
                
            case 1:
                $logData['description'] = $claim->description;
                block_sibportfolio_claims::write_log($logData); // информация о старом файле

                block_sibportfolio_claims::handle_claim($claim, trim($fromform->descr), $fromform->category);					
                break;
                
            case 2:
                $logData['description'] = $claim->description2;
                block_sibportfolio_claims::write_log($logData); // информация об удаляемом файле
                
                block_sibportfolio_claims::handle_claim($claim);
                break;
                
        }
        
        $event = \block_sibportfolio\event\claim_accepted::create(array(
            'objectid' 		=> $claimId,
            'relateduserid' => $userId,
            'other' 		=> array('claimtype' => $claim->claimtype)
        ));
        $event->trigger();

        block_sibportfolio_fredirect($userId);
        
    } else { 
        
        $userName = null;
        if ($USER->id != $userId) {
            $userInfo = block_sibportfolio_users::get_user_by_id($userId);
            $userName = fullname($userInfo);
        }
        $PAGE->navbar->ignore_active();
        $PAGE->navbar->add(get_string('key41', 'block_sibportfolio').(isset($userName) ? ': '.$userName : ''), 
                           new moodle_url('/blocks/sibportfolio/index.php', array('userid' => $userId)));
        $PAGE->navbar->add(get_string('key70', 'block_sibportfolio'), new moodle_url($returnUrl));
        $PAGE->navbar->add($header, new moodle_url('/blocks/sibportfolio/claiminfo.php', array('claimid' => $claimId, 'returnurl' => $returnUrl)));
        $PAGE->navbar->add(get_string('key96', 'block_sibportfolio'), $PAGE->url);
        $mform->set_data(array(
            'claimid'   => $claimId, 
            'descr' 	=> $claim->description2,
            'category'  => $claim->category2,
            'returnurl' => $returnUrl
        ));		
        block_sibportfolio_form_render($mform);
        
    }

} else block_sibportfolio_redirect(new moodle_url($returnUrl));

function block_sibportfolio_check_access($claimId) {
    $claim = block_sibportfolio_claims::get_claim_by_id($claimId);
    if (!$claim || !isset($claim->claimtype)) {
        //print_error('key28', 'block_sibportfolio');
        return false;
    } else if (!(is_siteadmin() || block_sibportfolio_is_curator($claim->userid) || block_sibportfolio_i_am_curator($claim->userid))) {
        print_error('key98', 'block_sibportfolio');
        return false;
    } else {
        return $claim;
    }
}

function block_sibportfolio_fredirect($userId) {
    global $CFG;
    if (block_sibportfolio_claims::get_count_wait_claims_by_user($userId) == 0) {
        block_sibportfolio_redirect(new moodle_url('/blocks/sibportfolio/handleclaims.php'));
    } else {
        $returnUrl = optional_param('returnurl', $CFG->wwwroot.'/blocks/sibportfolio/index.php', PARAM_URL);
        block_sibportfolio_redirect(new moodle_url($returnUrl));
    }
}
