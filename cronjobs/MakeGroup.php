<?php
/**
 * Created by PhpStorm.
 * User: Florian
 * Date: 05/04/2015
 * Time: 17:18
 */
require('../includes/boot.php');
require_once('../plugins/Groups/Groups.php');

class MakeGroup extends AppCron {

    public $name='MakeGroup';
    public $path;
    public $status='Off';
    public $installed=False;
    public $time;
    public $dayName;
    public $dayNb;
    public $hour;
    public $options;

    public function __construct(DbSet $db) {
        parent::__construct($db);
        $this->path = basename(__FILE__);
        $this->time = AppCron::parseTime($this->dayNb, $this->dayName, $this->hour);
    }

    public function install() {
        // Register the plugin in the db
        $class_vars = get_class_vars($this->name);
        return $this->make($class_vars);
    }

    public function run() {
        global $db;
        // run cron job
        $groups = new Groups($db);
        $result = $groups->run();
        $this->logger("$this->name.txt",$result);
        return $result;
    }
}

