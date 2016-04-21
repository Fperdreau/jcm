<?php
/**
 * File for class Mailing
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
 * Class Mailing
 *
 * Scheduled task that send email notifications (digests) to the users with information about the next sessions and
 * recent news.
 */
class Mailing extends AppCron {

    public $name='Mailing';
    public $status='Off';
    public $installed=False;
    public $dayName='Monday';
    public static $description = "Sends notifications (digests) to JCM members.";

    public function __construct(AppDb $db) {
        parent::__construct($db);
        $this->path = basename(__FILE__);
        //$this->time = AppCron::parseTime($this->dayNb, $this->dayName, $this->hour);
    }

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
        $users = $AppMail->get_mailinglist("notification");
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
    
}


