<?php

/**
 * File for class AppLogger
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
 * Class AppLogger
 */
class AppLogger {

    /**
     * @var string: log file name
     */
    private $file;

    /**
     * File handler
     */
    private $handler;

    /**
     * @var string: Default file name
     */
    static private $default_name = 'system';

    /**
     * @var array: logger instances
     */
    private static $instances;

    /**
     * Calling class name
     * @var null|string
     */
    private static $class_name;

    /**
     * Verbose level
     * @var string
     */
    private static $level = 'info';

    /**
     * Verbose levels
     */
    const DEBUG = 'debug';
    const INFO = 'info';
    const ERROR = 'error';
    const WARN = 'warning';
    const CRIT = 'critical';
    const FATAL = 'fatal';

    /**
     * AppLogger constructor.
     * @param null $file_name
     * @param null $class_name
     */
    private function __construct($file_name=null, $class_name=null) {
        $this->file_name = (is_null($file_name)) ? self::$default_name : $file_name;
        $this->set_class($class_name);

        $now = date('Ymd');
        $this->file = self::get_path() . "{$this->file_name}_{$now}.log";

        # Create log folder if it does not exist yet
        if (!is_dir(self::get_path())) {
            mkdir(self::get_path(), 0777);
        }
    }

    /**
     * Get log files
     * @param $class_name
     * @return array
     */
    public static function get_logs($class_name) {
        $files = array();
        foreach (scandir(self::get_path()) as $item) {
            if (preg_match("/{$class_name}/i", $item)) {
                $files[] = self::get_path() . $item;
            }
        }
        return $files;
    }

    /**
     * Verbose level setter
     * @param string $level
     */
    public static function set_level($level) {
        self::$level = $level;
    }

    /**
     * Get path to log files
     * @return string
     */
    public static function get_path() {
        return PATH_TO_APP . "/logs/";
    }

    /**
     * Get logger
     * @param string $log_name
     * @param null|string $class_name
     * @return AppLogger
     */
    public static function get_instance($log_name, $class_name=null) {
        if (is_null(self::$instances) or !isset(self::$instances[$log_name]) ) {
            self::$instances[$log_name] = new self($log_name, $class_name);
        } else {
            if (!is_null($class_name)) {
                self::$instances[$log_name]->set_class($class_name);
            }
        }

        return self::$instances[$log_name];
    }

    public function set_class($class_name) {
        self::$class_name = (is_null($class_name)) ? self::$default_name : $class_name;
    }

    /**
     * Alias for AppLogger->log($msg, $echo, $level)
     * @param $msg
     * @param bool $echo
     */
    public function info($msg, $echo=false) {
        $this->log($msg, $echo, AppLogger::INFO);
    }

    /**
     * Alias for AppLogger->log($msg, $echo, $level)
     * @param $msg
     * @param bool $echo
     */
    public function debug($msg, $echo=false) {
        $this->log($msg, $echo, AppLogger::DEBUG);
    }

    /**
     * Alias for AppLogger->log($msg, $echo, $level)
     * @param $msg
     * @param bool $echo
     */
    public function warning($msg, $echo=false) {
        $this->log($msg, $echo, AppLogger::WARN);
    }

    /**
     * Alias for AppLogger->log($msg, $echo, $level)
     * @param $msg
     * @param bool $echo
     */
    public function critical($msg, $echo=false) {
        $this->log($msg, $echo, AppLogger::CRIT);
    }

    /**
     * Alias for AppLogger->log($msg, $echo, $level)
     * @param $msg
     * @param bool $echo
     */
    public function fatal($msg, $echo=false) {
        $this->log($msg, $echo, AppLogger::FATAL);
    }

    /**
     * Alias for AppLogger->log($msg, $echo, $level)
     * @param $msg
     * @param bool $echo
     */
    public function error($msg, $echo=false) {
        $this->log($msg, $echo, AppLogger::ERROR);
    }

    /**
     * Write logs into file
     * @param array|string $msg
     * @param bool $echo
     * @param string $level
     * @return string
     */
    public function log($msg, $echo=false, $level=null) {
        if (is_null($string = self::parse_msg($msg))) {
            return null;
        }

        if (is_null($level)) {
            $level = self::parse_level($msg);
        }

        // Echo message
        if ($echo or php_sapi_name() === "cli") {
            self::echo_msg($string);
        }

        // Format message
        $string = self::format($string, $level);

        // Write into log file
        $this->write($string);

        return $msg;
    }

    /**
     * Parse level
     * @param $msg
     * @return string
     */
    private static function parse_level($msg) {
        if (is_array($msg)) {
            $level = (isset($msg['status']) & $msg['status'] === false) ? self::ERROR : self::INFO;
        } else {
            $level = (is_bool($msg) & !$msg) ? self::ERROR : self::INFO;
        }
        return $level;
    }

    /**
     * Parse message
     * @param $msg
     * @return mixed|null
     */
    private static function parse_msg($msg) {
        if (is_array($msg)) {
            if (isset($msg['msg'])) {
                return $msg['msg'];
            } else {
                return null;
            }
        } else {
            return $msg;
        }
    }

    /**
     * Open log file
     * @return null|resource
     */
    private function open() {
        if (!is_null($this->handler)) {
            return $this->handler;
        } else {
            try {
                $mode = !is_file($this->file) ? "w+" : "a+";
                $this->handler = fopen($this->file, $mode);
            } catch (Exception $e) {
                self::echo_msg("Could not open file '{$this->file}':<br>" . $e->getMessage());
            }
            return $this->handler;
        }
    }

    /**
     * Write into log file
     * @param string $msg
     */
    private function write($msg) {
        $this->open();
        try {
            fwrite($this->handler, $msg);
            $this->close();
        } catch (Exception $e) {
            self::echo_msg("Could not write into file '{$this->file}':<br>" . $e->getMessage());
        }
    }

    /**
     * Close log file and set handler to null
     */
    private function close() {
        fclose($this->handler);
        $this->handler = null;
    }

    /**
     * Choose right format to echo string
     * @param string $string
     */
    public static function echo_msg($string) {
        if (php_sapi_name() === "cli") {
            echo $string  . PHP_EOL;
        } elseif (self::isAjax()) {
            echo json_encode($string);
        } else {
            echo "<pre>{$string}</pre>";
        }
    }

    /**
     * Check if it is an AJAX request
     * @return bool
     */
    private static function isAjax() {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
    }

    /**
     * Delete logs
     * @return bool
     */
    public function delete() {
        return unlink($this->file);
    }

    /**
     * Format log message
     * @param string $message
     * @param string $level
     * @return string
     */
    private static function format($message, $level) {
        return "[" . date('Y-m-d H:i:s') . "] - [User: ". self::get_user() ."] - ". strtoupper($level) . " - [" . self::$class_name . "] - : {$message}.\r\n";
    }

    /**
     * Get user
     * @return string
     */
    private static function get_user() {
        if (php_sapi_name() === "cli") {
            return "system";
        } elseif (isset($_SESSION['username'])) {
            return $_SESSION['username'];
        } else {
            return "system";
        }
    }
}