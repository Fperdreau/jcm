<?php
/**
 * File for class Sessions and Session
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
 * Class Sessions
 */
class Session extends AppTable {

    protected $table_data = array(
        "id" => array("INT NOT NULL AUTO_INCREMENT", false),
        "date" => array("DATE", false),
        "status" => array("CHAR(10)", "FREE"),
        "room" => array("CHAR(10)", false),
        "time" => array("VARCHAR(200)", false),
        "type" => array("CHAR(30) NOT NULL"),
        "nbpres" => array("INT(2)", 0),
        "slots" => array("INT(2)", 0),
        "repeated" => array("INT(1) NOT NULL", 0),
        "to_repeat" => array("INT(1) NOT NULL", 0),
        "frequency" => array("INT(2)", 0),
        "start_date" => array("DATE", false),
        "end_date" => array("DATE", false),
        "start_time" => array("TIME", false),
        "end_time" => array("TIME", false),
        "primary" => "id"
    );

    public $max_nb_session;
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
    public $presids = array();
    public $speakers = array();
    private static $default = array();

    /**
     * Constructor
     * @param null $id: Session id
     */
    public function __construct($id=null) {
        parent::__construct("Session", $this->table_data);
        self::getDefaults();
        $this->max_nb_session = AppConfig::getInstance()->max_nb_session;

        if (!is_null($id)) {
            $this->getInfo(array('id'=>$id));
        }
    }

    /* Controller */

    /**
     * Register into Reminder table
     */
    public static function registerReminder() {
        $reminder = new ReminderMaker();
        $reminder->register(get_class());
    }

    /**
     * Register into DigestMaker table
     */
    public static function registerDigest() {
        $DigestMaker = new DigestMaker();
        $DigestMaker->register(get_class());
    }

    /**
     * Get default session settings
     * @return array: default settings
     */
    private static function getDefaults() {
        self::$default = array(
            'date'=>self::getJcDates(1)[0],
            'frequency'=>null,
            'slot'=>AppConfig::getInstance()->max_nb_session,
            'type'=>AppConfig::$session_type_default,
            'types'=>AppConfig::getInstance()->session_type,
            'start_time'=>AppConfig::getInstance()->jc_time_from,
            'end_time'=>AppConfig::getInstance()->jc_time_to,
            'room'=>AppConfig::getInstance()->room
        );
        return self::$default;
    }

    /**
     * Get all sessions
     * @param string $date: selected date
     * @return string
     */
    public function getSessionEditor($date) {
        if (!$this->is_available(array('date'=>$date))) {
            return Session::dayContainer(array('date'=>$date, 'content'=>Session::no_session()));
        } else {
            return $this->getDayContent($this->all(array('s.date'=>$date)), $date, true);
        }
    }

    /**
     * Get session viewer
     * @param string $date
     * @return string
     */
    public function getSessionViewer($date) {
        if (!$this->is_available(array('date'=>$date))) {
            return Session::dayContainer(array('date'=>$date, 'content'=>Session::no_session()));
        } else {
            return $this->getDayContent($this->all(array('s.date'=>$date)), $date, false);
        }
    }

    /**
     * Returns Session Manager
     * @return string
     */
    public function getSessionManager() {
        $date = self::getJcDates(1)[0];
        $form = self::add_session_form(self::getDefaults());
        if (!$this->is_available(array('date'=>$date))) return self::sessionManager(null, $form);
        $sessionEditor = $this->getSessionEditor($date);
        return self::sessionManager($sessionEditor, $form);
    }

    /**
     * Get session viewer
     * @param int $n: number of days to display
     * @return string
     */
    public function getViewer($n) {
        return self::sessionViewerContainer($this->showCalendar($n));
    }

    /**
     * Display the upcoming presentation(home page/mail)
     * @param bool $mail
     * @return string
     */
    public function showNextSession($mail=false) {
        $date = $this->getNextDates(1)[0];
        if (!$this->is_available(array('date'=>$date))) {
            if (!$mail) {
                return self::session_details($data, Session::no_session());
            } else {
                return self::mail_session_details($data, Session::no_session());
            }
        } else {
            $data = $this->all(array('s.date'=>$date));
            $data = reset($data);
            if (!$mail) {
                return self::session_details($data[0], $this->getSessionDetails($data, $date, $mail));
            } else {
                return self::mail_session_details($data[0], $this->getSessionDetails($data, $date, $mail));
            }
        }
    }

