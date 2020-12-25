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

$pageData = isset($SESSION->block_sibportfolio_users) ? $SESSION->block_sibportfolio_users : new stdClass();

$groupId = optional_param('groupid', isset($pageData->groupid) ? $pageData->groupid : -1, PARAM_INT);
if (isset($pageData->groupid) && $groupId != $pageData->groupid) {
    $SESSION->block_sibportfolio_users = null;
    $pageData = new stdClass();
}
$page = optional_param('page', isset($pageData->page) ? $pageData->page : 1, PARAM_INT);
if ($page <= 0) $page = 1;
$groupName = optional_param('groupname', isset($pageData->gname) ? $pageData->gname : null, PARAM_RAW_TRIMMED);	

block_sibportfolio_require_login_system('/blocks/sibportfolio/users.php', array('groupid' => $groupId, 'page' => $page, 'groupname' => $groupName));

if (!is_siteadmin()) {
    $SESSION->block_sibportfolio_users = null;
    print_error('key67', 'block_sibportfolio');
}

$notification = null;
$notification_type = null;	
if (optional_param('editRec', false, PARAM_BOOL) && confirm_sesskey() && data_submitted()) {

    $numbers = required_param_array('numbers', PARAM_RAW);
    $specialties = required_param_array('specialties', PARAM_RAW);
    $studies = required_param_array('studies', PARAM_RAW);

    $error = false;
    $changed = false;
    foreach ($numbers as $userId => $number) {
        $user = block_sibportfolio_users::get_user_by_id($userId);
        $groups = block_sibportfolio_groups::get_groups_by_user($userId);
        $userData = block_sibportfolio_groups::get_user_group_data($userId, $groupId);
        if (!$userData) {
            $userData = new stdClass();
            $userData->number = null;
            $userData->speciality = null;
            $userData->study = null;
        }

        if ($user && block_sibportfolio_contains_group($groups, $groupId)) {

            $newNumber; $newSpeciality; $newStudy;

            if (mb_strlen(trim($number), 'UTF-8') <= 250) {
                $newNumber = trim($number);
            } else {
                $newNumber = $userData->number;
                $error = true;
            }

            if (isset($specialties[$userId]) && mb_strlen(trim($specialties[$userId]), 'UTF-8') <= 250) {
                $newSpeciality = trim($specialties[$userId]);
            } else {
                $newSpeciality = $userData->speciality;
                $error = true;
            }

            if (isset($studies[$userId]) && mb_strlen(trim($studies[$userId]), 'UTF-8') <= 250) {
                $newStudy = trim($studies[$userId]);
            } else {
                $newStudy = $userData->study;
                $error = true;
            }

            if ($newNumber != $userData->number || $newSpeciality != $userData->speciality || $newStudy != $userData->study) {
                block_sibportfolio_groups::edit_user_group_data($newNumber, $newSpeciality, $newStudy, $userId, $groupId);
                $changed = true;
            }

        } else {
            $error = true;
        }
    }

    if ($error) {
        $notification = get_string('errorwithsettings', 'admin');
        $notification_type = 'alert-error';
    } else if ($changed) {
        $notification = get_string('changessaved');
        $notification_type = 'alert-success';
    }

}

$pageData->groupid = $groupId;
$pageData->page = $page;
$pageData->gname = $groupName;
$SESSION->block_sibportfolio_users = $pageData;

$result = block_sibportfolio_get_data($groupId, $page);
$groupInfo = block_sibportfolio_groups::get_group_by_id($groupId);
$groupData = block_sibportfolio_groups::get_group_data_by_id($groupId);
$result['current_group'] = !empty($groupInfo) ? ' ('.$groupInfo->name.')' : '';
$result['phspeciality'] = isset($groupData->speciality) && trim($groupData->speciality) != false ? $groupData->speciality : null;
$result['phstudy'] = isset($groupData->study) && trim($groupData->study) != false ? $groupData->study : null;
$result['group_name'] = $groupName;
$result['group_id'] = $groupId;
$result['current_page'] = $page;
$result['sesskey'] = sesskey();
$result['wwwroot'] = $CFG->wwwroot;

