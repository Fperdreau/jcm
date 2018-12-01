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
 * Patch for Tasks table
 */
class Tasks
{
    
    /**
     * List of patches
     *
     * @var array
     */
    public static $patches = array(
        'patch1'=>'mergeTable'
    );

    /**
     * Copy content of old Post table to new Posts table
     *
     * @return bool
     */
    public static function mergeTable()
    {
        $db = \includes\Db::getInstance();
        $self = new \includes\Tasks();
        $self->loadAll();
        if (!is_null($db->getAppTables('Crons'))) {
            $sql = "SELECT * FROM {$db->getAppTables('Crons')}";
            $req = $db->sendQuery($sql);
            while ($item = $req->fetch_assoc()) {
                $info = $self->load($item['name']);
                if (!is_null($info)) {
                    $newData = array(
                        'name' => $item['name'],
                        'time' => $item['time'],
                        'frequency' => $item['frequency'],
                        'status' => $item['status'] == 'On' ? 1:0,
                        'options' => $item['options'],
                        'description' => $info['description'],
                        'running' => 0,
                        'path' => $info['path']
                    );
                    if (!$self->isInstalled($item['name'])) {
                        if (!$self->add($newData)) {
                            return false;
                        }
                    } else {
                        if (!$self->update($newData, array('name'=>$name))) {
                            return false;
                        }
                    }
                }
            }
            
            // Drop old Presentation table
            $db->deletetable($db->getAppTables('Crons'));
        }
        return true;
    }
}
