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

class AssignSpeakers extends AppCron {
    /**
     * Assign Speakers for the next n sessions
     * @return bool
     */

    public $name = 'AssignSpeakers';
    public $path;
    public $status = 'Off';
    public $installed = False;
    public $time;
    public $dayName;
    public $dayNb;
    public $hour;
    public $options=array('nbsessiontoplan'=>10);

    /**
     * Constructor
     * @param DbSet $db
     */
    public function __construct(DbSet $db) {
        parent::__construct($db);
        $this->path = basename(__FILE__);
        $this->time = AppCron::parseTime($this->dayNb, $this->dayName, $this->hour);
    }

    /**
     * Register the plugin into the database
     * @return bool|mysqli_result
     */
    public function install() {
        $class_vars = get_class_vars($this->name);
        return $this->make($class_vars);
    }

    /**
     * Get previous chairs
     * @param $organizers
     * @param $type
     * @return array
     */
    public function getPreviousSpeakers($organizers, $type) {
        $Presentation = new Presentation($this->db);
        $sql = "SELECT orator,date FROM ".$Presentation->tablename;
        $req = $this->db->send_query($sql);
        $list = array();
        $session = new Session($this->db);

        while ($row=mysqli_fetch_assoc($req)) {
            $chair = $row['orator'];
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
        $prevSpeakers = $this->getPreviousSpeakers($usersList,$session->type);

        // Update exclusion list
        $exclude = array_merge($exclude,$prevSpeakers);

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

    /**
     * Run scheduled task: assign speaker to the next sessions
     * @return bool|string
     */
    public function run() {
        global $db, $Sessions;

        var_dump($this->options['nbsessiontoplan']);exit;
        // Get sessions dates
        $jc_days = $Sessions->getjcdates($this->options['nbsessiontoplan']);
        $created = 0;
        $updated = 0;
        foreach ($jc_days as $day) {

            // If session does not exist yet, let's create it
            $session = new Session($db, $day);
            if (!$session->dateexists($day)) {
                $session->make();
            }

            if ($session->type !== "none") {
                // If a session is planned for this day, we assign X speakers (1 speaker by presentation)
                for ($p = $session->nbpres; $p < $session->max_nb_session; $p++) {
                    // Get speaker
                    $Newspeaker = $this->getSpeakers($day);
                    $speaker = new User($this->db,$Newspeaker);

                    // Assign a presentation to the new speaker
                    $Presentation = new Presentation($this->db);
                    $post = array(
                        'title'=>'TBA',
                        'date'=>$day,
                        'type'=>'research',
                        'username'=>$speaker->username,
                        'orator'=>$speaker->fullname);

                    // Create presentation
                    $Presentation->make($post);

                    $updated += 1;

                    // Update session info
                    $session->get();
                }
            }
        }
        $result = "$created chair(s) created<br>$updated chair(s) updated";
        $this->logger("$this->name.txt",$result);
        return $result;
    }
}
