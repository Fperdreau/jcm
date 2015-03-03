<?php
/**
 * Created by PhpStorm.
 * User: U648170
 * Date: 26-2-2015
 * Time: 18:04
 */

class SessionInstance {
    protected static $_instance;
    private static $timeout = 3600;

    private function __construct() {
        session_start();
        session_set_cookie_params(self::$timeout);
    }

    function __destruct() {
        session_write_close();
    }

    public static function initsession() {
        if(self::$_instance === null)
            self::$_instance = new self();
    }

    public function set($key,$value) {
        $_SESSION[$key] = $value;
    }

    public function get($key) {
        return $_SESSION[$key];
    }
}