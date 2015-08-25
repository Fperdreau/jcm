<?php
/**
 * File for class Notification
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
 * Class Notification
 *
 * Scheduled tasks that send a notification email including the last submissions
 */
class Notification extends AppCron {

    public $name = 'Notification';
    public $path;
    public $status = 'Off';
    public $installed = False;
    public $time;
    public $dayName;
    public $dayNb;
    public $hour;
    public $options;

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
     * Register cron job to the db
     * @return bool|mysqli_result
     */
    public function install() {
        $class_vars = get_class_vars($this->name);
        return $this->make($class_vars);
    }

    /**
     * Run scheduled task: send an email with the last submissions
     * @return string
     */
    public function run() {
        // Declare classes
        global $AppMail;

        // Number of users
        $nusers = count($AppMail->get_mailinglist("reminder"));

        // Get presentation list
        $presentation = new Presentations($this->db);
        $presentationList = $presentation->getLatest();

        if (!empty($presentationList)) {
            $content = $this->makeMail($presentationList);
            $body = $AppMail->formatmail($content['body']);
            $subject = $content['subject'];
            if ($AppMail->send_to_mailinglist($subject, $body, "reminder")) {
                $result = "message sent successfully to $nusers users.";

                // Tell to the db that notifications have been sent about the new presentations
                foreach ($presentationList as $presid) {
                    $pres = new Presentation($this->db, $presid);
                    $pres->notified = 1;
                    $pres->update();
                }
            } else {
                $result = "ERROR message not sent.";
            }

            // Write log
            $this->logger("$this->name.txt", $result);
            return $result;
        } else {
            return "No new presentations";
        }
    }

    /**
     * Make reminder notification email (including only information about the upcoming session)
     * @param $presentationList
     * @return mixed
     */
    public function makeMail($presentationList) {
        // Get latest submitted presentation
        $nbpres = count($presentationList);
        $list = "";
        foreach ($presentationList as $presid) {
            $pres = new Presentation($this->db, $presid);
            $list .= $pres->showDetails(true);
        }
        $content['body'] = "
        <div style='width: 100%; margin: auto;'>
            <p>Hello,</p>
            <p>New presentations have been recently submitted!</p>
        </div>

        <div style='display: block; padding: 10px; margin: 0 30px 20px 0; border: 1px solid #ddd; background-color: rgba(255,255,255,1);'>
            <div style='color: #444444; margin-bottom: 10px;  border-bottom:1px solid #DDD; font-weight: 500; font-size: 1.2em;'>
                New submissions
            </div>
            <div style='padding: 5px; background-color: rgba(255,255,255,.5); display: block;'>
                $list
            </div>
        </div>
        ";
        $content['subject'] = "$nbpres new presentations have been submitted!";
        return $content;
    }
}
