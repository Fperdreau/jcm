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
class DigestMaker
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
     * Delete invalid entries
     *
     * @return bool
     */
    public static function patchTable()
    {
        $DigestMaker = new \includes\DigestMaker();        
        foreach ($DigestMaker->all() as $key => $item) {
            $name = explode('\\', $item['name']);
            if (!class_exists($item['name'], false) || count($name) < 2) {
                if (!$DigestMaker->delete(array('id'=>$item['id']))) {
                    \includes\Logger::getInstance(APP_NAME, __CLASS__)->info("DigestMaker {$item['name']} could not be 
                    updated");
                    return false;
                }
            }
        }
        return true;
    }      
}