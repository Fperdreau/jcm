<?php
/**
 * File for class Reminder
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
 * Class Reminder
 *
 * Scheduled tasks that send reminders to the users regarding the upcomming session
 */
class Reminder extends AppCron {

    public $name = 'Reminder';
    public $status = 'Off';
    public $installed = False;
    public static $description = "Sends a reminder regarding the upcoming session to members who agreed upon receiving 
    email notifications and reminders (which can be set on their profile page).";

    /**
     * Reminder constructor.
     * @param AppDb $db
     */
    public function __construct(AppDb $db) {
        parent::__construct($db);
        $this->path = basename(__FILE__);
        $this->time = AppCron::parseTime($this->dayNb, $this->dayName, $this->hour);
    }

    /**
     * @return bool|mysqli_result
     */
    public function install() {
        // Register the plugin in the db
        $class_vars = get_class_vars($this->name);
        return $this->make($class_vars);
    }

    /**
     * Run scheduled task
     * @return string
     */
    public function run() {
        global $AppMail;
        $MailManager = new MailManager($this->db);
        $DigestMaker = new DigestMaker($this->db);

        // Count number of users
        $users = $AppMail->get_mailinglist("reminder");
        $nusers = count($users);
        $sent = 0;
        foreach ($users as $username=>$user) {
            $content = $DigestMaker->makeDigest($user['username']);
            if ($MailManager->send($content, array($user['email']))) {
                $sent += 1;
            }
        }
        return "message sent successfully to {$sent}/{$nusers} users.";
    }

    /**
     * Make reminder notification email (including only information about the upcoming session)
     * @return mixed
     */
    public function makeMail($fullname) {
        $sessions = new Sessions($this->db);
        $dates = $sessions->getsessions();
        $session = new Session($this->db,$dates[0]);
        $sessioncontent = $session->showsessiondetails();
        $date = $dates[0];

        $content['body'] = "
            <div style='width: 100%; margin: auto;'>
                <p>Hello {$fullname},</p>
                <p>This is a reminder for the next Journal Club session.</p>
            </div>

            <div style='display: block; padding: 10px; margin: 0 30px 20px 0; border: 1px solid #ddd; background-color: rgba(255,255,255,1);'>
                <div style='color: #444444; margin-bottom: 10px;  border-bottom:1px solid #DDD; font-weight: 500; font-size: 1.2em;'>
                    Next session
                </div>
                <div style='padding: 5px; background-color: rgba(255,255,255,.5); display: block;'>
                    $sessioncontent
                </div>
            </div>


        ";
        $content['subject'] = "Next session: $date - reminder";
        return $content;
    }
}
