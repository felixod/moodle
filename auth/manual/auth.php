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
 * Authentication Plugin: Manual Authentication
 * Just does a simple check against the moodle database.
 *
 * @package    auth_manual
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/authlib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/gdlib.php');
require_once($CFG->dirroot.'/cohort/locallib.php');

/**
 * Manual authentication plugin.
 *
 * @package    auth
 * @subpackage manual
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class auth_plugin_manual extends auth_plugin_base {

    /**
     * The name of the component. Used by the configuration.
     */
    const COMPONENT_NAME = 'auth_manual';
    const LEGACY_COMPONENT_NAME = 'auth/manual';

    // Felixod 
    const SOAP_LOGIN    = 'web'; //логин пользователя к базе 1С
    const SOAP_PASSWORD = '5bi$dlyF'; //пароль пользователя к базе 1С
    const SOAP_CLIENT   = 'https://1c.samgups.ru/univer/ws/dekanat?wsdl';
    const SOAP_MOUNTPATH = '/mnt/univer/';
            
    /**
     * Constructor.
     */
    public function __construct() {
        $this->authtype = 'manual';
        $config = get_config(self::COMPONENT_NAME);
        $legacyconfig = get_config(self::LEGACY_COMPONENT_NAME);
        $this->config = (object)array_merge((array)$legacyconfig, (array)$config);
    }

    /**
     * Old syntax of class constructor. Deprecated in PHP7.
     *
     * @deprecated since Moodle 3.1
     */
    public function auth_plugin_manual() {
        debugging('Use of class name as constructor is deprecated', DEBUG_DEVELOPER);
        self::__construct();
    }

    /**
     * Returns true if the username and password work and false if they are
     * wrong or don't exist. (Non-mnet accounts only!)
     *
     * @param string $username The username
     * @param string $password The password
     * @return bool Authentication success or failure.
     */
    function user_login($username, $password) {
        global $CFG, $DB, $USER;
        // Проверем, если ли пользователя в базе данных 1С: Университет
        if (self::get_password_verify($username, $password)){
            // Нашли пользователя в базе данных 1С: Университет
            // Ищем пользователя в базе данных Moodle
            if (!$user = $DB->get_record('user', array('username'=>$username, 'mnethostid'=>$CFG->mnet_localhost_id))) {
                // Получаем код пользователя по логину и паролю из 1С: Университет
                $id1c = self::get_idnumber_from_login($username, $password);
                //error_log('Felixod idnumber: ' . $id1c);
                if (!empty($id1c)) {
                    $user = new stdClass();
                    // Создаем учетную запись с минимальным объемом параметров
                    $user = create_user_record($username, $password, $this->authtype);
                    if ($user) {
                        $updateuser = new stdClass();
                        $updateuser->id         = $user->id;
                        //Получаем адрес электронной почты из базы
                        $mail = $this->get_mail_from_login($username, $password);
                        if (!empty($mail)) {
                            $updateuser->email  = $mail;
                        }
                        else {
                            $updateuser->email  = strtolower($username.'@stud.samgups.ru');
                        }
                        $updateuser->idnumber   = $id1c;
                        $updateuser->confirmed  = 1;
                        $updateuser->country    = 'RU';
                        $updateuser->lang = $CFG->lang;
                        // Обновляем данные пользователя информацией из 1С
                        user_update_user($updateuser, false);
                        // Получаем информацию из 1С по пользователю
                        $this->get_1c_personalinfo($updateuser->idnumber, $username);
                        $this->get_1c_cohort($updateuser->idnumber, $username);
                        // Запрашиваем смену пароля при следующем входе
                        // Для ПРЕДУНИВЕРСАРИУМА ОТКЛЮЧАЕМ ОБНОВЛЕНИЕ ПАРОЛЯ (Можно входить из ЭИОС СамГУПС)
                        if ($this->is_internal()) {
                        //    set_user_preference('auth_forcepasswordchange', 1, $user->id);
                        }
                    }
                    return true;
                }
            }
        }
        if (!$user = $DB->get_record('user', array('username'=>$username, 'mnethostid'=>$CFG->mnet_localhost_id))) {
            return false;
        }

        if (!validate_internal_user_password($user, $password)) {
            // Логин и пароль не подошли, уходим!
            return false;
        }
        if ($password === 'changeme') {
            // force the change - this is deprecated and it makes sense only for manual auth,
            // because most other plugins can not change password easily or
            // passwords are always specified by users
            set_user_preference('auth_forcepasswordchange', true, $user->id);
        }
                
        $user = $DB->get_record('user', array('username'=>$username, 'mnethostid'=>$CFG->mnet_localhost_id));
        $id1c = $user->idnumber;
        if (!empty($id1c)) {
            // Обновляем информацию каждую минуту
            if ($this->get_lastlogin_day($user)) {
                $this->get_1c_personalinfo($id1c, $username);
                $this->get_1c_cohort($id1c, $username);
                // Создаем учетную запись в домене stud.samgups.ru
                if ($this->get_create_students_in_domain($id1c, ltrim($id1c,'0'), $password, 1)) {
                    // Если учетная запись была создана успешно в домене,
                    // обновляем адрес почты на новый (login@stud.samgups.ru)
                    if ($user->email !== strtolower(ltrim($id1c,'0').'@stud.samgups.ru')) {
                        $updateuser = new stdClass();
                        $updateuser->id    = $user->id;
                        $updateuser->email = strtolower(ltrim($id1c,'0').'@stud.samgups.ru');
                        user_update_user($updateuser, false);
			// TODO: Придумать как менять пароль у ППС
                    }
                }
            }
        }
        return true;
    }

    /**
    * Функция устанавливает SOAP соединение с 1С: Университет
    * 
    * @return array    Возвращает массив с объектом веб-сервиса.
    */

    function soap_1c_connector () {
        // Необходимо отключить кэширование для SOAP. Если этого не сделать, 
        // функции веб-сервисов будут работать некорректно.    
        ini_set('soap.wsdl_cache_enabled', 0 );
        ini_set('soap.wsdl_cache_ttl', 0); 
        try {
            $client = new SoapClient(self::SOAP_CLIENT,
            array(
                'login' => self::SOAP_LOGIN, //логин пользователя к базе 1С
                'password' => self::SOAP_PASSWORD, //пароль пользователя к базе 1С
                'soap_version' => SOAP_1_2, //версия SOAP
                'cache_wsdl' => WSDL_CACHE_NONE,
                'trace' => true,
                'features' => SOAP_USE_XSI_ARRAY_TYPE
            )
        );
        return $client;
        } catch (Exception $e) {
            debugging('Выброшено исключение: ',  $e->getMessage(), "\n");
            return null;
        }
    }

    /**
    * Функция проверяет что пользователь заходит меньше 1 минуты назад в ЭИОС
    *
    * @param  array   $user массив с параметрами пользователя
    * @return bool    Возвращает метку на успех операции обновления.
    */
    function get_lastlogin_day ($user) {
        $currentlogin = $user->currentlogin; // Текущий вход
        $lastlogin = $user->lastlogin; // Последний вход
        $lastlogin1m = strtotime("+1 mins", $lastlogin); // Время просрочки информации +1 минута
        // Если время последнего входа больше на два часа, чем текущее возвращаем истину
        if ($currentlogin > $lastlogin1m) {
            return true;
        } else {
            return false;
        }    
    }

    /**
    * Функция создает учетную запись в домене stud.samgups.ru 
    * с использованием данных из 1С: Университет
    *
    * @param  string  $id1c     Идентификатор 1С
    * @param  string  $username Имя пользователя
    * @param  string  $password Пароль
    * @return bool    Возвращает метку на успех операции обновления.
    */
    function get_create_students_in_domain ($id1c, $username, $password, $fcommand) {
        //Подключаемся к веб-сервисам 1С: Университет
        $client = self::soap_1c_connector (); 
        // Если не удалость подключиться к веб-сервису - откючиться!
        if (is_null($client)) {
            return false;
        }
        //Заполним массив передаваемых параметров
        $params["id"] = $id1c; 
        $params["Login"] = $username;
        $params["Password"] = $password;
        $params["fCommand"] = $fcommand;
        
        //Выполняем операцию
        //GetCreateStudentsInDomain - это метод веб-сервиса 1С, который описан в конфигурации.
        $result = $client->GetCreateStudentsInDomain($params); 
        //Обработаем возвращаемый результат
        $jsResult = $result->return;
        $dataResult = json_decode($jsResult);
        return (bool)$dataResult;
    }

    /**
    * Функция проверяет пользователя в базе данных 1С: Университет
    *
    * @param  string  $username Имя пользователя
    * @param  string  $password Пароль
    * @return bool    Возвращает метку на успех операции обновления.
    */
    function get_password_verify ($username, $password) {
        //Подключаемся к веб-сервисам 1С: Университет
        $client = self::soap_1c_connector (); 
        // Если не удалость подключиться к веб-сервису - откючиться!
        if (is_null($client)) {
            return false;
        }
        //Заполним массив передаваемых параметров 
        $params["Password"] = sha1($password);
        $params["Login"] = $username;
        //Выполняем операцию
        $result = $client->GetPasswordVerify($params); //GetPasswordVerify - это метод веб-сервиса 1С, который описан в конфигурации.
        //Обработаем возвращаемый результат
        $jsResult = $result->return;
        $dataResult = json_decode($jsResult);
        return (bool)$dataResult;
    }

    /**
    * Функция получает по имени пользователя адрес электронной почты
    *
    * @param  string  $username Имя пользователя
    * @param  string  $password Пароль
    * @return string  Возвращает адрес электронной почты.
    */
    function get_mail_from_login ($username, $password) {
        //Подключаемся к веб-сервисам 1С: Университет
        $client = self::soap_1c_connector ();
        // Если не удалость подключиться к веб-сервису - откючиться!
        if (is_null($client)) {
            return false;
        }
        //Заполним массив передаваемых параметров
        $params["Password"] = sha1($password);
        $params["Login"] = $username;
        //Выполняем операцию
        $result = $client->GetMailFromLogin($params); //GetMailFromLogin - это метод веб-сервиса 1С, который описан в конфигурации.
        //Обработаем возвращаемый результат
        $jsResult = $result->return;
        return $jsResult;
    }
    
    /**
    * Функция получает по имени пользователя код 1С: Университет
    *
    * @param  string  $username Имя пользователя
    * @param  string  $password Пароль
    * @return string  Возвращает код студента (1С: Университет).
    */
    function get_idnumber_from_login ($username, $password) {
        //Подключаемся к веб-сервисам 1С: Университет
        $client = self::soap_1c_connector ();
        // Если не удалость подключиться к веб-сервису - откючиться!
        if (is_null($client)) {
            return false;
        }
        //Заполним массив передаваемых параметров 
        $params["Password"] = sha1($password);
        $params["Login"] = $username;
        //Выполняем операцию
        $result = $client->GetNumberFromLogin($params); //GetNumberFromLogin - это метод веб-сервиса 1С, который описан в конфигурации.
        //Обработаем возвращаемый результат
        $jsResult = $result->return;
        return $jsResult;
    }

    /**
    * Функция обновляет пароль в базе данных 1С: Университет
    * 
    * @param  string  $id1c     Идентификатор 1С
    * @param  string  $username Имя пользователя
    * @param  string  $password Пароль
    * @param  string  $email    Адрес электронной почты
    * @return bool    Возвращает метку на успех операции обновления.
    */
    function password_1c_update ($id1c, $username, $password, $email) {
        //Подключаемся к веб-сервисам 1С: Университет
        $client = self::soap_1c_connector ();
        // Если не удалость подключиться к веб-сервису - откючиться!
        if (is_null($client)) {
            return null;
        }
        //Заполним массив передаваемых параметров 
        $params["id"] = $id1c;
        $params["sha1"] = sha1($password);
        $params["login"] = $username;
        $params["email"] = $email;
        //Выполняем операцию
        $result = $client->MakeLogin($params); //MakeLogin - это метод веб-сервиса 1С, который описан в конфигурации.
        //Обработаем возвращаемый результат
        $jsResult = $result->return;
        $dataResult = json_decode($jsResult);
        return $dataResult;
    }

    /**
    * Функция синхронизирует персональные данные с базой
    * данных 1С: Университет
    *
    * @param  string  $id1c     Идентификатор 1С
    * @param  string  $username Имя пользователя
    * @return bool    Возвращает метку на успех операции синхронизации.
    */
    function get_1c_personalinfo ($id1c, $username) {
        global $CFG, $DB, $USER;
        //Подключаемся к веб-сервисам 1С: Университет
        $client = self::soap_1c_connector ();
        // Если не удалость подключиться к веб-сервису - откючиться!
        if (is_null($client)) {
            return null;
        }
        //Заполним массив передаваемых параметров 
        $params["personid"] = $id1c;
        //Выполняем операцию
        $result = $client->GetPersonInfoById($params); //GetPersonInfoById - это метод веб-сервиса 1С, который описан в конфигурации.
        //Обработаем возвращаемый результат
        $jsResult = $result->return;       
        if (property_exists($jsResult, 'uni8array')) {
            $uni8array = $jsResult->uni8array; //получим значение параметра uni8array, который был сформирован при ответе веб-сервиса 1С
            if (!empty($uni8array->p0)) {
                $updateuser = new stdClass();
                $user = $DB->get_record('user', array('username' => $username));
                $updateuser->id          = $user->id;
                $updateuser->firstname   = $uni8array->p1;
                $updateuser->lastname    = $uni8array->p0;
                $updateuser->middlename  = $uni8array->p2;

                // Не загружаем фотографии, 
                //т.к. их синхронизация теперь выполняется через Azure
                //self::user_update_picture($user, $uni8array->p3);
                user_update_user($updateuser, false);
            }
        }
        return true;
    }

    /**
     * Загрузка изображений из папки 1с: Университет
     */
    public function user_update_picture($user, $originalfile) {
        global $DB, $USER, $CFG;
        if (empty($user) or empty($originalfile)) {
            return false;
        }
        $originalfile = str_replace('\\','/',$originalfile);
        $fullpathfile = self::SOAP_MOUNTPATH . $originalfile;
        // BMP файлы необходимо конвертировать в JPG перед добавлением в аватар
        $expansion = strtolower(substr(strrchr($originalfile, '.'), 1));
        // Создаем временный файл, конвертируем bmp в jpg 
        if ($expansion == 'bmp') {
            $imagick = new Imagick();
            $imagick->readImage($fullpathfile);
            $imagick->setImageFormat ("jpeg");
            $tmpFile = tmpfile();
            // Загружаем в профиль конвертированное изображение
            $fullpathfile = array_search('uri', @array_flip(stream_get_meta_data($tmpFile)));
            $imagick->writeImages($fullpathfile, false);
            //error_log("Отладка конвертирования изображений: " . $fullpathfile, 0); 
        }
        if ($newrev = self::my_save_profile_image($user->id, $fullpathfile)) {
            $DB->set_field('user', 'picture', $newrev, array('id'=>$user->id));
        }
        return true;
    }

    /**
    * Функция получает информацию от веб-сервиса 1С: Университет
    * глобальные группы и номера зачеток для портфолио 
    *
    * @param  string  $id1c     Идентификатор 1С
    * @param  string  $username Имя пользователя
    * @return bool    Возвращает метку на успех операции.
    */
    function get_1c_cohort ($id1c, $username) {
        global $CFG, $DB, $USER;
        //Подключаемся к веб-сервисам 1С: Университет
        $client = self::soap_1c_connector ();
        // Если не удалость подключиться к веб-сервису - откючиться!
        if (is_null($client)) {
            return null;
        }
        //Заполним массив передаваемых параметров 
        $params["id"] = $id1c;
        $params["status"] = false; // Выводить список всех групп
        //Выполняем операцию
        //$result = $client->GetAllGroupByStudID($params); //GetAllGroupByStudID - это метод веб-сервиса 1С, который описан в конфигурации.
        $result = $client->GetGroupByIdForLms($params); //GetAllGroupByStudID - это метод веб-сервиса 1С, который описан в конфигурации.
        //Обработаем возвращаемый результат
        $jsResult = $result->return;      
        if (property_exists($jsResult, 'uni8array')) {
            $uni8arrays = $jsResult->uni8array; //получим значение параметра uni8array, который был сформирован при ответе веб-сервиса 1С
            if (is_array($uni8arrays)) {
                // Если массив, значит групп больше чем одна
                foreach ($uni8arrays as $uni8array) {
                    self::check_cohort_member ($uni8array, $username);
                    // Добавляем в портфолио номер зачетной книги
                    self::check_sibport_group  ($uni8array, $username);
                }
            } else {
                // Если нет, значит обрабатываем одну группу
                self::check_cohort_member ($uni8arrays, $username);
                // Добавляем в портфолио номер зачетной книги
                self::check_sibport_group  ($uni8arrays, $username);
            }
        }
        return true;
    }
    
    /**
     * Проверяет, является ли пользователь членом данной гл. группы
     * Если не является, добавляет в перечень групп
     *
     * @param array  $array массив из 1С.
     * @param string $username имя пользователя.
     *
     * @return bool верно, если пользователь член группы
     */
    function check_cohort_member ($array, $username) {
		//p0 = Строка(Состояние);
		//p1 = Строка(Группа);
        //p2 = Строка(ЗачетнаяКнига);
        global $DB;
        if (!empty($array->p1)) {
            $cohort = $DB->get_record('cohort', ['idnumber' => $array->p1]);
            $user = $DB->get_record('user', array('username' => $username));
            
            if (!is_object($cohort)) {
                // Глобальной группы с этим именем нет
                $cohort->name = $array->p1;
                $cohort->idnumber = $array->p1;
                $cohort->contextid = 1;
                $cohort->visible = 1;
                $cohort->id = cohort_add_cohort($cohort);
            }
            if (!cohort_is_member($cohort->id, $user->id)) {
                // Пользователь не член глобальной группы
                if ((int)$array->p0 == 1) {
                    // Состоит в группе
                    cohort_add_member($cohort->id, $user->id); 
                }
            }
            else {
                if ((int)$array->p0 == 0) {
                    // Не состоит в группе, удаляем
                    cohort_remove_member($cohort->id, $user->id); 
                }
                // Пользователь член глобальной группы
                return true; 
            }
        } 
    }

    /**
     * Проверяет, является ли пользователь членом группы в
     * портфолио и если является, обновляет название
     *
     * @param array $array массив данных из 1С.
     * @param string $username имя пользователя.
     *
     * @return bool верно, если пользователь член группы
     */
    function check_sibport_group ($array, $username) {
		//p0 = Строка(Состояние);
		//p1 = Строка(Группа);
		//p2 = Строка(ЗачетнаяКнига);
        global $DB;
        if (!empty($array->p1)) {
            $cohort = $DB->get_record('cohort', ['idnumber' => $array->p1]);
            if (is_object($cohort)) {
                // Глобальной группы с этим именем есть
                $group_data = $DB->get_record('sibport_group_data', array('groupid' => $cohort->id));
                if (!is_object($group_data)) {
                    // Группы портфолио нету
                    $sibport = new stdClass();
                    $sibport->groupid = $cohort->id;
                    $sibport->curatorid = Null;
                    $sibport->study = $array->p5;
                    $speciality = $array->p3;
                    if (!empty($array->p4)) {
                        $speciality = $speciality . ' "' . $array->p4 . '"';    
                    } 
                    $sibport->speciality = $speciality;
                    $DB->insert_record('sibport_group_data', $sibport);
                } else {
                    $sibport = new stdClass();
                    $sibport->id = $group_data->id;
                    $sibport->study = $array->p5;
                    $speciality = $array->p3;
                    if (!empty($array->p4)) {
                        $speciality = $speciality . ' "' . $array->p4 . '"';    
                    } 
                    $sibport->speciality = $speciality;
                    // Группа портфолио есть
                    $DB->update_record('sibport_group_data', $sibport, true);
                }
                self::check_sibport_stud ($username, $cohort->id, $array->p2);     
            }
        } 
    }

    /**
     * Заполняет информацию об номере студенческого
     * билета в портфолио
     *
     * @param string $username имя пользователя.
     * @param string $cohortid код группы.
     * @param string $number   номер студенческого билета.
     *
     * @return bool верно, если пользователь член группы
     */
    function check_sibport_stud ($username, $cohortid, $number) {
		//p0 = Строка(Состояние);
		//p1 = Строка(Группа);
		//p2 = Строка(ЗачетнаяКнига);
        global $DB;
        $user = $DB->get_record('user', array('username' => $username));
        if (is_object($user)) {
            if (empty($cohortid)) {
                return false;
            }
            $sibport_user = $DB->get_record('sibport_users', array('userid' => $user->id));
            if (!is_object($sibport_user)) {
                // Студента в списке нету
                $sibport = new stdClass();
                $sibport->groupid = $cohortid;
                $sibport->userid = $user->id;
                $sibport->number = $number;
                $DB->insert_record('sibport_users', $sibport);
            } else {
                // Студент в списке есть
                $sibport = new stdClass();
                $sibport->id = $sibport_user->id;
                $sibport->number = $number;
                $DB->update_record('sibport_users', $sibport, true);
            }
        }
    }

    /**
     * Try to save the given file (specified by its full path) as the
     * picture for the user with the given id.
     *
     * @param integer $id the internal id of the user to assign the
     *                picture file to.
     * @param string $originalfile the full path of the picture file.
     *
     * @return mixed new unique revision number or false if not saved
     */
    function my_save_profile_image($id, $originalfile) {
        $context = context_user::instance($id);
        return process_new_icon($context, 'user', 'icon', 0, $originalfile);
    }

    /**
     * Updates the user's password.
     *
     * Called when the user password is updated.
     *
     * @param  object  $user        User table object
     * @param  string  $newpassword Plaintext password
     * @return boolean result
     */
    function user_update_password($user, $newpassword) {
        $user = get_complete_user_data('id', $user->id);
        set_user_preference('auth_manual_passwordupdatetime', time(), $user->id);
        // This will also update the stored hash to the latest algorithm
        // if the existing hash is using an out-of-date algorithm (or the
        // legacy md5 algorithm).
        return update_internal_user_password($user, $newpassword);
    }

    function prevent_local_passwords() {
        return false;
    }

    /**
     * Returns true if this authentication plugin is 'internal'.
     *
     * @return bool
     */
    function is_internal() {
        return true;
    }

    /**
     * Returns true if this authentication plugin can change the user's
     * password.
     *
     * @return bool
     */
    function can_change_password() {
        return true;
    }

    /**
     * Returns the URL for changing the user's pw, or empty if the default can
     * be used.
     *
     * @return moodle_url
     */
    function change_password_url() {
        return null;
    }

    /**
     * Returns true if plugin allows resetting of internal password.
     *
     * @return bool
     */
    function can_reset_password() {
        return true;
    }

    /**
     * Returns true if plugin can be manually set.
     *
     * @return bool
     */
    function can_be_manually_set() {
        return true;
    }

    /**
     * Return number of days to user password expires.
     *
     * If user password does not expire, it should return 0 or a positive value.
     * If user password is already expired, it should return negative value.
     *
     * @param mixed $username username (with system magic quotes)
     * @return integer
     */
    public function password_expire($username) {
        $result = 0;

        if (!empty($this->config->expirationtime)) {
            $user = core_user::get_user_by_username($username, 'id,timecreated');
            $lastpasswordupdatetime = get_user_preferences('auth_manual_passwordupdatetime', $user->timecreated, $user->id);
            $expiretime = $lastpasswordupdatetime + $this->config->expirationtime * DAYSECS;
            $now = time();
            $result = ($expiretime - $now) / DAYSECS;
            if ($expiretime > $now) {
                $result = ceil($result);
            } else {
                $result = floor($result);
            }
        }

        return $result;
    }

   /**
    * Confirm the new user as registered. This should normally not be used,
    * but it may be necessary if the user auth_method is changed to manual
    * before the user is confirmed.
    *
    * @param string $username
    * @param string $confirmsecret
    */
    function user_confirm($username, $confirmsecret = null) {
        global $DB;

        $user = get_complete_user_data('username', $username);

        if (!empty($user)) {
            if ($user->confirmed) {
                return AUTH_CONFIRM_ALREADY;
            } else {
                $DB->set_field("user", "confirmed", 1, array("id"=>$user->id));
                return AUTH_CONFIRM_OK;
            }
        } else  {
            return AUTH_CONFIRM_ERROR;
        }
    }

}


