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

/**
 * Class Autoloader
 * Loads class automatically
 */
class Autoloader {

    /**
     * register autoloader
     */
    static function register() {
        spl_autoload_register(array(__CLASS__, 'autoload'));
    }

    /**
     * Load called class
     * @param $class_name string
     */
    static function autoload($class_name) {
        // For composer
        require PATH_TO_APP . 'vendor/autoload.php';

        // For App
        $filename = PATH_TO_INCLUDES . $class_name . '.php';
        if (is_file($filename)) {
            require_once $filename;
        }

        // For Scheduled Tasks
        $filename = PATH_TO_TASKS . $class_name . '.php';
        if (is_file($filename)) {
            require_once $filename;
        }

        // For Plugins
        $filename = PATH_TO_PLUGINS . $class_name . DS . $class_name . '.php';
        if (is_file($filename)) {
            require_once $filename;
        }

    }

}