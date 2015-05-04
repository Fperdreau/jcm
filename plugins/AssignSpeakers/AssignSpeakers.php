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

class AssignSpeakers extends AppPlugins {

    protected $table_data = array(
        "id" => array("INT NOT NULL AUTO_INCREMENT", false),
        "date" => array("DATE", false),
        "chair" => array("VARCHAR(200) NOT NULL", false),
        "presid" => array("BIGINT(15)", false),
        "primary" => "id");
    protected $tablename;

    public $id;
    public $date;
    public $chair='TBA';
    public $presid=null;

    public $name = "AssignSpeakers";
    public $version = "0.9";
    public $page = False;
    public $status = 'Off';
    public $installed = False;
    public $options = array();

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
        $table = new Table($this->db, "AssignSpeakers", $this->table_data, 'chairs');
        $table->setup();

        // Register the plugin in the db
        $class_vars = get_class_vars('AssignSpeakers');
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
     * Make object
     * @param $date
     * @param $chair
     * @param null $presid
     * @return bool|mysqli_result
     */
    public function addSpeaker($date, $chair='TBA', $presid=null) {
        $this->date = $date;
        $this->chair = $chair;
        $this->presid = $presid;
        $content = array(
            'date'=>$this->date,
            'chair'=>$this->chair,
            'presid'=>$this->presid
        );
        return $this->db->addcontent($this->tablename,$content);
    }

    /**
     * Update object with information from the db
     * @param $ref
     * @param $value
     * @return bool
     */
    public function getSpeaker($ref,$value) {
        $sql = "SELECT * FROM $this->tablename WHERE $ref='$value'";
        $req = $this->db->send_query($sql);
        $row = mysqli_fetch_assoc($req);
        if (!empty($row)) {
            foreach ($row as $ref=>$value) {
                $this->$ref = htmlspecialchars_decode($value);
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Update Chairs table
     * @return bool
     */
    public function updateSpeaker() {
        $class_vars = get_class_vars("Chairs");
        $content = $this->parsenewdata($class_vars,array(),array('id'));
        if (!$this->db->updatecontent($this->tablename,$content,array("id"=>$this->id))) {
            return false;
        }
        return true;
    }

    /**
     * Delete chair from the db
     */
    public function deleteSpeaker($ref,$value) {
        return $this->db->deletecontent($this->tablename,$ref,$value);
    }

    /**
     * Get previous chairs
     * @param $organizers
     * @param $type
     * @return array
     */
    public function getPrevious($organizers, $type) {
        $sql = "SELECT chair,date FROM $this->tablename";
        $req = $this->db->send_query($sql);
        $list = array();
        $session = new Session($this->db);

        while ($row=mysqli_fetch_assoc($req)) {
            $chair = $row['chair'];
            $session->get($row['date']);
            if ($session->type == $type) {
                if (!in_array($chair, $list) && $chair != "TBA") {
                    $list[] = $chair;
                }
                $diff = array_values(array_diff($organizers,$list));
                if (empty($diff)) {
                    $list = array();
                }
            }

        }
        return $list;
    }

    /**
     * Pseudo randomly choose a chairman
     * @param $sessionid
     * @param null $speaker
     * @return string
     */
    public function Assign($sessionid,$speaker=null) {
        /** @var Session $session */
        $session = new Session($this->db,$sessionid);

        /** @var AppConfig $AppConfig */
        $AppConfig = new AppConfig($this->db);

        /** Get speakers planned for this session */
        if ($speaker != null && $session->type == 'Journal Club') {
            // Different rule for journal club sessions: the speaker is the chair
            $chair = $speaker;
        } else {

            /** Get chairmen planned for this session */
            $exclude = array();
            foreach ($session->chairs as $c => $info) {
                $exclude[] = $info['chair'];
            }
            $sessionChair = $exclude;

            /** Get list of previous chairmen*/
            $prevchairs = $AppConfig->session_type[$session->type];


            /** Get list of organizers */
            $Users = new Users($this->db);
            $organizers = $Users->getadmin();
            $chairs = array();
            if (!empty($organizers)) {
                foreach ($organizers as $organizer) {
                    $chairs[] = $organizer['username'];
                }
            }

            $prevchairs = $this->getPrevious($chairs,$session->type);
            foreach ($prevchairs as $prev) {
                $exclude[] = $prev;
            }

            if (empty($chairs)) {
                /** If no users have the organizer status, the chairman is to be announced.*/
                /** To Be Announced as a default */
                $chair = 'TBA';
                /** We start a new list of previous chairmen*/
                $prevchairs = array();
            } else {
                /** We randomly pick a chairman among organizers who have not chaired a session yet,
                 * apart from the other chairmen of this session.
                 */
                $possiblechairs = array_values(array_diff($chairs, $exclude));
                if (!empty($possiblechairs)) {
                    $ind = rand(0, count($possiblechairs) - 1);
                    $chair = $possiblechairs[$ind];
                } else {
                    /** Otherwise, if all organizers already have been chairman once,
                     * we randomly pick one among all the organizers,
                     * apart from the other chairmen of this session
                     */
                    $possiblechairs = array_values(array_diff($chairs, $sessionChair));

                    $ind = rand(0, count($possiblechairs) - 1);
                    $chair = $possiblechairs[$ind];

                    /** We start a new list of previous chairmen */
                    $prevchairs = array();
                }
            }
        }
        // Update the previous chairmen list
        $prevchairs[] = $chair;
        $AppConfig->session_type[$session->type] = $prevchairs;
        $AppConfig->update();

        return $chair;
    }
}


