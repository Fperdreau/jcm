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
Datepicker (calendar)
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/

// Get booked dates for DatePicker Calendar
if (!empty($_POST['update_user_availability'])) {
    $username = $_SESSION['username'];
    $date = $_POST['date'];
    $Availability = new Availability();
    $Presentation = new Presentation();

    $result['status'] = $Availability->edit(array('date'=>$date, 'username'=>$username));
    if ($result['status']) {
        // Check whether user has a presentation planned on this day, if yes, then we delete it and notify the user that
        // this presentation has been canceled
        $data = $Presentation->get(array('date'=>$date, 'orator'=>$username));
        if (!empty($data)) {
            $speaker = new Users($username);
            $Assignment = new Assignment();
            $session = new Session($date);
            $Presentation = new Presentation($data['id']);
            $info['type'] = $session->type;
            $info['date'] = $session->date;
            $info['presid'] = $data['id'];
            $result['status'] = $Presentation->delete_pres($data['id']);
            if ($result['status']) {
                $result['status'] = $Assignment->updateAssignment($speaker, $info, false, true);
            }
        }
    }
    echo json_encode($result);
    exit;
}


/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Login/Sign up
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
// Registration
if (!empty($_POST['register'])) {
    $user = new Users();
    $result = $user->make($_POST);
    echo json_encode($result);
    exit;
}

// Delete user
if (!empty($_POST['delete_user'])) {
    $username = htmlspecialchars($_POST['username']);
    $Auth = new Auth();
    $login_ok = $Auth->login(false);
    if ($login_ok['status'] == true) {
        $result = $user->delete_user($username, $_SESSION['username']);
        if ($result['status']) {
            $_SESSION['logok'] = false;
        }
    } else {
        $result['msg'] = 'Wrong username/password combination';
        $result['status'] = false;
    }
    echo json_encode($result);
    exit;
}

if (!empty($_POST['get_delete_account_form'])) {
    echo json_encode(Users::delete_account_form_modal());
}

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

//  delete files
if (!empty($_POST['del_upl'])) {
    $file_id = htmlspecialchars($_POST['id']);
    $up = new Media();
    $result = $up->delete(array('id'=>$file_id));
    $result['uplname'] = $file_id;
    echo json_encode($result);
    exit;
}

//  delete presentation
if (!empty($_POST['del_pub'])) {
    $controller = htmlspecialchars($_POST['controller']);

    /**
     * @var Suggestion|Presentation $Controller
     */
    $Controller = new $controller();
    $id_Presentation = htmlspecialchars($_POST['del_pub']);
    if ($Controller->delete_pres($id_Presentation)) {
        $result['msg'] = "Item has been deleted!";
        $result['status'] = true;
    } else {
        $result['status'] = false;
    }
    echo json_encode($result);
    exit;
}

