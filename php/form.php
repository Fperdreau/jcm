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
Scheduled Tasks
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
// Run cron job
if (!empty($_POST['run_cron'])) {
    $cronName = $_POST['cron'];
    $CronJobs = new Tasks();
    $result['msg'] = $CronJobs->execute($cronName);
    $result['status'] = true;
    echo json_encode($result);
    exit;
}

// Run cron job
if (!empty($_POST['stop_cron'])) {
    $cronName = $_POST['cron'];
    $Tasks = new Tasks();
    $result['status'] = $Tasks->unlock($cronName);
    echo json_encode($result);
    exit;
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Plugins
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
if (!empty($_POST['get_plugins'])) {
    $page = $_POST['page'];
    $Plugins = new Plugins();
    $plugins = $Plugins->loadAll($page);
    echo json_encode($plugins);
    exit;
}

if (!empty($_POST['mod_plugins'])) {
    $plugin = $_POST['Plugins'];
    $option = $_POST['option'];
    $value = $_POST['value'];
    $Plugins = new Plugins();
    $plugin = $Plugins->instantiate($plugin);
    if ($plugin->installed) {
        $plugin->getInfo();
        $plugin->options[$option] = $value;
        $result = $plugin->update(array('options'=>$plugins->options), array('name'=>$plugin));
    } else {
        $result = False;
    }

    echo json_encode($result);
    exit;
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Login/Sign up
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
// Send password change request if email exists in database
if (!empty($_POST['request_password_change'])) {
    $user = new Users();
    echo json_encode($user->request_password_change( htmlspecialchars($_POST['email'])));
    exit;
}

// Change user password after confirmation
if (!empty($_POST['password_change'])) {
    $user = new Users();
    echo json_encode($user->password_change(htmlspecialchars($_POST['username']), htmlspecialchars($_POST['password'])));
    exit;
}

// Process user modifications
if (!empty($_POST['user_modify'])) {
    $user = new Users();
    $result = $user->update($_POST, array('username'=>$_POST['username']));
    echo json_encode($result);
    exit;
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Media/Upload
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
if (!empty($_POST['add_upload'])) {
    $upload = new Media();
    $result = $upload->make($_FILES['file']);
    $result['name'] = false;
    if ($result['error'] == true) {
        $name = explode('.',$result['status']);
        $name = $name[0];
        $result['name'] = $name;
    }
    echo json_encode($result);
    exit;
}

//  delete files
if (!empty($_POST['del_upl'])) {
    $file_id = htmlspecialchars($_POST['id']);
    $up = new Media();
    $result = $up->delete(array('id'=>$file_id));
    $result['uplname'] = $file_id;
    echo json_encode($result);
    exit;
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Process submissions
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
// Get file list (download list)
if (!empty($_POST['getfiles'])) {
    $pubid = $_POST['pubid'];
    $pub = new Presentation($pubid);
    $filelist = explode(',',$pub->link);
    $result = "<div class='dlmenu'>";
    foreach ($filelist as $file) {
        $result .= "<div class='dl_info'><div class='upl_name' id='$file'>$file</div></div>";
    }
    $result .= "</div>";
    echo json_encode($result);
    exit;
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Archives
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
// Select years to display
if (!empty($_POST['select_year'])) {
	$Presentation = new Presentation();
    $selected_year = $_POST['select_year'];
	if ($selected_year == "" || $selected_year == "all") {
		$selected_year = null;
	}
    $publist = $Presentation -> getAllList($selected_year);
    echo json_encode($publist);
    exit;
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
User Management tools
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
// Sort user list
if (!empty($_POST['user_select'])) {
    $filter = htmlspecialchars($_POST['user_select']);
	if ($filter == "") {
		$filter = null;
	}
    $Users = new Users();
    $userlist = $Users->generateuserslist($filter);
    echo json_encode($userlist);
    exit;
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Application settings
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
// Update application settings
if (!empty($_POST['config_modify'])) {
    $controller_name = $_POST['controller'];
    /**
     * @var $controller BaseModel
     */
    $controller = new $controller_name();
    echo json_encode($controller->updateSettings($_POST));
    exit;
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Session Management tools
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/

if (!empty($_POST['add_session'])) {
    $Session = new Session();
    $result = $Session->make($_POST);
    echo json_encode($result);
    exit;
}

// Check if event is recurrent
if (!empty($_POST['is_recurrent'])) {
    $session = new Session();
    $result = $session->is_recurrent($_POST['is_recurrent']);
    echo json_encode($result);
    exit;
}

// Modify a session
if (!empty($_POST['modSession'])) {
    $session_id = htmlspecialchars($_POST['id']);
    $session = new Session();

    $operation = htmlspecialchars($_POST['operation']);

    $result = array('status'=>false, 'msg'=>null);

    if ($operation === 'present') {
        // Only update the current event
        $result['status'] = $session->update($_POST, array('id'=>$session_id));
    } elseif ($operation === 'future') {
        // Update all future occurences
        $result['status'] = $session->updateAllEvents($_POST, $session_id, 'future');
    } elseif ($operation === 'all') {
        // Update all (past/future) occurences
        $result['status'] = $session->updateAllEvents($_POST, $session_id, 'all');
    } else {
        throw new Exception("'{$operation}' is an unknown update operation");
    }

    $result['msg'] = $result['status'] ? "Session has been modified" : 'Something went wrong';

    echo json_encode($result);
    exit;
}

// Modify session type or cancel session
if (!empty($_POST['mod_session_type'])) {
    $sessionid = htmlspecialchars($_POST['session']);
    $prop = htmlspecialchars($_POST['prop']);
    $value = htmlspecialchars($_POST['value']);
    $session = new Session($sessionid);

    $post = array($prop=>$value);
    if ($prop === 'type' && $value === 'none') {
        /* If session type is set to none, we notify the assigned speakers of this session that their session
        has been canceled */
        $result['status'] = $session->cancelSession($session);
        if ($result['status']) $result['msg'] = "Session has been canceled";
    } else {
        $result['status'] = $session->set_session_type($session, $value);
        if ($result['status']) $result['msg'] = "Session's type has been set to {$value}";
    }
    echo json_encode($result);
    exit;
}

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