    /**
     * Get list of future presentations (home page/mail)
     * @param int $nsession
     * @return string
     */
    public function showCalendar($nsession = 4) {
        // Get next planned date
        $dates = $this->getNextDates(1);

        $dates = ($dates === false) ? false : $dates[0];

        // Get journal club days
        $jc_days = $this->getJcDates($nsession, $dates);

        // Repeat sessions
        $this->repeatAll(end($jc_days));

        // Get futures journal club sessions
        if ($dates !== false) {
            $content = "";
            foreach ($jc_days as $day) {
                $content .= $this->getDayContent($this->all(array('s.date'=>$day)), $day, false);
            }
        } else {
            $content = self::nothingToDisplay();
        }

        return $content;
    }

    /**
     * Get and render day content
     * @param array $data: day information
     * @param string $day : requested date (d-m-Y)
     * @param bool $edit: get editor (true) or viewer (false)
     * @return string
     */
    public function getDayContent(array $data, $day, $edit=false) {
        $date = date('d M Y', strtotime($day));
        $dayContent = null;
        if (!empty($data)) {
            foreach ($data as $session_id=>$session_data) {
                $dayContent .= $this->getSessionContent($session_data, $date, $edit);
            }
        } else {
            $dayContent .= Session::no_session();
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
    private function getSessionContent(array $data, $date, $edit=false) {
        $content = null;
        for ($i=0; $i<$data[0]['slots']; $i++) {
            if (isset($data[$i]) && !is_null($data[$i]['id_pres'])) {
                if (!$edit) {
                    $content .= self::slotContainer(Presentation::inSessionSimple($data[$i]), $data[$i]['id_pres']);
                } else {
                    $content .= self::slotEditContainer(Presentation::inSessionEdit($data[$i]), $data[$i]['id_pres']);
                }
            } else {
                if ($edit) {
                    $content .= self::emptySlotEdit();
                } else {
                    $content .= self::emptySlot($data[0], User::is_logged());
                }
            }
        }

        if ($edit) {
            return self::sessionEditContainer($data[0], $content);
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
    private function getSessionDetails(array $data, $date, $mail=false) {
        $content = null;
        for ($i=0; $i<$data[0]['slots']; $i++) {
            if (isset($data[$i]) && !is_null($data[$i]['id_pres'])) {
                if (!$mail) {
                    $content .= self::mail_slotContainer(Presentation::inSessionSimple($data[$i]));
                } else {
                    $content .= self::mail_slotContainer(Presentation::inSessionSimple($data[$i]));
                }
            } else {
                $content .= self::emptySlot($data[0], User::is_logged());
            }
        }

        return $content;
    }

    /**
     * Renders email notifying presentation assignment
     * @param User $user
     * @param array $info: array('type'=>session_type,'date'=>session_date, 'presid'=>presentation_id)
     * @param bool $assigned
     * @return mixed
     */
    public function notify_session_update(User $user, array $info, $assigned=true) {
        $MailManager = new MailManager();
        $sessionType = $info['type'];
        $date = $info['date'];
        $dueDate = date('Y-m-d',strtotime($date.' - 1 week'));
        $contactURL = URL_TO_APP."index.php?page=contact";
        $editUrl = URL_TO_APP."index.php?page=submission&op=edit&id={$info['presid']}&user={$user->username}";
        if ($assigned) {
            $content['body'] = "
            <div style='width: 100%; margin: auto;'>
                <p>Hello $user->fullname,</p>
                <p>You have been automatically invited to present at a <span style='font-weight: 500'>$sessionType</span> session on the <span style='font-weight: 500'>$date</span>.</p>
                <p>Please, submit your presentation on the Journal Club Manager before the <span style='font-weight: 500'>$dueDate</span>.</p>
                <p>If you think you will not be able to present on the assigned date, please <a href='$contactURL'>contact</a> the organizers as soon as possible.</p>
                <div>
                    You can edit your presentation from this link: <a href='{$editUrl}'>{$editUrl}</a>
                </div>
            </div>
        ";
            $content['subject'] = "Invitation to present on the $date";
        } else {
            $content['body'] = "
            <div style='width: 100%; margin: auto;'>
                <p>Hello $user->fullname,</p>
                <p>Your presentation planned on {$date} has been manually canceled. You are no longer required to give a presentation on this day.</p>
                <p>If you need more information, please <a href='$contactURL'>contact</a> the organizers.</p>
            </div>
            ";
            $content['subject'] = "Your presentation ($date) has been canceled";
        }

        // Notify organizers of the cancellation but only for real users
        if (!$assigned && $user->username !== 'TBA') $this->notify_organizers($user, $info);

        $result = $MailManager->send($content, array($user->email));
        return $result;

    }

    /**
     * Notify organizers that a presentation has been manually canceled
     * @param User $user
     * @param array $info
     * @return mixed
     */
    public function notify_organizers(User $user, array $info) {
        $MailManager = new MailManager();
        $date = $info['date'];
        $url = URL_TO_APP.'index.php?page=sessions';

        foreach ($user->getAdmin() as $key=> $info) {
            $content['body'] = "
                <div style='width: 100%; margin: auto;'>
                    <p>Hello {$info['fullname']},</p>
                    <p>This is to inform you that the presentation of <strong>{$user->fullname}</strong> planned on the <strong>{$date}</strong> has been canceled. 
                    You can either manually assign another speaker on this day in the <a href='{$url}'>Admin>Session</a> section or let the automatic 
                    assignment select a member for you.</p>
                </div>
            ";
            $content['subject'] = "A presentation ($date) has been canceled";

            if (!$MailManager->send($content, array($info['email']))) {
                return false;
            }
        }
        return true;
    }

    /**
     *
     * @param null $username
     * @return mixed
     */
    public function makeMail($username=null) {
        // Get future presentations
        $content['body'] = $this->showNextSession(true);;
        $content['title'] = 'Session Information';
        return $content;
    }

    /**
     *
     * @param null $username
     * @return mixed
     */
    public function makeReminder($username=null) {
        // Get future presentations
        $content['body'] = $this->showNextSession(true);;
        $content['title'] = 'Session Information';
        return $content;
    }

    /**
     * Cancel session (when session type is set to none)
     * @param Session $session
     * @return bool
     */
    public function cancelSession(Session $session) {
        $assignment = new Assignment();
        $result = true;

        // Loop over presentations scheduled for this session
        foreach ($session->presids as $id_pres) {
            $pres = new Presentation($id_pres);
            $speaker = new User($pres->orator);

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
     * Modify session type and notify speakers about the change
     * @param Session $session
     * @param $new_type
     * @return bool|mixed
     */
    public function set_session_type(Session $session, $new_type) {
        $assignment = new Assignment();
        $result = true;

        $previous_type = $session->type;

        // Loop over presentations scheduled for this session
        foreach ($session->presids as $id_pres) {
            $pres = new Presentation($id_pres);
            $speaker = new User($pres->orator);

            // Unassign
            $info = array(
                'speaker'=>$speaker->username,
                'type'=>$previous_type,
                'presid'=>$pres->id_pres,
                'date'=>$session->date
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
                <p>This is to inform you that the type of your session ({$date}) has been modified and will be a <strong>{$new_type}</strong> instead of a <strong>{$previous_type}</strong>.</p>
                <p>If you need more information, please <a href='$contactURL'>contact</a> the organizers.</p>
            </div>
            ";
            $content['subject'] = "Your session ($date) has been modified";

            $result = $MailManager->send($content, array($speaker->email));
        }

        // Update session information
        if ($result) {
            $result = $session->update(array('type'=>$new_type), array('id'=>$session->id));
        }
        return $result;
    }

    /**
     * Create session
     * @param array $post
     * @param bool $repeat: repeat session
     * @return array
     */
    public function make($post=array(), $repeat=true) {
        $post['date'] = (!empty($post['date'])) ? $post['date'] : $this->date;
        $post['start_date'] = $post['date'];
        $post['end_date'] = (!empty($post['to_repeat']) && $post['to_repeat'] == 1) ? $post['end_date'] : $post['date'];
        if (!$this->is_available($post)) {
            $class_vars = get_class_vars("Session");
            $content = $this->parsenewdata($class_vars, $post, array('presids','speakers', 'max_nb_session', 'default'));

            // Add session to the database
            if ($this->db->addcontent($this->tablename, $content)) {
                AppLogger::get_instance(APP_NAME, get_class($this))->info("New session created on {$this->date}");

                if ($repeat && (!empty($post['to_repeat']) && $post['to_repeat'] == 1)) $this->repeat($post);
                $result['status'] = true;
                $result['msg'] = 'Session successfully created!';

            } else {
                AppLogger::get_instance(APP_NAME, get_class($this))->error("Could not create session on {$this->date}");
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
     * Update session status
     * @return bool
     */
    public function update_status() {
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
     * @param int $session_id: session id
     * @return bool
     */
    public static function isBooked($session_id) {
        $session = new self();
        $data = $session->getInfo(array('id'=>$session_id));
        if ($data === false) {
            return false;
        } elseif ($data[0]['nbpres']<$data[0]['slots']) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get session info
     * @param array $id: session id
     * @return array|bool
     */
    public function getInfo(array $id) {
        // Get the associated presentations
        $data = $this->get($id);
        if (!empty($data)) {
            foreach ($data as $key=>$item) {
                $data[0] = $this->getPresids($data[0]);
            }
            return $data;
        } else {
            return false;
        }
    }

    /**
     * Removes duplicate sessions
     */
    public function clean_duplicates() {
        $sql = "SELECT * FROM {$this->tablename}";
        $req = $this->db->send_query($sql);
        $data = array();
        while ($row = $req->fetch_assoc()) {
            $data[] = $row;
        }

        if (!empty($data)) {
            foreach ($data as $key=>$info) {
                $sessions = $this->db->select($this->tablename, array('*'), array('date'=>$info['date']));
                if (count($sessions) > 1) {
                    $sessions = array_slice($sessions, 1);
                    foreach ($sessions as $id=>$row) {
                        $this->delete(array('id'=>$row['id']));
                    }
                }
            }
        }
    }

    /**
     * Show session details
     * @param bool $show
     * @param bool $prestoshow
     * @return string
     */
    public function showsessiondetails(array $data, $show=true,$prestoshow=false) {
        if ($this->type == 'none') {
            return "No journal club this day.";
        } elseif (count($this->presids) == 0) {
            return "There is no presentation planned for this session yet.";
        }

        $content = "
            <div style='background-color: rgba(255,255,255,.5); padding: 5px; margin-bottom: 10px;'>
                <div style='margin: 0 5px 5px 0;'><b>Type: </b>{$data['type']}</div>
                <div style='display: inline-block; margin: 0 0 5px 0;'><b>Date: </b>{$data['date']}</div>
                <div style='display: inline-block; margin: 0 5px 5px 0;'><b>From: </b>{$data['start_time']}<b> To: </b>{$data['end_time']}</div>
            <div style='display: inline-block; margin: 0 5px 5px 0;'><b>Room: </b>{$data['room']}</div><br>
            </div>";

        $presentations_list = '';
        $i = 0;
        foreach ($this->presids as $presid) {
            if ($prestoshow != false && $presid != $prestoshow) continue;

            $pres = new Presentation($presid);
            $presentations_list .= $pres->mail_details($show);
            $i++;
        }

        $content .= "
            <div style='color: #444444; margin-bottom: 10px;  border-bottom:1px solid #DDD; font-weight: 500; font-size: 1.2em;'>
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
     */
    public function repeatAll($max_date=null, $session_to_plan=1) {
        foreach ($this->get_repeated() as $key=>$item) {
            $this->repeat($item, $session_to_plan, $max_date);
        };
    }

    /**
     * Repeat sessions to be repeated
     * @param array $item : session data
     * @param int $session_to_plan
     * @param null $max_date
     * @internal param array $data
     */
    public function repeat(array $item, $session_to_plan=1, $max_date=null) {
        if (is_null($max_date) && $item['end_date'] === "never") {
            $max_date =  date('Y-m-d', strtotime("now + {$session_to_plan} days"));
        } else {
            $max_date = (is_null($max_date)) ? $item['end_date'] : $max_date;
        }
        self::recursive_repeat($item, $max_date);

    }

    /**
     * Recursively repeatAll sessions
     * @param $item
     * @param $max_date
     * @return bool
     */
    private static function recursive_repeat($item, $max_date) {
        $data = $item;

        // Get next date
        $data['date'] = date('Y-m-d', strtotime("{$item['date']} + {$item['frequency']} days"));

        if (new DateTime($data['date']) <= new DateTime($max_date)) {
            $session = new Session();

            $result = $session->make($data, false);
            if ($result['status']) {
                AppLogger::get_instance(APP_NAME, __CLASS__)->info("Session on {$item['date']} created");
                $item['repeated'] = 1;
                $session->update($item, array('date'=>$item['date']));
            } else {
                AppLogger::get_instance(APP_NAME, __CLASS__)->info("Session on {$item['date']} could not be created");
            }
            self::recursive_repeat($data, $max_date);
        }
        return true;
    }

    /**
     * Check consistency between presentations and sessions table
     * @return array
     */
    public static function checkDb() {
        $result = array('status'=>true, 'msg'=>null);
        $session = new self();
        if ($session->db->isColumn($session->tablename, 'time')) {
            $req = $session->db->send_query("SELECT date,jc_time FROM " . $session->db->tablesname['Presentation']);
            while ($row = $req->fetch_assoc()) {
                if (!$session->is_exist(array('date'=>$row['date']))) {
                    $result = $session->make(array('date'=>$row['date'],'time'=>$row['jc_time']));
                    if (!$result['status']) {
                        $result['msg'] = "<p class='sys_msg warning'>'" . $session->db->tablesname['Session'] . "' not updated</p>";
                    }
                }
            }
            return $result;
        } else {
            return $result;
        }
    }

    /**
     * Patch session table and update start and end time/date if not specified yet
     * @return bool
     */
    public static function patch_time() {
        $Session = new self();
        if (AppDb::getInstance()->isColumn($Session->tablename, 'time')) {
            foreach ($Session->all() as $key=>$item) {
                $data = $item[0];
                if (is_null($data['start_time'])) {
                    $time = explode(',', $data['time']);
                    $new_data = array();
                    $new_data['start_time'] = date('H:i:s', strtotime($time[0]));
                    $new_data['end_time'] = date('H:i:s', strtotime($time[1]));
                    $new_data['start_date'] = $data['date'];
                    $new_data['end_date'] = $data['date'];
                    if (!$Session->update($new_data, array('id'=>$data['id']))) {
                        AppLogger::get_instance(APP_NAME, __CLASS__)->info("Session {$data['id']} could not be updated");
                        return false;
                    }
                }
            }
            return AppDb::getInstance()->delete_column($Session->tablename, 'time');
        } else {
            return true;
        }
    }


    /* MODEL */

    /**
     * Retrieve all elements from the selected table
     * @param array $id
     * @param array $filter
     * @return array|mixed
     */
    public function all(array $id=null, array $filter=null) {
        $dir = (!is_null($filter) && isset($filter['dir'])) ? strtoupper($filter['dir']):'DESC';
        $param = (!is_null($filter) && isset($filter['order'])) ? "ORDER BY `{$filter['order']}` " . $dir : "ORDER BY start_time ASC";

        if (!is_null($id)) {
            $search = array();
            foreach ($id as $field=>$value) {
                $search[] = "{$field}='{$value}'";
            }
            $search = "WHERE " . implode('AND ', $search);
        } else {
            $search = null;
        }

        $sql = "SELECT *, id as session_id, type as session_type
                FROM {$this->tablename} s
                 LEFT JOIN 
                    (SELECT date as pres_date, type as pres_type, session_id as p_session_id, id_pres, title, orator, username  FROM ". AppDb::getInstance()->getAppTables('Presentations').") p
                        ON s.id=p.p_session_id
                 LEFT JOIN 
                    (SELECT username, fullname FROM " . AppDb::getInstance()->getAppTables('Users'). ") u
                        ON u.username=p.username
                 {$search} {$param}";

        $req = $this->db->send_query($sql);
        $data = array();
        while ($row = $req->fetch_assoc()) {
            if (!isset($data[$row['session_id']])) $data[$row['session_id']] = array();
            $data[$row['session_id']][] = $row;
        }
        return $data;
    }

    /**
     * Get presentations and speakers
     * @param array $data: session information
     * @return array $data: updated session information
     */
    private function getPresids(array $data) {
        $sql = "SELECT p.id_pres,u.fullname 
            FROM " . AppDb::getInstance()->getAppTables('Presentations') . " p
                INNER JOIN " . AppDb::getInstance()->getAppTables('Users'). " u
                ON p.username=u.username                
            WHERE p.session_id='{$data['id']}'";
        $req = $this->db->send_query($sql);
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
    public function get_repeated() {
        $sql = "SELECT * FROM {$this->tablename}
                  WHERE to_repeat=1 and repeated=0 and end_date>=CURDATE()";
        $req = $this->db->send_query($sql);
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
    public function getNextDates($limit=null) {
        $sql = "SELECT id,date FROM {$this->tablename} WHERE date>CURDATE() ORDER BY date ASC";

        $sql .= (!is_null($limit)) ? " LIMIT {$limit}": null;
        $req = $this->db->send_query($sql);

        $sessions = array();
        while ($row = $req->fetch_assoc()) {
            $sessions[] = $row['date'];
        }
        if (empty($sessions)) {$sessions = false;}
        return $sessions;
    }

    /**
     * Get upcoming dates
     * @param null|int $limit
     * @return array|bool
     */
    public function getNext($limit=null) {
        $sql = "SELECT * FROM {$this->tablename} WHERE date>CURDATE() ORDER BY date ASC";

        $sql .= (!is_null($limit)) ? " LIMIT {$limit}": null;
        $req = $this->db->send_query($sql);

        $sessions = array();
        while ($row = $req->fetch_assoc()) {
            $sessions[] = $row;
        }
        if (empty($sessions)) {$sessions = false;}
        return $sessions;
    }

    /**
     * Get journal club days
     * @param int $nb_session
     * @param bool $from
     * @return array
     */
    public static function getJcDates($nb_session=20, $from=null) {
        $start_date = is_null($from) ? date('Y-m-d', strtotime('now')) : date('Y-m-d', strtotime($from));
        $jc_days = array();
        for ($s=0; $s<$nb_session; $s++) {
            $what = ($s == 0) ? 'this' : 'next';
            $start_date = date('Y-m-d', strtotime($what . " " . AppConfig::getInstance()->jc_day . " " . $start_date));
            $jc_days[] = $start_date;
        }
        return $jc_days;
    }

    /**
     * Check if time slot is available
     * @param $session_data: session information
     * @return bool
     */
    public function is_available(array $session_data) {
        if (isset($session_data['start_time']) && isset($session_data['end_time'])) {
            $overlap = " and (start_time>'{$session_data['start_time']}' and start_time<'{$session_data['end_time']}') 
                  or (end_time>'{$session_data['start_time']}' and end_time<'{$session_data['end_time']}')";
        } else {
            $overlap = null;
        }
        $overlap = null;
        $sql = "SELECT * FROM {$this->tablename} WHERE date='{$session_data['date']}'{$overlap}";
        $data = $this->db->send_query($sql)->fetch_assoc();
        return !is_null($data);
    }

    /* VIEWS */

    /**
     * Render session viewer
     * @param string $sessions: list of sessions
     * @return string
     */
    public static function sessionViewerContainer($sessions) {
        return "
        <div class='section_content'>
            <div class='form-group'>
                <input type='date' class='selectSession datepicker' data-status='false' data-view='edit' name='date' />
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
    public static function sessionManager($sessionEditor, $form) {
        return "
            <div class='section_content'>
                <div class='session_viewer_container'>
                    <h3>Edit a session</h3>
                    <div class='form-group'>
                        <input type='date' class='selectSession datepicker' name='date' data-status='admin' data-view='edit' />
                        <label>Session to show</label>
                    </div>
                    <div id='sessionlist'>{$sessionEditor}</div>
                </div>
                <div class='session_viewer_container'>
                    <h3>Add a new session</h3>
                    {$form}
                </div>
            </div>
        ";
    }

    /**
     * Render list of days
     * @return null|string
     */
    private static function dayList() {
        $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
        $list = null;
        foreach ($days as $day) {
            $selected = ($day == AppConfig::getInstance()->jc_day) ? 'selected' : null;
            $list .= "<option value='{$day}' {$selected}>" . ucfirst($day) . "</option>";
        }
        return $list;
    }

    /**
     * Render form for modifying session default settings
     * @return string
     */
    public static function default_settings_form() {
        return "
        <section>
            <h2>Default Session Settings</h2>
            <div class='section_content'>
                <form method='post' action='php/form.php'>
                    <div class='feedback' id='feedback_jcsession'></div>
                    <input type='hidden' name='config_modify' value='true'>
                    <div class='form-group'>
                        <input type='text' name='room' value='" . AppConfig::getInstance()->room . "'>
                        <label>Room</label>
                    </div>
                    <div class='form-group'>
                        <select name='jc_day'>
                            " . self::dayList() . "
                        </select>
                        <label for='jc_day'>Day</label>
                    </div>
                    <div class='form-group'>
                        <input type='time' name='jc_time_from' value='" . AppConfig::getInstance()->jc_time_from . "' />
                        <label>From</label>
                    </div>
                    <div class='form-group'>
                        <input type='time' name='jc_time_to' value='" . AppConfig::getInstance()->jc_time_to . "' />
                        <label>To</label>
                    </div>
                    <div class='form-group'>
                        <input type='number' name='max_nb_session' value='" . AppConfig::getInstance()->max_nb_session . "'/>
                        <label>Slots/Session</label>
                    </div>
                    <p style='text-align: right'><input type='submit' name='modify' value='Modify' id='submit' class='processform'/></p>
                </form>
            </div>
        </section>
        ";
    }

    /**
     * Generate session type selection list
     * @param array $data
     * @param $session_type
     * @return string
     */
    public static function type_list(array $data, $session_type) {
        $type_options = "<option value='none' style='background-color: rgba(200,0,0,.5); color:#fff;'>Delete</option>";
        foreach ($data as $type) {
            $selected = ($type == $session_type) ? 'selected' : null;
            $type_options .= "<option value='{$type}' {$selected}>{$type}</option>";
        }
        return $type_options;
    }

    /**
     * Render session settings panel
     * @param array $session: session information
     * @param array $types: list of session types
     * @return string
     */
    public static function session_settings(array $session, array $types) {
        $type_list = self::type_list($types, $session['session_type']);

        $repeat_options = null;
        foreach (array('Yes'=>1, 'No'=>0) as $label=>$value) {
            $selected = $session['to_repeat'] === $value ? 'selected' : null;
            $repeat_options .= "<option value={$value} {$selected}>{$label}</option>";
        }
        $show_repeat_settings = $session['to_repeat'] == 1 ? 'display: visible' : 'display: none';

        return "
            <h3>Settings</h3>
            <form action='php/form.php' method='post'>
                <div class='session_type'>
                    <div class='form-group field_small inline_field' style='width: 100%;'>
                        <select class='mod_session' name='type'>
                        {$type_list}
                        </select>
                        <label>Type</label>
                    </div>
                </div>
                <div class='session_time'>
                    <div class='form-group field_small inline_field'>
                        <input type='time' class='mod_session' name='start_time' value='{$session['start_time']}' />
                        <label>From</label>
                    </div>
                    <div class='form-group field_small inline_field'>
                        <input type='time' class='mod_session' name='end_time' value='{$session['end_time']}' />
                        <label>To</label>
                    </div>
                    <div class='form-group field_small inline_field'>
                        <input type='text' class='mod_session' name='room' value='{$session['room']}' />
                        <label>Room</label>
                    </div>
                    <div class='form-group field_small inline_field'>
                        <input type='number' class='mod_session' name='slots' value='{$session['slots']}' />
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
            </form>";
    }

    /**
     * Render session editor
     * @param Session $session
     * @param string $presentations
     * @return string
     */
    public static function sessionViewer(Session $session, $presentations) {
        return "
            <div style='display: block; margin: 10px auto 0 auto;'>
                <!-- header -->
                <div style='display: block; margin: 0 0 15px 0; padding: 0; text-align: justify; min-height: 20px; height: auto; line-height: 20px; width: 100%;'>
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
     * Render nothing to display message
     * @return string
     */
    public static function nothingToDisplay() {
        $url = URL_TO_APP . 'index.php?page=organizer/sessions';
        return "
            <div class='sys_msg status'>
                <p>Sorry, there is nothing to display yet.</p>
                <p>You must set a journal club day from <a href='{$url}'>Admin>Sessions, 'Default settings'</a></p>
            </div>
        ";
    }

    /**
     * Render form to add session
     * @param array $data
     * @return string
     */
    public static function add_session_form(array $data) {
        $AppConfig = AppConfig::getInstance();
        return "
        <form action='php/form.php' method='post'>
            <div class='form-group'>
                <select name='type'> ". self::type_list($data['types'], $AppConfig->get_setting('default_type')) . "</select>
                <label>Type</label>
            </div>
            <div class='form-group field_small inline_field'>
                <input type='date' name='date' class='datepicker' data-view='edit' data-status='false' value='{$data['date']}' />
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
                <input type='number' name='slots' value='{$data['slot']}' />
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
                    <input type='hidden' name='add_session' value='true'/>
                    <input type='submit' value='Add' class='processform'/>
                </div>
            <div>
        </form>
        ";
    }

    /**
     * Get session types
     * @return array
     */
    public static function session_type() {
        $Sessionstype = "";
        $opttypedflt = "";
        foreach (AppConfig::getInstance()->session_type as $type) {
            $Sessionstype .= self::render_type($type, 'session');
            $opttypedflt .= $type == AppConfig::getInstance()->get_setting('default_type') ?
                "<option value='$type' selected>$type</option>"
                : "<option value='$type'>$type</option>";
        }
        return array(
            'types'=>$Sessionstype,
            "options"=>$opttypedflt
        );
    }

    /**
     * Render session/presentation type list
     * @param $data
     * @param $type
     * @return string
     */
    private static function render_type($data, $type) {
        return "
                <div class='type_div' id='session_$data'>
                    <div class='type_name'>".ucfirst($data)."</div>
                    <div class='type_del' data-type='$data' data-class='{$type}'></div>
                </div>
            ";
    }

    /**
     * Get presentation types
     * @param array|null $exclude
     * @return string
     */
    public static function presentation_type(array $exclude=null) {
        $prestype = "";
        $options = null;
        foreach (AppConfig::getInstance()->pres_type as $type) {
            if (!is_null($exclude) && in_array($type, $exclude)) continue;

            $prestype .= self::render_type($type, 'pres');
            $options .= $type == AppConfig::getInstance()->get_setting('default_type') ?
                "<option value='$type' selected>$type</option>"
                : "<option value='$type'>$type</option>";
        }
        return array(
            'types'=>$prestype,
            "options"=>$options
        );
    }

    /**
     * Show session slot as empty
     * @return string
     */
    public static function no_session() {
        return "<div style='display: block; margin: 0 auto 10px 0; padding-left: 10px; font-size: 14px; 
                    font-weight: 600; overflow: hidden;'>
                    No Journal Club this day</div>";
    }

    /**
     * Show presentation slot as empty
     * @param array $data : session data
     * @param bool $show_button: display add button
     * @return string
     */
    public static function emptySlot(array $data, $show_button=true) {
        $url = URL_TO_APP . "index.php?page=member/submission&op=edit&date=" . $data['date'];
        $addButton = ($show_button) ? "
            <a href='{$url}' class='leanModal' data-controller='Presentation' data-action='get_form'
             data-params='modal' data-section='presentation' data-operation='edit' 
            data-session_id='{$data['id']}'>
                <div class='add-button'></div>
            </a>" : null;

        $content = "
                <div>{$addButton}</div>";
        return self::slotContainer(array('name'=>'Free slot', 'button'=>$content, 'content'=>null));
    }

    /**
     * Show presentation slot as empty
     * @return string
     */
    public static function emptySlotEdit() {
        return self::slotContainer(array('name'=>'Free slot', 'button'=>null, 'content'=>
            "<span style='font-size: 14px; font-weight: 500; color: #777;'>" . Presentation::speakerList() . "</span>
            "));
    }

    /**
     * Template for slot container
     * @param array $data
     * @param null $div_id
     * @return string
     */
    public static function slotContainer(array $data, $div_id=null) {
        return "
            <div class='pres_container ' id='{$div_id}' data-id='{$div_id}'>
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
     * Template for slot container
     * @param array $data
     * @return string
     */
    public static function mail_slotContainer(array $data) {
        return "
            <div class='pres_container '>
                <div class='pres_type' style='display: inline-block; width: 50px; font-weight: 600; color: #222222; vertical-align: middle; 
                    text-transform: capitalize;'>
                    {$data['name']}
                </div>
                <div class='pres_info' style='display: inline-block; width: 210px; margin-left: 20px; vertical-align: middle;'>
                    {$data['content']}
                </div>
                <div class='pres_btn' style='display: inline-block; width: 35px; vertical-align: middle;'>{$data['button']}</div>
            </div>
            ";
    }

    /**
     * Template for slot container
     * @param array $data
     * @param null $div_id
     * @return string
     */
    public static function slotEditContainer(array $data, $div_id=null) {
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
    public static function sessionContainer(array $data) {
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
     * @return string
     */
    public static function sessionEditContainer(array $data, $presentations) {
        $settings = self::session_settings($data, AppConfig::getInstance()->session_type);
        return "
            <div class='session_div session_editor_div' id='session_{$data['session_id']}' 
            data-id='{$data['session_id']}'>
                <div class='session_editor_core'>
                    <div class='session_settings'>
                        {$settings}
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
    public static function dayContainer(array $data) {
        return "
            <div class='day_container'>
                <!-- Day header -->
                <div class='day_header'>
                    <div class='day_date'>{$data['date']}</div>
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
    public static function session_details(array $data, $presentations) {
        return "
            <div style='background-color: rgba(255,255,255,.5); padding: 5px; margin-bottom: 10px;'>
                <div style='margin: 0 5px 5px 0;'><b>Type: </b>{$data['type']}</div>
                <div style='display: inline-block; margin: 0 0 5px 0;'><b>Date: </b>{$data['date']}</div>
                <div style='display: inline-block; margin: 0 5px 5px 0;'><b>From: </b>{$data['start_time']}<b> To: </b>{$data['end_time']}</div>
                <div style='display: inline-block; margin: 0 5px 5px 0;'><b>Room: </b>{$data['room']}</div><br>
            </div>
            <div style='color: #444444; margin-bottom: 10px;  border-bottom:1px solid #DDD; font-weight: 500; font-size: 1.2em;'>
            Presentations
            </div>
            {$presentations}
            ";
    }

    /**
     * Render session details in email body
     * @param array $data
     * @param $presentations
     * @return string
     */
    public static function mail_session_details(array $data, $presentations) {
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

}
