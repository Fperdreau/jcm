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

namespace Tasks;
 
use includes\Task;
use includes\MailManager;
use includes\ReminderMaker;
use includes\Session;

/**
 * Class Reminder
 *
 * Scheduled tasks that send reminders to the users regarding the upcomming session
 */
class Reminder extends Task
{

    public $name = 'Reminder';
    public $status = 'Off';
    public $installed = false;
    public $description = "Sends a reminder regarding the upcoming session to members who agreed upon receiving 
    email notifications and reminders (which can be set on their profile page).";

    public $options = array(
        'when'=>array(
            'options'=>array(),
            'value'=>10)
    );

    /**
     * Run scheduled task
     * @return mixed
     */
    public function run()
    {
        $Session = new Session();
        $data = $Session->getUpcoming(1);
        if (!empty($data)) {
            reset($data);
            $id = key($data);
            $now = strtotime(date('Y-m-d H:i:s'));
            $dueTime = $data[$id]['date'] . ' ' . $data[$id]['start_time'];
            if ($now >= $dueTime) {
                return self::sendDigest();
            }
        }
        return array('status'=>false, 'msg'=>'Nothing to send');
    }

    /**
     * Send digest email to users
     * @return array
     */
    private static function sendDigest()
    {
        $MailManager = new MailManager();
        $ReminderMaker = new ReminderMaker();

        // Count number of users
        $users = $MailManager->getMailingList("reminder");
        $nusers = count($users);
        $sent = 0;
        foreach ($users as $username => $user) {
            $content = $ReminderMaker->makeMail($user['username']);
            $content['emails'] = $user['id'];
            if ($MailManager->addToQueue($content)) {
                $sent += 1;
            }
        }
        return array('status'=>true, 'msg'=>"message sent successfully to {$sent}/{$nusers} users.");
    }

    /**
     * Check if a reminder is needed
     * @param callable $callback
     * @return mixed
     */
    private function check($callback)
    {
        $Session = new Session();
        $data = $Session->getNext(1);
        if (!empty($data)) {
            $now = strtotime(date('Y-m-d H:i:s'));
            $dueTime = $data[0]['date'] . ' ' . $data[0]['start_time'];
            if ($now >= $dueTime) {
                return $callback();
            }
        }
        return array('status'=>false, 'msg'=>'Nothing to send');
    }

    /**
     * Make reminder notification email (including only information about the upcoming session)
     * @param $fullname
     * @return mixed
     */
    public function makeMail($fullname)
    {
        $session = new Session();
        $date = $session->getNextDates(1)[0];
        $sessioncontent = $session->showNextSession();

        $content['body'] = "
            <div style='width: 100%; margin: auto;'>
                <p>Hello {$fullname},</p>
                <p>This is a reminder for the next Journal Club session.</p>
            </div>

            <div style='display: block; padding: 10px; margin: 0 30px 20px 0; border: 1px solid #ddd; 
            background-color: rgba(255,255,255,1);'>
                <div style='color: #444444; margin-bottom: 10px;  border-bottom:1px solid #DDD; 
                font-weight: 500; font-size: 1.2em;'>
                    Session Information
                </div>
                <div style='padding: 5px; background-color: rgba(255,255,255,.5); display: block;'>
                    {$sessioncontent}
                </div>
            </div>
        ";
        $content['subject'] = "[Reminder] Next session on the {$date}";
        return $content;
    }
}
