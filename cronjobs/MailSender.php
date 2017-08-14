<?php
/**
 * File for class MailSender
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

/**
 * Class MailSender
 */
class MailSender extends Task {

    /**
     * @var string: Task name
     */
    public $name = 'MailSender';
    
    /**
     * @var array: task's settings
     */
    public $options = array(
        'nb_version'=>array(
            'options'=>array(),
            'value'=>10)
    );

    /**
     * @var MailManager $Manager
     */
    private static $Manager;

    public $description = "Checks that all emails have been sent and sends them otherwise. It also cleans the
    mailing database by deleting the oldest emails. The number of days of email storage can be defined in the task's 
    settings (default is 10 days).";

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        self::getMailer();
    }

    /**
     * Factory
     * @return MailManager
     */
    private function getMailer() {
        if (is_null(self::$Manager)) {
            self::$Manager = new MailManager();
        }
        return self::$Manager;
    }

    /**
     * Sends emails
     */
    public function run() {

        // Process emails
        $result = $this->process_mails();

        // Clean DB
        $this->clean();

        return array('status'=>true, 'msg'=> $result);
    }

    /**
     * Attempt to send emails that haven't been successfully sent in the past
     * @return string
     */
    public function process_mails() {
        // Get Mail API
        $this->getMailer();

        $sent = 0;
        $to_be_sent = count(self::getMailer()->all(array('status'=>0)));
        foreach (self::getMailer()->all(array('status'=>0)) as $key=>$email) {
            $recipients = explode(',', $email['recipients']);
            $email['body'] = $email['content'];
            if ($email['attachments'] == '') {
                $email['attachments'] = null;
            }
            if (self::getMailer()->send($email, $recipients, true, null, false)) {
                self::getMailer()->update(array('status'=>1), array('mail_id'=>$email['mail_id']));
                $sent += 1;
            }
            sleep(2); // Add some time interval before processing the next email
        }
        $msg = "{$sent}/{$to_be_sent} emails have been sent.";
        Tasks::get_logger()->log($msg);
        return $msg;
    }

    /**
     * Clean email table
     * @param int|null $day
     * @return bool
     */
    public function clean($day=null) {
        $day = (is_null($day)) ? $this->options['nb_version']['value']: $day;
        $date_limit = date('Y-m-d',strtotime("now - $day day"));
        $data = self::getMailer()->all(array('date <='=>$date_limit, 'status'=>1));

        $to_delete = count($data);
        $count = 0;
        foreach ($data as $key=>$email) {
            if (!self::getMailer()->delete(array('mail_id'=>$email['mail_id']))) {
                Tasks::get_logger()->log("Could not delete email '{$email['mail_id']}'");

                return false;
            } else {
                $count += 1;
            }
        }

        $msg = "{$count}/{$to_delete} emails have been deleted.";
        Tasks::get_logger()->log($msg);
        return true;
    }
}