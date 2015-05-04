<?php
/*
Copyright © 2014, Florian Perdreau
This file is part of Journal Club Manager.

Journal Club Manager is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Journal Club Manager is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with Journal Club Manager.  If not, see <http://www.gnu.org/licenses/>.
*/

require('../includes/boot.php');

class Groups extends AppPlugins {

    protected $table_data = array(
        "id"=>array("INT NOT NULL AUTO_INCREMENT",false),
        "groups"=>array("INT(2)",false),
        "username"=>array("CHAR(15)",false),
        "role"=>array("CHAR(10)",false),
        "presid"=>array("BIGINT(15)",false),
        "date"=>array("DATE",false),
        "primary"=>'id'
    );
    protected $tablename;
    public $name = "Groups";
    public $version = "0.9";
    public $page = 'profile';
    public $status = 'Off';
    public $installed = False;
    public $options = array(
        "width"=>200
    );

    /**
     * Constructor
     * @param DbSet $db
     */
    public function __construct(DbSet $db) {
        parent::__construct($db);
        $this->installed = $this->isInstalled();
        $this->tablename = $this->db->dbprefix.'_groups';
        if ($this->installed) {
            if ($this->db->tableExists($this->tablename)) {
                $this->get();
            } else {
                $this->delete();
            }
        }
    }

    /**
     * Check whether this cron job is registered to the database
     * @return bool|mysqli_result
     */
    public function install() {
        // Create corresponding table
        $table = new Table($this->db, "Groups", $this->table_data, 'groups');
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

        // 3: If new groups have been assigned, we send an email to every user
        if ($result !== false)
            $result = $this->mailing($result);
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
        $nextdate = $Sessions->getsessions(1);
        $session = new Session($db,$nextdate[0]);

        // Set the number of groups equal to the number of presentation for this day in case it exceeds it.
        $ngroups = $AppConfig->max_nb_session;

        // Get users list
        $Users = new Users($this->db);
        $users = $Users->getUsers();
        $nusers = count($users); // total nb of users

        if ($nusers <= $ngroups || $session->type == "none") {return false; }

        $excludedusers = array();
        $pregroups = array();
        for ($i=0;$i<$ngroups;$i++) {
            $speaker = (isset($session->speakers[$i])) ? $session->speakers[$i]:'TBA';
            $chair = (isset($session->chairs[$i]['chair'])) ? $session->chairs[$i]['chair']:'TBA';
            $pregroups[$i][] = array("member"=>$chair,"role"=>"chair");
            if ($chair != $speaker) {
                $pregroups[$i][] = array("member"=>$speaker,"role"=>"speaker");
            }
            $excludedusers[] = $speaker;
            $excludedusers[] = $chair;
        }
        $remainusers = array_values(array_diff($users,$excludedusers));

        // Shuffle the remaining users
        shuffle($remainusers);

        // Make groups
        $qtity = ceil(count($remainusers)/$ngroups); // nb of users per group
        $groups = array_chunk($remainusers,$qtity);

        // Assign presentation
        $assigned_groups = array();
        for ($i=0;$i<$ngroups;$i++) {
            $presid = (isset($session->presids[$i])) ? $session->presids[$i]:'TBA';
            $group = $pregroups[$i];
            foreach ($groups[$i] as $mbr) {
                $group[] = array("member"=>$mbr,"role"=>false);
            }
            $assigned_groups[$i] = array('presid'=>$presid,'group'=>$group);

            // Add to the table
            foreach ($group as $mbr) {
                $content = array(
                    'groups' => $i,
                    'username' => $mbr['member'],
                    'role' => $mbr['role'],
                    'presid' => $presid,
                    'date' => $session->date
                );
                $this->db->addcontent($this->tablename, $content);
            }
        }

        return $assigned_groups;
    }

