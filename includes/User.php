<?php
/**
 * File for class Users and User
 *
 * PHP version 5
 *
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
 * Class Users
 *
 * Handle user-related methods (generate users list/ get organizers list)
 */
class Users extends AppTable {

    /**
     * @var array $table_data: Table schema
     */
    protected $table_data = array(
        "id" => array("INT NOT NULL AUTO_INCREMENT", false),
        "date" => array("DATETIME", false),
        "firstname" => array("CHAR(30)", false),
        "lastname" => array("CHAR(30)", false),
        "fullname" => array("CHAR(30)", false),
        "username" => array("CHAR(30)", false),
        "password" => array("CHAR(50)", false),
        "position" => array("CHAR(10)", false),
        "email" => array("CHAR(100)", false),
        "notification" => array("INT(1) NOT NULL", 1),
        "reminder" => array("INT(1) NOT NULL", 1),
        "assign" => array("INT(1) NOT NULL", 1),
        "nbpres" => array("INT(3) NOT NULL", 0),
        "status" => array("CHAR(10)", false),
        "hash" => array("CHAR(32)", false),
        "active" => array("INT(1) NOT NULL", 0),
        "attempt" => array("INT(1) NOT NULL", 0),
        "last_login" => array("DATETIME NOT NULL"),
        "primary" => "id");


    /**
     * Users constructor.
     * @param AppDb $db
     */
    function __construct(AppDb $db) {
        parent::__construct($db, 'User', $this->table_data);
    }

