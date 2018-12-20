<?php
/**
 * File for class SessionInstance
 *
 * PHP version 5
 *
 * @author Florian Perdreau (fp@florianperdreau.fr)
 * @copyright Copyright (C) 2014 Florian Perdreau
 * @license <http://www.gnu.org/licenses/agpl-3.0.txt> GNU Affero General Public License v3
 *
 * This file is part of Journal Club Manager.
 *
 * Journal Club Manager is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Journal Club Manager is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Journal Club Manager.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace includes;

/**
 * Class SessionInstance
 *
 * Manage $_SESSION.
 */
class SessionInstance
{

    /**
     * @var $instance: session instance
     */
    protected static $instance;

    /**
     * Maximum duration of session (in seconds)
     */
    const TIMEOUT = 30;

    /**
     * Warning timing (in seconds)
     */
    const WARNING = 10;

    /**
     * Constructor
     */
    private function __construct()
    {
        session_start();
        session_set_cookie_params(self::TIMEOUT);
    }

    /**
     * Destroy instance of $_SESSION
     */
    public function __destruct()
    {
        session_write_close();
    }

    /**
     * Create instance of $_SESSION
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Set $_SESSION content
     * @param $key
     * @param $value
     */
    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Get $_SESSION content
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        return $_SESSION[$key];
    }

    /**
     * Check if session has started
     * @return bool
     */
    public static function isStarted()
    {
        return session_status() !== PHP_SESSION_NONE;
    }

    /**
     * Start logged session
     *
     * @param string $username: user name
     * @param string $status: user status
     * @return void
     */
    public static function startLoggedSession($username, $status)
    {
        $_SESSION['auth'] = $username;
        $_SESSION['logok'] = true;
        $_SESSION['login_start'] = time();
        $_SESSION['login_expire'] = $_SESSION['login_start'] + self::TIMEOUT;
        $_SESSION['login_warning'] = self::WARNING;
        $_SESSION['username'] = $username;
        $_SESSION['status'] = $status;
    }

    /**
     * Destroy session instance
     * @return bool|string
     */
    public static function destroy()
    {
        if (self::isStarted()) {
            $_SESSION = array();
            unset($_SESSION);
            session_destroy();
            return App::getAppUrl();
        } else {
            return false;
        }
    }

    /**
     * Check login status
     *
     * @return void
     */
    public static function checkLogin()
    {
        if (self::isLogged()) {
            $elapsed = time() - $_SESSION['login_start'];
            $remaining = $_SESSION['login_expire'] - time();
            $result = array(
                "start"=>$_SESSION['login_start'],
                "expire"=>$_SESSION['login_expire'],
                "warning"=>$_SESSION['login_warning'],
                "remaining"=>$remaining,
                "expired"=>$elapsed >= self::TIMEOUT
            );
        } else {
            $result = false;
        }
        return $result;
    }

    /**
     * Extend user session
     */
    public function extendSession()
    {
        // Extend session duration
        if (SessionInstance::isLogged()) {
            $_SESSION['login_start'] = time();
            $_SESSION['login_expire'] = $_SESSION['login_start'] + self::TIMEOUT;
            $result = array(
                "start"=>$_SESSION['login_start'],
                "expire"=>$_SESSION['login_expire'],
                "warning"=>$_SESSION['login_warning'],
                "expired"=>false
            );
            return $result;
        } else {
            return false;
        }
    }

    /**
     * Check if user is logged in
     * @return bool
     */
    public static function isLogged()
    {
        return SessionInstance::isStarted() && isset($_SESSION['auth']) && $_SESSION['logok'] == true;
    }
}
