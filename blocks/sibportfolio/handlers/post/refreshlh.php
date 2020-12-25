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

require_once('../../lib.php');
require_once($CFG->dirroot.'/blocks/sibportfolio/libs/savior.php');

$userId = required_param('userid', PARAM_INT);

block_sibportfolio_require_login_system('/blocks/sibportfolio/handlers/post/refreshlh.php', array('userid' => $userId));

block_sibportfolio_users::user_exist($userId);

$viewer = has_capability('block/sibportfolio:viewer', context_system::instance());
$canview = block_sibportfolio_get_config_sync() && ($viewer || block_sibportfolio_is_curator($userId) || $USER->id == $userId);

if (data_submitted() && confirm_sesskey() && (is_siteadmin() || $canview)) {
    block_sibportfolio_learning_history::synchronize_user($userId);
}

block_sibportfolio_redirect(new moodle_url('/blocks/sibportfolio/moodlefiles.php', array('userid' => $userId)));