// Submit a new presentation
if (!empty($_POST['process_submission'])) {
    $controllerName = $_POST['controller'];
    $action = $_POST['operation'];
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

// Add a suggestion
if (isset($_POST['suggest'])) {
    $pres = new Suggestion();
    $created = $pres->add_suggestion($_POST);
    if ($created !== false && $created !== "exist") {
        $result['status'] = true;
        $result['msg'] = "Thank you for your suggestion.";
    } elseif ($created == "exist") {
        $result['status'] = false;
        $result['msg'] = "This suggestion already exist in our database.";
    } else {
        $result['status'] = false;
    }
    echo json_encode($result);
    exit;
}

if (!empty($_POST['getFormContent'])) {
    $type = htmlspecialchars($_POST['getFormContent']);
    $controller_name = htmlspecialchars($_POST['controller']);

    /**
     * @var $Controller Presentation|Suggestion
     */
    $Controller = new $controller_name();
    if ($_POST['id'] !== 'false') {
        $Controller->getInfo($_POST['id']);
    }
    echo json_encode(Presentation::get_form_content($Controller, $type));
    exit;
}

// Display modification form
if (!empty($_POST['mod_pub'])) {
    $id_Presentation = $_POST['mod_pub'];
    $user = new Users($_SESSION['username']);
    $pub = new Presentation($id_Presentation);
    echo json_encode(Presentation::form($user, $pub, 'update'));
    exit;
}

if (!empty($_POST['getform'])) {
    $pub = new Presentation();
    $user = new Users($_SESSION['username']);
    echo json_encode(Presentation::form($user, $pub, 'submit'));
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
// Add a session/presentation type
if (!empty($_POST['add_type'])) {
    $class = $_POST['add_type'];
    $typename = $_POST['typename'];
    $var_name = "types";
    $div_id = $class . '_' . $typename;
    $controller_name = ucfirst($class);

    /**
     * @var $controller Presentation|Session
     */
    $controller = new $controller_name();
    $types = $controller->getSettings($var_name);
    $types[] = $typename;

    $result = $controller->updateSettings(array($var_name=>$types));
    if ($result['status']) {
        //Get session types
        $session_types = $controller::renderTypes($controller->getSettings("types"), $controller->getSettings("default_type"));
        $result = $session_types['types'];
    } else {
        $result = false;
    }
    echo json_encode($result);
    exit;
}

// Delete a session/presentation type
if (!empty($_POST['del_type'])) {
    $class = ucfirst($_POST['del_type']);
    $typename = $_POST['typename'];
    $var_name = "types";
    $defaults = 'defaults';
    $div_id = strtolower($class) . '_' . str_replace(' ', '_', strtolower($typename));
    $result['status'] = true;
    $controller_name = ucfirst($class);

    /**
     * @var $controller Presentation|Session
     */
    $controller = new $controller_name();
    $types = $controller->getSettings($var_name);

    if (in_array($typename, $controller->getSettings($defaults))) {
        $result['status'] = false;
        $result['msg'] = "Default types cannot be deleted";
    } else {
        if(($key = array_search($typename, $types)) !== false) {
            unset($types[$key]);
        }
        $new_types = array_values(array_diff($types, array($typename)));
        $updated = $controller->updateSettings(array($var_name=>$new_types));
        if ($result['status'] && $updated['status']) {
            //Get session types
            $session_types = $controller::renderTypes($new_types, $controller->getSettings("default_type"));
            $result = $session_types['types'];
        }
    }

    echo json_encode($result);
    exit;
}

// Show sessions
if (!empty($_POST['show_session'])) {
    $date = htmlspecialchars($_POST['show_session']);
    $status = htmlspecialchars($_POST['status']);
    $view = htmlspecialchars($_POST['view']);
    $Session = new Session();
    if ($status == 'admin') {
        $result = $Session->getSessionEditor($date);
    } else {
        $result = $Session->getSessionViewer($date);
    }
    echo json_encode($result);
    exit;
}

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

// Delete a session
if (!empty($_POST['delSession'])) {
    $session_id = htmlspecialchars($_POST['id']);
    $session = new Session();

    $operation = htmlspecialchars($_POST['operation']);

    $result = array('status'=>false, 'msg'=>null);

    if ($operation === 'present') {
        // Only update the current event
        $result['status'] = $session->delete(array('id'=>$session_id));
    } elseif ($operation === 'future') {
        // Update all future occurrences
        $result['status'] = $session->deleteAllEvents($session_id, 'future');
    } elseif ($operation === 'all') {
        // Update all (past/future) occurrences
        $result['status'] = $session->deleteAllEvents($session_id, 'all');
    } else {
        throw new Exception("'{$operation}' is an unknown update operation");
    }

    $result['msg'] = $result['status'] ? "Session has been deleted" : 'Something went wrong';

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

if (!empty($_POST['loadContent'])) {
    $controllerName = htmlspecialchars($_POST['controller']);
    $action = htmlspecialchars($_POST['action']);
    $params = isset($_POST['params']) ? explode(',', htmlspecialchars($_POST['params'])) : array();
    if (class_exists($controllerName, true)) {
        $Controller = new $controllerName();
        if (method_exists($controllerName, $action)) {
            echo json_encode(call_user_func_array(array($Controller,$action), $params));
        }
    }
    exit;
}

if (!empty($_POST['set_modal'])) {
    $Modal = new Modal();
    $result = $Modal->set_modal($_POST);
    echo json_encode($result);
    exit;
}