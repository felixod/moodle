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

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_configtext('block_sibportfolio/max_claims', get_string('key189', 'block_sibportfolio'), 
                   get_string('key190', 'block_sibportfolio'), 10, PARAM_INT, 10));

    if (isset($CFG->maxbytes)) {
        $maxbytes = get_config('block_sibportfolio', 'max_filesize');
        $max_upload_choices = get_max_upload_sizes($CFG->maxbytes, 0, 0, $maxbytes);
        $settings->add(new admin_setting_configselect('block_sibportfolio/max_filesize', get_string('key191', 'block_sibportfolio'),
                       get_string('key192', 'block_sibportfolio'), 1048576, $max_upload_choices));
    }
        
    $settings->add(new admin_setting_configtextarea('block_sibportfolio/locked', get_string('key193', 'block_sibportfolio'), 
                   get_string('key194', 'block_sibportfolio'), null, PARAM_NOTAGS, 50, 3));
        
    $settings->add(new admin_setting_configselect('block_sibportfolio/autosync', get_string('key232', 'block_sibportfolio'),
        get_string('key233', 'block_sibportfolio'), 0, array(0 => get_string('key235', 'block_sibportfolio'), 5 => 5, 10 => 10, 15 => 15, 20 => 20)));
        
    $settings->add(new admin_setting_configcheckbox('block_sibportfolio/sync', get_string('key228', 'block_sibportfolio'),
                   get_string('key229', 'block_sibportfolio'), 0));	
        
}
