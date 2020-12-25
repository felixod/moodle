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

class delCategoryForm extends moodleform
{
    public function definition() {
        global $OUTPUT;
        $mform = $this->_form;
        $categoryId = $this->_customdata['categoryid'];
        
        $mform->addElement('hidden', 'categoryid', null);
        $mform->setType('categoryid', PARAM_INT);	
            
        $mform->addElement('static', 'constraint', $OUTPUT->pix_icon('alert', get_string('key178', 'block_sibportfolio'), 
            'block_sibportfolio', array('height' => '19', 'width' => '19')), get_string('key179', 'block_sibportfolio'));
        
        $categories = array();
        $categories['none'] = get_string('key148', 'block_sibportfolio');
        $fileCategories = block_sibportfolio_files::get_file_categories();
        foreach ($fileCategories as $category) {
            if ($categoryId != $category->id) $categories[$category->id] = $category->name;
        }
        
        $select = $mform->addElement('select', 'newcategory', get_string('key181', 'block_sibportfolio'), $categories, null);
        $mform->addRule('newcategory', get_string('key149', 'block_sibportfolio'), 'required', null, 'server');
        $mform->addRule('newcategory', get_string('key149', 'block_sibportfolio'), 'numeric');
        $select->setMultiple(false);
        
        $mform->addHelpButton('newcategory', 'key181', 'block_sibportfolio');
        
        $this->add_action_buttons($cancel = true, $submitlabel = get_string('key176', 'block_sibportfolio'));
    }
    
    function validation($data, $files) {		
        $errors = array();
        
        $newcat = isset($data['newcategory']) ? $data['newcategory'] : null;
        $oldcat = isset($data['categoryid']) ? $data['categoryid'] : null;
        
        if (!is_numeric($newcat) || !is_numeric($oldcat) || $newcat == $oldcat || !block_sibportfolio_files::get_file_category_by_id($newcat)) {
            $errors['newcategory'] = get_string('key149', 'block_sibportfolio');
        }
        
        return $errors;
    }
}

class findUserForm extends moodleform
{
    public function definition() {
        global $USER, $CFG;
        $mform = $this->_form;
        
        $maxLength = 50;
        
        $mform->addElement('text', 'findparam', get_string('key170', 'block_sibportfolio'), array('size' => '50'));
        $mform->setType('findparam', PARAM_NOTAGS);		
        $mform->addRule('findparam', get_string('key127', 'block_sibportfolio'), 'required', null, 'server');	
        $mform->addRule('findparam', get_string('key202', 'block_sibportfolio', $maxLength), 'maxlength', $maxLength, 'server');
        
        $mform->addHelpButton('findparam', 'key170', 'block_sibportfolio');
        
        $mform->addElement('hidden', 'returnurl', null);
        $mform->setType('returnurl', PARAM_URL);
        
        if ($this->_customdata['enfilter']) {
            $mform->addElement('advcheckbox', 'myusers', get_string('key129', 'block_sibportfolio'), get_string('key130', 'block_sibportfolio'));
        }
        
        $findParam = data_submitted() ? $this->_customdata['findparam'] : '';
        if (is_string($findParam) && $findParam != '' && mb_strlen($findParam, 'UTF-8') <= $maxLength && data_submitted()) {
            $option = $this->_customdata['enfilter'] && $this->_customdata['myusers'] ? $USER->id : null;
            $users = block_sibportfolio_users::get_users_by_name($findParam, false, $option);
            
            $result = array();
            if ($users === 0) { // превышен лимит
                $row = new stdClass();
                $row->name = get_string('key132', 'block_sibportfolio');
                $result[] = $row;
            } else if (count($users) == 0) { // не найдено
                $row = new stdClass();
                $row->name = get_string('key131', 'block_sibportfolio');
                $result[] = $row;
            } else {
                foreach($users as $user) { // найдено
                    $row = new stdClass();
                    $row->id = $user->id;
                    $row->name = fullname($user);
                    $row->group = $user->group_name;
                    $result[] = $row;
                }
            }

            $render = new block_sibportfolio_render('views');
            $mform->addElement('html', $render->html_template('finduser.html', array(
                'wwwroot' => $CFG->wwwroot,
                'users' => $result,
                'user_label' => get_string('key126', 'block_sibportfolio'),
                'group_label' => get_string('key33', 'block_sibportfolio'),
                'profile_label' => get_string('key42', 'block_sibportfolio'),
                'profile_more_label' => get_string('key133', 'block_sibportfolio')
            )));
        }

        $this->add_action_buttons($cancel = true, $submitlabel = get_string('key19', 'block_sibportfolio'));
    }
    