    // Execute cron job
    /**
     * @param $assigned_groups
     * @return string
     */
    function mailing($assigned_groups) {
        global $Sessions, $db, $AppMail, $AppConfig;

        // Declare classes
        $nextdate = $Sessions->getsessions(1);
        $session = new Session($db,$nextdate[0]);
        $chairs = $session->chairs;

        $rooms = array("B.2.15","B.1.38");
        $nsent = 0;
        // Make email
        $string = "";

        for ($i=0;$i<$AppConfig->max_nb_session;$i++) {
            $groupinfo = $assigned_groups[$i];
            $presid = $groupinfo['presid'];
            $room = (isset($rooms[$i])) ? $rooms[$i]:'TBA';

            /** @var Presentation $pres */
            $pres = new Presentation($db,$presid);
            $type = ($pres->type !== '') ? ucfirst($pres->type):'TBA';
            $title = ($pres->title !== '') ? ucfirst($pres->title):'TBA';
            $authors = ($pres->authors !== '') ? ucfirst($pres->authors):'TBA';

            $group = $groupinfo['group'];
            $chair = (isset($chairs[$i]['chair'])) ? new User($db,$chairs[$i]['chair']):'TBA';
            $speaker = new User($db, $pres->orator);
            $speaker = ($speaker->username !== '') ? $speaker->fullname:'TBA';
            $chair = ($chair !== 'TBA') ? $chair->fullname:'TBA';

            // Get file list for this presentation
            $filelist = $pres->link;
            $filecontent = "";
            foreach ($filelist as $file) {
                $ext = explode('.',$file);
                $ext = strtoupper($ext[1]);
                $urllink = $AppConfig->site_url."uploads/".$file;
                $filecontent .= "<div style='display: inline-block; height: 15px; line-height: 15px;
                    text-align: center; padding: 5px; white-space: pre-wrap; min-width: 40px; width: auto;
                    margin: 5px; cursor: pointer; background-color: #bbbbbb; font-weight: bold;'>
                    <a href='$urllink' target='_blank'>$ext</a></div>";
            }


            // Display details about this presentation
            if ($type !== 'TBA') {
                $presentation_desc = "
                <div style='display: block; position: relative; margin: 0 0 5px; text-align: center; height: 20px; line-height: 20px; width: 100px; background-color: #555555; color: #FFF; padding: 5px;'>
                    $type
                </div>
                <div style='width: 95%; margin: auto; padding: 5px 10px 0 10px; background-color: rgba(250,250,250,1); border-bottom: 5px solid #aaaaaa;'>
                    <span style='font-weight: bold;'>Title:</span> $title<br>
                    <div style='display: inline-block; margin-left: 0;'><b>Authors:</b> $authors</div>
                    <div style='display: inline-block; float:right;'><b>Speaker:</b> $speaker</div>
                    <div style='margin-left: 30px; display: inline-block;'><b>Chair:</b> $chair</div>
                </div>
                <div style='width: 95%; text-align: justify; margin: auto; background-color: #eeeeee; padding: 10px;'>
                    <span style='font-style: italic; font-size: 13px;'>$pres->summary</span>
                </div>
                <div style='display: block; text-align: justify; width: auto; min-height: 0; height: auto; margin: auto; background-color: #444444;'>
                    $filecontent
                </div>
                ";
            } else {
                $submit_url = $AppConfig->site_url.'index.php?page=submission&op=new';
                $presentation_desc = "
                <div style='width: 95%; text-align: justify; margin: auto; background-color: #eeeeee; padding: 10px;'>
                There is no presentation assigned to your group yet. <a href='$submit_url' target='_blank'>Be the first!</a></div>";
            }

            $pubcontent = "
                <div style='margin: auto; border: 1px solid #aaaaaa;'>
                    <div style='background-color: #BE4141; color: #eeeeee; padding: 5px; text-align: left; font-weight: bold; font-size: 16px;'>
                        Your Group Presentation
                    </div>
                    <div style='width: 100%; min-height: 50px; padding-bottom: 5px; margin: auto auto 0 auto; background-color: rgba(255,255,255,.5); border: 1px solid #bebebe;'>
                        $presentation_desc
                     </div>
                </div>";

            foreach($group as $mbr) {
                $username = $mbr['member'];
                if (empty($username) || $username == 'TBA') continue; // We do not send emails to fake users
                // Get the user's group
                $groupcontent = $this->show($username);

                $user = new User($db,$username);
                $content = "
                    <div style='width: 95%; margin: auto; font-size: 16px;'>
                        <p>Hello <span style='font-weight: 600;'>$user->firstname</span>,</p>
                        <p>Here is your assignment for our next journal club session that will be held on the
                        $session->date in room <b>$room</b>.</p>
                        <p>Cheers,<br>The Journal Club Team</p>
                        <div style='display: block; margin: auto; width: auto;'>
                            <div style='display: inline-block; width: 20%; border: 1px solid #aaaaaa;'>
                                $groupcontent
                            </div>
                            <div style='display: inline-block; margin-left: 5%; vertical-align: top; width: 70%;'>
                                $pubcontent
                            </div>
                        </div>
                    </div>
                    ";

                $body = $AppMail -> formatmail($content);
                $subject = "Your group assignment - $session->date";
                //print($subject); echo "<br>"; print($body); exit;
                if ($AppMail->send_mail($user->email,$subject,$body)) {
                    $nsent += 1;
                } else {
                    $string .= "ERROR message not sent to $user->email.";
                }
            }
        }

        $string .= "Message sent to $nsent users.";
        return $string;
    }

