<?php
/**
 * File for class Assignment
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

namespace includes;

use includes\BaseModel;

/**
 * Class Assignment
 *
 * Class that handles speaker assignment routines
 */
class Assignment extends BaseModel
{

    /**
     * @var Session
     */
    private static $session;

    /**
     * Setup
     */
    public function setup()
    {
        $this->check();
        $this->getPresentations();
    }

    /**
     * Check correspondences between table
     */
    public function check()
    {
        if ($this->db->tableExists($this->tablename)) {
            $this->getSessionInstance();
            $this->updateTypes();
            $this->updateUsers();
        }
    }

    /**
     * Reset assignment table
     *
     * @return string
     */
    public function resetTable()
    {
        $data = $this->all();
        $session_types = $this->getSessionTypes('app');
        $values = array_fill(0, count($session_types), 0);
        $newValues = array_combine($session_types, $values);
        foreach ($this->all() as $key => $item) {
            $this->update($newValues, array('id'=>$item['id']));
        }

        return $this->showList($this->all());
    }

    /**
     * Update assignment table based on submission records
     *
     * @return string
     */
    public function updateAssignmentTable()
    {
        // Check correspondence users/assignment tables
        $this->check();

        // Get presentations per user
        $this->getPresentations();

        return $this->showList($this->all());
    }

    /**
     * Add missing session types and remove deleted ones
     */
    private function updateTypes()
    {
        $this->addTypes();

        $this->deleteTypes();
    }

