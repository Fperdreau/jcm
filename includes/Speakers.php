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

class Speakers {

    private $db;

    public function __construct(DbSet $db) {
        $this->db = $db;
    }

    /**
     * Make object
     * @param $date
     * @param $chair
     * @param null $presid
     * @return bool|mysqli_result
     */
    public function make($date, $chair='TBA', $presid=null) {
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
    public function get($ref,$value) {
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
     * Update Speakers table
     * @return bool
     */
    public function update() {
        $class_vars = get_class_vars("Speakers");
        $content = $this->parsenewdata($class_vars,array(),array('id'));
        if (!$this->db->updatecontent($this->tablename,$content,array("id"=>$this->id))) {
            return false;
        }
        return true;
    }

    /**
     * Delete chair from the db
     */
    public function delete($ref,$value) {
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
     * @return string
     */
    public function getSpeakers($sessionid) {
        /** @var Session $session */
        $session = new Session($this->db,$sessionid);

        // Get speakers planned for this session
        $speakers = $session->speakers;
        $exclude = $speakers;

        // Get list of users
        $Users = new Users($this->db);
        $usersList = $Users->getUsers();

        // get previous speakers
        $prevSpeakers = $this->getPrevious($usersList,$session->type);

        // Update exclusion list
        $exclude = array_push($exclude,$prevSpeakers);

        if (empty($usersList)) {
            /** If no users have the organizer status, the chairman is to be announced.*/
            /** To Be Announced as a default */
            $chair = 'TBA';
            /** We start a new list of previous chairmen*/
            $prevSpeakers = array();
        } else {
            /** We randomly pick a chairman among organizers who have not chaired a session yet,
             * apart from the other chairmen of this session.
             */
            $possiblechairs = array_values(array_diff($usersList, $exclude));
            if (!empty($possiblechairs)) {
                $ind = rand(0, count($possiblechairs) - 1);
                $chair = $possiblechairs[$ind];
            } else {
                /** Otherwise, if all organizers have already been speaker once,
                 * we randomly pick one among all the organizers,
                 * apart from the other speakers of this session
                 */
                $possiblechairs = array_values(array_diff($usersList, $speakers));
                $ind = rand(0, count($possiblechairs) - 1);
                $chair = $possiblechairs[$ind];

                /** We start a new list of previous chairmen */
                $prevSpeakers = array();
            }
        }

        // Update the previous chairmen list
        $prevSpeakers[] = $chair;
        return $chair;
    }

}