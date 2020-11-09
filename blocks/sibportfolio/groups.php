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

$pageData = isset($SESSION->block_sibportfolio_groups) ? $SESSION->block_sibportfolio_groups : new stdClass();

$groupName = optional_param('groupname', isset($pageData->gname) ? $pageData->gname : null, PARAM_RAW_TRIMMED);
if (isset($pageData->gname) && $groupName != $pageData->gname) {
    $SESSION->block_sibportfolio_groups = null;
    $pageData = new stdClass();
}
$page = optional_param('page', isset($pageData->page) ? $pageData->page : 1, PARAM_INT);
if ($page <= 0) $page = 1;
$sort = optional_param('sortorder', isset($pageData->sort) ? $pageData->sort : 0, PARAM_INT);
if ($sort < 0 || $sort > 1) $sort = 0;

block_sibportfolio_require_login_system('/blocks/sibportfolio/groups.php', array('page' => $page, 'sortorder' => $sort, 'groupname' => $groupName));

if (!is_siteadmin()) {
    $SESSION->block_sibportfolio_groups = null;
    print_error('key67', 'block_sibportfolio');
}

$pageData->gname = $groupName;
$pageData->page = $page;
$pageData->sort = $sort;
$SESSION->block_sibportfolio_groups = $pageData;

$result = block_sibportfolio_get_data($groupName, $sort, $page);
$result['sortorder'] = $sort;
$result['group_name'] = $groupName;
$result['icon_addmod'] = $OUTPUT->pix_icon('assign', get_string('key138', 'block_sibportfolio'), 'block_sibportfolio');
$result['add_curator_label'] = get_string('key138', 'block_sibportfolio');
$result['icon_delmod'] = $OUTPUT->pix_icon('remove', get_string('key139', 'block_sibportfolio'), 'block_sibportfolio');
$result['del_curator_label'] = get_string('key139', 'block_sibportfolio');
$result['icon_editgr'] = $OUTPUT->pix_icon('settings', get_string('key200', 'block_sibportfolio'), 'block_sibportfolio');
$result['edit_group_label'] = get_string('key200', 'block_sibportfolio');
$result['return_url_local'] = urlencode($PAGE->url->out_as_local_url());
$result['return_url'] = urlencode($PAGE->url->out(false));
$result['wwwroot'] = $CFG->wwwroot;

$sortLinks = array();
for ($i = 0; $i < 2; $i++) {
    $link = new stdClass();
    switch($i) {
        case 0:
            $link->caption = get_string('key33', 'block_sibportfolio');
            $link->sortorder = 0;
            break;
        case 1:
            $link->caption = get_string('key34', 'block_sibportfolio');
            $link->sortorder = 1;
            break;
    }
    if ($sort == $i) $link->style = 'font-weight: bold;';
    $sortLinks[] = $link;
}

$result['sort_links'] = $sortLinks;
if (count($result['groups']) == 0) {
    $result['not_found_label'] = get_string('key135', 'block_sibportfolio');
}

if (!block_sibportfolio_is_ajax_request()) {
    
    $result['find'] = count($result['groups']) > 0;
    $result['sort_label'] = get_string('key37', 'block_sibportfolio');
    $result['header_label'] = get_string('key195', 'block_sibportfolio');
    $result['group_label'] = get_string('key33', 'block_sibportfolio');
    $result['curator_label'] = get_string('key34', 'block_sibportfolio');
    $result['speciality_label'] = get_string('key198', 'block_sibportfolio');
    $result['study_label'] = get_string('key199', 'block_sibportfolio');
    $result['fgroup_label'] = get_string('key140', 'block_sibportfolio');

    $render = new block_sibportfolio_render('views');
    $render->add_script('scripts/jquery-1.11.3.min.js');
    $render->add_script('scripts/handlebars-v3.0.3.js');
    $render->add_script('scripts/handlebars-jquery.js');
    $render->set_title(get_string('key21', 'block_sibportfolio'));
    $render->set_heading(get_string('key21', 'block_sibportfolio'));
    $render->add_navigation(get_string('key41', 'block_sibportfolio'), new moodle_url('/blocks/sibportfolio/index.php', array('userid' => $USER->id)));
    $render->add_navigation(get_string('key21', 'block_sibportfolio'), $PAGE->url);
    $render->add_views(array(
        'navigator.html'   => block_sibportfolio_get_navigator_model(),
        'groups.html' 	   => $result,
        'groups-ajax.html' => block_sibportfolio_get_ajax_model() // закомментировать для отключения ajax
    ));
    $render->display();

} else {

    header('Content-Type: application/json');
    echo json_encode($result);

}

function block_sibportfolio_get_data($groupName, $sort, $page) {
    $groupData = block_sibportfolio_groups::get_group_data($groupName, $sort, $page);
    $groups = $groupData->groups;
    $page = $groupData->page;
    $totalPages = $groupData->total;
    
    $groupsInfo = array();
    foreach($groups as $group) {
        $group->hasCurator = isset($group->curatorid);
        if ($group->hasCurator)
            $group->curator = fullname(block_sibportfolio_users::get_user_by_id($group->curatorid));
        if (trim($group->study) == false) 
            $group->study = '<i>'.get_string('key111', 'block_sibportfolio').'</i>';
        if (trim($group->speciality) == false) 
            $group->speciality = '<i>'.get_string('key111', 'block_sibportfolio').'</i>';
        $groupsInfo[] = $group;
    }
    
    $result = array();
    $result['groups'] = $groupsInfo;
    $result['pages'] = block_sibportfolio_page_helper($page, $totalPages);
    return $result;
}
