<?php
/**
 * File for class SpeakerAssignment
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
    public $status = 'Off';
    public $installed = False;
    public static $description = "Automatically assigns speakers to the future sessions. Speaker are pseudo-randomly 
    selected with priority given to members with the least number of presentations given so far. This task also sends 
    reminders to assigned speakers to remind them about their upcoming presentation. You can choose how many days before
     the session the reminder should be sent.";
    public $options = array(
        'Days'=>array(
            'options'=>array(),
            'value'=>7)
    );

    /**
     * Constructor
     * @param AppDb $db
     */
    public function __construct(AppDb $db) {
        parent::__construct($db);
        $this->path = basename(__FILE__);
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
     * @return string
     */
    public function run() {
        $Assignment = new autoAssignment($this->db);
        if (!$Assignment->installed) {
            return 'You must install the Assignment plugin first!';
        }
        // Assign speakers
        $result = $Assignment->assign();
        $log = $result['msg'];
        $result = $this->send_reminders();
        $log .= $result['msg'];

        return $log;
    }

    /**
     * Send reminders to users about their upcoming presentation
     * @return mixed
     */
    public function send_reminders() {
        $result['status'] = true;
        $result['msg'] = null;

        $Assignment = new Session($this->db);
        $MailManager = new MailManager($this->db);

        // Get future sessions dates
        $jc_days = $Assignment->getjcdates(1);
        $session = new Session($this->db, $jc_days[0]);
        $today = date('Y-m-d');
        $dueDate = date('Y-m-d',strtotime($jc_days[0] . " - {$this->options['Days']['value']} day"));
        var_dump($dueDate);
        if ($today == $dueDate) {
            $n = 0;
            foreach ($session->presids as $presid) {
                $Presentation = new Presentation($this->db, $presid);
                $speaker = new User($this->db, $Presentation->username);
                $content = $this->reminderEmail($speaker, array('date'=>$session->date, 'type'=>$session->type));
                if ($MailManager->send($content, array($speaker->email))) {
                    $result['status'] = true;
                    $n++;
                } else {
                    $result['status'] = false;
                    $result['msg'] = "Could not sent email to {$speaker->email}.";
                }
            }
            $result['msg'] = "Reminder sent to {$n}";
        }
        return $result;
    }

    /**
     * Make reminder notification email (including only information about the upcoming session)
     * @param User $user
     * @param array $info
     * @return mixed
     */
    public function makeMail(User $user, array $info) {
        $sessionType = $info['type'];
        $date = $info['date'];
        $dueDate = date('Y-m-d',strtotime($date.' - 1 week'));
        $AppConfig = new AppConfig($this->db);
        $contactURL = $AppConfig::$site_url."index.php?page=contact";
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

    /** Notify user about session update
     * @param User $user
     * @param array $info
     * @return mixed
     */
    public function sessionUpdatedN(User $user, array $info) {
        $sessionType = $info['type'];
        $date = $info['date'];
        $dueDate = date('Y-m-d',strtotime($date.' - 1 week'));
        $AppConfig = new AppConfig($this->db);
        $contactURL = $AppConfig::$site_url."index.php?page=contact";
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
     * Reminder email sent to user about his/her upcoming presentation
     * @param User $user
     * @param array $info
     * @return mixed
     */
    public function reminderEmail(User $user, array $info) {
        $sessionType = $info['type'];
        $date = $info['date'];
        $AppConfig = new AppConfig($this->db);
        $contactURL = $AppConfig::$site_url."index.php?page=contact";
        $content['body'] = "
            <div style='width: 100%; margin: auto;'>
                <p>Hello $user->fullname,</p>
                <p>This is to remind you that you have a presentation (<span style='font-weight: 500'>$sessionType</span>) planned on the a <span style='font-weight: 500'>$date</span>.</p>
                <p>Please, submit your presentation on the Journal Club Manager if you have not already.</p>
                <p>If you think you will not be able to present on the assigned date, please <a href='$contactURL'>contact</a> on the organizers as soon as possible.</p>
            </div>
        ";
        $content['subject'] = "Reminder: invitation to present on the {$date}";
        return $content;
    }
}