    function validation($data, $files) {
        $errors = array();
        
        if (!isset($data['findparam']) || !is_string($data['findparam']) || trim($data['findparam']) == false) {
            $errors['findparam'] = get_string('key127', 'block_sibportfolio');
        }
        
        return $errors;
    }
}

class addCuratorForm extends moodleform
{
    public function definition() {	
        $mform = $this->_form;
        
        $maxLength = 50;
        
        $mform->addElement('hidden', 'groupid', null);
        $mform->setType('groupid', PARAM_INT);	
        
        $mform->addElement('hidden', 'returnurl', null);
        $mform->setType('returnurl', PARAM_URL);
                    
        $mform->addElement('text', 'user', get_string('key165', 'block_sibportfolio'), array('size' => '50'));	
        $mform->addRule('user', get_string('key202', 'block_sibportfolio', $maxLength), 'maxlength', $maxLength, 'server');
        $mform->setType('user', PARAM_NOTAGS);
        
        $mform->addHelpButton('user', 'key170', 'block_sibportfolio');
        
        $curators = array();
        $curators['none'] = get_string('key93', 'block_sibportfolio');
        $curator = data_submitted() ? $this->_customdata['user'] : '';
        if (is_string($curator) && $curator != '' && mb_strlen($curator, 'UTF-8') <= $maxLength) {				
            $foundedusers = block_sibportfolio_users::get_users_by_name($curator, true);
            
            if ($foundedusers === 0) { // превышен лимит
                $curators['none'] = get_string('key95', 'block_sibportfolio');
            } else if (count($foundedusers) == 0) { // не найдено
                $curators['none'] = get_string('key94', 'block_sibportfolio');
            } else { // найдено
                foreach ($foundedusers as $foundeduser)	{
                    $curators[$foundeduser->id] = fullname($foundeduser) . '&nbsp;(' . $foundeduser->email . ')';
                }
            }
        }
        
        $select = $mform->addElement('select', 'curator', get_string('key141', 'block_sibportfolio'), $curators, null);
        $select->setMultiple(false);			
        
        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'findbutton', get_string('key142', 'block_sibportfolio'));
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('key138', 'block_sibportfolio'));
        $buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');
    }
    
    function validation($data, $files) {
        $errors = array();
        
        $userInfo = isset($data['curator']) && is_numeric($data['curator']) ? block_sibportfolio_users::get_user_by_id($data['curator']) : null;
        if (isset($data['findbutton']) && (!isset($data['user']) || !is_string($data['user']) || trim($data['user']) == false)) {
            $errors['user'] = get_string('key143', 'block_sibportfolio');
        } else if (isset($data['submitbutton']) && (!$userInfo || $userInfo->id == 1 || $userInfo->deleted == 1 || $userInfo->suspended == 1)) {
            $errors['curator'] = get_string('key144', 'block_sibportfolio');
        }
        
        return $errors;
    }
}

class delCuratorForm extends moodleform
{	
    public function definition() {	
        $mform = $this->_form;
        $formData = $this->_customdata['formdata'];
    
        $mform->addElement('hidden', 'groupid', null);
        $mform->setType('groupid', PARAM_INT);	
    
        $mform->addElement('hidden', 'returnurl', null);
        $mform->setType('returnurl', PARAM_URL);

        $data = new stdClass();
        $data->group = $formData['groupName'];
        $data->moderator = $formData['userName'];
        $mform->addElement('static', 'question', '', get_string('key112', 'block_sibportfolio', $data));
        
        $this->add_action_buttons($cancel = true, $submitlabel = get_string('key139', 'block_sibportfolio'));
    }

    function validation($data, $files) {
        return array();
    }
}

class editGroupForm extends moodleform
{	
    public function definition() {	
        $mform = $this->_form;
    
        $mform->addElement('hidden', 'groupid', null);
        $mform->setType('groupid', PARAM_INT);
    
        $mform->addElement('hidden', 'returnurl', null);
        $mform->setType('returnurl', PARAM_URL);
    
        $mform->addElement('text', 'speciality', get_string('key198', 'block_sibportfolio'), array('size'=>'50'));	
        $mform->addRule('speciality', get_string('key202', 'block_sibportfolio', 250), 'maxlength', 250, 'server');
        $mform->setType('speciality', PARAM_NOTAGS);
        
        $mform->addElement('text', 'study', get_string('key199', 'block_sibportfolio'), array('size'=>'50'));	
        $mform->addRule('study', get_string('key202', 'block_sibportfolio', 250), 'maxlength', 250, 'server');
        $mform->setType('study', PARAM_NOTAGS);
        
        $this->add_action_buttons($cancel = true, $submitlabel = get_string('key159', 'block_sibportfolio'));
    }

