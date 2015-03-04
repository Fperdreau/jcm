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

/**
 * Class Users
 *
 * Handle user-related methods (generate users list/ get organizers list)
 */
class Users {
    protected $db;
    protected $tablename;

    /**
     * @param $db
     */
    function __construct($db) {
        $this->db = $db;
        $this->tablename = $this->db->tablesname["User"];
    }

    /**
     * Get organizers list
     * @param null $admin
     * @return array
     */
    public function getadmin($admin=null) {
        $sql = "SELECT username,password,firstname,lastname,fullname,position,email,status FROM $this->tablename WHERE status='organizer'";
        if (null != $admin) {
            $sql .= "or status='admin'";
        }
        $req = $this->db->send_query($sql);
        $user_info = array();
        while ($row = mysqli_fetch_assoc($req)) {
            $user_info[]= $row;
        }
        return $user_info;
    }

    /**
     * Generate and show list of users
     * @param null $filter
     * @return string
     */
    public function generateuserslist($filter = null) {
        if (null == $filter) {
            $filter = 'lastname';
        }

        $sql = "SELECT username FROM $this->tablename ORDER BY $filter";

        $req = $this->db->send_query($sql);
        $result =  "
            <div class='list-container'>
                <div class='list-heading' style='width: 10%'>First Name</div>
                <div class='list-heading' style='width: 10%'>Last Name</div>
                <div class='list-heading' style='width: 10%'>User Name</div>
                <div class='list-heading' style='width: 20%'>Email</div>
                <div class='list-heading' style='width: 10%'>Activated</div>
                <div class='list-heading' style='width: 5%'>Submissions</div>
                <div class='list-heading' style='width: 10%'>Status</div>
            </div>
        ";

        while ($cur_user = mysqli_fetch_array($req)) {
            /** @var User $user */
            $user = new User($this->db,$cur_user['username']);

            $nbpres = $user->get_nbpres();
            // Compute age
            if ($user->active == 1) {
                $to   = new DateTime(date('Y-m-d'));
                $from = new DateTime(date("Y-m-d",strtotime($user->date)));
                $diff = $to->diff($from);

                $cur_trage = "$diff->d days ago";
                if ($diff->days >31) {
                    $cur_age = $diff->m;
                    $cur_trage = "$cur_age months ago";
                    if ($diff->m >12) {
                        $cur_age = $diff->y;
                        $cur_trage = "$cur_age years ago";
                    }
                }
                $option_active = "<option value='desactivate'>Deactivate</option>";
            } else {
                $cur_trage = "No";
                $option_active = "<option value='activate'>Activate</option>";
            }

            $result .= "
            <div class='list-container' id='section_$user->username'>
                <div class='list-section' style='width: 10%'>$user->firstname</div>
                <div class='list-section' style='width: 10%'>$user->lastname</div>
                <div class='list-section' style='width: 10%'>$user->username</div>
                <div class='list-section' style='width: 20%'>$user->email</div>
                <div class='list-section' style='width: 10%'>$cur_trage</div>
                <div class='list-section' style='width: 5%'>$nbpres</div>

                <div class='list-section' style='width: 10%'>
                    <select name='status' id='status' data-user='$user->username' class='modify_status'>
                        <option value='$user->status' selected='selected'>$user->status</option>
                        <option value='member'>Member</option>
                        <option value='admin'>Admin</option>
                        <option value='organizer'>Organizer</option>
                        $option_active
                        <option value='delete' style='background-color: rgba(207, 81, 81, .5);'>Delete</option>
                    </select>
                </div>
            </div>
            ";
        }
        return $result;
    }
}

/**
 * Class User
 *
 * Handle User information (username,password,etc.) and user-related routines (creation, update of user information).
 */
class User extends Users{
    /** @var string  */
    public $date = "";

    /** @var null|string  */
    public $username = "";

    /** @var string  */
    public $password = "";

    /** @var string  */
    public $firstname = "";

    /** @var string  */
    public $lastname = "";

    /** @var string  */
    public $fullname = "";

    /** @var string  */
    public $position = "";

    /** @var string  */
    public $email = "";

    /** @var int  */
    public $reminder = 1;

    /** @var int  */
    public $notification = 1;

    /** @var string  */
    public $status = "member";

    /** @var int  */
    public $nbpres = 0;

    /** @var int  */
    public $active = 0;

    /** @var string  */
    public $hash = "";

    /**
     * Number of unsuccessful login attempts
     * @var int
     *
     */
    public $attempt = 0;

    /** @var  string */
    private $last_login;

    /**
     * Constructor
     *
     * @param DbSet $db
     * @param null $username
     */
    function __construct(DbSet $db,$username=null) {
        $this->db = $db;
        $this->tablename = $this->db->tablesname["User"];
        $this->username = $username;
        if ($username != null) {
            self::get($username);
        }
    }

