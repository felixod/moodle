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

$userId = optional_param('userid', $USER->id, PARAM_INT);

$page = optional_param('page', 1, PARAM_INT);
if ($page <= 0) $page = 1;

block_sibportfolio_require_login_system('/blocks/sibportfolio/logclaims.php', array('userid' => $userId, 'page' => $page));

$userInfo = block_sibportfolio_users::get_user_by_id($userId);
block_sibportfolio_users::user_exist($userInfo);

if (!(is_siteadmin() || block_sibportfolio_is_curator($userId) || $USER->id == $userId)) {
    print_error('key71', 'block_sibportfolio');
}

$userName = fullname($userInfo);

$result = block_sibportfolio_get_data($userId, $page);
$result['user_id'] = $userId;
$result['type_label'] = get_string('key55', 'block_sibportfolio');
$result['status_label'] = get_string('key56', 'block_sibportfolio');
$result['curator_label'] = get_string('key57', 'block_sibportfolio');
$result['usercomment_label'] = get_string('key74', 'block_sibportfolio');
$result['comment_label'] = get_string('key58', 'block_sibportfolio');
$result['more_label'] = get_string('key76', 'block_sibportfolio');
$result['wwwroot'] = $CFG->wwwroot;

if (!block_sibportfolio_is_ajax_request()) {

    $result['find'] = count($result['logs']) > 0;
    if ($USER->id != $userId) $result['user_name'] = '('.$userName.')';
    $result['header_label'] = get_string('key51', 'block_sibportfolio');
    $result['date_label'] = get_string('key53', 'block_sibportfolio');
    $result['desc_label'] = get_string('key3', 'block_sibportfolio');
    $result['filename_label'] = get_string('file');
    $result['not_found_label'] = get_string('key59', 'block_sibportfolio');
            
    $render = new block_sibportfolio_render('views');
    $render->add_style('css/styles-plugin.css');
    $render->add_script('scripts/jquery-1.11.3.min.js');
    $render->add_script('scripts/handlebars-v3.0.3.js');
    $render->add_script('scripts/handlebars-jquery.js');
    $render->set_title(get_string('key20', 'block_sibportfolio'));
    $render->set_heading(get_string('key20', 'block_sibportfolio'));
    $render->add_navigation(get_string('key41', 'block_sibportfolio').($USER->id != $userId ? ': '.$userName : ''), 
                            new moodle_url('/blocks/sibportfolio/index.php', array('userid' => $userId)));
    $render->add_navigation(get_string('key20', 'block_sibportfolio'), $PAGE->url);
    $render->add_views(array(
        'navigator.html' 	  => block_sibportfolio_get_navigator_model(),
        'logclaims.html' 	  => $result,
        'logclaims-ajax.html' => block_sibportfolio_get_ajax_model() // закомментировать для отключения ajax
    ));
    $render->display();

} else {
    
    header('Content-Type: application/json');
    echo json_encode($result);		
    
}

function block_sibportfolio_get_data($userId, $page) {
    $logData = block_sibportfolio_claims::get_logclaim($userId, $page);
    $log = $logData->log;
    $page = $logData->page;
    $totalPages = $logData->total;
    
    $resultLog = array();
    foreach ($log as $item) {
        $itemLog = new stdClass();
        $itemLog->desc = $item->description;
        
        $itemLog->bigname = false;
        if ($item->filename) {
            $itemLog->filename = $item->filename;
            $itemLog->bigname = mb_strlen($itemLog->filename, 'UTF-8') > 35;
            $abbfilename = $itemLog->bigname ? mb_substr($itemLog->filename, 0, 32, 'UTF-8').'...' : $itemLog->filename;
            $itemLog->fileinfo = $abbfilename;
            if (isset($item->itemid)) {
                $file = block_sibportfolio_files::get_file_by_claim_id($item->itemid);
                if ($file) $itemLog->fileinfo = block_sibportfolio_files::get_sibport_file_link($file, $abbfilename, array('target' => '_blank'));
            }
        } else $itemLog->filename = $itemLog->fileinfo = '<i>'.get_string('key172', 'block_sibportfolio').'</i>';
        
        $itemLog->type = block_sibportfolio_get_claim_type_name($item->claimtype);
        $itemLog->status = block_sibportfolio_get_claim_status_name($item->claimstatus);
        $itemLog->curator = fullname(block_sibportfolio_users::get_user_by_id($item->curatorid));
        $itemLog->date = userdate($item->timecreated, "%d %b %Y, %H:%M");
        $itemLog->usercomment = isset($item->usercomment) ? $item->usercomment : '—';
        $itemLog->comment = isset($item->comment) ? $item->comment : '—';
        if ($item->claimtype == 0) {
            $itemLog->icon = 'add';
        } else if ($item->claimtype == 1) {
            $itemLog->icon = 'edit';
        } else {
            $itemLog->icon = 'delete';
        }
        if ($item->claimstatus == 0) {
            $itemLog->statusicon = 'reject';
            $itemLog->background = 'block_sibportfolio_darkred';
        } else {
            $itemLog->statusicon = 'accept';
            $itemLog->background = 'block_sibportfolio_darkgreen';
        }
                
        $resultLog[] = $itemLog;
    }
    
    $result = array();
    $result['logs'] = $resultLog;
    $result['pages'] = block_sibportfolio_page_helper($page, $totalPages);
    return $result;
}
