<?php
/**
 * File for class DbBackup
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

 namespace cronjobs;
 
 use includes\Task;
 
/**
 * Class DbBackup
 *
 * Scheduled task that creates backup of the database and store them in backup/mysql.
 */
class DbBackup extends Task {

    public $name = 'DbBackup';
    
    public $status = 'Off';
    
    public $installed = False;
    
    public $options = array(
        'nb_version'=>array(
            'options'=>array(),
            'value'=>10)
    );    
    
    public $description = "Makes backup of the database, saves it into the backup/mysql folder that can be found
    at the root of the JCM application, and sends a copy by email to the admin. It also automatically delete older versions.
    The number of versions to store can be defined in the task's settings";

    /**
     * Run scheduled task: backup the database
     * @return mixed
     */
    public function run() {
        // Run cron job
        $result = Backup::backupDb($this->options['nb_version']['value']);
        if ($result['status']) {
            $result['msg'] = "Backup successfully done: {$result['filename']}";
        }
        return $result;
    }
}
