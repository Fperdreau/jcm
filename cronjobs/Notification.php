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
    private function makeMail($presentationList) {
        // Get latest submitted presentation
            $list = "";
            foreach ($presentationList as $presid) {
                $pres = new Presentation($this->db, $presid);
                $list .= $pres->showDetails(true);
            }
            $date = date('Y-m-d');
            $content['body'] = "
            <div style='width: 95%; margin: auto;'>
                <p>Hello,<br>
                New presentations have been recently submitted!</p>
            </div>

            <div style='width: 95%; margin: 10px auto;'>
                <div style='color: rgba(207,81,81,.8); border-bottom: 1px solid rgba(207,81,81,.8); padding: 5px; text-align: left; font-weight: bold; font-size: 15px;'>
                    New submissions
                </div>
                <div style='font-size: 14px; background-color: rgba(255,255,255,.5);'>
                    $list
                </div>
            </div>

            <div style='width: 95%; margin: 10px auto;'>
                <p>Cheers,<br>
                The Journal Club Team</p>
            </div>
        ";
        $content['subject'] = "Last submissions: $date";
        return $content;
    }
}
