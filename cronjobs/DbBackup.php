<?php
/*
Copyright © 2014, Florian Perdreau
This file is part of Journal Club Manager.

Journal Club Manager is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Journal Club Manager is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with Journal Club Manager.  If not, see <http://www.gnu.org/licenses/>.
*/

require('../includes/boot.php');

class DbBackup extends AppCron {
    /**
     * Assign chairmen for the next n sessions
     * @return bool
     */

    public $name = 'DbBackup';
    public $path;
    public $status = 'Off';
    public $installed = False;
    public $time;
    public $dayName;
    public $dayNb;
    public $hour;
    public $options=array("nb_version"=>10);

    /**
     * Constructor
     * @param AppDb $db
     */
    public function __construct(AppDb $db) {
        parent::__construct($db);
        $this->path = basename(__FILE__);
        $this->time = AppCron::parseTime($this->dayNb, $this->dayName, $this->hour);
    }

    /**
     * Install cron job
     * @return bool|mysqli_result
     */
    public function install() {
        // Register the plugin in the db
        $class_vars = get_class_vars($this->name);
        return $this->make($class_vars);
    }

    /**
     * Run scheduled task: backup the database
     * @return string
     */
    public function run() {
        // Run cron job
        $backupfile = backup_db();
        $filelink = json_encode($backupfile);

        // Write log only if server request
        $result = "Backup successfully done";
        $this->logger("$this->name.txt",$result);
        $this->time = AppCron::parseTime($this->dayNb,$this->dayName, $this->hour);
        return $filelink;
    }
}
