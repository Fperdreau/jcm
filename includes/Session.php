<?php
/**
 * File for class Session
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

namespace includes;

use includes\BaseModel;

/**
 * Class Session
 */
class Session extends BaseModel
{

    /**
     * Session settings
     * @var $settings array
     *      jc_day: default journal club day
     *      room: default meeting room
     *      jc_time_from: default starting time
     *      jc_time_to: default ending time
     *      defaults: default session types
     *      default_type: default session type
     *      types: session types (defaults + customs)
     */
    protected $settings = array(
        'jc_day'=>null,
        'room'=>null,
        'jc_time_from'=>'17:00',
        'jc_time_to'=>'18:00',
        'max_nb_session'=>1,
        'defaults'=>array('Journal Club', 'Business Meeting'),
        'default_type'=>'Journal Club',
        'types'=>array('Journal Club', 'Business Meeting')
    );

    public $id;
    public $date;
    public $status = "FREE";
    public $start_time;
    public $end_time;
    public $type = 'none';
    public $nbpres = 0;
    public $room;
    public $slots;
    public $repeated = false;
    public $frequency;
    public $start_date;
    public $end_date;
    public $to_repeat;
    public $event_id;
    public $recurrent;
    public $presids = array();
    public $speakers = array();
    private static $default = array();

    /**
     * Constructor
     * @param null $id: Session id
     */
    public function __construct($id = null)
    {
        parent::__construct();

        // Set types to defaults before loading custom information
        $this->settings['types'] = $this->settings['defaults'];

        // Get defaults properties
        $this->getDefaults();

        if (!is_null($id)) {
            $this->getInfo(array('id'=>$id));
        }
    }

    /* Controller */

    /**
     * Register into Reminder table
     */
    public static function registerReminder()
    {
        $reminder = new ReminderMaker();
        $reminder->register(get_class());
    }

    /**
     * Register into DigestMaker table
     */
    public static function registerDigest()
    {
        $DigestMaker = new DigestMaker();
        $DigestMaker->register(get_class());
    }

    /**
     *
     * @param null $username
     * @return mixed
     */
    public function makeMail($username = null)
    {
        // Get future presentations
        $content['body'] = $this->showNextSession(true);
        $content['title'] = 'Session Information';
        return $content;
    }

    /**
     * Get default session settings
     * @return array: default settings
     */
    private function getDefaults()
    {
        self::$default = array(
            'date'=>self::getJcDates($this->settings['jc_day'], 1)[0],
            'frequency'=>null,
            'slots'=>$this->settings['max_nb_session'],
            'type'=>$this->settings['default_type'],
            'start_time'=>$this->settings['jc_time_from'],
            'end_time'=>$this->settings['jc_time_to'],
            'room'=>$this->settings['room'],
            'to_repeat'=>0
        );
        return self::$default;
    }

    public function showSessionCalendar($date, $view)
    {
        if ($view === 'edit') {
            return $this->getSessionEditor($date);
        } else {
            return $this->getSessionViewer($date);
        }
    }

    /**
     * Get all sessions
     * @param string $date: selected date
     * @return string
     */
    public function getSessionEditor($date)
    {
        if ($this->isAvailable(array('date'=>$date))) {
            return Session::dayContainer(array('date'=>$date, 'content'=>Session::nothingPlannedThisDay()));
        } else {
            return $this->getDayContent($this->all(array('s.date'=>$date)), $date, true);
        }
    }

    /**
     * Get session viewer
     * @param string $date
     * @return string
     */
    public function getSessionViewer($date)
    {
        if ($this->isAvailable(array('date'=>$date))) {
            return Session::dayContainer(array('date'=>$date, 'content'=>Session::nothingPlannedThisDay()));
        } else {
            return $this->getDayContent($this->all(array('s.date'=>$date)), $date, false);
        }
    }

    /**
     * Returns Session Manager
     * @return string
     */
    public function getSessionManager($date = null)
    {
        // Get next session date if none is provided
        if (is_null($date)) {
            $data = $this->getNext(1);
            $date = $data[0]['date'];
        }

        // Add session form
        $Addform = self::form($this->getDefaults(), $this->settings['default_type']);

        if ($this->isAvailable(array('date'=>$date))) {
            return self::sessionManager(null, $Addform, $date);
        } else {
            $sessionEditor = $this->getSessionEditor($date);
            $session = $this->get(array('date'=>$date));
            $slots = $this->getSlots($session['id']);
            return self::sessionManager(
                self::form(
                    $this->get(array('date'=>$date)),
                    $this->getSlots($session['id'])
                ),
                $Addform,
                $date
            );
        }
    }

    /**
     * Get session viewer
     * @param int $n: number of days to display
     * @return string
     */
    public function getViewer($n)
    {
        return self::sessionViewerContainer($this->showCalendar($n));
    }

    /**
     * Display the upcoming presentation(home page/mail)
     * @param bool $mail
     * @return string
     */
    public function showNextSession($mail = false)
    {
        $date = $this->getNextDates(1)[0];
        $data = $this->all(array('s.date'=>$date));
        $data = reset($data);
        if (!empty($data)) {
            if ($this->isAvailable(array('date'=>$date))) {
                if (!$mail) {
                    return self::sessionDetailsBody($data[0], Session::nothingPlannedThisDay());
                } else {
                    return self::sessionDetailsEmail($data[0], Session::nothingPlannedThisDay());
                }
            } else {
                if (!$mail) {
                    return self::sessionDetailsBody($data[0], $this->getSessionDetails($data, $date, $mail));
                } else {
                    return self::sessionDetailsEmail($data[0], $this->getSessionDetails($data, $date, $mail));
                }
            }
        } else {
            return self::noUpcomingSession();
        }
    }

    /**
     * Get list of future presentations (home page/mail)
     * @param int $nsession
     * @return string
     */
    public function showCalendar($nSession = 4)
    {
        // Get next planned date
        $today = date('Y-m-d', time());
        if (!empty($dates = $this->getNextDates($nSession))) {
            // Repeat sessions$this->getDayContent
            $this->repeatAll(end($dates));
    
            $content = "";
            foreach ($dates as $day) {
                $content .= $this->getDayContent($this->all(array('s.date'=>$day)), $day, false);
            }
            return $content;
        } else {
            return self::noUpcomingSession();
        }
    }

