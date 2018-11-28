<?php

/**
 *
 * @author Florian Perdreau (fp@florianperdreau.fr)
 * @copyright Copyright (C) 2016 Florian Perdreau
 * @license <http://www.gnu.org/licenses/agpl-3.0.txt> GNU Affero General Public License v3
 *
 * This file is part of DropMVC.
 *
 * DropMVC is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * DropMVC is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with DropMVC.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Patches;

/**
 * Patch for Session table
 */
class Session
{

    /**
     * List of patches
     *
     * @var array
     */
    public static $patches = array(
        'patch1'=>'patchTime'
    );
    
    /**
     * Patch session table and update start and end time/date if not specified yet
     *
     * @return bool
     */
    public static function patchTime()
    {
        $Session = new \includes\Session();
        $db = \includes\Db::getInstance();
        if ($db->isColumn($db->getAppTables('Session'), 'time')) {
            foreach ($Session->all() as $key => $item) {
                if (is_null($item['start_time'])) {
                    $time = explode(',', $item['time']);
                    if (count($time) < 2) {
                        continue;
                    }
                    $new_data = array();
                    $new_data['start_time'] = date('H:i:s', strtotime($time[0]));
                    $new_data['end_time'] = date('H:i:s', strtotime($time[1]));
                    $new_data['start_date'] = $item['date'];
                    $new_data['end_date'] = $item['date'];
                    if (!$Session->update($new_data, array('id'=>$item['id']))) {
                        \includes\Logger::getInstance(APP_NAME, __CLASS__)->info("Session {$item['id']} could not be 
                        updated");
                        return false;
                    }
                }
            }
            return $db->deleteColumn($db->getAppTables('Session'), 'time');
        } else {
            return true;
        }
    }
}