    /**
     * Create user
     *
     * @param $username
     * @param $password
     * @param $firstname
     * @param $lastname
     * @param $position
     * @param $email
     * @param string $status
     * @return bool|string
     */
    function make($username,$password,$firstname,$lastname,$position,$email,$status = "member") {
        $config = new AppConfig($this->db);
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

        /** @var AppMail $mail */
        $mail = new AppMail($this->db,$config);

		// Parse variables and values to store in the table
		$class_vars = get_class_vars("User");
        $keys = array();
		$values = array();
        foreach ($class_vars as $key => $value) {
            if (in_array($key,array("db","tablename"))) continue;
            $escaped = $this->db->escape_query($this->$key);
        	$values[] = "'$escaped'";
            $keys[] = $key;
        }
		$values = implode(",", $values);
        $variables = implode(",", $keys);
        if (self :: user_exist($this->username) == false
            && self :: mail_exist($this->email) == false) {
                // Add to user table
                $this->db->addcontent($this->tablename,$variables,$values);

                if ($this->status !=  "admin") {

                    // Send verification email to admins/organizer
                    if ($mail-> send_verification_mail($this->hash,$this->email,$this->fullname)) {
                        return true;
                    } else {
                        self::delete_user($this->username);
                        return 'mail_pb';
                    }
                } else {
                    if ($mail-> send_confirmation_mail($this->email,$this->username,$this->password)) {
                        return true;
                    } else {
                        return 'mail_pb';
                    }
                }
		} else {
			return 'exist';
		}
    }

