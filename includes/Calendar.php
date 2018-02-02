<?php

namespace includes;

/**
 * Calendar UI
 *
 * Shows planned sessions.
 */
class Calendar
{

    /**
     * Session instance
     *
     * @var Session
     */
    private static $instance;

    /**
     * Class constructor
     *
     */
    public function __construct()
    {
    }

    /**
     * Get Session instance
     *
     * @return Session
     */
    private static function factory()
    {
        if (is_null(self::$instance)) {
            self::$instance = new Session();
        }
        return self::$instance;
    }

    /**
     * Get calendar data
     *
     * @param bool $force_select
     *
     * @return array
     * @throws Exception
     */
    public static function getData($force_select = true)
    {
        $formatdate = array();
        $nb_pres = array();
        $type = array();
        $slots = array();
        $session_ids = array();

        foreach (self::factory()->all() as $session_id => $session_data) {
            // Count how many presentations there are for this day
            $type[] = $session_data['type'];
            $slots[] = $session_data['slots'];
            $formatdate[] = date('d-m-Y', strtotime($session_data['date']));
            $nb_pres[] = count($session_data['content']);
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
            "max_nb_session"=>self::factory()->getSettings('max_nb_session'),
            "jc_day"=>self::factory()->getSettings('jc_day'),
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
     *
     * @param string|null $date: day to show
     * @param int $n: number of days to display
     *
     * @return string
     */
    public static function show($date = null, $n = 4)
    {
        return self::layout(self::getCalendarContent($date, $n), $date);
    }

    /**
     * Get list of future presentations (home page/mail)
     *
     * @param int $nsession: number of sessions to get
     *
     * @return string
     */
    public static function getCalendarContent($date = null, $nSession = 4)
    {
        // Get next planned date
        $today = date('Y-m-d', time());
        if (is_null($date)) {
            $dates = self::factory()->getNextDates($nSession);
        } else {
            $dates = array($date);
        }

        if (!empty($dates)) {
            // Repeat sessions
            self::factory()->repeatAll(end($dates));
    
            $content = "";
            foreach ($dates as $day) {
                $content .= self::getDayContent(self::factory()->all(array('s.date'=>$day)), $day, false);
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
    public static function getDayContent(array $data, $day, $edit = false)
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
        return self::dayContainer(array('date'=>$date, 'content'=>$dayContent));
    }

    /**
     * Get and render session content
     *
     * @param array $data: session data
     * @param string $date: selected date
     * @param bool $edit: Get editor or viewer
     *
     * @return string
     */
    private static function getSessionContent(array $data, $date)
    {
        $content = null;
        $nSlots = max(count($data['content']), (int)$data['slots']);
        for ($i=0; $i<$nSlots; $i++) {
            if (isset($data['content'][$i])) {
                $content .= self::slotContainer(
                    Presentation::inSessionSimple($data['content'][$i]),
                    $data['content'][$i]['id']
                );
            } else {
                $content .= self::emptySlotContainer($data, SessionInstance::isLogged());
            }
        }
        return self::sessionContainer($data, $content);
    }

    /**
     * Render session viewer
     *
     * @param string $sessions: list of sessions
     *
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

    /**
     * Render date selection input
     *
     * @param string $selectedDate: selected date (Y-m-d)
     *
     * @return string
     */
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
     * @param array $data
     *
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
     * Render session slot
     *
     * @param array $data
     *
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
                            <div>" . date('H:i', strtotime($data['start_time']))
                             . '-' . date('H:i', strtotime($data['end_time'])) . "</div>
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
     *
     * @param array $data
     * @param null|string $div_id: container id
     *
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
     *
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
                    Nothing planned this day</div>";
    }
}
