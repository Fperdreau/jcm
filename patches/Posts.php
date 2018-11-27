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
     * Apply patch to table
     *
     * @return bool
     */
    public static function patch()
    {
        return self::patchTable();
    }
    
    /**
     * Convert post username
     *
     * @return bool
     */
    private static function patchTable()
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
}
