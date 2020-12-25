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

$location = __DIR__;
$root = $location."/../..";
require_once("$root/config.php");
require_once("$CFG->libdir/formslib.php");
require_once("$location/Twig/Autoloader.php");
require_once("$location/libs/users.php");
require_once("$location/libs/groups.php");
require_once("$location/libs/claims.php");
require_once("$location/libs/files.php");
require_once("$location/libs/system.php");

function get_record($userid, $shortname) {
    global $DB;

    $sql = "SELECT *
              FROM {user_info_data} uda
              JOIN {user_info_field} uif ON uda.fieldid = uif.id
             WHERE uda.userid = :userid
                   AND uif.datatype  = :datatype
                   AND uif.shortname = :shortname";
    $params = [
        'userid'    => $userid,
        'shortname' => $shortname,
        'datatype'  => 'text'
    ];
    return $DB->get_record_sql($sql, $params);
}

function block_sibportfolio_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = array()) {
    global $USER;
    
    if ($context->contextlevel != CONTEXT_USER) {
        return false;
    }
    
    block_sibportfolio_require_login_system();
    
    $itemid = (int)array_shift($args);
    $userid = $context->instanceid;
    
    switch ($filearea) {
    
        case 'sibport_files':
            $storedfile = block_sibportfolio_files::is_file_in_portfolio($itemid);
            if (!$storedfile && !(is_siteadmin() || block_sibportfolio_is_curator($userid) || $USER->id == $userid)) {
                return false;
            }
            break;
            
        case 'sibport_assignsubmission':
        case 'sibport_assignfeedback':
        case 'sibport_assignfeedback_2':
            $viewer = has_capability('block/sibportfolio:viewer', context_system::instance());
            if (!($viewer || block_sibportfolio_is_curator($userid) || $USER->id == $userid)) {
                return false;
            }
            break;
            
        default:
            return false;
    
    }
    
    $filename = array_pop($args);
    $filepath = empty($args) ? '/' : '/'.implode('/', $args).'/';
    
    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'block_sibportfolio', $filearea, $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }
    
    send_stored_file($file, 86400, 0, $forcedownload, $options);
}

function block_sibportfolio_comment_validate($comment_param) {
    if ($comment_param->commentarea != 'file_comments') {
        throw new comment_exception('invalidcommentarea');
    }

    $file = block_sibportfolio_claims::get_claim_by_id($comment_param->itemid);

    if (!$file) {
        throw new comment_exception('invalidcommentitemid');
    }

    if (!block_sibportfolio_files::is_file_in_portfolio($comment_param->itemid)) {
        throw new comment_exception('nopermissiontocomment');
    }

    if ($comment_param->context != context_user::instance($file->userid)) {
        throw new comment_exception('invalidcontext');
    }

    return true;
}

function block_sibportfolio_comment_permissions($args) {
    global $CFG;

    if (!$CFG->usecomments) {
        throw new comment_exception('nopermissiontocomment');
    }

    if ($args->commentarea != 'file_comments') {
        throw new comment_exception('invalidcommentarea');
    }

    $file = block_sibportfolio_claims::get_claim_by_id($args->itemid);

    if (!$file) {
        throw new comment_exception('invalidcommentitemid');
    }

    if (!block_sibportfolio_files::is_file_in_portfolio($args->itemid)) {
        throw new comment_exception('nopermissiontocomment');
    }

    if ($args->context != context_user::instance($file->userid)) {
        throw new comment_exception('invalidcontext');
    }

    return array('post'=>true, 'view'=>true);
}

function block_sibportfolio_comment_display($comments, $args) {
    if ($args->commentarea != 'file_comments') {
        throw new comment_exception('invalidcommentarea');
    }

    $file = block_sibportfolio_claims::get_claim_by_id($args->itemid);

    if (!$file) {
        throw new comment_exception('invalidcommentitemid');
    }

    if (!block_sibportfolio_files::is_file_in_portfolio($args->itemid)) {
        throw new comment_exception('nopermissiontocomment');
    }

    if ($args->context != context_user::instance($file->userid)) {
        throw new comment_exception('invalidcontext');
    }

    return $comments;
}

function block_sibportfolio_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    if (isguestuser($user)) {
        return false;
    }
    $url = new moodle_url('/blocks/sibportfolio/index.php', array('userid' => $user->id));
    $title = $iscurrentuser ? get_string('key171', 'block_sibportfolio') : get_string('key42', 'block_sibportfolio');
    $node = new core_user\output\myprofile\node('miscellaneous', 'sibportfolio', $title, null, $url);
    $tree->add_node($node);
    return true;
}

class block_sibportfolio_render {

    private $twig;
    private $views = array();
    private $includes = array();
    private $styles = array();
    private $scripts = array();
    
    public function __construct($path_views) {
        global $PAGE, $CFG;
        $PAGE->navbar->ignore_active();
        Twig_Autoloader::register();
        $loader = new Twig_Loader_Filesystem($CFG->dirroot.'/blocks/sibportfolio/'.$path_views);
        $this->twig = new Twig_Environment($loader);
    }
    
    public function add_view($view, $model) {
        $this->views[$view] = $model;
    }
    
    public function add_views($views) {
        foreach ($views as $view => $model) {
            $this->views[$view] = $model;
        }
    }
    
    public function add_html($path) {
        $this->includes[] = $path;
    }
    
    public function add_style($path) {
        $this->styles[] = $path;
    }
    
    public function add_script($path) {
        $this->scripts[] = $path;
    }
    
    public function set_title($title) {
        global $PAGE;
        $PAGE->set_title($title);
    }
    
    public function add_navigation($title, $url) {
        global $PAGE;
        $PAGE->navbar->add($title, $url);
    }
    
    public function set_heading($header) {
        global $PAGE;
        $PAGE->set_heading($header);
    }
    
    public function display() {
        global $OUTPUT, $PAGE;
        
        foreach ($this->styles as $style) {
            $PAGE->requires->css('/blocks/sibportfolio/'.$style);
        }
        foreach ($this->scripts as $script) {
            $PAGE->requires->js('/blocks/sibportfolio/'.$script, true);
        }
        
        echo $OUTPUT->header();
        echo '<div id="sibportfolio" class="block_sibportfolio_wrapper">';
        foreach ($this->views as $view => $model) {
            $this->twig->loadTemplate($view)->display($model);
        }
        foreach ($this->includes as $path) {
            include_once($path);
        }
        echo '</div>';
        echo $OUTPUT->footer();
    }
    
    public function dislpay_template($view, $model) {			
        $this->twig->loadTemplate($view)->display($model);
    }
    
    public function html_template($view, $model) {			
        return $this->twig->render($view, $model);
    }

}