if (!block_sibportfolio_is_ajax_request()) {

    $groups = array();
    if (empty($groupName)) {
        $groups = block_sibportfolio_array_push($groups, 0, 'key185');
    } else {
        $findGroups = block_sibportfolio_groups::get_groups_by_name($groupName);
        if ($findGroups === 0) {
            $groups = block_sibportfolio_array_push($groups, 0, 'key95');
        } else if (count($findGroups) == 0) {
            $groups = block_sibportfolio_array_push($groups, 0, 'key135');
        } else {
            $groups = block_sibportfolio_array_push($groups, 0, 'key185');
            $groups = array_merge($groups, $findGroups);
        }
    }

    foreach ($groups as $group) {
        if ($group->id == $groupId) {
            $group->selected = 'selected';
            break;
        }
    }

    $result['groups'] = $groups;
    $result['notification_message'] = $notification;
    $result['notification_type'] = $notification_type;
    $result['header_label'] = get_string('key205', 'block_sibportfolio');
    $result['group_label'] = get_string('key33', 'block_sibportfolio');
    $result['fgroup_label'] = get_string('key140', 'block_sibportfolio');
    $result['groupid_label'] = get_string('key151', 'block_sibportfolio');
    $result['username_label'] = get_string('key1', 'block_sibportfolio');
    $result['record_label'] = get_string('key206', 'block_sibportfolio');
    $result['speciality_label'] = get_string('key198', 'block_sibportfolio');
    $result['study_label'] = get_string('key199', 'block_sibportfolio');
    $result['edit_users_label'] = get_string('key159', 'block_sibportfolio');

    $render = new block_sibportfolio_render('views');
    $render->add_style('css/styles-plugin.css');
    $render->add_script('scripts/jquery-1.11.3.min.js');
    $render->add_script('scripts/handlebars-v3.0.3.js');
    $render->add_script('scripts/handlebars-jquery.js');
    $render->set_title(get_string('key204', 'block_sibportfolio'));
    $render->set_heading(get_string('key204', 'block_sibportfolio'));
    $render->add_navigation(get_string('key41', 'block_sibportfolio'), new moodle_url('/blocks/sibportfolio/index.php', array('userid' => $USER->id)));
    $render->add_navigation(get_string('key204', 'block_sibportfolio'), $PAGE->url);
    $render->add_views(array(
        'navigator.html'  => block_sibportfolio_get_navigator_model(),
        'users.html' 	  => $result,
        'users-ajax.html' => block_sibportfolio_get_ajax_model() // закомментировать для отключения ajax
    ));
    $render->display();

} else {

    header('Content-Type: application/json');
    echo json_encode($result);

}

function block_sibportfolio_get_data($groupId, $page) {
    $usersData = block_sibportfolio_users::get_users_by_group($groupId, $page, 30);
    $users = $usersData->users;
    $page = $usersData->page;
    $totalPages = $usersData->total;

    $usersInfo = array();
    foreach ($users as $user) {
        $userInfo = new stdClass();
        $userInfo->userid = $user->id;
        $userInfo->username = fullname($user);
        $userData = block_sibportfolio_groups::get_user_group_data($userInfo->userid, $groupId);
        $userInfo->number = isset($userData->number) && trim($userData->number) != false ? $userData->number : null;
        $userInfo->speciality = isset($userData->speciality) && trim($userData->speciality) != false ? $userData->speciality : null;
        $userInfo->study = isset($userData->study) && trim($userData->study) != false ? $userData->study : null;
        $usersInfo[] = $userInfo;
    }

    $result = array();
    $result['users'] = $usersInfo;
    $result['pages'] = block_sibportfolio_page_helper($page, $totalPages);
    return $result;
}

function block_sibportfolio_array_push($array, $id, $key_name) {
    $temp = new stdClass();
    $temp->id = $id;
    $temp->name = get_string($key_name, 'block_sibportfolio');
    $array[] = $temp;
    return $array;
}

function block_sibportfolio_contains_group($groups, $groupId) {
    foreach ($groups as $group) {
        if ($group->id == $groupId)
            return true;
    }
    return false;
}
