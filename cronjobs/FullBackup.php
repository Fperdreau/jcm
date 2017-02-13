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

require_once(PATH_TO_APP . '/includes/boot.php');

/**
 * Class FullBackup
 *
 * Scheduled task that creates full backups of the web-site (files & database) and store the corresponding archives
 * in backup/complete.
 */
class FullBackup extends AppCron {
    /**
     * Assign chairmen for the next n sessions
     * @return bool
     */

    public $name = 'FullBackup';
    public $status = 'Off';
    public $installed = False;
    public $options = array(
        'nb_version'=>array(
            'options'=>array(),
            'value'=>10)
    );
    public static $description = "Makes a backup of the whole application (files and database), saves it into the 
    backup/complete folder and automatically cleans older backups. The number of versions that has to be stored can be 
    defined in the task's settings";

    public function __construct() {
        parent::__construct();
        $this->path = basename(__FILE__);
        //$this->time = AppCron::parseTime($this->dayNb, $this->dayName, $this->hour);
    }

    public function install() {
        // Register the plugin in the db
        $class_vars = get_class_vars($this->name);
        return $this->make($class_vars);
    }

    public function run() {
        // db backup
        $backupFile = backupDb($this->options['nb_version']['value']); // backup database
        mail_backup($backupFile); // Send backup file to admins

        // file backup
        $zipFile = backupFiles(); // Backup site files (archive)
        $fileLink = json_encode($zipFile);

        // Write log only if server request
        $result = "Full Backup successfully done: $fileLink";
        return $result;
    }
}