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

/**
 * Class Notification
 *
 * Scheduled tasks that send a notification email including the last submissions
 */
class Notification extends Task {

    public $name = 'Notification';
    public $status = 'Off';
    public $installed = False;
    public $description = "Sends a email notification to members with the list of the last submitted presentations";

    /**
     * Run scheduled task: send an email with the last submissions
     * @return mixed
     */
    public function run() {
        $MailManager = new MailManager();

        // Number of users
        $mailing_list = $MailManager->get_mailinglist("notification");
        $nusers = count($mailing_list);

        // Get presentation list
        $presentation = new Presentation();
        $presentationList = $presentation->getLatest();

        if (!empty($presentationList)) {
            $result = false;
            foreach ($mailing_list as $fullname=>$data) {
                $content = $this->makeMail($presentationList, $fullname);

                if ($result = $MailManager->send($content, array($data['email']))) {

                    // Tell to the db that notifications have been sent about the new presentations
                    foreach ($presentationList as $presid) {
                        $pres = new Presentation();
                        $pres->update(array('notified'=>1), array('id_pres'=>$presid));
                    }

                } else {
                    $result = false;
                }
            }

            return array('status'=>$result, 'msg'=>$result ? "message sent successfully to $nusers users." : "ERROR message not sent.");
        } else {
            return array('status'=>true, 'msg'=>"No new presentations");
        }
    }

    /**
     * Make reminder notification email (including only information about the upcoming session)
     * @param $presentationList
     * @param string $fullname
     * @return mixed
     */
    public function makeMail($presentationList, $fullname) {
        // Get latest submitted presentation
        $nbpres = count($presentationList);
        $list = "";
        foreach ($presentationList as $presid) {
            $pres = new Presentation($presid);
            $list .= $pres->mail_details(true);
        }
        $content['body'] = "
        <div style='width: 100%; margin: auto;'>
            <p>Hello $fullname,</p>
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
