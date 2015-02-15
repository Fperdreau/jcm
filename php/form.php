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

// Get booked dates for DatePicker Calendar
if (!empty($_POST['get_calendar_param'])) {
	$booked = $db_set -> getinfo($presentation_table,"date"); // Get booked out dates from db
	$formatdate = array();
    $nb_pres = array();
	foreach($booked as $date) {
        // Count how many presentations there are for this day
        $sql = "SELECT date FROM $presentation_table WHERE date='$date'";
        $req = $db_set->send_query($sql);
        $pres = array();
        while ($row = mysqli_fetch_array($req)) {
            $pres[] = $row[0];
        }

	    $fdate = explode("-",$date);
	    $day = $fdate[2];
	    $month = $fdate[1];
	    $year = $fdate[0];

	    $formatdate[] = "$day-$month-$year";
        if (!empty($pres)) {
            $nb_pres[] = count($pres);
        } else {
            $nb_pres[] = 0;
        }
	}

    // Get application settings
	$config = new site_config('get');

	$result = array("max_nb_session"=>$config->max_nb_session,"jc_day"=>$config->jc_day,"today"=>date('d-m-Y'),"booked"=>$formatdate,"nb"=>$nb_pres);
	echo json_encode($result);
}

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
    if ($user -> make($user->username,$user->password,$user->firstname,$user->lastname,$user->position,$user->email)) {
        $result = "created";
    } else {
        $result = "exist";
    }
    echo json_encode($result);
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
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Process submissions
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
// Get file list (download list)
if (!empty($_POST['getfiles'])) {
    $pubid = $_POST['pubid'];
    $pub = new Press($pubid);
    $filelist = explode(',',$pub->link);
    $result = "<div class='dlmenu'>";
    foreach ($filelist as $file) {
        $result .= "<div class='dl_info'><div class='upl_name' id='$file'>$file</div></div>";
    }
    $result .= "</div>";
    echo json_encode($result);
}

//  delete publication
if (!empty($_POST['del_pub'])) {
    $Press = new Press();
    $id_press = htmlspecialchars($_POST['del_pub']);
    $Press->delete_pres($id_press);
    $result = "deleted";
    echo json_encode($result);
}

//  delete files
if (!empty($_POST['del_upl'])) {
    $uplname = htmlspecialchars($_POST['uplname']);
    $pub = new Press();
    $result = $pub->delete_file($uplname);
    echo json_encode($result);
}

// Submit a new presentation
if (!empty($_POST['submit'])) {
    // check entries
    $pub = new Press();
    $user = new users($_SESSION['username']);
    $date = $_POST['date'];
    if ($pub->isbooked($date)) {
        $result = "<p id='warning'>This date is booked out</p>";
    } else {
        if ($_POST['type'] != "guest") {
            $_POST['orator'] = $user->fullname;
        }

        $created = $pub -> make($_POST);
        if ($created == true) {
            $result = "<p id='success'>Your presentation has been submitted.</p>";
        } elseif ($created == "exists") {
            $result = "<p id='warning'>This presentation already exist in our database.</p>";
        } else {
            $result = "<p id='warning'>Oops, sorry went wrong.</p>";
        }
    }

    echo json_encode($result);
}

// Update/modify a presentation
if (!empty($_POST['update'])) {
    $pub = new Press($_POST['id_pres']);
    // check entries
    $user = new users($_SESSION['username']);
    $date = $_POST['date'];
    if ($pub->isbooked($date)) {
        $result = "<p id='warning'>This date is booked out</p>";
    } else {
        if ($_POST['type'] != "guest") {
            $_POST['orator'] = $user->fullname;
        }

        if ($_POST['type'] == "wishlist") {
            $_POST['type'] = "paper";
        }
        $created = $pub -> update($_POST);

        if ($created == true) {
            $result = "<p id='success'>Your presentation has been updated.</p>";
        } else {
            $result = "<p id='warning'>Oops, something went wrong</p>";
        }
    }
    echo json_encode($result);
}

// Suggest a presentation to the wishlist
if (isset($_POST['suggest'])) {
    $pub = new Press();
    $_POST['date'] = "";
    $_POST['type'] = "wishlist";
    $created = $pub -> make($_POST);
    if ($created == true) {
        $result = "<p id='success'>Your presentation has been submitted.</p>";
    } elseif ($created == "exist") {
        $result = "<p id='warning'>This presentation already exist in our database.</p>";
    } else {
        $result = "<p id='warning'>Oops, something went wrong</p>";
    }
    echo json_encode($result);
}

// Display presentation (modal dialog)
if (!empty($_POST['show_pub'])) {
    $id_press = $_POST['show_pub'];
    if (!isset($_SESSION['username'])) {
        $_SESSION['username'] = false;
    }
    $user = new users($_SESSION['username']);
    $pub = new Press($id_press);
    $form = displaypub($user,$pub);
    echo json_encode($form);
}

// Display modification form
if (!empty($_POST['mod_pub'])) {
    $id_press = $_POST['mod_pub'];
    $user = new users($_SESSION['username']);
    $pub = new Press($id_press);
    $form = displayform($user,$pub,'update');
    echo json_encode($form);
}

if (!empty($_POST['getform'])) {
    $pub = new Press();
    $user = new users($_SESSION['username']);
    $form = displayform($user,$pub,'submit');
    echo json_encode($form);
}
/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Archives
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
// Select years to display
if (!empty($_POST['select_year'])) {
	$Press = new Press();
    $selected_year = $_POST['select_year'];
	if ($selected_year == "" || $selected_year == "all") {
		$selected_year = null;
	}
    $publist = $Press -> getpublicationlist($selected_year);
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
	$pub = new Press();
	$filename = $pub->upload_file($_FILES['file']);
	echo json_encode($filename);
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Admin tools
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
if (!empty($_POST['user_select'])) {
	$config = new site_config();
    $filter = htmlspecialchars($_POST['user_select']);
	if ($filter == "") {
		$filter = null;
	}

    $userlist = $config -> generateuserslist($filter);
    echo json_encode($userlist);
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

if (!empty($_POST['modify_status'])) {
    $username = htmlspecialchars($_POST['username']);
    $newstatus = htmlspecialchars($_POST['option']);
    $selected_user = new users($username);

    if ($newstatus == 'delete') {
        $selected_user -> delete_user();
        $result = "Account successfully deleted";
    } elseif ($newstatus == "activate") {
        $result = $selected_user -> activation(1);
    } elseif ($newstatus == "desactivate") {
        $result = $selected_user -> activation(0);
    } else {
        $selected_user -> change_user_status($newstatus);
        $result = "User status is now $newstatus!";
    }
    echo json_encode($result);
}

if (!empty($_POST['config_modify'])) {
    $config = new site_config('get');
    $config->update($_POST);
    $result = "<p id='success'>Modifications have been made!</p>";
    echo json_encode($result);
}

if (!empty($_POST['post_send'])) {
    $post = new posts();
    $new_post = htmlspecialchars($_POST['new_post']);
    $user_fullname = htmlspecialchars($_POST['fullname']);
    $post -> add_post($new_post,$user_fullname);
    $result = "posted";
    echo json_encode($result);
}

if (!empty($_POST['delete_temp'])) {
	$path_to_file = $_SESSION['path_to_app'].htmlspecialchars($_POST['link']);
	if (unlink($path_to_file)) {
		$result = "deleted";
	} else {
		$result = "not_deleted";
	}
	echo json_encode($result);
}
