<?php
/**
 * Created by PhpStorm.
 * User: Florian
 * Date: 25/09/14
 * Time: 08:54
 */

class Calendar {

    private $db;

    public $dayLabels = array("S","M","T","W","T","F","S");
    public $monthLabels = array("January", "February", "March", "April", "May", "June", "July",
                          "August", "September", "October", "November", "December");
    public $cur_year;
    public $cur_month;
    public $cur_day;
    public $daylimits;
    public $next_month;
    public $prev_month;
    public $next_year;
    public $prev_year;
    public $select_day = NULL;
    public $jcday = NULL;
    public $booked = array();

    /**
     * Constructor
     * @param $db
     * @param $jcday
     * @param $booked
     * @param $select_day
     * @param $nbMonthToShow
     */
    public function __construct(DbSet $db,$jcday,$booked,$select_day,$nbMonthToShow){
        $this->db = $db;
        $this->jcday = $jcday;
        $this->booked = $booked;
        $this->get_curdate();
        $this->get_envdate();
        $this->getdaylimits();
        $this->select_day = $select_day;
        $this->nbMonthToShow = $nbMonthToShow;
    }

    /**
     * Get current date (day, month, year)
     */
    function get_curdate() {
        $this -> cur_month = date("n");
        $this -> cur_year = date("Y");
        $this -> cur_day = date("j");
    }

    /**
     * Get previous & next month/year
     */
    function get_envdate() {
        $this->prev_year = $this->cur_year - 1;
        $this->next_year = $this->cur_year + 1;
        $this->prev_month = $this->cur_month-1;
        $this->next_month = $this->cur_month+1;

        if ($this->prev_month == 0 ) {
            $this->prev_month = 12;
            $this->prev_year = $this->cur_year - 1;
        }

        if ($this->next_month == 13 ) {
            $this->next_month = 1;
            $this->next_year = $this->cur_year + 1;
        }
    }

    /**
     * Update calendar
     * @param $month
     * @param $year
     */
    function update_nav($month,$year) {
        $this->cur_month = $month;
        $this->cur_year = $year;
        $this->get_envdate();
    }

    private function createNavi(){
        $navi_bar = "
                <div class='cal_nav_btn' id='prev' data-month='$this->prev_month' data-year='$this->prev_year'></div>
                <div class='cal_nav_btn' id='next' data-month='$this->prev_month' data-year='$this->next_year'></div>
            ";
        return $navi_bar;
    }

    public function show_calendar($selected) {
        // Navigation bar
        $cal_nav = $this->createNavi();

        // Heading
        $cal_days = "";
        foreach ($this->dayLabels as $label) {
            $cal_days .= "<div class='cal_label'>$label</div>";
        }

        // Days
        $days = $this->getdays($selected);
        $d = 0;
        $cal_core = "";
        for ($r=1;$r<=5;$r++) {
            $weekcontent = "";
            for ($dw=1;$dw<=7;$dw++) {
                $day = $days[$d];
                $weekcontent .= self::showday($day);
                $d++;
            }
            $cal_core .= "<div class='cal_row'>$weekcontent</div>";
        }
        $calendar_block =  "
        <div class='cal'>
            <div class='cal_nav'>$cal_nav</div>
            <div class='cal_days'>$cal_days</div>
            <div class='cal_core'>
                $cal_core
            </div>
        </div>
        ";
        return $calendar_block;
    }

    /**
     * Get starting and ending day of a month
     * @return array
     */
    function getdaylimits() {
        $timestamp = mktime(0,0,0,$this->cur_month,1,$this->cur_year);
        $maxday = date("t",$timestamp); // Last day of the current month
        $thismonth = getdate($timestamp);
        $startday = $thismonth['wday'];
        $this->daylimits = array('start'=>$startday,'end'=>(int)$maxday);
    }

    /**
     * Get months to display
     * @return array
     */
    public function getmonths() {
        $months = array();
        for ($m=0;$m<$this->nbMonthToShow;$m++) {
            $months[] = date('m-Y',strtotime("$this->cur_year-$this->cur_month-01 + $m month"));
        }
        return $months;
    }

    public function addmonth($month,$year) {
        if ($month == 13) {
            $month = 1;
            $year++;
        } elseif ($month == 0) {
            $month = 12;
            $year--;
        }
        $days = $this->days($month,$year);
        return "
        <div class='cal_month' id='month_$month-$year'>
        $days
        </div>
        ";
    }

    public function make() {
        $months = $this->getmonths();
        $cal_core = "";
        foreach ($months as $month) {
            $exploded = explode('-',$month);
            $days = $this->days($exploded[0],$exploded[1]);
            $cal_core .= "
                <div class='cal_month' id='month_$month'>
                    $days
                </div>
            ";
        }

        return "
        <div class='cal'>
            <div class='cal_days'>
                <div class='cal_nav_btn' id='prev'></div>
                <div class='cal_nav_btn' id='next'></div>
            </div>
            <div class='cal_wrapper'>
                <div class='cal_core'>
                    $cal_core
                </div>
            </div>
        </div>
        ";
    }

