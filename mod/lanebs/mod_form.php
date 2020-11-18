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
 * The main mod_lanebs configuration form.
 *
 * @package     mod_lanebs
 * @copyright   2020 Senin Yurii <katorsi@mail.ru>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package    mod_lanebs
 * @copyright  2020 Senin Yurii <katorsi@mail.ru>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_lanebs_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG, $PAGE, $DB, $USER;
        $mform = $this->_form;
        $settings = get_config("lanebs");
        if (isset($settings->token) && !empty($settings->token)) {
            $_SESSION['subscriberToken'] = $settings->token;
        }
        else if (isset($USER->profile['mod_lanebs_token']) && !empty($USER->profile['mod_lanebs_token'])) {
            $_SESSION['subscriberToken'] = $USER->profile['mod_lanebs_token'];
        }
        $PAGE->requires->js_call_amd('mod_lanebs/modal_search_handle', 'init');
        //$PAGE->requires->js_call_amd('mod_lanebs/modal_book_handle', 'init');

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('lanebsname', 'mod_lanebs'), array('size' => '64'));

        $mform->addElement('hidden', 'content', '');
        $mform->setType('content', PARAM_TEXT);
        $mform->addRule('content', get_string('content_error', 'mod_lanebs'), 'required', null, 'client');

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'lanebsname', 'mod_lanebs');

        // Adding the standard "intro" and "introformat" fields.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }

        $mform->addElement('button', 'modal_show_button', get_string('button_desc', 'mod_lanebs'));
        $mform->addElement('text', 'content_name', 'Выбранный ресурс', ['style' => 'width:100%']);
        $mform->setType('content_name', PARAM_TEXT);
        $mform->addRule('content_name', null, 'required', null, 'client');
        $mform->addHelpButton('modal_show_button', 'lanebsbutton', 'mod_lanebs');

        // Adding the rest of mod_lanebs settings, spreading all them into this fieldset
        // ... or adding more fieldsets ('header' elements) if needed for better logic.
        /*$mform->addElement('static', 'label1', 'lanebssettings', get_string('lanebssettings', 'mod_lanebs'));
        $mform->addElement('header', 'lanebsfieldset', get_string('lanebsfieldset', 'mod_lanebs'));*/

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }
}
