<?php
/**
 * Created by PhpStorm.
 * User: Florian
 * Date: 30/03/2015
 * Time: 18:33
 */

class AppCron extends Table {

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
    public $options;


    public function __construct(DbSet $db, $name=False) {
        parent::__construct($db, 'Crons', $this->table_data);
        $this->path = dirname(dirname(__FILE__).'/');
        $this->daysNbs = range(0,31,1);
        $this->hours = range(0,23,1);
        if ($name !== False) {
            $this->get($name);
        }
    }

    public function make($post=array()) {
        $class_vars = get_class_vars('AppCron');
        $content = $this->parsenewdata($class_vars,$post,array('installed','daysNames','daysNbs','hours'));
        return $this->db->addcontent($this->tablename,$content);
    }

    public function get() {
        $sql = "SELECT * FROM $this->tablename WHERE name='$this->name'";
        $req = $this->db->send_query($sql);
        $data = mysqli_fetch_assoc($req);
        foreach ($data as $prop=>$value) {
            $this->$prop = $value;
        }
    }

    public function update($post=array()) {
        $class_vars = get_class_vars('AppCron');
        $content = $this->parsenewdata($class_vars,$post,array('installed','daysNames','daysNbs','hours'));
        return $this->db->updatecontent($this->tablename,$content,array("name"=>$this->name));
    }

    public function delete() {
        $this->db->deletecontent($this->db->tablesname['Crons'],array('name'),array($this->name));
        return $this->db->deletetable($this->tablename);
    }

    public function isInstalled() {
        /**
         * Check if this plugin is registered to the db
         */
        $plugins = $this->db->getinfo($this->db->tablesname['Crons'],'name');
        return in_array($this->name,$plugins);
    }

    public function instantiateCron($pluginName) {
        /**
         * Instantiate a class from class name
         * @param: class name (must be the same as the file name)
         * @return: object
         */
        $folder = PATH_TO_APP.'/cronjobs/';
        include_once($folder . $pluginName .'.php');
        return new $pluginName($this->db);
    }

    static function parseTime($dayNb, $dayName, $hour) {
        $today = date('Y-m-d');
        $month = date('m');
        $year = date('Y');
        $day = date('d');
        $now = date('H:i:s');

        $timestamp = mktime(0,0,0,$month,1,$year);
        $maxday = date("t",$timestamp); // Last day of the current month
        $dayNb = ($dayNb>$maxday) ? $maxday:$dayNb;
        if ($dayNb > 0) {
            $strday = ($dayNb<$day)
                ? date('Y-m-d',strtotime("$year-$month-$dayNb + 1 month"))
                :date('Y-m-d',strtotime("$year-$month-$dayNb"));
        } elseif ($dayName !=='All') {
            $strday = date('Y-m-d',strtotime("next $dayName"));
        } elseif ($dayName == 'All') {
            $strday = ($hour < $now) ? date('Y-m-d',strtotime("$today + 1 day")) : $today;
        }
        $strtime = date('H:i:s',strtotime("$hour:00:00"));
        $time = $strday.' '.$strtime;
        return $time;
    }

    static function logger($file, $string) {
        // Write log
        $cronlog = PATH_TO_APP."/cronjobs/logs/$file";
        if (!is_file($cronlog)) {
            $fp = fopen($cronlog,"w");
        } else {
            $fp = fopen($cronlog,"a+");
        }
        $string = "[" . date('Y-m-d H:i:s') . "]: $string.\r\n";

        fwrite($fp,$string);
        fclose($fp);
    }

    public function getRunningJobs() {
        $now = strtotime(date('Y-m-d H:i:s'));
        $jobs = $this->getJobs();
        $runnningjobs = array();
        foreach ($jobs as $thisJob=>$info) {
            $jobTime = strtotime($info['time']);
            if ($info['installed'] && $info['status'] == 'On' && $jobTime >= $now && $jobTime<=($now+(59*60))) {
                $runnningjobs[] = $thisJob;
            }
        }
        return $runnningjobs;
    }

    /**
     * Get list of plugins, their settings and status
     * @return array
     * @internal param bool $page
     */
    public function getJobs() {
        $folder = PATH_TO_APP.'/cronjobs/';
        $cronList = scandir($folder);
        $jobs = array();
        foreach ($cronList as $cronFile) {
            if (!empty($cronFile) && !in_array($cronFile,array('.','..','run.php','logs'))) {
                $name = explode('.',$cronFile);
                $name = $name[0];
                $thisPlugin = $this->instantiateCron($name);
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
                    'hour'=>$thisPlugin->hour);
            }
        }
        return $jobs;
    }

}