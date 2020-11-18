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
 * Code to be executed after the plugin's database scheme has been installed is defined here.
 *
 * @package     mod_lanebs
 * @category    upgrade
 * @copyright   2020 Senin Yurii <katorsi@mail.ru>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Custom code to be run on installing the plugin.
 */
function xmldb_lanebs_install() {
    global $DB;

    $obj = new stdClass();
    $obj->name = 'Библиотечная интеграция';
    $obj->sortorder = 1;
    $result = $DB->get_record('user_info_category', array('name' => $obj->name), '*');
    if (!$result) {
        $id = $DB->insert_record('user_info_category', $obj);
    }
    else {
        $id = $result->id;
    }

    $obj = new stdClass();
    $obj->shortname = 'mod_lanebs_token';
    $obj->name = 'Токен подписчика';
    $obj->datatype = 'text';
    $obj->description = '<p>Токен подписчика для доступа к читалке</p>';
    $obj->descriptionformat = 1;
    $obj->categoryid = $id;
    $obj->sortorder = 0;
    $obj->sortorder = 0;
    $obj->required = 0;
    $obj->locked = 0;
    $obj->visible = 0;
    $obj->forceunique = 0;
    $obj->signup = 0;
    $obj->defaultdataformat = 0;
    $obj->param1 = 30;
    $obj->param2 = 2048;
    $obj->param3 = 0;
    $result = $DB->get_record('user_info_field', array('shortname' => $obj->shortname), '*');
    if (!$result) {
        $DB->insert_record('user_info_field', $obj);
    }
    purge_all_caches();
    return true;
}
