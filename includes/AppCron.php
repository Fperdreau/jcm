<?php
/**
 * File for class AppCron
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
 * Class AppCron
 *
 * Handle scheduled tasks and corresponding routines.
 * - installation
 * - update
 * - run
 *
 */
class AppCron extends AppTable {

    /**
     * @var array $table_data: Task table schema
     */
    protected $table_data = array(
        "id"=>array('INT NOT NULL AUTO_INCREMENT',false),
        "name"=>array('CHAR(20)',false),
        "time"=>array('DATETIME',false),
        "frequency"=>array('CHAR(15)',false),
        "path"=>array('VARCHAR(255)',false),
        "status"=>array('CHAR(3)',false),
        "options"=>array('TEXT',false),
        "running"=>array('INT(1) NOT NULL',0),
        "primary"=>"id"
    );

    /**
     * @var array $daysNames: list of days names
     */
    public static $daysNames = array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday','All');

    /**
     * @var string $name: Task's name
     */
    public $name;

    /**
     * @var datetime $time: running time
     */
    public $time = '1970-01-01 00:00:00';

    /**
     * @var string $frequency: running frequency (format: 'month,days,hours,minutes')
     */
    public $frequency = '0,0,0,0';

    /**
     * @var string $path: path to script
     */
    public $path;

    /**
     * @var int $status: Task's status (0=>Off, 1=>On)
     */
    public $status = 0;

    /**
     * Is this task registered into the database?
     * @var bool $installed
     */
    public $installed = 0;

    /**
     * Is this task currently running
     * @var int
     */
    public $running = 0;

    /**
     * Task's settings
     * Must be formatted as follows:
     *     $options = array(
*                       'setting_name'=>array(
     *                     'options'=>array(),
     *                     'value'=>0)
     *                );
     *     'options': if not an empty array, then the settings will be displayed as select input. In this case, options
     * must be an associative array: e.g. array('Yes'=>1, 'No'=>0). If it is empty, then it will be displayed as a text
     * input.
     * @var array $options
     */
    public $options=array();

    /**
     * Task's description
     * @var string $description
     */
    public static $description;

    /** Application logger
     * @var AppLogger
     */
    public static $logger;

    /**
     * Constructor
     * @param AppDb $db
     * @param bool $name
     */
    public function __construct(AppDb $db, $name=False) {
        parent::__construct($db, 'Crons', $this->table_data);
        $this->path = dirname(dirname(__FILE__).'/');

        self::get_logger();

        // Get task's information
        $this->get();

        // If time is default, set time to now
        $this->time = ($this->time === '1970-01-01 00:00:00') ? date('Y-m-d H:i:s', time()) : $this->time;
    }

    /**
     * Factory for logger
     * @return AppLogger
     */
    public static function get_logger() {
        self::$logger = AppLogger::get_instance(get_class());
        return self::$logger;
    }

    /**
     * Register scheduled task into the database
     * @return bool|mysqli_result
     */
    public function install() {
        $class_vars = get_class_vars($this->name);
        return $this->make($class_vars);
    }

    /**
     * Run scheduled tasks and send a notification to the admins
     * @return array
     * @throws Exception
     */
    public function execute_all() {
        $runningCron = $this->getRunningJobs();
        $nbJobs = count($runningCron);
        $logs = array();
        if ($nbJobs > 0) {
            $logs['msg'] = self::$logger->log("There are $nbJobs task(s) to run.");
            foreach ($runningCron as $job) {
                $logs[$job] = $this->execute($job);
            }
            return $logs;
        } else {
            return $logs;
        }
    }

    /**
     * Run scheduled tasks and send a notification to the admins
     * @param string $task_name: task name
     * @return array|mixed
     */
    public function execute($task_name) {
        self::get_logger();
        /**
         * Instantiate job object
         * @var AppCron $thisJob
         */
        $thisJob = $this->instantiate($task_name);
        $thisJob->get();

        $logs = array();

        if ($thisJob->running == 0) {
            $thisJob->lock();

            $this::$logger->log("Task '{$task_name}' starts");
            $result = null;

            // Run job
            try {
                $result = $thisJob->run();
                $logs[] = $this::$logger->log("Task '$task_name' result: $result");
            } catch (Exception $e) {
                $thisJob->unlock();
                $logs[] = $this::$logger->log("Execution of '$task_name' encountered an error: " . $e->getMessage());
            }

            // Update new running time
            $newTime = $thisJob->updateTime();
            if ($newTime['status']) {
                $logs[] = $this::$logger->log("$task_name: Next running time: {$newTime['msg']}");
            } else {
                $logs[] = $this::$logger->log("$task_name: Could not update the next running time");
            }

            $thisJob->unlock();
            $logs[] = $this::$logger->log("Task '$task_name' completed");
        } else {
            $this::$logger->log("Task '{$task_name}' is already running");

        }

        return $logs;
    }

