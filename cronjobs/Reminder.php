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

class Reminder extends AppCron {

    public $name = 'Reminder';
    public $path;
    public $status = 'Off';
    public $installed = False;
    public $time;
    public $dayName;
    public $dayNb;
    public $hour;
    public $options;

    public function __construct(AppDb $db) {
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
        /**
         * Run cron job
         */

        // Declare classes
        global $AppMail;

        // Number of users
        $nusers = count($AppMail->get_mailinglist("reminder"));

        $content = $this->makeMail();
        $body = $AppMail->formatmail($content['body']);
        $subject = $content['subject'];
        if ($AppMail->send_to_mailinglist($subject, $body, "reminder")) {
            $result = "message sent successfully to $nusers users.";
        } else {
            $result = "ERROR message not sent.";
        }

        // Write log
        $this->logger("$this->name.txt",$result);
        return $result;

    }

    /**
     * Make reminder notification email (including only information about the upcoming session)
     * @return mixed
     */
    private function makeMail() {
        $sessions = new Sessions($this->db);
        $next_session = $sessions->getsessions(true);
        $sessioncontent = $sessions->shownextsession();
        $date = $next_session[0];

        $content['body'] = "
            <div style='width: 95%; margin: auto; font-size: 16px;'>
                <p>Hello,<br>
                This is a reminder for the next Journal Club session.</p>
            </div>

            <div style='width: 95%; margin: 10px auto; border: 1px solid #aaaaaa;'>
                <div style='background-color: #CF5151; color: #eeeeee; padding: 5px; text-align: left; font-weight: bold; font-size: 16px;'>
                    Next session
                </div>
                <div style='font-size: 14px; padding: 5px; background-color: rgba(255,255,255,.5);'>
                    $sessioncontent
                </div>
            </div>

            <div style='width: 95%; margin: 10px auto; font-size: 16px;'>
                <p>Cheers,<br>
                The Journal Club Team</p>
            </div>
        ";
        $content['subject'] = "Next session: $date -reminder";
        return $content;
    }
}
