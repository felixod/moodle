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

$userName = optional_param('user', '', PARAM_RAW_TRIMMED);

block_sibportfolio_require_login_system('/blocks/sibportfolio/handlers/ajax/finduser.php', array('user' => $userName));

header('Content-Type: application/json');

$users = array();

if ($userName != '') {		

    $foundedusers = block_sibportfolio_users::get_users_by_name($userName, true);

    $user = new stdClass();
    $user->id = 'none';
    $user->fullname = get_string('key93', 'block_sibportfolio');
    $users[] = $user;

    if ($foundedusers === 0) { // превышен лимит
        $users[0]->fullname = get_string('key95', 'block_sibportfolio');
    } else if (count($foundedusers) == 0) { // не найдено
        $users[0]->fullname = get_string('key94', 'block_sibportfolio');
    } else { // найдено
        foreach ($foundedusers as $foundeduser)	{
            $user = new stdClass();
            $user->id = $foundeduser->id;
            $user->fullname = fullname($foundeduser) . '&nbsp;(' . $foundeduser->email . ')';
            $users[] = $user;
        }
    }

} else {
    $users[0]->fullname = get_string('key94', 'block_sibportfolio');
}

echo json_encode($users);
