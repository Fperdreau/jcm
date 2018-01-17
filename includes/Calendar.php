<?php

namespace includes;

class Calendar
{

    public function __construct()
    {
    }

    /**
     * Session instance
     *
     * @var Session
     */
    private static $instance;

    /**
     * Get Session instance
     *
     * @return Session
     */
    private function factory()
    {
        if (is_null($this::$instance)) {
            $this::$instance = new Session();
        }
        return $this::$instance;
    }

    /**
     * Get calendar data
     *
     * @param bool $force_select
     * @return array
     * @throws Exception
     */
    public function getCalendarParams($force_select = true)
    {
        $formatdate = array();
        $nb_pres = array();
        $type = array();
        $slots = array();
        $session_ids = array();

        foreach ($this->factory()->all() as $session_id => $session_data) {
            // Count how many presentations there are for this day
            $nb = count($session_data['content']);
            $type[] = $session_data['type'];
            $slots[] = $session_data['slots'];
            $formatdate[] = date('d-m-Y', strtotime($session_data['date']));
            $nb_pres[] = $nb;
            $session_ids[] = $session_id;
        }

        // Get user's availability and assignments
        if (SessionInstance::isLogged()) {
            $username = $_SESSION['username'];
            $Availability = new Availability();
            $availabilities = array();
            foreach ($Availability->all(array('username'=>$username)) as $info) {
                // Format date
                $fdate = explode("-", $info['date']);
                $day = $fdate[2];
                $month = $fdate[1];
                $year = $fdate[0];
                $availabilities[] = "$day-$month-$year";
            }

            // Get user's assignments
            $Presentation = new Presentation();
            $assignments = array();
            foreach ($Presentation->getList($username) as $row => $info) {
                // Format date
                $fdate = explode("-", $info['date']);
                $day = $fdate[2];
                $month = $fdate[1];
                $year = $fdate[0];
                $assignments[] = "$day-$month-$year";
            }
        } else {
            $assignments = array();
            $availabilities = array();
        }

        return array(
            "Assignments"=>$assignments,
            "Availability"=>$availabilities,
            "max_nb_session"=>$this->factory()->getSettings('max_nb_session'),
            "jc_day"=>$this->factory()->getSettings('jc_day'),
            "today"=>date('d-m-Y'),
            "booked"=>$formatdate,
            "nb"=>$nb_pres,
            "slots"=>$slots,
            "renderTypes"=>$type,
            "force_select"=>$force_select,
            "session_id"=>$session_ids
        );
    }

    /**
     * Get session viewer
     * @param string|null $date: day to show
     * @param int $n: number of days to display
     * @return string
     */
    public function show($date = null, $n = 4)
    {
        
        return self::layout($this->getCalendarContent($date, $n), $date);
    }

    /**
     * Get list of future presentations (home page/mail)
     * @param int $nsession
     * @return string
     */
    public function getCalendarContent($date = null, $nSession = 4)
    {
        // Get next planned date
        $today = date('Y-m-d', time());
        if (is_null($date)) {
            $dates = $this->factory()->getNextDates($nSession);
        } else {
            $dates = array($date);
        }

        if (!empty($dates)) {
            // Repeat sessions
            $this->factory()->repeatAll(end($dates));
    
            $content = "";
            foreach ($dates as $day) {
                $content .= $this->getDayContent($this->factory()->all(array('s.date'=>$day)), $day, false);
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
                $dayContent .= self::getSessionContent($session_data, $date);
            }
        } else {
            $dayContent .= self::nothingPlannedThisDay();
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
    private function getSessionContent(array $data, $date)
    {
        $content = null;
        $nSlots = max(count($data['content']), $data['slots']);
        for ($i=0; $i<$nSlots; $i++) {
            if (isset($data['content'][$i]) && !is_null($data['content'][$i]['id_pres'])) {
                $content .= self::slotContainer(
                    Presentation::inSessionSimple($data['content'][$i]),
                    $data['content'][$i]['id_pres']
                );
            } else {
                $content .= self::emptySlotContainer($data, SessionInstance::isLogged());
            }
        }
        return self::sessionContainer($data, $content);
    }

    /**
     * Render session viewer
     * @param string $sessions: list of sessions
     * @return string
     */
    public static function layout($sessions, $selectedDate = null)
    {
        return "
        <div class='section_content'>
            <div id='dateInput'>". self::dateInput($selectedDate) . "</div>
            <div id='sessionList'>{$sessions}</div>
        </div>
        ";
    }

    private static function dateInput($selectedDate = null)
    {
        $url = Router::buildUrl('Calendar', 'getCalendarContent');
        return "
        <div class='form-group'>
            <input type='date' class='selectSession datepicker viewerCalendar' data-url='{$url}'
            name='date' value='{$selectedDate}' data-destination='#sessionList'/>
            <label>Filter</label>
        </div>";
    }

    /**
     * Render day container
     *
     * @param array $data: day's data
     *
     * @return string
     */
    private static function day(array $data)
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
     * Render session slot
     * @param array $data
     * @return string
     */
    public static function sessionContainer(array $data, $content)
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
                    {$content}
                </div>
            </div>
             ";
    }

    /**
     * Template for slot container
     * @param array $data
     * @param null $div_id
     * @return string
     */
    public static function slotContainer(array $data, $div_id = null)
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
     * Show presentation slot as empty
     *
     * @param array $data : session data
     * @param bool $show_button: display add button
     * @return string
     */
    public static function emptySlotContainer(array $data, $show_button = true)
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
        return self::slotContainer(array('name'=>'Free slot', 'button'=>$content, 'content'=>null));
    }


    /**
     * Render event slot
     *
     * @param array $data: event's data
     *
     * @return string
     */
    private static function slot(array $data)
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
            {$data['presentations']}
            ";
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

}
