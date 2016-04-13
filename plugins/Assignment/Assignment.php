<?php
/**
 * File for class AssignSpeakers
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
 * Plugins that handles speaker assignment routines
 */
class Assignment extends AppPlugins {

    /**
     * @var array
     */
    protected $table_data = array(
        "id"=>array("INT NOT NULL AUTO_INCREMENT",false),
        "username"=>array("CHAR(255)",false),
        "primary"=>'id'
    );
    
    /**
     * @var string
     */
    public $name = "Assignment";

    /**
     * @var string
     */
    public $version = "1.1";
    
    /**
     * @var array
     */
    public $options = array(
        'nbsessiontoplan'=>array(
            'options'=>array(),
            'value'=>10)
    );

    /**
     * @var Session
     */
    private static $session;

    public static $description = "Automatically assigns members of the JCM (who agreed upon being assigned by settings 
    the corresponding option on their profile page) as speakers to the future sessions. 
    The number of sessions to plan in advance can be set in the plugin's settings.";

    /**
     * Constructor
     * @param AppDb $db
     */
    public function __construct(AppDb $db) {
        parent::__construct($db);

        $this->installed = $this->isInstalled();
        $this->tablename = $this->db->dbprefix . '_' . strtolower($this->name);

        if ($this->installed) {
            $this->registerDigest();
            if ($this->db->tableExists($this->tablename)) {
                $this->get();
                $this->getSession();
                $this->addSessionType();
                $this->addUsers();
            } else {
                $this->delete();
            }
        }
    }

    /**
     * Registers plugin into the database
     * @return bool|mysqli_result
     */
    public function install() {
        // Create corresponding table
        $table = new AppTable($this->db, $this->name, $this->table_data, strtolower($this->name));
        $table->setup();

        // Register the plugin in the db
        $class_vars = get_class_vars($this->name);
        if ($this->make($class_vars)) {
            $this->get();
            $this->getSession();
            $this->addSessionType();
            $this->addUsers();
            $this->getPresentations();
            return true;
        } else {
            return false;
        }
    }

    /**
     * Register into DigestMaker table
     */
    public function registerDigest() {
        $DigestMaker = new DigestMaker($this->db);
        $DigestMaker->register($this->name);
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
    private static function prettyName($string, $encode=true) {
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
        global $AppConfig;
        // Get session types
        $session_types = array();
        foreach ($AppConfig->session_type as $type=>$info) {
            $session_types[] = $this->prettyName($type, true);
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
                $type = $this->prettyName($type, true);
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
    private function getAssignable($session_type, $max) {
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
    private function getMax($session_type) {
        $sql = "SELECT MAX($session_type) as maximum FROM $this->tablename";
        $data = $this->db->send_query($sql)->fetch_assoc();
        return (int)$data['maximum'];
    }

    /**
     * Get new speaker
     * 
     * @param Session $session
     * @return string
     */
    private function getSpeaker(Session $session) {
        set_time_limit(10);

        // Prettify session type
        $session_type = $this->prettyName($session->type, true);

        // Get maximum number of presentations
        $max = $this->getMax($session_type);

        // Get speakers planned for this session
        $speakers = array_diff($session->speakers, array('TBA'));

        // Get assignable users
        $assignable_users = array();
        while (empty($assignable_users)) {
            $assignable_users = $this->getAssignable($session_type, $max);
            $max += 1;
        }

        $usersList = array();
        foreach ($assignable_users as $key=>$user) {
            $usersList[] = $user['username'];
        }

        // exclude the already assigned speakers for this session from the list of possible speakers
        $assignable = array_values(array_diff($usersList,$speakers));

        if (empty($assignable)) {
            // If there are no users registered yet, the speaker is to be announced.
            $newSpeaker = 'TBA';
        } else {
            /* We randomly pick a speaker among organizers who have not chaired a session yet,
             * apart from the other speakers of this session.
             */
            $ind = rand(0, count($assignable) - 1);
            $newSpeaker = $assignable[$ind];
        }

        // Update the assignment table
        if (!$this->updateTable($session_type, $newSpeaker, true)) {
            return false;
        }

        return $newSpeaker;
    }

    /**
     * Update user's number of presentations
     * @param $session_type
     * @param $speaker
     * @param bool $add: if true, then increase the number of presentations by 1, or decrease by 1 if false
     * @return bool
     */
    private function updateTable($session_type, $speaker, $add=true) {
        if ($session_type === 'none') return true;
        $inc = ($add) ? 1:-1; // increase or decrease number of presentations
        $value = $this->db->send_query("SELECT {$session_type} 
                                        FROM {$this->tablename} 
                                        WHERE username='{$speaker}'")->fetch_array();
        $value = (int)$value[$session_type] + $inc;
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
     * @param null|int $nb_session: number of sessions
     * @return mixed
     */
    public function assign($nb_session=null) {
        $this->get();
        $nb_session = (is_null($nb_session)) ? $this->options['nbsessiontoplan']['value']:$nb_session;

        // Get future sessions dates
        $jc_days = self::getSession()->getjcdates(intval($nb_session));

        $created = 0;
        $updated = 0;
        $assignedSpeakers = array();

        // Loop over sessions
        foreach ($jc_days as $day) {

            // If session does not exist yet, we create a new one
            $session = new Session($this->db, $day);
            if (!$session->dateexists($day)) {
                $session->make();
            }

            // Do nothing if nothing is planned on that day
            if ($session->type === "none") continue;

            // If a session is planned for this day, we assign 1 speaker by presentation
            for ($p=0; $p<self::$session->max_nb_session; $p++) {

                // If there is already a presentation planned for this day, check if the speaker is a real member, otherwise
                // we will assign a new one
                if (isset($session->presids[$p])) {
                    $Presentation = new Presentation($this->db, $session->presids[$p]);
                    $doAssign = $Presentation->orator === 'TBA';
                    $new = false;
                } else {
                    $Presentation = new Presentation($this->db);
                    $doAssign = true;
                    $new = true;
                }

                if (!$doAssign) { continue; }

                // Get & assign new speaker
                if (!$Newspeaker = $this->getSpeaker($session)) {
                    return false;
                }

                // Get speaker information
                $speaker = new User($this->db, $Newspeaker);

                // Create/Update presentation
                if ($new) {
                    $post = array(
                        'title'=>'TBA',
                        'date'=>$day,
                        'type'=>'paper',
                        'username'=>$speaker->username,
                        'orator'=>$speaker->username);
                    if ($presid = $Presentation->make($post)) {
                        $created += 1;
                    }
                } else {
                    $post = array(
                        'date'=>$day,
                        'username'=>$speaker->username,
                        'orator'=>$speaker->username);
                    if ($Presentation->update($post)) {
                        $updated += 1;
                    }
                }

                // Update session info
                $session->get();
                
                // Notify assigned user
                $info = array(
                    'speaker'=>$speaker->username, 
                    'type'=>$session->type, 
                    'presid'=>$Presentation->id_pres,
                    'date'=>$session->date
                );
                $session->notify_session_update($speaker, $info);

                $assignedSpeakers[$day][] = $info;
            }

        }
        $result['content'] = $assignedSpeakers;
        $result['msg'] = "$created chair(s) created<br>$updated chair(s) updated";
        return $result;
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