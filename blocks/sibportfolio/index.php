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

if ($userId == 0) $userId = $USER->id;

block_sibportfolio_require_login_system('/blocks/sibportfolio/index.php', array('userid' => $userId));

$userInfo = block_sibportfolio_users::get_user_by_id($userId);

//Получаем идентификационный номер СамГУПС
$idNumber = $userInfo->idnumber;
block_sibportfolio_users::user_exist($userInfo);

//Проверяем какие группы есть у студента
// 0 - Общие
// 1 - Студенческие
// 2 - Преподавательские
// 3 - Студенческие и преподавательские
$group_type = 0;

//Подключение кнопок социальных сетей
$ScienceIndex = '';
if (is_object(get_record($userId, 'ScienceIndex'))) {
    $ScienceIndex  = get_record($userId, 'ScienceIndex')->data;
}
$GoogleScholar = '';
if (is_object(get_record($userId, 'GoogleScholar'))) {
    $GoogleScholar  = get_record($userId, 'GoogleScholar')->data;
}
$ORCID = '';
if (is_object(get_record($userId, 'ORCID'))) {
    $ORCID  = get_record($userId, 'ORCID')->data;
}
$ResearcherID = '';
if (is_object(get_record($userId, 'ResearcherID'))) {
    $ResearcherID  = get_record($userId, 'ResearcherID')->data;
}

$is_siteadmin = is_siteadmin();

$userName = fullname($userInfo);
$userUrl = $userInfo->url; 

$groupsInfo = block_sibportfolio_groups::get_groups_by_user($userId, true);
if (count($groupsInfo) == 0) $groupsInfo = get_string('key39', 'block_sibportfolio');
else foreach ($groupsInfo as $key => $groupInfo) {
    $groupData = block_sibportfolio_groups::get_group_data_by_id($key);
    $userData = block_sibportfolio_groups::get_user_group_data($userId, $key);

    if (isset($groupData)) {
        $curatorInfo = null;
        if (isset($groupData->curatorid)) $curatorInfo = block_sibportfolio_users::get_user_by_id($groupData->curatorid);
        $groupsInfo[$key]->curatorid = $curatorInfo && ($USER->id == $userId || $is_siteadmin) ? $curatorInfo->id : null;
        $groupsInfo[$key]->curator = $curatorInfo ? fullname($curatorInfo) : get_string('key93', 'block_sibportfolio');
    }

    $groupsInfo[$key]->study = $userData && $userData->study ? $userData->study : ($groupData && $groupData->study ? $groupData->study : '—');
    $groupsInfo[$key]->speciality = $userData && $userData->speciality ? $userData->speciality : ($groupData && $groupData->speciality ? $groupData->speciality : '—');
    $groupsInfo[$key]->record = $userData && $userData->number ? $userData->number : '—';
    
    //Felixod
    //Ищем обзначение группы кафедры
    if (substr($groupsInfo[$key]->name, 0, 1) == '_') {
        // Если группа начинается на _, то исключить ее из списка групп
        $groupsInfo[$key]->disable = true;
    } else {
        // Если не скрыто, то ищем группа относиться к ППС или к студентами
        $groupsInfo[$key]->disable = false;
        $pieces = explode("-", $groupsInfo[$key]->name);
        if ($pieces[0] == 'ППС') {
            switch($group_type) {
                case 0:
                    $group_type = 2;
                    $groupsInfo[$key]->pps = true;
                    break;
                case 1:
                    $group_type = 3;
                    break;
            }
        } else {
            switch($group_type) {
                case 0:
                    $group_type = 1;
                    break;
                case 1:
                    $group_type = 1;
                    break;
                case 2:
                    $group_type = 3;
                    break;
            }
        }
    }

    //throw new moodle_exception($group_type);
}


// Общие категории
$categoriesInfo = array();

$categoriesData = array();
$fileCategories = block_sibportfolio_files::get_file_categories_type(0);
foreach ($fileCategories as $category) {
    $categoryInfo = new stdClass();
    $categoryInfo->id = $category->id;
    $categoryInfo->name = $category->name;
    $categoryInfo->filesCount = block_sibportfolio_files::get_count_files($categoryInfo->id, $userId);
    $categoriesData[$categoryInfo->id] = $categoryInfo;
    $categoriesInfo[] = $categoryInfo;
}

// Студенческие категории
$categoriesInfoStud = array();
$categoryInfoStud = new stdClass();
$categoryInfoStud->id = 0;
$categoryInfoStud->name = get_string('key40', 'block_sibportfolio').' '.$OUTPUT->help_icon('key40', 'block_sibportfolio');
$categoryInfoStud->filesCount = '-';
$categoriesInfoStud[] = $categoryInfoStud;
$categoriesDataStud = array();
$fileCategoriesStud = block_sibportfolio_files::get_file_categories_type(1);
foreach ($fileCategoriesStud as $categoryStud) {
    $categoryInfoStud = new stdClass();
    $categoryInfoStud->id = $categoryStud->id;
    $categoryInfoStud->name = $categoryStud->name;
    $categoryInfoStud->filesCount = block_sibportfolio_files::get_count_files($categoryInfoStud->id, $userId);
    $categoriesDataStud[$categoryInfoStud->id] = $categoryInfoStud;
    $categoriesInfoStud[] = $categoryInfoStud;
}

