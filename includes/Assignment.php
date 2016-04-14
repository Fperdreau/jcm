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
class Assignment extends AppTable {

    /**
     * @var array
     */
    protected $table_data = array(
        "id"=>array("INT NOT NULL AUTO_INCREMENT",false),
        "username"=>array("CHAR(255)",false),
        "primary"=>'id'
    );

    /**
     * @var Session
     */
    private static $session;

    /**
     * Constructor
     * @param AppDb $db
     */
    public function __construct(AppDb $db) {
        parent::__construct($db, 'Assignment', $this->table_data);

        $this->registerDigest();
        if ($this->db->tableExists($this->tablename)) {
            $this->getSession();
            $this->addSessionType();
            $this->addUsers();
        }

    }

    /**
     * Get user's assignments
     * @param $username
     * @return array
     */
    public function get($username) {
        $sql = "SELECT * FROM {$this->tablename} WHERE username='{$username}'";
        return $this->db->send_query($sql)->fetch_assoc();
    }

    /**
     * Register into DigestMaker table
     */
    public function registerDigest() {
        $DigestMaker = new DigestMaker($this->db);
        $DigestMaker->register(get_class());
    }

    /**
     * Get session instance
     */
    private function getSession() {
        if (is_null(self::$session)) {
            self::$session = new Session($this->db);
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
     * @return bool
     */
    private function addSessionType() {
        $AppConfig = new AppConfig($this->db);

        // Get session types
        $session_types = array();
        foreach ($AppConfig->session_type as $type=>$info) {
            $session_types[] = self::prettyName($type, true);
        }

        $reg_types = $this->db->getcolumns($this->tablename);
        $reg_types = array_values(array_diff($reg_types, array_keys($this->table_data)));
        $diff = (array_diff($session_types, $reg_types));

        if (!empty($diff)) {
            foreach ($diff as $type) {
                if (!$this->db->addcolumn($this->tablename, $type, 'INT NOT NULL DEFAULT 0')) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Update members' presentation number based on presentations registered in the Presentation table
     */
    public function getPresentations() {
        $Session = new Session($this->db);
        $sql = "SELECT * FROM " . $this->db->tablesname['Presentation'];
        $req = $this->db->send_query($sql);
        $data = array();
        while ($row = $req->fetch_assoc()) {
            $data[] = $row;
        }

        // Get number of presentations for every member and session type
        $list = array();
        foreach ($data as $key=>$info) {
            $Session->get($info['date']);
            if ($Session->type === 'none' || empty($info['username'])) continue;
            if (!isset($list[$info['username']][$Session->type])) {
                $list[$info['username']][$Session->type] = 0;
            } else {
                $list[$info['username']][$Session->type] += 1;
            }
        }

        // Step 2: update table
        foreach ($list as $username=>$info) {
            foreach ($info as $type=>$value) {
                $type = self::prettyName($type, true);
                $this->db->updatecontent($this->tablename, array($type=>$value), array('username'=>$username));
            }
        }
    }

    /**
     * Add users to assignment table
     *
     * @return bool
     */
    public function addUsers() {
        // Get users list
        $Users = new Users($this->db);
        $usersList = array();
        foreach ($Users->getUsers(false) as $key=>$user) {
            $usersList[] = $user['username'];
        }

        // Get list of users currently registered into the assignment table
        $req = $this->db->send_query("SELECT username FROM {$this->tablename}");
        $data = array();
        while ($row = $req->fetch_assoc()) {
            $data[] = $row;
        }

        $AssignUsers = array();
        foreach ($data as $key=>$user) {
            $AssignUsers[] = $user['username'];
        }

        // Add users to the assignment table if not present yet
        $diff = array_values(array_diff($usersList, $AssignUsers));
        foreach ($diff as $user) {
            if (!$this->db->addcontent($this->tablename, array('username'=>$user))) {
                return false;
            }
        }
        return true;
    }


    /**
     * Get list of assignable users
     *
     * @param string $session_type: session type (pretty formatted: eg. "Journal Club" => "journal_club")
     * @param int $max: maximum number of presentations
     * @return mixed
     */
    public function getAssignable($session_type, $max) {
        $req = $this->db->send_query("
            SELECT * 
            FROM {$this->tablename} a
            INNER JOIN ".$this->db->tablesname['User']." u
            ON a.username=u.username
            WHERE (a.$session_type<$max)
                AND u.assign=1 AND u.status!='admin'
            ");
        $data = array();
        while ($row = $req->fetch_assoc()) {
            $data[] = $row;
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
        return $this->db->updatecontent($this->tablename, array($session_type=>$value), array("username"=>$speaker));
    }

    /**
     * Update speaker assignment: update assignment table and notify user
     * @param User $user
     * @param array $info
     * @param bool $assign : assign (true) or unassign (false) user
     * @param bool $notify: notify user by email
     * @return bool
     */
    public function updateAssignment(User $user, array $info, $assign=true, $notify=false) {
        $session = new Session($this->db, $info['date']);
        if ($this->updateTable(self::prettyName($session->type, true), $user->username, $assign)) {
            if ($notify) {
                $session->notify_session_update($user, $info, $assign);
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     *
     * @param null $username
     * @return mixed
     */
    public function makeMail($username=null) {
        $user = new User($this->db, $username);
        $content['body'] = $user->getAssignments(true, $username);;
        $content['title'] = 'Your assignments';
        return $content;
    }

}