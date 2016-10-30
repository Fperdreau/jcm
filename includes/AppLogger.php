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
                $files[] = $item;
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

        error_log($string, 3, $this->file);
        // Write into log file
        //$this->write($string);

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
     * @param $file: log file name
     * @return bool
     */
    public static function delete($file) {
        if (is_file(self::get_path() . $file)) {
            return unlink(self::get_path() . $file);
        } else {
            return false;
        }
    }

    /**
     * Format log message
     * @param string $message
     * @param string $level
     * @return string
     */
    private static function format($message, $level) {
        return "[" . date('Y-m-d H:i:s') . "] - [User: ". self::get_user() ."] - ". strtoupper($level) . " - [" . self::$class_name . "] : {$message}.\r\n";
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

    /**
     *  Delete scheduled task's logs
     * @param string $name: Task's name
     * @return null|string: logs
     */
    public static function delete_all($name=null) {
        $name = (is_null($name)) ? get_class() : $name;
        $result = false;
        foreach (AppLogger::get_logs($name) as $path) {
            $result = self::delete($path);
        }
        return $result;
    }

    /**
     * Show last log file
     * @param string $class
     * @return null
     */
    public static function last($class) {
        $logs = AppLogger::get_logs($class);
        if (empty($logs)) {
            return null;
        }
        return self::show($logs[0]);
    }

    /**
     * Render logs manager (view and search in logs)
     * @param $class_name
     * @param null $log_name
     * @return string
     */
    public static function manager($class_name, $log_name=null) {
        $name = self::get_logs($class_name)[0];
        $list = self::show_list(self::get_logs($class_name), $name, $log_name);
        $logs = self::show($name, $log_name);
        return "
            <div class='log_container' id='{$class_name}'>
                <div class='log_search_bar'>
                    <form method='post' action='" . URL_TO_APP . "/php/form.php?show_log=true'>
                        <input type='search' name='search' value='' placeholder='Search...'/>
                        <input type='hidden' name='name' value='$name'/>
                        <input type='button' class='search_log' id='{$class_name}' />
                    </form>
                </div>
                <div class='log_files_container'>
                    <div class='log_list_container'>{$list}</div>
                    <div class='log_content_container' id='{$class_name}'>{$logs}</div>
                </div>

            </div>
        ";
    }

    /**
     * Get all log files for a particular instance
     * @param $class
     * @return null|string
     */
    public static function all($class) {
        $logs = AppLogger::get_logs($class);
        if (empty($logs)) {
            return null;
        }
        return self::show_list($logs);
    }

    // VIEWS

    /**
     *  Display scheduled task's logs
     * @param string $name: class name
     * @param string $search : search
     * @return null|string : logs
     */
    public static function show($name, $search=null) {
        $logs = null;
        $path = self::get_path() . $name;
        if (is_file($path)) {
            $content = file_get_contents($path);
            if (!is_null($search)) {
                $pattern = "/[^\\n]*{$search}[^\\n]*/";
                preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);
                foreach ($matches[0] as $res=>$line) {
                    $logs .= "<pre>{$line[0]}</pre>";
                }
            } else {
                $pattern = "/[^\\n]*[^\\n]*/";
                preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);
                foreach ($matches[0] as $res=>$line) {
                    $logs .= "<pre>{$line[0]}</pre>";
                }
            }
        }

        if (is_null($logs)) {
            $logs = 'Nothing to display';
        }
        return $logs;
    }

    /**
     * Render list of log files
     * @param $data
     * @param null $selected
     * @param null $log_name
     * @return null|string
     */
    public static function show_list($data, $selected=null, $log_name=null) {
        $content = null;
        foreach ($data as $key=>$item) {
            $active = (!is_null($selected) & $item == $selected);
            $content .= self::show_in_list($item, $active, $log_name);
        }
        return $content;
    }

    /**
     * Render log file in list
     * @param $item
     * @param bool $selected
     * @param null $log_name
     * @return string
     */
    public static function show_in_list($item, $selected=false, $log_name=null) {
        $split = explode('_', $item);
        $ext = explode('.', $split[1]);
        $name = $split[0];
        $date = date('d M y',strtotime($ext[0]));
        $active = ($selected) ? 'log_list_active' : null;
        $search = (!is_null($log_name)) ? "&search={$log_name}" : null;
        return "
            <div class='log_list_item_container {$active}' id='{$item}'>
                <div class='log_info'><a href='" . URL_TO_APP . "php/form.php?show_log={$item}{$search}' class='show_log'><span class='log_name'>{$name}</span> - <span class='log_date'>{$date}</span></a></div>
                <div class='log_icon'><a href='" . URL_TO_APP . "php/form.php?delete_log={$name}&file={$item}' class='delete_log'><img src='" . URL_TO_APP . "/images/trash.png' alt='Delete log'></a></div>
            </div>
        ";
    }
}