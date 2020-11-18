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
header('Access-Control-Allow-Origin: *');
/**
 * Webservices for lanebs.
 *
 * @package    mod_lanebs
 * @copyright  2020 Yurii Senin (katorsi@mail.ru)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . "/webservice/lib.php");
require_once($CFG->libdir . "/filelib.php");

class mod_lanebs_external extends external_api
{

    private static $subscribeToken = false;
    private static $readerToken = false;
    private static $baseUrl = 'https://moodle-api.e.lanbook.com';

    /**
     * Return category_tree webservice parameters.
     *
     * @return \external_function_parameters
     */
    public static function search_books_parameters()
    {
        return new external_function_parameters(
            array(
                'searchParam' => new external_single_structure(
                    array('searchString'  => new external_value(PARAM_TEXT, 'Поисковая строка'),)
                ),
                'page'        => new external_value(PARAM_INT, 'смещение выдачи'),
                'limit'       => new external_value(PARAM_INT, 'количетво элементов на страницу (10 по дефолту)'),
                'catId'       => new external_value(PARAM_RAW, 'ИД категории поиска'),
            )
        );
    }

    /**
     * @param $searchParam
     * @param $page
     * @param $limit
     * @param $catId
     * @return array
     * @throws coding_exception
     */
    public static function search_books($searchParam, $page, $limit, $catId)
    {
        global $DB, $USER;
        if (isset($_SESSION['readerToken']) && !empty($_SESSION['readerToken'])) {
            self::$readerToken = $_SESSION['readerToken'];
        }
        if (isset($_SESSION['subscriberToken']) && !empty($_SESSION['subscriberToken'])) {
            self::$subscribeToken = $_SESSION['subscriberToken'];
        }
        $params = array('page' => $page, 'limit' => $limit);
        $url = self::$baseUrl.'/api/search/book';
        if (isset($searchParam['searchString']) && !empty($searchParam['searchString'])) {
            $params['query_id_isbn_title'] = $searchParam['searchString'];
        }
        else {
            $url = self::$baseUrl.'/api/categories/books';
        }
        if (isset($catId) && !empty($catId)) {
            $params['category_id'] = (int)$catId;
        }
        $curl = new curl();
        $options = array(
            'CURLOPT_SSL_VERIFYPEER' => true,
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_VERBOSE' => true);
        $curl->setopt($options);
        $curl->setopt([ 'CURLOPT_HTTPHEADER' =>
                ['x-auth-token-subscriber: '.self::$subscribeToken,
                    'x-auth-token-reader: '.self::$readerToken]
            ]
        );
        /*if (isset($catId)) {
            $data = $curl->get(self::$baseUrl . '/api/categories/books', array('category_id' => $catId, 'page' => $page, 'limit' => $limit), $options);
        }
        else {*/
        $data = $curl->get($url, $params, $options);
        /*}*/
        return array(
            'body' => $data
        );
    }

    /**
     *
     * @return \external_single_structure
     */
    public static function search_books_returns() {
        return new external_single_structure(
            array(
                'body' => new external_value(PARAM_RAW, 'Поисковая строка'),
            )
        );
    }

    public static function book_content($id)
    {
        global $DB, $USER;
        //$params = self::validate_parameters(self::category_tree_parameters(), $searchParam);
        if (isset($_SESSION['readerToken']) && !empty($_SESSION['readerToken'])) {
            self::$readerToken = $_SESSION['readerToken'];
        }
        if (isset($_SESSION['subscriberToken']) && !empty($_SESSION['subscriberToken'])) {
            self::$subscribeToken = $_SESSION['subscriberToken'];
        }
        $curl = new curl();
        $options = array(
            //'CURLOPT_HEADER'            => true,
            'CURLOPT_POST'              => false,
            'CURLOPT_SSL_VERIFYPEER'    => true,
            'CURLOPT_RETURNTRANSFER'    => true,
            'CURLOPT_USERAGENT'         => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36');
        $curl->setopt($options);
        $curl->setopt([ 'CURLOPT_HTTPHEADER' =>
                ['x-auth-token-subscriber: '.self::$subscribeToken,
                'x-auth-token-reader: '.self::$readerToken]
            ]
        );
        $id = $id[0];
        $data = $curl->get(self::$baseUrl . '/api/reader/book/'.$id, null, $options);
        $result = self::regexReplace($data);
        return array(
            'body' => $result
        );
    }

    public static function book_content_parameters()
    {
        return new external_function_parameters(
            array(
                'id' => new external_single_structure(array(new external_value(PARAM_TEXT, 'ИД книги'))
                )
            )
        );
    }

    public static function book_content_returns()
    {
        return new external_single_structure(
            array(
                'body' => new external_value(PARAM_RAW, 'сырой HTML читалки'),
            )
        );
    }

    public static function category_tree_parameters()
    {
        return new external_function_parameters(
            array(
                'categoryId' => new external_single_structure(array(new external_value(PARAM_TEXT, 'ИД книги'))
                )
            )
        );
    }

    public static function category_tree($categoryId)
    {
        global $DB, $USER;
        //$params = self::validate_parameters(self::category_tree_parameters(), $searchParam);
        if (isset($_SESSION['readerToken']) && !empty($_SESSION['readerToken'])) {
            self::$readerToken = $_SESSION['readerToken'];
        }
        if (isset($_SESSION['subscriberToken']) && !empty($_SESSION['subscriberToken'])) {
            self::$subscribeToken = $_SESSION['subscriberToken'];
        }
        $curl = new curl();
        $options = array(
            'CURLOPT_POST'              => false,
            'CURLOPT_SSL_VERIFYPEER'    => true,
            'CURLOPT_RETURNTRANSFER'    => true,
            'CURLOPT_USERAGENT'         => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36');
        $curl->setopt($options);
        $curl->setopt([ 'CURLOPT_HTTPHEADER' =>
                ['x-auth-token-subscriber: '.self::$subscribeToken,
                    'x-auth-token-reader: '.self::$readerToken]
            ]
        );
        $categoryId = $categoryId[0];
        if (empty($categoryId) || !isset($categoryId) || ($categoryId === 'null')) {
            $url = self::$baseUrl . '/api/categories';
        }
        else {
            $url = self::$baseUrl . '/api/category/'.$categoryId;
        }
        $data = $curl->get($url, null, $options);
        return array(
            'body' => $data
        );
    }

    public static function category_tree_returns()
    {
        return new external_single_structure(
            array(
                'body' => new external_value(PARAM_RAW, 'верхние категории'),
            )
        );
    }

    public static function auth_parameters()
    {
        return new external_function_parameters([]);
    }

    public static function auth()
    {
        if (isset($_SESSION['subscriberToken']) && !empty($_SESSION['subscriberToken'])) {
            self::$subscribeToken = $_SESSION['subscriberToken'];
        }
        $curl = new curl();
        $options = array(
            'CURLOPT_POST'              => false,
            'CURLOPT_SSL_VERIFYPEER'    => true,
            'CURLOPT_RETURNTRANSFER'    => true,
            'CURLOPT_USERAGENT'         => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36');
        $curl->setopt($options);
        $curl->setopt([ 'CURLOPT_HTTPHEADER' => ['x-auth-token-subscriber: '.self::$subscribeToken]]);
        $data = $curl->get(self::$baseUrl . '/api/auth', null, $options);
        $token = (json_decode($data))->body->token;
        if ($token) {
            $_SESSION['readerToken'] = $token;
        }
        else {
            $_SESSION['readerToken'] = false;
        }
        return array('body' => $data);
    }

    public static function auth_returns()
    {
        return new external_single_structure(
            array(
                'body' => new external_value(PARAM_RAW, 'Результат получения токена читателя')
            )
        );
    }

    public static function regexReplace($data)
    {
        //$pattern = '/(href=(".*"))|(src=(".*"))|(src:url\((.*)\))/';
        $patternHref = '/href=((\'|")(\/))/';
        $replaceHref = 'href="'.self::$baseUrl.'/';
        $patternSrc = '/src=((\'|")(\/))/';
        $replaceSrc = 'src="'.self::$baseUrl.'/';
        $data = preg_replace($patternHref, $replaceHref, $data);
        $data = preg_replace($patternSrc, $replaceSrc, $data);
        return $data;
    }
}