    /**
     * Get and render day content
     *
     * @param array  $data: day information
     * @param string $day : requested date (d-m-Y)
     * @param bool   $edit: get editor (true) or viewer (false)
     *
     * @return string
     */
    public function getDayContent(array $data, $day, $edit = false)
    {
        $date = date('d M Y', strtotime($day));
        $dayContent = null;
        if (!empty($data)) {
            foreach ($data as $session_id => $session_data) {
                $dayContent .= $this->getSessionContent($session_data, $date, $edit);
            }
        } else {
            $dayContent .= Session::nothingPlannedThisDay();
        }
        return Session::dayContainer(array('date'=>$date, 'content'=>$dayContent));
    }

    /**
     * Get and render session content
     * @param array $data: session data
     * @param string $date: selected date
     * @param bool $edit: Get editor or viewer
     * @return string
     */
    private function getSessionContent(array $data, $date, $edit = false)
    {
        $content = null;
        for ($i=0; $i<$data[0]['slots']; $i++) {
            if (isset($data[$i]) && !is_null($data[$i]['id_pres'])) {
                if (!$edit) {
                    $content .= self::slotContainerBody(Presentation::inSessionSimple($data[$i]), $data[$i]['id_pres']);
                } else {
                    $content .= self::slotEditContainer(Presentation::inSessionEdit($data[$i]), $data[$i]['id_pres']);
                }
            } else {
                if ($edit) {
                    $content .= self::emptySlotEdit();
                } else {
                    $content .= self::emptyPresentationSlot($data[0], SessionInstance::isLogged());
                }
            }
        }

        if ($edit) {
            return self::sessionEditContainer($data[0], $content, TypesManager::getTypes(self::getClassName()));
        } else {
            return self::sessionContainer(array(
                'date'=>$date,
                'content'=>$content,
                'type'=>$data[0]['session_type'],
                'start_time'=>$data[0]['start_time'],
                'end_time'=>$data[0]['end_time'],
                'room'=>$data[0]['room']
            ));
        }
    }

    /**
     * Get and render session content
     * @param array $data: session data
     * @param string $date: selected date
     * @param bool $mail: Get mail view
     * @return string
     */
    private function getSessionDetails(array $data, $date, $mail = false)
    {
        $content = null;
        for ($i=0; $i<$data[0]['slots']; $i++) {
            if (isset($data[$i]) && !is_null($data[$i]['id_pres'])) {
                if (!$mail) {
                    $content .= self::slotContainerEmail(Presentation::inSessionSimple($data[$i]));
                } else {
                    $content .= self::slotContainerEmail(Presentation::inSessionSimple($data[$i]));
                }
            } else {
                $content .= self::emptyPresentationSlot($data[0], SessionInstance::isLogged());
            }
        }
        return $content;
    }

    /**
     * Renders email notifying presentation assignment
     * @param Users $user
     * @param array $info: array('type'=>session_type,'date'=>session_date, 'presid'=>presentation_id)
     * @param bool $assigned
     * @return mixed
     */
    public function notify_session_update(Users $user, array $info, $assigned = true)
    {
        $MailManager = new MailManager();
        if ($assigned) {
            $dueDate = date('Y-m-d', strtotime($info['date'].' - 1 week'));
            $content = self::invitationEmail($user->fullname, $dueDate, $info['date'], $info['type']);
        } else {
            $content = self::cancelationUserEmail($user->fullname, $info['date']);
        }

        // Notify organizers of the cancellation but only for real users
        if (!$assigned && $user->username !== 'TBA') {
            $this->notify_organizers($user, $info);
        }

        // Send email
        $result = $MailManager->send($content, array($user->email));
        return $result;
    }

    /**
     * Notify organizers that a presentation has been manually canceled
     * @param Users $user
     * @param array $info
     * @return mixed
     */
    public function notify_organizers(Users $user, array $info)
    {
        $MailManager = new MailManager();
        foreach ($user->getAdmin() as $key => $info) {
            $content = self::cancelationOrganizerEmail($info['fullname'], $user->fullname, $info['date']);
            if (!$MailManager->send($content, array($info['email']))) {
                return false;
            }
        }
        return true;
    }

    /**
     * Cancel session (when session type is set to none)
     * @param Session $session
     * @return bool
     */
    public function cancelSession(Session $session)
    {
        $assignment = new Assignment();
        $result = true;

        // Loop over presentations scheduled for this session
        foreach ($session->presids as $id_pres) {
            $pres = new Presentation($id_pres);
            $speaker = new Users($pres->orator);

            // Delete presentation and notify speaker that his/her presentation has been canceled
            if ($result = $pres->delete_pres($id_pres)) {
                $info = array(
                    'speaker'=>$speaker->username,
                    'type'=>$session->type,
                    'presid'=>$pres->id_pres,
                    'date'=>$session->date
                );
                // Notify speaker
                $result = $assignment->updateAssignment($speaker, $info, false, true);
            }
        }

        // Update session information
        if ($result) {
            $result = $session->delete(array('id'=>$session->id));
        }
        return $result;
    }

    /**
     * Modify session type and change corresponding assignments
     *
     * @param string $id: session id
     * @param string $type: new session type
     * @return array
     */
    public function modifySessionType($id, $type)
    {
        if ($this->modifyAssignments($this->get(array('id'=>$id), $type))) {
            $result = $this->update(array('type'=>$type), array('id'=>$id));
            if ($result['status']) {
                $result['msg'] = "Session's type has been set to {$value}";
            }
        }
        return $result;
    }

    /**
     * Modify session type and notify speakers about the change
     * @param array $data
     * @param $new_type
     * @return bool
     */
    public function modifyAssignments(array $data, $new_type)
    {
        $assignment = new Assignment();
        $result = true;

        $previous_type = $data['type'];

        // Loop over presentations scheduled for this session
        foreach ($data['presids'] as $id_pres) {
            $pres = new Presentation($id_pres);
            $speaker = new Users($pres->orator);

            // Unassign
            $info = array(
                'speaker'=>$speaker->username,
                'type'=>$previous_type,
                'presid'=>$pres->id_pres,
                'date'=>$data['date']
            );

            // Update assignment table with new session type
            if ($assignment->updateAssignment($speaker, $info, false, false)) {
                $info['type'] = $new_type;
                $result = $assignment->updateAssignment($speaker, $info, true, false);
            }

            // Notify user about the change of session type
            $MailManager = new MailManager();
            $date = $info['date'];
            $contactURL = URL_TO_APP."index.php?page=contact";

            $content['body'] = "
            <div style='width: 100%; margin: auto;'>
                <p>Hello $speaker->fullname,</p>
                <p>This is to inform you that the type of your session ({$date}) 
                has been modified and will be a <strong>{$new_type}</strong> instead of a 
                <strong>{$previous_type}</strong>.</p>
                <p>If you need more information, please <a href='$contactURL'>contact</a> the organizers.</p>
            </div>
            ";
            $content['subject'] = "Your session ($date) has been modified";

            $result = $MailManager->send($content, array($speaker->email));
        }
        return $result;
    }

