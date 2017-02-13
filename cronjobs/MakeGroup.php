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

require_once(PATH_TO_APP . '/includes/boot.php');
require_once(PATH_TO_APP . '/plugins/Groups/Groups.php');

/**
 * Class MakeGroup
 *
 * Scheduled task that creates users groups according to the number of presentations for a particular session
 * (1 group/presentation)
 */
class MakeGroup extends AppCron {

    public $name='MakeGroup';
    public $status='Off';
    public $installed=False;
    public $options = array(
        'send'=>array(
            'options'=>array('Yes'=>1,'No'=>0),
            'value'=>0)
    );
    public static $description = "Creates groups of members for the upcoming session with one group per presentation. 
    This task is calling the Group plugin that must be installed. You can choose to notify users in MakeGroup's settings";

    /**
     * MakeGroup constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->path = basename(__FILE__);
    }
    
    /**
     * Run scheduled task
     * @return array|string
     */
    public function run() {
        $groups = new Groups();
        if (!$groups->installed) {
            return 'You must install the Group plugin first!';
        }

        $result = $groups->run();
        if ($result['status'] && $this->options['send']['value'] == 1) {
            $result .= $this->notify();
        }
        return $result['msg'];
    }

    /**
     * Run scheduled task
     * @return string
     */
    public function notify() {
        $MailManager = new MailManager();
        $Group = new Groups();
        $AppMail = new AppMail();

        // Count number of users
        $users = $AppMail->get_mailinglist("notification");
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

