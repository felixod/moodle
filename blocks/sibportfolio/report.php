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

$pageData = isset($SESSION->block_sibportfolio_report) ? $SESSION->block_sibportfolio_report : new stdClass();

$user = optional_param('user', isset($pageData->user) ? $pageData->user : null, PARAM_RAW_TRIMMED);
if (isset($pageData->user) && $user != $pageData->user) {
    $SESSION->block_sibportfolio_report = null;
    $pageData = new stdClass();
}
$page = optional_param('page', isset($pageData->page) ? $pageData->page : 1, PARAM_INT);
if ($page <= 0) $page = 1;
$sort = optional_param('sortorder', isset($pageData->sort) ? $pageData->sort : 0, PARAM_INT);
if ($sort < 0 || $sort > 2) $sort = 0;

block_sibportfolio_require_login_system('/blocks/sibportfolio/report.php', array('page' => $page, 'sortorder' => $sort, 'user' => $user));

if (!is_siteadmin()) {
    $SESSION->block_sibportfolio_report = null;
    print_error('key67', 'block_sibportfolio');
}

$pageData->user = $user;
$pageData->page = $page;
$pageData->sort = $sort;
$SESSION->block_sibportfolio_report = $pageData;

$result = block_sibportfolio_get_data($user, $sort, $page);
$result['sortorder'] = $sort;
$result['user_name'] = $user;
$result['more_label'] = get_string('key75', 'block_sibportfolio');
$result['group_label'] = get_string('key33', 'block_sibportfolio');
$result['count_users_label'] = get_string('key218', 'block_sibportfolio');
$result['percent_label'] = get_string('key219', 'block_sibportfolio');
$result['empty_label'] = get_string('key220', 'block_sibportfolio');
$result['wwwroot'] = $CFG->wwwroot;

$sortLinks = array();
for ($i = 0; $i <= 2; $i++) {
    $link = new stdClass();
    switch($i) {
        case 0:
            $link->caption = get_string('key34', 'block_sibportfolio');
            $link->sortorder = 0;
            break;
        case 1:
            $link->caption = get_string('lastaccess');
            $link->sortorder = 1;
            break;
        case 2:
            $link->caption = get_string('key35', 'block_sibportfolio');
            $link->sortorder = 2;
            break;
    }
    if ($sort == $i) $link->style = 'font-weight: bold;';
    $sortLinks[] = $link;
}

$result['sort_links'] = $sortLinks;
if (count($result['curators']) == 0) {
    $result['not_found_label'] = get_string('key131', 'block_sibportfolio');
}

if (!block_sibportfolio_is_ajax_request()) {

    $result['find'] = count($result['curators']) > 0;
    $result['header_label'] = get_string('key68', 'block_sibportfolio');
    $result['curator_label'] = get_string('key34', 'block_sibportfolio');
    $result['claims_label'] = get_string('key35', 'block_sibportfolio');
    $result['last_access_label'] = get_string('lastaccess');
    $result['sort_label'] = get_string('key37', 'block_sibportfolio');
    $result['fuser_label'] = get_string('key165', 'block_sibportfolio');

    $exporturl = new moodle_url($CFG->wwwroot . '/blocks/sibportfolio/handlers/get/xlsreport.php');
    $result['export'] = $OUTPUT->single_button($exporturl, get_string('key237', 'block_sibportfolio'), 'get');

    $render = new block_sibportfolio_render('views');
    $render->add_style('css/styles-plugin.css');
    $render->add_script('scripts/jquery-1.11.3.min.js');
    $render->add_script('scripts/handlebars-v3.0.3.js');
    $render->add_script('scripts/handlebars-jquery.js');
    $render->set_title(get_string('key24', 'block_sibportfolio'));
    $render->set_heading(get_string('key24', 'block_sibportfolio'));
    $render->add_navigation(get_string('key41', 'block_sibportfolio'), new moodle_url('/blocks/sibportfolio/index.php', array('userid' => $USER->id)));
    $render->add_navigation(get_string('key24', 'block_sibportfolio'), $PAGE->url);
    $render->add_views(array(
        'navigator.html'   => block_sibportfolio_get_navigator_model(),
        'report.html' 	   => $result,
        'report-ajax.html' => block_sibportfolio_get_ajax_model() // закомментировать для отключения ajax
    ));
    $render->display();

} else {

    header('Content-Type: application/json');
    echo json_encode($result);

}

function block_sibportfolio_get_data($user, $sort, $page)
{
    $curatorData = block_sibportfolio_users::get_curators_data($user, $sort, $page);
    $curators = $curatorData->curators;
    $page = $curatorData->page;
    $totalPages = $curatorData->total;

    $curatorsInfo = array();
    foreach ($curators as $curator) {
        $item = new stdClass();
        $item->id = $curator->id;
        $item->name = fullname($curator);
        $item->tlaccess = $curator->lastaccess;
        $item->laccess = $curator->lastaccess ? userdate($curator->lastaccess, get_string('strftimerecentfull')) : get_string('never');
        $item->claims = $curator->count_claims;
        $curatorsInfo[] = $item;
    }

    foreach ($curatorsInfo as $curatorInfo) {
        $groupsCurator = block_sibportfolio_groups::get_groups_by_curator($curatorInfo->id);
        $groupsInfo = array();
        foreach ($groupsCurator as $group) {
            $groupInfo = new stdClass();
            $groupInfo->id = $group->id;
            $groupInfo->name = $group->name;
            $groupInfo->visible = $group->visible;
            $groupData = block_sibportfolio_groups::get_group_files($groupInfo->id);
            $groupInfo->count_users = $groupData->count_members;
            $groupInfo->count_empty = $groupInfo->count_users - $groupData->count_filled;
            $groupInfo->percent_filled = $groupInfo->count_users > 0 ? format_float($groupData->count_filled / $groupInfo->count_users * 100, 2, true, true) : 0;
            $groupsInfo[] = $groupInfo;
        }
        $curatorInfo->groups = $groupsInfo;
    }

    $result = array();
    $result['curators'] = $curatorsInfo;
    $result['pages'] = block_sibportfolio_page_helper($page, $totalPages);
    return $result;
}
