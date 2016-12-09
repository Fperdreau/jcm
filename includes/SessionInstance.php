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

/**
 * Class SessionInstance
 *
 * Manage $_SESSION.
 */
class SessionInstance {

    /**
     * @var $_instance: session instance
     */
    protected static $_instance;

    /**
     * Maximum duration of session
     */
    const timeout = 3600;

    /**
     * Warning timing (in seconds)
     */
    const warning = 300;

    /**
     * Constructor
     */
    private function __construct() {
        session_start();
        session_set_cookie_params(self::timeout);
    }

    /**
     * Destroy instance of $_SESSION
     */
    function __destruct() {
        session_write_close();
    }

    /**
     * Create instance of $_SESSION
     */
    public static function initsession() {
        if(self::$_instance === null)
            self::$_instance = new self();
    }

    /**
     * Set $_SESSION content
     * @param $key
     * @param $value
     */
    public function set($key,$value) {
        $_SESSION[$key] = $value;
    }

    /**
     * Get $_SESSION content
     * @param $key
     * @return mixed
     */
    public function get($key) {
        return $_SESSION[$key];
    }

    /**
     * Check if session has started
     * @return bool
     */
    public static function is_started() {
        return session_status() !== PHP_SESSION_NONE;
    }

    /**
     * Destroy session instance
     * @return bool
     */
    public static function destroy() {
        if (self::is_started()) {
            $_SESSION = array();
            unset($_SESSION);
            session_destroy();
            return true;
        } else {
            return false;
        }
    }
}