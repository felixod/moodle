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

$sort = optional_param('sortorder', 0, PARAM_INT);
if ($sort < 0 || $sort > 1) $sort = 0;

block_sibportfolio_require_login_system('/blocks/sibportfolio/userclaims.php', array('userid' => $userId, 'page' => $page, 'sortorder' => $sort));

$userInfo = block_sibportfolio_users::get_user_by_id($userId);
block_sibportfolio_users::user_exist($userInfo);

if (!(is_siteadmin() || block_sibportfolio_is_curator($userId) || $USER->id == $userId)) {
    print_error('key71', 'block_sibportfolio');
}

$userName = fullname($userInfo);

$result = block_sibportfolio_get_data($userId, $sort, $page);
$result['user_id'] = $userId;
$result['sortorder'] = $sort;
$result['return_url_enc'] = urlencode($PAGE->url->out(false));
$result['more_label'] = get_string('key76', 'block_sibportfolio');
$result['wwwroot'] = $CFG->wwwroot;

if (!block_sibportfolio_is_ajax_request()) {

    $sortLinks = array();
    for ($i = 0; $i < 2; $i++) {
        $link = new stdClass();
        switch($i) {
            case 0:
                $link->caption = get_string('key72', 'block_sibportfolio');
                $link->sortorder = 0;
                break;
            case 1:
                $link->caption = get_string('key55', 'block_sibportfolio');
                $link->sortorder = 1;
                break;
        }
        if ($sort == $i) $link->style = 'font-weight: bold;';
        $sortLinks[] = $link;
    }

    if ($USER->id != $userId) $result['user_name'] = '('.$userName.')';
    $result['sort_links'] = $sortLinks;
    $result['find'] = count($result['claims']) > 0;
    $result['header_label'] = get_string('key70', 'block_sibportfolio');
    $result['type_label'] = get_string('key55', 'block_sibportfolio');
    $result['date_label'] = get_string('key72', 'block_sibportfolio');
    $result['comment_label'] = get_string('key74', 'block_sibportfolio');
    $result['info_label'] = get_string('key75', 'block_sibportfolio');
    $result['sort_label'] = get_string('key37', 'block_sibportfolio');
    $result['data_claim_label'] = get_string('key77', 'block_sibportfolio');
    $result['type_claim_label'] = get_string('key55', 'block_sibportfolio');
    $result['not_found_label'] = get_string('key38', 'block_sibportfolio');

    $render = new block_sibportfolio_render('views');
    $render->add_script('scripts/jquery-1.11.3.min.js');
    $render->add_script('scripts/handlebars-v3.0.3.js');
    $render->add_script('scripts/handlebars-jquery.js');
    $render->set_title(get_string('key70', 'block_sibportfolio'));
    $render->set_heading(get_string('key73', 'block_sibportfolio'));
    $render->add_navigation(get_string('key41', 'block_sibportfolio').($USER->id != $userId ? ': '.$userName : ''), 
                            new moodle_url('/blocks/sibportfolio/index.php', array('userid' => $userId)));
    $render->add_navigation(get_string('key70', 'block_sibportfolio'), $PAGE->url);
    $render->add_views(array(
        'navigator.html' 	   => block_sibportfolio_get_navigator_model(),
        'userclaims.html' 	   => $result,
        'userclaims-ajax.html' => block_sibportfolio_get_ajax_model() // закомментировать для отключения ajax
    ));
    $render->display();

} else {

    header('Content-Type: application/json');
    echo json_encode($result);
    
}

function block_sibportfolio_get_data($userId, $sort, $page) {
    $claimsData = block_sibportfolio_claims::get_wait_claims($userId, null, $sort, $page);
    $waitClaims = $claimsData->claims;
    $page = $claimsData->page;
    $totalPages = $claimsData->total;

    $userClaims = array();
    foreach ($waitClaims as $claim) {
        $userClaim = new stdClass();
        $userClaim->type = block_sibportfolio_get_claim_type_name($claim->claimtype);
        $userClaim->comment = $claim->usercomment;
        $userClaim->id = $claim->id;
        $userClaim->date = userdate($claim->timemodified, "%d %b %Y, %H:%M");
        if ($claim->claimtype == 0) {
            $userClaim->icon = 'add';
            $userClaim->class = 'block_sibportfolio_green';
            $userClaim->comment = '—';
        } else if ($claim->claimtype == 1) {
            $userClaim->icon = 'edit';
            $userClaim->class = 'block_sibportfolio_yellow';
        } else {
            $userClaim->icon = 'delete';
            $userClaim->class = 'block_sibportfolio_red';
        }
        $userClaims[] = $userClaim;
    }
    
    $result = array();
    $result['claims'] = $userClaims;
    $result['pages'] = block_sibportfolio_page_helper($page, $totalPages);
    return $result;
}
