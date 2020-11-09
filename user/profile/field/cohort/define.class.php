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
 * Information cohort profile field.
 *
 * @package    profilefield_cohort
 * @copyright  2019 onwards Sergey Gorbatov {@link http://www.ipmimp.ru}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


class profile_define_cohort extends profile_define_base {

    /**
     * Create elements for insert/edit cohort profile field.
     * @param moodleform $form
     */
    public function define_form_specific($form) {
        // Default data
        $form->addElement('text', 'defaultdata', get_string('profiledefaultdata', 'admin'), 'size="50"');
        $form->setType('defaultdata', PARAM_TEXT);
        // Param 1 for show cohort name.
        $form->addElement('selectyesno', 'param1', get_string('name', 'cohort'));
        $form->setDefault('param1', 1); // Defaults to 'yes'.
        $form->setType('param1', PARAM_INT);
        // Param 2 for show cohort idnamber.
        $form->addElement('selectyesno', 'param2', get_string('idnumber', 'cohort'));
        $form->setDefault('param2', 0); // Defaults to 'no'.
        $form->setType('param2', PARAM_INT);
        // Param 3 for show cohort decription.
        $form->addElement('selectyesno', 'param3', get_string('description', 'cohort'));
        $form->setDefault('param3', 0); // Defaults to 'no'.
        $form->setType('param3', PARAM_INT);
        // Param 4 for show hidden cohort.
        $form->addElement('selectyesno', 'param4', get_string('hiddencohort', 'profilefield_cohort'));
        $form->setDefault('param4', 0); // Defaults to 'no'.
        $form->setType('param4', PARAM_INT);
    }
}