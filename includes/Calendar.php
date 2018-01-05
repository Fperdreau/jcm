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
}
