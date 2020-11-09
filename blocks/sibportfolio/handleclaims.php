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

$page = optional_param('page', 1, PARAM_INT);
if ($page <= 0) $page = 1;

$sort = optional_param('sortorder', 0, PARAM_INT);
if ($sort < 0 || $sort > 3) $sort = 0;

block_sibportfolio_require_login_system('/blocks/sibportfolio/handleclaims.php', array('page' => $page, 'sortorder' => $sort));

$is_siteadmin = is_siteadmin();

if (!($is_siteadmin || block_sibportfolio_is_sitecurator())) {
    print_error('key31', 'block_sibportfolio');
}

$userId = $is_siteadmin ? optional_param('userid', 0, PARAM_INT) : $USER->id;
if ($is_siteadmin && $userId != 0 && !block_sibportfolio_is_sitecurator($userId)) print_error('key94', 'block_sibportfolio');

$result = block_sibportfolio_get_data($userId == 0 ? null : $userId, $sort, $page);
if ($is_siteadmin) $result['user_id'] = $userId;
$result['sortorder'] = $sort;
$result['is_admin'] = $is_siteadmin;
$result['curator_label'] = get_string('key34', 'block_sibportfolio');
$result['wwwroot'] = $CFG->wwwroot;

if (!block_sibportfolio_is_ajax_request()) {

    $sortLinks = array();
    for ($i = 0; $i <= 1; $i++) {
        $link = new stdClass();
        switch($i) {
            case 0:
                $link->caption = get_string('key32', 'block_sibportfolio');
                $link->sortorder = 0;
                break;
            case 1:
                $link->caption = get_string('key35', 'block_sibportfolio');
                $link->sortorder = 1;
                break;
        }
        if ($sort == $i) $link->style = 'font-weight: bold;';
        $sortLinks[] = $link;
    }
    
    $result['sort_links'] = $sortLinks;
    $result['find'] = count($result['users']) > 0;
    $result['header_label'] = get_string('key36', 'block_sibportfolio');
    $result['user_label'] = get_string('key1', 'block_sibportfolio');
    $result['group_label'] = get_string('key33', 'block_sibportfolio');
    $result['claim_label'] = get_string('key35', 'block_sibportfolio');
    $result['sort_label'] = get_string('key37', 'block_sibportfolio');
    $result['not_found_label'] = get_string('key131', 'block_sibportfolio');
    
    $render = new block_sibportfolio_render('views');
    $render->add_script('scripts/jquery-1.11.3.min.js');
    $render->add_script('scripts/handlebars-v3.0.3.js');
    $render->add_script('scripts/handlebars-jquery.js');
    $render->set_title(get_string('key22', 'block_sibportfolio'));
    $render->set_heading(get_string('key22', 'block_sibportfolio'));
    $render->add_navigation(get_string('key41', 'block_sibportfolio'), new moodle_url('/blocks/sibportfolio/index.php', array('userid' => $USER->id)));
    $render->add_navigation(get_string('key22', 'block_sibportfolio'), $PAGE->url);
    $render->add_views(array(
        'navigator.html' 		 => block_sibportfolio_get_navigator_model(),
        'handleclaims.html' 	 => $result,
        'handleclaims-ajax.html' => block_sibportfolio_get_ajax_model() // закомментировать для отключения ajax
    ));
    $render->display();

} else {

    header('Content-Type: application/json');
    echo json_encode($result);	

}

function block_sibportfolio_get_data($userId, $sort, $page) {
    global $OUTPUT;

    $userData = block_sibportfolio_users::get_wait_users($userId, $sort, $page);
    $users = $userData->users;
    $page = $userData->page;
    $totalPages = $userData->total;
    
    $usersInfo = array();
    foreach ($users as $key => $user) {
        $userInfo = new stdClass();
        $userInfo->id = $key;
        $userInfo->picture = $OUTPUT->user_picture($user, array('size' => 20));
        $userInfo->fullname = fullname($user);
        $userInfo->count_claims = $user->count_claims;
        $userInfo->groups = array();
        $groups = block_sibportfolio_groups::get_groups_by_user($key);
        foreach ($groups as $group) {
            $curator = block_sibportfolio_users::get_curator_by_group($group->id);
            $curatorId = isset($curator->curatorid) ? $curator->curatorid : null;
            $curatorInfo = isset($curatorId) ? block_sibportfolio_users::get_user_by_id($curatorId) : null;			
            $groupInfo = new stdClass();
            $groupInfo->name = $group->name;
            $groupInfo->visible = $group->visible;
            $groupInfo->curator = $curatorInfo ? fullname($curatorInfo) : null;
            $userInfo->groups[] = $groupInfo;
        }
        $usersInfo[] = $userInfo;
    }

    $result = array();
    $result['users'] = $usersInfo;
    $result['pages'] = block_sibportfolio_page_helper($page, $totalPages);
    return $result;
}