    /**
     * Get user's information
     *
     * @param $prov_username
     * @return bool
     */
    function get($prov_username) {
        $class_vars = get_class_vars("User");
        $sql = "SELECT * FROM $this->tablename WHERE username='$prov_username'";
        $req = $this->db -> send_query($sql);
        $data = mysqli_fetch_assoc($req);
        $exist = $this->db->getinfo($this->tablename,'username',array("username"),array("'$prov_username'"));
        if (!empty($exist)) {
            foreach ($data as $varname=>$value) {
                if (array_key_exists($varname,$class_vars)) {
                    $this->$varname = htmlspecialchars_decode($value);
                }
            }
            $this->fullname = $this->firstname." ".$this->lastname;
            $this->nbpres = self::get_nbpres();
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get the number of presentations submitted by the user
     *
     * @return int
     */
    function get_nbpres() {
        $tablename = $this->db->tablesname['Presentation'];
        $sql = "SELECT title FROM $tablename WHERE orator='$this->username' and type!='wishlist'";
        $req = $this->db -> send_query($sql);
        $cpt = 0;
        while (mysqli_fetch_array($req)) {
            $cpt++;
        }
        return $cpt;
    }

    /**
     * Update user's information
     *
     * @param $post
     * @return bool
     */
    function update($post) {
        $class_vars = get_class_vars("User");
        $class_keys = array_keys($class_vars);
        foreach ($post as $name => $value) {
            if (in_array($name,array("db","tablename"))) continue;
            $value = htmlspecialchars($value);
            if (in_array($name,$class_keys)) {
                $this->db->updatecontent($this->tablename,"$name","'$value'",array("username"),array("'$this->username'"));
            }
        }
        self::get($this->username);
        return true;
    }

    /**
     * Check if the provided username already exists in the database
     *
     * @param $prov_username
     * @return bool
     */
    function user_exist($prov_username) {
        $userslist = $this->db -> getinfo($this->tablename,'username');
        $active = $this->db->getinfo($this->tablename,'active',array('username'),array("'$prov_username'"));
        if (in_array($prov_username,$userslist) && $active == 1) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if the provided email already exists (TRUE) or not (FALSE) in the database
     *
     * @param $prov_mail
     * @return bool
     */
    function mail_exist($prov_mail) {
        $mailinglist = $this->db->getinfo($this->tablename,'email');
        return (in_array($prov_mail,$mailinglist));
    }

    /**
     * Creates an unique hash for the user (used in emails),
     * i.e. Generate random 32 character hash and assign it to a local variable
     *
     * @return string
     */
    function create_hash() {
        $hash = md5( rand(0,1000) );
        return $hash;
    }

    /**
     * Activate the user's account
     *
     * @param $hash
     * @param $email
     * @param $result
     * @return string
     */
    function check_account_activation($hash,$email,$result) {
        $config = new AppConfig($this->db);
        $username = $this->db ->getinfo($this->tablename,'username',array("email"),array("'$email'"));
        $this->get($username);
        if ($result == "true") {
            if ($this->active == 0) {
                if ($this->hash == $hash) {
                    $this->db->updatecontent($this->tablename,array('active','attempt'),array(1,0),array("email"),array("'$this->email'"));
                    /** @var AppMail $mail */
                    $mail = new AppMail($this->db,$config);
                    $mail-> send_confirmation_mail($this->email,$this->username);
                    return "Account successfully activated. An email has been sent to the user!";
                } else {
                    return "Unexistent hash code for user.";
                }
            } else {
                return "This account has already been activated.";
            }
        } else {
            self::delete_user($this->username);
            return "Permission denied by the admin. Account successfully deleted.";
        }
    }

    /**
     * Activate or deactivate the user's account
     *
     * @param $option
     * @return string
     */
    function activation($option) {
        if ($option == 1){
            return self::check_account_activation($this->hash,$this->email,true);
        } else {
            /** @var $this->db DbSet */
            $this->db = new DbSet();
            $this->db->updatecontent($this->tablename,'active',0,array("email"),array("'$this->email'"));
            return "Account successfully deactivated";
        }
    }

    /**
     * Check number of unsuccessful login attempts.
     * Deactivate the user's account if this number exceeds the maximum
     * allowed number of attempts and send an email to the user with an activation link.
     * @return int
     */
    public function checkattempt() {
        $last_login = new DateTime($this->last_login);
        $now = new DateTime();
        $diff = $now->diff($last_login);
        // Reset the number of attempts if last login attempt was 1 hour ago
        $this->attempt = $diff->h >= 1 ? 0:$this->attempt;
        $this->attempt += 1;
        $AppConfig = new AppConfig($this->db);
        if ($this->attempt >= $AppConfig->max_nb_attempt) {
            self::activation(0); // We deactivate the user's account
            /** @var AppMail $mail */
            $mail = new AppMail($this->db,$AppConfig);
            $mail->send_activation_mail($this);
            return false;
        }
        $this->db->updatecontent($this->tablename,'attempt',$this->attempt,array("username"),array("'$this->username'"));
        return $AppConfig->max_nb_attempt - $this->attempt;
    }

    /**
     * Check if the provided password is correct (TRUE) or not (FALSE)
     *
     * @param $password
     * @return bool
     */
    function check_pwd($password) {
        $truepwd = $this->db-> getinfo($this->tablename,"password",array("username"),array("'$this->username'"));

        $check = validate_password($password, $truepwd);

        if ($check == 1) {
            $this->logged = true;
            return true;
        } else {
            $this->logged = false;
            return false;
        }
    }

    /**
     * Encrypt the password before adding it to the database
     *
     * @param $password
     * @return string
     */
    function crypt_pwd($password) {
        $hash = create_hash($password);
        return $hash;
    }


    /**
     * Delete user's account
     *
     * @return bool
     */
    function delete_user() {
        return $this->db -> deletecontent($this->tablename,array("username"),array("'$this->username'"));
    }

    /**
     * Change the user's status (admin/organizer/member)
     *
     * @param $newstatus
     * @return bool
     */
    function change_user_status($newstatus) {
        return $this->db->updatecontent($this->tablename,'status',"'$newstatus'",array("username"),array("'$this->username'"));
    }

    /**
     * Get user's list of publications to display it on his/her profile page
     *
     * @param null $filter
     * @return string
     */
    function getpublicationlist($filter = NULL) {

        $sql = "SELECT id_pres FROM ".$this->db->tablesname['Presentation']." WHERE username='$this->username'";
        if (null != $filter) {
            $sql .= " AND YEAR(date)=$filter";
        }
        $sql .= " ORDER BY date";
        $req = $this->db->send_query($sql);
        $content = "
            <div class='list-container' id='pub_labels'>
                <div style='text-align: center; font-weight: bold; width: 10%;'>Date</div>
                <div style='text-align: center; font-weight: bold; width: 50%;'>Title</div>
                <div style='text-align: center; font-weight: bold; width: 20%;'>Authors</div>
                <div style='text-align: center; font-weight: bold; width: 10%;'></div>
            </div>
        ";

        while ($row = mysqli_fetch_assoc($req)) {
            $pubid = $row['id_pres'];
            /** @var Presentation $pub */
            $pub = new Presentation($this->db,$pubid);
            if ($pub->date == "0000-00-00") {
                if ($pub->type == "wishlist") {
                    $date = "WISH";
                } else {
                    $date = "";
                }
            } else {
                $date = $pub->date;
            }
            $content .= "
                <div class='pub_container' id='$pub->id_pres'>
                    <div class='list-container'>
                        <div style='text-align: center; width: 10%;'>$date</div>
                        <div style='text-align: left; width: 50%; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;'>$pub->title</div>
                        <div style='text-align: center; width: 20%; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;'>$pub->authors</div>
                        <div style='text-align: center; width: 10%; vertical-align: middle;'>
                            <div class='show_btn'><a href='#pub_modal' class='modal_trigger' id='modal_trigger_pubcontainer' rel='pub_leanModal' data-id='$pub->id_pres'>MORE</a>
                            </div>
                        </div>
                    </div>
                </div>
            ";
        }
        return $content;
    }
}
