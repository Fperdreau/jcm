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

class Mailing extends AppCron {

    public $name='Mailing';
    public $path;
    public $status='Off';
    public $installed=False;
    public $time;
    public $dayName='Monday';
    public $dayNb=0;
    public $hour=0;
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
        /**
         * Run cron job
         */
        global $AppMail;

        // Count number of users
        $nusers = count($AppMail->get_mailinglist("notification"));

        $content = $this->makeMail();
        $body = $AppMail -> formatmail($content['body']);

        $subject = $content['subject'];
        if ($AppMail->send_to_mailinglist($subject,$body,"notification")) {
            $result = "message sent successfully to $nusers users.";
        } else {
            $result = "ERROR message not sent.";
        }

        $this->logger("$this->name.txt",$result);
        return($result);
    }

    /**
     * Make notification email
     * (weekly digest including last news, information about the upcoming session, about future sessions, and the wish list)
     * @return mixed
     */
    public function makeMail() {
        // Get recent news
        $Posts = new Posts($this->db);
        $sessions = new Sessions($this->db);
        $presentations = new Presentations($this->db);

        $last = $Posts->getlastnews();
        $last_news = new Posts($this->db,$last);
        $today = date('Y-m-d');
        if ( date('Y-m-d',strtotime($last_news->date)) < date('Y-m-d',strtotime("$today - 7 days"))) {
            $last_news->content = "No recent news this week";
        }

        // Get future presentations
        $pres_list = $sessions->showfuturesession(4,'mail');

        // Get wishlist
        $wish_list = $presentations->getwishlist(4,true);

        // Get next session
        $next_session = $sessions->shownextsession(true);

        $content['body'] = "

                <div style='width: 95%; margin: auto; font-size: 16px;'>
                    <p>Hello,</p>
                    <p>This is your Journal Club weekly digest.</p>
                </div>

                <div style='width: 95%; margin: 10px auto; border: 1px solid #aaaaaa;'>
                    <div style='background-color: #CF5151; color: #eeeeee; padding: 5px; text-align: left; font-weight: bold; font-size: 16px;'>
                        Last News
                    </div>

                    <div style='font-size: 14px; padding: 5px; background-color: rgba(255,255,255,.5);'>
                        $last_news->content
                    </div>
                </div>

                <div style='width: 95%; margin: 10px auto; border: 1px solid #aaaaaa;'>
                    <div style='background-color: #CF5151; color: #eeeeee; padding: 5px; text-align: left; font-weight: bold; font-size: 16px;'>
                        Upcoming session
                    </div>
                    <div style='font-size: 14px; padding: 5px; background-color: rgba(255,255,255,.5);'>
                        $next_session
                    </div>
                </div>

                <div style='width: 95%; margin: 10px auto; border: 1px solid #aaaaaa;'>
                    <div style='background-color: #CF5151; color: #eeeeee; padding: 5px; text-align: left; font-weight: bold; font-size: 16px;'>
                        Future sessions
                    </div>

                    <div style='font-size: 14px; padding: 5px; background-color: rgba(255,255,255,.5); display: block;'>
                        $pres_list
                    </div>
                </div>

                <div style='width: 95%; margin: 10px auto; border: 1px solid #aaaaaa;'>
                    <div style='background-color: #CF5151; color: #eeeeee; padding: 5px; text-align: left; font-weight: bold; font-size: 16px;'>
                        Wish list
                    </div>

                    <div style='font-size: 14px; padding: 5px; background-color: rgba(255,255,255,.5); height: auto;'>
                        $wish_list
                    </div>
                </div>

                <div style='width: 95%; margin: auto; font-size: 16px;'>
                    <p>Cheers,<br>
                    The Journal Club Team</p>
                </div>
        ";

        $content['subject'] = "Last News - ".date('d M Y');
        return $content;
    }
}


