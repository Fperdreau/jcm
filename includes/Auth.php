<?php
/**
 *
 * @author Florian Perdreau (fp@florianperdreau.fr)
 * @copyright Copyright (C) 2016 Florian Perdreau
 * @license <http://www.gnu.org/licenses/agpl-3.0.txt> GNU Affero General Public License v3
 *
 * This file is part of DropCMS.
 *
 * DropCMS is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * DropCMS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with DropCMS.  If not, see <http://www.gnu.org/licenses/>.
 */


require 'PasswordHash.php';

/**
 * Class Auth
 * @package Core\Auth
 */
class Auth extends BaseModel {

    /**
     * Settings
     * @var array
     */
    protected $settings = array('max_nb_attempt'=>5);

    /**
     * @var Settings
     */
    private $config;

    /**
     * @var Users
     */
    protected $Users;

    public $date;
    public $username;
    public $last_login;
    public $attempt;

    /**
     * Auth constructor.
     */
    function __construct(){
        parent::__construct();
        $this->Users = new Users();
        $this->config = $this->Settings->settings;
    }

    /**
     * Get id of logged user
     * @return bool
     */
    public static function getUserId(){
        if(self::is_logged()){
            return $_SESSION['auth'];
        }
        return false;
    }

    /**
     * User login
     * @param bool $login: log user in
     * @return mixed
     */
    public function login($login=true) {
        $password = htmlspecialchars($_POST['password']);
        $username = htmlspecialchars($_POST['username']);
        $data = $this->Users->get(array('username'=>$username));

        if (!empty($data)) {
            if ($data['active'] == 1) {
                if ($this->check_pwd($username, $password, $data) == true) {
                    if ($login) {
                        $_SESSION['auth'] = $username;
                        $_SESSION['logok'] = true;
                        $_SESSION['login_start'] = time();
                        $_SESSION['login_expire'] = $_SESSION['login_start'] + SessionInstance::timeout;
                        $_SESSION['login_warning'] = SessionInstance::warning;
                        $_SESSION['username'] = $username;
                        $_SESSION['status'] = $data['status'];
                    }
                    $result['msg'] = "Hi {$data['firstname']},<br> welcome back!";
                    $result['status'] = true;
                } else {
                    $_SESSION['logok'] = false;
                    $result['status'] = false;
                    $attempt = $this->check_attempt($data);
                    if ($attempt == false) {
                        $result['msg'] = "Wrong password. You have exceeded the maximum number
                            of possible attempts, hence your account has been deactivated for security reasons.
                            We have sent an email to your address including an activation link.";
                    } else {
                        $result['msg'] = "Wrong password. {$attempt} login attempts remaining.";
                    }
                }
            } else {
                $result['status'] = false;
                $result['msg'] = "Sorry, your account is not activated yet. <br> You will receive an
                    email as soon as your registration is confirmed by an admin.<br> Please,
                    <a href='" . URL_TO_APP . "index.php?page=contact'>contact us</a> if you have any question.";
            }
        } else {
            $result['status'] = false;
            $result['msg'] = "Wrong username";
        }
        return $result;
    }

    /**
     * Extend user session
     */
    public function extend_login() {
        // Extend session duration
        if (self::is_logged()) {
            $_SESSION['login_expire'] = time() + SessionInstance::timeout;
            $result = array(
                "start"=>$_SESSION['login_start'],
                "expire"=>$_SESSION['login_expire'],
                "warning"=>$_SESSION['login_warning']
            );
            return $result;
        } else {
            return false;
        }
    }

    /**
     * Check number of unsuccessful login attempts.
     * Deactivate the user's account if this number exceeds the maximum
     * allowed number of attempts and send an email to the user with an activation link.
     * @param array $data
     * @return int
     */
    private function check_attempt(array $data) {
        // Get attempt information
        $auth_inf = $this->get(array('username'=>$data['username']));

        // Get last login timestamp and amount of previous login attempts
        if (empty($auth_inf)) {
            $last_login = new DateTime();
            $attempt = 0;
        } else {
            $last_login = new DateTime($auth_inf[0]['last_login']);
            $attempt = $auth_inf[0]['attempt'];
        }

        // Time interval since last login attempt
        $now = new DateTime();
        $diff = $now->diff($last_login);

        // Reset the number of attempts if last login attempt was 1 hour ago
        $data['attempt'] = $diff->h >= 1 ? 0 : $attempt;
        $data['attempt'] += 1;

        // If amount of attempts exceeds authorized limit, then deactivate user account and notify by email
        if ($data['attempt'] >= $this->settings['max_nb_attempt']) {
            $this->Users->activation($data['hash'], $data['email'], 0); // We deactivate the user's account
            $this->Users->send_activation_mail();
            return false;
        }
        $data['last_login'] = date('Y-m-d H:i:s');

        // Add/Update login attempt info
        if (empty($auth_inf)) {
            $this->add($data);
        } else {
            $this->update($data, array('username'=>$data['username']));
        }
        return (int)($this->settings['max_nb_attempt'] - $data['attempt']);
    }

    /**
     * Check if the provided password is correct (TRUE) or not (FALSE)
     *
     * @param $username
     * @param $password
     * @param array $data
     * @return bool
     */
    private function check_pwd($username, $password, array $data) {
        if (validate_password($password, $data['password']) == 1) {
            if ($this->is_exist(array('username'=>$username))) {
                return $this->update(array(
                        'attempt'=>0,
                        'last_login'=>date('Y-m-d H:i:s'),
                    ),
                    array("username"=>$username)
                );
            } else {
                return $this->add(array(
                        'date'=>date('Y-m-d H:i:s'),
                        'username'=>$username,
                        'attempt'=>0,
                        'last_login'=>date('Y-m-d H:i:s')
                ));
            }
        } else {
            return false;
        }
    }

    /**
     * Encrypt the password before adding it to the database
     *
     * @param $password
     * @return string
     */
    public static function crypt_pwd($password) {
        $hash = create_hash($password);
        return $hash;
    }

    /**
     * Check if user is logged in
     * @return bool
     */
    public static function is_logged() {
        return SessionInstance::is_started() && isset($_SESSION['auth']) && $_SESSION['logok'] == true;
    }

}