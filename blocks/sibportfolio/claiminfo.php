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

$claimId = optional_param('claimid', 0, PARAM_INT);

$returnUrl = optional_param('returnurl', $CFG->wwwroot.'/blocks/sibportfolio/index.php', PARAM_URL);

block_sibportfolio_require_login_system('/blocks/sibportfolio/claiminfo.php', array('claimid' => $claimId, 'returnurl' => $returnUrl));

if ($claim = block_sibportfolio_check_access($claimId)) {

    $userInfo = block_sibportfolio_users::get_user_by_id($claim->userid);
    $userName = fullname($userInfo);

    $header = '';
    $claimInfo = array();
    $claimInfo[] = array('head' => get_string('key1', 'block_sibportfolio'), 'row_value' => $userName);

    $action = is_siteadmin() || block_sibportfolio_is_sitecurator();

    switch ($claim->claimtype) {

        case 0:
            $icon = 'add';
            $header = get_string('key2', 'block_sibportfolio');
            $file = block_sibportfolio_files::get_file_by_claim_id($claimId);
            $fileLink = '<i>'.get_string('key172', 'block_sibportfolio').'</i>';
            if ($file) {
                $fileLink = block_sibportfolio_files::get_sibport_file_link($file);
                if ($action) $fileLink .= ' <i>('.round($file->filesize / 1048576, 2).' '.get_string('key196', 'block_sibportfolio').')</i>';
            }
            $claimInfo[] = array('head' => get_string('key3', 'block_sibportfolio'), 'row_value' => $claim->description2, 'class' => 'block_sibportfolio_green');
            $claimInfo[] = array('head' => get_string('key4', 'block_sibportfolio'), 'row_value' =>
                                 block_sibportfolio_files::get_file_category_by_id($claim->category2)->name, 'class' => 'block_sibportfolio_green');
            $claimInfo[] = array('head' => get_string('key5', 'block_sibportfolio'), 'row_value' => $fileLink, 'class' => 'block_sibportfolio_green');
            break;

        case 1:
            $icon = 'edit';
            $header = get_string('key6', 'block_sibportfolio');
            $file = block_sibportfolio_files::get_file_by_claim_id($claimId);
            $claimInfo[] = array('head' => get_string('key74', 'block_sibportfolio'), 'row_value' => $claim->usercomment);
            $claimInfo[] = array('head' => get_string('key8', 'block_sibportfolio'), 'row_value' => $claim->description, 'class' => 'block_sibportfolio_red');
            $claimInfo[] = array('head' => get_string('key9', 'block_sibportfolio'), 'row_value' =>
                                 block_sibportfolio_files::get_file_category_by_id($claim->category)->name, 'class' => 'block_sibportfolio_red');
            $claimInfo[] = array('head' => get_string('key11', 'block_sibportfolio'), 'row_value' => $claim->description2, 'class' => 'block_sibportfolio_green');
            $claimInfo[] = array('head' => get_string('key12', 'block_sibportfolio'), 'row_value' =>
                                 block_sibportfolio_files::get_file_category_by_id($claim->category2)->name, 'class' => 'block_sibportfolio_green');
            $claimInfo[] = array('head' => get_string('key5', 'block_sibportfolio'), 'row_value' => $file ?
                                 block_sibportfolio_files::get_sibport_file_link($file) :
                                 '<i>'.get_string('key172', 'block_sibportfolio').'</i>', 'class' => 'block_sibportfolio_green');
            break;

        case 2:
            $icon = 'delete';
            $header = get_string('key15', 'block_sibportfolio');
            $file = block_sibportfolio_files::get_file_by_claim_id($claimId);
            $claimInfo[] = array('head' => get_string('key74', 'block_sibportfolio'), 'row_value' => $claim->usercomment);
            $claimInfo[] = array('head' => get_string('key3', 'block_sibportfolio'), 'row_value' => $claim->description2, 'class' => 'block_sibportfolio_red');
            $claimInfo[] = array('head' => get_string('key4', 'block_sibportfolio'), 'row_value' =>
                                 block_sibportfolio_files::get_file_category_by_id($claim->category2)->name, 'class' => 'block_sibportfolio_red');
            $claimInfo[] = array('head' => get_string('key5', 'block_sibportfolio'), 'row_value' => $file ?
                                 block_sibportfolio_files::get_sibport_file_link($file) :
                                 '<i>'.get_string('key172', 'block_sibportfolio').'</i>', 'class' => 'block_sibportfolio_red');
            break;

    }

    $render = new block_sibportfolio_render('views');
    $render->set_title(get_string('key16', 'block_sibportfolio'));
    $render->set_heading(get_string('key16', 'block_sibportfolio'));
    $render->add_navigation(get_string('key41', 'block_sibportfolio').($USER->id != $claim->userid ? ': '.$userName : ''),
                            new moodle_url('/blocks/sibportfolio/index.php', array('userid' => $claim->userid)));
    $render->add_navigation(get_string('key70', 'block_sibportfolio'), $returnUrl);
    $render->add_navigation($header, $PAGE->url);
    $render->add_views(array(
        'navigator.html' => block_sibportfolio_get_navigator_model(),
        'claiminfo.html' => array (
            'wwwroot'      => $CFG->wwwroot,
            'claim_id'     => $claimId,
            'claim_info'   => $claimInfo,
            'self' 		   => $claim->userid == $USER->id,
            'sesskey' 	   => sesskey(),
            'return_url'   => $returnUrl,
            'icon_name'    => $icon,
            'header_label' => $header,
            'accept_label' => get_string('key96', 'block_sibportfolio'),
            'reject_label' => get_string('key125', 'block_sibportfolio'),
            'undo_label'   => get_string('key203', 'block_sibportfolio'),
            'claims_block' => $action
        )
    ));
    $render->display();

} else block_sibportfolio_redirect(new moodle_url($returnUrl));

function block_sibportfolio_check_access($claimId) {
    global $USER;
    $claim = block_sibportfolio_claims::get_claim_by_id($claimId);
    if (!$claim || !isset($claim->claimtype)) {
        //print_error('key28', 'block_sibportfolio');
        return false;
    } else if (!(is_siteadmin() || block_sibportfolio_is_curator($claim->userid) || $USER->id == $claim->userid)) {
        print_error('key29', 'block_sibportfolio');
        return false;
    } else {
        return $claim;
    }
}
