<?php
/**
 * File for class Groups
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

namespace Plugins;

use includes\DigestMaker;
use includes\ReminderMaker;
use includes\Session;
use includes\Presentation;
use includes\Users;
use includes\Plugin;

/**
 * Class Groups
 *
 * Plugin that assign users to different groups according to the number of presentations in a session. Display the
 * user's group on his/her profile page
 */
class Groups extends Plugin
{

    protected $schema = array(
        "id"=>array("INT NOT NULL AUTO_INCREMENT", false),
        "groups"=>array("INT(2)", false),
        "username"=>array("CHAR(15)", false),
        "role"=>array("CHAR(10)", false),
        "presid"=>array("BIGINT(15)", false),
        "date"=>array("DATE", false),
        "room"=>array("CHAR(10)", false),
        "primary"=>'id'
    );

    public $name = "Groups";
    public $version = "1.1.0";
    public $description = "Automatically creates groups of users based on the number of presentations scheduled 
    for the upcoming session. Users will be notified by email about their group's information. If the different groups
    are meeting in different rooms, then the rooms can be specified in the plugin's settings 
    (rooms must be comma-separated).";

    public $page = 'profile';
    public $options = array(
        'room'=>array(
            'options'=>array(),
            'value'=>'')
    );

    /**
     * @var Session $session
     */
    private static $session;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->registerDigest();
        $this->registerReminder();
    }

    /**
     * Register into DigestMaker table
     */
    private function registerDigest()
    {
        $DigestMaker = new DigestMaker();
        $DigestMaker->register($this->name);
    }

    /**
     * Register into Reminder table
     */
    private function registerReminder()
    {
        $reminder = new ReminderMaker();
        $reminder->register($this->name);
    }

    /**
     * Session instance factory
     * @return Session
     */
    private static function getSession()
    {
        if (is_null(self::$session)) {
            self::$session = new Session();
        }
        return self::$session;
    }


    /**
     * Run scheduled task: Assign users to groups and send them an email with their assigned group and presentation
     * @return array|string
     */
    public function run()
    {
        $next_session = $this->get_next_session();

        // 1: Check if group has not been made yet for the next session
        if ($next_session !== false && $next_session[0]['type'] !== 'none'
        && !$this->group_exist($next_session[0]['date'])) {
            // 2: Clear the group table
            $this->clearTable();

            // 3: Assign groups
            $result = $this->makegroups($next_session); // Make groups

        } else {
            $result['status'] = false;
            $result['msg'] = 'Either there is no session plan on the next journal club day, or groups have already been 
            made.';
        }
        return $result;
    }

    /**
     * Check if group has been already made for the next session
     * @param $session_date: next session date
     * @return bool
     */
    private function group_exist($session_date)
    {
        $sql = "SELECT date FROM {$this->tablename}";
        $req = $this->db->send_query($sql);
        while ($row = mysqli_fetch_assoc($req)) {
            if ($row['date'] == $session_date) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get information about next session
     * @return array
     */
    private function get_next_session()
    {
        $nextSession = self::getSession()->getUpcoming(1);
        if ($nextSession !== false) {
            reset($nextSession);
            return $nextSession[key($nextSession)];
        } else {
            return array();
        }
    }

    /**
     * Clear the group table
     * @return bool|mysqli_result
     */
    private function clearTable()
    {
        return $this->db->clearTable($this->tablename);
    }

    /**
     * Randomly assigns groups to users for the next session
     * @param array $session: next session
     * @return array|bool
     */
    public function makegroups(array $session)
    {

        $rooms = explode(',', $this->options['room']['value']);

        // Do not make group if there is no session planned on the next journal club day
        if ($session[0]['type'] == 'none') {
            return false;
        }

        // Set the number of groups equal to the number of presentation for this day in case it exceeds it.
        $ngroups = max(self::getSession()->getSettings('max_nb_session'), count($session));

        // Get users list
        $Users = new Users();
        $users = array();
        foreach ($Users->all() as $key => $user) {
            $users[] = $user['username'];
        }

        $nusers = count($users); // total nb of users

        if (($nusers-$ngroups) < $ngroups || $session[0]['type'] == "none") {
            $result['status'] = false;
            $result['msg'] = "Not enough members to create groups";
            return $result;
        }

        $excludedusers = array();
        $pregroups = array();
        for ($i=0; $i<$ngroups; $i++) {
            $speaker = (isset($session->speakers[$i])) ? $session[$i]['speakers'] : 'TBA';
            $pregroups[$i][] = array("member"=>$speaker,"role"=>"speaker");
            $excludedusers[] = $speaker;
        }
        $remainusers = array_values(array_diff($users, $excludedusers));

        // Shuffle the remaining users
        shuffle($remainusers);

        // Make groups
        $qtity = ceil(count($remainusers)/$ngroups); // nb of users per group
        $groups = array_chunk($remainusers, $qtity);

        // Assign presentation
        $assigned_groups = array();
        for ($i=0; $i<$ngroups; $i++) {
            $room = (!empty($rooms[$i])) ? $rooms[$i] : 'TBA';
            $presid = (isset($session->presids[$i])) ? $session[$i]['presids'] : 'TBA';
            $group = $pregroups[$i];
            foreach ($groups[$i] as $mbr) {
                $group[] = array("member"=>$mbr,"role"=>false);
            }
            $assigned_groups[$i] = array('presid'=>$presid,'group'=>$group);

            // Add to the table
            foreach ($group as $mbr) {
                if (!$this->db->insert($this->tablename, array(
                    'groups' => $i,
                    'username' => $mbr['member'],
                    'role' => $mbr['role'],
                    'presid' => $presid,
                    'date' => $session[0]['date'],
                    'room' => $room
                ))) {
                    return false;
                };
            }
        }

        $result['status'] = true;
        $result['msg'] = "{$ngroups} groups have been created.";
        return $result;
    }

    /**
     *
     * @param null $username
     * @return mixed
     */
    public function makeMail($username = null)
    {
        $data = $this->getGroup($username);
        if ($data !== false) {
            $data['group'] = $this->showList($username);
            $publication = new Presentation($data['presid']);
            $data['publication'] = $publication->mail_details(true);
            $content['body'] = self::renderSection($data);
            $content['title'] = 'Your Group assignment';
            $content['subject'] = "Your Group assignment: {$data['date']}";
            return $content;
        } else {
            return array();
        }
    }

    /**
     *
     * @param null $username
     * @return array
     */
    public function makeReminder($username = null)
    {
        $data = $this->getGroup($username);
        if ($data !== false) {
            $data['group'] = $this->showList($username);
            $publication = new Presentation($data['presid']);
            $data['publication'] = $publication->mail_details(true);
            $content['body'] = self::renderSection($data);
            $content['title'] = 'Your Group assignment';
            $content['subject'] = "Your Group assignment: {$data['date']}";
            return $content;
        } else {
            return array();
        }
    }
    
    /**
     * Renders group information to be displayed in emails
     * @param array $data
     * @return string
     */
    public static function renderSection(array $data)
    {
        return "
        <p>Here is your group assignment for the session held on <b>{$data['date']}</b>.</p>
        <p>Your group will meet in room {$data['room']}.</p>
        <div style='display: inline-block; padding: 10px; margin: 0 auto 20px auto; 
        background-color: rgba(255,255,255,1); width: 45%; min-width: 250px; vertical-align: top;'>
            {$data['group']}
        </div>
        <div style='display: inline-block; padding: 10px; margin: 0 auto 20px auto; 
        background-color: rgba(255,255,255,1); width: 45%; min-width: 250px; vertical-align: top;'>
            <div style='color: #444444; margin-bottom: 10px;  border-bottom:1px solid #DDD; 
            font-weight: 500; font-size: 1.2em;'>
                Your group presentation
            </div>
            <div style='min-height: 50px; padding-bottom: 5px; margin: auto auto 0 auto;'>
                {$data['publication']}
            </div>
        </div>
        ";
    }

    /**
     * Renders Email notification
     * @param Users $user
     * @param array $data
     * @return mixed
     */
    public static function renderMail($data, Users $user)
    {
        $result['body'] = "
            <div style='width: 100%; margin: auto;'>
                <p>Hello <span style='font-weight: 600;'>{$user->firstname}/span>,</p>
                <p>Here is your assignment for our next journal club session that will be held on the
                {$data['date']} in room <b> {$data['room']}</b>.</p>
    
                <div style='display: block; vertical-align: top; margin: auto;'>
                    <div style='display: inline-block; padding: 10px; 
                    margin: 0 30px 20px 0;background-color: rgba(255,255,255,1);'>
                        " . $data['group'] . "
                    </div>
                    <div style='display: inline-block; padding: 10px; margin: auto; vertical-align: top; 
                    max-width: 60%; background-color: rgba(255,255,255,1);'>
                         " . $data['publication'] . "
                    </div>
                </div>
            </div>
            ";
        $result['subject'] = "Your group assignment - {$data['date']}";
        return $result;
    }

    /**
     * Get user's group
     * @param $username
     * @return bool|array
     */
    public function getGroup($username)
    {
        $data = $this->get(array('username'=>$username));

        $groupusrs['members'] = array();
        $groupusrs['room'] = $data['room'];
        $groupusrs['date'] = $data['date'];
        $groupusrs['presid'] = $data['presid'];

        if (!empty($data)) {
            foreach ($this->get(array('groups'=>$data['group'])) as $key => $row) {
                $groupusrs['members'][$row['username']] = $row;
            }
            return $groupusrs;
        } else {
            return false;
        }
    }

    /**
     * Display user's group (profile page or in email)
     * @param bool $username
     * @return string
     */
    public function showList($username = false)
    {
        if ($username === false) {
            $username = $_SESSION['username'];
        }
        $group = $this->getGroup($username);
        if (empty($group['members'])) {
            return 'No group has been made yet';
        } else {
            $u = 0;
            $content = "";
            foreach ($group['members'] as $grpmember => $info) {
                if ($grpmember == 'TBA') {
                    continue; // We do not send emails to fake users
                }
                $role = $info['role'];
                $grpuser = new Users($grpmember);
                $fullname = ucfirst(strtolower($grpuser->firstname))." ".ucfirst(strtolower($grpuser->lastname));
                $color = ($u % 2 == 0) ? 'rgba(220,220,220,.7)':'rgba(220,220,220,.2)';
                if ($grpuser->username == $username) {
                    $content .= "<div style='display: block; text-align: left; padding: 5px; background-color: $color;'>
                    <div style='display: inline-block; width: 70%; color: #CF5151;'>YOU</div>
                    <div style='display: inline-block; font-weight: 600;'>$role</div></div>";
                } else {
                    $content .= "<div style='display: block; text-align: left; padding: 5px; background-color: $color;'>
                    <div style='display: inline-block; width: 70%;'>$fullname</div>
                    <div style='display: inline-block; font-weight: 600;'>$role</div></div>";
                }
                $u++;
            }
        }
        return "
                <div style='color: #444444; margin-bottom: 10px;  border-bottom:1px solid #DDD; 
                font-weight: 500; font-size: 1.2em;'>
                    Group members
                </div>
                <div style='min-height: 50px; padding-bottom: 5px; margin: auto auto 0 auto;'>
                    {$content}
                </div>
            ";
    }

    /**
     * Display user's group (profile page or in email)
     * @return string
     */
    public function show()
    {
        $username = $_SESSION['username'];
        $data = $this->getGroup($username);
        $content = $this->showList($username);
        
        if (!empty($data['members'])) {
            $ids = array();
            foreach ($data['members'] as $grpmember => $info) {
                if ($grpmember == 'TBA') {
                    continue; // We do not send emails to fake users
                }
                $member = new Users($grpmember);
                $ids[] = $member->id;
            }
            $ids = implode(',', $ids);
            $groupContact = "
                <div class='div_button'><a href='" . URL_TO_APP . 'index.php?page=member/email&recipients_list=' . $ids . "'>Contact my group</a></div>";
            $groupContent = "
                <p>Here is your group assignment for the session held on {$data['date']} in room {$data['room']}.</p>
                <div>{$content}</div>
            ";
        } else {
            $groupContact = null;
            $groupContent = "You have not been assigned to any group yet.";
        }
        
        return "
                <h2>My group</h2>
                <div class='section_content'>
                {$groupContact}
                {$groupContent}
                </div>
            ";
    }
}
