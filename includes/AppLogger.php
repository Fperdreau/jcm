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
     * @var string: Default file name
     */
    static private $default_name = 'system';

    /**
     * @var array: logger instances
     */
    private static $instances;

    private static $class_name;

    /**
     * AppLogger constructor.
     * @param null $file_name
     */
    private function __construct($file_name=null) {
        $this->file_name = (is_null($file_name)) ? self::$default_name : $file_name;
        self::$class_name = (is_null($file_name)) ? self::$default_name : $file_name;
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

    public static function get_path() {
        return PATH_TO_APP . "/logs/";
    }

    /**
     * Get logger
     * @param $class_name
     * @return AppLogger
     */
    public static function get_instance($class_name) {
        if (is_null(self::$instances) or !isset(self::$instances[$class_name]) ) {
            self::$instances[$class_name] = new self($class_name);
        }
        return self::$instances[$class_name];
    }

    /**
     * Write logs into file
     * @param $string
     * @return string
     */
    public function log($string) {
        if (!is_file($this->file)) {
            $fp = fopen($this->file,"w+");
        } else {
            $fp = fopen($this->file,"a+");
        }

        if (php_sapi_name() === "cli") {echo $string  . PHP_EOL;}

        try {
            fwrite($fp, self::format($string));
            fclose($fp);
        } catch (Exception $e) {
            echo "<p>Could not write into file '{$this->file}':<br>".$e->getMessage()."</p>";
        }
        return $string;
    }

    /**
     * Delete logs
     * @return bool
     */
    public function delete() {
        return unlink($this->file);
    }

    /**
     * Format log
     * @param $message
     * @return string
     */
    private static function format($message) {
        return "[" . date('Y-m-d H:i:s') . "] - " . self::$class_name . " - [User: ". self::get_user() ."]: $message.\r\n";
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