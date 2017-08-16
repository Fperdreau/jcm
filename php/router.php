<?php
/**
 * @author Florian Perdreau (fp@florianperdreau.fr)
 * @copyright Copyright (C) 2014 Florian Perdreau
 * @license <http://www.gnu.org/licenses/agpl-3.0.txt> GNU Affero General Public License v3
 *
 * This file is part of Journal Club Manager.
 *
 * Journal Club Manager is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Journal Club Manager is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Journal Club Manager.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * BOOTING PART
 */
include('../includes/App.php');
App::boot(true);

Router::route();
exit;

if (!empty($_GET)) {
    // Sanitize $_POST data
    foreach ($_POST as $key=>$value) {
        $_POST[$key] = htmlspecialchars($value);
    }

    // Merge and sanitize $_GET data
    foreach ($_GET as $key=>$value) {
        $_POST[$key] = htmlspecialchars($value);
    }
}

if (empty($_POST['controller'])) {
    Page::notFound();
} else {
    $controllerName = $_POST['controller'];
    $action = !empty($_POST['action']) ? $_POST['action'] : 'index';
    if (class_exists($controllerName, true)) {
        if (method_exists($controllerName, $action)) {
            $MethodChecker = new ReflectionMethod($controllerName, $action);
            if ($MethodChecker->isStatic()) {
                try {
                    echo json_encode($controllerName::$action($_POST));
                } catch (Exception $e) {
                    Logger::get_instance(APP_NAME)->error($e);
                    echo json_encode(array('status'=>false));
                }
            } else {
                if (method_exists($controllerName, 'getInstance')) {
                    $Controller = $controllerName::getInstance();
                } else {
                    $Controller = new $controllerName();
                }
                if (method_exists($controllerName, $action)) {
                    try {
                        echo json_encode(call_user_func_array(array($Controller,$action), array($_POST)));
                    } catch (Exception $e) {
                        Logger::get_instance(APP_NAME)->error($e);
                        echo json_encode(array('status'=>false));
                    }
                }
            }
        } else {
            Page::notFound();
        }

    } else {
        Page::notFound();
    }
    exit;
}