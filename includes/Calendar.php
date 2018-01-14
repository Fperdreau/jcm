<?php

namespace includes;

class Calendar
{
    private static $Session;

    public function __construct()
    {
    }

    /**
     * Get Session instance
     *
     * @return Session
     */
    private function getSessionInstance()
    {
        if (is_null(self::$Session)) {
            self::$Session = new Session();
        }
        return self::$Session;
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
        $status = array();
        $slots = array();
        $session_ids = array();

        foreach ($this->getSessionInstance()->all() as $session_id => $session_data) {
            // Count how many presentations there are for this day
            $nb = 0;
            foreach ($session_data as $key => $data) {
                $nb += !is_null($data['id_pres']) ? 1 : 0;
            }
            $type[] = $session_data[0]['type'];
            $status[] = $session_data[0]['status'];
            $slots[] = $session_data[0]['slots'];
            $formatdate[] = date('d-m-Y', strtotime($session_data[0]['date']));
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
            "max_nb_session"=>$this->getSessionInstance()->getSettings('max_nb_session'),
            "jc_day"=>$this->getSessionInstance()->getSettings('jc_day'),
            "today"=>date('d-m-Y'),
            "booked"=>$formatdate,
            "nb"=>$nb_pres,
            "status"=>$status,
            "slots"=>$slots,
            "renderTypes"=>$type,
            "force_select"=>$force_select,
            "session_id"=>$session_ids
        );
    }

    /**
     * Update user's availability on the selected date
     *
     * @param string $date: selected date (Y-m-d)
     * @return array
     */
    public function updateUserAvailability($date)
    {
        $username = $_SESSION['username'];
        //$date = $data['date'];
        $Availability = new Availability();
        $Presentation = new Presentation();

        $result['status'] = $Availability->edit(array('date'=>$date, 'username'=>$username));
        if ($result['status'] !== false) {
            // Check whether user has a presentation planned on this day, if yes, then we delete it and notify the user that
            // this presentation has been canceled
            $data = $Presentation->get(array('date'=>$date, 'orator'=>$username));
            if (!empty($data)) {
                $speaker = new Users($username);
                $Assignment = new Assignment();
                $session = new Session($data['session_id']);
                $Presentation = new Presentation($data['id']);
                $info['type'] = $session->type;
                $info['date'] = $date;
                $info['presid'] = $data['id'];
                $result['status'] = $Presentation->deleteSubmission($data['id']);
                if ($result['status']) {
                    $result['status'] = $Assignment->updateAssignment($speaker, $info, false, true);
                }
            }
        }
        return $result;
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
}
