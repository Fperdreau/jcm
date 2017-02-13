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

class Sessions extends AppTable {

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
        "frequency" => array("INT(2)", 0),
        "start_date" => array("DATE", false),
        "end_date" => array("DATE", false),
        "start_time" => array("TIME", false),
        "end_time" => array("TIME", false),
        "primary" => "id");

    public $max_nb_session;

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct("Session", $this->table_data);

        $this->max_nb_session = AppConfig::getInstance()->max_nb_session;
        $this->registerDigest();
        $this->registerReminder();
    }

    /**
     * Register into Reminder table
     */
    private function registerReminder() {
        $reminder = new ReminderMaker();
        $reminder->register(get_class());
    }

    /**
     * Register into DigestMaker table
     */
    private function registerDigest() {
        $DigestMaker = new DigestMaker();
        $DigestMaker->register(get_class());
    }

    /**
     *  Get all sessions
     * @param null $opt
     * @return array|bool
     */
    public function getsessions($opt=null) {
        $sql = "SELECT date FROM $this->tablename";
        if ($opt == true || is_null($opt)) {
            $sql .= " WHERE date>CURDATE()";
        } elseif ($opt !== null) {
            $sql .= " WHERE date>=$opt";
        }
        $sql .= " ORDER BY date ASC";
        $req = $this->db->send_query($sql);
        $sessions = array();
        while ($row = mysqli_fetch_assoc($req)) {
            $sessions[] = $row['date'];
        }
        if (empty($sessions)) {$sessions = false;}
        return $sessions;
    }

    /**
     * Get journal club days
     * @param int $nsession
     * @param bool $from
     * @return array
     */
    public function getjcdates($nsession=20,$from=false) {
        /** @var AppConfig $AppConfig */
        $AppConfig = new AppConfig();

        $startdate = ($from == false) ? strtotime('now'):strtotime($from);
        $jc_days = array();
        for ($s=0; $s<$nsession; $s++) {
            $what = ($s == 0) ? 'this':'next';
            $startdate = strtotime("$what $AppConfig->jc_day",$startdate);
            $jc_days[] = date('Y-m-d',$startdate);
        }
        return $jc_days;
    }

    /**
     * Check if date already exist
     * @param $date
     * @return bool
     */

    public function dateexists($date) {
        $sql = "SELECT * FROM {$this->tablename} WHERE date='{$date}'";
        $data = $this->db->send_query($sql)->fetch_assoc();
        return !is_null($data);
    }

    /**
     * Check if the date of presentation is already booked
     * @param $date
     * @return string
     */
    public function isbooked($date) {
        $session = new Session($date);

        if ($session === false) {
            return "Free";
        } elseif ($session->nbpres<$this->max_nb_session) {
            if ($session->nbpres == 0) {
                return "Free";
            } else {
                return "Booked";
            }
        } else {
            return "Booked out";
        }
    }

    /**
     * Get all sessions
     * @param $date
     * @param string $view
     * @return string
     */
    public function sessionManager($date=null, $view='simple') {
        // Get next JC date
        if ($date == null) {
            $date = $this->getjcdates(1);
            $date = $date[0];
        }

        // Check if session exists, otherwise create one
        if ($this->dateexists($date)) {
            $session = new Session($date);
        } else {
            return self::no_session($date);
        }

        // Get presentations
        $nbPres = max($session->slots, count($session->presids));
        $presentations = "";
        for ($i=0;$i<$nbPres;$i++) {
            $presid = (isset($session->presids[$i]) ? $session->presids[$i] : false);
            $pres = new Presentation($presid);
            if (!empty($pres->id_pres)) {
                if ($view == 'edit') {
                    $presentations .= Session::slotContainer(Presentation::inSessionEdit($pres),
                        $pres->id_pres);
                } else {
                    $presentations .= Session::slotContainer(Presentation::inSessionSimple($pres, $pres->username),
                        $pres->id_pres);
                }

            } else {
                $presentations .= Session::emptySlot($date);
            }
        }
        if ($view === 'edit') {
            return self::sessionEditor($session, $presentations, AppConfig::getInstance()->session_type);
        } else {
            return self::sessionViewer($session, $presentations);
        }

    }

    /**
     * Render non planned session
     * @param string $date
     * @return string
     */
    private static function no_session($date) {
        return "
            <div class='session_div' id='session_{$date}' data-id='{$date}'>
            <div class='session_header'>
                <div class='session_date'>{$date}</div>
                <div class='session_status'></div>
            </div>
            <div class='session_core'>
               <div class='session_presentations'>
                    Nothing planned yet
                </div>
            </div>
        </div>
        ";
    }

    /**
     * Generate session type selection list
     * @param array $data
     * @param $session_type
     * @return string
     */
    private static function type_list(array $data, $session_type) {
        $type_options = "<option value='none' style='background-color: rgba(200,0,0,.5); color:#fff;'>NONE</option>";
        foreach ($data as $type) {
            if ($type === $session_type) {
                $type_options .= "<option value='$type' selected>$type</option>";
            } else {
                $type_options .= "<option value='$type'>$type</option>";
            }
        }
        return $type_options;
    }

    /**
     * Render session settings panel
     * @param Session $session
     * @param array $types
     * @return string
     */
    public static function session_settings(Session $session, array $types) {
        $type_list = self::type_list($types, $session->type);

        $time = explode(',', $session->time);
        $time_from = $time[0];
        $time_to = str_replace(' ', '', $time[1]);

        return "
            <h3>Settings</h3>
            <div class='session_type'>
                <div class='form-group' style='width: 100%;'>
                    <select class='mod_session_type' name='type'>
                    {$type_list}
                    </select>
                    <label>Type</label>
                </div>
            </div>
            <div class='session_time'>
                <div class='form-group field_small inline_field'>
                    <input type='time' class='mod_session' name='time_from' value='{$time_from}' />
                    <label>From</label>
                </div>
                <div class='form-group field_small inline_field'>
                    <input type='time' class='mod_session' name='time_from' value='{$time_to}' />
                    <label>To</label>
                </div>
                <div class='form-group field_small inline_field'>
                    <input type='text' class='mod_session' name='room' value='{$session->room}' />
                    <label>Room</label>
                </div>
                <div class='form-group field_small inline_field'>
                    <input type='number' class='mod_session' name='slots' value='{$session->slots}' />
                    <label>Slots</label>
                </div>
            </div>";
    }

    /**
     * Render session editor
     * @param Session $session
     * @param $presentations
     * @param array $types
     * @return string
     */
    public static function sessionEditor(Session $session, $presentations, array $types) {
        $settings = self::session_settings($session, $types);
        return "
            <div class='session_div' id='session_{$session->date}' data-id='{$session->date}'>
            <div class='session_header'>
                <div class='session_date'>{$session->date}</div>
                <div class='session_status'>{$session->type}</div>
            </div>
            <div class='session_core'>
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
     * Display the upcoming presentation(home page/mail)
     * @param bool $mail
     * @return string
     */
    public function shownextsession($mail=false) {
        $show = $mail === true || (!empty($_SESSION['logok']) && $_SESSION['logok'] === true);
        $dates = $this->getsessions(true);
        if ($dates !== false) {
            $session = new Session($dates[0]);
            $content = $session->showsessiondetails($show);
        } else {
            $content = "Nothing planned yet.";
        }
        return $content;
    }

    /**
     * Get list of future presentations (home page/mail)
     * @param int $nsession
     * @param null $mail
     * @return string
     */
    public function showfuturesession($nsession = 4, $mail=null) {
        // Get future planned dates
        $dates = $this->getsessions(1);
        $dates = ($dates == false) ? false: $dates[0];

        // Get journal club days
        $jc_days = $this->getjcdates($nsession, $dates);

        // Get futures journal club sessions
        $content = "";
        foreach ($jc_days as $day) {
            $session = new Session($day);
            $sessioncontent = $session->show_session($mail);

            $type = ($session->type == "none") ? "No Meeting":ucfirst($session->type);
            $date = date('d M y',strtotime($session->date));
            $content .= "
            <div style='display: block; margin: 10px auto 0 auto;'>
                <!-- header -->
                <div style='display: block; margin: 0 0 15px 0; padding: 0; text-align: justify; min-height: 20px; height: auto; line-height: 20px; width: 100%;'>
                    <div style='vertical-align: top; text-align: left; margin: 5px; font-size: 16px;'>
                        <span style='color: #222; font-weight: 900;'>{$date}</span>
                        <span style='color: rgba(207,81,81,.5); font-weight: 900; font-size: 20px;'> . </span>
                        <span style='color: #777; font-weight: 600;'>{$type}</span>
                    </div>
                </div>

                <div style='padding: 10px 20px 10px 10px; background-color: rgba(239,239,239,.6); margin: 0 0 0 10px; 
                border-left: 2px solid rgba(175,175,175,.8);'>
                    $sessioncontent
                </div>

            </div>";
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

        foreach ($user->getadmin() as $key=>$info) {
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
        $content['body'] = $this->shownextsession(true);;
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
        $content['body'] = $this->shownextsession(true);;
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
            $result = $session->update(array('nbpres'=>0, 'status'=>'Free', 'type'=>'none'));
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
            $result = $session->update(array('type'=>$new_type));
        }
        return $result;
    }

    public function patch_session_info() {
        $this->all();
    }
}


class SessionView {

}


class Session extends Sessions {
/**
 * Child class of Sessions
 * Instantiates session objects
 */

    public $id;
    public $date;
    public $status = "FREE";
    public $time;
    public $type;
    public $nbpres = 0;
    public $room;
    public $slots;
    public $repeated = false;
    public $frequency;
    public $start_date;
    public $end_date;
    public $presids = array();
    public $speakers = array();

    /**
     * @param null $date
     */
    public function __construct($date=null) {
        parent::__construct();
        $this->time = AppConfig::getInstance()->jc_time_from . ',' . AppConfig::getInstance()->jc_time_to;
        $this->type = AppConfig::$session_type_default[0];
        $this->date = $date;
        $this->room = AppConfig::getInstance()->room; // Default room number
        $this->slots = AppConfig::getInstance()->max_nb_session;

        if ($date != null) {
            self::get($date);
        }
    }

    /**
     * Create session
     * @param $post
     * @return bool
     */
    public function make($post=array()) {
        $this->date = (!empty($post['date'])) ? $post['date']:$this->date;
        if (!$this->dateexists($this->date)) {
            $class_vars = get_class_vars("Session");
            $content = $this->parsenewdata($class_vars, $post, array('presids','speakers', 'max_nb_session'));

            // Add session to the database
            if ($this->db->addcontent($this->tablename, $content)) {
                AppLogger::get_instance(APP_NAME, get_class($this))->info("New session created on {$this->date}");
                return true;
            } else {
                AppLogger::get_instance(APP_NAME, get_class($this))->error("Could not create session on {$this->date}");
                return false;
            }
        } else {
            $this->get($this->date);
            return $this->update($post);
        }
    }

    /**
     * Update session status
     * @return bool
     */
    public function updatestatus() {
        $this->nbpres = count($this->presids);
        if ($this->type=="none") {
            $status = "Booked out";
        } elseif ($this->nbpres == 0) {
            $status = "Free";
        } elseif ($this->nbpres<$this->slots) {
            $status = "Booked";
        } else {
            $status = "Booked out";
        }

        // Only update DB if new status is different
        if ($this->status !== $status) {
            if ($this->db->updatecontent($this->tablename,array("status"=>$status, "nbpres"=>$this->nbpres),array('date'=>$this->date))) {
                AppLogger::get_instance(APP_NAME, get_class($this))->info("Status of session {$this->date} set to: {$status}");
                return true;
            } else {
                AppLogger::get_instance(APP_NAME, get_class($this))->error("Could not change status of session {$this->date} to: {$status}");
                return false;
            }
        }
        return true;
    }

    /**
     * Get session info
     * @param null $date
     * @return Session|bool
     */
    public function get($date=null) {
        $this->date = ($date !== null) ? $date : $this->date;

        // Get the associated presentations
        $this->getPresids();

        $class_vars = get_class_vars("Session");
        $sql = "SELECT * FROM $this->tablename WHERE date='$this->date'";
        $req = $this->db -> send_query($sql);
        $data = $req->fetch_assoc();
        if (!empty($data)) {
            foreach ($data as $varname=>$value) {
                if (array_key_exists($varname,$class_vars)) {
                    if (!empty($value)) {
                        $this->$varname = htmlspecialchars_decode($value);
                    }
                }
            }
            $this->updatestatus();
            return $this;
        } else {
            return false;
        }
    }

    /**
     * Get presentations and speakers
     */
    public function getPresids() {
        $sql = "SELECT id_pres,orator FROM ".$this->db->tablesname['Presentation']." WHERE date='$this->date'";
        $req = $this->db->send_query($sql);
        $this->presids = array();
        $this->speakers = array();
        while ($row = mysqli_fetch_assoc($req)) {
            $this->presids[] = $row['id_pres'];
            $this->speakers[] = $row['orator'];
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
                $sql = "SELECT * FROM {$this->tablename} WHERE date='{$info['date']}'";
                $req = $this->db->send_query($sql);
                $sessions = array();
                while ($row = $req->fetch_assoc()) {
                    $sessions[] = $row;
                }
                if (count($sessions) > 1) {
                    $sessions = array_slice($sessions, 1);
                    foreach ($sessions as $id=>$row) {
                        $this->db->deletecontent($this->tablename, 'id', $row['id']);
                    }
                }
            }
        }
    }

    /**
     * Update session info
     * @param array $post
     * @return bool
     */
    public function update($post=array()) {
        $this->status = parent::isbooked($this->date);

        $class_vars = get_class_vars("Session");
        $content = $this->parsenewdata($class_vars,$post, array('speakers','presids', 'max_nb_session'));
        if (!$this->db->updatecontent($this->tablename,$content,array('date'=>$this->date))) {
            AppLogger::get_instance(APP_NAME, get_class($this))->error("Could not update session ({$this->date})");
        }

        $this->get();
        return true;
    }

    /**
     * Show session (list)
     * @param bool $mail
     * @return string
     */
    public function show_session($mail=true) {
        if ($this->type == 'none') return self::no_session();

        $content = "";
        $max = (count($this->presids) < $this->slots) ? $this->slots:count($this->presids);
        for ($i=0;$i<$max;$i++) {
            $presid = (isset($this->presids[$i]) ? $this->presids[$i] : false);
            $pub = new Presentation($presid);
            if (!empty($pub->id_pres)) {
                $speaker = new User($pub->orator);
                $content .= self::slotContainer(Presentation::inSessionSimple($pub, $speaker->fullname), $pub->id_pres);
            } else {
                $content .= self::emptySlot($this->date);
            }

        }
        return $content;
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
            if ($type == AppConfig::$session_type_default) {
                $opttypedflt .= "<option value='$type' selected>$type</option>";
            } else {
                $opttypedflt .= "<option value='$type'>$type</option>";
            }
        }
        return array(
            'types'=>$Sessionstype,
            "default"=>$opttypedflt
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
                    <div class='type_del' data-type='$data' data-class='{$type}'>
                    </div>
                </div>
            ";
    }

    /**
     * Get presentation types
     * @return string
     */
    public static function presentation_type() {
        $prestype = "";
        foreach (AppConfig::getInstance()->pres_type as $type) {
                $prestype .= self::render_type($type, 'pres');
        }
        return $prestype;
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
     * @param string $date: session date
     * @return string
     */
    public static function emptySlot($date) {
        $url = URL_TO_APP . "index.php?page=member/submission&op=edit&date=" . $date;
        $addButton = "<button class='add_btn'><a href='{$url}' class='leanModal' id='modal_trigger_pubmod' data-section='submission_form' 
                        data-date='{$date}'>Add</a></button>";

        $content = "
                <div>{$addButton}</div>";
        return self::slotContainer(array('name'=>'Free slot', 'content'=>$content, 'button'=>null));
    }

    /**
     * Template for slot container
     * @param array $data
     * @param null $div_id
     * @return string
     */
    public static function slotContainer(array $data, $div_id=null) {
        $modal_trigger = (!is_null($div_id)) ? 'leanModal modal_trigger_pubcontainer' : null;
        return "
            <div class='pres_container {$modal_trigger}' id='{$div_id}' data-section='submission_form' 
            data-id='{$div_id}' style='display: block; position: relative; margin: auto auto 10px auto; 
            font-size: 0.9em; font-weight: 300; overflow: hidden; border: 1px dashed rgb(200, 200, 200); border-radius: 5px; 
            box-sizing: border-box; padding: 5px;'>
                <div class='pres_type' style='display: inline-block; font-weight: 600; color: #222222; vertical-align: top; 
                    text-transform: capitalize;'>
                    {$data['name']}
                </div>
                <div class='pres_info' style='display: inline-block; margin-left: 20px; min-width: 200px;'>
                    {$data['content']}
                </div>
                <div class='pres_btn' style='display: inline-block; vertical-align: middle;'>{$data['button']}</div>
            </div>";
    }

    /**
     * Show session details
     * @param bool $show
     * @param bool $prestoshow
     * @return string
     */
    public function showsessiondetails($show=true,$prestoshow=false) {
        $time = explode(',',$this->time);
        $time_from = $time[0];
        $time_to = $time[1];
        if ($this->type == 'none') {
            return "No journal club this day.";
        } elseif (count($this->presids) == 0) {
            return "There is no presentation planned for this session yet.";
        }

        $content = "
            <div style='background-color: rgba(255,255,255,.5); padding: 5px; margin-bottom: 10px;'>
                <div style='margin: 0 5px 5px 0;'><b>Type: </b>{$this->type}</div>
                <div style='display: inline-block; margin: 0 0 5px 0;'><b>Date: </b>$this->date</div>
                <div style='display: inline-block; margin: 0 5px 5px 0;'><b>From: </b>$time_from<b> To: </b>$time_to</div>
            <div style='display: inline-block; margin: 0 5px 5px 0;'><b>Room: </b>" . AppConfig::getInstance()->room. "</div><br>
            </div>";

        $presentations_list = '';
        $i = 0;
        foreach ($this->presids as $presid) {
            if ($prestoshow != false && $presid != $prestoshow) continue;

            $pres = new Presentation($presid);
            $presentations_list .= $pres->showDetails($show);
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
}
