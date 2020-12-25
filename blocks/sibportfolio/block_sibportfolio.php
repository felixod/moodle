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

class block_sibportfolio extends block_list {

    function init() {
        $this->title = get_string('pluginname', 'block_sibportfolio');
    }

    function instance_allow_multiple() {
        return false;
    }

    function instance_allow_config() {
        return false;
    }

    function has_config() {
        return true;
    }

    function get_content() {
        global $CFG, $PAGE;
        require_once($CFG->dirroot.'/blocks/sibportfolio/libs/system.php');

        /*if (!isloggedin() || isguestuser()) {
            $this->content = '';
            return $this->content;
        }*/

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $this->content->items[] = html_writer::tag('a', get_string('key171', 'block_sibportfolio'), array('href' => $CFG->wwwroot.'/blocks/sibportfolio/index.php'));
        $this->content->icons[] = html_writer::empty_tag('img', array('src' => $CFG->wwwroot.'/blocks/sibportfolio/pix/sibport.png'));
		
        if ((isloggedin() && !isguestuser()) && (is_siteadmin() || block_sibportfolio_is_sitecurator())) {
            $itemcontent = html_writer::tag('a', get_string('key22', 'block_sibportfolio'), array('href' => $CFG->wwwroot.'/blocks/sibportfolio/handleclaims.php'));
            $requests = block_sibportfolio_notify_count_claims();
            if (!empty($requests)) {
                $PAGE->requires->css('/blocks/sibportfolio/css/styles-alert.css');
                $itemcontent .= ' ' . html_writer::tag('span', $requests, array('class' => 'block_sibportfolio_requests'));
            }
            $this->content->items[] = $itemcontent;
            $this->content->icons[] = html_writer::empty_tag('img', array('src' => $CFG->wwwroot.'/blocks/sibportfolio/pix/waits.png'));
        }
        return $this->content;
    }

    function cron() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/blocks/sibportfolio/libs/files.php');
        require_once($CFG->dirroot.'/blocks/sibportfolio/libs/savior.php');
        require_once($CFG->dirroot.'/comment/lib.php');

        $fs = get_file_storage();
        $date = time() - 604800;
        $records = $DB->get_records_select('sibport_files', "userid = 0 AND timemodified < ?", array($date), 'id');
        foreach ($records as $record) { // удаление записей, для которых файл был удален
            $file = block_sibportfolio_files::get_file_by_claim_id($record->id);
            if ($file) $fs->delete_area_files($file->contextid, 'block_sibportfolio', 'sibport_files', $file->itemid);
            comment::delete_comments(array('contextid' => $file->contextid, 'component' => 'block_sibportfolio', 'commentarea' => 'file_comments', 'itemid' => $record->id));
            $DB->execute("UPDATE {sibport_claim_log} SET itemid = NULL WHERE itemid = ?", array($record->id));
            $DB->delete_records('sibport_files', array('id' => $record->id));
        }

        $synclimit = get_config('block_sibportfolio', 'autosync');
        if ($synclimit > 0) block_sibportfolio_learning_history::synchronize($synclimit); // синхронизация файлов курсов с портфолио

        return true;
    }
}
