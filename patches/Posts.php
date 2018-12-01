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
 * Patch for Posts table
 */
class Posts
{
    
    /**
     * List of patches
     *
     * @var array
     */
    public static $patches = array(
        'patch1'=>'mergeTable',
        'patch2'=>'patchTable'
    );
    
    /**
     * Convert post username
     *
     * @return bool
     */
    public static function patchTable()
    {
        $db = \includes\Db::getInstance();
        $self = new \includes\Posts();

        $req = $db->sendQuery("SELECT * FROM {$db->getAppTables('Posts')}");
        $user = new \includes\Users();
        while ($row = mysqli_fetch_assoc($req)) {
            $data = $user->get(array('fullname'=>$row['username']));
            if (!empty($data)) {
                if (!$self->update(array('username'=>$data['username']), array('id'=>$row['id']))) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Copy content of old Post table to new Posts table
     *
     * @return bool
     */
    public static function mergeTable()
    {
        $db = \includes\Db::getInstance();
        $self = new \includes\Posts();
        if (!is_null($db->getAppTables('Post'))) {
            $sql = "SELECT * FROM {$db->getAppTables('Post')}";
            $req = $db->sendQuery($sql);
            while ($item = $req->fetch_assoc()) {
                if (!$self->get(array('title LIKE'=>json_encode($item['title'])))) {
                    $id = $item['id'];
                    unset($item['id']);
                    if ($self->add($item)) {
                        $sql = "DELETE FROM {$db->getAppTables('Post')} WHERE id={$id}";
                        $res = $db->sendQuery($sql);
                    } else {
                        return false;
                    }
                }
            }
            // Drop old Presentation table
            $db->deletetable($db->getAppTables('Post'));
        }
        return true;
    }
}
