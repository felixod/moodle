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

$groupName = optional_param('groupname', null, PARAM_RAW_TRIMMED);

block_sibportfolio_require_login_system('/blocks/sibportfolio/handlers/ajax/findgroup.php', array('groupname' => $groupName));

$viewer = has_capability('block/sibportfolio:viewer', context_system::instance());

if ($viewer || block_sibportfolio_is_sitecurator()) {

    header('Content-Type: application/json');

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
    
    echo json_encode($groups);

}

function block_sibportfolio_array_push($array, $id, $key_name) {
    $temp = new stdClass();
    $temp->id = $id;
    $temp->name = get_string($key_name, 'block_sibportfolio');
    $array[] = $temp;
    return $array;
}