    public function make_navbar($months) {
        $content = "";
        foreach ($months as $this_month) {
            $exploded = explode('-',$this_month);
            $month = $exploded[0];
            $month = $this->monthLabels[$month];
            $year = $exploded[1];
            $content .= "
                <div class='cal_label'>$month $year</div>
            ";
        }
        return $content;
    }

    public function days($month, $year) {
        $sessions = new Sessions($this->db);
        $AppConfig = new AppConfig($this->db);
        $formatted = date('F',strtotime("$year-$month-01"));
        $jcdays = $sessions->getjcdates(5,$month.'-'.$year);
        var_dump((int)$month);
        var_dump($jcdays);
        $content = "<div class='cal_month_label'>".$this->monthLabels[$month-1]." $year</div>";
        foreach ($jcdays as $day) {
            $session = new Session($this->db,$day);
            $daynum = end(explode('-',$day));

            $booked = ($session->status == 'Booked out') ? ' default-full':'';
            $session_type = ucfirst($session->type);

            // Presentations
            $presids = $session->presids;
            $desc = "";
            for ($i=0;$i<$AppConfig->max_nb_session;$i++) {
                $pub = (!empty($presids[$i])) ? new Presentation($this->db,$presids[$i]):false;
                if ($pub !== false) {
                    $type = ucfirst($pub->type);
                    $desc .= "
                    <div class='cal_day_pres' id='$pub->id_pres'>
                        <div class='cal_day_pres_type'>$type</div>
                        <div class='cal_day_pres_title'>$pub->title</div>
                    </div>";
                }
            }
            $content .= "
            <div class='cal_day $booked' id='cal_but'>
                <div class='cal_day_back'>$daynum</div>
                <div class='cal_day_content'>
                    <div style='width: 100%;'>
                        <div class='cal_day_header' style='display: inline-block;'>$session_type</div>
                        <div class='cal_day_add_icon'></div>
                    </div>
                    <div class='cal_day_section cal_day_desc'>$desc</div>
                </div>
            </div>";
        }
        return $content;
    }

    /**
     * Get current days of the selected month and their properties
     * @param $selected
     * @return array
     */
    public function getdays($selected) {
        $result = array();
        for ($i=0; $i<35; $i++) {
            if ($i<$this->daylimits['start']) {
                $prev_calendar = new self($this->db,$this->jcday,$this->booked,$this->select_day);
                $prev_calendar->update_nav($this->prev_month,$this->cur_year);
                $month = $prev_calendar->cur_month;
                $year = $prev_calendar->cur_year;
                $daynum = $prev_calendar->daylimits['end'] - ($prev_calendar->daylimits['start'] - $i);
                $ismonth = false;
            } elseif ($i>$this->daylimits['end']-1) {
                $next_calendar = new self($this->db,$this->jcday,$this->booked,$this->select_day);
                $next_calendar->update_nav($this->next_month,$this->cur_year);
                $month = $next_calendar->cur_month;
                $year = $next_calendar->cur_year;
                $daynum = ($i - $this->daylimits['end'] + 1);
                $ismonth = false;
            } else {
                $month = $this->cur_month;
                $year = $this->cur_year;
                $daynum = $i - $this->daylimits['start'] + 1;
                $ismonth = true;
            }
            $time = strtotime("$month/$daynum/$year");
            $cur_date = date('Y-m-d',$time);
            $cur_day = date('l',mktime(0,0,0,$month,$daynum,$year));

            $result[$i] = array(
                'date'=>$cur_date,
                'day'=>$daynum,
                'jcday'=>$cur_day === ucfirst($this->jcday),
                'ismonth'=>$ismonth,
                'selected'=>$cur_date==$selected
            );
        }
        return $result;
    }

    /**
     * @param $day
     * @return string
     */
    function showday($day) {
        $daynum = $day['day'];
        /** @var Session $session */
        $session = new Session($this->db,$day['date']);
        $AppConfig = new AppConfig($this->db);
        $month = $day['ismonth'] == false ? ' default-notmonth' : "";
        $booked = ($session->status == 'Booked out') ? ' default-full':'';
        $jc = ($day['jcday'] == true) ? 'default-jcday':'default-inactive';
        $session_type = ($day['jcday'] == true) ? ucfirst($session->type):"";
        $desc = "";
        for ($i=0;$i<$AppConfig->max_nb_session;$i++) {
            $pub = (!empty($session->presids[$i])) ? new Presentation($this->db,$session->presids[$i]):false;
            if ($pub !== false) {
                $type = ucfirst($pub->type);
                $desc .= "
                    <div class='cal_day_pres' id='$pub->id_pres'>
                        <div class='cal_day_pres_type'>$type</div>
                        <div class='cal_day_pres_title'>$pub->title</div>
                    </div>";
            }
        }

        return "
            <div class='cal_day $month $booked $jc' id='cal_but'>
                <div class='cal_day_back'>$daynum</div>
                <div class='cal_day_content'>
                    <div>
                        <div class='cal_day_header' style='display: inline-block;'>$session_type</div>
                        <div style='display: inline-block;'><div class='cal_day_add_icon'>Add</div></div>
                    </div>
                    <div class='cal_day_section cal_day_desc'>$desc</div>
                </div>
            </div>";
    }
}