// Категории преподавателей
$categoriesInfoPrepod = array();
$categoriesDataPrepod = array();
$fileCategoriesPrepod = block_sibportfolio_files::get_file_categories_type(2);
foreach ($fileCategoriesPrepod as $categoryPrepod) {
    $categoryInfoPrepod = new stdClass();
    $categoryInfoPrepod->id = $categoryPrepod->id;
    $categoryInfoPrepod->name = $categoryPrepod->name;
    $categoryInfoPrepod->filesCount = block_sibportfolio_files::get_count_files($categoryInfoPrepod->id, $userId);
    $categoriesDataPrepod[$categoryInfoPrepod->id] = $categoryInfoPrepod;
    $categoriesInfoPrepod[] = $categoryInfoPrepod;
}

$userPicture = $OUTPUT->user_picture($userInfo, array('size' => 150));

$groupsCuratorInfo = null;
$curatorBlock = block_sibportfolio_i_am_curator($userId);
if ($curatorBlock) {
    $groupsCurator = block_sibportfolio_groups::get_groups_by_curator($userId);
    $groupsCuratorInfo = array();
    foreach ($groupsCurator as $group) {
        $groupInfo = new stdClass();
        $groupInfo->id = $group->id;
        $groupInfo->name = $group->name;
        $groupInfo->visible = $group->visible;
        $groupData = block_sibportfolio_groups::get_group_files($groupInfo->id);
        $groupInfo->count_users = $groupData->count_members;
        $groupInfo->count_empty = $groupInfo->count_users - $groupData->count_filled;
        $groupInfo->percent_filled = $groupInfo->count_users > 0 ? format_float($groupData->count_filled / $groupInfo->count_users * 100, 2, true, true) : 0;
        $groupsCuratorInfo[] = $groupInfo;
      
    }
}

$render = new block_sibportfolio_render('views');
$render->add_style('css/styles-plugin.css');
$render->set_title(get_string('key41', 'block_sibportfolio'));
$render->set_heading(get_string('key42', 'block_sibportfolio'));
$render->add_navigation(get_string('key41', 'block_sibportfolio').($USER->id != $userId ? ': '.$userName : ''), $PAGE->url);
$render->add_views(array(
    'navigator.html' => block_sibportfolio_get_navigator_model(),
    'profile.html' 	 => array (
        'wwwroot'            => $CFG->wwwroot,
        'user_id' 			 => $userId,
        'idnumber' 		     => $idNumber,
        'ScienceIndex' 		 => $ScienceIndex,
        'GoogleScholar'      => $GoogleScholar,
        'ORCID' 		     => $ORCID,
        'ResearcherID' 		 => $ResearcherID,
        'user_picture' 		 => $userPicture,
        'user_name'		     => $userName,
        'user_url'           => $userUrl,
        'groups_info' 		 => $groupsInfo,
        'groups_cur_info' 	 => $groupsCuratorInfo,
        'claims_block' 		 => $is_siteadmin || block_sibportfolio_is_curator($userId) || $USER->id == $userId,
        'curator_block' 	 => $curatorBlock,
        'is_curator' 		 => block_sibportfolio_is_sitecurator($userId),
        'categories' 		 => $categoriesInfo,
        'categoriesstud'	 => $categoriesInfoStud,
        'categoriesprepod'	 => $categoriesInfoPrepod,
        'grouptype'	         => $group_type,
        'user_suspended' 	 => $userInfo->suspended,
        'return_url' 		 => $PAGE->url->out(false),
        'user_name_label' 	 => get_string('key32', 'block_sibportfolio'),
        'messages_mod' 		 => get_string('key207', 'block_sibportfolio'),
        'icon_messages' 	 => $OUTPUT->pix_icon('messages', get_string('key207', 'block_sibportfolio'), 'block_sibportfolio'),
        'group_label' 		 => get_string('key33', 'block_sibportfolio'),
        'curator_name_label' => get_string('key34', 'block_sibportfolio'),
        'icon_moderator' 	 => $OUTPUT->pix_icon('moderator', get_string('key34', 'block_sibportfolio'), 'block_sibportfolio', array('height' => '18', 'width' => '18')),
        'user_files_label'   => get_string('key43', 'block_sibportfolio'),
        'category_label' 	 => get_string('key44', 'block_sibportfolio'),
        'count_label' 		 => get_string('key45', 'block_sibportfolio'),
        'review_label' 		 => get_string('key46', 'block_sibportfolio'),
        'more_label' 		 => get_string('key75', 'block_sibportfolio'),
        'view_files_label'   => get_string('key47', 'block_sibportfolio'),
		'view_samgups_label'    => get_string('key238', 'block_sibportfolio'),
        'record_label' 		 => get_string('key206', 'block_sibportfolio'),
        'speciality_label'   => get_string('key198', 'block_sibportfolio'),
        'groups_cur_label'   => get_string('key221', 'block_sibportfolio'),
        'count_users_label'  => get_string('key218', 'block_sibportfolio'),
        'percent_label' 	 => get_string('key219', 'block_sibportfolio'),
        'empty_label' 		 => get_string('key220', 'block_sibportfolio'),
        'study_label' 		 => get_string('key199', 'block_sibportfolio'),
        'apply_label' 		 => $is_siteadmin || block_sibportfolio_is_sitecurator() ? get_string('key49', 'block_sibportfolio') : get_string('key50', 'block_sibportfolio')
    )
));

if ($is_siteadmin || block_sibportfolio_is_sitecurator()) {
    $render->add_script('scripts/jquery-1.11.3.min.js');
    $render->add_view('profile-js.html', array('wwwroot' => $CFG->wwwroot));
}

$render->display();

$event = \block_sibportfolio\event\profile_viewed::create(array(
    'objectid'	    => $userId,
    'relateduserid' => $userId
));
$event->trigger();