    function validation($data, $files) {
        return array();
    }
}

class addFileForm extends moodleform
{
    public function definition() {
        global $OUTPUT;
        $mform = $this->_form;
        
        $fileSize = block_sibportfolio_files::get_max_filesize();
        $mform->setMaxFileSize($fileSize);
        $mform->setType('MAX_FILE_SIZE', PARAM_INT);
        
        $mform->addElement('hidden', 'userid', null);
        $mform->setType('userid', PARAM_INT);	
        
        $mform->addElement('hidden', 'returnurl', null);
        $mform->setType('returnurl', PARAM_URL);
        
        $mform->addElement('textarea', 'descr', get_string('key3', 'block_sibportfolio'), 'wrap="virtual" rows="7" cols="60"');
        $mform->setType('descr', PARAM_NOTAGS);
        $mform->addRule('descr', get_string('key146', 'block_sibportfolio'), 'required', null, 'server');
        $mform->addRule('descr', get_string('key202', 'block_sibportfolio', 1000), 'maxlength', 1000, 'server');
        
        $mform->addHelpButton('descr', 'key3', 'block_sibportfolio');
        
        $categories = array();
        $categories['none'] = get_string('key148', 'block_sibportfolio');
        $fileCategories = block_sibportfolio_files::get_file_categories();
        foreach ($fileCategories as $category) {
            $categories[$category->id] = $category->name;
        }

        $select = $mform->addElement('select', 'categoryid', get_string('key4', 'block_sibportfolio'), $categories, null);
        $mform->addRule('categoryid', get_string('key149', 'block_sibportfolio'), 'required', null, 'server');
        $mform->addRule('categoryid', get_string('key149', 'block_sibportfolio'), 'numeric');
        $select->setMultiple(false);

        $mform->addElement('static', 'constraint', $OUTPUT->pix_icon('alert', get_string('key178', 'block_sibportfolio'), 'block_sibportfolio', 
            array('height' => '19', 'width' => '19')), get_string('key197', 'block_sibportfolio', round($fileSize / 1048576, 2)));
        
        $mform->addElement('file', 'attachment', get_string('file'));
        $mform->addRule('attachment', get_string('key150', 'block_sibportfolio'), 'required', null, 'server');
        
        $this->add_action_buttons($cancel = true, $submitlabel = get_string('key151', 'block_sibportfolio'));
    }
    
    function validation($data, $files) {
        $errors = array();
        
        $descr = isset($data['descr']) ? $data['descr'] : null;
        $category = isset($data['categoryid']) ? $data['categoryid'] : null;
        $filepath = isset($files['attachment']) ? $files['attachment'] : null;
        
        if (!is_string($descr) || trim($descr) == false) {
            $errors['descr'] = get_string('key146', 'block_sibportfolio');
        }
        if (!is_numeric($category) || !block_sibportfolio_files::get_file_category_by_id($category)) {
            $errors['categoryid'] = get_string('key149', 'block_sibportfolio');
        }
        if ($filepath) {
            $filedata = pathinfo($_FILES['attachment']['name']);
            $filesize = filesize($filepath);
            $cfg_filesize = block_sibportfolio_files::get_max_filesize();
            $cfg_lockedtypes = block_sibportfolio_files::get_locked_types();
            if ($filesize == 0 || $filesize > $cfg_filesize) {
                $errors['attachment'] = get_string('key106', 'block_sibportfolio', round($cfg_filesize / 1048576, 2));
                unlink($filepath);
            } else if (!$this->_customdata['accepted'] && in_array(mb_strtolower($filedata['extension'], 'UTF-8'), $cfg_lockedtypes)) {
                $errors['attachment'] = get_string('key188', 'block_sibportfolio');
                unlink($filepath);
            }
        }
        
        return $errors;
    }
}

class delFileForm extends moodleform 
{
    public function definition() {
        $mform = $this->_form;
        
        $mform->addElement('hidden', 'claimid', null);	
        $mform->setType('claimid', PARAM_INT);
        
        $mform->addElement('hidden', 'returnurl', null);
        $mform->setType('returnurl', PARAM_URL);
        
        $mform->addElement('textarea', 'cause', get_string('key153', 'block_sibportfolio'), 'wrap="virtual" rows="5" cols="60"');
        $mform->setType('cause', PARAM_NOTAGS);
        $mform->addRule('cause', get_string('key202', 'block_sibportfolio', 500), 'maxlength', 500, 'server');
        $mform->addHelpButton('cause', 'key74', 'block_sibportfolio');
        
        if (!$this->_customdata['accepted']) {
            $mform->addRule('cause', get_string('key154', 'block_sibportfolio'), 'required', null, 'server');
        }		

        $this->add_action_buttons($cancel = true, $submitlabel = get_string('key128', 'block_sibportfolio'));
    }
    
