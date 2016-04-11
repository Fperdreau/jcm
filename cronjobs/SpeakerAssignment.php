<?php
/**
 * File for class AssignSpeakers
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
require('../includes/boot.php');

/**
 * Class SpeakerAssignment
 * 
 * Scheduled task that automatically assigns speakers for the next presentations. Every assigned user will be notified
 * by email.
 */
class SpeakerAssignment extends AppCron {

    /**
     * Assign Speakers for the next n sessions
     * @return bool
     */

    public $name = 'SpeakerAssignment';
    public $path;
    public $status = 'Off';
    public $installed = False;
    public $time;
    public $dayName;
    public $dayNb;
    public $hour;
    public static $description = "Automatically assigns speakers to the future sessions. Speaker are pseudo-randomly 
    selected, with priority given to members with the least number of presentations given so far.";


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
     * Register the plugin into the database
     * @return bool|mysqli_result
     */
    public function install() {
        $class_vars = get_class_vars($this->name);
        return $this->make($class_vars);
    }
    
    /**
     * Run scheduled task: assign speaker to the next sessions
     * @return bool|string
     */
    public function run() {
        $Assignment = new Assignment($this->db);

        // Assign speakers
        $result = $Assignment->assign();

        return $result['msg'];
    }
    
    /**
     * Make reminder notification email (including only information about the upcoming session)
     * @return mixed
     */
    public function makeMail($user, $info) {
        $sessionType = $info['type'];
        $date = $info['date'];
        $dueDate = date('Y-m-d',strtotime($date.' - 1 week'));
        $AppConfig = new AppConfig($this->db);
        $contactURL = $AppConfig->site_url."index.php?page=contact";
        $content['body'] = "
            <div style='width: 100%; margin: auto;'>
                <p>Hello $user->fullname,</p>
                <p>You have been automatically invited to present at a <span style='font-weight: 500'>$sessionType</span> session on the <span style='font-weight: 500'>$date</span>.</p>
                <p>Please, submit your presentation on the Journal Club Manager before the <span style='font-weight: 500'>$dueDate</span>.</p>
                <p>If you think you will not be able to present on the assigned date, please <a href='$contactURL'>contact</a> on the organizers as soon as possible.</p>
            </div>
        ";
        $content['subject'] = "Invitation to present on the $date";
        return $content;
    }

    /**
     * @param $user
     * @param $info
     * @return mixed
     */
    public function sessionUpdatedN($user, $info) {
        $sessionType = $info['type'];
        $date = $info['date'];
        $dueDate = date('Y-m-d',strtotime($date.' - 1 week'));
        $AppConfig = new AppConfig($this->db);
        $contactURL = $AppConfig->site_url."index.php?page=contact";
        $content['body'] = "
            <div style='width: 100%; margin: auto;'>
                <p>Hello $user->fullname,</p>
                <p>You have been automatically invited to present at a <span style='font-weight: 500'>$sessionType</span> session on the <span style='font-weight: 500'>$date</span>.</p>
                <p>Please, submit your presentation on the Journal Club Manager before the <span style='font-weight: 500'>$dueDate</span>.</p>
                <p>If you think you will not be able to present on the assigned date, please <a href='$contactURL'>contact</a> on the organizers as soon as possible.</p>
            </div>
        ";
        $content['subject'] = "Invitation to present on the $date";
        return $content;
    }
}