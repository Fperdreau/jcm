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
class Tasks extends BaseModel {

    /**
     * Scheduled tasks settings
     * @var array
     */
    protected $settings = array(
        'notify_admin_task'=>'yes'
    );

    /**
     * @var array $daysNames: list of days names
     */
    public static $daysNames = array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday','All');


    /** Application logger
     * @var Logger
     */
    public static $logger;

    /**
     * Path to tasks files
     * @var string
     */
    protected $path;

    private $instances;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->path = dirname(dirname(__FILE__).'/');
        self::get_logger();
        $this->loadAll();
    }

    /**
     * Factory for logger
     * @return Logger
     */
    public static function get_logger() {
        self::$logger = Logger::get_instance(get_class());
        return self::$logger;
    }

    /**
     * Instantiate a class from class name
     * @param: class name (must be the same as the file name)
     * @return Task
     */
    public function getTask($pluginName) {
        if (is_null($this->instances) || !in_array($pluginName, array_keys($this->instances))) {
            $this->instances[$pluginName] = new $pluginName();
        }
        return $this->instances[$pluginName];
    }

    public function install($name) {
        if (isset($_POST['name'])) $name = $_POST['name'];
        $result['status'] = $this->add($this->getTask($name)->getInfo());
        $result['status'] = $result['status'] !== false;
        $result['msg'] = $result['status'] === true ? $name . " has been installed" : "Oops, something went wrong";
        return $result;
    }

    public function uninstall($name) {
        if (isset($_POST['name'])) $name = $_POST['name'];
        $result['status'] = $this->delete(array('name'=>$name));
        $result['msg'] = $result['status'] ? $name . " has been uninstalled" : "Oops, something went wrong";
        return $result;
    }

    public function activate($name) {
        if (isset($_POST['name'])) $name = $_POST['name'];
        $result['status'] = $this->update(array('status'=>1), array('name'=>$name));
        $result['msg'] = $result['status'] ? $name . " has been activated" : "Oops, something went wrong";
        return $result;
    }

    public function deactivate($name) {
        if (isset($_POST['name'])) $name = $_POST['name'];
        $result['status'] =  $this->update(array('status'=>0), array('name'=>$name));
        $result['msg'] = $result['status'] ? $name . " has been deactivated" : "Oops, something went wrong";
        return $result;
    }

    /**
     * Check if the plugin is registered to the Plugin table
     * @param string $name: plugin name
     * @return bool
     */
    public function isInstalled($name) {
        return $this->is_exist(array('name'=>$name));
    }

    /**
     * Update plugin's options
     * @return mixed
     */
    public function updateOptions() {
        $name = htmlspecialchars($_POST['name']);
        if ($this->isInstalled($name)) {
            foreach ($_POST as $key=>$setting) {
                $this->getTask($name)->setOption($key, $setting);
            }

            if ($this->update($this->getTask($name)->getInfo(), array('name'=>$name))) {
                $result['status'] = true;
                $result['msg'] = "$name's settings successfully updated!";
            } else {
                $result['status'] = false;
            }
        } else {
            $result['status'] = false;
            $result['msg'] = "You must install this plugin before modifying its settings";
        }

        return $result;
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
                $result = $this->execute($job);
                $logs[$job] = $result['logs'];
            }
            return $logs;
        } else {
            return $logs;
        }
    }

    /**
     * Run scheduled tasks and send a notification to the admins
     * @param string $name: task name
     * @return array|mixed
     */
    public function execute($name) {
        if (isset($_POST['name'])) $name = $_POST['name'];
        self::get_logger();

        /**
         * Instantiate job object
         * @var Task $thisJob
         */
        $thisJob = $this->getTask($name);

        $logs = array();
        $result = false;

        if ($thisJob->running == 0) {
            $this->lock($name);

            $this::$logger->log("Task '{$name}' starts");

            // Run job
            try {
                $result = $thisJob->run();
                $logs[] = $this::$logger->log("Task '{$name}' result: {$result['msg']}");
            } catch (Exception $e) {
                $this->unlock($name);
                $logs[] = $this::$logger->log("Execution of '{$name}' encountered an error: " . $e->getMessage());
            }

            // Update new running time
            $newTime = $this->updateTime($thisJob->name, $thisJob->time, $thisJob->frequency);
            if ($newTime['status']) {
                $logs[] = $this::$logger->log("{$name}: Next running time: {$newTime['msg']}");
            } else {
                $logs[] = $this::$logger->log("{$name}: Could not update the next running time");
            }

            $this->unlock($name);
            $logs[] = $this::$logger->log("Task '{$name}' completed");
        } else {
            $this::$logger->log("Task '{$name}' is already running");
        }

        return array('status'=>$result['status'], 'logs'=>$logs, 'msg'=>end($logs));
    }

    public function stop($name=null) {
        if (isset($_POST['name'])) $name = $_POST['name'];
        $result['status'] = $this->unlock($name);
        return $result;
    }

    /**
     * Lock the task. A task will not be executed if currently running
     * @param string $name: task name
     * @return bool
     */
    public function lock($name) {
        $this->getTask($name)->running = 1;
        return $this->update(array('running'=>1), array('name'=>$name));
    }

    /**
     * Unlock the task. A task will not be executed if currently running
     * @param string $name: task name
     * @return bool
     */
    public function unlock($name) {
        $this->getTask($name)->running = 0;
        return $this->update(array('running'=>0), array('name'=>$name));
    }

    /**
     * Sends an email to admins with the scheduled tasks logs
     * @param array $logs
     * @return mixed
     */
    public function notify_admin(array $logs) {
        $MailManager = new MailManager();

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
        $adminMails = $this->db->resultSet($this->db->tablesname['Users'],array('email'),array('status'=>"admin"));
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
     * Get info from the scheduled tasks table
     * @param string $name: task name
     * @return bool
     */
    public function getInfo($name) {
        $data = $this->get(array('name'=>$name));
        if (empty($data)) return false;

        foreach ($data as $prop=>$value) {
            $value = ($prop == "options") ? json_decode($value,true) : $value;
            $this->$prop = $value;
        }
        return true;
    }

    /**
     * Get next running time from day number, day name and hour
     * @param string $time
     * @param array $frequency
     * @return string: updated running time
     */
    private static function parseTime($time, array $frequency) {
        $str_time = date('Y-m-d H:i:s', strtotime($time));
        $date = new DateTime($str_time);
        $date->add(new DateInterval("P{$frequency[0]}M{$frequency[1]}DT{$frequency[2]}H{$frequency[3]}M0S"));
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Update scheduled time
     * @param $name
     * @param $time
     * @param $frequency
     * @return bool
     */
    public function updateTime($name=null, $time=null, $frequency=null) {
        if (is_null($time)) {
            $name = $_POST['name'];
            $time = date('Y-m-d H:i:s', strtotime($_POST['date'] . ' ' . $_POST['time']));
            $frequency = array($_POST['months'], $_POST['days'], $_POST['hours'], $_POST['minutes']);
            $frequency = implode(',', $frequency);
        }

        try {
            $newTime = self::parseTime($time, explode(',', $frequency));
            if ($this->update(array('frequency'=>$frequency, 'time'=>$newTime), array('name'=>$name))) {
                $result['status'] = true;
                $result['msg'] = $newTime;
            } else {
                $result['status'] = false;
            }
            return $result;
        } catch (Exception $e) {
            Logger::get_instance(APP_NAME, __CLASS__)->error($e);
            $result['status'] = False;
            return $result;
        }
    }

    /**
     * Get list of the scheduled tasks to run
     * @return array
     */
    public function getRunningJobs() {
        $now = strtotime(date('Y-m-d H:i:s'));
        $jobs = $this->loadAll();
        $running_jobs = array();
        foreach ($jobs as $thisJob=>$info) {
            $jobTime = strtotime($info['time']);
            if ($info['installed'] && $info['status'] == 'On' && $now >= $jobTime && $now<=($jobTime+(59*60))) {
                $running_jobs[] = $thisJob;
            }
        }
        return $running_jobs;
    }

    /**
     * Returns Plugin's options form
     * @param string $name: plugin name
     * @return string: form
     */
    public function getOptions($name=null) {
        if (isset($_POST['name'])) $name = $_POST['name'];
        return $this->displayOpt($name, $this->getTask($name)->options);
    }

    /**
     * Get list of scheduled tasks, their settings and status
     * @return array
     */
    public function loadAll() {
        $cronList = scandir(PATH_TO_TASKS);
        $tasks = array();
        foreach ($cronList as $key=>$plugin_name) {
            if (!empty($plugin_name) && !in_array($plugin_name, array('.', '..', 'run.php'))) {
                $split = explode('.', $plugin_name);
                $tasks[$split[0]] = $this->load($split[0]);
            }
        }
        return $tasks;
    }

    /**
     * Load plugin
     * @param string $name
     * @return array
     */
    public function load($name) {

        // Get plugin info if installed
        $installed = $this->isInstalled($name);

        if ($installed) {
            $this->getTask($name)->setInfo($this->get(array('name'=>$name)));
        }

        // Instantiate plugin
        $thisPlugin = $this->getTask($name);

        return array(
            'installed' => $installed,
            'status' => $thisPlugin->status,
            'path'=>$thisPlugin->path,
            'time'=>$thisPlugin->time,
            'frequency'=>$thisPlugin->frequency,
            'options'=>$thisPlugin->options,
            'description'=>$thisPlugin->description,
            'running'=>$thisPlugin->running
        );
    }

    /**
     * Display Plugin's settings
     * @param string $name: plugin name
     * @param array $options
     * @return string
     */
    public function displayOpt($name, array $options) {
        $content = "<h4 style='font-weight: 600;'>Options</h4>";
        if (!empty($options)) {
            $content .= "
                <form method='post' action='php/router.php?controller=". __CLASS__ . "&action=updateOptions&name={$name}'>
                    " . self::renderOptions($options) . "
                    <div class='submit_btns'>
                        <input type='submit' class='processform' value='Modify'>
                    </div>
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
        $logs = Logger::get_logs(get_class());
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
        foreach (Logger::get_logs($name) as $path) {
            if (is_file($path)) {
                $result = unlink($path);
            } else {
                $result = false;
            }
        }
        return $result;
    }

    /**
     * Render Tasks page
     * @return string
     */
    public function index() {
        $notify = $this->settings['notify_admin_task'];

        return "
            <div class='page_header'>
            <p class='page_description'>Here you can install, activate or deactivate scheduled tasks and manage their settings.
            Please note that in order to make these tasks running, you must have set a scheduled task pointing to 'cronjobs/run.php'
            either via a Cron AppTable (Unix server) or via the Scheduled Tasks Manager (Windows server)</p>
            </div>    
            
            <section>
                <h2>General settings</h2>
                <div class='section_content'>
                    <p>You have the possibility to receive logs by email every time a task is executed.</p>
                    <form method='post' action='php/router.php?controller=" . __CLASS__ . "&action=updateOptions'>
                        <input type='hidden' name='config_modify' value='true'/>
                        <div class='form-group' style='width: 300px;'>
                            <select name='notify_admin_task'>
                                <option value='{$notify}' selected>{$notify}</option>
                                <option value='yes'>Yes</option>
                                <option value='no'>No</option>
                            </select>
                            <label>Get notified by email</label>
                        </div>
                        <input type='submit' name='modify' value='Modify' class='modCron'>
                        <div class='feedback' id='feedback_site'></div>
                    </form>
                </div>
            </section>
            
            <div class='feedback'></div>
        
            <section>
                <h2>Tasks list</h2>
                <div class='section_content'>
                " . $this->show() . "
                </div>
            </section>
        ";
    }
    
    /**
     * Display jobs list
     * @return string
     */
    public function show() {

        $cronList = "";
        foreach ($this->loadAll() as $cronName => $info) {

            $pluginDescription = (!empty($info['description'])) ? $info['description'] : null;
            $install_btn = self::install_button($cronName, $info['installed']);
            $activate_btn = self::activate_button($cronName, $info['status']);

            // Icon showing if task is currently being executed
            $css_running = ($info['running'] == 1) ? 'is_running' : 'not_running';
            $running_icon = "<div class='task_running_icon {$css_running}'></div>";

            $runBtn = "<div class='run_cron workBtn runBtn' data-cron='$cronName'></div>";
            $stopBtn = "<div class='stop_cron workBtn stopBtn' data-cron='$cronName'></div>";

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
                        <div class='loadContent workBtn settingsBtn' data-controller='" . __CLASS__ . "' data-action='getOptions' data-destination='.plugOpt#{$cronName}' data-name='{$cronName}'></div>
                        {$install_btn}
                        {$activate_btn}
                        {$runBtn}
                        {$stopBtn}
                        {$running_icon}
                    </div>
                </div>

                <div class='plugSettings'>
                    <div class='description'>
                        {$pluginDescription}
                    </div>
                    <div>
    
                        <div class='settings'>
                            <form method='post' action='php/router.php?controller=" . __CLASS__ . "&action=updateTime'>
                            
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
                                    <input type='hidden' name='name' value='{$cronName}'/> 
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

    /**
     * Run task
     * @return string: result message
     */
    public function run() {
        return "";
    }

    /* VIEWS */

    private static function renderOptions(array $options) {
        $opt = '';
        foreach ($options as $optName=>$settings) {
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
                    <div class='form-group inline_field field_auto'>
                        {$optProp}
                        <label for='{$optName}'>{$optName}</label>
                    </div>
                ";
        }
        return $opt;
    }


    /**
     * Render install/uninstall button
     * @param $pluginName
     * @param $installed
     * @return string
     */
    private static function install_button($pluginName, $installed) {
        if ($installed) {
            return "<div class='installDep workBtn uninstallBtn' data-controller='" . __CLASS__ . "' data-action='uninstall' data-name='$pluginName'></div>";
        } else {
            return "<div class='installDep workBtn installBtn' data-controller='" . __CLASS__ . "' data-action='install' data-name='$pluginName'></div>";
        }
    }

    /**
     * Render activate/deactivate button
     * @param $pluginName
     * @param $status
     * @return string
     */
    private static function activate_button($pluginName, $status) {
        if ($status === 1) {
            return "<div class='activateDep workBtn deactivateBtn' data-controller='" . __CLASS__ . "' data-action='deactivate' data-name='$pluginName'></div>";
        } else {
            return "<div class='activateDep workBtn activateBtn' data-controller='" . __CLASS__ . "' data-action='activate' data-name='$pluginName'></div>";
        }
    }
}