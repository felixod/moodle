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

defined('MOODLE_INTERNAL') || die();

function block_sibportfolio_require_login_system($url = null, $params = null)
{
    global $PAGE;
    if (isset($url)) $PAGE->set_url($url, $params);
    require_login(1);
    if (!isloggedin() || isguestuser()) {
        block_sibportfolio_notification(get_string('key86', 'block_sibportfolio'));
    }
}

function block_sibportfolio_is_sitecurator($userId = null) // 28.11.2015
{
    global $USER, $DB;
    $curatorId = isset($userId) ? $userId : $USER->id;
    $result = $DB->count_records('sibport_group_data', array('curatorid' => $curatorId));
    return $result > 0;
}

function block_sibportfolio_is_curator($userId) // 29.11.2015
{
    global $USER, $DB;
    $query = "SELECT COUNT(*) 
                FROM {sibport_group_data} gc 
          INNER JOIN {cohort_members} cm ON (gc.groupid = cm.cohortid AND cm.userid = ?) 
               WHERE gc.curatorid = ?";
    $result = $DB->count_records_sql($query, array($userId, $USER->id));
    return $result > 0;
}

function block_sibportfolio_is_group_curator($groupId)
{
    global $USER, $DB;
    $curatorInfo = $DB->get_record('sibport_group_data', array('groupid' => $groupId));
    return $curatorInfo && $curatorInfo->curatorid == $USER->id;
}

function block_sibportfolio_i_am_curator($userId)
{
    global $USER;
    return ($USER->id == $userId && block_sibportfolio_is_sitecurator());
}

function block_sibportfolio_redirect($url, $permanent = false)
{
    $reference = $url instanceof moodle_url ? $url : new moodle_url($url);
    header('Location: '.$reference->out(false), true, $permanent ? 301 : 302);
    exit();
}

function block_sibportfolio_notify_count_claims()
{
    global $USER, $CFG;
    $countClaims = '';
    if (is_siteadmin() || block_sibportfolio_is_sitecurator()) {
        require_once($CFG->dirroot.'/blocks/sibportfolio/libs/claims.php');
        $countClaims = '+'.block_sibportfolio_claims::get_count_wait_claims(is_siteadmin() ? null : $USER->id);
        if ($countClaims == 0) $countClaims = '';
    }
    return $countClaims;
}

function block_sibportfolio_notification($message, $title = '')
{
    global $OUTPUT, $PAGE;
    $PAGE->set_title(trim($title) == false ? get_string('error') : $title);
    echo $OUTPUT->header();
    echo $OUTPUT->notification($message);
    echo $OUTPUT->footer();
    die();
}

function block_sibportfolio_print_message($message, $returnUrl)
{
    global $OUTPUT;
    echo $OUTPUT->header();
    echo $OUTPUT->box_start('generalbox', 'notice');
    echo html_writer::tag('p', $message);
    echo $OUTPUT->single_button($returnUrl, get_string('key30', 'block_sibportfolio'), 'get');
    echo $OUTPUT->box_end();
    echo $OUTPUT->footer();
    die();
}

function block_sibportfolio_get_config_sync()
{
    $configSync = get_config('block_sibportfolio', 'sync');
    return $configSync == true;
}

function block_sibportfolio_to_lower($element)
{
    return mb_strtolower($element, 'UTF-8');
}

function block_sibportfolio_get_claim_type_name($claimType)
{		
    switch ($claimType) {
        case 0:
            return get_string('key90', 'block_sibportfolio');
        case 1:
            return get_string('key84', 'block_sibportfolio');
        case 2:
            return get_string('key82', 'block_sibportfolio');
    }
    return false;
}

function block_sibportfolio_get_claim_status_name($claimStatus)
{
    switch ($claimStatus) {
        case 0:
            return get_string('key91', 'block_sibportfolio');
        case 1:
            return get_string('key92', 'block_sibportfolio');
    }
    return false;	
}

function block_sibportfolio_is_ajax_request() 
{
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
	//return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

function block_sibportfolio_page_helper($currentPage, $totalPages)
{
    $pages = array();
    if ($totalPages == 1) {
        // ничего не делать
    } else if ($totalPages < 4) {
        for ($i = 0; $i < $totalPages; $i++) {
            $pages[] = $i + 1;
        }
    } else if ($currentPage < 3) {
        for ($i = 0; $i < 3; $i++) {
            $pages[] = $i + 1;
        }
        $pages[] = null;
        $pages[] = $totalPages;
    } else if ($currentPage >= $totalPages - 1) {
        $pages[] = 1;
        $pages[] = null;
        for ($i = $totalPages - 3; $i < $totalPages; $i++) {
            $pages[] = $i + 1;
        }
    } else {
        $pages[] = 1;
        $pages[] = null;
        for ($i = $currentPage - 1; $i <= $currentPage + 1; $i++) {
            $pages[] = $i;
        }
        $pages[] = null;
        $pages[] = $totalPages;
    }
    
    $result = array();
    for ($i = 0; $i < count($pages); $i++) {
        $page = new stdClass();
        if (is_numeric($pages[$i])) {
            $page->url = true;
            $page->caption = $pages[$i];
        } else {
            $page->url = false;
            $page->caption = null;
        }
        $page->style = $page->caption == $currentPage ? 'font-weight: bold;' : '';
        $result[] = $page;
    }
    
    return $result;
}

function block_sibportfolio_get_navigator_model()
{
    global $CFG, $PAGE;
    return array (
        'wwwroot' 		   => $CFG->wwwroot,
        'return_url' 	   => urlencode($PAGE->url->out(false)),
        'is_admin' 	   	   => is_siteadmin(),
        'is_curator' 	   => block_sibportfolio_is_sitecurator(),
        'is_viewer' 	   => has_capability('block/sibportfolio:viewer', context_system::instance()),
        'count_claims'     => block_sibportfolio_notify_count_claims(),
        'profile_label'    => get_string('key17', 'block_sibportfolio'),
        'app_label' 	   => get_string('key18', 'block_sibportfolio'),
        'users_label' 	   => get_string('key19', 'block_sibportfolio'),
        'log_label' 	   => get_string('key20', 'block_sibportfolio'),
        'groups_label' 	   => get_string('key21', 'block_sibportfolio'),
        'appv_label'	   => get_string('key22', 'block_sibportfolio'),
        'menu_label' 	   => get_string('key23', 'block_sibportfolio'),
        'report_label' 	   => get_string('key24', 'block_sibportfolio'),
        'ureport_label'    => get_string('key182', 'block_sibportfolio'),
        'categories_label' => get_string('key174', 'block_sibportfolio'),
        'records_label'    => get_string('key204', 'block_sibportfolio')
    );
}

function block_sibportfolio_get_ajax_model()
{
    global $CFG;
    return array (
        'loading_label' => get_string('key226', 'block_sibportfolio'),
        'error_label'   => get_string('key227', 'block_sibportfolio'),
        'wwwroot'		=> $CFG->wwwroot,
    );
}
