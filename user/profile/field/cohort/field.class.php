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

class profile_field_cohort extends profile_field_base {
    
    /**
    * Get from data base cohort information.
    * @param tinyint $param
    * @return array
    */
    private function profile_field_get_cohort($param) {
        global $DB;
        $sql = 'SELECT name, {cohort}.idnumber, {cohort}.description, {cohort}.visible '
                . 'FROM ({cohort} INNER JOIN {cohort_members} '
                . 'ON {cohort}.id={cohort_members}.cohortid) '
                . 'INNER JOIN {user} '
                . 'ON {cohort_members}.userid={user}.id '
                . 'WHERE {user}.id = ?';
        if ($param == 0) {
            $sql .= ' and {cohort}.visible = 1';
        }
        return $DB->get_records_sql($sql,array($this->userid));
    }
    
    /**
    * Building string.
    * @param int $param
    * @param int $visible
    * @param string $name
    * @param string $desc
    * @return string
    */
    private function profile_field_cohort_show($param, $visible, $name, $desc) {
        if ($param == 1) {
            $str = '<p';
            if ($visible == 0){
                $str .= ' class="dimmed_text">';
            } else {
                $str .= '>';
            } 
            $str .= '<b>' . $desc . '</b>: ';
            $str .= $name;
            $str .= '</p>';
            return $str;
        } else {
            return NULL;
        }
    }
    
    /**
     * Add fields for editing a associated profile field.
     * @param moodleform $mform
     */
    public function edit_field_add($mform) {
    }

	public function get_field_properties() {
        return array(PARAM_RAW, NULL_NOT_ALLOWED);
    }
    
    /**
     * Accessor method: Load the field record and user data associated with the
     * object's fieldid and userid
     */
    public function __construct($fieldid = 0, $userid = 0, $fielddata = null) {
        parent::__construct($fieldid, $userid, $fielddata);
		global $DB;			   
               
        // Load the field object.
        if (($this->fieldid == 0) or (!($field = $DB->get_record('user_info_field',
                array('id' => $this->fieldid))))) {
            $this->field = null;
            $this->inputname = '';
        } else {
            $this->field = $field;
            $this->inputname = 'profile_field_'.$field->shortname;
        }
        if (isset($this->field->param4)) {
            $hiddencohort = $this->field->param4;
            $usercohort = self::profile_field_get_cohort($hiddencohort);
        } else {
            $usercohort = self::profile_field_get_cohort(false);
        }
        
        if (!empty($usercohort)) {
            $cohort = '';
            $cohortname = $this->field->param1;
            $idnumber = $this->field->param2;
            $description = $this->field->param3;
            foreach ($usercohort as $record) {
                $cohort .= self::profile_field_cohort_show($cohortname, $record->visible,
                        $record->name, get_string('name', 'cohort'));
                $cohort .= self::profile_field_cohort_show($idnumber, $record->visible,
                        $record->idnumber, get_string('idnumber', 'cohort'));
                $cohort .= self::profile_field_cohort_show($description, $record->visible,
                        $record->description, get_string('description',
                                'cohort'));
            }
            $this->data = $cohort;
            $this->dataformat = FORMAT_HTML;
        } else {
            $this->data = null;
        }
    }
}