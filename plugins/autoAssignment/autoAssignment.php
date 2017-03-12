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
class autoAssignment extends AppPlugins {

    /**
     * @var string
     */
    public $name = "autoAssignment";

    /**
     * @var string
     */
    public $version = "1.1.1";
    
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

    /**
     * @var Assignment
     */
    private static $Assignment;

    public static $description = "Automatically assigns members of the JCM (who agreed upon being assigned by settings 
    the corresponding option on their profile page) as speakers to the future sessions. 
    The number of sessions to plan in advance can be set in the plugin's settings.";

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();

        $this->installed = $this->isInstalled();
        $this->tablename = $this->db->config['dbprefix'] . '_' . strtolower($this->name);

        if ($this->installed) {
            $this->getInfo();
            $this->getSession();
            $this->getAssignement();
        }
    }

    /**
     * Get session instance
     */
    public function getSession() {
        if (is_null(self::$session)) {
            self::$session = new Session($this->db);
        }
        return self::$session;
    }

    /**
     * Get assignment instance
     * @return Assignment
     */
    private function getAssignement() {
        if (is_null(self::$Assignment)) {
            self::$Assignment = new Assignment($this->db);
        }
        return self::$Assignment;
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
        $session_type = Assignment::prettyName($session->type, true);

        // Get maximum number of presentations
        $max = self::$Assignment->getMax($session_type);

        // Get speakers planned for this session
        $speakers = array_diff($session->speakers, array('TBA'));

        // Get assignable users
        $assignable_users = array();
        while (empty($assignable_users)) {
            $assignable_users = self::$Assignment->getAssignable($session_type, $max, $session->date);
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
        if (!self::$Assignment->updateTable($session_type, $newSpeaker, true)) {
            return false;
        }

        return $newSpeaker;
    }


    /**
     * Assigns speakers to the $nb_session future sessions
     * @param null|int $nb_session: number of sessions
     * @return mixed
     */
    public function assign($nb_session=null) {
        $this->getInfo();
        $nb_session = (is_null($nb_session)) ? $this->options['nbsessiontoplan']['value']:$nb_session;

        // Get future sessions dates
        $jc_days = $this->getSession()->getJcDates(intval($nb_session));

        $created = 0;
        $updated = 0;
        $assignedSpeakers = array();

        // Check if there is enough users
        $User = new Users($this->db);
        $usersList = $User->getUsers(true);
        if (empty($usersList)) {
            $result['msg'] = 'There is not enough assignable members';
            $result['status'] = false;
            return $result;
        };

        // Update assignment table
        self::$Assignment->check();
        
        // Loop over sessions
        foreach ($jc_days as $day) {

            // If session does not exist yet, we create a new one
            $session = new Session($this->db, $day);

            // Do nothing if nothing is planned on that day
            if ($session->type === "none") continue;

            // If a session is planned for this day, we assign 1 speaker by slot
            for ($p=0; $p<self::$session->slots; $p++) {

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
                    $result['status'] = false;
                    $result['msg'] = 'Could not assign speakers';
                    return $result;
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
                    if ($Presentation->update($post, array('id_pres'=>$Presentation->id_pres))) {
                        $updated += 1;
                    }
                }

                // Update session info
                $session->getInfo();
                
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

}