    /**
     * Lock the task. A task will not be executed if currently running
     * @return bool
     */
    protected function lock() {
        $this->running = 1;
        return $this->update(array('running'=>1));
    }

    /**
     * Unlock the task. A task will not be executed if currently running
     * @return bool
     */
    protected function unlock() {
        $this->running = 0;
        return $this->update(array('running'=>0));
    }

    /**
     * Sends an email to admins with the scheduled tasks logs
     * @param array $logs
     * @return mixed
     */
    public function notify_admin(array $logs) {
        $MailManager = new MailManager($this->db);

        // Convert array to string
        $string = "<p>{$logs['msg']}</p>";
        foreach ($logs as $job=>$info) {
            if ($job == 'msg') {
                continue;
            }
            $string .= "<h3>{$job}</h3>";
            foreach ($info as $status) {
                $string .= "<li>{$status}</li>";
            }
        }

        // Get admins email
        $adminMails = $this->db->getinfo($this->db->tablesname['User'],'email',array('status'),array("'admin'"));
        if (!is_array($adminMails)) $adminMails = array($adminMails);
        $content['body'] = "
            <p>Hello, </p>
            <p>Please find below the logs of the scheduled tasks.</p>
            <div style='display: block; padding: 10px; margin: 0 30px 20px 0; border: 1px solid #ddd; background-color: rgba(255,255,255,1);'>
                <div style='color: #444444; margin-bottom: 10px;  border-bottom:1px solid #DDD; font-weight: 500; font-size: 1.2em;'>
                    Logs
                </div>
                <div style='padding: 5px; background-color: rgba(255,255,255,.5); display: block;'>
                    $string
                </div>
            </div>";
        $content['subject'] = "Scheduled tasks logs";
        if ($MailManager->send($content, $adminMails)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Register cronjobs to the table
     * @param array $post
     * @return bool|mysqli_result
     */
    public function make($post=array()) {
        $class_vars = get_class_vars('AppCron');
        $content = $this->parsenewdata($class_vars,$post,array('installed', 'daysNames', 'daysNbs', 'hours', 'description'));
        return $this->db->addcontent($this->tablename,$content);
    }

    /**
     * Get info from the scheduled tasks table
     */
    public function get() {
        $sql = "SELECT * FROM {$this->tablename} WHERE name='{$this->name}'";
        $req = $this->db->send_query($sql);
        $data = mysqli_fetch_assoc($req);
        if (!empty($data)) {
            foreach ($data as $prop=>$value) {
                $value = ($prop == "options") ? json_decode($value,true):$value;
                $this->$prop = $value;
            }
        }
    }

    /**
     * Update cronjobs table
     * @param array $post
     * @return bool
     */
    public function update($post=array()) {
        $class_vars = get_class_vars('AppCron');
        $content = $this->parsenewdata($class_vars,$post,array('installed','daysNames','daysNbs','hours', 'description'));
        return $this->db->updatecontent($this->tablename,$content,array("name"=>$this->name));
    }

    /**
     * Delete tasks from the cronjobs table
     * @return bool|mysqli_result
     */
    public function delete() {
        $this->db->deletecontent($this->db->tablesname['Crons'],array('name'),array($this->name));
        return $this->db->deletetable($this->tablename);
    }

    /**
     * Check if this plugin is registered to the db
     */
    public function isInstalled() {
        $plugins = $this->db->getinfo($this->db->tablesname['Crons'],'name');
        return in_array($this->name,$plugins);
    }

    /**
     * Instantiate a class from class name
     * @param: class name (must be the same as the file name)
     * @return AppCron:
     */
    public function instantiate($pluginName) {
        $folder = PATH_TO_APP.'/cronjobs/';
        include_once($folder . $pluginName .'.php');
        return new $pluginName($this->db);
    }

    /**
     * Get next running time from day number, day name and hour
     * @param string $time
     * @param array $frequency
     * @return string: updated running time
     */
    static function parseTime($time, array $frequency) {
        $strtime = date('Y-m-d H:i:s', strtotime($time));
        $date = new DateTime($strtime);
        $date->add(new DateInterval("P{$frequency[0]}M{$frequency[1]}DT{$frequency[2]}H{$frequency[3]}M0S"));
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Update scheduled time
     * @return bool
     */
    function updateTime() {
        $newTime = self::parseTime($this->time, explode(',', $this->frequency));
        if ($this->update(array('time'=>$newTime))) {
            $result['status'] = true;
            $result['msg'] = $newTime;
        } else {
            $result['status'] = false;
        }
        return $result;
    }

    /**
     * Get list of the scheduled tasks to run
     * @return array
     */
    public function getRunningJobs() {
        $now = strtotime(date('Y-m-d H:i:s'));
        $jobs = $this->getJobs();
        $runnningjobs = array();
        foreach ($jobs as $thisJob=>$info) {
            $jobTime = strtotime($info['time']);
            if ($info['installed'] && $info['status'] == 'On' && $now >= $jobTime && $now<=($jobTime+(59*60))) {
                $runnningjobs[] = $thisJob;
            }
        }
        return $runnningjobs;
    }

    /**
     * Get list of scheduled tasks, their settings and status
     * @return array
     * @internal param bool $page
     */
    public function getJobs() {
        $folder = PATH_TO_APP.'/cronjobs/';
        $cronList = scandir($folder);
        $jobs = array();
        foreach ($cronList as $cronFile) {
            if (!empty($cronFile) && !in_array($cronFile,array('.','..','run.php'))) {
                $name = explode('.',$cronFile);
                $name = $name[0];
                
                /**
                 * @var AppCron $thisPlugin
                 */
                $thisPlugin = $this->instantiate($name);
                
                if ($thisPlugin->isInstalled()) {
                    $thisPlugin->get();
                }
                
                $jobs[$name] = array(
                    'installed' => $thisPlugin->isInstalled(),
                    'status' => $thisPlugin->status,
                    'path'=>$thisPlugin->path,
                    'time'=>$thisPlugin->time,
                    'frequency'=>$thisPlugin->frequency,
                    'options'=>$thisPlugin->options,
                    'description'=>$thisPlugin::$description,
                    'running'=>$thisPlugin->running
                );
            }
        }
        return $jobs;
    }

    /**
     * Display job's settings
     * @return string
     */
    public function displayOpt() {
        $content = "<div style='font-weight: 600;'>Options</div>";
        if (!empty($this->options)) {
            $opt = '';
            foreach ($this->options as $optName=>$settings) {
                if (isset($settings['options']) && !empty($settings['options'])) {
                    $options = "";
                    foreach ($settings['options'] as $prop=>$value) {
                        $options .= "<option value='{$value}'>{$prop}</option>";
                    }
                    $optProp = "<select name='{$optName}'>{$options}</select>";
                } else {
                    $optProp = "<input type='text' name='$optName' value='{$settings['value']}'/>";
                }
                $opt .= "
                    <div class='form-group'>
                        {$optProp}
                        <label for='{$optName}'>{$optName}</label>
                    </div>
                ";
            }
            $content .= "
                <form method='post' action='php/form.php'>
                {$opt}
                    <input type='submit' class='modOpt' data-op='cron' value='Modify'>
                </form>
                
                ";
        } else {
            $content = "No settings available for this task.";
        }
        return $content;
    }

    /** 
     *  Display scheduled task's logs 
     * @param string $name: Task's name
     * @return null|string: logs
     */
    public static function showLog($name) {
        $logs = AppLogger::get_logs(get_class());
        if (empty($logs)) {
            return null;
        }
        $path = $logs[0];
        $logs = null;

        if (is_file($path)) {
            $content = file_get_contents($path);
            $pattern = "/[^\\n]*{$name}[^\\n]*/";
            preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);
            foreach ($matches[0] as $res=>$line) {
                $logs .= "<pre>{$line[0]}</pre>";
            }
        }

        if (is_null($logs)) {
            $logs = 'Nothing to display';
        }
        return $logs;
    }

    /**
     *  Delete scheduled task's logs
     * @param string $name: Task's name
     * @return null|string: logs
     */
    public static function deleteLog($name=null) {
        $name = (is_null($name)) ? get_class() : $name;
        $result = false;
        foreach (AppLogger::get_logs($name) as $path) {
            if (is_file($path)) {
                $result = unlink($path);
            } else {
                $result = false;
            }
        }
        return $result;
    }
    
    /**
     * Display jobs list
     * @return string
     */
    public function show() {
        $jobsList = $this->getJobs();

        $cronList = "";
        foreach ($jobsList as $cronName => $info) {
            $installed = $info['installed'];
            $pluginDescription = (!empty($info['description'])) ? $info['description']:null;

            if ($installed) {
                $install_btn = "<div class='installDep workBtn uninstallBtn' data-type='cron' data-op='uninstall' data-name='$cronName'></div>";
            } else {
                $install_btn = "<div class='installDep workBtn installBtn' data-type='cron' data-op='install' data-name='$cronName'></div>";
            }
            
            if ($info['status'] === 'On') {
                $activate_btn = "<div class='activateDep workBtn deactivateBtn' data-type='cron' data-op='Off' data-name='$cronName'></div>";
            } else {
                $activate_btn = "<div class='activateDep workBtn activateBtn' data-type='cron' data-op='On' data-name='$cronName'></div>";
            }

            // Icon showing if task is currently being executed
            $css_running = ($info['running'] == 1) ? 'is_running' : 'not_running';
            $running_icon = "<div class='task_running_icon {$css_running}'></div>";

            $runBtn = "<div class='run_cron workBtn runBtn' data-cron='$cronName'></div>";

            $datetime = $info['time'];
            $date = date('Y-m-d', strtotime($datetime));
            $time = date('H:i', strtotime($datetime));

            $frequency = (!empty($info['frequency'])) ? explode(',', $info['frequency']): array(0, 0, 0, 0);

            $cronList .= "
            <div class='plugDiv' id='cron_$cronName'>
                <div class='plugHeader'>
                    <div class='plug_header_panel'>
                        <div class='plugName'>$cronName</div>
                        <div class='plugTime' id='cron_time_$cronName'>$datetime</div>
                    </div>
                    <div class='optBar'>
                        <div class='optShow workBtn settingsBtn' data-op='cron' data-name='$cronName'></div>
                        {$install_btn}
                        {$runBtn}
                        {$activate_btn}
                        {$running_icon}
                    </div>
                </div>

                <div class='plugSettings'>
                    <div class='description'>
                        {$pluginDescription}
                    </div>
                    <div>
    
                        <div class='settings'>
                            <form method='post' action='php/form.php'>
                            
                                <div class='plug_settings_panel'>
                                    <div>Date & Time</div>
                                    <div class='form-group field_small'>
                                        <input type='date' name='date' value='{$date}'/>
                                        <label>Date</label>
                                    </div>
                                    <div class='form-group field_small'>
                                        <input type='time' name='time' value='{$time}' />
                                        <label>Time</label>
                                    </div>
                                </div>

                                <div class='plug_settings_panel frequency_container'>
        
                                    <div>Frequency</div>
                                    <div class='form-group field_small'>
                                        <input name='months' type='number' value='{$frequency[0]}'/>
                                        <label>Months</label>
                                    </div>
                                    <div class='form-group field_small'>
                                        <input name='days' type='number' value='{$frequency[1]}'/>
                                        <label>Days</label>
                                    </div>
                                    <div class='form-group field_small'>
                                        <input name='hours' type='number' value='{$frequency[2]}'/>
                                        <label>Hours</label>
                                    </div>
                                    <div class='form-group field_small'>
                                        <input name='minutes' type='number' value='{$frequency[3]}'/>
                                        <label>Minutes</label>
                                    </div>
                                </div>
                                <div class='submit_btns'>
                                    <input type='hidden' name='modCron' value='{$cronName}'/> 
                                    <input type='submit' value='Update' class='modCron'/>
                                </div>
                            </form>
    
                        </div>
    
                        <div class='plugOpt' id='$cronName'></div>
                        <div>
                            <a href='" . URL_TO_APP . "php/form.php?show_log_manager=true&class=AppCron&search={$cronName}' class='show_log_manager' id='{$cronName}'>
                            <input type='submit' value='Show logs' id='{$cronName}' />
                            </a>
                        </div>

                        <div class='log_target_container' id='${cronName}' style='display: none'></div>

                    </div>
                    
                </div>
            </div>
            ";
        }

        return $cronList;
    }
}