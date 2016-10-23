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
    echo json_encode($AppConfig->status);
    exit;
}

if (!empty($_POST['isLogged'])) {
    $result = (isset($_SESSION['logok']) && $_SESSION['logok']);
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
    $App = ($type == 'plugin') ? new AppPlugins($db):new AppCron($db);
    $thisApp = $App->instantiate($name);
    if ($op == 'install') {
        if ($thisApp->install()) {
            $result['status'] = true;
            $result['msg'] = "$name has been installed!";
        } else {
            $result['status'] = false;
        }
    } elseif ($op == 'uninstall') {
        if ($thisApp->delete()) {
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
    $App = ($type === 'plugin') ? new AppPlugins($db):new AppCron($db);
    $thisApp = $App->instantiate($name);

    $result['status'] = $thisApp->update(array('status'=>$op));
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
    $App = ($op == 'plugin') ? new AppPlugins($db):new AppCron($db);
    $thisApp = $App->instantiate($name);
    $thisApp->get();
    $result = $thisApp->displayOpt();
    echo json_encode($result);
    exit;
}

// Modify settings
if (!empty($_POST['modOpt'])) {
    $name = htmlspecialchars($_POST['modOpt']);
    $op = htmlspecialchars($_POST['op']);
    $data = $_POST['data'];
    $App = ($op == 'plugin') ? new AppPlugins($db): new AppCron($db);
    $thisApp = $App->instantiate($name);
    $thisApp->get();
    foreach ($data as $key=>$settings) {
        $thisApp->options[$settings['name']]['value'] = $settings['value'];
    }
    if ($thisApp->update(array('options'=>$thisApp->options))) {
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
    $App = new AppCron($db);
    $thisApp = $App->instantiate($name);

    if ($thisApp->isInstalled()) {
        $thisApp->get();
        $thisApp->time = date('Y-m-d H:i:s', strtotime($_POST['date'] . ' ' . $_POST['time']));
        $frequency = array($_POST['months'], $_POST['days'], $_POST['hours'], $_POST['minutes']);
        $thisApp->frequency = implode(',', $frequency);
        if ($thisApp->update()) {
            $result = $thisApp->time;
        } else {
            $result = false;
        }
        
    } else {
        $result = False;
    }
    echo json_encode($result);
    exit;
}

// Modify status
if (!empty($_POST['modStatus'])) {
    $name = htmlspecialchars($_POST['modStatus']);
    $status = htmlspecialchars($_POST['status']);
    $op = htmlspecialchars($_POST['op']);
    $App = ($op == 'plugin') ? new AppPlugins($db): new AppCron($db);
    $thisApp = $App->instantiate($name);
    $thisApp->get();
    $thisApp->status = $status;
    $result = $thisApp->isInstalled() ? $thisApp->update() : False;
    echo json_encode($result);
    exit;
}

// Update settings
if (!empty($_POST['modSettings'])) {
    $name = htmlspecialchars($_POST['modSettings']);
    $option = htmlspecialchars($_POST['option']);
    $value = htmlspecialchars($_POST['value']);
    $op = htmlspecialchars($_POST['op']);

    $App = ($op == 'plugin') ? new AppPlugins($db): new AppCron($db);
    $thisApp = $App->instantiate($name);
    if ($thisApp->isInstalled()) {
        $thisApp->get();
        $thisApp->$option = $value;
        if ($op == 'plugin') {
            $result = $thisApp->update();
        } else {
            $thisApp->time = $App::parseTime($thisApp->dayNb, $thisApp->dayName, $thisApp->hour);
            if ($thisApp->update()) {
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

    $DigestMaker = new DigestMaker($db);
    $result['status'] = $DigestMaker->update($_POST, $name);
    echo json_encode($result);
    exit;
}

// Update ReminderMaker sections
if (!empty($_POST['modReminder'])) {
    $name = htmlspecialchars($_POST['name']);
    $display = htmlspecialchars($_POST['display']);
    $position = htmlspecialchars($_POST['position']);

    $reminderMaker = new ReminderMaker($db);
    $result['status'] = $reminderMaker->update($_POST, $name);
    echo json_encode($result);
    exit;
}

if (!empty($_POST['preview'])) {
    $operation = htmlspecialchars($_POST['preview']);
    if ($operation === 'digest') {
        $DigestMaker = new DigestMaker($db);
        $result = $DigestMaker->makeDigest($_SESSION['username']);
    } else {
        $DigestMaker = new ReminderMaker($db);
        $result = $DigestMaker->makeDigest($_SESSION['username']);
    }
    $AppMail = new AppMail($db);
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
    $CronJobs = new AppCron($db);
    $cron = $CronJobs->instantiate($cronName);
    if ($cron->isInstalled()) {
        $cron->get();
        $cron->$option = $value;
        $cron->time = AppCron::parseTime($cron->dayNb, $cron->dayName, $cron->hour);
        if ($cron->update()) {
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
    $CronJobs = new AppCron($db);
    $result['msg'] = $CronJobs->execute($cronName);
    $result['status'] = true;
    echo json_encode($result);
    exit;
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Plugins
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
if (!empty($_POST['get_plugins'])) {
    $page = $_POST['page'];
    $Plugins = new AppPlugins($db);
    $plugins = $Plugins->getPlugins($page);
    echo json_encode($plugins);
    exit;
}

if (!empty($_POST['mod_plugins'])) {
    $plugin = $_POST['plugin'];
    $option = $_POST['option'];
    $value = $_POST['value'];
    $Plugins = new AppPlugins($db);
    $plugin = $Plugins->instantiate($plugin);
    if ($plugin->installed) {
        $plugin->get();
        $plugin->options[$option] = $value;
        $result = $plugin->update();
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
    $AppStatus = $AppConfig->status;
    if ($AppStatus) {
        $Page = new AppPage($db, $page);
        $Plugins = new AppPlugins($db);

        $result = $Page->check_login();

        $result['plugins'] = $Plugins->getPlugins($page);
        $result['pageName'] = $Page->name;
        $result['title'] = $Page->meta_title;
        $result['keywords'] = $Page->meta_keywords;
        $result['description'] = $Page->meta_description;
    }
    $result['AppStatus'] = $AppStatus;
    echo json_encode($result);
    exit;
}

// Modify page settings
if (!empty($_POST['modPage'])) {
    $name = htmlspecialchars($_POST['name']);
    $Page = new AppPage($db,$name);
    if ($Page->update($_POST)) {
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

    // Get planned sessions
    $booked = $Sessions->getsessions();
    if ($booked === false) {
        $booked = array();
    }

    $formatdate = array();
    $nb_pres = array();
    $type = array();
    $status = array();
    foreach($booked as $date) {
        // Count how many presentations there are for this day
        $session = new Session($db, $date);
        $nb_pres[] = $session->nbpres;
        $type[] = $session->type;
        $status[] = $session->status;
        // Format date
        $fdate = explode("-", $date);
        $day = $fdate[2];
        $month = $fdate[1];
        $year = $fdate[0];
        $formatdate[] = "$day-$month-$year";
    }

    // Get user's availability and assignments
    if (isset($_SESSION['username'])) {
        $username = $_SESSION['username'];
        $Availability = new Availability($db);
        $availabilities = array();
        foreach ($Availability->get($username) as $info) {
            // Format date
            $fdate = explode("-", $info['date']);
            $day = $fdate[2];
            $month = $fdate[1];
            $year = $fdate[0];
            $availabilities[] = "$day-$month-$year";
        }

        // Get user's assignments
        $Presentation = new Presentation($db);
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
        "max_nb_session"=>$AppConfig->max_nb_session,
        "jc_day"=>$AppConfig->jc_day,
        "today"=>date('d-m-Y'),
        "booked"=>$formatdate,
        "nb"=>$nb_pres,
        "status"=>$status,
        "sessiontype"=>$type);
    echo json_encode($result);
    exit;
}

// Get booked dates for DatePicker Calendar
if (!empty($_POST['update_user_availability'])) {
    $username = $_SESSION['username'];
    $date = $_POST['date'];
    $Availability = new Availability($db);

    $result['status'] = $Availability->edit(array('date'=>$date, 'username'=>$username));
    if ($result['status']) {
        // Check whether user has a presentation planned on this day, if yes, then we delete it and notify the user that
        // this presentation has been canceled
        $sql = "SELECT * FROM ". $db->tablesname['Presentation'] . " WHERE date='{$date}' and orator='{$username}'";
        $data = $db->send_query($sql)->fetch_assoc();
        if (!empty($data)) {
            $speaker = new User($db, $username);
            $Assignment = new Assignment($db);
            $session = new Session($db, $date);
            $Presentation = new Presentation($db, $data['id_pres']);
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
    $_SESSION = array();
    unset($_SESSION);
    session_destroy();
    echo json_encode(AppConfig::$site_url);
    exit;
}

// Check login
if (!empty($_POST['login'])) {
    $username = htmlspecialchars($_POST['username']);
    $user = new User($db,$username);
    $result = $user->login($_POST);
    echo json_encode($result);
    exit;
}

// Registration
if (!empty($_POST['register'])) {
    $user = new User($db);
    $result = $user->make($_POST);
    echo json_encode($result);
    exit;
}

// Delete user
if (!empty($_POST['delete_user'])) {
    $username = htmlspecialchars($_POST['username']);
    $password = htmlspecialchars($_POST['password']);
    $user = new User($db, $username);
    $result = $user->login($_POST);
    if ($result['status'] == true) {
        if ($user ->delete_user($username)) {
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
    $email = htmlspecialchars($_POST['email']);
    $user = new User($db);

    if ($user->mail_exist($email)) {
        $username = $db ->getinfo($db->tablesname['User'],'username',array("email"),array("'$email'"));
        $user->get($username);
        $reset_url = $AppConfig->site_url."index.php?page=renew&hash=$user->hash&email=$user->email";
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
    $user = new User($db,$username);
    $post['password'] = $user->crypt_pwd($password);
    if ($user->update($post)) {
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
    $user = new User($db,$_POST['username']);
    $result = $user->update($_POST);
    echo json_encode($result);
    exit;
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Media/Upload
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
if (!empty($_POST['add_upload'])) {
    $upload = new Media($db);
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
    $pub = new Presentation($db,$pubid);
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
    $up = new Media($db, $fileid);
    $result = $up->delete();
    $result['uplname'] = $fileid;
    echo json_encode($result);
    exit;
}

//  delete presentation
if (!empty($_POST['del_pub'])) {
    $Presentation = new Presentation($db);
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
if (!empty($_POST['submit'])) {
    $Presentation = new Presentation($db);
    $result = $Presentation->edit($_POST);
    echo json_encode($result);
    exit;
}

// Suggest a presentation to the wishlist
if (isset($_POST['suggest'])) {
    $_POST['date'] = "";
    $_POST['type'] = "wishlist";
    $pres = new Presentation($db);
    $created = $pres->make($_POST);
    if ($created !== false && $created !== "exist") {
        $result['status'] = true;
        $result['msg'] = "Your presentation has been submitted.";
    } elseif ($created == "exist") {
        $result['status'] = false;
        $result['msg'] = "This presentation already exist in our database.";
    } else {
        $result['status'] = false;
    }
    echo json_encode($result);
    exit;
}

// Display submission form
if (!empty($_POST['getpubform'])) {
    if (isset($_SESSION['logok']) && $_SESSION['logok']) {
        $id_Presentation = $_POST['getpubform'];
        if ($id_Presentation == "false") {
            $pub = false;
        } else {
            $pub = new Presentation($db, $id_Presentation);
        }
        if (!isset($_SESSION['username'])) {
            $_SESSION['username'] = false;
        }
        $date = (!empty($_POST['date']) && $_POST['date'] !== 'false') ? $_POST['date']:false;
        $type = (!empty($_POST['type']) && $_POST['type'] !== 'false') ? $_POST['type']:false;
        $prestype = (!empty($_POST['prestype']) && $_POST['prestype'] !== 'false') ? $_POST['prestype']:false;

        $user = new User($db, $_SESSION['username']);
        $result = Presentation::displayform($user, $pub, $type, $prestype, $date);
    } else {
        $result = "<p class='sys_msg warning'>You must sign in to access this page!</p>";
    }
    echo json_encode($result);
    exit;
}

// Display presentation (modal dialog)
if (!empty($_POST['show_pub'])) {
    $id_Presentation = htmlspecialchars($_POST['show_pub']);
    if ($id_Presentation === "false") {
        $id_Presentation = false;
    }

    if (!isset($_SESSION['username'])) {
        $_SESSION['username'] = false;
    }

    $user = new User($db,$_SESSION['username']);
    $pub = new Presentation($db,$id_Presentation);
    $form = $pub->displaypub($user,true);
    echo json_encode($form);
    exit;
}

// Display modification form
if (!empty($_POST['mod_pub'])) {
    $id_Presentation = $_POST['mod_pub'];
    $user = new User($db,$_SESSION['username']);
    $pub = new Presentation($db,$id_Presentation);
    $form = Presentation::displayform($user,$pub,'update');
    echo json_encode($form);
    exit;
}

if (!empty($_POST['getform'])) {
    $pub = new Presentation($db);
    $user = new User($db,$_SESSION['username']);
    $form = Presentation::displayform($user,$pub,'submit');
    echo json_encode($form);
    exit;
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Archives
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
// Select years to display
if (!empty($_POST['select_year'])) {
	$Presentation = new Presentation($db);
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
Upload file
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
if (!empty($_POST['upload'])) {
	$pub = new Presentation($db);
	$filename = $pub->upload_file($_FILES['file']);
	echo json_encode($filename);
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

    $userlist = $Users->generateuserslist($filter);
    echo json_encode($userlist);
    exit;
}

// Change user status
if (!empty($_POST['modify_status'])) {
    $username = htmlspecialchars($_POST['username']);
    $newStatus = htmlspecialchars($_POST['option']);
    $user = new User($db,$username);
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
//    $content['body'] = $_POST['spec_msg']; // Message
//    $content['subject'] = $_POST['spec_head']; // Title
//    $content['mail_from'] = $_POST['mail_from']; // Sender email
//    $content['mail_from_name'] = $_POST['mail_from']; // Sender email
    $ids = explode(',',$_POST['emails']); // Recipients list
    $content['attachments'] = $_POST['attachments']; // Attached files
    $disclose = htmlspecialchars($_POST['undisclosed']) == 'yes'; // Do we show recipients list?
    $user = new User($db);
    $MailManager = new MailManager($db);

    // Get emails from the provided list of IDs
    $mailing_list = array();
    foreach ($ids as $id) {
        $data = $user->getById($id);
        $mailing_list[] = $data['email'];
    }
    
    $result = $MailManager->send($content, $mailing_list, $disclose);

    echo json_encode($result);
    exit;

}

// Add emails to recipients list
if (!empty($_POST['add_emails'])) {
    $id = htmlspecialchars($_POST['add_emails']);
    $icon = "images/close.png";
    $user = new User($db);
    if (strtolower($id) === 'all') {
        $users = $user->all();
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
    if ($AppConfig->update($_POST)) {
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
        $post = new Posts($db);
        $result = $post->make($_POST);
    } else {
        $id = htmlspecialchars($_POST['postid']);
        $post = new Posts($db,$id);
        $result = $post->update($_POST);
    }
    echo json_encode($result);
    exit;
}

// Show selected post
if (!empty($_POST['post_show'])) {
    $postid = $_POST['postid'];
    if ($postid == "false") $postid = false;
    $username = htmlspecialchars($_SESSION['username']);
    $user = new User($db,$username);
    $post = new Posts($db,$postid);
    $result = $post->showpost($user->fullname,$postid);
    echo json_encode($result);
    exit;
}

// Delete a post
if (!empty($_POST['post_del'])) {
    $postid = htmlspecialchars($_POST['postid']);
    $post = new Posts($db,$postid);
    if ($post->delete($postid)) {
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
    $divid = $class.'_'.$typename;
    if ($class == "session") {
        $AppConfig->session_type[$typename] = array();
        $types = array_keys($AppConfig->$varname);
    } else {
        $AppConfig->$varname .= ",$typename";
        $types = explode(',',$AppConfig->$varname);
    }
    if ($AppConfig->update()) {
        //Get session types
        $result = showtypelist($types,$class,$divid);
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
    if ($class == "session") {
        unset($AppConfig->session_type[$typename]);
        $types = array_keys($AppConfig->$varname);
    } else {
        $AppConfig->$varname = explode(',',$AppConfig->$varname);
        $types = array_values(array_diff($AppConfig->$varname,array($typename)));
        $AppConfig->$varname = implode(',',$types);
    }

    if ($AppConfig->update()) {
        //Get session types
        $result = showtypelist($types,$class,$divid);
    } else {
        $result = false;
    }
    echo json_encode($result);
    exit;
}

// Show sessions
if (!empty($_POST['show_session'])) {
    $date = htmlspecialchars($_POST['show_session']);
    $status = htmlspecialchars($_POST['status']);
    $result = $Sessions->managesessions($date,$status);
    echo json_encode($result);
    exit;
}

// Modify a session
if (!empty($_POST['modSession'])) {
    $sessionid = htmlspecialchars($_POST['session']);
    $prop = htmlspecialchars($_POST['prop']);
    $value = htmlspecialchars($_POST['value']);
    $session = new Session($db, $sessionid);
    if (in_array($prop, array('time_to','time_from'))) {
        $time = explodecontent(',', $session->time);
        $value = ($prop == 'time_to') ? $time[0] . ',' . $value : $value . ',' . $time[1];
        $prop = 'time';
    }

    $post = array($prop=>$value);
    if ($result['status'] = $session->update($post)) {
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
    $session = new Session($db, $sessionid);

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
    $previous = new User($db, $_POST['previous']);
    $speaker = new User($db, $speaker);
    $Presentation = new Presentation($db);
    $Assignment = new Assignment($db);
    if (empty($presid)) {
        $presid = $Presentation->make(array(
            'title'=>'TBA',
            'date'=>$date,
            'orator'=>$speaker->username,
            'username'=>$speaker->username,
            'type'=>'paper'));
    }
    $pres = $Presentation->get($presid);
    $session = new Session($db, $date);
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
            if ($pres->update(array('orator'=>$speaker->username))) {
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
    if ($AppConfig->update($_POST)) {
        $result['status'] = true;
        $result['msg'] = "DONE";
    } else {
        $result['status'] = false;
        $result['msg'] = "Something went wrong";
    }
    echo json_encode($result);
    exit;
}