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
block_sibportfolio_require_login_system('/blocks/sibportfolio/categories.php');

if (!is_siteadmin()) {
    print_error('key67', 'block_sibportfolio');
}

$notification = null;
$notification_type = null;
if (optional_param('editCat', false, PARAM_BOOL) && confirm_sesskey() && data_submitted()) {

    $categoryName = trim(required_param('category', PARAM_NOTAGS));
    $categoryId = required_param('categoryid', PARAM_INT);
    $category = block_sibportfolio_files::get_file_category_by_id($categoryId);

    $category2 = block_sibportfolio_files::get_file_category_by_name($categoryName);
    $exist = $category2 && ($category2->id != $categoryId);

    if ($category && !empty($categoryName) && mb_strlen($categoryName, 'UTF-8') <= 250 && !$exist) {
        if ($category->name != $categoryName) {
            block_sibportfolio_files::edit_file_category($categoryId, $categoryName);
            $notification = get_string('changessaved');
            $notification_type = 'alert-success';
        }
    } else {
        $notification = get_string('errorwithsettings', 'admin');
        $notification_type = 'alert-error';
    }
}

$categories = block_sibportfolio_files::get_file_categories();
$find = count($categories) > 0;

$render = new block_sibportfolio_render('views');
$render->add_style('css/styles-plugin.css');
$render->set_title(get_string('key174', 'block_sibportfolio'));
$render->set_heading(get_string('key174', 'block_sibportfolio'));
$render->add_navigation(get_string('key41', 'block_sibportfolio'), new moodle_url('/blocks/sibportfolio/index.php', array('userid' => $USER->id)));
$render->add_navigation(get_string('key174', 'block_sibportfolio'), $PAGE->url);
$render->add_views(array(
    'navigator.html'  => block_sibportfolio_get_navigator_model(),
    'categories.html' => array (
        'wwwroot'			   => $CFG->wwwroot,
        'sesskey' 			   => sesskey(),
        'categories' 		   => $categories,
        'find' 				   => $find,
        'notification_message' => $notification,
        'notification_type'    => $notification_type,
        'header_label'         => get_string('key175', 'block_sibportfolio'),
        'category_label'       => get_string('key44', 'block_sibportfolio'),
        'next_label'           => get_string('key180', 'block_sibportfolio'),
        'edit_category_label'  => get_string('key84', 'block_sibportfolio'),
        'del_category_label'   => get_string('key176', 'block_sibportfolio'),
        'add_category_label'   => get_string('key177', 'block_sibportfolio'),
        'not_found_label' 	   => get_string('key94', 'block_sibportfolio')
    )
));
$render->display();
