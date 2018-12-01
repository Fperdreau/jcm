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
class Media
{

    /**
     * List of patches
     *
     * @var array
     */
    public static $patches = array(
        'patch1'=>'patchTable'
    );

    /**
     * Patch upload table: add object name ('Presentation').
     */
    public static function patchTable()
    {
        $Presentation = new \includes\Presentation();
        $Media = new \includes\Media();
        foreach ($Media->all() as $key => $item) {
            $data = $Presentation->get(array('id_pres'=>$item['presid']));
            if (!empty($data)) {
                if (!$Media->update(
                    array('obj'=>'Presentation', 'obj_id'=>$data['id']),
                    array('presid'=>$item['presid'])
                )
                ) {
                    return false;
                }
            }
        }
        return true;
    }
}
