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
    if ($thisApp->update(array('options'=>$data))) {
        $result['stauts'] = true;
        $result['msg'] = "$name's settings successfully updated!";
    } else {
        $result['stauts'] = true;
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
    if ($thisApp->isInstalled()) {
        $result = $thisApp->update();
    } else {
        $result = False;
    }
    echo json_encode($result);
    exit;
}

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
    $cron = $CronJobs->instantiate($cronName);
    $result['msg'] = $cron->run();
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
    $Page = new AppPage($db,$page);
    $result = $Page->check_login();
    $result['pageName'] = $Page->filename;
    $result['title'] = $Page->meta_title;
    $result['keywords'] = $Page->meta_keywords;
    $result['description'] = $Page->meta_description;
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
	$booked = $Sessions->getsessions();// Get booked sessions
    if ($booked === false) {
        $booked = array();
    }
	$formatdate = array();
    $nb_pres = array();
    $type = array();
    $status = array();
	foreach($booked as $date) {
        // Count how many presentations there are for this day
        $session = new Session($db,$date);
        $nb_pres[] = $session->nbpres;
        $type[] = $session->type;
        $status[] = $session->status;
        // Format date
	    $fdate = explode("-",$date);
	    $day = $fdate[2];
	    $month = $fdate[1];
	    $year = $fdate[0];
	    $formatdate[] = "$day-$month-$year";
	}

	$result = array(
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

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Login/Sign up
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
// Logout
if (!empty($_POST['logout'])) {
    session_unset();
    session_destroy();
    echo json_encode(true);
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
    $user = new User($db);
    $username = htmlspecialchars($_POST['username']);
    $password = htmlspecialchars($_POST['password']);
    $result = $user->login($_POST);
    if ($result['status'] == true) {
        if ($user ->delete_user($username)) {
            $result['msg'] = "Your account has been deleted!";
            $result['status'] = false;
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
    // check entries
    $presid = htmlspecialchars($_POST['id_pres']);
    $user = new User($db,$_SESSION['username']);
    $date = $_POST['date'];

    if ($_POST['type'] != "guest") {
        $_POST['orator'] = $user->username;
    }
    // Create or update the presentation
    if ($presid !== "false") {
        $pub = new Presentation($db,$presid);
        $created = $pub->update($_POST);
    } else {
        $pub = new Presentation($db);
        $created = $pub->make($_POST);
    }

    if ($created !== false && $created !== 'exists') {
        // Add to sessions table
        $postsession = array("date"=>$date);
        $session = new Session($db);
        if ($session->make($postsession)) {
            $result['status'] = true;
            $result['msg'] = "Thank you for your submission!";
         } else {
            $pub->delete_pres($created);
            $result['status'] = false;
            $result['msg'] = "Sorry, we could not create/update the session";
         }
    } elseif ($created == "exists") {
        $result['status'] = false;
        $result['msg'] = "This presentation already exist in our database.";
    } else {
        $result['status'] = false;
    }

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
            $pub = new Presentation($db,$id_Presentation);
        }
        if (!isset($_SESSION['username'])) {
            $_SESSION['username'] = false;
        }
        $date = (!empty($_POST['date']) && $_POST['date'] !== 'false') ? $_POST['date']:false;
        $type = (!empty($_POST['type']) && $_POST['type'] !== 'false') ? $_POST['type']:false;
        $prestype = (!empty($_POST['prestype']) && $_POST['prestype'] !== 'false') ? $_POST['prestype']:false;

        $user = new User($db,$_SESSION['username']);
        $result = displayform($user,$pub,$type,$prestype,$date);
    } else {
        $result = "<p id='warning'>You must sign in to access this page!</p>";
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
    $form = displayform($user,$pub,'update');
    echo json_encode($form);
    exit;
}

if (!empty($_POST['getform'])) {
    $pub = new Presentation($db);
    $user = new User($db,$_SESSION['username']);
    $form = displayform($user,$pub,'submit');
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
    $body = $AppMail -> formatmail($content);
    $subject = "Contact from $usr_name";

    if ($AppMail->send_mail($sel_admin_mail,$subject,$body)) {
        $result['status'] = true;
        $result['msg'] = "Your message has been sent!";
    } else {
        $result['status'] = false;
    }
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
    $content['body'] = $_POST['spec_msg'];
    $content['subject'] = $_POST['spec_head'];
    $ids = explode(',',$_POST['emails']);
    $user = new User($db);

    // Get emails from the provided list of IDs
    $mailing_list = array();
    foreach ($ids as $id) {
        $data = $user->getById($id);
        $mailing_list[] = $data['email'];
    }

    $body = $AppMail -> formatmail($content['body']);
    $subject = $content['subject'];
    if ($AppMail->send_mail($mailing_list, $subject, $body, 'notification')) {
        $result['status'] = true;
        $result['msg'] = "Your message has been sent!";
    } else {
        $result['status'] = false;
    };
    echo json_encode($result);
    exit;
}

// Add emails to mailing list
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
            $content .= "
            <div class='added_email' id='{$info['id']}'><div class='added_email_name'>{$info['fullname']}</div><div class='added_email_delete' id='{$info['id']}'><img src='{$icon}'></div></div>
            ";
        }
        $result['ids'] = implode(',', $ids);
    } else {
        $info = $user->getById($id);
        $content = "
            <div class='added_email' id='{$info['id']}'><div class='added_email_name'>{$info['fullname']}</div><div class='added_email_delete' id='{$info['id']}'><img src='{$icon}'></div></div>
        ";
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
    $session = new Session($db,$sessionid);
    if (in_array($prop,array('time_to','time_from'))) {
        $time = explodecontent(',',$session->time);
        $value = ($prop == 'time_to') ? $time[0].','.$value:$value.','.$time[1];
        $prop = 'time';
    }
    $post = array($prop=>$value);
    if ($session->update($post)) {
        $result['status'] = true;
        $result['msg'] = "Session has been modified";
    } else {
        $result['status'] = false;
    }
    echo json_encode($result);
    exit;
}

// Modify speaker
if (!empty($_POST['modSpeaker'])) {
    $speaker = $_POST['modSpeaker'];
    $presid = $_POST['presid'];
    $speaker = new User($db,$speaker);

    $pres = new Presentation($db,$presid);
    if ($pres->update(array('orator'=>$speaker->username))) {
        $result['msg'] = "$speaker->fullname is the new speaker!";
        $result['status'] = true;
    } else {
        $result['status'] = false;
    }
    echo json_encode($result);
    exit;
}

// Check db integrity
if (!empty($_POST['db_check'])) {
    $Sessions->checkcorrespondence();
    echo json_encode(true);
    exit;
}

// Modify defaut session type
if (!empty($_POST['session_type_default'])) {
    if ($AppConfig->update($_POST)) {
        $result['status'] = true;
        $result['msg'] = "DONE";
    } else {
        $result['status'] = false;
    }
    echo json_encode($result);
    exit;
}