    /**
     * Get user's group
     * @param $username
     * @return array
     */
    public function getGroup($username) {
        $sql = "SELECT groups FROM $this->tablename WHERE username='$username'";
        $req = $this->db->send_query($sql);
        $data = mysqli_fetch_assoc($req);
        $groupusrs = array();
        if (!empty($data)) {
            $sql = "SELECT groups,username,role FROM $this->tablename WHERE groups='".$data['groups']."'";
            $req = $this->db->send_query($sql);
            while ($row = mysqli_fetch_assoc($req)) {
                $groupusrs[$row['username']] = array('groups'=>$row['groups'],'role'=>$row['role']);
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
        if (empty($group)) {
            $content = 'No group has been made yet';
        } else {
            $u = 0;
            $content = "";
            foreach($group as $grpmember=>$info) {
                if ($grpmember == 'TBA') continue; // We do not send emails to fake users
                $role = $info['role'];
                $grpuser = new User($this->db,$grpmember);
                $fullname = ucfirst(strtolower($grpuser->firstname))." ".ucfirst(strtolower($grpuser->lastname));
                $color = ($u % 2 == 0) ? '#dddddd':'#bbbbbb';
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
        $width = $this->options['width']."px";
        return "
            <div style='width: $width; margin: auto; vertical-align: top;'>
            <div style='display: block; position: relative; background-color: rgba(187,81,81,1); color: #eeeeee;
            width: 50%; height: 20px; line-height: 20px; padding: 5px; margin: 20px auto -10px auto; text-align: center;
            font-weight: 300; font-size: 16px; z-index: 11; -webkit-box-shadow: 0px 0px 1px 1px rgba(187,187,187,1);
            -moz-box-shadow: 0px 0px 1px 1px rgba(187,187,187,1); box-shadow: 0px 0px 1px 1px rgba(187,187,187,1);'>
            My Group</div>
            <div style='font-size: 12px; display: block; margin: auto;text-align: justify; padding: 20px;
            background-color: rgba(200,200,200,.1); -webkit-box-shadow: 0px 0px 1px 1px rgba(187,187,187,1);
            -moz-box-shadow: 0px 0px 1px 1px rgba(187,187,187,1); box-shadow: 0px 0px 1px 1px rgba(187,187,187,1);'>
            $content</div>
            </div>";
    }
}