    /**
     * Create session
     * @param array $post
     * @param bool $initial: creation of initial occurrence
     * @return array
     */
    public function make(array $post = array(), $initial = true)
    {
        $post['date'] = (!empty($post['date'])) ? $post['date'] : $this->date;
        $post['start_date'] = $post['date'];
        $post['end_date'] = (!empty($post['to_repeat']) && $post['to_repeat'] == 1) ? $post['end_date'] : $post['date'];

        if ($this->isAvailable($post)) {
            $content = $this->parseData($post, array('presids','speakers', 'max_nb_session', 'default'));

            // Add session to the database
            if ($id = $this->db->insert($this->tablename, $content)) {
                Logger::getInstance(APP_NAME, get_class($this))->info("New session created on {$this->date}");

                if ($initial) {
                    $this->update(array('event_id'=>$id), array('id'=>$id));

                    // Get id of insert session
                    $post['event_id'] = $id;
                }

                if ($initial && (!empty($post['to_repeat']) && $post['to_repeat'] == 1)) {
                    $this->repeat($post);
                }
                $result['status'] = true;
                $result['msg'] = 'Session successfully created!';
            } else {
                Logger::getInstance(APP_NAME, get_class($this))->error("Could not create session on {$this->date}");
                $result['status'] = false;
                $result['msg'] = 'Sorry, something went wrong.';
            }
        } else {
            $result['status'] = false;
            $result['msg'] = 'Sorry, this time slot is not available.';
        }
        return $result;
    }

    /**
     * Update session information
     *
     * @return array
     */
    public function updateSession()
    {
        $session_id = $_POST['id'];
        $operation = $_POST['operation'];
        unset($_POST['operation']);
        unset($_POST['id']);
        $result = array('status'=>false, 'msg'=>null);

        if ($operation === 'present') {
            // Only update the current event
            $result['status'] = $this->update($_POST, array('id'=>$session_id));
        } elseif ($operation === 'future') {
            // Update all future occurences
            $result['status'] = $this->updateAllEvents($_POST, $session_id, 'future');
        } elseif ($operation === 'all') {
            // Update all (past/future) occurences
            $result['status'] = $this->updateAllEvents($_POST, $session_id, 'all');
        } else {
            throw new Exception("'{$operation}' is an unknown update operation");
        }

        $result['msg'] = $result['status'] ? "Session has been modified" : 'Something went wrong';
        return $result;
    }

    /**
     * Update session status
     *
     * @return bool
     */
    public function update_status()
    {
        $this->nbpres = count($this->presids);
        if ($this->nbpres == 0) {
            $status = "Free";
        } elseif ($this->nbpres<$this->slots) {
            $status = "Booked";
        } else {
            $status = "Booked out";
        }
        return $status;
    }

    /**
     * Check if the date of presentation is already booked
     *
     * @param int $session_id: session id
     * @return bool
     */
    public static function isBooked($session_id)
    {
        $session = new self();
        $data = $session->getInfo(array('id'=>$session_id));
        if ($data === false) {
            return 'nothingPlannedThisDay';
        } elseif ($data['nbpres']<$data['slots']) {
            return false;
        } else {
            return 'booked';
        }
    }

    /**
     * Get session info
     *
     * @param array $id: session id
     * @return array|bool
     */
    public function getInfo(array $id)
    {
        $data = $this->get($id);

        // Get the associated presentations
        if (!empty($data)) {
            $data = $this->getPresids($data);
            return $data;
        } else {
            return false;
        }
    }

    /**
     * Show session details
     *
     * @param bool $show
     * @param bool $prestoshow
     * @return string
     */
    public function showsessiondetails(array $data, $show = true, $prestoshow = false)
    {
        if ($this->type == 'none') {
            return "No journal club this day.";
        } elseif (count($this->presids) == 0) {
            return "There is no presentation planned for this session yet.";
        }

        $content = "
            <div style='background-color: rgba(255,255,255,.5); padding: 5px; margin-bottom: 10px;'>
                <div style='margin: 0 5px 5px 0;'><b>Type: </b>{$data['type']}</div>
                <div style='display: inline-block; margin: 0 0 5px 0;'><b>Date: </b>{$data['date']}</div>
                <div style='display: inline-block; margin: 0 5px 5px 0;'>
                <b>From: </b>{$data['start_time']}<b> To: </b>{$data['end_time']}</div>
            <div style='display: inline-block; margin: 0 5px 5px 0;'><b>Room: </b>{$data['room']}</div><br>
            </div>";

        $presentations_list = '';
        $i = 0;
        foreach ($this->presids as $presid) {
            if ($prestoshow != false && $presid != $prestoshow) {
                continue;
            }

            $pres = new Presentation($presid);
            $presentations_list .= $pres->mail_details($show);
            $i++;
        }

        $content .= "
            <div style='color: #444444; margin-bottom: 10px;  border-bottom:1px solid #DDD; 
            font-weight: 500; font-size: 1.2em;'>
            Presentations
            </div>
            {$presentations_list}
        ";
        return $content;
    }

    /**
     * Repeat sessions to be repeated
     * @param null $max_date
     * @param int $session_to_plan
     * @return mixed
     */
    public function repeatAll($max_date = null, $session_to_plan = 1)
    {
        $result = array('status'=>true, 'msg'=>'Nothing to plan');
        foreach ($this->get_repeated() as $key => $item) {
            $result = $this->repeat($item, $session_to_plan, $max_date);
        };
        return $result;
    }

    /**
     * Repeat sessions to be repeated
     * @param array $item : session data
     * @param int $session_to_plan
     * @param null $max_date
     *
     * @return mixed
     */
    public function repeat(array $item, $session_to_plan = 1, $max_date = null)
    {
        if (is_null($max_date) && $item['end_date'] === "never") {
            $max_date =  date('Y-m-d', strtotime("now + {$session_to_plan} days"));
        } else {
            $max_date = (is_null($max_date)) ? $item['end_date'] : $max_date;
        }
        $result = $this->recursiveRepeat($item, $max_date);
        $result['msg'] = "{$result['counter']} sessions have been created";
        return $result;
    }

