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
     * @param AppDb $db
     */
    public function __construct(AppDb $db) {
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
     * Get previous Speakers
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
            $speaker = $row['orator'];
            $session->get($row['date']);
            if ($session->type == $type) {
                if (!in_array($speaker, $list) && $speaker != "TBA") {
                    $list[] = $speaker;
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
        $this->get();

        // Get sessions dates
        $jc_days = $Sessions->getjcdates(intval($this->options['nbsessiontoplan']));
        $created = 0;
        $updated = 0;
        $assignedSpeakers = array();
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
                        'type'=>'paper',
                        'username'=>$speaker->username,
                        'orator'=>$speaker->username);

                    // Create presentation
                    $Presentation->make($post);

                    $updated += 1;

                    // Update session info
                    $session->get();

                    $assignedSpeakers[$speaker->username] = array('date'=>$day,'type'=>$session->type);
                }
            }
        }
        $result = "$created chair(s) created<br>$updated chair(s) updated";

        // Notify speakers of their assignments
        if (!empty($assignedSpeakers)) {
            $result .= $this->noticing($assignedSpeakers);
        }
        $this->logger("$this->name.txt",$result);
        return $result;
    }

    /**
     * Send an email to users who have been assigned to a presentation
     * @param $assignedSpeakers
     * @return string
     */
    public function noticing($assignedSpeakers) {
        // Declare classes
        global $AppMail,$db;

        $nsuccess = 0;
        $nuser = count($assignedSpeakers);
        if (!empty($assignedSpeakers)) {
            foreach ($assignedSpeakers as $userName=>$info) {
                $user = new User($db,$userName);
                $content = $this->makeMail($user,$info);
                $body = $AppMail->formatmail($content['body']);
                $subject = $content['subject'];
                if ($AppMail->send_mail($user->email,$subject, $body)) {
                    $nsuccess +=1;
                }
            }
        }
        return "Notifications sent: $nsuccess/$nuser";
    }

    /**
     * Make reminder notification email (including only information about the upcoming session)
     * @return mixed
     */
    public function makeMail($user, $info) {
        $sessionType = $info['type'];
        $date = $info['date'];
        $dueDate = date('Y-m-d',strtotime($date.' - 1 week'));
        $AppConfig = new AppConfig($this->db);
        $contactURL = $AppConfig->site_url."index.php?page=contact";
        $content['body'] = "
            <div style='width: 95%; margin: auto; font-size: 16px;'>
                <p>Hello $user->fullname,</p>
                <p>You have been automatically invited to present at a <span style='font-weight: 500'>$sessionType</span> session on the <span style='font-weight: 500'>$date</span>.</p>
                <p>Please, submit your presentation on the Journal Club Manager before the <span style='font-weight: 500'>$dueDate</span>.</p>
                <p>If you think you will not be able to present on the assigned date, please <a href='$contactURL'>contact</a> on the organizers as soon as possible.</p>
            </div>

            <div style='width: 95%; margin: 20px auto; font-size: 16px;'>
                <p>Cheers,<br>
                The Journal Club Team</p>
            </div>
        ";
        $content['subject'] = "Invitation to present on the $date";
        return $content;
    }
}