    /**
     * Get organizers list
     * @param null $admin
     * @return array
     */
    public function getadmin($admin=null) {
        $sql = "SELECT * FROM $this->tablename WHERE status='organizer'";
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
     * Get user list
     * @param bool $assign
     * @return array
     */
    public function getUsers($assign = false) {
        $sql = "SELECT * FROM $this->tablename WHERE active=1 and status!='admin'";
        $sql = ($assign == true) ? $sql." and assign=1":$sql;
        $req = $this->db->send_query($sql);
        $data = array();
        while ($row = $req->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    /**
     * @return array|null
     */
    public function all() {
        $sql = "SELECT * FROM $this->tablename WHERE active=1 and status!='admin' ORDER BY fullname";
        $req = $this->db->send_query($sql);
        $data = array();
        while ($row = $req->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
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
            <div class='list-container list-heading'>
                <div class='user_select user_firstname' data-filter='firstname'>First Name</div>
                <div class='user_select user_lastname' data-filter='lastname'>Last Name</div>
                <div class='user_select user_email' data-filter='email'>Email</div>
                <div class='user_select user_small' data-filter='active'>Activated</div>
                <div class='user_select user_small' data-filter='nbpres'>Submissions</div>
                <div class='user_select user_op' data-filter='status'>Status</div>
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
                <div class='user_firstname'>$user->firstname</div>
                <div class='user_lastname'>$user->lastname</div>
                <div class='user_email'>$user->email</div>
                <div class='user_small'>$cur_trage</div>
                <div class='user_small'>$nbpres</div>
                <div class='user_op'>
                    <select name='status' class='user_status modify_status' data-user='$user->username' style='max-width: 75%;'>
                        <option value='$user->status' selected='selected'>$user->status</option>
                        <option value='member'>Member</option>
                        <option value='admin'>Admin</option>
                        <option value='organizer'>Organizer</option>
                        $option_active
                        <option value='delete' style='background-color: rgba(207, 81, 81, 1); color: white;'>Delete</option>
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

    /**
     * @var int
     */
    public $id;
    
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

    /** @var int  */
    public $assign = 1;

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
    protected $last_login;

    /**
     * Constructor
     *
     * @param AppDb $db
     * @param null $username
     */
    function __construct(AppDb $db,$username=null) {
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
     * @param array $post
     * @return bool|string
     */
    function make($post=array()) {
        $mail = new AppMail($this->db);

        $post = self::sanitize($post); // Escape $_POST content
        $this->date = date("Y-m-d H:i:s"); // Date of creation (today)

        // Reformat first and last names
        if (!empty($post['firstname'])) {
            $post['firstname'] = ucfirst(strtolower($_POST['firstname']));
            $post['lastname'] = ucfirst(strtolower($_POST['lastname']));
            $post['fullname'] = $post['firstname']." ".$post['lastname'];
        }

        $post['hash'] = $this->make_hash(); // Create an unique hash for this user
        $post['password']= self::crypt_pwd($post['password']); // Encrypt password
        $post['active'] = ($post['status'] == "admin") ? 1:0; // Automatically activate the account if the user has an
        // admin level

		$class_vars = get_class_vars("User");
        if (self :: user_exist($post['username']) == false
            && self :: mail_exist($post['email']) == false) {
            $content = $this->parsenewdata($class_vars,$post); // Parse variables and values to store in the table
            $this->db->addcontent($this->tablename,$content); // Add to user table

            if ($this->status !=  "admin") {
                // Send verification email to admins/organizer
                if ($mail-> send_verification_mail($this->hash,$this->email,$this->fullname)) {
                    $result['status'] = true;
                    $result['msg'] = "Your account has been created. You will receive an email after
                        its validation by our admins.";
                } else {
                    self::delete_user($this->username);
                    $result['status'] = false;
                    $result['msg'] = "Sorry, we have not been able to send a verification email to the organizers.
                        Your registration cannot be validated for the moment. Please try again later.";
                }
            } else {
                // Send confirmation email to the user directly
                if ($this->send_confirmation_mail()) {
                    $result['status'] = true;
                    $result['msg'] = "Your account has been successfully created!";
                } else {
                    $result['status'] = true;
                    $result['msg'] = "Sorry, we have not been able to send a verification email to the organizers.
                        Your registration cannot be validated for the moment. Please try again later.";;
                }
            }
		} else {
            $result['status'] = false;
			$result['msg'] = "This username/email address already exist in our database";
		}
        return $result;
    }

    /**
     * Get user's information from the database
     *
     * @param $prov_username
     * @return bool
     */
    function get($prov_username) {
        $class_vars = get_class_vars("User");
        $sql = "SELECT * FROM $this->tablename WHERE username='$prov_username'";
        $data = $this->db -> send_query($sql)->fetch_assoc();
        if (!empty($data)) {
            foreach ($data as $varname=>$value) {
                if (array_key_exists($varname,$class_vars)) {
                    $this->$varname = htmlspecialchars_decode($value);
                }
            }
            $this->firstname = ucfirst(strtolower($this->firstname));
            $this->lastname = ucfirst(strtolower($this->lastname));
            $this->fullname = $this->firstname." ".$this->lastname;
            $this->nbpres = self::get_nbpres();
            $this->update(array('nbpres'=>$this->nbpres));
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $id
     * @return array|null
     */
    public function getById($id) {
        $sql = "SELECT * FROM $this->tablename WHERE id='{$id}'";
        $req = $this->db -> send_query($sql);
        return mysqli_fetch_assoc($req);
    }

    /**
     * Get the number of presentations submitted by the user
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
    public function update($post) {
        $class_vars = get_class_vars("User");
        $content = $this->parsenewdata($class_vars,$post);
        $result['status'] = $this->db->updatecontent($this->tablename,$content,array("username"=>$this->username));
        return $result;
    }

    /**
     * Check if the provided username already exists in the database
     *
     * @param $prov_username
     * @return bool
     */
    public function user_exist($prov_username) {
        $userslist = $this->db->getinfo($this->tablename,'username');
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
    public function mail_exist($prov_mail) {
        $mailinglist = $this->db->getinfo($this->tablename,'email');
        return (in_array($prov_mail,$mailinglist));
    }

    /**
     * Creates an unique hash for the user (used in emails),
     * i.e. Generate random 32 character hash and assign it to a local variable
     *
     * @return string
     */
    private function make_hash() {
        $hash = md5( rand(0,1000) );
        return $hash;
    }

    /**
     * Activate the user's account
     *
     * @param $hash
     * @param $email
     * @param $activ
     * @return string
     */
    public function check_account_activation($hash,$email,$activ) {
        $username = $this->db ->getinfo($this->tablename,'username',array("email"),array("'$email'"));
        $this->get($username);
        if ($activ == "true") {
            if ($this->active == 0) {
                if ($this->hash == $hash) {
                    $this->db->updatecontent($this->tablename,array('active'=>1,'attempt'=>0),array("email"=>$this->email));
                    if ($this->send_confirmation_mail()) {
                        $result['status'] = true;
                        $result['msg'] = "Account successfully activated. An email has been sent to the user!";
                    } else {
                        $result['status'] = false;
                        $result['msg'] = "Account successfully activated, but we could not send a confirmation email to
                        the user.";
                    }
                } else {
                    $result['status'] = false;
                    $result['msg'] = "Unexistent hash code for user.";
                }
            } else {
                $result['status'] = false;
                $result['msg'] = "This account has already been activated.";
            }
        } else {
            self::delete_user($this->username);
            return "Permission denied by the admin. Account successfully deleted.";
        }
        return $result;
    }

    /**
     * Activate or deactivate the user's account
     *
     * @param $option
     * @return string
     */
    private function activation($option) {
        if ($option == 1){
            $result = self::check_account_activation($this->hash,$this->email,true);
        } else {
            if ($this->db->updatecontent($this->tablename,array('active'=>0),array("email"=>$this->email))) {
                $result['status'] = true;
                $result['msg'] = "Account successfully deactivated";
            } else {
                $result['status'] = false;
            }
        }
        return $result;
    }

    /**
     * Send a confirmation email to the new user once his/her registration has been validated by an organizer
     * @return bool
     */
    private function send_confirmation_mail() {
        $config = new AppConfig($this->db);
        $AppMail = new AppMail($this->db);
        $subject = 'Sign up | Confirmation'; // Give the email a subject
        $login_url = $config->getAppUrl()."index.php";

        $content = "
        <div style='width: 100%; margin: auto;'>
            <p>Hello $this->fullname,</p>
            <p>Thank you for signing up!</p>
        </div>
        <div style='display: block; padding: 10px; margin: 0 30px 20px 0; border: 1px solid #ddd; background-color: rgba(255,255,255,1);'>
            Your account has been created, you can now <a href='$login_url'>log in</a> with the following credentials.
            <p><b>Username</b>: $this->username</p>
            <p></p><b>Password</b>: Only you know it!</p>
        </div>";
        $body = $AppMail->formatmail($content);
        return $AppMail->send_mail($this->email,$subject,$body);
    }

    /**
     * Send an email to the user if his/her account has been deactivated due to too many login attempts.
     * @return bool
     */
    private function send_activation_mail() {
        $config = new AppConfig($this->db);
        $AppMail = new AppMail($this->db);

        $subject = 'Your account has been deactivated'; // Give the email a subject
        $authorize_url = $config->getAppUrl()."index.php?page=verify&email=$this->email&hash=$this->hash&result=true";
        $newpwurl = $config->getAppUrl()."index.php?page=renew_pwd&hash=$this->hash&email=$this->email";
        $content = "
        <div style='width: 100%; margin: auto;'>
            <p>Hello $this->fullname,</p>
            <p>We have the regret to inform you that your account has been deactivated due to too many login attempts.</p>
        </div>
        <div style='display: block; padding: 10px; margin: 0 30px 20px 0; border: 1px solid #ddd; background-color: rgba(255,255,255,1);'>
            <p>You can reactivate your account by following this link:</br>
                <a href='$authorize_url'>$authorize_url</a>
            </p>
            <p>If you forgot your password, you can ask for another one here:<br>
                <a href='$newpwurl'>$newpwurl</a>
            </p>
        </div>";
        $body = $AppMail->formatmail($content);
        return $AppMail->send_mail($this->email,$subject,$body);
    }

    /**
     * User login
     * @param $post
     * @return mixed
     */
    public function login($post) {
        $password = htmlspecialchars($post['password']);
        if ($this->get($this->username) == true) {
            if ($this->active == 1) {
                if ($this -> check_pwd($password) == true) {
                    $_SESSION['logok'] = true;
                    $_SESSION['username'] = $this -> username;
                    $_SESSION['status'] = $this -> status;
                    $result['msg'] = "Hi $this->fullname,<br> welcome back!";
                    $result['status'] = true;
                } else {
                    $_SESSION['logok'] = false;
                    $result['status'] = false;
                    $attempt = $this->checkattempt();
                    if ($attempt == false) {
                        $result['msg'] = "Wrong password. You have exceeded the maximum number
                            of possible attempts, hence your account has been deactivated for security reasons.
                            We have sent an email to your address including an activation link.";
                    } else {
                        $result['msg'] = "Wrong password. $attempt login attempts remaining";
                    }
                }
            } else {
                $result['status'] = false;
                $result['msg'] = "Sorry, your account is not activated yet. <br> You will receive an
                    email as soon as your registration is confirmed by an admin.<br> Please,
                    <a href='index.php?page=contact'>contact us</a> if you have any question.";
            }
        } else {
            $result['status'] = false;
            $result['msg'] = "Wrong username";
        }
        return $result;
    }

    /**
     * Set User's status (delete/activate/deactivate/permission level)
     * @param $newstatus
     * @return string
     */
    public function setStatus($newstatus) {
        $result['status'] = false; // Set default status as false
        if ($newstatus == 'delete') {
            if ($this -> delete_user()) {
                $result['status'] = true;
                $result['msg'] = "Account successfully deleted";
            }
        } elseif ($newstatus == "activate") {
            $result = $this -> activation(1);
        } elseif ($newstatus == "desactivate") {
            $result = $this -> activation(0);
        } else {
            if ($this -> change_user_status($newstatus))  {
                $result['status'] = true;
                $result['msg'] = "User status is now $newstatus!";
            }
        }
        return $result;
    }

    /**
     * Check number of unsuccessful login attempts.
     * Deactivate the user's account if this number exceeds the maximum
     * allowed number of attempts and send an email to the user with an activation link.
     * @return int
     */
    private function checkattempt() {
        $last_login = new DateTime($this->last_login);
        $now = new DateTime();
        $diff = $now->diff($last_login);
        // Reset the number of attempts if last login attempt was 1 hour ago
        $this->attempt = $diff->h >= 1 ? 0:$this->attempt;
        $this->attempt += 1;
        $AppConfig = new AppConfig($this->db);
        if ($this->attempt >= $AppConfig->max_nb_attempt) {
            self::activation(0); // We deactivate the user's account
            $this->send_activation_mail();
            return false;
        }
        $this->last_login = date('Y-m-d H:i:s');
        $this->db->updatecontent($this->tablename,array('attempt'=>$this->attempt,'last_login'=>$this->last_login),array("username"=>$this->username));
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
            $this->attempt = 0;
            $this->last_login = date('Y-m-d H:i:s');
            $this->db->updatecontent($this->tablename,array('attempt'=>$this->attempt,'last_login'=>$this->last_login),array("username"=>$this->username));
            return true;
        } else {
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
    function delete_user($username=null) {
        $username = ($username == null) ? $this->username:$username;
        return $this->db -> deletecontent($this->tablename,array("username"),array("$username"));
    }

    /**
     * Change the user's status (admin/organizer/member)
     *
     * @param $newstatus
     * @return bool
     */
    function change_user_status($newstatus) {
        return $this->db->updatecontent($this->tablename,array('status'=>$newstatus),array("username"=>$this->username));
    }

    /**
     * Get user's list of publications to display it on his/her profile page
     *
     * @param null $filter
     * @return string
     */
    function getpublicationlist($filter = NULL) {

        $sql = "SELECT id_pres FROM ".$this->db->tablesname['Presentation']." WHERE username='$this->username' and date<CURDATE()";
        if (null != $filter) {
            $sql .= " AND YEAR(date)=$filter";
        }
        $sql .= " ORDER BY date";
        $req = $this->db->send_query($sql);

        $content = "";
        while ($row = mysqli_fetch_assoc($req)) {
            $pubid = $row['id_pres'];
            /** @var Presentation $pub */
            $pub = new Presentation($this->db,$pubid);
            $content .= $pub->show(true);
        }

        return         $content = "
            <div class='table_container' style='display: table; width: 100%;'>
                <div style='display: table-row; text-align: left; font-weight: 600; text-transform: uppercase; font-size: 0.9em;'>
                    <div style='width: 20%; display: table-cell;'>Date</div>
                    <div style='width: 75%; display: table-cell;'>Title</div>
                </div>
                {$content}
            </div>
        ";
    }

    /**
     * Gets user's assignments list
     * @param bool $show
     * @param null|string $username
     * @return string
     */
    public function getAssignments($show=true, $username=null) {
        $sql = "SELECT id_pres FROM ".$this->db->tablesname['Presentation']." WHERE username='{$this->username}' AND date>CURDATE()";
        $req = $this->db->send_query($sql);
        $content = "";
        while ($row = mysqli_fetch_assoc($req)) {
            $pubid = $row['id_pres'];
            $pub = new Presentation($this->db, $pubid);
            $content .= $pub->show($show, $username);
        }
        return "
            <div class='table_container' style='display: table; width: 100%;'>
                <div style='display: table-row; text-align: left; font-weight: 600; text-transform: uppercase; font-size: 0.9em;'>
                    <div style='width: 20%; display: table-cell;'>Date</div>
                    <div style='width: 75%; display: table-cell;'>Title</div>
                </div>
            {$content}
            </div>
        ";
    }
}
