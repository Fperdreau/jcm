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
        "groupId"=>array("INT(2)", false),
        "username"=>array("CHAR(15)", false),
        "role"=>array("CHAR(10)", false),
        "presid"=>array("BIGINT(15)", false),
        "sessionId"=>array("INT(11)", false),
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
            'value'=>''),
        'planning'=>array(
            'options'=>array(),
            'value'=>5
        )
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
    public static function registerDigest()
    {
        $DigestMaker = new DigestMaker();
        $DigestMaker->register(get_class());
    }

    /**
     * Register into Reminder table
     */
    public static function registerReminder()
    {
        $reminder = new ReminderMaker();
        $reminder->register(get_class());
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
        $nextSessions = self::getSession()->getUpcoming($this->options['planning']['value']);
        if (empty($nextSessions) || $nextSessions == false) {
            $result['status'] = false;
            $result['msg'] = 'There is no upcoming session planned.';
        } else {
            foreach ($nextSessions as $key => $session) {
                // 1: Check if group has not been made yet for the next session
                if ($session['type'] === 'none') {
                    continue;
                } elseif (!$this->groupExist($session['id'])) {
                    $result = $this->makeGroups($session); // Make groups
                } else {
                    $result = $this->updateGroup($session);
                }
            }
        }
        return $result;
    }

    /**
     * Check if group has been already made for the next session
     * @param $session_id: session id
     * @return bool
     */
    private function groupExist($session_id)
    {
        $sql = "SELECT sessionId FROM {$this->tablename} WHERE sessionId={$session_id}";
        return $this->db->sendQuery($sql)->num_rows > 0;
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
    public function makeGroups(array $session, array $preGroups = array())
    {
        // Get rooms
        $rooms = explode(',', $this->options['room']['value']);

        // Do not make group if there is no session planned on the next journal club day
        if ($session['type'] == 'none') {
            return false;
        }

        // Set the number of groups equal to the number of presentation for this day in case it exceeds it.
        $ngroups = count($session['presids']);

        // If there is a mismatch between the number of slots and the number of predefined groups, we start
        // from scratch
        if ($ngroups !== count($preGroups)) {
            $preGroups = array();
        }

        // Get list of assignable users
        $Users = new Users();
        $users = array();
        foreach ($Users->all(array('assign'=>'1')) as $key => $user) {
            $users[] = $user['username'];
        }

        // Check if there is enough members to create the required groups
        $nusers = count($users); // total nb of users
        if (($nusers-$ngroups) < $ngroups) {
            $result['status'] = false;
            $result['msg'] = "Not enough members to create groups";
            return $result;
        }

        // Get speakers for each group
        $speakers = array();
        $excludedusers = array();
        for ($i=0; $i<$ngroups; $i++) {
            $speaker = (isset($session['content'][$i]['username'])) ? $session['content'][$i]['username'] : 'TBA';
            $speakers[$i] = $speaker;
            $excludedusers[] = $speaker;
        }
        
        // Check pre groups
        foreach ($preGroups as $groupId => $group) {
            foreach ($group as $key => $mbr) {
                if ($mbr['member'] == $speakers[$groupId]) {
                    // Remove member if he was previously assigned as speaker
                    unset($preGroups[$groupId][$key]);
                } else {
                    $excludedusers[] = $mbr['member'];
                }
            }
        }

        // Add speakers to groups
        for ($i=0; $i<$ngroups; $i++) {
            $preGroups[$i][] = array("member"=>$speakers[$i], "role"=>"speaker");
        }

        // Partition users
        $remainusers = array_values(array_diff($users, $excludedusers));
        $preGroups = self::balanceGroups($preGroups, $remainusers);

        // Assign presentation
        for ($i=0; $i<$ngroups; $i++) {
            $room = (!empty($rooms[$i])) ? $rooms[$i] : 'TBA';
            $presid = (isset($session['content'][$i]['id'])) ? $session['content'][$i]['id'] : 'TBA';

            // Add to the table
            foreach ($preGroups[$i] as $mbr) {
                $mbrData = $this->get(array('username'=>$mbr['member'], 'sessionId'=>$session['id']));
                if (!$mbrData) {
                    if (!$this->db->insert($this->tablename, array(
                        'groupId' => $i,
                        'username' => $mbr['member'],
                        'role' => $mbr['role'],
                        'presid' => $presid,
                        'sessionId' => $session['id'],
                        'room' => $room
                    ))) {
                        return false;
                    };
                } else {
                    if (!$this->db->update($this->tablename, array(
                        'groupId' => $i,
                        'username' => $mbr['member'],
                        'role' => $mbr['role'],
                        'presid' => $presid,
                        'sessionId' => $session['id'],
                        'room' => $room
                    ), array('id'=>$mbrData['id']))) {
                        return false;
                    };
                }
            }
        }

        $result['status'] = true;
        $result['msg'] = "{$ngroups} groups have been created.";
        return $result;
    }

    /**
     * Balance groups
     *
     * @param array $groups
     * @param array $members
     * @return array
     */
    private static function balanceGroups(array $groups, array $members)
    {
        // Shuffle list of new members
        shuffle($members);

        $counts = self::countUsers($groups);
        while (!empty($members)) {
            foreach ($counts['byGroup'] as $groupId => $c) {
                $key = count($members)-1;
                $groups[$groupId][] = array("member"=>$members[$key], "role"=>false);
                unset($members[$key]);
                if (empty($members)) {
                    break;
                }
            }
        }

        $counts = self::countUsers($groups);
        if ($counts['max'] > 1) {
            $input = self::extractUsers($groups);
            return self::balanceGroups($input['groups'], $input['excluded']);
        } else {
            return $groups;
        }
    }

    /**
     * Remove members from exceeding groups
     *
     * @param array $groups
     * @return array
     */
    private static function extractUsers(array $groups)
    {
        $counts = self::countUsers($groups);
        $excluded = array();
        foreach ($counts['diff'] as $groupId => $count) {
            $n = 0;
            while ($n < $count) {
                foreach ($groups[$groupId] as $key => $mbr) {
                    if ($mbr['role'] != "speaker") {
                        $excluded[] = $mbr['member'];
                        unset($groups[$groupId][$key]);
                        $n += 1;
                    }
                    if ($n >= $count) {
                        break;
                    }
                }
            }
        }

        return array('excluded'=>$excluded, 'groups'=>$groups);
    }

    /**
     * Count the number of users per group and the difference between groups
     *
     * @param array $groups
     * @return array
     */
    private static function countUsers(array $groups)
    {
        $nbByGroup = array();
        $count = array();
        foreach ($groups as $groupId => $group) {
            $nbByGroup[$groupId] = count($group);
            $count[] = count($group);
        }
        asort($nbByGroup);

        $diff = array();
        foreach ($nbByGroup as $groupId => $c) {
            $diff[$groupId] = $c - min($count);
        }
        asort($diff);

        return array('byGroup'=>$nbByGroup, 'diff'=>$diff, 'max'=>max($diff));
    }

    /**
     * Update groups
     *
     * @param array $session
     * @return array
     */
    private function updateGroup(array $session)
    {
        $this->checkSession($session);

        $sql = "SELECT * FROM {$this->tablename} WHERE sessionId={$session['id']}";
        $req = $this->db->sendQuery($sql);
        $user = new Users();
        $groups = array();
        while ($item = $req->fetch_assoc()) {
            $userData = $user->get(array('username'=>$item['username']));
            if (empty($userData) || intval($userData['assign']) == 0 || $userData['status'] == 'admin') {
                $this->delete(array('id'=>$item['id']));
            } else {
                $groups[$item['groupId']][] = array(
                    "member"=>$item['username'],
                    "role"=>$item['role']
                );
            }
        }

        // Remake groups
        return $this->makeGroups($session, $groups);
    }

    /**
     * Make reminder/digest email
     *
     * @param string|null $username
     * @return array
     */
    public function makeMail($username = null)
    {
        $data = $this->getGroup($username);
        $presentation = new \includes\Presentation();
        if ($data !== false) {
            $data['group'] = $this->showList($data, $username);
            $publication = $presentation->getInfo($data['presid']);
            $data['publication'] = Presentation::mailDetails($publication);
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
            $data['group'] = $this->showList($data, $username);
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
     * Check correspondence between group info and session info
     *
     * @param array $session: session data
     * @return bool
     */
    private function checkSession(array $session)
    {
        $sql = "SELECT COUNT(DISTINCT(groupId)) as count_grp 
                FROM {$this->tablename}
                WHERE sessionId=367";
        $nb_groups = (int)$this->db->sendQuery($sql)->fetch_assoc();
        if ($nb_groups !== count($session['presids'])) {
            if (!$this->delete(array('sessionId'=>$session['id']))) {
                return false;
            } else {
                return $this->makeGroups($session);
            }
        } else {
            return true;
        }
    }
    
    /**
     * Get user's group
     * @param $username
     * @return bool|array
     */
    public function getGroup($username, $sessionId = null)
    {
        if (is_null($sessionId)) {
            $sessionData = $this->getSession()->getUpcoming(1);
            $sessionId = $sessionData[key($sessionData)]['id'];
        }
        $session = $this->getSession()->getInfo(array('id'=>$sessionId));
        $data = $this->get(array('username'=>$username, 'sessionId'=>$sessionId));
        if (!empty($data) && isset($session['presids'][$data['groupId']])) {     
            $groupusrs['members'] = array();
            $groupusrs['room'] = $data['room'];
            $groupusrs['date'] = $session['date'];
            $groupusrs['presid'] = $session['presids'][$data['groupId']];
            foreach ($this->all(array('sessionId'=>$data['sessionId'], 'groupId'=>$data['groupId'])) as $key => $row) {
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
    public function showList(array $group, $username)
    {
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
    public function show($date = null)
    {
        if (!is_null($date)) {
            $sessionData = $this->getSession()->get(array('date'=>$date));
            $sessionId = $sessionData['id'];
        } else {
            $sessionData = $this->getSession()->getUpcoming(1);
            $sessionId = $sessionData[key($sessionData)]['id'];
            $date = $sessionData[key($sessionData)]['date'];
        }

        // Get group data
        $username = $_SESSION['username'];
        $data = $this->getGroup($username, $sessionId);

        if (!is_null($sessionData) && !empty($data['members'])) {
            $content = $this->showList($data, $username);
            $ids = array();
            foreach ($data['members'] as $grpmember => $info) {
                if ($grpmember == 'TBA') {
                    continue; // We do not send emails to fake users
                }
                $member = new Users($grpmember);
                $ids[] = $member->id;
            }
            $ids = implode(',', $ids);

            $groupContent = self::groupOnPage($date, $data['room'], $ids, $content);
        } else {
            $groupContent = self::noGroup($date);
        }
        
        return self::groupSection($date, $groupContent);
    }

    /**
     * Render group section
     *
     * @param string $date
     * @param string $content
     * @return string
     */
    private static function groupSection($date, $content)
    {
        return "
                <h2>My group</h2>
                <div class='section_content'>
                ". self::dateInput($date) ."
                {$content}
                </div>

            ";
    }

    /**
     * Render group details on profile page
     *
     * @param string $date
     * @param string $room
     * @param string $ids
     * @param string $content
     * @return string
     */
    private static function groupOnPage($date, $room, $ids, $content)
    {
        return "
        <div class='group_header'>
            <div>
                <div style='display: inline-block; width: 20px; vertical-align: middle;'>
                    <img src='". URL_TO_IMG . 'calendar_bk.png' . "'style='width: 100%; 
                    vertical-align:middle;'/>
                </div>
                <div style='display: inline-block; vertical-align: middle;'>{$date}</div>
            </div>
            <div>
                <div style='display: inline-block; width: 20px; vertical-align: middle;'>
                    <img src='" . URL_TO_IMG . 'location_bk.png' . "' style='width: 100%; 
                    vertical-align:middle;'/>
                </div>
                <div style='display: inline-block; vertical-align: middle;'>{$room}</div>
            </div>
            <div class='group_contact_btn'>
            <div class='div_button'>
                <a href='" . URL_TO_APP . 'index.php?page=member/email&recipients_list=' . $ids . "'>
                    Contact my group</a>
                </div>
            </div>
        </div>
        <div>{$content}</div>
    ";
    }

    /**
     * View when no group has been made for the selected date
     *
     * @param string $date: session date
     * @return string
     */
    private static function noGroup($date)
    {
        return "
        <div class='group_header'>
            <div>
                <div style='display: inline-block; width: 20px; vertical-align: middle;'>
                    <img src='". URL_TO_IMG . 'calendar_bk.png' . "'style='width: 100%; vertical-align:middle;'/>
                </div>
                <div style='display: inline-block; vertical-align: middle;'>{$date}</div>
            </div>
        </div>
        <div class='group_content'>
            You have not been assigned to any group yet for this session.
        </div>";
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
     * Render date selection input
     *
     * @param string $selectedDate: selected date (Y-m-d)
     *
     * @return string
     */
    private static function dateInput($selectedDate = null)
    {
        $url = \includes\Router::buildUrl('Groups', 'show', null, 'Plugins');
        return "
        <div class='form-group'>
            <input type='date' class='selectSession datepicker viewerCalendar' data-url='{$url}'
            name='date' value='{$selectedDate}' data-destination='.plugin#Groups'/>
            <label>Filter</label>
        </div>";
    }
}
