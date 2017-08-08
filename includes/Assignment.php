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


/**
 * Class Assignment
 *
 * Class that handles speaker assignment routines
 */
class Assignment extends BaseModel {

    /**
     * @var Session
     */
    private static $session;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Setup
     */
    public function setup() {
        $this->check();
        $this->getPresentations();
    }

    /**
     * Check correspondences between table
     */
    public function check() {
        if ($this->db->tableExists($this->tablename)) {
            $this->get_session_instance();
            $this->update_types();
            $this->update_users();
        }
    }

    /**
     * Add missing session types and remove deleted ones
     */
    private function update_types() {
        $this->add_types();

        $this->delete_types();
    }

    /**
     * Add missing users and remove deleted user accounts
     */
    private function update_users() {
        $this->add_users();

        $this->delete_users();
    }

    /**
     * Register into DigestMaker table
     */
    public static function registerDigest() {
        $DigestMaker = new DigestMaker();
        $DigestMaker->register(get_class());
    }

    /**
     * Get session instance
     */
    private function get_session_instance() {
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
    public static function prettyName($string, $encode=true) {
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
    private function add_type(array $types=array()) {
        if (!empty($types)) {
            foreach ($types as $type) {
                if (!$this->db->add_column($this->tablename, $type, "INT NOT NULL DEFAULT '0'")) {
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
    private function delete_type(array $types=array()) {
        if (!empty($types)) {
            foreach ($types as $type) {
                if (!$this->db->delete_column($this->tablename, $type)) {
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
    private function add_types() {
        $missing_types = (array_diff($this->get_session_types('app'), $this->get_session_types('db')));
        return $this->add_type($missing_types);
    }

    /**
     * Add missing session types to db
     * @return bool
     */
    private function delete_types() {
        $types = $this->get_session_types('app');
        // Get users present in assignment table but not in users table
        $to_remove = array();
        foreach ($this->get_session_types('db') as $type) {
            if (!in_array($type, $types)) {
                $to_remove[] = $type;
            }
        }
        return $this->delete_type($to_remove);
    }

    /**
     * Get list of session types
     * @param $source: information source
     * @return array
     */
    private function get_session_types($source) {
        if ($source === 'app') {
            // Get session types
            $session_types = array();
            foreach ($this->get_session_instance()->getTypes() as $type) {
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
    public function getPresentations() {
        // Step 1: get users' presentations sorted by session type
        $sql = "SELECT * FROM " . $this->db->gen_name('Presentation');
        $req = $this->db->send_query($sql);
        $list = array();

        while ($row = $req->fetch_assoc()) {
            $Session = new Session($row['date']);
            if ($Session->type === 'none' || empty($row['orator'])) continue;

            if (!isset($list[$row['orator']][$Session->type])) {
                $list[$row['orator']][$Session->type] = 1;
            } else {
                $list[$row['orator']][$Session->type] += 1;
            }
        }

        // Step 2: update table
        foreach ($list as $username=>$info) {
            foreach ($info as $type=>$value) {
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
    public function add_users() {
        // Get users list
        $usersList = $this->get_users_list('users');
        $AssignUsers = $this->get_users_list('db');

        // Add users to the assignment table if not present yet
        $diff = array_values(array_diff($usersList, $AssignUsers));
        return $this->add_user($diff);
    }

    /**
     * Delete users from assignment table
     *
     * @return bool
     */
    public function delete_users() {
        // Get users list
        $usersList = $this->get_users_list('users');

        // Get users present in assignment table but not in users table
        $to_remove = array();
        foreach ($this->get_users_list('db') as $user) {
            if (!in_array($user, $usersList)) {
                $to_remove[] = $user;
            }
        }

        // Add users to the assignment table if not present yet
        return $this->delete_user($to_remove);
    }

    /**
     * Add users to assignment table
     * @param array $users
     * @return bool
     */
    private function add_user(array $users=array()) {
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
    private function delete_user(array $users=array()) {
        foreach ($users as $user) {
            if (!$this->db->delete($this->tablename, array('username'=>$user))) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get list of users
     * @param $source
     * @return array
     */
    private function get_users_list($source) {
        $usersList = array();

        if ($source === 'db') {
            // Get list of users currently registered into the assignment table
            $req = $this->db->send_query("SELECT username FROM {$this->tablename}");
            $data = array();
            while ($row = $req->fetch_assoc()) {
                $data[] = $row;
            }

            foreach ($data as $key=>$user) {
                $usersList[] = $user['username'];
            }
        } elseif ($source === 'users') {
            // Get users list
            $Users = new Users();
            foreach ($Users->getAll() as $key=>$user) {
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
    public function all(array $id=null, array $filter=null) {
        $sql = "SELECT p.*, u.fullname
                FROM {$this->tablename} p
                LEFT JOIN {$this->db->gen_name('Users')} u
                ON p.username=u.username";
        $req = $this->db->send_query($sql);
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
    public function getAssignable($session_type, $max, $date) {
        $req = $this->db->send_query("
            SELECT * 
            FROM {$this->tablename} a
            INNER JOIN ".$this->db->tablesname['Users']." u
            ON a.username=u.username
            WHERE (a.$session_type<$max)
                AND u.assign=1 AND u.status!='admin'
            ");

        // Check users availability for this day
        $Availability = new Availability($this->db);
        $data = array();
        while ($row = $req->fetch_assoc()) {
            $availability = array();
            foreach ($Availability->get(array('username'=>$row['username'])) as $key=> $info) {
                $availability[] = $info['date'];
            }
            if (empty($availability) || !in_array($date, $availability)) {
                $data[] = $row;
            }
        }
        return $data;
    }

    /**
     * Get Maximum number of presentations
     *
     * @param $session_type
     * @return mixed
     */
    public function getMax($session_type) {
        $sql = "SELECT MAX($session_type) as maximum FROM $this->tablename";
        $data = $this->db->send_query($sql)->fetch_assoc();
        return (int)$data['maximum'];
    }

    /**
     * Update user's number of presentations
     * @param $session_type
     * @param $speaker
     * @param bool $add: if true, then increase the number of presentations by 1, or decrease by 1 if false
     * @return bool
     */
    public function updateTable($session_type, $speaker, $add=true) {
        if ($session_type === 'none') return true;
        $inc = ($add) ? 1:-1; // increase or decrease number of presentations
        $value = $this->db->send_query("SELECT {$session_type} 
                                        FROM {$this->tablename} 
                                        WHERE username='{$speaker}'")->fetch_array();
        $value = ((int)$value > 0) ? (int)$value[$session_type] + $inc: 0; // Assignment number can be negative
        return $this->db->update($this->tablename, array($session_type=>$value), array("username"=>$speaker));
    }

    /**
     * Update speaker assignment: update assignment table and notify user
     * @param Users $user
     * @param array $info: array('type'=>renderTypes, 'date'=>session_date, 'presid'=>presentation_id)
     * @param bool $assign : assign (true) or unassign (false) user
     * @param bool $notify: notify user by email
     * @return bool
     */
    public function updateAssignment(Users $user, array $info, $assign=true, $notify=false) {
        $session = new Session($info['date']);
        if ($this->updateTable(self::prettyName($info['type'], true), $user->username, $assign)) {
            if ($notify) {
                $session->notify_session_update($user, $info, $assign);
            }
            Logger::get_instance(APP_NAME, get_class($this))->info("Assignments for {$user->username} have been updated");
            return true;
        } else {
            Logger::get_instance(APP_NAME, get_class($this))->info("Could not update assignments for {$user->username}");
            return false;
        }
    }

    /**
     *
     * @param null $username
     * @return mixed
     */
    public function makeMail($username=null) {
        $user = new Users($username);
        $content['body'] = $user->getAssignments();;
        $content['title'] = 'Your assignments';
        return $content;
    }

    /**
     * Returns all list of members with their corresponding number of assignments per sessions type
     * (used in admin>assignments page)
     * @return string
     */
    public function showAll() {
        $data = $this->all();
        return self::showList($data);
    }

    /**
     * Renders members list and their corresponding number of assignments per sessions type
     * @param array $data
     * @return string
     */
    public static function showList(array $data) {
        $content = "";
        $headers = array_diff(array_keys($data[0]), array('id', 'fullname', 'username'));
        foreach ($data as $key=>$info) {
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
    public static function showSingle(array $info, array $session_types) {
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

}