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

// Sanitize GET and POST data
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

if (!empty($_POST['router'])) {
    $controllerName = $_POST['controller'];
    $action = $_POST['action'];
    if (class_exists($controllerName, true)) {
        $Controller = new $controllerName();
        if (method_exists($controllerName, $action)) {
            try {
                echo json_encode(call_user_func_array(array($Controller,$action), array($_POST)));
            } catch (Exception $e) {
                Logger::getInstance(APP_NAME)->error($e);
                echo json_encode(array('status'=>false));
            }
        }
    }
    exit;
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Session Management tools
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/

// Modify speaker
if (!empty($_POST['modSpeaker'])) {
    $speaker = $_POST['modSpeaker'];
    $presid = $_POST['presid'];
    $session_id = $_POST['session_id'];
    $previous = new Users($_POST['previous']);
    $speaker = new Users($speaker);
    $Presentation = new Presentation();
    $Assignment = new Assignment();
    $session = new Session($session_id);

    // Make presentation if new
    if (empty($presid)) {
        $presid = $Presentation->make(array(
            'title'=>'TBA',
            'date'=>$session->date,
            'session_id'=>$session_id,
            'orator'=>$speaker->username,
            'username'=>$speaker->username,
            'type'=>'paper'));
    }

    $info = array(
        'type'=>$session->type,
        'date'=>$session->date,
        'presid'=>$presid
    );

    if (!is_null($previous->username)) {
        // Only send notification to real users
        $result['status'] = $Assignment->updateAssignment($previous, $info, false, true);
    } else {
        $result['status'] = true;
    }
    if ($result['status']) {
        if (!is_null($speaker->username)) {
            // Only send notification to real users
            $result['status'] = $Assignment->updateAssignment($speaker, $info, true, true);
        } else {
            $result['status'] = true;
        }
        if ($result['status']) {
            if ($Presentation->update(array('username'=>$speaker->username), array('id'=>$presid))) {
                $result['msg'] = "{$speaker->fullname} is the new speaker!";
                $result['status'] = true;
            } else {
                $result['status'] = false;
            }
        }
    }
    echo json_encode($result);
    exit;
}

// Modify default session type
if (!empty($_POST['type_default'])) {
    $controller_name = htmlspecialchars(ucfirst($_POST['class_name']));

    /**
     * @var $controller Presentation|Session
     */
    $controller = new $controller_name();
    $result = $controller->updateSettings(
        array('default_type'=>htmlspecialchars($_POST['type_default']))
    );
    echo json_encode($result);
    exit;
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Votes
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
if (!empty($_POST['process_vote'])) {
    $controller_name = $_POST['controller'];
    $Operation = $_POST['operation'];
    $Controller = new $controller_name();
    echo json_encode($Controller->$Operation($_POST));
    exit;
}
