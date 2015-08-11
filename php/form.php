<?php
/*
Copyright © 2014, Florian Perdreau
This file is part of Journal Club Manager.

Journal Club Manager is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Journal Club Manager is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with Journal Club Manager.  If not, see <http://www.gnu.org/licenses/>.
*/

// Includes required files (classes)
require('../includes/boot.php');

if (!empty($_POST['get_app_status'])) {
    echo json_encode($AppConfig->status);
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Scheduled Tasks
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
if (!empty($_POST['mod_cron'])) {
    $cronName = $_POST['cron'];
    $option = $_POST['option'];
    $value = $_POST['value'];
    $CronJobs = new AppCron($db);
    $cron = $CronJobs->instantiateCron($cronName);
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

// Install/uninstall cron jobs
if (!empty($_POST['install_cron'])) {
    $cronName = $_POST['cron'];
    $op = $_POST['op'];
    $CronJobs = new AppCron($db);
    $cron = $CronJobs->instantiateCron($cronName);
    if ($op == 'install') {
        $result = $cron->install();
    } elseif ($op == 'uninstall') {
        $result = $cron->delete();
    } else {
        $result = $cron->run();
    }
    echo json_encode($result);
    exit;
}

// Run cron job
if (!empty($_POST['run_cron'])) {
    $cronName = $_POST['cron'];
    $CronJobs = new AppCron($db);
    $cron = $CronJobs->instantiateCron($cronName);
    $result = $cron->run();
    echo json_encode($result);
    exit;
}

// Modify cron status (on/off)
if (!empty($_POST['cron_status'])) {
    $cron = $_POST['cron'];
    $status = $_POST['status'];
    $CronJobs = new AppCron($db);
    $cron = $CronJobs->instantiateCron($cron);
    $cron->status = $status;
    if ($cron->isInstalled()) {
        $result = $cron->update();
    } else {
        $result = False;
    }
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
    $plugin = $Plugins->instantiatePlugin($plugin);
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

if (!empty($_POST['install_plugin'])) {
    $plugin = $_POST['plugin'];
    $op = $_POST['op'];
    $Plugins = new AppPlugins($db);
    $plugin = $Plugins->instantiatePlugin($plugin);
    if ($op == 'install') {
        $result = $plugin->install();
    } else {
        $result = $plugin->delete();
    }
    echo json_encode($result);
    exit;
}

if (!empty($_POST['plugin_status'])) {
    $plugin = $_POST['plugin'];
    $status = $_POST['status'];
    $Plugins = new AppPlugins($db);
    $plugin = $Plugins->instantiatePlugin($plugin);
    $plugin->status = $status;
    if ($plugin->installed) {
        $result = $plugin->update();
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
    $user = new User($db);

    $username = htmlspecialchars($_POST['username']);
    $password = htmlspecialchars($_POST['password']);
    if ($user->get($username) == true) {
        if ($user->active == 1) {
            if ($user -> check_pwd($password) == true) {
                $_SESSION['logok'] = true;
                $_SESSION['username'] = $user -> username;
                $_SESSION['firstname'] = $user -> firstname;
                $_SESSION['lastname'] = $user -> lastname;
                $_SESSION['status'] = $user -> status;
                $result['status'] = true;
            } else {
                $attempt = $user->checkattempt();
                $result['msg'] = $attempt == false ? "blocked_account":"wrong_password";
                $result['status'] = $attempt;
                $_SESSION['logok'] = false;
            }
        } else {
            $result['status'] = false;
            $result['msg'] = "not_activated";
        }
    } else {
        $result['status'] = false;
        $result['msg'] = "wrong_username";
    }
    echo json_encode($result);
    exit;
}

// Registration
if (!empty($_POST['register'])) {
    $user = new User($db);
    $result = "none";
    foreach($_POST as $key => $value) {
        if(!empty($value)) {
            $$key = htmlspecialchars($value);
            $user->$key = $$key;
            switch ($key) {
                case 'password':
                    if (empty($_POST['conf_password']) or ($_POST['conf_password'] != $$key)) {
                        $result = "mismatch";
                    }
                    break;
                case 'email':
                    if (!filter_var($$key, FILTER_VALIDATE_EMAIL)) {
                        $result = "wrong_email";
                    }
                    break;
            }
        }
    }
    $result = $user -> make($user->username,$user->password,$user->firstname,$user->lastname,$user->position,$user->email);
    echo json_encode($result);
    exit;
}

// Delete user
if (!empty($_POST['delete_user'])) {
    $user = new User($db);
    $username = htmlspecialchars($_POST['username']);
    $password = htmlspecialchars($_POST['password']);
    if ($user -> get($username) == true) {
        if ($user->active == 1) {
            if ($user -> check_pwd($password) == true) {
                $user ->delete_user($username);
                $result = "deleted";
                $_SESSION['logok'] = false;
            } else {
                $result = "wrong_password";
            }
        } else {
        	$result = "not_activated";
        }

    } else {
        $result = "wrong_username";
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
        $reset_url = $AppConfig->site_url."index.php?page=renew_pwd&hash=$user->hash&email=$user->email";
        $subject = "Change password";
        $content = "
            Hello $user->firstname $user->lastname,<br>
            <p>You requested us to change your password.</p>
            <p>To reset your password, click on this link:
            <br><a href='$reset_url'>$reset_url</a></p>
            <br>
            <p>If you did not request this change, please ignore this email.</p>
            The Journal Club Team
            ";

        $body = $AppMail->formatmail($content);
        if ($AppMail->send_mail($email,$subject,$body)) {
            $result = "sent";
        } else {
            $result = "not_sent";
        }
    } else {
        $result = "wrong_email";
    }
    echo json_encode($result);
    exit;
}

// Change user password after confirmation
if (!empty($_POST['conf_changepw'])) {
    $user = new User($db);
    $username = htmlspecialchars($_POST['username']);
    $password = htmlspecialchars($_POST['password']);
    $conf_password = htmlspecialchars($_POST['conf_password']);
    if ($password != $conf_password) {
        $result = "mismatch";
    } else {
        $user->get($username);
        $post['password'] = $user->crypt_pwd($password);
        $user->update($post);
        $result = "changed";
    }
    echo json_encode($result);
    exit;
}

// Process user modifications
if (!empty($_POST['user_modify'])) {
    $user = new User($db,$_POST['username']);
    if ($user -> update($_POST)) {
        $result = "<p id='success'>The modification has been made!</p>";
    } else {
        $result = "<p id='warning'>Something went wrong!</p>";
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

//  delete publication
if (!empty($_POST['del_pub'])) {
    $Presentation = new Presentation($db);
    $id_Presentation = htmlspecialchars($_POST['del_pub']);
    if ($Presentation->delete_pres($id_Presentation)) {
        $result = 'deleted';
    } else {
        $result = 'failed';
    }
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
    echo json_encode($result);
    exit;
}

// Submit a new presentation
if (!empty($_POST['submit'])) {
    // check entries
    $user = new User($db,$_SESSION['username']);
    $date = $_POST['date'];
    if ($Sessions->isbooked($date) == "Booked out" && $_POST['type'] !== 'minute') {
        $result = "<p id='warning'>This date is booked out</p>";
    } else {
        if ($_POST['type'] != "guest") {
            $_POST['orator'] = $user->username;
        }

        $pub = new Presentation($db);
        $created = $pub -> make($_POST);
        if ($created !== false && $created !== 'exists') {
            // Add to sessions table
            $postsession = array(
                "date"=>$date,
                "presid"=>$created,
                "speakers"=>$_POST['orator']
                );
            $session = new Session($db);
            if ($session->make($postsession)) {
                $result['status'] = true;
                $result['msg'] = "<p id='success'>Your presentation has been submitted.</p>";
             } else {
                $pub->delete_pres($created);
                $result['status'] = false;
                $result['msg'] = "<p id='warning'>We could not create/update the session</p>";
             }
        } elseif ($created == "exists") {
            $result['status'] = false;
            $result['msg'] = "<p id='warning'>This presentation already exist in our database.</p>";
        } else {
            $result['status'] = false;
            $result['msg'] = "<p id='warning'>Oops, something has gone wrong.</p>";
        }
    }
    echo json_encode($result);
    exit;
}

// Update/modify a presentation
if (!empty($_POST['update'])) {
    $pub = new Presentation($db,$_POST['id_pres']);
    // check entries
    $user = new User($db,$_SESSION['username']);
    $date = $_POST['date'];
    if ($Sessions->isbooked($date) === "Booked out" && $_POST['type'] !== 'minute') {
        $result = "<p id='warning'>This date is booked out</p>";
    } else {
        if ($_POST['type'] != "guest") {
            $_POST['orator'] = $user->username;
        }

        $created = $pub->update($_POST);
        if ($created !== false) {
            // Add to sessions table
            $postsession = array(
                "date"=>$date,
                "presid"=>$created,
                "speakers"=>$_POST['orator']
                );
            $session = new Session($db);
            if ($session->make($postsession)) {
                $result['status'] = true;
                $result['msg'] = "<p id='success'>Your presentation has been submitted.</p>";
             } else {
                $pub->delete_pres($created);
                $result['status'] = false;
                $result['msg'] = "<p id='warning'>We could not create/update the session</p>";
             }
        } else {
            $result['status'] = false;
            $result['msg'] = "<p id='warning'>Oops, something has gone wrong.</p>";
        }
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
        $result['msg'] = "<p id='success'>Your presentation has been submitted.</p>";
    } elseif ($created == "exist") {
        $result['status'] = false;
        $result['msg'] = "<p id='warning'>This presentation already exist in our database.</p>";
    } else {
        $result['status'] = false;
        $result['msg'] = "<p id='warning'>Oops, something went wrong</p>";
    }
    echo json_encode($result);
    exit;
}

// Display submission form
if (!empty($_POST['getpubform'])) {

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
    echo json_encode($result);
    exit;
}

// Display presentation (modal dialog)
if (!empty($_POST['show_pub'])) {
    $id_Presentation = $_POST['show_pub'];
    if ($id_Presentation === "false") {
        $id_Presentation = false;
    }
    if (!isset($_SESSION['username'])) {
        $_SESSION['username'] = false;
    }

    $user = new User($db,$_SESSION['username']);
    $pub = new Presentation($db,$id_Presentation);
    $form = displaypub($user,$pub);
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
    $usr_mail = htmlspecialchars($_POST["mail"]);
    $usr_name = htmlspecialchars($_POST["name"]);
    $content = "Message sent by $usr_name ($usr_mail):<br><p>$usr_msg</p>";
    $body = $AppMail -> formatmail($content);
    $subject = "Contact from $usr_name";

    if ($AppMail->send_mail($sel_admin_mail,$subject,$body)) {
        $result = "sent";
    } else {
        $result = "not_sent";
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
Admin tools
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

// Export db if asked
if (!empty($_POST['export'])) {
    $table_name = htmlspecialchars($_POST['tablename']);
    $result = exportdbtoxls($table_name);
    echo json_encode($result);
    exit;
}

// Send mail if asked
if (!empty($_POST['mailing_send'])) {
    $content['body'] = $_POST['spec_msg'];
    $content['subject'] = $_POST['spec_head'];
    $body = $AppMail -> formatmail($content['body']);
    $subject = $content['subject'];
    if ($AppMail_result = $AppMail->send_to_mailinglist($subject,$body)) {
        $result = "sent";
    } else {
        $result = "not_sent";
    };
    echo json_encode($result);
    exit;
}

// Change user status
if (!empty($_POST['modify_status'])) {
    $username = htmlspecialchars($_POST['username']);
    $newstatus = htmlspecialchars($_POST['option']);
    $selected_user = new User($db,$username);

    if ($newstatus == 'delete') {
        $selected_user -> delete_user();
        $result['status'] = "Account successfully deleted";
    } elseif ($newstatus == "activate") {
        $result['status'] = $selected_user -> activation(1);
    } elseif ($newstatus == "desactivate") {
        $result['status'] = $selected_user -> activation(0);
    } else {
        $selected_user -> change_user_status($newstatus);
        $result['status'] = "User status is now $newstatus!";
    }
    $result['content'] = $Users->generateuserslist();
    echo json_encode($result);
    exit;
}

// Udpate application settings
if (!empty($_POST['config_modify'])) {
    $AppConfig->update($_POST);
    $result = "<p id='success'>Modifications have been made!</p>";
    echo json_encode($result);
    exit;
}

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
    $result = $post->delete($postid);
    echo json_encode($result);
    exit;
}

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
    $nbsession = $_POST['show_session'];
    $result = $Sessions->managesessions($nbsession);
    echo json_encode($result);
    exit;
}

// Modify a session type
if (!empty($_POST['mod_session_type'])) {
    $sessionid = $_POST['session'];
    $type = $_POST['type'];
    $session = new Session($db);
    $sessionpost = array(
        'date'=>$sessionid,
        'type'=>$type);
    $result = $session->make($sessionpost);
    echo json_encode($result);
    exit;
}

// Modify a session time
if (!empty($_POST['mod_session_time'])) {
    $sessionid = $_POST['session'];
    $time = $_POST['time'];
    $session = new Session($db);
    $sessionpost = array(
        'date'=>$sessionid,
        'time'=>$time);
    $result = $session->make($sessionpost);
    echo json_encode($result);
    exit;
}

// Modify chairman
if (!empty($_POST['mod_chair'])) {
    $sessionid = $_POST['session'];
    $chair = $_POST['chair'];
    $presid = $_POST['presid'];
    $chairID = $_POST['chairID'];

    $Chairs = new Chairs($db);
    $Chairs->get('id',$chairID);
    $Chairs->chair = $chair;
    $result = $Chairs->update();

    $Presentation = new Presentation($db,$presid);
    $Presentation->chair = $chair;
    $result = $Presentation->update();

    echo json_encode($result);
    exit;
}

// Check db integrity
if (!empty($_POST['db_check'])) {
    $Sessions->checkcorrespondence();
    echo json_encode(true);
    exit;
}

