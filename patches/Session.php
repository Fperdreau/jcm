<?php

namespace Patch;

class Session
{
    
    /**
     * Patch session table and update start and end time/date if not specified yet
     * @return bool
     */
    public static function patchTime()
    {
        $Session = new self();
        if (Db::getInstance()->isColumn($Session->tablename, 'time')) {
            foreach ($Session->all() as $key => $item) {
                if (is_null($item['start_time'])) {
                    $time = explode(',', $item['time']);
                    $new_data = array();
                    $new_data['start_time'] = date('H:i:s', strtotime($time[0]));
                    $new_data['end_time'] = date('H:i:s', strtotime($time[1]));
                    $new_data['start_date'] = $item['date'];
                    $new_data['end_date'] = $item['date'];
                    if (!$Session->update($new_data, array('id'=>$item['id']))) {
                        Logger::getInstance(APP_NAME, __CLASS__)->info("Session {$item['id']} could not be updated");
                        return false;
                    }
                }
            }
            return Db::getInstance()->delete_column($Session->tablename, 'time');
        } else {
            return true;
        }
    }
}