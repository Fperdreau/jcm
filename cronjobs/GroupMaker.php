<?php
/**
 * File for class MakeGroup
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
 * Class MakeGroup
 *
 * Scheduled task that creates users groups according to the number of presentations for a particular session
 * (1 group/presentation)
 */
class GroupMaker extends Task {

    public $name= 'GroupMaker';
    public $options = array(
        'send'=>array(
            'options'=>array('Yes'=>1,'No'=>0),
            'value'=>0)
    );
    public $description = "Creates groups of members for the upcoming session with one group per presentation. 
    This task is calling the Group plugin that must be installed. You can choose to notify users in GroupMaker's settings";

    /**
     * Run scheduled task
     * @return mixed
     */
    public function run() {
        $Plugins = new Plugins();
        $Plugins->isInstalled('Groups');
        if (!$Plugins->isInstalled('Groups')) {
            return array('status'=>false, 'msg'=>'You must install the Group plugin first!');
        }

        $groups = new Groups();
        $result = $groups->run();
        if ($result['status'] && $this->options['send']['value'] == 1) {
            $result['msg'] .= $this->notify();
        }
        return $result;
    }

    /**
     * Run scheduled task
     * @return string
     */
    public function notify() {
        $MailManager = new MailManager();
        $Group = new Groups();

        // Count number of users
        $users = $MailManager->get_mailinglist("notification");
        $nusers = count($users);
        $sent = 0;
        foreach ($users as $username=>$user) {
            $content = $Group->makeMail($user['username']);
            if ($MailManager->send($content, array($user['email']))) {
                $sent += 1;
            }
        }
        return "Message sent successfully to {$sent}/{$nusers} users.";
    }
}

