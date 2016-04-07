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

    protected $table_data = array(
        "id"=>array('INT NOT NULL AUTO_INCREMENT',false),
        "name"=>array('CHAR(20)',false),
        "time"=>array('DATETIME',false),
        "dayName"=>array('CHAR(15)',false),
        "dayNb"=>array('INT(2)',false),
        "hour"=>array('INT(2)',false),
        "path"=>array('VARCHAR(255)',false),
        "status"=>array('CHAR(3)',false),
        "options"=>array('TEXT',false),
        "primary"=>"id"
    );

    public $daysNames = array('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday','All');
    public $daysNbs;
    public $hours;
    public $name;
    public $time;
    public $dayName;
    public $dayNb;
    public $hour;
    public $path;
    public $status;
    public $installed;
    public $options=array();

    /**
     * Constructor
     * @param AppDb $db
     * @param bool $name
     */
    public function __construct(AppDb $db, $name=False) {
        parent::__construct($db, 'Crons', $this->table_data);
        $this->path = dirname(dirname(__FILE__).'/');
        $this->daysNbs = range(0,31,1);
        $this->hours = range(0,23,1);
        if ($name !== False) {
            $this->get($name);
        }
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
        $content = $this->parsenewdata($class_vars,$post,array('installed','daysNames','daysNbs','hours'));
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
        $content = $this->parsenewdata($class_vars,$post,array('installed','daysNames','daysNbs','hours'));
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
     * @param $dayNb
     * @param $dayName
     * @param $hour
     * @return string
     */
    static function parseTime($dayNb, $dayName, $hour) {
        $today = date('Y-m-d');
        $month = date('m');
        $year = date('Y');
        $day = date('d');
        $todayName = date('l');
        $thisHour = date('H');

        $timestamp = mktime(0,0,0,$month,1,$year);
        $maxday = date("t",$timestamp); // Last day of the current month
        $dayNb = ($dayNb>$maxday) ? $maxday:$dayNb;

        if ($dayNb > 0) {
            // Run scheduled task on a particular date
            $strday = ($dayNb < $day)
                ? date('Y-m-d',strtotime("$year-$month-$dayNb + 1 month"))
                :date('Y-m-d',strtotime("$year-$month-$dayNb"));
        } elseif ($dayName !=='All') {
            // Run scheduled task on a particular day in the week
            if ($dayName == $todayName && $hour > $thisHour) {
                $strday = $today;
            } else {
                $strday = date('Y-m-d',strtotime("next $dayName"));
            }
        } elseif ($dayName == 'All') {
            $strday = ($thisHour < $hour) ? $today : date('Y-m-d',strtotime("$today + 1 day"));
        }
        $strtime = date('H:i:s',strtotime("$hour:00:00"));
        $time = $strday.' '.$strtime;
        return $time;
    }

    /**
     * Update scheduled time
     * @return bool
     */
    function updateTime() {
        $newTime = self::parseTime($this->dayNb,$this->dayName, $this->hour);
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
        $string = "[" . date('Y-m-d H:i:s') . "]: $string.\r\n";

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
                $thisPlugin = $this->instantiate($name);
                if ($thisPlugin->isInstalled()) {
                    $thisPlugin->get();
                }
                $jobs[$name] = array(
                    'installed' => $thisPlugin->isInstalled(),
                    'status' => $thisPlugin->status,
                    'path'=>$thisPlugin->path,
                    'time'=>$thisPlugin->time,
                    'dayName'=>$thisPlugin->dayName,
                    'dayNb'=>$thisPlugin->dayNb,
                    'hour'=>$thisPlugin->hour,
                    'options'=>$thisPlugin->options);
            }
        }
        return $jobs;
    }

    /**
     * Display job's settings
     * @return string
     */
    public function displayOpt() {
        $opt = "<div style='font-weight: 600;'>Options</div>";
        if (!empty($this->options)) {
            foreach ($this->options as $optName => $settings) {
                if (count($settings) > 1) {
                    $optProp = "";
                    foreach ($settings as $prop) {
                        $optProp .= "<option value='$prop'>$prop</option>";
                    }
                    $optProp = "<select name='$optName'>$optProp</select>";
                } else {
                    $optProp = "<input type='text' name='$optName' value='$settings' style='width: auto;'/>";
                }
                $opt .= "
                <div class='formcontrol'>
                    <label for='$optName'>$optName</label>
                    $optProp
                </div>";
            }
            $opt .= "<input type='submit' class='modOpt' data-op='cron' value='Modify'>";
        } else {
            $opt = "No settings are available for this job.";
        }
        return $opt;
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
            if ($installed) {
                $install_btn = "<div class='installDep workBtn uninstallBtn' data-type='cron' data-op='uninstall' data-name='$cronName'></div>";
            } else {
                $install_btn = "<div class='installDep workBtn installBtn' data-type='cron' data-op='install' data-name='$cronName'></div>";
            }

            $runBtn = "<div class='run_cron workBtn runBtn' data-cron='$cronName'></div>";
            $status = $info['status'];
            $time = $info['time'];

            $dayName_list = "";
            foreach ($this->daysNames as $day) {
                if ($day == $info['dayName']) {
                    $dayName_list .= "<option value='$day' selected>$day</option>";
                } else {
                    $dayName_list .= "<option value='$day'>$day</option>";
                }
            }

            $dayNb_list = "";
            foreach ($this->daysNbs as $i) {
                if ($i == $info['dayNb']) {
                    $dayNb_list .= "<option value='$i' selected>$i</option>";
                } else {
                    $dayNb_list .= "<option value='$i'>$i</option>";
                }
            }

            $hours_list = "";
            foreach ($this->hours as $i) {
                if ($i == $info['hour']) {
                    $hours_list .= "<option value='$i' selected>$i:00</option>";
                } else {
                    $hours_list .= "<option value='$i'>$i:00</option>";
                }
            }

            $cronList .= "
            <div class='plugDiv' id='cron_$cronName'>
                <div class='plugLeft'>
                    <div class='plugName'>$cronName</div>
                    <div class='plugTime' id='cron_time_$cronName'>$time</div>
                    <div class='optbar'>
                        <div class='optShow workBtn settingsBtn' data-op='cron' data-name='$cronName'></div>
                        $install_btn
                        $runBtn
                    </div>
                </div>

                <div class='plugSettings'>
                    <div class='optbar'>
                        <div class='formcontrol'>
                            <label>Status</label>
                            <select class='select_opt modSettings' data-op='cron' data-option='status' data-name='$cronName'>
                            <option value='$status' selected>$status</option>
                            <option value='On'>On</option>
                            <option value='Off'>Off</option>
                            </select>
                        </div>
                    </div>

                    <div class='settings'>
                        <div class='formcontrol'>
                            <label>Day</label>
                            <select class='select_opt modSettings' data-name='$cronName' data-op='cron' data-option='dayName'>
                                $dayName_list
                            </select>
                        </div>
                        <div class='formcontrol'>
                            <label>Date</label>
                            <select class='select_opt modSettings' data-name='$cronName' data-op='cron' data-option='dayNb'>
                                $dayNb_list
                            </select>
                        </div>
                        <div class='formcontrol'>
                           <label>Time</label>
                            <select class='select_opt modSettings' data-name='$cronName' data-op='cron' data-option='hour'>
                                $hours_list
                            </select>
                        </div>
                    </div>

                    <div class='plugOpt' id='$cronName'></div>

                </div>
            </div>
            ";
        }

        return $cronList;
    }
}