    function validation($data, $files) {
        $errors = array();
        
        $cause = isset($data['cause']) ? $data['cause'] : null;
        
        if (!$this->_customdata['accepted']) {
            if (!is_string($cause) || trim($cause) == false) {
                $errors['cause'] = get_string('key154', 'block_sibportfolio');
            }
        } else {
            if ($cause && !is_string($cause)) {
                $errors['cause'] = get_string('key154', 'block_sibportfolio');
            }
        }
        
        return $errors;
    }
}

class editFileForm extends moodleform 
{
    public function definition() {
        $mform = $this->_form;
        
        $mform->addElement('hidden', 'claimid', null);	
        $mform->setType('claimid', PARAM_INT);
        
        $mform->addElement('hidden', 'returnurl', null);
        $mform->setType('returnurl', PARAM_URL);			

        $mform->addElement('textarea', 'cause', get_string('key157', 'block_sibportfolio'), 'wrap="virtual" rows="5" cols="60"');
        $mform->setType('cause', PARAM_NOTAGS);
        $mform->addRule('cause', get_string('key202', 'block_sibportfolio', 500), 'maxlength', 500, 'server');
        $mform->addHelpButton('cause', 'key74', 'block_sibportfolio');	
        
        if (!($this->_customdata['accepted'])) {			
            $mform->addRule('cause', get_string('key158', 'block_sibportfolio'), 'required', null, 'server');
        }
        
        $mform->addElement('textarea', 'descr', get_string('key3', 'block_sibportfolio'), 'wrap="virtual" rows="7" cols="60"');
        $mform->setType('descr', PARAM_NOTAGS);
        $mform->addRule('descr', get_string('key146', 'block_sibportfolio'), 'required', null, 'server');
        $mform->addRule('descr', get_string('key202', 'block_sibportfolio', 1000), 'maxlength', 1000, 'server');
        
        $mform->addHelpButton('descr', 'key3', 'block_sibportfolio');
        
        $categories = array();
        $categories['none'] = get_string('key148', 'block_sibportfolio');
        $fileCategories = block_sibportfolio_files::get_file_categories();
        foreach ($fileCategories as $category) {
            $categories[$category->id] = $category->name;
        }

        $select = $mform->addElement('select', 'category', get_string('key4', 'block_sibportfolio'), $categories, null);
        $mform->addRule('category', get_string('key149', 'block_sibportfolio'), 'required', null, 'server');
        $mform->addRule('category', get_string('key149', 'block_sibportfolio'), 'numeric');
        $select->setMultiple(false);

        $file = block_sibportfolio_files::get_file_by_claim_id($this->_customdata['claimid']);
        $fileLink = $file ? block_sibportfolio_files::get_sibport_file_link($file) : '<i>'.get_string('key172', 'block_sibportfolio').'</i>';
        $mform->addElement('static', 'filelink', get_string('key5', 'block_sibportfolio'), $fileLink);

        $this->add_action_buttons($cancel = true, $submitlabel = get_string('key159', 'block_sibportfolio'));			
    }
    
    function validation($data, $files) {
        $errors = array();
        
        $descr = isset($data['descr']) ? $data['descr'] : null;
        $category = isset($data['category']) ? $data['category'] : null;
        $cause = isset($data['cause']) ? $data['cause'] : null;
        
        if (!is_string($descr) || trim($descr) == false) {
            $errors['descr'] = get_string('key146', 'block_sibportfolio');
        }
        if (!is_numeric($category) || !block_sibportfolio_files::get_file_category_by_id($category)) {
            $errors['category'] = get_string('key149', 'block_sibportfolio');
        }
        if (!$this->_customdata['accepted']) {
            if (!is_string($cause) || trim($cause) == false) {
                $errors['cause'] = get_string('key158', 'block_sibportfolio');
            }
        } else {
            if ($cause && !is_string($cause)) {
                $errors['cause'] = get_string('key158', 'block_sibportfolio');
            }
        }
        
        return $errors;
    }
}

