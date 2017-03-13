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

// Includes required files (classes)
require('../includes/boot.php');

if (!empty($_GET)) {
    $_POST = $_GET;
}

if (!empty($_POST['get_app_status'])) {
    echo json_encode(AppConfig::getInstance()->status);
    exit;
}

if (!empty($_POST['isLogged'])) {
    $result = User::is_logged();
    echo json_encode($result);
    exit;
}

if (!empty($_POST['load_content'])) {
    $url = htmlspecialchars($_POST['load_content']);
}

if (!empty($_POST['delete_item'])) {
    $params = explode('/', htmlspecialchars($_POST['params']));
    $class_name = $params[0];
    $action = $params[1];
    $id = $params[2];
    $obj = new $class_name();
    $result['status'] = $obj->$action($id);
    echo json_encode($result);
    exit;
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Common to Plugins/Scheduled tasks
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
// Install/uninstall cron jobs
if (!empty($_POST['installDep'])) {
    $name = $_POST['installDep'];
    $op = $_POST['op'];
    $type = $_POST['type'];
    $App = ($type == 'plugin') ? new AppPlugins() : new AppCron();
    $thisApp = $App->instantiate($name);
    if ($op == 'install') {
        if ($thisApp->install()) {
            $result['status'] = true;
            $result['msg'] = "$name has been installed!";
        } else {
            $result['status'] = false;
        }
    } elseif ($op == 'uninstall') {
        if ($thisApp->delete(array('name'=>$name))) {
            $result['status'] = true;
            $result['msg'] = "$name has been deleted!";
        } else {
            $result['status'] = false;
        }
    } else {
        $result['msg'] = $thisApp->run();
        $result['status'] = true;
    }
    echo json_encode($result);
    exit;
}

// Install/uninstall cron jobs
if (!empty($_POST['activateDep'])) {
    $name = $_POST['activateDep'];
    $op = $_POST['op'];
    $type = $_POST['type'];
    $App = ($type === 'plugin') ? new AppPlugins() : new AppCron();
    $thisApp = $App->instantiate($name);

    $result['status'] = $thisApp->update(array('status'=>$op), array('name'=>$name));
    if ($result['status']) {
        $result['msg'] = ($op === 'On') ? "{$name} has been activated!":"{$name} has been deactivated";
    }
    echo json_encode($result);
    exit;
}

// Get settings
if (!empty($_POST['getOpt'])) {
    $name = htmlspecialchars($_POST['getOpt']);
    $op = htmlspecialchars($_POST['op']);
    $App = ($op == 'plugin') ? new AppPlugins() : new AppCron();
    $thisApp = $App->instantiate($name);
    $thisApp->getInfo();
    $result = $thisApp->displayOpt();
    echo json_encode($result);
    exit;
}

// Modify settings
if (!empty($_POST['modOpt'])) {
    $name = htmlspecialchars($_POST['modOpt']);
    $op = htmlspecialchars($_POST['op']);
    $data = $_POST['data'];
    $App = ($op == 'plugin') ? new AppPlugins() : new AppCron();
    $thisApp = $App->instantiate($name);
    $thisApp->getInfo();
    foreach ($data as $key=>$settings) {
        $thisApp->options[$settings['name']]['value'] = $settings['value'];
    }
    if ($thisApp->update(array('options'=>$thisApp->options), array('name'=>$name))) {
        $result['status'] = true;
        $result['msg'] = "$name's settings successfully updated!";
    } else {
        $result['status'] = true;
    }
    echo json_encode($result);
    exit;
}

if (!empty($_POST['modCron'])) {
    $name = htmlspecialchars($_POST['modCron']);
    $App = new AppCron();
    $thisApp = $App->instantiate($name);

    if ($thisApp->isInstalled()) {
        $thisApp->getInfo();
        $thisApp->time = date('Y-m-d H:i:s', strtotime($_POST['date'] . ' ' . $_POST['time']));
        $frequency = array($_POST['months'], $_POST['days'], $_POST['hours'], $_POST['minutes']);
        $thisApp->frequency = implode(',', $frequency);
        if ($thisApp->update(array('frequency'=>$thisApp->frequency, 'time'=>$thisApp->time), array('name'=>$name))) {
            $result['status'] = true;
            $result['msg'] = $thisApp->time;
        } else {
            $result['status'] = false;
        }
    } else {
        $result['status'] = false;
    }
    echo json_encode($result);
    exit;
}

// Modify status
if (!empty($_POST['modStatus'])) {
    $name = htmlspecialchars($_POST['modStatus']);
    $status = htmlspecialchars($_POST['status']);
    $op = htmlspecialchars($_POST['op']);
    $App = ($op == 'plugin') ? new AppPlugins(): new AppCron();
    $thisApp = $App->instantiate($name);
    $thisApp->getInfo();
    $thisApp->status = $status;
    $result = $thisApp->isInstalled() ? $thisApp->update(array('status'=>$status), array('name'=>$name)) : False;
    echo json_encode($result);
    exit;
}

// Update settings
if (!empty($_POST['modSettings'])) {
    $name = htmlspecialchars($_POST['modSettings']);
    $option = htmlspecialchars($_POST['option']);
    $value = htmlspecialchars($_POST['value']);
    $op = htmlspecialchars($_POST['op']);

    $App = ($op == 'plugin') ? new AppPlugins(): new AppCron();
    $thisApp = $App->instantiate($name);
    if ($thisApp->isInstalled()) {
        $thisApp->getInfo();
        $thisApp->$option = $value;
        if ($op == 'plugin') {
            $result = $thisApp->update(array($option=>$value), array('name'=>$name));
        } else {
            $thisApp->time = $App::parseTime($thisApp->time, explode(',', $thisApp->frequency));
            if ($thisApp->update(array('time'=>$thisApp->time), array('name'=>$name))) {
                $result = $thisApp->time;
            } else {
                $result = false;
            }
        }
    } else {
        $result = False;
    }
    echo json_encode($result);
    exit;
}

// Get scheduled task's logs
if (!empty($_POST['showLog'])) {
    $name = htmlspecialchars($_POST['showLog']);
    $result = AppCron::showLog($name);
    if (is_null($result)) $result = 'Nothing to display';
    echo json_encode($result);
    exit;
}

// Delete scheduled task's logs
if (!empty($_POST['deleteLog'])) {
    $name = htmlspecialchars($_POST['deleteLog']);
    $result['status'] = AppCron::deleteLog($name);
    echo json_encode($result);
    exit;
}

// Get scheduled task's logs
if (!empty($_POST['show_log'])) {
    $name = (isset($_POST['name'])) ? htmlspecialchars($_POST['name']) : htmlspecialchars($_POST['show_log']);
    $search = (isset($_POST['search'])) ? htmlspecialchars($_POST['search']) : null;
    $result = AppLogger::show($name, $search);
    if (is_null($result)) $result = 'Nothing to display';
    echo json_encode($result);
    exit;
}

// Delete scheduled task's logs
if (!empty($_POST['delete_log'])) {
    $name = htmlspecialchars($_POST['delete_log']);
    $file = htmlspecialchars($_POST['file']);
    $result['status'] = AppLogger::delete($file);
    $result['content'] = AppLogger::manager($name);
    echo json_encode($result);
    exit;
}

// Get log manager
if (!empty($_POST['show_log_manager'])) {
    $name = htmlspecialchars($_POST['class']);
    $search = htmlspecialchars($_POST['search']);
    $result = AppLogger::manager($name, $search);
    echo json_encode($result);
    exit;
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
DigestMaker/ReminderMaker
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
// Update DigestMaker sections
if (!empty($_POST['modDigest'])) {
    $name = htmlspecialchars($_POST['name']);
    $display = htmlspecialchars($_POST['display']);
    $position = htmlspecialchars($_POST['position']);

    $DigestMaker = new DigestMaker();
    $result['status'] = $DigestMaker->update($_POST, array('name'=>$name));
    echo json_encode($result);
    exit;
}

// Update ReminderMaker sections
if (!empty($_POST['modReminder'])) {
    $name = htmlspecialchars($_POST['name']);
    $display = htmlspecialchars($_POST['display']);
    $position = htmlspecialchars($_POST['position']);

    $reminderMaker = new ReminderMaker();
    $result['status'] = $reminderMaker->update($_POST, array('name'=>$name));
    echo json_encode($result);
    exit;
}

if (!empty($_POST['preview'])) {
    $operation = htmlspecialchars($_POST['preview']);
    if ($operation === 'digest') {
        $DigestMaker = new DigestMaker();
        $result = $DigestMaker->makeDigest($_SESSION['username']);
    } else {
        $DigestMaker = new ReminderMaker();
        $result = $DigestMaker->makeDigest($_SESSION['username']);
    }
    $AppMail = new AppMail();
    $result['body'] = $AppMail->formatmail($result['body']);
    echo json_encode($result);
    exit;
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Scheduled Tasks
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
// Modify cron job
if (!empty($_POST['mod_cron'])) {
    $cronName = $_POST['cron'];
    $option = $_POST['option'];
    $value = $_POST['value'];
    $CronJobs = new AppCron();
    $cron = $CronJobs->instantiate($cronName);
    if ($cron->isInstalled()) {
        $cron->getInfo();
        $cron->$option = $value;
        $cron->time = AppCron::parseTime($cron->time, explode(',', $cron->frequency));
        if ($cron->update(array($option=>$value, 'time'=>$cron->time), array('name'=>$cronName))) {
            $result = $cron->time;
        } else {
            $result = false;
        }
    } else {
        $result = False;
    }

    echo json_encode($result);
    exit;
}

// Run cron job
if (!empty($_POST['run_cron'])) {
    $cronName = $_POST['cron'];
    $CronJobs = new AppCron();
    $result['msg'] = $CronJobs->execute($cronName);
    $result['status'] = true;
    echo json_encode($result);
    exit;
}

// Run cron job
if (!empty($_POST['stop_cron'])) {
    $cronName = $_POST['cron'];
    $CronJobs = new AppCron($cronName);
    $CronJobs->unlock();
    $result['status'] = $CronJobs->unlock();
    echo json_encode($result);
    exit;
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Plugins
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
if (!empty($_POST['get_plugins'])) {
    $page = $_POST['page'];
    $Plugins = new AppPlugins();
    $plugins = $Plugins->getPlugins($page);
    echo json_encode($plugins);
    exit;
}

if (!empty($_POST['mod_plugins'])) {
    $plugin = $_POST['plugin'];
    $option = $_POST['option'];
    $value = $_POST['value'];
    $Plugins = new AppPlugins();
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
Pages Management
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
// Get Pages
if (!empty($_POST['getPage'])) {
    $page = htmlspecialchars($_POST['getPage']);
    if (strpos($page, "#")) {
        // Remove hashtags
        $page = substr($page, 0, strpos($page, "#"));
    }
    $split = explode('/', $page);

    // Get page id
    $page_id = end($split);

    $page_name = implode('\\\\', $split);
    $Page = new AppPage($page_name);
    $Plugins = new AppPlugins();

    $content = array();
    $content['plugins'] = $Plugins->getPlugins($page_id);
    $content['pageName'] = $page_id;
    $content['parent'] = $split[0];
    $content['title'] = (!empty($Page->meta_title)) ? $Page->meta_title : $page_id;
    $content['keywords'] = $Page->meta_keywords;
    $content['description'] = $Page->meta_description;
    $content['content'] = null;
    $content['AppStatus'] = AppConfig::getInstance()->status;
    $content['icon'] = (is_file(PATH_TO_IMG . $content['pageName'] . '_bk_40x40.png')) ? $content['pageName']: $content['parent'];
    $status = $Page->check_login();
    if ($content['AppStatus'] == 'On' || $split[0] === 'admin' || ($status['status'] && $status['msg'] == 'admin')) {
        if ($status['status'] == false) {
            $result = $status['msg'];
        } else {
            if (!AppPage::exist($page)) {
                $result = AppPage::notFound();
            } else {
                $result['content'] = AppPage::render($page);
                $result['header'] = AppPage::header($page_id, $content['icon']);
            }
        }
    } else {
        $result = AppPage::maintenance();
    }

    // Update content
    foreach ($result as $key=>$value) {
        $content[$key] = $value;
    }

    echo json_encode($content);
    exit;
}

// Modify page settings
if (!empty($_POST['modPage'])) {
    $name = htmlspecialchars($_POST['name']);
    $Page = new AppPage($name);
    if ($Page->update($_POST, array('name'=>$name))) {
        $result['status'] = true;
        $result['msg'] = "The modification has been made!";
    } else {
        $result['status'] = false;
    }
    echo json_encode($result);
    exit;
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Datepicker (calendar)
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
// Get booked dates for DatePicker Calendar
if (!empty($_POST['get_calendar_param'])) {
    $Sessions = new Session();
    $force_select = htmlspecialchars($_POST['get_calendar_param']) === 'edit';

    $formatdate = array();
    $nb_pres = array();
    $type = array();
    $status = array();
    $slots = array();
    $all = array();
    foreach($Sessions->all() as $session_id=>$session_data) {
        // Count how many presentations there are for this day
        foreach ($session_data as $key=>$data) {
            $nb_pres[] = count($data);
            $type[] = $data['type'];
            $status[] = $data['status'];
            $slots[] = $data['slots'];
            $formatdate[] = date('d-m-Y', strtotime($data['date']));
        }
    }

    // Get user's availability and assignments
    if (User::is_logged()) {
        $username = $_SESSION['username'];
        $Availability = new Availability();
        $availabilities = array();
        foreach ($Availability->get(array('username'=>$username)) as $info) {
            // Format date
            $fdate = explode("-", $info['date']);
            $day = $fdate[2];
            $month = $fdate[1];
            $year = $fdate[0];
            $availabilities[] = "$day-$month-$year";
        }

        // Get user's assignments
        $Presentation = new Presentation();
        $assignments = array();
        foreach ($Presentation->getList($username) as $row=>$info) {
            // Format date
            $fdate = explode("-", $info['date']);
            $day = $fdate[2];
            $month = $fdate[1];
            $year = $fdate[0];
            $assignments[] = "$day-$month-$year";
        }
        
    } else {
        $assignments = array();
        $availabilities = array();
    }
    
    $result = array(
        "Assignments"=>$assignments,
        "Availability"=>$availabilities,
        "max_nb_session"=>AppConfig::getInstance()->max_nb_session,
        "jc_day"=>AppConfig::getInstance()->jc_day,
        "today"=>date('d-m-Y'),
        "booked"=>$formatdate,
        "nb"=>$nb_pres,
        "status"=>$status,
        "slots"=>$slots,
        "session_type"=>$type,
        "force_select"=>$force_select
    );
    echo json_encode($result);
    exit;
}

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
            $speaker = new User($username);
            $Assignment = new Assignment();
            $session = new Session($date);
            $Presentation = new Presentation($data['id_pres']);
            $info['type'] = $session->type;
            $info['date'] = $session->date;
            $info['presid'] = $data['id_pres'];
            $result['status'] = $Presentation->delete_pres($data['id_pres']);
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
// Logout
if (!empty($_POST['logout'])) {
    if (SessionInstance::destroy()) {
        echo json_encode(AppConfig::$site_url);
    }
    exit;
}

// Check login status
if (!empty($_POST['check_login'])) {
    if (User::is_logged()) {
        $result = array(
            "start"=>$_SESSION['login_start'],
            "expire"=>$_SESSION['login_expire'],
            "warning"=>$_SESSION['login_warning']
        );
        echo json_encode($result);
    } else {
        echo json_encode(false);
    }
}

// Extend session duration
if (!empty($_POST['extend_login'])) {
    if (User::is_logged()) {
        $_SESSION['login_expire'] = time() + SessionInstance::timeout;
        $result = array(
            "start"=>$_SESSION['login_start'],
            "expire"=>$_SESSION['login_expire'],
            "warning"=>$_SESSION['login_warning']
        );
        echo json_encode($result);
    } else {
        echo json_encode(false);
    }
}

// Check login
if (!empty($_POST['login'])) {
    $username = htmlspecialchars($_POST['username']);
    $user = new User($username);
    $result = $user->login($_POST);
    echo json_encode($result);
    exit;
}

// Registration
if (!empty($_POST['register'])) {
    $user = new User();
    $result = $user->make($_POST);
    echo json_encode($result);
    exit;
}

// Delete user
if (!empty($_POST['delete_user'])) {
    $username = htmlspecialchars($_POST['username']);
    $password = htmlspecialchars($_POST['password']);
    $user = new User($username);
    $result = $user->login($_POST);
    if ($result['status'] == true) {
        if ($user->delete_user($username)) {
            $result['msg'] = "Your account has been deleted!";
            $result['status'] = true;
            $_SESSION['logok'] = false;
        } else {
            $result['status'] = false;
        }
    }
    echo json_encode($result);
    exit;
}

// Send password change request if email exists in database
if (!empty($_POST['change_pw'])) {
    $AppMail = new AppMail();
    $email = htmlspecialchars($_POST['email']);
    $user = new User();

    if ($user->is_exist(array('email'=>$email))) {
        $username = AppDb::getInstance()->select(AppDb::getInstance()->tablesname['User'], array('username'),
            array("email"=>$email));
        $user->getUser($username);
        $reset_url = URL_TO_APP . "index.php?page=renew&hash=$user->hash&email=$user->email";
        $subject = "Change password";
        $content = "
            Hello $user->firstname $user->lastname,<br>
            <p>You requested us to change your password.</p>
            <p>To reset your password, click on this link:
            <br><a href='$reset_url'>$reset_url</a></p>
            <br>
            <p>If you did not request this change, please ignore this email.</p>
            ";

        $body = $AppMail->formatmail($content);
        if ($AppMail->send_mail($email,$subject,$body)) {
            $result['msg'] = "An email has been sent to your address with further information";
            $result['status'] = true;
        } else {
            $result['msg'] = "Oops, we couldn't send you the verification email";
            $result['status'] = false;
        }
    } else {
        $result['msg'] = "This email does not exist in our database";
        $result['status'] = false;
    }
    echo json_encode($result);
    exit;
}

// Change user password after confirmation
if (!empty($_POST['conf_changepw'])) {
    $username = htmlspecialchars($_POST['username']);
    $password = htmlspecialchars($_POST['password']);
    $user = new User($username);
    if ($user->update(array('password'=>$user->crypt_pwd($password)), array('username'=>$username))) {
        $result['msg'] = "Your password has been changed!";
        $result['status'] = true;
    } else {
        $result['status'] = false;
    }
    echo json_encode($result);
    exit;
}

// Process user modifications
if (!empty($_POST['user_modify'])) {
    $user = new User();
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
    $uplname = htmlspecialchars($_POST['uplname']);
    $fileid = explode(".",$uplname);
    $fileid = $fileid[0];
    $up = new Media();
    $result = $up->delete(array('fileid'=>$fileid));
    $result['uplname'] = $fileid;
    echo json_encode($result);
    exit;
}

//  delete presentation
if (!empty($_POST['del_pub'])) {
    $Presentation = new Presentation();
    $id_Presentation = htmlspecialchars($_POST['del_pub']);
    if ($Presentation->delete_pres($id_Presentation)) {
        $result['msg'] = "The presentation has been deleted!";
        $result['status'] = true;
    } else {
        $result['status'] = false;
    }
    echo json_encode($result);
    exit;
}

// Submit a new presentation
if (!empty($_POST['edit'])) {
    $Presentation = new Presentation();
    $result = $Presentation->edit($_POST);
    echo json_encode($result);
    exit;
}

// Add a suggestion
if (isset($_POST['suggest'])) {
    $_POST['type'] = "wishlist";
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

// Display submission form
if (!empty($_POST['getpubform'])) {
    $destination = isset($_POST['destination']) ? htmlspecialchars($_POST['destination']) : 'body';
    $Presentation = new Presentation();
    if ($destination === "body") {
        echo json_encode($Presentation::format_section($Presentation->editor($_POST)));
    } else {
        echo json_encode($Presentation::format_modal($Presentation->editor($_POST)));
    }
    exit;
}

if (!empty($_POST['getFormContent'])) {
    $type = htmlspecialchars($_POST['getFormContent']);
    $Presentation = new Presentation();
    echo json_encode($Presentation::get_form_content($Presentation, $type));
}

// Show wishlist selector
if (!empty($_POST['show_wish_list'])) {
    $Suggestion = new Suggestion();
    $result['content'] = $Suggestion->generate_selectwishlist('.submission_container');
    $result['title'] = "Select a wish";
    $result['description'] = Suggestion::description("wishpick");

    echo json_encode(Presentation::format_section($result));
    exit;
}

// Display presentation (modal dialog)
if (!empty($_POST['show_pub'])) {
    $id_Presentation = htmlspecialchars($_POST['show_pub']);
    if ($id_Presentation === "false") {
        $id_Presentation = false;
    }

    $pub = new Presentation();
    $data = $pub->getInfo($id_Presentation);
    $user = User::is_logged() ? new User($_SESSION['username']) : null;
    $show = !is_null($user) && (in_array($user->status, array('organizer', 'admin'))
            || $data['orator'] === $user->username);
    $form = Presentation::details($data, $show);
    echo json_encode($form);
    exit;
}

// Display modification form
if (!empty($_POST['mod_pub'])) {
    $id_Presentation = $_POST['mod_pub'];
    $user = new User($_SESSION['username']);
    $pub = new Presentation($id_Presentation);
    echo json_encode(Presentation::form($user, $pub, 'update'));
    exit;
}

if (!empty($_POST['getform'])) {
    $pub = new Presentation();
    $user = new User($_SESSION['username']);
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
    $publist = $Presentation -> getpublicationlist($selected_year);
    echo json_encode($publist);
    exit;
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Contact form
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
if (!empty($_POST['contact_send'])) {
    $AppMail = new AppMail();
    $sel_admin_mail = htmlspecialchars($_POST['admin_mail']);
    $usr_msg = htmlspecialchars($_POST["message"]);
    $usr_mail = htmlspecialchars($_POST["email"]);
    $usr_name = htmlspecialchars($_POST["name"]);
    $content = "Message sent by $usr_name ($usr_mail):<br><p>$usr_msg</p>";
    $body = $AppMail->formatmail($content, null, false);
    $subject = "Contact from $usr_name";

    $settings['mail_from'] = $usr_mail;
    $settings['mail_from_name'] = $usr_mail;

    if ($AppMail->send_mail($sel_admin_mail, $subject, $body, null, true, $settings)) {
        $result['status'] = true;
        $result['msg'] = "Your message has been sent!";
    } else {
        $result['status'] = false;
    }
    echo json_encode($result);
    exit;
}

// Test email settings
if (!empty($_POST['test_email_settings'])) {
    $AppMail = new AppMail();
    $settings = array();
    foreach ($_POST as $setting=>$value) {
        $settings[$setting] = htmlspecialchars($value);
    }
    $to = (isset($_POST['test_email'])) ? htmlspecialchars($_POST['test_email']) : null;
    $result = $AppMail->send_test_email($settings, $to);
    echo json_encode($result);
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

// Change user status
if (!empty($_POST['modify_status'])) {
    $Users = new Users();
    $username = htmlspecialchars($_POST['username']);
    $newStatus = htmlspecialchars($_POST['option']);
    $user = new User($username);
    $result = $user->setStatus($newStatus);
    $result['content'] = $Users->generateuserslist();
    echo json_encode($result);
    exit;
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Mailing
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
// Send mail if asked
if (!empty($_POST['mailing_send'])) {
    $content = array();
    foreach ($_POST as $key => $value) {
        if (!is_array($value)) {
            $value = htmlspecialchars_decode($value);
        }
        $content[$key] = $value;
    }

    $ids = explode(',',$_POST['emails']); // Recipients list
    $content['attachments'] = $_POST['attachments']; // Attached files
    $disclose = htmlspecialchars($_POST['undisclosed']) == 'yes'; // Do we show recipients list?
    $make_news = htmlspecialchars($_POST['make_news']) == 'yes'; // Do we show recipients list?
    $user = new User();
    $MailManager = new MailManager();

    // Get emails from the provided list of IDs
    $mailing_list = array();
    foreach ($ids as $id) {
        $data = $user->getById($id);
        $mailing_list[] = $data['email'];
    }
    
    $result = $MailManager->send($content, $mailing_list, $disclose);

    if ($make_news) {
        $news = new Posts();
        $news->make(array(
            'title'=>$content['subject'],
            'content'=>$content['body'],
            'username'=>$_SESSION['username'],
            'homepage'=>1
        ));
    }

    echo json_encode($result);
    exit;
}

// Add emails to recipients list
if (!empty($_POST['add_emails'])) {
    $id = htmlspecialchars($_POST['add_emails']);
    $icon = "images/close.png";
    $user = new User();
    if (strtolower($id) === 'all') {
        $users = $user->all_but_admin();
        $content = "";
        $ids = array();
        foreach ($users as $key=>$info) {
            $ids[] = $info['id'];
            $content .= MailManager::showRecipient($info);
        }
        $result['ids'] = implode(',', $ids);
    } else {
        $info = $user->getById($id);
        $content = MailManager::showRecipient($info);
        $result['ids'] = $id;
    }
    $result['content'] = $content;
    $result['status'] = true;
    echo json_encode($result);
    exit;
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Application settings
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
// Update application settings
if (!empty($_POST['config_modify'])) {
    if (AppConfig::getInstance()->update_all($_POST)) {
        $result['msg'] = "Modifications have been made!";
        $result['status'] = true;
    } else {
        $result['status'] = false;
    }
    echo json_encode($result);
    exit;
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
POSTS
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
// Add a new post
if (!empty($_POST['post_add'])) {
    if ($_POST['post_add'] === 'post_add') {
        $post = new Posts();
        $result = $post->make($_POST);
    } else {
        $id = htmlspecialchars($_POST['postid']);
        $post = new Posts($id);
        $result = $post->update($_POST, array('postid'=>$id));
    }
    echo json_encode($result);
    exit;
}

// Show selected post
if (!empty($_POST['post_show'])) {
    $postid = $_POST['postid'];
    if ($postid == "false") $postid = false;
    $username = htmlspecialchars($_SESSION['username']);
    $user = new User($username);
    $post = new Posts($postid);
    $result = $post->form($user->username, $postid);
    echo json_encode($result);
    exit;
}

// Delete a post
if (!empty($_POST['post_del'])) {
    $postid = htmlspecialchars($_POST['postid']);
    $post = new Posts($postid);
    if ($post->delete(array('postid'=>$postid))) {
        $result['status'] = true;
        $result['msg'] = "The post has been deleted";
    } else {
        $result['status'] = true;
    }
    echo json_encode($result);
    exit;
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Session Management tools
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
// Add a session/presentation type
if (!empty($_POST['add_type'])) {
    $class = $_POST['add_type'];
    $typename = $_POST['typename'];
    $varname = $class."_type";
    $div_id = $class.'_'.$typename;
    if ($class == "session") {
        AppConfig::getInstance()->session_type[] = $typename;
        $types = AppConfig::getInstance()->session_type;
    } else {
        AppConfig::getInstance()->pres_type[] = $typename;
        $types = AppConfig::getInstance()->pres_type;
    }
    if (AppConfig::getInstance()->update_all()) {
        //Get session types
        $result = showtypelist($types, $class, $div_id);
    } else {
        $result = false;
    }
    echo json_encode($result);
    exit;
}

// Delete a session/presentation type
if (!empty($_POST['del_type'])) {
    $class = $_POST['del_type'];
    $typename = $_POST['typename'];
    $varname = $class."_type";
    $divid = $class.'_'.$typename;
    $result['status'] = true;
    if ($class == "session") {
        if (in_array($typename, AppConfig::$session_type_default)) {
            $result['status'] = false;
            $result['msg'] = "Default types cannot be deleted";
        } else {
            if(($key = array_search($typename, AppConfig::getInstance()->session_type)) !== false) {
                unset(AppConfig::getInstance()->session_type[$key]);
            }
        }

    } else {
        if (in_array($typename, AppConfig::$pres_type_default)) {
            $result['status'] = false;
            $result['msg'] = "Default types cannot be deleted";
        } else {
            if(($key = array_search($typename, AppConfig::getInstance()->pres_type)) !== false) {
                unset(AppConfig::getInstance()->pres_type[$key]);
            }
        }
    }
    $types = AppConfig::getInstance()->$varname;

    if ($result['status'] && AppConfig::getInstance()->update_all()) {
        //Get session types
        $result['msg'] = showtypelist($types, $class, $divid);
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

// Modify a session
if (!empty($_POST['modSession'])) {
    $sessionid = htmlspecialchars($_POST['session']);
    $prop = htmlspecialchars($_POST['prop']);
    $value = htmlspecialchars($_POST['value']);
    $session = new Session();
    $post = array($prop=>$value);
    if ($result['status'] = $session->update($post, array('id'=>$sessionid))) {
        if ($result['status']) $result['msg'] = "Session has been modified";
    } else {
        $result['status'] = false;
    }
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
    $date = $_POST['date'];
    $previous = new User($_POST['previous']);
    $speaker = new User($speaker);
    $Presentation = new Presentation();
    $Assignment = new Assignment();
    if (empty($presid)) {
        $presid = $Presentation->make(array(
            'title'=>'TBA',
            'date'=>$date,
            'orator'=>$speaker->username,
            'username'=>$speaker->username,
            'type'=>'paper'));
    }
    $session = new Session($date);
    $info['type'] = $session->type;
    $info['date'] = $session->date;
    $info['presid'] = $presid;
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
            if ($Presentation->update(array('orator'=>$speaker->username), array('id_pres'=>$presid))) {
                $result['msg'] = "$speaker->fullname is the new speaker!";
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
if (!empty($_POST['session_type_default'])) {
    $result['status'] = AppConfig::getInstance()->update(array(
        'default_type'=>htmlspecialchars($_POST['session_type_default'])), array('variable'=>'default_type'));
    echo json_encode($result);
    exit;
}