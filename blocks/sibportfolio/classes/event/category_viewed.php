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

namespace block_sibportfolio\event;
defined('MOODLE_INTERNAL') || die();

class category_viewed extends \core\event\base {
    protected function init() {
        $this->context = \context_system::instance();
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'sibport_files';
    }

    public static function get_name() {
        return get_string('key236', 'block_sibportfolio');
    }

    public function get_description() {
        $desc = "The user with id {$this->data['userid']} viewed the category with id {$this->data['objectid']}";
        if ($this->data['userid'] != $this->data['relateduserid']) $desc .= " of the user with id {$this->data['relateduserid']}";
        return $desc;		
    }

    public function get_url() {
        return $this->data['objectid'] == 0 ?
            new \moodle_url('/blocks/sibportfolio/moodlefiles.php', array('userid' => $this->data['relateduserid'])) :
            new \moodle_url('/blocks/sibportfolio/userfiles.php', array('userid' => $this->data['relateduserid'], 'categoryid' => $this->data['objectid']));
    }
}
