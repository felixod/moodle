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

require_once ('../../lib.php');

$claimId = required_param('claimid', PARAM_INT);

block_sibportfolio_require_login_system('/blocks/sibportfolio/handlers/post/rejectclaim.php', array('claimid' => $claimId));

if ($claim = block_sibportfolio_check_access($claimId)) {
    $userId = $claim->userid;
    if ($claim->claimtype == 0) {
        $fs = get_file_storage();
        $fs->delete_area_files(context_user::instance($userId)->id, 'block_sibportfolio', 'sibport_files', $claimId);	
    }
    block_sibportfolio_claims::reject_claim($claim);
    $event = \block_sibportfolio\event\claim_deleted::create(array(
        'objectid' => $claimId,
        'other'    => array('claimtype' => $claim->claimtype)
    ));
    $event->trigger();
    block_sibportfolio_fredirect($userId);
} else block_sibportfolio_fredirect($USER->id);

function block_sibportfolio_check_access($claimId) {
    global $USER;
    $claim = block_sibportfolio_claims::get_claim_by_id($claimId);
    if (!data_submitted() || !confirm_sesskey()) {
        return false;
    } else if (!$claim || !isset($claim->claimtype)) {
        //print_error('key28', 'block_sibportfolio');
        return false;
    } else if ($claim->userid != $USER->id) {
        return false;
    } else {
        return $claim;
    }
}

function block_sibportfolio_fredirect($userId) {
    global $CFG;
    if (block_sibportfolio_claims::get_count_wait_claims_by_user($userId) == 0) {
        block_sibportfolio_redirect(new moodle_url('/blocks/sibportfolio/index.php'));
    } else {
        $returnUrl = optional_param('returnurl', $CFG->wwwroot.'/blocks/sibportfolio/index.php', PARAM_URL);
        block_sibportfolio_redirect(new moodle_url($returnUrl));
    }
}
