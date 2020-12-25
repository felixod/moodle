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

$userId = required_param('userid', PARAM_INT);
$categoryId = optional_param('categoryid', 0, PARAM_INT);
$returnUrl = optional_param('returnurl', $CFG->wwwroot.'/blocks/sibportfolio/index.php', PARAM_URL);

$params = array(
    'userid' 	 => $userId, 
    'categoryid' => $categoryId, 
    'returnurl'  => $returnUrl
);
block_sibportfolio_require_login_system('/blocks/sibportfolio/handlers/forms/addfile.php', $params);

$PAGE->set_title(get_string('key103', 'block_sibportfolio'));

if (block_sibportfolio_check_access($userId)) {

    $action = is_siteadmin() || block_sibportfolio_is_sitecurator();
        
    $mform = new addFileForm(null, array('accepted' => $action));
    
    if ($mform->is_cancelled()) {
    
        block_sibportfolio_redirect(new moodle_url($returnUrl));
        
    } else if ($fromform = $mform->get_data()) {
        
        if (!(isset($_FILES['attachment']) && file_exists($_FILES['attachment']['tmp_name']))) die();
        
        $fs = get_file_storage();			
        $description = trim($fromform->descr);
        
        $request = new stdClass();
        $request->type = 'add';
        $request->accepted = $action;
        $request->userid = $userId;
        $request->description = $description;
        $request->category = $fromform->categoryid;
        $claimId = block_sibportfolio_claims::register_claim($request);
        
        if ($action) {
        
            $logData = array(
                'userid'      => $userId,
                'curatorid'   => $USER->id,
                'description' => $description,
                'filename'    => $_FILES['attachment']['name'],
                'claimtype'   => 0,
                'claimstatus' => 1,
                'timecreated' => time(),
                'usercomment' => null,
                'comment'     => get_string_manager()->get_string('key105', 'block_sibportfolio', null, $CFG->lang),//get_string('key105', 'block_sibportfolio'),
                'itemid'      => $claimId
            );
            block_sibportfolio_claims::write_log($logData);
            
        }	
        
        $userInfo = block_sibportfolio_users::get_user_by_id($USER->id);
        $fileinfo = array(
            'contextid' => context_user::instance($userId)->id, 
            'component' => 'block_sibportfolio',  
            'filearea'  => 'sibport_files',    
            'itemid'    => $claimId, 
            'filepath'  => '/', 
            'filename'  => $_FILES['attachment']['name'],
            'userid'    => $userId,
            'author'    => fullname($userInfo)		
        ); 
        $fs->create_file_from_pathname($fileinfo, $_FILES['attachment']['tmp_name']);
        unlink($_FILES['attachment']['tmp_name']);					
        
        if ($action) {
            $event = \block_sibportfolio\event\file_added::create(array(
                'objectid'	    => $claimId,
                'relateduserid' => $userId
            ));
            $event->trigger();
        } else {
            $event = \block_sibportfolio\event\claim_add_created::create(array(
                'objectid' => $claimId
            ));
            $event->trigger();
        }

        block_sibportfolio_redirect(new moodle_url($returnUrl));	
        
    } else { 

        if (isset($_FILES['attachment']) && file_exists($_FILES['attachment']['tmp_name'])) unlink($_FILES['attachment']['tmp_name']);
        
        $userName = null;
        if ($USER->id != $userId) {
            $userInfo = block_sibportfolio_users::get_user_by_id($userId);
            $userName = fullname($userInfo);
        }
        $header = $action ? get_string('key103', 'block_sibportfolio') : get_string('key104', 'block_sibportfolio');
        $PAGE->set_heading($header);
        $PAGE->navbar->ignore_active();
        $PAGE->navbar->add(get_string('key41', 'block_sibportfolio').(isset($userName) ? ': '.$userName : ''), 
                           new moodle_url('/blocks/sibportfolio/index.php', array('userid' => $userId)));
        if ($categoryId > 0) {
            $categoryInfo = block_sibportfolio_files::get_file_category_by_id($categoryId);
            if ($categoryInfo) $PAGE->navbar->add($categoryInfo->name, new moodle_url($returnUrl));
        }
        $PAGE->navbar->add($header, $PAGE->url);	
        $mform->set_data(array(
            'userid' 	 => $userId, 
            'categoryid' => $categoryId, 
            'returnurl'  => $returnUrl
        ));
        block_sibportfolio_form_render($mform);
        
    }

}

function block_sibportfolio_check_access($userId) {
    global $USER, $CFG;
    $userInfo = block_sibportfolio_users::get_user_by_id($userId);
    $maxClaims = block_sibportfolio_claims::get_max_claims();
    if (!$userInfo || $userInfo->id == 1 || $userInfo->deleted == 1 || $userInfo->suspended == 1) {
        block_sibportfolio_notification($userInfo && $userInfo->deleted ? get_string('userdeleted') : get_string('invaliduser', 'error'));
        return false;			
    } else if (!(is_siteadmin() || block_sibportfolio_is_curator($userId) || $USER->id == $userId)) {	
        print_error('key108', 'block_sibportfolio');
        return false;
    } else if (!is_siteadmin() && !block_sibportfolio_is_sitecurator() && block_sibportfolio_claims::get_count_wait_claims_by_user($userId) >= $maxClaims) {
        $message = $maxClaims == 0 ? get_string('key109', 'block_sibportfolio') : get_string('key110', 'block_sibportfolio', $maxClaims);
        $returnUrl = optional_param('returnurl', $CFG->wwwroot.'/blocks/sibportfolio/index.php', PARAM_URL);	
        block_sibportfolio_print_message($message, new moodle_url($returnUrl));
        return false;			
    } else {
        return true;
    }
}