class acceptClaimForm extends moodleform 
{	
    public function definition() {
        $mform = $this->_form;
        $claim = $this->_customdata['claim'];
        
        $mform->addElement('hidden', 'claimid', null);
        $mform->setType('claimid', PARAM_INT);			
            
        $mform->addElement('hidden', 'returnurl', null);
        $mform->setType('returnurl', PARAM_URL);
        
        if ($claim->claimtype != 2) {
            
            $mform->addElement('textarea', 'descr', get_string('key3', 'block_sibportfolio'), 'wrap="virtual" rows="7" cols="60"');
            $mform->setType('descr', PARAM_NOTAGS);
            $mform->addRule('descr', get_string('key146', 'block_sibportfolio'), 'required', null, 'server');
            $mform->addRule('descr', get_string('key202', 'block_sibportfolio', 1000), 'maxlength', 1000, 'server');
            
            $mform->addHelpButton('descr', 'key3', 'block_sibportfolio');
            
            $categories = array();
            $categories['none'] = get_string('key148', 'block_sibportfolio');
            $fileCategories = block_sibportfolio_files::get_file_categories();
            foreach ($fileCategories as $category) {
                $categories[$category->id] = $category->name;
            }

            $select = $mform->addElement('select', 'category', get_string('key4', 'block_sibportfolio'), $categories, null);
            $mform->addRule('category', get_string('key149', 'block_sibportfolio'), 'required', null, 'server');
            $mform->addRule('category', get_string('key149', 'block_sibportfolio'), 'numeric');
            $select->setMultiple(false);
            
            $file = block_sibportfolio_files::get_file_by_claim_id($claim->id);
            $fileLink = $file ? block_sibportfolio_files::get_sibport_file_link($file) : '<i>'.get_string('key172', 'block_sibportfolio').'</i>';
            $mform->addElement('static', 'filelink', get_string('key161', 'block_sibportfolio'), $fileLink);
            
        } else {
        
            $mform->addElement('static', 'question', '', get_string('key162', 'block_sibportfolio'));
            
        }
        
        $this->add_action_buttons($cancel = true, $submitlabel = get_string('key26', 'block_sibportfolio'));
    }
    
    function validation($data, $files) {
        $errors = array();
        
        $nodel = $this->_customdata['claim']->claimtype != 2;
        
        if ($nodel) {
            $descr = isset($data['descr']) ? $data['descr'] : null;
            $category = isset($data['category']) ? $data['category'] : null;
            
            if (!is_string($descr) || trim($descr) == false) {
                $errors['descr'] = get_string('key146', 'block_sibportfolio');
            }
            if (!is_numeric($category) || !block_sibportfolio_files::get_file_category_by_id($category)) {
                $errors['category'] = get_string('key149', 'block_sibportfolio');
            }
        }
        
        return $errors;
    }		
}

class rejectClaimForm extends moodleform 
{
    public function definition() {
        $mform = $this->_form;
        
        $mform->addElement('hidden', 'claimid', null);	
        $mform->setType('claimid', PARAM_INT);	
        
        $mform->addElement('hidden', 'returnurl', null);
        $mform->setType('returnurl', PARAM_URL);

        $mform->addElement('textarea', 'cause', get_string('key163', 'block_sibportfolio'), 'wrap="virtual" rows="5" cols="60"');
        $mform->setType('cause', PARAM_NOTAGS);
        $mform->addRule('cause', get_string('key164', 'block_sibportfolio'), 'required', null, 'server');
        $mform->addRule('cause', get_string('key202', 'block_sibportfolio', 500), 'maxlength', 500, 'server');
        
        $mform->addHelpButton('cause', 'key163', 'block_sibportfolio');

        $this->add_action_buttons($cancel = true, $submitlabel = get_string('key27', 'block_sibportfolio'));
    }
    
    function validation($data, $files) {
        $errors = array();

        if (isset($data['cause']) && (!is_string($data['cause']) || trim($data['cause']) == false)) {
            $errors['cause'] = get_string('key164', 'block_sibportfolio');
        }
        
        return $errors;
    }
}

function block_sibportfolio_form_render($form, $html = null)
{
    global $OUTPUT;

    echo $OUTPUT->header();
    echo '<div id="sibportfolio" class="block_sibportfolio_wrapper">';
    
    if ($html != null) echo $html;
    
    $render = new block_sibportfolio_render('views');
    echo $render->html_template('navigator.html', block_sibportfolio_get_navigator_model());
    
    echo '<div class="block_sibportfolio_content"><br />';
    $form->display();
    echo '</div>';
    
    echo '</div>';
    echo $OUTPUT->footer();
}
