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


/**
 * Class Groups
 *
 * Plugin that assign users to different groups according to the number of presentations in a session. Display the
 * user's group on his/her profile page
 */
class Groups extends AppPlugins {

    protected $table_data = array(
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
    public $version = "1.0.1";
    public $page = 'profile';
    public $status = 'Off';
    public $installed = False;
    public $options = array(
        "room"=>""
    );

    public static $description = "Automatically creates groups of users based on the number of presentations scheduled 
    for the upcoming session. Users will be notified by email about their group's information. If the different groups are
    meeting in different rooms, then the rooms can be specified in the plugin's settings (rooms must be comma-separated).";

    /**
     * Constructor
     * @param AppDb $db
     */
    public function __construct(AppDb $db) {
        parent::__construct($db);
        $this->installed = $this->isInstalled();
        $this->tablename = $this->db->dbprefix.'_groups';
        $this->registerDigest();
        if ($this->installed) {
            if ($this->db->tableExists($this->tablename)) {
                $this->get();
            } else {
                $this->delete();
            }
        }
    }

    /**
     * Register into DigestMaker table
     */
    private function registerDigest() {
        $DigestMaker = new DigestMaker($this->db);
        $DigestMaker->register($this->name);
    }

    /**
     * Check whether this cron job is registered to the database
     * @return bool|mysqli_result
     */
    public function install() {
        // Create corresponding table
        $table = new AppTable($this->db, "Groups", $this->table_data, 'groups');
        $table->setup();

        // Register the plugin in the db
        $class_vars = get_class_vars('Groups');
        return $this->make($class_vars);
    }

    /**
     * Run scheduled task: Assign users to groups and send them an email with their assigned group and presentation
     * @return array|string
     */
    public function run() {
        // 1: Clear the group table
        $this->clearTable();

        // 2: Assign groups
        $result = $this->makegroups(); // Make groups

        return $result;
    }

    /**
     * Clear the group table
     * @return bool|mysqli_result
     */
    private function clearTable() {
        return $this->db->clearTable($this->tablename);
    }

    /**
     * Randomly assign groups to users for the next session
     * @return array|bool
     */
    function makegroups() {
        global $Sessions, $db, $AppConfig;

        // Get presentations of the next session
        $nextdate = $Sessions->getsessions(true);
        $session = new Session($db,$nextdate[0]);

        $rooms = explode(',', $this->options['room']);

        // Do not make group if there is no session planned on the next journal club day
        if ($session->type == 'none') {
            return false;
        }

        // Set the number of groups equal to the number of presentation for this day in case it exceeds it.
        $ngroups = max($AppConfig->max_nb_session, count($session->presids));

        // Get users list
        $Users = new Users($this->db);
        $users = array();
        foreach ($Users->getUsers(true) as $key=>$user) {
            $users[] = $user['username'];
        }

        $nusers = count($users); // total nb of users

        if ( ($nusers-$ngroups) < $ngroups || $session->type == "none") {return "Not enough members to create groups"; }

        $excludedusers = array();
        $pregroups = array();
        for ($i=0;$i<$ngroups;$i++) {
            $speaker = (isset($session->speakers[$i])) ? $session->speakers[$i]:'TBA';
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
        for ($i=0;$i<$ngroups;$i++) {
            $room = (!empty($rooms[$i])) ? $rooms[$i] : 'TBA';
            $presid = (isset($session->presids[$i])) ? $session->presids[$i]:'TBA';
            $group = $pregroups[$i];
            foreach ($groups[$i] as $mbr) {
                $group[] = array("member"=>$mbr,"role"=>false);
            }
            $assigned_groups[$i] = array('presid'=>$presid,'group'=>$group);

            // Add to the table
            foreach ($group as $mbr) {
                if(!$this->db->addcontent($this->tablename, array(
                    'groups' => $i,
                    'username' => $mbr['member'],
                    'role' => $mbr['role'],
                    'presid' => $presid,
                    'date' => $session->date,
                    'room' => $room
                ))) {
                    return false;
                };
            }
        }

        return "{$ngroups} groups created.";
    }

    /**
     *
     * @param null $username
     * @return mixed
     */
    public function makeMail($username=null) {
        $data = $this->getGroup($username);
        $data['group'] = $this->show($username);
        $publication = new Presentation($this->db, $data['presid']);
        $data['publication'] = $publication->showDetails(true);
        $content['body'] = self::renderSection($data);
        $content['title'] = 'Your Group assignment';
        return $content;
    }

    /**
     * Renders group information to be displayed in emails
     * @param array $data
     * @return string
     */
    public static function renderSection(array $data) {
        return "
        <p>Here is your group assignment for the session held on <b>{$data['date']}</b>.</p>
        <p>Your group will meet in room {$data['room']}.</p>
        <div style='display: inline-block; padding: 10px; margin: 0 auto 20px auto; background-color: rgba(255,255,255,1); width: 45%; min-width: 250px; vertical-align: top;'>
            " . $data['group'] . "
        </div>
        <div style='display: inline-block; padding: 10px; margin: 0 auto 20px auto; background-color: rgba(255,255,255,1); width: 45%; min-width: 250px; vertical-align: top;'>
            <div style='color: #444444; margin-bottom: 10px;  border-bottom:1px solid #DDD; font-weight: 500; font-size: 1.2em;'>
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
     * @param User $user
     * @param array $data
     * @return mixed
     */
    public static function renderMail($data, User $user) {
        $result['body'] = "
            <div style='width: 100%; margin: auto;'>
                <p>Hello <span style='font-weight: 600;'>{$user->firstname}/span>,</p>
                <p>Here is your assignment for our next journal club session that will be held on the
                {$data['date']} in room <b> {$data['room']}</b>.</p>
    
                <div style='display: block; vertical-align: top; margin: auto;'>
                    <div style='display: inline-block; padding: 10px; margin: 0 30px 20px 0;background-color: rgba(255,255,255,1);'>
                        " . $data['group'] . "
                    </div>
                    <div style='display: inline-block; padding: 10px; margin: auto; vertical-align: top; max-width: 60%; background-color: rgba(255,255,255,1);'>
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
     * @return array
     */
    public function getGroup($username) {
        $sql = "SELECT * FROM $this->tablename WHERE username='$username'";
        $data = $this->db->send_query($sql)->fetch_assoc();
        $groupusrs['members'] = array();
        $groupusrs['room'] = $data['room'];
        $groupusrs['date'] = $data['date'];
        $groupusrs['presid'] = $data['presid'];
        if (!empty($data)) {
            $sql = "SELECT * FROM $this->tablename WHERE groups='".$data['groups']."'";
            $req = $this->db->send_query($sql);
            $rows = array();
            while ($row = $req->fetch_assoc()) {
                $rows[] = $row;
            }
            
            foreach ($rows as $key=>$row) {
                $groupusrs['members'][$row['username']] = $row;
            }
        }

        return $groupusrs;
    }

    /**
     * Display user's group (profile page or in email)
     * @param bool $username
     * @return string
     */
    public function show($username=False) {
        if ($username === False) {
            $username = $_SESSION['username'];
        }
        $group = $this->getGroup($username);
        if (empty($group['members'])) {
            $content = 'No group has been made yet';
        } else {
            $u = 0;
            $content = "";
            foreach($group['members'] as $grpmember=>$info) {
                if ($grpmember == 'TBA') continue; // We do not send emails to fake users
                $role = $info['role'];
                $grpuser = new User($this->db,$grpmember);
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
                <div style='color: #444444; margin-bottom: 10px;  border-bottom:1px solid #DDD; font-weight: 500; font-size: 1.2em;'>
                    Your group
                </div>
                <div style='min-height: 50px; padding-bottom: 5px; margin: auto auto 0 auto;'>
                    {$content}
                </div>
            ";
    }
}


