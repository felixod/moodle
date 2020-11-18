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
 * Plugin administration pages are defined here.
 *
 * @package     mod_lanebs
 * @category    admin
 * @copyright   2020 Senin Yurii <katorsi@mail.ru>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    global $PAGE;
    //$PAGE->requires->js_call_amd('mod_lanebs/modal_utils', 'init');
    //$PAGE->requires->js_call_amd('mod_forum/discussion', 'init');
   // TODO: Define the plugin settings page.
   // https://docs.moodle.org/dev/Admin_settings

    /*$settings->add(new admin_setting_configmultiselect('lanebs/domain_name',
        get_string('domain_name', 'mod_lanebs'), get_string('domain_name_additional', 'mod_lanebs'),
        array_keys($options), $options), 'www.test.test');*/

    /*$settings->add(new admin_setting_configtext_with_advanced('lanebs/timelimit',
        get_string('domain_name', 'mod_lanebs'), get_string('domain_name_additional', 'mod_lanebs'),
        array('value' => '0', 'fix' => false), PARAM_INT));*/

    /*$settings->add(new admin_setting_configtext('lanebs/name',
        get_string('lanebs:name', 'mod_lanebs'), get_string('lanebs:name_desc', 'mod_lanebs'),
        get_string('lanebs:name_default', 'mod_lanebs')));*/
    $settings->add(new admin_setting_configtext('lanebs/token',
        get_string('lanebs:token', 'mod_lanebs'),
        get_string('lanebs:token_desc', 'mod_lanebs'), ''));
    /* $settings->add(new admin_setting_configmultiselect('lanebs/users', get_string('users', 'mod_lanebs'), get_string('tokens', 'mod_lanebs')
    ); */
    /* $settings->add(new admin_setting_configtext('lanebs/login',
        get_string('lanebs:login', 'mod_lanebs'), get_string('lanebs:login_desc', 'mod_lanebs'),
        get_string('lanebs:login_default', 'mod_lanebs')));

    $settings->add(new admin_setting_configtext_with_maxlength('lanebs/token', get_string('lanebs:token', 'mod_lanebs'),
        get_string('lanebs:token_desc', 'mod_lanebs'), '')); */
}
