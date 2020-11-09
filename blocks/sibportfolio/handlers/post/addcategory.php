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

$category = trim(required_param('category', PARAM_NOTAGS));

block_sibportfolio_require_login_system('/blocks/sibportfolio/handlers/post/addcategory.php', array('category' => $category));

if (data_submitted() && confirm_sesskey() && is_siteadmin() && 
    !empty($category) && mb_strlen($category, 'UTF-8') <= 250) {
    block_sibportfolio_files::add_file_category($category);
}

block_sibportfolio_redirect(new moodle_url('/blocks/sibportfolio/categories.php'));
