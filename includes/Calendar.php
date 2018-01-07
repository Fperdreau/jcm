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
     * Get calendar parameters
     *
     * @param boolean $force_select
     * @return array
     */
    public function getParams($force_select = false)
    {
        try {
            $result = $this->getSessionInstance()->getCalendarParams($force_select);
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
