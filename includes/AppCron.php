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

    /**
     * Constructor
     * @param AppDb $db
     * @param bool $name
     */
    public function __construct(AppDb $db, $name=False) {
        parent::__construct($db, 'Crons', $this->table_data);
        $this->path = dirname(dirname(__FILE__).'/');
        if ($name !== False) {
            $this->get();
        }

        // If time is default, set time to now
        $this->time = ($this->time === '1970-01-01 00:00:00') ? date('Y-m-d H:i:s', time()) : $this->time;
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
        $sql = "SELECT * FROM $this->tablename WHERE name='$this->name'";
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
     * @return: object
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
        $newTime = self::parseTime($this->time, $this->frequency);
        if ($this->update(array('time'=>$newTime))) {
            $result['status'] = true;
            $result['msg'] = $newTime;
        } else {
            $result['status'] = false;
        }
        return $result;
    }

    /**
     * Write logs into file
     * @param $file
     * @param $string
     */
    static function logger($file, $string) {
        $cronlog = PATH_TO_APP."/cronjobs/logs/$file";
        if (!is_dir(PATH_TO_APP.'/cronjobs/logs')) {
            mkdir(PATH_TO_APP.'/cronjobs/logs',0777);
        }
        if (!is_file($cronlog)) {
            $fp = fopen($cronlog,"w+");
        } else {
            $fp = fopen($cronlog,"a+");
        }
        $string = "\r\n[" . date('Y-m-d H:i:s') . "]: $string.\r\n";

        try {
            fwrite($fp,$string);
            fclose($fp);
        } catch (Exception $e) {
            echo "<p>Could not write file '$cronlog':<br>".$e->getMessage()."</p>";
        }
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
            if (!empty($cronFile) && !in_array($cronFile,array('.','..','test_assignment.php','run.php','logs'))) {
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
                    'description'=>$thisPlugin::$description
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
                    $optProp = "<input type='text' name='$optName' value='{$settings['value']}' style='width: auto;'/>";
                }
                $opt .= "
                    <div class='formcontrol'>
                        <label for='{$optName}'>{$optName}</label>
                        {$optProp}
                    </div>
                ";
            }
            $content .= "
                <form method='post' action=''>
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
        $path = PATH_TO_APP . '/cronjobs/logs/'. $name . '.txt';
        if (is_file($path)) {
            $logs = '';
            $fh = fopen($path,'r');
            while ($line = fgets($fh)) {
                $logs .= "{$line}<br>";
            }
            fclose($fh);
        } else {
            $logs = null;
        }
        return $logs;
    }

    /**
     *  Delete scheduled task's logs
     * @param string $name: Task's name
     * @return null|string: logs
     */
    public static function deleteLog($name) {
        $path = PATH_TO_APP . '/cronjobs/logs/'. $name . '.txt';
        if (is_file($path)) {
            return unlink($path);
        } else {
            return false;
        }
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

            $runBtn = "<div class='run_cron workBtn runBtn' data-cron='$cronName'></div>";

            $datetime = $info['time'];
            $date = date('Y-m-d', strtotime($datetime));
            $time = date('H:i', strtotime($datetime));

            $frequency = (!empty($info['frequency'])) ? explode(',', $info['frequency']): array(0, 0, 0, 0);

            $cronList .= "
            <div class='plugDiv' id='cron_$cronName'>
                <div class='plugLeft'>
                    <div class='plugName'>$cronName</div>
                    <div class='plugTime' id='cron_time_$cronName'>$datetime</div>
                    <div class='optbar'>
                        <div class='optShow workBtn settingsBtn' data-op='cron' data-name='$cronName'></div>
                        $install_btn
                        $runBtn
                        $activate_btn
                    </div>
                </div>

                <div class='plugSettings'>
                    <div class='description'>
                        {$pluginDescription}
                    </div>
                    <div>
    
                        <div class='settings'>
                            <form method='post' action=''>
                                <div>Date & Time</div>
                                <div class='formcontrol'>
                                    <label>Date</label>
                                    <input type='date' name='date' value='{$date}'/>
                                </div>
                                <div class='formcontrol'>
                                    <label>Time</label>
                                    <input type='time' name='time' value='{$time}' />
                                </div>
                                
                                <div class='frequency_container'>
        
                                    <div>Frequency</div>
                                    <div class='formcontrol'>
                                        <label>Months</label>
                                        <input name='months' type='number' value='{$frequency[0]}'/>
                                    </div>
                                    <div class='formcontrol'>
                                        <label>Days</label>
                                        <input name='days' type='number' value='{$frequency[1]}'/>
                                    </div>
                                    <div class='formcontrol'>
                                        <label>Hours</label>
                                        <input name='hours' type='number' value='{$frequency[2]}'/>
                                    </div>
                                    <div class='formcontrol'>
                                        <label>Minutes</label>
                                        <input name='minutes' type='number' value='{$frequency[3]}'/>
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
                            <input type='submit' class='showLog' value='Show logs' id='{$cronName}' />
                            <input type='submit' class='deleteLog' value='Delete logs' id='{$cronName}' />
                        </div>

                        <div class='plugLog' id='${cronName}' style='display: none'></div>

                    </div>
                    
                </div>
            </div>
            ";
        }

        return $cronList;
    }
}