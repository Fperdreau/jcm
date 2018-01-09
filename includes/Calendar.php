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
    private function getCalendarParams($force_select = true)
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
     * Get calendar parameters
     *
     * @param boolean $force_select
     * @return array
     */
    public function getParams($force_select = false)
    {
        try {
            $result = $this->getCalendarParams($force_select);
            return $result;
        } catch (\Exception $e) {
        }
    }

    public function updateUserAvailability($date)
    {
        $username = $_SESSION['username'];
        //$date = $data['date'];
        $Availability = new Availability();
        $Presentation = new Presentation();

        $result['status'] = $Availability->edit(array('date'=>$date, 'username'=>$username));
        if ($result['status']) {
            // Check whether user has a presentation planned on this day, if yes, then we delete it and notify the user that
            // this presentation has been canceled
            $data = $Presentation->get(array('date'=>$date, 'orator'=>$username));
            if (!empty($data)) {
                $speaker = new Users($username);
                $Assignment = new Assignment();
                $session = new Session($date);
                $Presentation = new Presentation($data['id']);
                $info['type'] = $session->type;
                $info['date'] = $session->date;
                $info['presid'] = $data['id'];
                $result['status'] = $Presentation->delete_pres($data['id']);
                if ($result['status']) {
                    $result['status'] = $Assignment->updateAssignment($speaker, $info, false, true);
                }
            }
        }
    }
}
