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

@session_start();
date_default_timezone_set('Europe/Paris');

// Includes required files (classes)
include_once($_SESSION['path_to_includes'].'includes.php');

// Create a database object
$db_set = new DB_set();

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Datepicker (calendar)
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
// Get booked dates for DatePicker Calendar
if (!empty($_POST['get_calendar_param'])) {
	$booked = Sessions::getsessions();// Get booked sessions
    if ($booked === false) {
        $booked = array();
    }
	$formatdate = array();
    $nb_pres = array();
    $type = array();
	foreach($booked as $date) {
        // Count how many presentations there are for this day
        $session = new Session($date);
        $nb_pres[] = $session->nbpres;
        $type[] = $session->type;

        // Format date
	    $fdate = explode("-",$date);
	    $day = $fdate[2];
	    $month = $fdate[1];
	    $year = $fdate[0];
	    $formatdate[] = "$day-$month-$year";
	}

    // Get application settings
	$config = new site_config('get');

	$result = array(
        "max_nb_session"=>$config->max_nb_session,
        "jc_day"=>$config->jc_day,
        "today"=>date('d-m-Y'),
        "booked"=>$formatdate,
        "nb"=>$nb_pres,
        "sessiontype"=>$type);
	echo json_encode($result);
    exit;
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Login/Sign up
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
// Check login
if (!empty($_POST['login'])) {
    $user = new users();

    $username = htmlspecialchars($_POST['username']);
    $password = htmlspecialchars($_POST['password']);
    $result = "nothing";
    if ($user -> get($username) == true) {
        if ($user->active == 1) {
            if ($user -> check_pwd($password) == true) {
                $_SESSION['logok'] = true;
                $_SESSION['username'] = $user -> username;
                $_SESSION['firstname'] = $user -> firstname;
                $_SESSION['lastname'] = $user -> lastname;
                $_SESSION['status'] = $user -> status;
                $result = "logok";
            } else {
                $_SESSION['logok'] = false;
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

// Registration
if (!empty($_POST['register'])) {
    $user = new users();
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
    $user = new users();
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
    $user = new users();
    $mail = new myMail();

    if ($user->mail_exist($email)) {
        $username = $db_set ->getinfo($users_table,'username',array("email"),array("'$email'"));
        $user->get($username);
        $reset_url = $mail->site_url."index.php?page=renew_pwd&hash=$user->hash&email=$user->email";
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

        $body = $mail -> formatmail($content);
        if ($mail->send_mail($email,$subject,$body)) {
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
    $user = new users();
    $username = htmlspecialchars($_POST['username']);
    $password = htmlspecialchars($_POST['password']);
    $conf_password = htmlspecialchars($_POST['conf_password']);
    if ($password != $conf_password) {
        $result = "mismatch";
    } else {
        $user->get($username);
        $crypt_pwd = $user->crypt_pwd($password);
        $db_set->updatecontent($users_table,"password","'$crypt_pwd'",array("username"),array("'$username'"));
        $result = "changed";
    }
    echo json_encode($result);
    exit;
}

// Process user modifications
if (!empty($_POST['user_modify'])) {
    $user = new users($_POST['username']);
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

//  delete publication
if (!empty($_POST['del_pub'])) {
    $Presentation = new Presentation();
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
    $pub = new Presentation();
    $result = $pub->delete_file($uplname);
    echo json_encode($result);
    exit;
}

// Submit a new presentation
if (!empty($_POST['submit'])) {
    // check entries
    $user = new users($_SESSION['username']);
    $date = $_POST['date'];
    if (Sessions::isbooked($date) == "Booked out") {
        $result = "<p id='warning'>This date is booked out</p>";
    } else {
        if ($_POST['type'] != "guest") {
            $_POST['orator'] = $user->fullname;
        }

        $pub = new Presentation();
        $created = $pub -> make($_POST);
        if ($created !== false && $created !== 'exists') {
            // Pseudo randomly choose a chairman for this presentation
            $chairs = Sessions::getchair($date,$_POST['orator']);
            // Add to sessions table
            $postsession = array(
                "date"=>$date,
                "presid"=>$created,
                "chairs"=>$chairs,
                "speakers"=>$_POST['orator']
                );
            $session = new Session();
            if ($session->make($postsession)) {
                $result['status'] = true;
                $result['msg'] = "<p id='success'>Your presentation has been submitted.</p>";
             } else {
                Presentation::delete_pres($created);
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
}

// Update/modify a presentation
if (!empty($_POST['update'])) {
    $pub = new Presentation($_POST['id_pres']);
    // check entries
    $user = new users($_SESSION['username']);
    $date = $_POST['date'];
    if (Sessions::isbooked($date) === "Booked out") {
        $result = "<p id='warning'>This date is booked out</p>";
    } else {
        if ($_POST['type'] != "guest") {
            $_POST['orator'] = $user->fullname;
        }

        $created = $pub->update($_POST);
        if ($created !== false) {
            // Pseudo randomly choose a chairman for this presentation
            $chairs = Sessions::getchair($date,$_POST['orator']);
            // Add to sessions table
            $postsession = array(
                "date"=>$date,
                "presid"=>$created,
                "chairs"=>$chairs,
                "speakers"=>$_POST['orator']
                );
            $session = new Session();
            if ($session->make($postsession)) {
                $result['status'] = true;
                $result['msg'] = "<p id='success'>Your presentation has been submitted.</p>";
             } else {
                Presentation::delete_pres($created);
                $result['status'] = false;
                $result['msg'] = "<p id='warning'>We could not create/update the session</p>";
             }
        } else {
            $result['status'] = false;
            $result['msg'] = "<p id='warning'>Oops, something has gone wrong.</p>";
        }
    }
    echo json_encode($result);
}

// Suggest a presentation to the wishlist
if (isset($_POST['suggest'])) {
    $_POST['date'] = "";
    $_POST['type'] = "wishlist";
    $pres = new Presentation();
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

// Display presentation (modal dialog)
if (!empty($_POST['getpubform'])) {
    $id_Presentation = $_POST['getpubform'];
    $type = $_POST['type'];
    if ($id_Presentation == "false") {
        $pub = false;
    } else {
        $pub = new Presentation($id_Presentation);
    }
    if (!isset($_SESSION['username'])) {
        $_SESSION['username'] = false;
    }
    $user = new users($_SESSION['username']);
    $result = displayform($user,$pub,$type);
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
    $user = new users($_SESSION['username']);
    $pub = new Presentation($id_Presentation);
    $form = displaypub($user,$pub);
    echo json_encode($form);
}

// Display modification form
if (!empty($_POST['mod_pub'])) {
    $id_Presentation = $_POST['mod_pub'];
    $user = new users($_SESSION['username']);
    $pub = new Presentation($id_Presentation);
    $form = displayform($user,$pub,'update');
    echo json_encode($form);
}

if (!empty($_POST['getform'])) {
    $pub = new Presentation();
    $user = new users($_SESSION['username']);
    $form = displayform($user,$pub,'submit');
    echo json_encode($form);
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
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Contact form
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
if (!empty($_POST['contact_send'])) {
    $mail = new myMail();
    $sel_admin_mail = htmlspecialchars($_POST['admin_mail']);
    $usr_msg = htmlspecialchars($_POST["message"]);
    $usr_mail = htmlspecialchars($_POST["mail"]);
    $usr_name = htmlspecialchars($_POST["name"]);
    $content = "Message sent by $usr_name ($usr_mail):<br><p>$usr_msg</p>";
    $body = $mail -> formatmail($content);
    $subject = "Contact from $usr_name";

    if ($mail->send_mail($sel_admin_mail,$subject,$body)) {
        $result = "sent";
    } else {
        $result = "not_sent";
    }
    echo json_encode($result);
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Upload file
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
if (!empty($_POST['upload'])) {
	print_r($_FILES['file']);
	$pub = new Presentation();
	$filename = $pub->upload_file($_FILES['file']);
	echo json_encode($filename);
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Admin tools
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
// Sort user list
if (!empty($_POST['user_select'])) {
	$config = new site_config();
    $filter = htmlspecialchars($_POST['user_select']);
	if ($filter == "") {
		$filter = null;
	}

    $userlist = site_config::generateuserslist($filter);
    echo json_encode($userlist);
    exit;
}

// Export db if asked
if (!empty($_POST['export'])) {
    $table_name = htmlspecialchars($_POST['tablename']);
    $result = exportdbtoxls($table_name);
    echo json_encode($result);
}

// Send mail if asked
if (!empty($_POST['mailing_send'])) {
    $mail = new myMail();
    $content['body'] = $_POST['spec_msg'];
    $content['subject'] = $_POST['spec_head'];
    $body = $mail -> formatmail($content['body']);
    $subject = $content['subject'];
    if ($mail_result = $mail->send_to_mailinglist($subject,$body)) {
        $result = "sent";
    } else {
        $result = "not_sent";
    };
    echo json_encode($result);
}

// Change user status
if (!empty($_POST['modify_status'])) {
    $username = htmlspecialchars($_POST['username']);
    $newstatus = htmlspecialchars($_POST['option']);
    $selected_user = new users($username);

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
    $result['content'] = site_config::generateuserslist();
    echo json_encode($result);
    exit;
}

// Udpate application settings
if (!empty($_POST['config_modify'])) {
    $config = new site_config('get');
    $config->update($_POST);
    $result = "<p id='success'>Modifications have been made!</p>";
    echo json_encode($result);
}

// Add a new post
if (!empty($_POST['post_add'])) {
    if ($_POST['post_add'] === 'post_add') {
        $post = new Posts();
        $result = $post->make($_POST);
    } else {
        $id = htmlspecialchars($_POST['postid']);
        $post = new Posts($id);
        $result = $post->update($_POST);
    }
    echo json_encode($result);
}

// Show selected post
if (!empty($_POST['post_show'])) {
    $postid = $_POST['postid'];
    if ($postid == "false") {$postid = false;}

    $username = htmlspecialchars($_SESSION['username']);
    $user = new users($username);
    $post = new Posts();
    $result = $post->showpost($user->fullname,$postid);
    echo json_encode($result);
}

// Delete a post
if (!empty($_POST['post_del'])) {
    $postid = htmlspecialchars($_POST['postid']);
    $post = new Posts();
    $result = $post->delete($postid);
    echo json_encode($result);
}

// Add a session/presentation type
if (!empty($_POST['add_type'])) {
    $config = new site_config('get');
    $class = $_POST['add_type'];
    $typename = $_POST['typename'];
    $varname = $class."_type";
    $config->$varname .= ",$typename";
    if ($config->update()) {
        //Get session types
        $result = "";
        $types = explode(',',$config->$varname);
        $divid = $class.'_'.$typename;
        foreach ($types as $type) {
            $result .= "
                <div class='type_div' id='$divid'>
                    <div class='type_name'>$type</div>
                    <div class='type_del' data-type='$type' data-class='$class'>
                    <img src='images/delete.png' style='width: 15px; height: auto;'>
                    </div>
                </div>
            ";
        }
    } else {
        $result = false;
    }
    echo json_encode($result);
}

// Delete a session/presentation type
if (!empty($_POST['del_type'])) {
    $config = new site_config('get');
    $class = $_POST['del_type'];
    $typename = $_POST['typename'];
    $varname = $class."_type";
    $config->$varname = explode(',',$config->$varname);
    $config->$varname = array_diff($config->$varname,array($typename));
    $config->$varname = implode(',',$config->$varname);
    if ($config->update()) {
        //Get session types
        $result = "";
        $types = explode(',',$config->$varname);
        $divid = $class.'_'.$typename;
        foreach ($types as $type) {
            $result .= "
                <div class='type_div' id='$divid'>
                    <div class='type_name'>$type</div>
                    <div class='type_del' data-type='$type' data-class='$class'>
                    <img src='images/delete.png' style='width: 15px; height: auto;'>
                    </div>
                </div>
            ";
        }
    } else {
        $result = false;
    }
    echo json_encode($result);
}

// Show sessions
if (!empty($_POST['show_session'])) {
    $nbsession = $_POST['show_session'];
    $result = Sessions::managesessions($nbsession);
    echo json_encode($result);
    exit;
}

// Modify a session type
if (!empty($_POST['mod_session_type'])) {
    $sessionid = $_POST['session'];
    $type = $_POST['type'];
    $session = new Session();
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
    $session = new Session();
    $sessionpost = array(
        'date'=>$sessionid,
        'time'=>$time);
    $result = $session->make($sessionpost);
    echo json_encode($result);
    exit;
}