    /**
     * Update all or only upcoming occurences of an event
     * @param array $post: Updated information
     * @param $id: id of current occurrence
     * @param $what: 'future': only upcoming occurences, 'all': past and future occurences
     * @return bool
     */
    public function updateAllEvents(array $post, $id, $what)
    {
        // Get event id
        $data = $this->get(array('id'=>$id));

        $today = date('Y-m-d');
        if ($what === 'future') {
            $all = $this->db->resultSet(
                $this->tablename,
                array('*'),
                array('event_id'=>$data['event_id'],
                'date >='=>$today)
            );
        } else {
            $all = $this->db->resultSet(
                $this->tablename,
                array('*'),
                array('event_id'=>$data['event_id'])
            );
        }

        if (!empty($all)) {
            foreach ($all as $key => $item) {
                if (!$this->update($post, array('id'=>$item['id']))) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Delete all or only upcoming occurences of an event
     *
     * @param $id: id of current occurence
     * @param $what: 'future': only upcoming occurences, 'all': past and future occurences
     * @return bool
     */
    public function deleteAllEvents($id, $what)
    {
        // Get event id
        $data = $this->get(array('id'=>$id));
        $today = date('Y-m-d');
        if ($what === 'future') {
            $all = $this->db->resultSet(
                $this->tablename,
                array('*'),
                array('event_id'=>$data['event_id'],
                'date >='=>$today)
            );
        } else {
            $all = $this->db->resultSet(
                $this->tablename,
                array('*'),
                array('event_id'=>$data['event_id'])
            );
        }

        if (!empty($all)) {
            foreach ($all as $key => $item) {
                if (!$this->delete(array('id'=>$item['id']))) {
                    Logger::getInstance(APP_NAME, get_class($this))->error("Could not delete session {$item['id']}");
                    return false;
                } else {
                    Logger::getInstance(APP_NAME, get_class($this))->info("Session {$item['id']} has been deleted");
                }
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * Test if event is recurrent
     *
     * @param $id
     * @return bool
     */
    public function isRecurrent($id)
    {
        $data = $this->get(array('id'=>$id));
        return count($this->all(array('event_id'=>$data['event_id']))) > 1;
    }

    /**
     * Recursively repeat All sessions
     *
     * @param $item
     * @param $max_date
     * @param array|null $result
     * @return mixed
     */
    private function recursiveRepeat($item, $max_date, array $result = null)
    {
        if (is_null($result)) {
            $result = array('status'=>true, 'msg'=>null, 'counter'=>0);
        }

        if (new DateTime($item['date']) <= new DateTime($max_date)) {
            if (new DateTime($item['date']) == new DateTime($max_date)
            && new DateTime($item['date']) < new DateTime($item['end_date'])) {
                $item['repeated'] = 0;
            } else {
                $item['repeated'] = 1;
            }

            // Add new occurrence
            foreach ($this->make($item, false) as $key => $value) {
                $result[$key] = $value;
            }

            if ($result['status']) {
                $result['counter']++;
            }

            // Update occurrence
            $this->update(array('repeated'=>$item['repeated']), array('date'=>$item['date'], 'event_id'=>$item['event_id']));

            // Continue with next occurrences
            $data = $item;
            $data['date'] = date('Y-m-d', strtotime("{$item['date']} + {$item['frequency']} days"));
            foreach ($this->recursiveRepeat($data, $max_date, $result) as $key => $value) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Check consistency between presentations and sessions table
     *
     * @return array
     */
    public function checkDb()
    {
        if ($this->db->isColumn($this->tablename, 'time')) {
            $req = $this->db->sendQuery("SELECT date,jc_time FROM " . $this->db->getAppTables('Presentation'));
            while ($row = $req->fetch_assoc()) {
                if (!$this->isExist(array('date'=>$row['date']))) {
                    $result = $this->make(array('date'=>$row['date'],'time'=>$row['jc_time']));
                    if (!$result['status']) {
                        $result['msg'] = "<p class='sys_msg warning'>'" . $this->tablename . "' not updated</p>";
                    }
                }
            }
            return array('status'=>true, 'msg'=>'Database checked');
        } else {
            return array('status'=>true, 'msg'=>'Database checked');
        }
    }

    /**
     * Removes duplicate sessions
     */
    public function clean_duplicates()
    {
        $sql = "SELECT * FROM {$this->tablename}";
        $req = $this->db->sendQuery($sql);
        $data = array();
        while ($row = $req->fetch_assoc()) {
            $data[] = $row;
        }

        if (!empty($data)) {
            foreach ($data as $key => $info) {
                $sessions = $this->db->resultSet($this->tablename, array('*'), array('date'=>$info['date']));
                if (count($sessions) > 1) {
                    $sessions = array_slice($sessions, 1);
                    foreach ($sessions as $id => $row) {
                        $this->delete(array('id'=>$row['id']));
                    }
                }
            }
        }
    }

    /* MODEL */

    /**
     * Retrieve all elements from the selected table
     * @param array $id
     * @param array $filter
     * @return array|mixed
     */
    public function all(array $id = null, array $filter = null)
    {
        $dir = (!is_null($filter) && isset($filter['dir'])) ? strtoupper($filter['dir']):'DESC';
        $param = (!is_null($filter) && isset($filter['order'])) ? "ORDER BY {$filter['order']} " . $dir
        : "ORDER BY start_time ASC";
        $limit = (!is_null($filter) && isset($filter['limit'])) ? " LIMIT {$filter['limit']} " : null;

        if (!is_null($id)) {
            $search = $this->db->parse(array(), $id);
        } else {
            $search = null;
        }

        $sql = "SELECT *, id as session_id, type as session_type
                FROM {$this->tablename} s
                 LEFT JOIN 
                    (SELECT date as pres_date, type as pres_type, session_id as p_session_id, id as id_pres, 
                    title, orator, username  
                    FROM " . $this->db->getAppTables('Presentation') . ") p
                        ON s.id=p.p_session_id
                 LEFT JOIN 
                    (SELECT username, fullname FROM " . $this->db->getAppTables('Users'). ") u
                        ON u.username=p.username
                 {$search['cond']} {$param} {$limit}";

        $req = $this->db->sendQuery($sql);
        $data = array();
        if ($req !== false) {
            while ($row = $req->fetch_assoc()) {
                if (!isset($data[$row['session_id']])) {
                    $data[$row['session_id']] = array();
                }
                $data[$row['session_id']][] = $row;
            }
        }
        return $data;
    }

    /**
     * Get slots related to session
     *
     * @param string $id: session id
     *
     * @return array
     */
    public function getSlots($id)
    {
        $sql = "SELECT * FROM " . $this->db->getAppTables('Presentation') . " WHERE session_id={$id}";
        
        $req = $this->db->sendQuery($sql);
        $data = array();
        if ($req !== false) {
            while ($row = $req->fetch_assoc()) {
                if (!isset($data[$row['session_id']])) {
                    $data[$row['session_id']] = array();
                }
                $data[$row['session_id']][] = $row;
            }
        }
        return $data;
    }

    /**
     * Get presentations and speakers
     * @param array $data: session information
     * @return array $data: updated session information
     */
    private function getPresids(array $data)
    {
        $sql = "SELECT p.id_pres,u.fullname 
            FROM " . Db::getInstance()->getAppTables('Presentation') . " p
                INNER JOIN " . Db::getInstance()->getAppTables('Users'). " u
                ON p.username=u.username                
            WHERE p.session_id='{$data['id']}'";
        $req = $this->db->sendQuery($sql);
        $data['presids'] = array();
        $data['speakers'] = array();
        while ($row = $req->fetch_assoc()) {
            $data['presids'][] = $row['id_pres'];
            $data['speakers'][] = $row['fullname'];
        }
        $data['nbpres'] = count($data['presids']);
        return $data;
    }

    /**
     * Get list of sessions to repeatAll
     * @return array: list of sessions to be repeated
     */
    public function get_repeated()
    {
        $sql = "SELECT * FROM {$this->tablename}
                  WHERE to_repeat=1 and repeated=0 and end_date>date";
        $req = $this->db->sendQuery($sql);
        $data = array();
        while ($row = $req->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    /**
     *  Get upcoming dates
     * @param null|int $limit
     * @return array|bool
     */
    public function getNextDates($limit = null)
    {
        $sql = "SELECT id,date FROM {$this->tablename} WHERE date>=CURDATE() ORDER BY date ASC";

        $sql .= (!is_null($limit)) ? " LIMIT {$limit}": null;
        $req = $this->db->sendQuery($sql);

        $sessions = array();
        while ($row = $req->fetch_assoc()) {
            $sessions[] = $row['date'];
        }
        if (empty($sessions)) {
            $sessions = false;
        }
        return $sessions;
    }

    /**
     * Get upcoming sessions information
     * @param null $limit
     * @return array|bool|mixed
     */
    public function getUpcoming($limit = null)
    {
        $today = date('Y-m-d');
        $data = $this->all(array('date >'=>$today), array('order'=>'date', 'dir'=>'ASC', 'limit'=>$limit));
        return (empty($data)) ? false : $data;
    }

    /**
     * Get upcoming dates
     * @param null|int $limit
     * @return array|bool
     */
    public function getNext($limit = null)
    {
        $sql = "SELECT * FROM {$this->tablename} WHERE date>CURDATE() ORDER BY date ASC";

        $sql .= (!is_null($limit)) ? " LIMIT {$limit}": null;
        $req = $this->db->sendQuery($sql);

        $sessions = array();
        while ($row = $req->fetch_assoc()) {
            $sessions[] = $row;
        }
        if (empty($sessions)) {
            $sessions = false;
        }
        return $sessions;
    }

    /**
     * Get journal club days
     * @param string $jc_day: week day of journal club
     * @param int $nb_session
     * @param bool $from
     * @return array
     */
    public static function getJcDates($jc_day, $nb_session = 20, $from = null)
    {
        $start_date = is_null($from) ? date('Y-m-d', strtotime('now')) : date('Y-m-d', strtotime($from));
        $jc_days = array();
        if (!empty($jc_day)) {
            for ($s=0; $s<$nb_session; $s++) {
                $what = ($s == 0) ? 'this' : 'next';
                $start_date = date('Y-m-d', strtotime($what . " " . $jc_day . " " . $start_date));
                $jc_days[] = $start_date;
            }
        } else {
            $jc_days[] = date('Y-m-d', strtotime('now'));
        }
        return $jc_days;
    }

    /**
     * Check if time slot is available
     * @param $session_data: session information
     * @return bool: True if nothing is planned on this time slot
     */
    public function isAvailable(array $session_data)
    {
        if (isset($session_data['start_time']) && isset($session_data['end_time'])) {
            $overlap = " and (start_time>'{$session_data['start_time']}' and start_time<'{$session_data['end_time']}') 
                  or (end_time>'{$session_data['start_time']}' and end_time<'{$session_data['end_time']}')";
        } else {
            $overlap = null;
        }
        $overlap = null;
        $sql = "SELECT * FROM {$this->tablename} WHERE date='{$session_data['date']}'{$overlap}";
        $data = $this->db->sendQuery($sql)->fetch_assoc();
        return is_null($data);
    }

    /* VIEWS */

    /**
     * Render session viewer
     * @param string $sessions: list of sessions
     * @return string
     */
    public static function sessionViewerContainer($sessions)
    {
        return "
        <div class='section_content'>
            <div class='form-group'>
                <input type='date' class='selectSession datepicker viewerCalendar' data-status='false' 
                data-view='view' name='date' />
                <label>Filter</label>
            </div>
            <div id='sessionlist'>{$sessions}</div>
        </div>
        ";
    }

    /**
     * Render session manager
     * @param $sessionEditor
     * @param $form: edit form
     * @return string
     */
    public static function sessionManager($sessionEditor, $form, $selected_date = null)
    {
        return "
            <div class='session_viewer_container'>
                <h3>Edit a session</h3>
                <div class='form-group'>
                    <input type='date' class='selectSession datepicker viewerCalendar' 
                    name='date' data-view='edit' data-destination='#session_list' value='{$selected_date}' />
                    <label>Select a session</label>
                </div>
                <div id='session_list'>{$sessionEditor}</div>
            </div>

            <div>
                <h3>Add a new session</h3>
                {$form}
            </div>
        ";
    }

    /**
     * Render list of days
     * @param $jc_day: week day of journal club
     * @return null|string
     */
    private static function dayList($jc_day)
    {
        $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
        $list = null;
        foreach ($days as $day) {
            $selected = ($day == $jc_day) ? 'selected' : null;
            $list .= "<option value='{$day}' {$selected}>" . ucfirst($day) . "</option>";
        }
        return $list;
    }

    /**
     * Render form for modifying session default settings
     * @param array $settings
     * @return string
     */
    public static function defaultSettingsForm(array $settings)
    {
        return "
        <div class='section_content'>
            <form method='post' action='php/router.php?controller=Session&action=updateSettings'>
                <div class='feedback' id='feedback_jcsession'></div>
                <div class='form-group'>
                    <input type='text' name='room' value='" . $settings['room'] . "'>
                    <label>Room</label>
                </div>
                <div class='form-group'>
                    <select name='jc_day'>
                        " . self::dayList($settings['jc_day']) . "
                    </select>
                    <label for='jc_day'>Day</label>
                </div>
                <div class='form-group'>
                    <input type='time' name='jc_time_from' value='" . $settings['jc_time_from'] . "' />
                    <label>From</label>
                </div>
                <div class='form-group'>
                    <input type='time' name='jc_time_to' value='" . $settings['jc_time_to'] . "' />
                    <label>To</label>
                </div>
                <div class='form-group'>
                    <input type='number' name='max_nb_session' value='" . $settings['max_nb_session'] . "'/>
                    <label>Slots/Session</label>
                </div>
                <p style='text-align: right'><input type='submit' name='modify' value='Modify' id='submit' 
                class='processform'/></p>
            </form>
        </div>
        ";
    }

    /**
     * Render form to add session
     * @param array $data
     * @param $default_type
     * @return string
     */
    public static function form(array $data, $slots = null, $default_type = null)
    {
        // Repeat session option
        $repeat_options = null;
        foreach (array('Yes'=>1, 'No'=>0) as $label => $value) {
            $selected = (int)$data['to_repeat'] === $value ? 'selected' : null;
            $repeat_options .= "<option value={$value} {$selected}>{$label}</option>";
        }
        $show_repeat_settings = $data['to_repeat'] == 1 ? 'display: visible' : 'display: none';

        // Form action url
        $url = Router::buildUrl(
            self::getClassName(),
            'make'
        );

        // Select of input for session type
        $selectedType = (!empty($data['session_type'])) ? $data['session_type'] : $default_type;
        $type_list = TypesManager::getTypeSelectInput(self::getClassName(), $selectedType);

        // Submit buttons
        if (isset($data['id'])) {
            $addButton = "<input type='submit' value='Add' class='processform' />";
            $deleteButton = "<input type='submit' value='Delete' class='delete_session' data-controller='Session' 
            data-action='delete' data-id='{$data['id']}' />";
            $modifyButton = "<input type='submit' class='modify_session' value='Modify' />";
            $buttons = "
            {$modifyButton}
            {$deleteButton}
            ";
        } else {
            $buttons = "<input type='submit' class='modify_session' value='Modify' />";
            $data['id'] = false;
        }

        return "
        <form action='{$url}' method='post'>
            <div class='form-group'>
                <select name='type'>{$type_list['options']}</select>
                <label>Type</label>
            </div>
            <div class='form-group field_small inline_field'>
                <input type='date' name='date' class='datepicker viewerCalendar' data-view='edit' 
                value='{$data['date']}' />
                <label>Date</label>
            </div>
            <div class='form-group field_small inline_field'>
                <input type='time' name='start_time' value='{$data['start_time']}' />
                <label>From</label>
            </div>
            <div class='form-group field_small inline_field'>
                <input type='time' name='end_time' value='{$data['end_time']}' />
                <label>To</label>
            </div>
            <div class='form-group field_small inline_field'>
                <input type='text' name='room' value='{$data['room']}' />
                <label>Room</label>
            </div>
            <div class='form-group field_small inline_field'>
                <input type='number' name='slots' value='{$data['slots']}' />
                <label>Slots</label>
            </div>
            <div>
                <div class='form-group field_small inline_field'>
                    <select name='to_repeat' class='repeated_session'>
                        <option value=1>Yes</option>
                        <option value=0 selected>No</option>
                    </select>
                    <label>Repeat</label>
                </div>
                <div class='form-group field_small inline_field settings_hidden' style='display: none;'>
                    <input type='date' name='end_date' value='{$data['date']}' />
                    <label>End date</label>
                </div>
                <div class='form-group field_small inline_field settings_hidden' style='display: none;'>
                    <input type='number' name='frequency' value='{$data['frequency']}' />
                    <label>Frequency (day)</label>
                </div>
                <div class='submit_btns'>
                    <input type='hidden' name='start_date' value='{$data['date']}' />
                    <input type='hidden' name='id' value='{$data['id']}' />
                </div>
            </div>
        </form>
        ";
    }

    /**
     * Render session settings panel
     *
     * @param array $session: session information
     * @param array $types: list of session types
     * @return string
     */
    public static function sessionSettings(array $session, array $types)
    {
        $type_list = TypesManager::getTypeSelectInput(self::getClassName(), $session['session_type']);
        $repeat_options = null;
        foreach (array('Yes'=>1, 'No'=>0) as $label => $value) {
            $selected = (int)$session['to_repeat'] === $value ? 'selected' : null;
            $repeat_options .= "<option value={$value} {$selected}>{$label}</option>";
        }
        $show_repeat_settings = $session['to_repeat'] == 1 ? 'display: visible' : 'display: none';

        return "
            <h3>Settings</h3>
            <form action='php/router.php?controller=Session&action=updateSession' method='post'>
                <div class='renderTypes'>
                    <div class='form-group field_small inline_field' style='width: 100%;'>
                        <select name='type'>
                        {$type_list['options']}
                        </select>
                        <label>Type</label>
                    </div>
                </div>
                <div class='session_time'>
                    <div class='form-group field_small inline_field'>
                        <input type='time' name='start_time' value='{$session['start_time']}' />
                        <label>From</label>
                    </div>
                    <div class='form-group field_small inline_field'>
                        <input type='time' name='end_time' value='{$session['end_time']}' />
                        <label>To</label>
                    </div>
                    <div class='form-group field_small inline_field'>
                        <input type='text' name='room' value='{$session['room']}' />
                        <label>Room</label>
                    </div>
                    <div class='form-group field_small inline_field'>
                        <input type='number' name='slots' value='{$session['slots']}' />
                        <label>Slots</label>
                    </div>
                </div>
                <div>
                    <div class='form-group field_small inline_field'>
                        <select name='to_repeat' class='repeated_session'>
                            $repeat_options
                        </select>
                        <label>Repeat</label>
                    </div>
                    <div class='form-group field_small inline_field settings_hidden' style='{$show_repeat_settings}'>
                        <input type='date' name='end_date' value='{$session['end_date']}' />
                        <label>End date</label>
                    </div>
                    <div class='form-group field_small inline_field settings_hidden' style='{$show_repeat_settings}'>
                        <input type='number' name='frequency' value='{$session['frequency']}' />
                        <label>Frequency (day)</label>
                    </div>
                <div>
                <div class='submit_btns'>
                    <input type='hidden' name='id' value='{$session['id']}' />
                    <input type='submit' class='modify_session' value='Modify' />
                    <input type='submit' value='Delete' class='delete_session' data-controller='Session' 
                    data-action='delete' data-id='{$session['id']}'/>
                </div>
            </form>";
    }

    /**
     * Render session editor
     * @param Session $session
     * @param string $presentations
     * @return string
     */
    public static function sessionViewer(Session $session, $presentations)
    {
        return "
            <div style='display: block; margin: 10px auto 0 auto;'>
                <!-- header -->
                <div style='display: block; margin: 0 0 15px 0; padding: 0; text-align: 
                justify; min-height: 20px; height: auto; line-height: 20px; width: 100%;'>
                    <div style='vertical-align: top; text-align: left; margin: 5px; font-size: 16px;'>
                        <span style='color: #222; font-weight: 900;'>{$session->date}</span>
                        <span style='color: rgba(207,81,81,.5); font-weight: 900; font-size: 20px;'> . </span>
                        <span style='color: #777; font-weight: 600;'>{$session->type}</span>
                    </div>
                </div>

                <div style='padding: 10px 20px 10px 10px; background-color: rgba(239,239,239,.6); margin: 0 0 0 10px; 
                border-left: 2px solid rgba(175,175,175,.8);'>
                    {$presentations}
                </div>
            </div>";
    }

    /**
     * Message displayed when there is no upcoming session planned
     *
     * @return string
     */
    public static function noUpcomingSession()
    {
        return "
            <div class='sys_msg status'>There is no upcoming session.</div>
        ";
    }

    /**
     * Message to display when there is no session planned yet
     *
     * @return string
     */
    public static function nothingPlannedYet()
    {
        $url = URL_TO_APP . 'index.php?page=organizer/sessions';
        return "
            <div class='sys_msg status'>
                <p>Sorry, there is nothing planned yet.</p>
                <p>Add your first session from <a href='{$url}'>Admin>Sessions, 'Default settings'</a></p>
            </div>
        ";
    }


    /**
     * Show session slot as empty
     *
     * @return string
     */
    public static function nothingPlannedThisDay()
    {
        return "<div style='display: block; margin: 0 auto 10px 0; padding-left: 10px; font-size: 14px; 
                    font-weight: 600; overflow: hidden;'>
                    No Journal Club this day</div>";
    }

    /**
     * Show presentation slot as empty
     *
     * @param array $data : session data
     * @param bool $show_button: display add button
     * @return string
     */
    public static function emptyPresentationSlot(array $data, $show_button = true)
    {
        $url = URL_TO_APP . "index.php?page=member/submission&op=edit&date=" . $data['date'];
        $leanModalUrl = Router::buildUrl(
            'Presentation',
            'getForm',
            array(
                'view'=>'modal',
                'operation'=>'edit',
                'session_id'=>$data['id']
            )
        );
        $addButton = ($show_button) ? "
            <a href='{$url}' class='leanModal' data-url='{$leanModalUrl}' data-section='presentation'>
                <div class='add-button'></div>
            </a>" : null;

        $content = "
                <div>{$addButton}</div>";
        return self::slotContainerBody(array('name'=>'Free slot', 'button'=>$content, 'content'=>null));
    }

    /**
     * Show presentation slot as empty
     *
     * @return string
     */
    public static function emptySlotEdit()
    {
        return self::slotContainerBody(array('name'=>'Free slot', 'button'=>null, 'content'=>
            "<span style='font-size: 14px; font-weight: 500; color: #777;'>" . Presentation::speakerList() . "</span>
            "));
    }

    /**
     * Template for slot container
     * @param array $data
     * @param null $div_id
     * @return string
     */
    public static function slotContainerBody(array $data, $div_id = null)
    {
        return "
            <div class='pres_container ' id='{$div_id}' data-id='{$div_id}'>
                <div class='pres_type'>
                    {$data['name']}
                </div>
                <div class='pres_content'>
                    <div class='pres_info'>
                        {$data['content']}
                    </div>
                    <div class='pres_btn'>{$data['button']}</div>
                </div>
            </div>
            ";
    }

    /**
     * Template for slot container
     * @param array $data
     * @return string
     */
    public static function slotContainerEmail(array $data)
    {
        return "
            <div class='pres_container '>
                <div class='pres_type' style='display: inline-block; width: 50px; font-weight: 600; 
                color: #222222; vertical-align: middle; 
                    text-transform: capitalize;'>
                    {$data['name']}
                </div>
                <div class='pres_info' style='display: inline-block; width: 210px; 
                margin-left: 20px; vertical-align: middle;'>
                    {$data['content']}
                </div>
                <div class='pres_btn' style='display: inline-block; width: 35px; 
                vertical-align: middle;'>{$data['button']}</div>
            </div>
            ";
    }

    /**
     * Template for editable slot container
     *
     * @param array $data
     * @param null $div_id
     * @return string
     */
    public static function slotEditContainer(array $data, $div_id = null)
    {
        return "
            <div class='pres_container' id='{$div_id}' data-section='submission_form' data-id='{$div_id}'>
                <div class='pres_type'>
                    {$data['name']}
                </div>
                <div class='pres_info'>
                    {$data['content']}
                </div>
                <div class='pres_btn'>{$data['button']}</div>
            </div>
            ";
    }

    /**
     * Render session slot
     * @param array $data
     * @return string
     */
    public static function sessionContainer(array $data)
    {
        return "
            <div class='session_container'>
                <div class='session_header'>
                    <div class='session_type'>{$data['type']}</div>
                    <div class='session_info'>
                        <div>
                            <div><img src='".URL_TO_IMG . 'clock_bk.png'."'/></div>
                            <div>" . date('H:i', strtotime($data['start_time'])) . '-' . date('H:i', strtotime($data['end_time'])) . "</div>
                        </div>
                        <div>
                            <div><img src='".URL_TO_IMG . 'location_bk.png'."'/></div>
                            <div>{$data['room']}</div>
                        </div>
                    </div>      
                </div>
    
                <div class='session_content'>
                    {$data['content']}
                </div>
            </div>
             ";
    }

    /**
     * Render session slot
     * @param array $data
     * @param $presentations
     * @param $session_type: session type
     * @return string
     */
    public static function sessionEditContainer(array $data, $presentations, $session_type)
    {
        return "
            <div class='session_div session_editor_div' id='session_{$data['session_id']}' 
            data-id='{$data['session_id']}'>
                <div class='session_editor_core'>
                    <div class='session_settings'>
                        ". self::sessionSettings($data, $session_type) ."
                    </div>
    
                    <div class='session_presentations'>
                        <h3>Presentations</h3>
                        {$presentations}
                    </div>
                </div>
            </div>
        ";
    }

    /**
     * Render day container
     * @param array $data
     * @return string
     */
    public static function dayContainer(array $data)
    {
        $date = date('d M Y', strtotime($data['date']));
        return "
            <div class='day_container'>
                <!-- Day header -->
                <div class='day_header'>
                    <div class='day_date'>{$date}</div>
                </div>
                
                <!-- Day content -->
                <div class='day_content'>{$data['content']}</div>
            </div>";
    }

    /**
     * Render session details in email body
     * @param array $data
     * @param $presentations
     * @return string
     */
    public static function sessionDetailsBody(array $data, $presentations)
    {
        return "
            <div style='background-color: rgba(255,255,255,.5); padding: 5px; margin-bottom: 10px;'>
                <div style='margin: 0 5px 5px 0;'><b>Type: </b>{$data['type']}</div>
                <div style='display: inline-block; margin: 0 0 5px 0;'><b>Date: </b>{$data['date']}</div>
                <div style='display: inline-block; margin: 0 5px 5px 0;'>
                    <b>From: </b>{$data['start_time']}<b> To: </b>{$data['end_time']}</div>
                <div style='display: inline-block; margin: 0 5px 5px 0;'><b>Room: </b>{$data['room']}</div><br>
            </div>
            <div style='color: #444444; margin-bottom: 10px;  border-bottom:1px solid #DDD; 
            font-weight: 500; font-size: 1.2em;'>
            Presentations
            </div>
            {$presentations}
            ";
    }

    /**
     * Render session details in email body
     * 
     * @param array $data
     * @param $presentations
     * @return string
     */
    public static function sessionDetailsEmail(array $data, $presentations)
    {
        return "
        <div class='session_details_container'>
            <div class='session_details_header'>
                <div style='margin: 0 auto 5px 0; text-align: center; height: 20px; line-height: 20px; width: 100px; 
                    background-color: #555555; color: #FFF; padding: 5px; border-radius: 5px;'>
                    {$data['session_type']}
                </div>
                <div class='session_info' style='text-align: right; width: 100%; font-size: 12px;'>
                    <div id='pub_date'>
                        <div style='display: inline-block; width: 20px; vertical-align: middle;'><img src='" . URL_TO_IMG . 'calendar_bk.png' . "' 
                        style='width: 100%; vertical-align:middle;'/></div>
                        <div style='display: inline-block; vertical-align: middle;'>{$data['date']}</div>
                    </div>
                    <div id='pub_date'>
                        <div style='display: inline-block; width: 20px; vertical-align: middle;'><img src='" . URL_TO_IMG . 'clock_bk.png' . "' 
                        style='width: 100%; vertical-align:middle;'/></div>
                        <div style='display: inline-block; vertical-align: middle;'>
                        " . date('H:i', strtotime($data['date'])) . ' - ' . date('H:i', strtotime($data['date'])) . "
                        </div>
                    </div>
                    <div id='pub_date'>
                        <div style='display: inline-block; width: 20px; vertical-align: middle;'><img src='" . URL_TO_IMG . 'location_bk.png' . "' 
                        style='width: 100%; vertical-align:middle;'/></div>
                        <div style='display: inline-block; vertical-align: middle;'>{$data['room']}</div>
                    </div>
                </div>  
            </div>
    
            <div class='session_details_content'>
                {$presentations}
            </div>
        </div>
        ";
    }

    /**
     * Content of invitation email
     *
     * @param string $fullname: user's full name
     * @param string $dueDate: deadline for submitting presentation
     * @param string $date: date of presentation
     * @param string $session_type: session type
     *
     * @return array: array('body'=>content of email, 'subject'=>email's title)
     */
    private static function invitationEmail($fullname, $dueDate, $date, $session_type)
    {
        $contactURL = URL_TO_APP."index.php?page=contact";
        $editUrl = URL_TO_APP."index.php?page=submission&op=edit&id={$info['presid']}&user={$user->username}";
        return array(
            'body'=> "<div style='width: 100%; margin: auto;'>
                    <p>Hello {$fullname},</p>
                    <p>You have been automatically invited to present at a 
                    <span style='font-weight: 500'>{$session_type}</span> 
                    session on the <span style='font-weight: 500'>$date</span>.</p>
                    <p>Please, submit your presentation on the Journal Club Manager before the 
                    <span style='font-weight: 500'>{$dueDate}</span>.</p>
                    <p>If you think you will not be able to present on the assigned date, please 
                    <a href='{$contactURL}'>contact</a> the organizers as soon as possible.</p>
                    <div>
                        You can edit your presentation from this link: <a href='{$editUrl}'>{$editUrl}</a>
                    </div>
                </div>
            ",
            'subject'=> "Invitation to present on the $date"
        );
    }

    /**
     * Content of presentation cancelation email sent to speaker
     *
     * @param string $fullname: user's full name
     * @param string $date: presentation date
     *
     * @return array: array('body'=>content of email, 'subject'=>email's title)
     */
    private static function cancelationUserEmail($fullname, $date)
    {
        $contactURL = URL_TO_APP . "index.php?page=contact";
        return array(
            'body'=>"<div style='width: 100%; margin: auto;'>
                <p>Hello {$fullname},</p>
                <p>Your presentation planned on {$date} has been manually canceled. 
                You are no longer required to give a presentation on this day.</p>
                <p>If you need more information, please <a href='{$contactURL}'>contact</a> the organizers.</p>
                </div>
                ",
            'subject'=>"Your presentation ($date) has been canceled"
        );
    }

    /**
     * Content of presentation cancelation email sent to organizers
     *
     * @param string $fullname: organizer's full name
     * @param string $speaker: speaker's full name
     * @param string $date: date of presentation
     *
     * @return array: array('body'=>content of email, 'subject'=>email's title)
     */
    private static function cancelationOrganizerEmail($fullname, $speaker, $date)
    {
        $url = URL_TO_APP . 'index.php?page=organizer/sessions';
        return array(
            'body'=>"<div style='width: 100%; margin: auto;'>
                <p>Hello {$info['fullname']},</p>
                <p>This is to inform you that the presentation of 
                <strong>{$user->fullname}</strong> planned on the <strong>{$date}</strong> has been canceled. 
                You can either manually assign another speaker on this day in the 
                <a href='{$url}'>Admin>Session</a> section or let the automatic 
                assignment select a member for you.</p>
                </div>
            ",
            'subject'=>"A presentation ($date) has been canceled"
        );
    }
}
