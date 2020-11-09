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
require_once($CFG->dirroot.'/blocks/sibportfolio/handlers/forms/forms.php');

$categoryId = required_param('categoryid', PARAM_INT);

block_sibportfolio_require_login_system('/blocks/sibportfolio/handlers/forms/delcategory.php', array('categoryid' => $categoryId));

$PAGE->set_title(get_string('key180', 'block_sibportfolio'));

if ($category = block_sibportfolio_check_access($categoryId)) {
        
    $PAGE->set_heading(get_string('key180', 'block_sibportfolio').' «'.$category->name.'»');
    
    $customData = array('categoryid' => $categoryId);
    $mform = new delCategoryForm(null, $customData);
    
    if ($mform->is_cancelled()) {

        block_sibportfolio_redirect(new moodle_url('/blocks/sibportfolio/categories.php'));
        
    } else if ($fromform = $mform->get_data()) {

        block_sibportfolio_files::del_file_category($categoryId, $fromform->newcategory);
        block_sibportfolio_redirect(new moodle_url('/blocks/sibportfolio/categories.php'));
    
    } else {

        $PAGE->navbar->ignore_active();
        $PAGE->navbar->add(get_string('key41', 'block_sibportfolio'), new moodle_url('/blocks/sibportfolio/index.php', array('userid' => $USER->id)));
        $PAGE->navbar->add(get_string('key174', 'block_sibportfolio'), new moodle_url('/blocks/sibportfolio/categories.php'));
        $PAGE->navbar->add(get_string('key180', 'block_sibportfolio'), $PAGE->url);		
        $mform->set_data(array(
            'categoryid' => $categoryId
        ));
        block_sibportfolio_form_render($mform);
        
    }

}

function block_sibportfolio_check_access($categoryId) {
    $category = block_sibportfolio_files::get_file_category_by_id($categoryId);
    if (!is_siteadmin()) {	
        print_error('key67', 'block_sibportfolio');
        return false;
    } if (!$category) {
        print_error('key78', 'block_sibportfolio');
        return false;
    } else {
        return $category;
    }
}
