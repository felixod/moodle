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

$pageData = isset($SESSION->block_sibportfolio_ureport) ? $SESSION->block_sibportfolio_ureport : new stdClass();

$groupId = optional_param('groupid', isset($pageData->groupid) ? $pageData->groupid : -1, PARAM_INT);
if (isset($pageData->groupid) && $groupId != $pageData->groupid) {
    $SESSION->block_sibportfolio_ureport = null;
    $pageData = new stdClass();
}
$page = optional_param('page', isset($pageData->page) ? $pageData->page : 1, PARAM_INT);
if ($page <= 0) $page = 1;		
$sort = optional_param('sortorder', isset($pageData->sort) ? $pageData->sort : -1, PARAM_INT);	
$dir = optional_param('dir', isset($pageData->dir) ? $pageData->dir : 'asc', PARAM_NOTAGS);	
$groupName = optional_param('groupname', isset($pageData->gname) ? $pageData->gname : null, PARAM_RAW_TRIMMED);

$params = array('page' => $page, 'sortorder' => $sort, 'dir' => $dir, 'groupname' => $groupName, 'groupid' => $groupId);
block_sibportfolio_require_login_system('/blocks/sibportfolio/ureport.php', $params);

$groupInfo = block_sibportfolio_groups::get_group_by_id($groupId);
if ($groupId != -1 && $groupId != 0 && empty($groupInfo)) { // 28.11.2015
	block_sibportfolio_print_error_ureport('key101');
}

$viewer = has_capability('block/sibportfolio:viewer', context_system::instance());	
if (!$viewer) {
    if (!block_sibportfolio_is_sitecurator())
		block_sibportfolio_print_error_ureport('key31');
    if ($groupId > 0 && !block_sibportfolio_is_group_curator($groupId))
		block_sibportfolio_print_error_ureport('key67');
}

$pageData->groupid = $groupId;
$pageData->page = $page;
$pageData->sort = $sort;
$pageData->dir = $dir;
$pageData->gname = $groupName;
$SESSION->block_sibportfolio_ureport = $pageData;

$result = block_sibportfolio_get_data($groupId, $sort, $dir, $page, $viewer);
$result['current_group'] = !empty($groupInfo) ? ' ('.$groupInfo->name.')' : '';
$result['group_id'] = $groupId;
$result['sortorder'] = $sort;
$result['direction'] = $dir;
$result['wwwroot'] = $CFG->wwwroot;

if ($groupId > 0) {
    $groupData = block_sibportfolio_groups::get_group_files($groupId);
    $result['count_users'] = $groupData->count_members;
    $result['count_empty'] = $groupData->count_members - $groupData->count_filled;
    $result['percent_filled'] = $groupData->count_members > 0 ? format_float($groupData->count_filled / $groupData->count_members * 100, 2, true, true) : 0;
    $result['count_users_label'] = get_string('key218', 'block_sibportfolio');
    $result['empty_label'] = get_string('key220', 'block_sibportfolio');
    $result['percent_label'] = get_string('key219', 'block_sibportfolio');
}

if (!block_sibportfolio_is_ajax_request()) {


    /* блок фильтрации по группам - начало */
    $groups = array();
    if (empty($groupName)) {	
        $groups = block_sibportfolio_array_push($groups, -1, 'key185');	
        $groups = block_sibportfolio_array_push($groups, 0, 'key186');	
    } else {
        $findGroups = block_sibportfolio_groups::get_groups_by_name($groupName, $viewer ? null : $USER->id);
        if ($findGroups === 0) {
            $groups = block_sibportfolio_array_push($groups, -1, 'key95');			
            $groups = block_sibportfolio_array_push($groups, 0, 'key186');							
        } else if (count($findGroups) == 0) {		
            $groups = block_sibportfolio_array_push($groups, -1, 'key135');
            $groups = block_sibportfolio_array_push($groups, 0, 'key186');				
        } else {
            $groups = block_sibportfolio_array_push($groups, -1, 'key185');
            $groups = block_sibportfolio_array_push($groups, 0, 'key186');	
            $groups = array_merge($groups, $findGroups);			
        }
    }
    
    foreach ($groups as $group) {
        if ($group->id == $groupId) {
            $group->selected = 'selected';
            break;
        }
    }	
    /* блок фильтрации по группам - конец */
    

    $result['groups'] = $groups;
    $result['group_name'] = $groupName;
    $result['header_label'] = get_string('key183', 'block_sibportfolio');
    $result['group_label'] = get_string('key33', 'block_sibportfolio');
    $result['fgroup_label'] = get_string('key140', 'block_sibportfolio');
    $result['groupid_label'] = get_string('key151', 'block_sibportfolio');
    
    $render = new block_sibportfolio_render('views');	
    $render->add_style('css/styles-plugin.css');
    //$render->add_script('scripts/jquery-1.11.3.min.js');
    $render->add_script('scripts/handlebars-v3.0.3.js');
    $render->add_script('scripts/handlebars-jquery.js');
    $render->set_title(get_string('key182', 'block_sibportfolio'));
    $render->set_heading(get_string('key182', 'block_sibportfolio'));
    $render->add_navigation(get_string('key41', 'block_sibportfolio'), new moodle_url('/blocks/sibportfolio/index.php', array('userid' => $USER->id)));
    $render->add_navigation(get_string('key182', 'block_sibportfolio'), $PAGE->url);
    $render->add_views(array(
        'navigator.html' 	=> block_sibportfolio_get_navigator_model(),
        'ureport.html' 		=> $result,
        'ureport-ajax.html' => block_sibportfolio_get_ajax_model() // закомментировать для отключения ajax
    ));
    $render->display();

} else {

    header('Content-Type: application/json');
    echo json_encode($result);	

}

