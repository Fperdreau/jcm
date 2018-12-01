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

 namespace Plugins;

 use includes\Plugin;
 use includes\Session;
 use includes\Assignment;
 use includes\Users;
 use includes\Presentation;

/**
 * Class autoAssignment
 *
 * Plugins that handles speaker assignment routines
 */
class AutoAssignment extends Plugin
{

    /**
     * @var string
     */
    public $name = "AutoAssignment";

    /**
     * @var string
     */
    public $version = "1.1.1";

    /**
     * Plugin's description
     */
    public $description = "Automatically assigns members of the JCM (who agreed upon being assigned by settings 
    the corresponding option on their profile page) as speakers to the future sessions. 
    The number of sessions to plan in advance can be set in the plugin's settings.";
    
    /**
     * @var array
     */
    public $options = array(
        'nbsessiontoplan'=>array(
            'options'=>array(),
            'value'=>10),
        'method'=>array(
            'options'=>array(
                'random'=>'random',
                'max'=>'max',
                'score'=>'score'
            ),
            'value'=>'random'
        )
    );

    /**
     * @var Session $session
     */
    private static $session;

    /**
     * @var Assignment $Assignment
     */
    private static $Assignment;


    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        self::getSession();
        self::getAssignement();
    }

    /**
     * Get session instance
     */
    public static function getSession()
    {
        if (is_null(self::$session)) {
            self::$session = new Session();
        }
        return self::$session;
    }

    /**
     * Get assignment instance
     * @return Assignment
     */
    private static function getAssignement()
    {
        if (is_null(self::$Assignment)) {
            self::$Assignment = new Assignment();
        }
        return self::$Assignment;
    }

    /**
     * Pick members with the least number of presentations and longest time since last presentation
     *
     * @param array $session: session info
     * @param array $preAssigned: list of preassigned members (speakers)
     * @return array
     */
    private function getAssignable(array $session, array $preAssigned)
    {
        // Get maximum number of assignments for the session type
        $session_type = Assignment::prettyName($session['type'], true);
        $max = self::$Assignment->getMax($session_type);

        $scoreQuery = "
            SELECT a.username, (1/a.norm_assign) + d.norm_duration AS score
            FROM (
                /* Get normalized number of presentations per member */
                SELECT j.username, j.journal_club/max_a.max_assign AS norm_assign
                FROM {$this->db->getAppTables('Assignment')} j
                JOIN (
                    SELECT username, {$session_type}, MAX({$session_type}) AS max_assign
                    FROM {$this->db->getAppTables('Assignment')}
                ) max_a
            ) a
            LEFT JOIN (
                /* Get normalized time since last presentation per member */
                SELECT p.username, p.since_last/max_d.max_duration AS norm_duration
                FROM (
                    /* Get time since last presentation per member */
                    SELECT pres.username, MIN(pres.duration) AS since_last
                    FROM (
                        /* Get time since last presentation */
                        SELECT p.username, TIMESTAMPDIFF(SECOND, p.date, NOW()) AS duration
                        FROM {$this->db->getAppTables('Presentation')} p
                        JOIN {$this->db->getAppTables('Session')} s
                        ON p.session_id = s.id
                        WHERE s.type = '{$session['type']}'
                    ) pres
                    GROUP BY pres.username
                ) p
                JOIN (
                    /* Get maximum time since last presentation across all members*/
                    SELECT MAX(pres.duration) AS max_duration
                    FROM (
                        /* Get time since last presentation */
                        SELECT TIMESTAMPDIFF(SECOND, p.date, NOW()) AS duration
                        FROM {$this->db->getAppTables('Presentation')} p
                        JOIN {$this->db->getAppTables('Session')} s
                        ON p.session_id = s.id
                        WHERE s.type = '{$session['type']}'
                    ) pres
                ) max_d
            ) d
            ON a.username = d.username
        ";

        $speakersArray = !empty($preAssigned) ? 'AND u.username NOT IN (' . self::toString($preAssigned) . ')' : null;
        $sql = "
            /* Filter available members on session */
            SELECT DISTINCT(sc.username) AS username, sc.score AS score
            FROM (
                {$scoreQuery}
            ) sc
            LEFT JOIN " . $this->db->getAppTables('Users') . " u
            ON u.username=sc.username
            WHERE u.assign=1 {$speakersArray}
                AND u.username IN (
                    SELECT username
                    FROM " . $this->db->getAppTables('Availability') . " a
                    WHERE a.date!='{$session['date']}'
                )
            ORDER BY score DESC
            LIMIT 1
        ";

        $req = $this->db->sendQuery($sql);
        $user = null;
        while ($row = $req->fetch_assoc()) {
            $user = $row['username'];
        }
        return $user;
    }

    /**
     * Randomly pick members with the least number of presentations and available on session
     *
     * @param array $session: session info
     * @param array $preAssigned: list of preassigned members (speakers)
     * @return array
     */
    private function getAssignableMax(array $session, array $preAssigned)
    {
        // Prettify session type
        $session_type = Assignment::prettyName($session['type'], true);

        // Get maximum number of assignments
        $max = self::$Assignment->getMax($session_type);

        $allUsers = self::$Assignment->getAssignable($session_type, $max+1, $session['date']);
        $N = count($allUsers);

        // Get assignable users
        $assignable = array();
        $n = 0;
        while (empty($assignable) & $n <= $N) {
            $usersList = self::$Assignment->getAssignable($session_type, $max, $session['date']);
            $max += 1;
            $n += 1;

            // exclude the already assigned speakers for this session from the list of possible speakers
            $assignable = array_values(array_diff($usersList, $preAssigned));
        }

        if (!empty($assignable)) {
            $ind = rand(0, count($assignable) - 1);
            $newSpeaker = $assignable[$ind];
        } else {
            return null;
        }
    }

    /**
     * Randomly pick members available on session
     *
     * @param array $session: session info
     * @param array $preAssigned: list of preassigned members (speakers)
     * @return array
     */
    private function getAssignableRand(array $session, array $preAssigned)
    {
        // Prettify session type
        $session_type = Assignment::prettyName($session['type'], true);

        $speakersArray = !empty($preAssigned) ? 'AND u.username NOT IN (' . self::toString($preAssigned) . ')' : null;
        $sql = "
            SELECT DISTINCT(u.username) 
            FROM " . $this->db->getAppTables('Users') . " u
            INNER JOIN " . $this->db->getAppTables('Assignment') . " p
            ON u.username=p.username
            WHERE u.assign=1 {$speakersArray}
                AND u.username IN (
                    SELECT username
                    FROM " . $this->db->getAppTables('Availability') . " a
                    WHERE a.date!='{$session['date']}'
                )
            ";
        $req = $this->db->sendQuery($sql);

        // Fetch results
        $assignable = array();
        while ($row = $req->fetch_assoc()) {
            $assignable[] = $row['username'];
        }

        if (!empty($assignable)) {
            $N = count($assignable);
            $ind = rand(0, $N - 1);
            return $assignable[$ind];
        } else {
            return null;
        }
    }

    /**
     * Get new speaker
     *
     * @param array $session: session data
     * @param array $plannedSpeakers: List of planned speakers for this session
     * @return string|bool
     */
    private function getSpeaker(array $session, $plannedSpeakers)
    {
        // Get speakers planned for this session
        $speakers = array_diff($plannedSpeakers, array('TBA'));
        $speakers = array_filter($speakers, 'strlen');

        // Get all assignable users
        switch ($this->options['method']['value']) {
            case 'max':
                $speaker = $this->getAssignableMax($session, $speakers);
                break;
            case 'score':
                $speaker = $this->getAssignableScore($session, $speakers);
                break;
            case 'random':
                $speaker = $this->getAssignableRand($session, $speakers);
                break;
        }

        if (!is_null($speaker)) {
            // Update the assignment table
            $session_type = Assignment::prettyName($session['type'], true);
            if (!self::$Assignment->updateTable($session_type, $speaker, true)) {
                return false;
            } else {
                return $speaker;
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * Assigns speakers to the $nb_session future sessions
     * @param null|int $nb_session: number of sessions
     * @return mixed
     */
    public function assign($nb_session = null)
    {
        // Number of session to plan
        $nb_session = (is_null($nb_session)) ? $this->options['nbsessiontoplan']['value']:$nb_session;

        // Initialize output
        $result = array('status'=>true, 'msg'=>null, 'content'=>null);
        $created = 0;
        $updated = 0;
        $assignedSpeakers = array();

        // Check if there is enough users
        $User = new Users();
        $usersList = $User->allButAdmin();
        if (empty($usersList)) {
            $result['msg'] = 'There is not enough assignable members';
            $result['status'] = false;
            return $result;
        };

        // Update assignment table
        self::$Assignment->check();

        $Presentation = new Presentation();
        $Users = new Users();
        
        // Loop over sessions
        foreach ($this->getSession()->getUpcoming(intval($nb_session)) as $key => $item) {
            // If session does not exist yet, we skip it
            if ($item['type'] == 'none') {
                continue;
            }

            // If a session is planned for this day, we assign 1 speaker by slot
            for ($p=0; $p<$item['slots']; $p++) {
                // Update info array
                $session = $this->getSession()->getInfo(array('id'=>$item['id']));

                // Get list of planned speakers
                $plannedSpeakers = $session['usernames'];

                // If there is already a presentation planned for this day,
                // check if the speaker is a real member, otherwise
                // we will assign a new one
                if (isset($session['presids'][$p])) {
                    $PresentationId = $session['presids'][$p];
                    $doAssign = empty($session['usernames'][$p]);
                    $new = false;
                } else {
                    $doAssign = true;
                    $new = true;
                }

                // Skip if no assignment is needed
                if (!$doAssign) {
                    continue;
                }

                // Get & assign new speaker
                $newSpeaker = $this->getSpeaker($session, $session['usernames']);
                if (!$newSpeaker) {
                    $result['status'] = false;
                    $result['msg'] = 'Could not assign speakers';
                    return $result;
                }

                // Get speaker information
                $speaker = $Users->get(array('username'=>$newSpeaker));

                // Create/Update presentation
                if ($new) {
                    $result = $Presentation->edit(array(
                        'id'=>'false',
                        'title'=>'TBA',
                        'date'=>$session['date'],
                        'type'=>'paper',
                        'username'=>$speaker['username'],
                        'orator'=>$speaker['username'],
                        'session_id'=>$session['id']
                    ));
                    $PresentationId = $result['id'];
                    if ($result['status']) {
                        $created += 1;
                    }
                } else {
                    $post = array(
                        'date'=>$session['date'],
                        'username'=>$speaker['username'],
                        'orator'=>$speaker['username']
                    );
                    if ($result['status'] = $Presentation->update($post, array('id'=>$PresentationId))) {
                        $updated += 1;
                    }
                }
                
                // Notify assigned user
                $info = array(
                    'speaker'=>$speaker['username'],
                    'type'=>$session['type'],
                    'presid'=>$PresentationId,
                    'date'=>$session['date']
                );

                if ($newSpeaker !== 'TBA') {
                    \includes\SessionManager::notifyUpdate(
                        new Users($speaker['username']),
                        $info
                    );
                }

                $assignedSpeakers[$session['date']][] = $info;
            }
        }
        $result['content'] = $assignedSpeakers;
        $result['msg'] = "{$created} chair(s) created<br>{$updated} chair(s) updated";
        return $result;
    }

    /**
     * Convert array to comma separated string
     *
     * @param array $data
     * @return string
     */
    private static function toString(array $data)
    {
        $data = array_map(
            function ($x) {
                return "'{$x}'";
            },
            $data
        );
        return implode(',', $data);
    }
}
