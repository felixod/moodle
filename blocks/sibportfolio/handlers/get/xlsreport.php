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
require_once($CFG->libdir.'/excellib.class.php');

$reportData = $SESSION->block_sibportfolio_report ? $SESSION->block_sibportfolio_report : null;

$user = isset($reportData->user) ? $reportData->user : null;
$sort = isset($reportData->sort) ? $reportData->sort : 0;
if ($sort < 0 || $sort > 2) $sort = 0;

block_sibportfolio_require_login_system('/blocks/sibportfolio/handlers/get/xlsreport.php');

if (!is_siteadmin()) {
    $SESSION->block_sibportfolio_report = null;
    print_error('key67', 'block_sibportfolio');
}

$strreport = get_string('key68', 'block_sibportfolio');
$downloadfilename = clean_filename("$strreport.xls");
$workbook = new MoodleExcelWorkbook("-");
$workbook->send($downloadfilename);
$myxls = $workbook->add_worksheet($strreport);

// Формирование заголовка таблицы
$myxls->write_string(0, 0, get_string('key34', 'block_sibportfolio'));
$myxls->write_string(0, 1, get_string('lastaccess'));
$myxls->write_string(0, 2, get_string('key35', 'block_sibportfolio'));
$myxls->write_string(0, 3, get_string('key33', 'block_sibportfolio'));
$myxls->write_string(0, 4, get_string('key218', 'block_sibportfolio'));
$myxls->write_string(0, 5, get_string('key220', 'block_sibportfolio'));

$index = 1;
$curators = block_sibportfolio_users::get_curators_data($user, $sort)->curators;
foreach ($curators as $curator) {
    $fullname = fullname($curator);
    $laccess = $curator->lastaccess ? userdate($curator->lastaccess, get_string('strftimerecentfull')) : get_string('never');
    $groups = block_sibportfolio_groups::get_groups_by_curator($curator->id);
    foreach ($groups as $group) {
        $groupfiles = block_sibportfolio_groups::get_group_files($group->id);
        $myxls->write_string($index, 0, $fullname);
        $myxls->write_string($index, 1, $laccess);
        $myxls->write_number($index, 2, $curator->count_claims);
        $myxls->write_string($index, 3, $group->name);
        $myxls->write_number($index, 4, $groupfiles->count_members);
        $myxls->write_number($index, 5, $groupfiles->count_members - $groupfiles->count_filled);
        $index++;
    }
}

$workbook->close();
