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
require_once($CFG->dirroot.'/comment/lib.php');

$userId = optional_param('userid', $USER->id, PARAM_INT);

$categoryId = optional_param('categoryid', 0, PARAM_INT);

$page = optional_param('page', 1, PARAM_INT);
if ($page <= 0) $page = 1;

$showComments = optional_param('showcomments', 0, PARAM_INT);

block_sibportfolio_require_login_system('/blocks/sibportfolio/userfiles.php', array('userid' => $userId,
    'categoryid' => $categoryId, 'page' => $page, 'showcomments' => $showComments));

if (!block_sibportfolio_files::get_file_category_by_id($categoryId)) {
    block_sibportfolio_notification(get_string('key78', 'block_sibportfolio'));
}

$userInfo = block_sibportfolio_users::get_user_by_id($userId);
block_sibportfolio_users::user_exist($userInfo);

$action = is_siteadmin() || block_sibportfolio_is_sitecurator();
$userName = fullname($userInfo);

comment::init();

$result = block_sibportfolio_get_data($userId, $categoryId, $page, $showComments);
$result['user_id'] = $userId;
$result['category_id'] = $categoryId;	
$result['return_url'] = $PAGE->url->out(false);
$result['claims_block'] = is_siteadmin() || block_sibportfolio_is_curator($userId) || $USER->id == $userId;
$result['date_label'] = get_string('key79', 'block_sibportfolio');
$result['comments_label'] = get_string('comments');
$result['usecomments'] = $CFG->usecomments == true;
$result['del_label'] = get_string('key81', 'block_sibportfolio');
$result['edit_label'] = get_string('key83', 'block_sibportfolio');
$result['wwwroot'] = $CFG->wwwroot;

if (!block_sibportfolio_is_ajax_request()) {

    $result['find'] = count($result['files']) > 0;
    if ($USER->id != $userId) $result['user_name'] = '('.$userName.')';
    $result['icon_name'] = (file_exists('pix/'.$categoryId.'.png')) ? $categoryId : 'folder';
    $result['user_suspended'] = $userInfo->suspended;
    $result['header_label'] = block_sibportfolio_files::get_file_category_by_id($categoryId)->name;
    $result['apply_label'] = $action ? get_string('key49', 'block_sibportfolio') : get_string('key50', 'block_sibportfolio');
    $result['not_found_label'] = get_string('key85', 'block_sibportfolio');

    $render = new block_sibportfolio_render('views');
    $render->add_script('scripts/jquery-1.11.3.min.js');
    $render->add_script('scripts/handlebars-v3.0.3.js');
    $render->add_script('scripts/handlebars-jquery.js');
    $render->set_title(get_string('key60', 'block_sibportfolio'));
    $render->set_heading(get_string('key60', 'block_sibportfolio'));
    $render->add_navigation(get_string('key41', 'block_sibportfolio').($USER->id != $userId ? ': '.$userName : ''), 
                            new moodle_url('/blocks/sibportfolio/index.php', array('userid' => $userId)));
    $render->add_navigation($result['header_label'], $PAGE->url);
    $render->add_views(array(
        'navigator.html' 	  => block_sibportfolio_get_navigator_model(),
        'userfiles.html' 	  => $result,
        'userfiles-ajax.html' => block_sibportfolio_get_ajax_model() // закомментировать для отключения ajax
    ));
    $render->display();
    
    $event = \block_sibportfolio\event\category_viewed::create(array(
        'objectid' 		=> $categoryId,
        'relateduserid' => $userId
    ));
    $event->trigger();
    
} else {

    header('Content-Type: application/json');
    echo json_encode($result);
    
}

function block_sibportfolio_get_data($userId, $categoryId, $page, $showComments) {
    global $CFG;
    
    $filesData = block_sibportfolio_files::get_user_files($userId, $categoryId, $page);
    $userFiles = $filesData->files;
    $page = $filesData->page;
    $totalPages = $filesData->total;

    $files = array();
    foreach ($userFiles as $userFile) {
        $fileData = block_sibportfolio_files::get_file_by_claim_id($userFile->id);
        $fileInfo = new stdClass();
        $fileInfo->filename = $fileData ? $fileData->filename : get_string('key172', 'block_sibportfolio');
        $fileInfo->link = $fileData ? block_sibportfolio_files::get_sibport_file_link($fileData, get_string('key5', 'block_sibportfolio'), array('target' => '_blank')) : 
                                      '<i>'.get_string('key172', 'block_sibportfolio').'</i>';
        $fileInfo->descr = $userFile->description;
        $fileInfo->date = userdate($userFile->timecreated, "%d %b %Y");
        $fileInfo->claimid = $userFile->id;

        /* Вывод комментариев, если они включены */
        if ($CFG->usecomments) {
            $showCommentsUrl = new moodle_url('/blocks/sibportfolio/userfiles.php', array('userid' => $userId,
                'categoryid' => $categoryId, 'page' => $page, 'showcomments' => $fileInfo->claimid));
            $args = new stdClass();
            $args->context   = context_user::instance($userId);
            $args->area      = 'file_comments';
            $args->component = 'block_sibportfolio';
            $args->itemid    = $fileInfo->claimid;
            $args->linktext  = get_string('showcomments');
            $args->autostart = true;
            $args->notoggle  = true;
            $args->displaycancel = false;
            $comment = new comment($args);
            $comment->set_view_permission(true);
            $comment->set_fullwidth();

            $fileInfo->commentscount = $comment->count();
            $fileInfo->comments = $showComments == $fileInfo->claimid ? $comment->output(true) : false;
            $fileInfo->showcomments_url = $showCommentsUrl->out(false);
        }

        $files[] = $fileInfo;
    }
    
    $result = array();
    $result['files'] = $files;
    $result['pages'] = block_sibportfolio_page_helper($page, $totalPages);
    return $result;
}
