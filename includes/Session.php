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
use \DateTime;

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
     * Get default session settings
     * @return array: default settings
     */
    public function getDefaults($selectedDate = null)
    {
        $date = is_null($selectedDate) ? $this->getJcDates($this->settings['jc_day'], 1)[0]
         : date('Y-m-d', strtotime($selectedDate));
        self::$default = array(
            'id'=>null,
            'date'=>$date,
            'start_date'=>$date,
            'end_date'=>$date,
            'frequency'=>null,
            'slots'=>$this->settings['max_nb_session'],
            'type'=>$this->settings['default_type'],
            'start_time'=>$this->settings['jc_time_from'],
            'end_time'=>$this->settings['jc_time_to'],
            'room'=>$this->settings['room'],
            'repeated'=>0,
            'recurrent'=>0
        );
        return self::$default;
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
        $post['end_date'] = (!empty($post['recurrent']) && $post['recurrent'] == 1) ? $post['end_date'] : $post['date'];

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

                if ($initial && (!empty($post['recurrent']) && $post['recurrent'] == 1)) {
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
     * Test if event is recurrent
     *
     * @param $id
     * @return bool
     */
    public function isRecurrent($id)
    {
        $data = $this->get(array('id'=>$id));
        return (int)$data['recurrent'] == 1;
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
        foreach ($this->getRepeated() as $key => $item) {
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
            $this->update(
                array('repeated'=>$item['repeated']),
                array('date'=>$item['date'], 'event_id'=>$item['event_id'])
            );

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
     * Display the details of next session
     * @param bool $mail
     * @return string
     */
    public function showNextSession($mail = false)
    {
        $data = $this->getUpcoming(1);
        $data = reset($data);
        return self::renderSession($data);
    }

    /**
     * Display the details of session
     * @param bool $mail
     * @return string
     */
    public function showSession($id, $mail = false)
    {
        $data = $this->all(array('id'=>$id));
        $data = reset($data);
        return self::renderSession($data);
    }

    /**
     * Render session details
     *
     * @param array $data: session date
     * @return string
     */
    private function renderSession(array $data)
    {
        if (!empty($data)) {
            if ($this->isAvailable(array('date'=>$data['date']))) {
                return self::sessionDetails($data, Session::nothingPlannedThisDay());
            } else {
                return self::sessionDetails($data, $this->getSessionDetails($data, $data['date']));
            }
        } else {
            return self::noUpcomingSession();
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
        $Pres = new Presentation();
        for ($i=0; $i<$data['slots']; $i++) {
            if (isset($data['content'][$i]) && !is_null($data['content'][$i]['id'])) {
                $content .= Presentation::mailDetails(
                    $Pres->getInfo($data['content'][$i]['id']),
                    true
                );
            } else {
                $content .= self::emptySlot($data);
            }
        }
        return $content;
    }

    /**
     * Check if session is full
     *
     * @param string $id: session id
     *
     * @return boolean
     */
    public function isFull($id)
    {
        $data = $this->all(array('s.id'=>$id));
        if ($data !== false) {
            $data = reset($data);
            return count($data['content']) >= (int)$data['slots'];
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
    public static function showDetails(array $data, $show = true, $prestoshow = false)
    {
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
        foreach ($data['content'] as $key => $item) {
            if ($prestoshow != false && $item['id'] != $prestoshow) {
                continue;
            }
            $presentations_list .= Presentation::mailDetails($item, $show);
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
    public function cleanDuplicates()
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
                {$search['cond']} {$param} {$limit}";
        $req = $this->db->sendQuery($sql);

        $data = array();
        if ($req !== false) {
            while ($row = $req->fetch_assoc()) {
                if (!isset($data[$row['session_id']])) {
                    $data[$row['session_id']] = $row;
                    $data[$row['session_id']] = $this->getPresids($data[$row['session_id']]);
                    $data[$row['session_id']]['content'] = $this->getSlots($row['session_id']);
                }
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
        $sql = "SELECT date, type, session_id, id, title, orator, username, u.fullname 
                FROM " . $this->db->getAppTables('Presentation') . " p
                LEFT JOIN 
                    (SELECT username as user_name, fullname FROM " . $this->db->getAppTables('Users'). ") u
                ON u.user_name=p.username
                WHERE p.session_id={$id}
        ";
        
        $req = $this->db->sendQuery($sql);
        $data = array();
        if ($req !== false) {
            while ($row = $req->fetch_assoc()) {
                $data[] = $row;
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
        $sql = "
        SELECT p.id, u.fullname, p.username AS speaker_username
            FROM " . Db::getInstance()->getAppTables('Presentation') . " p
                LEFT JOIN " . Db::getInstance()->getAppTables('Users'). " u
                ON p.username=u.username   
            WHERE p.session_id='{$data['id']}'

        UNION

        SELECT p.id, u.fullname, p.username AS speaker_username
        FROM " . Db::getInstance()->getAppTables('Presentation') . " p
            RIGHT JOIN " . Db::getInstance()->getAppTables('Users'). " u
            ON p.username=u.username
            WHERE p.session_id='{$data['id']}'
        ";
        
        $req = $this->db->sendQuery($sql);
        $data['presids'] = array();
        $data['speakers'] = array();
        $data['usernames'] = array();
        while ($row = $req->fetch_assoc()) {
            $data['presids'][] = $row['id'];
            $data['speakers'][] = $row['fullname'];
            $data['usernames'][] = $row['speaker_username'];
        }
        $data['nbpres'] = count($data['presids']);

        return $data;
    }

    /**
     * Get list of sessions to repeat
     * @return array: list of sessions to be repeated
     */
    public function getRepeated()
    {
        $sql = "SELECT * FROM {$this->tablename}
                  WHERE recurrent=1 and repeated=0 and end_date>date";
        $req = $this->db->sendQuery($sql);
        $data = array();
        while ($row = $req->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    /**
     * Get upcoming sessions information
     *
     * @param null|int $limit: number of sessions to retrieve (all by default)
     * @param null|string $date: reference date (today by default)
     *
     * @return array|bool
     */
    public function getUpcoming($limit = null, $date = null)
    {
        if (is_null($date)) {
            $date = date('Y-m-d');
        }
        $data = $this->all(array('date >'=>$date), array('order'=>'date', 'dir'=>'ASC', 'limit'=>$limit));
        return empty($data) ? false : $data;
    }

    /**
     *  Get upcoming dates
     *
     * @param null|int $limit: number of dates to retrieve (all by default)
     * @param null|string $date: reference date (today by default)
     *
     * @return array|bool
     */
    public function getNextDates($limit = null, $date = null)
    {
        if (is_null($date)) {
            $date = date('Y-m-d');
        }
        $sql = "SELECT DISTINCT date FROM {$this->tablename} WHERE date>='{$date}' ORDER BY date ASC";
        $sql .= !is_null($limit) ? " LIMIT {$limit}": null;
        $req = $this->db->sendQuery($sql);

        $data = array();
        while ($row = $req->fetch_assoc()) {
            $data[] = $row['date'];
        }
        return empty($data) ? false : $data;
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
     * Show presentation slot as empty
     *
     * @param array $data : session data
     * @param bool $show_button: display add button
     *
     * @return string
     */
    private static function emptySlot(array $data)
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

        return "
        <div style='width: 100%; padding-bottom: 5px; margin: auto auto 10px auto; 
        background-color: rgba(255,255,255,.5); border: 1px solid #bebebe;'>
            <div style='display: block; margin: 0 0 15px 0; padding: 0; text-align: justify; 
            min-height: 20px; height: auto; line-height: 20px; width: 100%;'>
                <div style='vertical-align: top; text-align: left; margin: 5px; font-size: 16px;'>
                    <span style='color: #222; font-weight: 900;'>Free slot</span>
                </div>
            </div>
            <div style='width: 100%; text-align: justify; margin: auto; box-sizing: border-box;'>
                <div style='max-width: 80%; margin: 5px;'>
                    <div style='font-weight: bold; font-size: 20px;'>{$leanModalUrl}</div>
                </div>

            </div>
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
     * Message displayed when there is no upcoming session planned
     *
     * @return string
     */
    private static function noUpcomingSession()
    {
        return "
            <div style='font-size: 16px; font-weight: 600;'>There is no upcoming session.</div>
        ";
    }

    /**
     * Render session details in email body
     *
     * @param array $data
     * @param $presentations
     * @return string
     */
    private static function sessionDetails(array $data, $presentations)
    {
        return "
            <div style='display: block; margin: 5px; padding: 0; text-align: justify; min-height: 20px; height: auto;
                line-height: 20px; width: 100%; color: #222; font-weight: 900; font-size: 16px;'>
                <div style='display: inline-block; width: 20px; vertical-align: middle;'>
                    <img src='". URL_TO_IMG . 'calendar_bk.png' . "'style='width: 100%; vertical-align:middle;'/>
                </div>
                <div style='display: inline-block; vertical-align: middle;'>{$data['date']}</div>
            </div>
            <div style='display: table;  width: 100%; margin: 0; text-align: left; font-weight: 300; height: 30px; 
            line-height: 30px; border: 0; padding: 0;'>
                <div style='display: table-cell; vertical-align: top; min-height: 20px; line-height: 20px; 
                padding: 5px; font-weight: 600; color: #777; font-size:16px;'>
                    {$data['session_type']}
                </div>

                <div style='display: table-cell; vertical-align: top; min-height: 20px; line-height: 20px; 
                padding: 5px; text-align: right; font-size: 12px;'>
                    <div style='display: inline-block;'>
                        <div style='display: inline-block; width: 20px; vertical-align: middle;'>
                            <img src='" . URL_TO_IMG . 'clock_bk.png' . "' style='width: 100%; vertical-align:middle;'/>
                        </div>
                        <div style='display: inline-block; vertical-align: middle;'>
                        " . date('H:i', strtotime($data['date'])) . ' - ' . date('H:i', strtotime($data['date'])) . "
                        </div>
                    </div>
                    <div style='display: inline-block;'>
                        <div style='display: inline-block; width: 20px; vertical-align: middle;'>
                            <img src='" . URL_TO_IMG . 'location_bk.png' . "' style='width: 100%; 
                            vertical-align:middle;'/>
                        </div>
                        <div style='display: inline-block; vertical-align: middle;'>{$data['room']}</div>
                    </div>
                </div>  
            </div>
    
            <div class='session_details_content'>
                {$presentations}
            </div>
        ";
    }
}