    /**
     * Add missing users and remove deleted user accounts
     */
    private function updateUsers()
    {
        $this->addUsers();

        $this->deleteUsers();
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
     * Get session instance
     */
    private function getSessionInstance()
    {
        if (is_null(self::$session)) {
            self::$session = new Session();
        }
        return self::$session;
    }

    /**
     * Beautify session type name
     * @param $string
     * @param bool $encode
     * @return mixed
     */
    public static function prettyName($string, $encode = true)
    {
        if ($encode) {
            return strtolower(str_replace(" ", "_", $string));
        } else {
            return ucfirst(str_replace("_", " ", $string));
        }
    }

    /**
     * Adds session type to Assignment table
     * @param array $types
     * @return bool
     */
    private function addType(array $types = array())
    {
        if (!empty($types)) {
            foreach ($types as $type) {
                if (!$this->db->addColumn($this->tablename, $type, "INT NOT NULL DEFAULT '0'")) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Adds session type to Assignment table
     * @param array $types
     * @return bool
     */
    private function deleteType(array $types = array())
    {
        if (!empty($types)) {
            foreach ($types as $type) {
                if (!$this->db->deleteColumn($this->tablename, $type)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Add missing session types to db
     * @return bool
     */
    private function addTypes()
    {
        $missing_types = (array_diff($this->getSessionTypes('app'), $this->getSessionTypes('db')));
        return $this->addType($missing_types);
    }

    /**
     * Add missing session types to db
     * @return bool
     */
    private function deleteTypes()
    {
        $types = $this->getSessionTypes('app');
        // Get users present in assignment table but not in users table
        $to_remove = array();
        foreach ($this->getSessionTypes('db') as $type) {
            if (!in_array($type, $types)) {
                $to_remove[] = $type;
            }
        }
        return $this->deleteType($to_remove);
    }

    /**
     * Get list of session types
     * @param $source: information source
     * @return array
     */
    private function getSessionTypes($source)
    {
        if ($source === 'app') {
            // Get session types
            $session_types = array();
            $types = \includes\TypesManager::getTypes('Session');
            foreach ($types['types'] as $type) {
                $session_types[] = self::prettyName($type, true);
            }
        } elseif ($source === 'db') {
            $reg_types = $this->db->getColumns($this->tablename);
            $session_types = array_values(array_diff($reg_types, array_keys($this->table_data)));
        } else {
            $session_types = array();
        }
        return $session_types;
    }

    /**
     * Update members' presentation number based on presentations registered in the Presentation table
     */
    public function getPresentations()
    {
        // Step 1: get users' presentations sorted by session type
        $sql = "SELECT * FROM " . $this->db->genName('Presentation');
        $req = $this->db->sendQuery($sql);
        $list = array();
        $Session = new Session();

        while ($row = $req->fetch_assoc()) {
            $session = $Session->getInfo(array('id'=>$row['session_id']));
            if ($session['type'] === 'none' || empty($row['orator'])) {
                continue;
            }

            if (!isset($list[$row['orator']][$session['type']])) {
                $list[$row['orator']][$session['type']] = 1;
            } else {
                $list[$row['orator']][$session['type']] += 1;
            }
        }

        // Step 2: update table
        foreach ($list as $username => $info) {
            foreach ($info as $type => $value) {
                $type = self::prettyName($type, true);
                $this->db->update($this->tablename, array($type=>$value), array('username'=>$username));
            }
        }
    }

    /**
     * Add users to assignment table
     *
     * @return bool
     */
    public function addUsers()
    {
        // Get users list
        $usersList = $this->getUsersList('users');
        $AssignUsers = $this->getUsersList('db');

        // Add users to the assignment table if not present yet
        $diff = array_values(array_diff($usersList, $AssignUsers));
        return $this->addUser($diff);
    }

    /**
     * Delete users from assignment table
     *
     * @return bool
     */
    public function deleteUsers()
    {
        // Get users list
        $usersList = $this->getUsersList('users');

        // Get users present in assignment table but not in users table
        $to_remove = array();
        foreach ($this->getUsersList('db') as $user) {
            if (!in_array($user, $usersList)) {
                $to_remove[] = $user;
            }
        }

        // Add users to the assignment table if not present yet
        return $this->deleteUser($to_remove);
    }

    /**
     * Add users to assignment table
     * @param array $users
     * @return bool
     */
    private function addUser(array $users = array())
    {
        foreach ($users as $user) {
            if (!$this->db->insert($this->tablename, array('username'=>$user))) {
                return false;
            }
        }
        return true;
    }

    /**
     * Delete user from assignment table
     * @param array $users
     * @return bool
     */
    private function deleteUser(array $users = array())
    {
        foreach ($users as $user) {
            if (!$this->db->delete($this->tablename, array('username'=>$user))) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get list of users
     * @param string $source
     * @return array
     */
    private function getUsersList($source)
    {
        $usersList = array();

        if ($source === 'db') {
            // Get list of users currently registered into the assignment table
            $req = $this->db->sendQuery("SELECT username FROM {$this->tablename}");
            $data = array();
            while ($row = $req->fetch_assoc()) {
                $data[] = $row;
            }

            foreach ($data as $key => $user) {
                $usersList[] = $user['username'];
            }
        } elseif ($source === 'users') {
            // Get users list
            $Users = new Users();
            foreach ($Users->getAll() as $key => $user) {
                $usersList[] = $user['username'];
            }
        } else {
            $usersList = array();
        }

        return $usersList;
    }

    /**
     * Gets whole table
     * @param array $id
     * @param array $filter
     * @return array
     */
    public function all(array $id = null, array $filter = null)
    {
        $sql = "SELECT p.*, u.fullname
                FROM {$this->tablename} p
                LEFT JOIN {$this->db->genName('Users')} u
                ON p.username=u.username";
        $req = $this->db->sendQuery($sql);
        $data = array();
        while ($row = $req->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    /**
     * Get list of assignable users
     *
     * @param string $session_type : session type (pretty formatted: eg. "Journal Club" => "journal_club")
     * @param int $max : maximum number of presentations
     * @param $date: session date
     * @return mixed
     */
    public function getAssignable($session_type, $max, $date)
    {
        $req = $this->db->sendQuery("
            SELECT DISTINCT(u.username) 
            FROM " . $this->db->getAppTables('Users') . " u
            INNER JOIN  " . $this->db->getAppTables('Assignment') . " p
            ON u.username=p.username
            WHERE u.assign=1 AND p.{$session_type}<{$max}
                AND u.username IN (
                    SELECT username
                    FROM " . $this->db->getAppTables('Availability') . " a
                    WHERE a.date!='{$date}'
                )
            ");

        // Get list of usernames
        $data = array();
        while ($row = $req->fetch_assoc()) {
            $data[] = $row['username'];
        }
        return $data;
    }

    /**
     * Get Maximum number of presentations
     *
     * @param $session_type
     * @return mixed
     */
    public function getMax($session_type)
    {
        $sql = "SELECT MAX($session_type) as maximum FROM $this->tablename";
        $data = $this->db->sendQuery($sql)->fetch_assoc();
        return (int)$data['maximum'];
    }

    /**
     * Update user's number of presentations
     * @param $session_type
     * @param $speaker
     * @param bool $add: if true, then increase the number of presentations by 1, or decrease by 1 if false
     * @return bool
     */
    public function updateTable($session_type, $speaker, $add = true)
    {
        if ($session_type === 'none') {
            return true;
        }

        $value = $this->db->sendQuery("SELECT {$session_type} 
                                        FROM {$this->tablename} 
                                        WHERE username='{$speaker}'")->fetch_array();

        $inc = ($add) ? 1:-1; // increase or decrease number of presentations
        $newValue = max(0, (int)$value[$session_type] + $inc);
        return $this->db->update($this->tablename, array($session_type=>$newValue), array("username"=>$speaker));
    }

    /**
     * Update speaker assignment: update assignment table and notify user
     * @param string $username
     * @param array $info: array('type'=>renderTypes, 'date'=>session_date, 'presid'=>presentation_id)
     * @param bool $assign : assign (true) or unassign (false) user
     * @param bool $notify: notify user by email
     * @return bool
     */
    public function updateAssignment($username, array $info, $assign = true, $notify = false)
    {
        $user = new Users();
        $userData = $user->get(array('username'=>$username));
        if ($this->updateTable(self::prettyName($info['type'], true), $userData['username'], $assign)) {
            Logger::getInstance(APP_NAME, get_class($this))->info(
                "Assignments for {$userData['username']} have been updated"
            );
            if ($notify) {
                return self::notifyUpdate($userData['username'], $info, $assign);
            } else {
                return true;
            }
        } else {
            Logger::getInstance(APP_NAME, get_class($this))->info(
                "Could not update assignments for {$userData['username']}"
            );
            return false;
        }
    }

    /**
     * Unassign presentation
     *
     * @param int $id: presentation id
     * @param bool $notify: send notification to speaker by email
     * @return bool
     */
    public function unlinkPresentation(array $id, $notify = false)
    {
        // Get presentation data
        $presentation = new Presentation();
        $data = $presentation->get($id);

        if (!empty($data['session_id'])) {
            // Get session info
            $session = new Session();
            $sessionData = $session->getInfo(array('id'=>$data['session_id']));
            return $this->updateAssignment(
                $data['username'],
                array(
                    'type'=>$sessionData['type'],
                    'date'=>$sessionData['date'],
                    'presid'=>$data['id']
                ),
                false,
                $notify
            );
        }
        return true;
    }

    /**
     * Renders email notifying presentation assignment
     * @param string $username
     * @param array $info: array('type'=>session_type,'date'=>session_date, 'presid'=>presentation_id)
     * @param bool $assigned
     * @return mixed
     */
    public static function notifyUpdate($username, array $info, $assigned = true)
    {
        // Return true if not real user
        if ($username == 'TBA') {
            return true;
        }

        $MailManager = new MailManager();
        $user = new Users();
        $userData = $user->get(array('username'=>$username));
        if ($assigned) {
            $dueDate = date('Y-m-d', strtotime($info['date'].' - 1 week'));
            $content = self::invitationEmail(
                $userData['username'],
                $userData['fullname'],
                $dueDate,
                $info['date'],
                $info['type'],
                $info['presid']
            );
        } else {
            $content = self::cancelationEmail($userData['fullname'], $info['date']);
            self::notifyOrganizers($userData['fullname'], $info);
        }

        // Send email
        $content['emails'] = $userData['id'];
        $result = $MailManager->addToQueue($content);
        return $result;
    }

    /**
     * Notify organizers that a presentation has been manually canceled
     * @param string $userFullname
     * @param array $info
     * @return mixed
     */
    public static function notifyOrganizers($userFullname, array $info)
    {
        $MailManager = new MailManager();
        $user = new Users();
        foreach ($user->getAdmin() as $key => $userInfo) {
            $content = self::cancelationOrganizerEmail($userInfo['fullname'], $userFullname, $info['date']);
            $content['emails'] = $userInfo['id'];
            if (!$MailManager->addToQueue($content)) {
                return false;
            }
        }
        return true;
    }

    /**
     *
     * @param null $username
     * @return mixed
     */
    public function makeMail($username = null)
    {
        $user = new Users($username);
        $content['body'] = $user->getAssignments();
        $content['title'] = 'Your assignments';
        return $content;
    }

    /**
     * Returns all list of members with their corresponding number of assignments per sessions type
     * (used in admin>assignments page)
     * @return string
     */
    public function showAll()
    {
        return self::showTable($this->all());
    }

    /**
     * Render assignments table
     *
     * @param array $data
     * @return string
     */
    public static function showTable(array $data)
    {
        $buttons = self::tableButtons();
        $table = self::showList($data);

        return "
        {$buttons}
        <div id='tableAssignments'>
        {$table}
        </div>
        ";
    }

    private static function tableButtons()
    {
        $leanModalUrlReset = Router::buildUrl(
            'Assignment',
            'resetTable'
        );

        $leanModalUrlUpdate = Router::buildUrl(
            'Assignment',
            'updateAssignmentTable'
        );

        return "
        <div class='button_container'>
            <div>
                <a class='loadContent' data-url='{$leanModalUrlUpdate}' data-destination='#tableAssignments'>
                    <input type='submit' value='Update' />
                </a>
            </div>
            <div>
                <a class='loadContent' data-url='{$leanModalUrlReset}' data-destination='#tableAssignments'>
                    <input type='submit' value='Reset' />
                </a>
            </div>
        </div>";
    }

    /**
     * Renders members list and their corresponding number of assignments per sessions type
     * @param array $data
     * @return string
     */
    public static function showList(array $data)
    {
        $content = "";
        $headers = array_diff(array_keys($data[0]), array('id', 'fullname', 'username'));
        foreach ($data as $key => $info) {
            $content .= self::showSingle($info, $headers);
        }
        
        $headings = "";
        foreach ($headers as $heading) {
            $headings .= "<div>" . self::prettyName($heading, false) . "</div>";
        }

        return "
        <div class='table_container'>
            <div class='list-heading'>
                <div>Name</div>
                {$headings}
            </div>
            {$content}
        </div>
        ";
    }

    /**
     * Renders member's number of assignments per sessions type
     * @param array $info
     * @param array $session_types
     * @return string
     */
    public static function showSingle(array $info, array $session_types)
    {
        $session_type = "";
        foreach ($session_types as $heading) {
            $session_type .= "<div>{$info[$heading]}</div>";
        }
        return "
            <div class='list-container'>
                <div>{$info['fullname']}</div>   
                {$session_type}
            </div>";
    }

    /**
     * Content of presentation cancelation email sent to speaker
     *
     * @param string $fullname: user's full name
     * @param string $date: presentation date
     *
     * @return array: array('body'=>content of email, 'subject'=>email's title)
     */
    private static function cancelationEmail($fullname, $date)
    {
        $contactURL = URL_TO_APP . "index.php?page=contact";
        return array(
            'body'=>"<div style='width: 100%; margin: auto;'>
                <p>Hello {$fullname},</p>
                <p>Your presentation planned on {$date} has been canceled. 
                You are no longer required to give a presentation on this day.</p>
                <p>If you need more information, please <a href='{$contactURL}'>contact</a> the organizers.</p>
                </div>
                ",
            'subject'=>"Your presentation ($date) has been canceled"
        );
    }

    /**
     * Content of invitation email
     *
     * @param string $fullname: user's full name
     * @param string $dueDate: deadline for submitting presentation
     * @param string $date: date of presentation
     * @param string $session_type: session type
     *
     * @return array: array('body'=>content of email, 'subject'=>email's title)
     */
    private static function invitationEmail($username, $fullname, $dueDate, $date, $session_type, $presId)
    {
        $contactURL = URL_TO_APP."index.php?page=contact";
        $editUrl = URL_TO_APP."index.php?page=presentation&id={$presId}";
        return array(
            'body'=> "<div style='width: 100%; margin: auto;'>
                    <p>Hello {$fullname},</p>
                    <p>You have been automatically invited to present at a 
                    <span style='font-weight: 500'>{$session_type}</span> 
                    session on the <span style='font-weight: 500'>{$date}</span>.</p>
                    <p>Please, submit your presentation on the Journal Club Manager before the 
                    <span style='font-weight: 500'>{$dueDate}</span>.</p>
                    <p>If you think you will not be able to present on the assigned date, please 
                    <a href='{$contactURL}'>contact</a> the organizers as soon as possible.</p>
                    <div>
                        You can edit your presentation from this link: <a href='{$editUrl}'>{$editUrl}</a>
                    </div>
                </div>
            ",
            'subject'=> "Invitation to present on the {$date}"
        );
    }

    /**
     * Content of presentation cancelation email sent to organizers
     *
     * @param string $fullname: organizer's full name
     * @param string $speaker: speaker's full name
     * @param string $date: date of presentation
     *
     * @return array: array('body'=>content of email, 'subject'=>email's title)
     */
    private static function cancelationOrganizerEmail($fullname, $speaker, $date)
    {
        $url = URL_TO_APP . 'index.php?page=organizer/sessions';
        return array(
            'body'=>"<div style='width: 100%; margin: auto;'>
                <p>Hello {$fullname},</p>
                <p>This is to inform you that the presentation of 
                <strong>{$speaker}</strong> planned on the <strong>{$date}</strong> has been canceled. 
                You can either manually assign another speaker on this day in the 
                <a href='{$url}'>Admin>Session</a> section or let the automatic 
                assignment select a member for you.</p>
                </div>
            ",
            'subject'=>"A presentation ($date) has been canceled"
        );
    }
}
