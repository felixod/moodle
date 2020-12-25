<?php

$services = array(
    'lab_ebsservice' => array(
        'functions' => array('mod_lanebs_search_books', 'mod_lanebs_book_content', 'mod_lanebs_category_tree', 'mod_lanebs_auth'),
        'requiredcapability' => ['mod_lanebs:get_tree'],
        'restrictedusers' => 1,
        'enabled' => 1,
        'shortname' => 'LanEbsIntegration',
        'downloadfiles' => 0,
        'uploadfiles' => 0,
    ),
);

$functions = array(
    'mod_lanebs_search_books' => array(
        'classname' => 'mod_lanebs_external',
        'methodname' => 'search_books',
        'classpath' => 'mod/lanebs/externallib.php',
        'description' => 'Получение списка книг',
        'type' => 'read',
        'ajax' => true,
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'capabilities' => array('lanebs:get_tree')
    ),
    'mod_lanebs_book_content' => array(
        'classname' => 'mod_lanebs_external',
        'methodname' => 'book_content',
        'classpath' => 'mod/lanebs/externallib.php',
        'description' => 'Получение читалки с контентом',
        'type' => 'read',
        'ajax' => true,
        'service' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'capabilities' => array('lanebs:get_tree')
    ),
    'mod_lanebs_category_tree' => array(
        'classname' => 'mod_lanebs_external',
        'methodname' => 'category_tree',
        'classpath' => 'mod/lanebs/externallib.php',
        'description' => 'Получение дерева категорий',
        'type' => 'read',
        'ajax' => true,
        'service' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'capabilities' => array('lanebs:get_tree'),
    ),
    'mod_lanebs_auth' => array(
        'classname' => 'mod_lanebs_external',
        'methodname' => 'auth',
        'classpath' => 'mod/lanebs/externallib.php',
        'description' => 'Авторизация в ЭБС',
        'type'  => 'read',
        'ajax'  => true,
        'service' => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
        'capabilities' => array('lanebs:get_tree'),
    ),
);