<?php
/*
Copyright © 2014, F. Perdreau, Radboud University Nijmegen
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

class users {
    public $date = "";
    public $username = "";
    public $password = "";
    public $firstname = "";
    public $lastname = "";
    public $fullname = "";
    public $position = "";
    public $email = "";
    public $reminder = 1;
    public $notification = 1;
    public $status = "member";
    public $nbpres = 0;
    public $active = 0;
    public $hash = "";

    function __construct($prov_username=null) {
        if ($prov_username != null) {
            self::getuserinfo($prov_username);
        }
    }

    // Create user
    function create_user($username,$password,$firstname,$lastname,$position,$email,$status = "member") {
		$this -> date = date("Y-m-d H:i:s");
        $this -> username = $username;
        $this -> firstname = $firstname;
        $this -> lastname = $lastname;
		$this -> fullname = "$this->firstname $this->lastname";
        $this -> position = $position;
        $this -> email = $email;
        $this -> status = $status;
        $this -> hash = $this -> create_hash($this);
        $this -> password = self::crypt_pwd($password);
        if ($this->status == "admin") {
        	$this->active = 1;
		}

		require_once($_SESSION['path_to_includes'].'db_connect.php');
        require_once($_SESSION['path_to_includes'].'myMail.php');
        require($_SESSION['path_to_app']."/admin/conf/config.php");
        $mail = new myMail();
        $db_set = new DB_set();

		// Parse variables and values to store in the table
		$class_vars = get_class_vars("users");
		$class_keys = array_keys($class_vars);
    	$variables = implode(",", $class_keys);
		$values = array();
        foreach ($class_vars as $key => $value) {
        	$values[] = "'".$this->$key."'";
        }
		$values = implode(",", $values);
        if (self :: user_exist($this->username) == false && self :: mail_exist($this->email) == false) {
			// Add to user table
            $db_set->addcontent($users_table,$variables,$values);

        	if ($this->status !=  "admin") {
				// Add to mailing list
                $db_set->addcontent($mailinglist_table,"username,email","'$this->username','$this->email'");

				// Send verification email to admins/organizer
                if ($mail-> send_verification_mail($this->hash,$this->email,$this->fullname)) {
                	return true;
                } else {
                	return false;
                }
	        } else {
	            if ($mail-> send_confirmation_mail($this->email,$this->username,$this->password)) {
	            	return true;
	            } else {
	            	return false;
	            }
	        }
		} else {
			return false;
		}
    }

    function getuserinfo($prov_username) {
        require_once($_SESSION['path_to_includes'].'db_connect.php');
        require($_SESSION['path_to_app']."/admin/conf/config.php");

        $class_vars = get_class_vars("users");

        $db_set = new DB_set();
        $sql = "SELECT * FROM $users_table WHERE username='$prov_username'";
        $req = $db_set -> send_query($sql);
        $data = mysqli_fetch_assoc($req);
        $exist = $db_set->getinfo($users_table,'username',array("username"),array("'$prov_username'"));
        if (!empty($exist)) {
            foreach ($data as $varname=>$value) {
                if (array_key_exists($varname,$class_vars)) {
                    $this->$varname = $value;
                }
            }
            $this->fullname = $this->firstname." ".$this->lastname;
            $this -> nbpres = self::get_nbpres();
            return true;
        } else {
            return false;
        }
    }

    // Get the user number of presentations
    function get_nbpres() {
        // Update user nb of presentations
        require_once($_SESSION['path_to_app'].'/includes/db_connect.php');
        require($_SESSION['path_to_app']."/admin/conf/config.php");
        $db_set = new DB_set();
        $sql = "SELECT title FROM $presentation_table WHERE orator='$this->fullname' and type!='wishlist'";
        $req = $db_set -> send_query($sql);
        $cpt = 0;
        while (mysqli_fetch_array($req)) {
            $cpt++;
        }
        return $cpt;
    }

    // Update user info
    function updateuserinfo($post) {
        require_once($_SESSION['path_to_includes'].'db_connect.php');
        require($_SESSION['path_to_app']."/admin/conf/config.php");
        $db_set = new DB_set();

        $class_vars = get_class_vars("users");
        $class_keys = array_keys($class_vars);
        foreach ($post as $name => $value) {
            $value = htmlspecialchars($value);
            if (in_array($name,$class_keys)) {
                $db_set->updatecontent($users_table,"$name","'$value'",array("username"),array("'$this->username'"));
            }
        }
        self::getuserinfo($this->username);
        return true;
    }

    function user_exist($prov_username) {
        require_once($_SESSION['path_to_includes'].'db_connect.php');
        require($_SESSION['path_to_app']."/admin/conf/config.php");

        $db_set = new DB_set();
        $userslist = $db_set -> getinfo($users_table,'username');
        $active = $db_set->getinfo($users_table,'active',array('username'),array("'$prov_username'"));
        if (in_array($prov_username,$userslist) && $active == 1) {
            return true;
        } else {
            return false;
        }
    }

    function mail_exist($prov_mail) {
        require_once($_SESSION['path_to_includes'].'db_connect.php');
        require($_SESSION['path_to_app']."/admin/conf/config.php");

        $db_set = new DB_set();
        $maillist = $db_set -> getinfo($users_table,'email');
        $active = $db_set->getinfo($users_table,'active',array('email'),array("'$prov_mail'"));

        if (in_array($prov_mail,$maillist)) {
            return true;
        } else {
            return false;
        }
    }

    function create_hash() {
        $hash = md5( rand(0,1000) ); // Generate random 32 character hash and assign it to a local variable.
        return $hash;
    }

    function check_account_activation($hash,$email,$result) {
        require_once($_SESSION['path_to_includes'].'db_connect.php');
        require_once($_SESSION['path_to_includes'].'myMail.php');
        require($_SESSION['path_to_app']."/admin/conf/config.php");
        $db_set = new DB_set();
        $username = $db_set ->getinfo($users_table,'username',array("email"),array("'$email'"));
        $this->getuserinfo($username);
        if ($result == "true") {
            if ($this->active == 0) {
                if ($this->hash == $hash) {
                    $db_set->updatecontent($users_table,'active',1,array("email"),array("'$this->email'"));
                    $mail = new myMail();
                    $mail-> send_confirmation_mail($this->email,$this->username);
                    return "Account successfully activated. An email has been sent to the user!";
                } else {
                    return "Unexistent hash code for user.";
                }
            } else {
                return "This account has already been activated.";
            }
        } else {
            $db_set->deletecontent($users_table,array("email"),array("'$email''"));
            return "Permission denied by the admin. Account successfully deleted.";
        }
    }

    function activation($option) {
        require_once($_SESSION['path_to_includes'].'db_connect.php');
        require($_SESSION['path_to_app']."/admin/conf/config.php");
        if ($option == 1){
            return self::check_account_activation($this->hash,$this->email,true);
        } else {
            $db_set = new DB_set();
            $db_set->updatecontent($users_table,'active',0,array("email"),array("'$this->email'"));
            return "Account successfully deactivated";
        }
    }

    function check_pwd($password) {
        require_once($_SESSION['path_to_includes'].'db_connect.php');
        require_once($_SESSION['path_to_includes'].'PasswordHash.php');
        require($_SESSION['path_to_app']."/admin/conf/config.php");

        $db_set = new DB_set();
        $truepwd = $db_set -> getinfo($users_table,"password",array("username"),array("'$this->username'"));

        $check = validate_password($password, $truepwd);

        if ($check == 1) {
            $this->logged = true;
            return true;
        } else {
            return false;
        }
    }

    function crypt_pwd($password) {
        require_once($_SESSION['path_to_includes'].'PasswordHash.php');
        $hash = create_hash($password);

        return $hash;
    }

    function delete_user() {
        require_once($_SESSION['path_to_includes'].'db_connect.php');
        require($_SESSION['path_to_app']."/admin/conf/config.php");

        $db_set = new DB_set();
        // Delete corresponding entry in the publication table
        $db_set -> deletecontent($users_table,array("username"),array("'$this->username'"));
    }

    function change_user_status($newstatus) {
        require_once($_SESSION['path_to_includes'].'db_connect.php');
        require($_SESSION['path_to_app']."/admin/conf/config.php");
        $db_set = new DB_set();
        $db_set -> updatecontent($users_table,'status',"'$newstatus'",array("username"),array("'$this->username'"));
    }


}
