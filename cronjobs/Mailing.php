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

namespace cronjobs;
 
use includes\Task;

/**
 * Class Mailing
 *
 * Scheduled task that send email notifications (digests) to the users with information about the next sessions and
 * recent news.
 */
class Mailing extends Task {

    public $name='Mailing';
    public $status='Off';
    public $installed=False;
    public $dayName='Monday';
    public $description = "Sends notifications (digests) to JCM members.";

    /**
     * Run scheduled task
     * @return mixed
     */
    public function run() {
        $MailManager = new MailManager();
        $DigestMaker = new DigestMaker();

        // Count number of users
        $users = $MailManager->get_mailinglist("notification");
        $nusers = count($users);
        $sent = 0;
        foreach ($users as $username=>$user) {
            $content = $DigestMaker->makeDigest($user['username']);
            if ($MailManager->send($content, array($user['email']))) {
                $sent += 1;
            }
        }
        return array('status'=>true, 'msg'=> "message sent successfully to {$sent}/{$nusers} users.");
    }
    
}


