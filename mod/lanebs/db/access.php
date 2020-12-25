<?php

/**
 *
 * @package    mod_lanebs
 * @copyright  2020 Yurii Senin (katorsi@mail.ru)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    'mod/lanebs:get_tree' => array(
        'riskbitmask'  => RISK_SPAM | RISK_PERSONAL,
        'captype'      => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes'   => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
    )),
    'mod/lanebs:addinstance' => array(
            'riskbitmask' => RISK_SPAM | RISK_XSS,
            'captype' => 'write',
            'contextlevel' => CONTEXT_COURSE,
            'archetypes' => array(
                'editingteacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW
            ),
    'clonepermissionsfrom' => 'moodle/course:manageactivities'
    )
);