function block_sibportfolio_get_data($groupId, $sort, $dir, $page, $viewer) {
    global $USER, $OUTPUT;
    
    
    /* блок формирования отчета по категориям файлов - начало */
    $reportData = $groupId == 0 ? ($viewer ? block_sibportfolio_users::get_users_files_count($sort, $dir != 'desc', $page, 15, 0) : 
        block_sibportfolio_users::get_users_files_count($sort, $dir != 'desc', $page, 15, 1, $USER->id)) : 
        block_sibportfolio_users::get_users_files_count($sort, $dir != 'desc', $page, 15, 2, $groupId);
    $page = $reportData->page;
    $totalPages = $reportData->total;
    
    $usersInfo = array();
    $categories = block_sibportfolio_files::get_file_categories();
    foreach ($reportData->users as $user) {
        $countFiles = 0;
        $countClaims = block_sibportfolio_claims::get_count_wait_claims_by_user($user->userid);
        $userData = block_sibportfolio_users::get_user_by_id($user->userid);
    
        $userInfo = new stdClass();
        $userInfo->userid = $user->userid;
        $userInfo->count_claims = $countClaims == 0 ? '' : '<span class="block_sibportfolio_notify">+' . $countClaims . '</span>';
        $userInfo->picture = $OUTPUT->user_picture($userData, array('size' => 20));
        $userInfo->username = fullname($userData);
        $userInfo->categories = array();
        foreach ($categories as $category) {
            $userInfo->categories[] = $countCategory = block_sibportfolio_files::get_count_files($category->id, $user->userid);
            $countFiles += $countCategory;
        }
        $userInfo->allFiles = $countFiles;
        $usersInfo[] = $userInfo;
    }
    
    $allFiles = 0;
    $sumCategories = array();
    foreach ($categories as $category) {
        $sumCategories[] = $reportData->files[$category->id];
        $allFiles += $reportData->files[$category->id];
    }
    $sumCategories[] = $allFiles;
    array_unshift($sumCategories, get_string('key187', 'block_sibportfolio'));
    /* блок формирования отчета по категориям файлов - конец */
    
    
    /* блок установки заголовка таблицы - начало */
    $cat = new stdClass();
    $cat->id = -1;
    $cat->name = get_string('key1', 'block_sibportfolio');
    array_unshift($categories, $cat);	
    
    $categories = block_sibportfolio_array_push($categories, 0, 'key184');
    
    foreach ($categories as $category) {
        $category->dir = ($sort == $category->id && $dir == 'desc') ? 'asc' : 'desc';
        if ($sort == $category->id) {
            $category->tag = $dir != 'desc' ?
                $OUTPUT->pix_icon('sort_asc', '', 'block_sibportfolio') :
                $OUTPUT->pix_icon('sort_desc', '', 'block_sibportfolio');
        }
    }
    /* блок установки заголовка таблицы - конец */
    
    
    $result = array();
    $result['report'] = $usersInfo;
    $result['categories'] = $categories;
    $result['total'] = $sumCategories;
    $result['pages'] = block_sibportfolio_page_helper($page, $totalPages);
    return $result;
}

function block_sibportfolio_print_error_ureport($key) {
    global $SESSION;		
    $SESSION->block_sibportfolio_ureport = null;
    print_error($key, 'block_sibportfolio');
}

function block_sibportfolio_array_push($array, $id, $key_name) {
    $temp = new stdClass();
    $temp->id = $id;
    $temp->name = get_string($key_name, 'block_sibportfolio');
    $array[] = $temp;
    return $array;
}
