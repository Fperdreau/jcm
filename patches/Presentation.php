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
 * Patch for Presentation table
 */
class Presentation
{

    /**
     * List of patches
     *
     * @var array
     */
    public static $patches = array(
        'patch1'=>'mergeTable',
        'patch2'=>'patchUploads',
        'patch3'=>'patchSessionId'
    );

    /**
     * Patch presentation table
     *
     * @return bool
     */
    public static function patch()
    {
        foreach ($patches as $key => $fun) {
            if (!$result = call_user_func(array(__class__, $fun))) {
                return $result;
            }
        }
        return $result;
    }

    /**
     * Copy content of old presentations table to new presentation table
     *
     * @return bool
     */
    public static function mergeTable()
    {
        $db = \includes\Db::getInstance();
        $self = new \includes\Presentation();
        if (!is_null($db->getAppTables('Presentations'))) {
            $sql = "SELECT * FROM {$db->getAppTables('Presentations')}";
            $req = $db->sendQuery($sql);
            while ($item = $req->fetch_assoc()) {
                if (!$self->get(array('title LIKE'=>json_encode($item['title'])))) {
                    $id = $item['id'];
                    unset($item['id']);
                    if ($self->add($item)) {
                        $sql = "DELETE FROM {$db->getAppTables('Presentations')} WHERE id={$id}";
                        $res = $db->sendQuery($sql);
                    } else {
                        return false;
                    }
                }
            }

            // Drop old Presentation table
            $db->deletetable($db->getAppTables('Presentations'));
        }
        return true;
    }

    /**
     * Patch upload table: add object name ('Presentation').
     */
    public static function patchUploads()
    {
        $Publications = new \includes\Presentation();
        $Media = new \includes\Media();
        foreach ($Publications->all() as $key => $item) {
            if ($Media->isExist(array('id'=>$item['id']))) {
                if (!$Media->update(array('obj'=>'Presentation'), array('id'=>$item['id']))) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Patch Presentation table by adding session ids if missing
     *
     * @return bool
     */
    public static function patchSessionId()
    {
        $Publications = new \includes\Presentation();
        $Session = new \includes\Session();
        foreach ($Publications->all() as $key => $item) {
            if ($Session->isExist(array('date'=>$item['date']))) {
                $session_info = $Session->getInfo(array('date'=>$item['date']));
                if (!$Publications->update(array('session_id'=>$session_info['id']), array('id'=>$item['id']))) {
                    \includes\Logger::getInstance(APP_NAME, __CLASS__)->error('Could not update publication 
                    table with new session id');
                    return false;
                }
            }
        }
        return true;
    }
}
