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
class User extends AppTable {

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
        "password" => array("CHAR(255)", false),
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
        "primary" => "id"
    );

    /**
     * @var int
     */
    public $id;

    /** @var string  */
    public $date;

    /** @var null|string  */
    public $username;

    /** @var string  */
    public $password;

    /** @var string  */
    public $firstname;

    /** @var string  */
    public $lastname;

    /** @var string  */
    public $fullname;

    /** @var string  */
    public $position;

    /** @var string  */
    public $email;

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
     * User constructor.
     * @param null $username
     */
    function __construct($username=null) {
        parent::__construct('User', $this->table_data);
        $this->tablename = $this->db->tablesname["User"];
        $this->username = $username;
        if (!is_null($username)) {
            $this->getUser($username);
        }
    }

    // Controller
    /**
     * Create user
     *
     * @param array $post
     * @return bool|string
     */
    function make($post=array()) {
        $post = self::sanitize($post); // Escape $_POST content

        $this->date = date("Y-m-d H:i:s"); // Date of creation (today)
        $this->last_login = $this->date;

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
        if (!$this->is_exist(array('username'=>$post['username'], 'active'=>1)) && !$this->is_exist(array('email'=>$post['email']))) {
            $content = $this->parsenewdata($class_vars,$post); // Parse variables and values to store in the table
            $this->db->addcontent($this->tablename,$content); // Add to user table

            if ($this->status !=  "admin") {
                // Send verification email to admins/organizer
                $mail = new AppMail();
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
            AppLogger::get_instance(APP_NAME, get_class($this))->log($result);
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
    public function getUser($prov_username) {
        $data = $this->get(array('username'=>$prov_username));
        if (!empty($data)) {
            $this->map($data[0]);
            $this->firstname = ucfirst(strtolower($this->firstname));
            $this->lastname = ucfirst(strtolower($this->lastname));
            $this->fullname = $this->firstname." ".$this->lastname;
            $this->nbpres = self::get_nbPres($prov_username);
            $this->update(array('nbpres'=>$this->nbpres), array('username'=>$prov_username));
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get organizers list
     * @param bool $admin
     * @return array
     */
    public function getAdmin($admin=false) {
        if ($admin) {
            $search = array('admin', 'organizer');
        } else {
            $search = 'organizer';
        }
        return $this->get(array('status'=>$search));
    }

    /**
     * Get user list
     * @param bool $assign
     * @return array
     */
    public function getAll($assign = false) {
        $search = array('active'=>1, 'status !='=>'admin');
        if ($assign) $search['assign'] = 1;
        return $this->get($search);
    }

    /**
     * Get all activated members except admins
     * @return array
     */
    public function all_but_admin() {
        return $this->all(
            array(
                'active'=>1,
                'status !='=>'admin'),
            array('dir'=>'DESC', 'filter'=>'fullname')
        );
    }

    /**
     * Generate and show list of users
     * @param string $filter
     * @return string
     */
    public function generateuserslist($filter='lastname') {
        return self::users_list($this->all(array(), array('dir'=>'ASC', 'filter'=>$filter)));
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
    public function check_account_activation($hash, $email, $activ) {
        $username = $this->db->select($this->tablename, array('username'), array("email"=>$email));
        $this->getUser($username[0]['username']);
        if ($activ === "true") {
            if ($this->active == 0) {
                if ($this->hash === $hash) {
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
            $result['status'] = false;
            $result['msg'] = "Permission denied by the admin. Account successfully deleted.";
        }
        AppLogger::get_instance(APP_NAME, get_class($this))->log($result);
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
                $result['msg'] = "We could not deactivate this account";
            }
        }
        AppLogger::get_instance(APP_NAME, get_class($this))->log($result);
        return $result;
    }

    /**
     * Send a confirmation email to the new user once his/her registration has been validated by an organizer
     * @return bool
     */
    private function send_confirmation_mail() {
        $config = AppConfig::getInstance();
        $AppMail = new AppMail();
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
        $AppMail = new AppMail();

        $subject = 'Your account has been deactivated'; // Give the email a subject
        $authorize_url = AppConfig::getInstance()->getAppUrl()."index.php?page=verify&email=$this->email&hash=$this->hash&result=true";
        $newpwurl = AppConfig::getInstance()->getAppUrl()."index.php?page=renew_pwd&hash=$this->hash&email=$this->email";
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
     * @param bool $login: log user in
     * @return mixed
     */
    public function login($post, $login=true) {
        $password = htmlspecialchars($post['password']);
        if ($this->getUser($this->username)) {
            if ($this->active == 1) {
                if ($this -> check_pwd($password) == true) {
                    if ($login) {
                        $_SESSION['logok'] = true;
                        $_SESSION['login_start'] = time();
                        $_SESSION['login_expire'] = $_SESSION['login_start'] + SessionInstance::timeout;
                        $_SESSION['login_warning'] = SessionInstance::warning;
                        $_SESSION['username'] = $this -> username;
                        $_SESSION['status'] = $this -> status;
                    }
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
                    <a href='" . URL_TO_APP . "index.php?page=contact'>contact us</a> if you have any question.";
            }
        } else {
            $result['status'] = false;
            $result['msg'] = "Wrong username";
        }
        return $result;
    }

    /**
     * Procedure for password modification request
     * @param $email
     * @return array: array('status'=>bool, 'msg"=>string)
     */
    public function request_password_change($email) {
        if ($this->is_exist(array('email'=>$email))) {
            $username = AppDb::getInstance()->select(AppDb::getInstance()->tablesname['User'], array('username'),
                array("email"=>$email));
            $this->getUser($username[0]['username']);
            $reset_url = URL_TO_APP . "index.php?page=renew&hash=$this->hash&email=$this->email";
            $subject = "Change password";
            $content = "
            Hello $this->firstname $this->lastname,<br>
            <p>You requested us to change your password.</p>
            <p>To reset your password, click on this link:
            <br><a href='$reset_url'>$reset_url</a></p>
            <br>
            <p>If you did not request this change, please ignore this email.</p>
            ";

            $AppMail = new AppMail();
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
        return $result;
    }

    /**
     * Get password modification form
     * @return string
     */
    public function get_password_form() {
        // Modify user password
        if (!empty($_POST['hash']) && !empty($_POST['email'])) {
            $hash = htmlspecialchars($_POST['hash']);
            $email = htmlspecialchars($_POST['email']);
            $data = $this->get(array('email'=>$email));
            if ($data[0]['hash'] === $hash) {
                $result = self::password_form($data);
            } else {
                $result = self::incorrect_hash();
            }
        } else {
            $result = self::incorrect_hash();
        }
        return $result;
    }

    /**
     * Modify user's password
     * @param $username
     * @param $password
     * @return mixed
     */
    public function password_change($username, $password) {
        if ($this->is_exist(array('username'=>$username))) {
            if ($this->update(array('password' => $this->crypt_pwd($password)), array('username' => $username))) {
                $result['msg'] = "Your password has been changed!";
                $result['status'] = true;
            } else {
                $result['status'] = false;
            }
        } else {
            $result['status'] = False;
            $result['msg'] = 'This account does not exist';
        }
        return $result;
    }

    /**
     * Check if user is logged in
     * @return bool
     */
    public static function is_logged() {
        return SessionInstance::is_started() && isset($_SESSION['logok']) && $_SESSION['logok'] == true;
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
        AppLogger::get_instance(APP_NAME, get_class($this))->log($result);
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
        if ($this->attempt >= AppConfig::getInstance()->max_nb_attempt) {
            self::activation(0); // We deactivate the user's account
            $this->send_activation_mail();
            return false;
        }
        $this->last_login = date('Y-m-d H:i:s');
        $this->db->updatecontent($this->tablename,array('attempt'=>$this->attempt,'last_login'=>$this->last_login),array("username"=>$this->username));
        return AppConfig::getInstance()->max_nb_attempt - $this->attempt;
    }

    /**
     * Check if the provided password is correct (TRUE) or not (FALSE)
     *
     * @param $password
     * @return bool
     */
    private function check_pwd($password) {
        $truepwd = $this->db->select($this->tablename, array("password"), array("username"=>$this->username));
        $check = validate_password($password, $truepwd[0]['password']);
        if ($check == 1) {
            $this->attempt = 0;
            $this->last_login = date('Y-m-d H:i:s');
            $this->db->updatecontent($this->tablename,
                array('attempt'=>$this->attempt,'last_login'=>$this->last_login),
                array("username"=>$this->username));
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
    public function crypt_pwd($password) {
        $hash = create_hash($password);
        return $hash;
    }

    /**
     * Delete user's account
     *
     * @param $current_user: logged user
     * @param null $username: provided username
     * @return array
     */
    public function delete_user($username, $current_user=null) {
        // check if user has admin status and if there will be remaining admins after we delete this account.
        $is_admin = false;
        $admins = $this->all(array('status'=>'admin'));
        foreach ($admins as $key=>$admin) {
            if ($admin['username'] == $username) {
                $is_admin = true;
                break;
            }
        }
        if (is_null($current_user) || (!is_null($current_user) && $current_user === $username)) {
            if ($is_admin and count($admins) === 1) {
                return array(
                    'status'=>false,
                    'msg'=>'This account has an admin status and there must be at least one admin account registered'
                );
            }
            return array('status'=>$this->delete(array("username"=>$username)));
        } else {
            return array('status'=>false, 'msg'=>"You cannot delete another user's account");
        }
    }

    /**
     * Change the user's status (admin/organizer/member)
     *
     * @param $newstatus
     * @return bool
     */
    public function change_user_status($newstatus) {
        return $this->db->updatecontent($this->tablename,array('status'=>$newstatus),array("username"=>$this->username));
    }

    /**
     * Get user's list of publications to display it on his/her profile page
     *
     * @return string
     */
    public function getPublicationList() {
        $pub = new Presentation();
        return self::user_assignments($pub->getUserPresentations($this->username, 'previous'));
    }

    /**
     * Gets user's assignments list
     * @return string
     */
    public function getAssignments() {
        $pub = new Presentation();
        return self::user_assignments($pub->getUserPresentations($this->username, 'next'));
    }

    /**
     * Get list of bookmarks
     */
    public function getBookmarks() {
        $Bookmark = new Bookmark();
        return $Bookmark->getList($_SESSION['username']);
    }

    /**
     * View getter
     * @param string $view: requested view name
     * @param string $destination: view's destination (body or modal)
     * @return string|array
     */
    public function get_view($view, $destination='body') {
        $method_name = $view . '_' . $destination;
        return self::$method_name();
    }

    /**
     * @param $id
     * @return array|null
     */
    public function getById($id) {
        return $this->get(array('id'=>$id));
    }

    /**
     * Get the number of presentations submitted by the user
     * @param string $username: user name
     * @return int
     */
    private static function get_nbPres($username) {
        $pub = new Presentation();
        return count($pub->all(array('orator'=>$username, 'type !='=>'wishlist')));
    }

    // View
    /**
     * Render users list
     * @param array $data
     * @return string
     */
    private static function users_list(array $data=array()) {
        $content = null;
        foreach ($data as $key=>$item) {
            $content .= self::user_in_list($item);
        }
        return "
            <div class='list-container list-heading'>
                <div class='user_select user_firstname' data-filter='firstname'>First Name</div>
                <div class='user_select user_lastname' data-filter='lastname'>Last Name</div>
                <div class='user_select user_email' data-filter='email'>Email</div>
                <div class='user_select user_email' data-filter='email'>Position</div>
                <div class='user_select user_small' data-filter='active'>Activated</div>
                <div class='user_select user_op' data-filter='status'>Status</div>
            </div>
            {$content}
        ";
    }

    /**
     * Render user information in users list
     * @param array $item
     * @return string
     */
    private static function user_in_list(array $item) {
        // Compute age
        if ($item['active'] === 1) {
            $to   = new DateTime(date('Y-m-d'));
            $from = new DateTime(date("Y-m-d",strtotime($item['date'])));
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

        return "
            <div class='list-container' id='section_{$item['username']}->username'>
                <div class='user_firstname'>{$item['firstname']}</div>
                <div class='user_lastname'>{$item['lastname']}</div>
                <div class='user_email'>{$item['email']}</div>
                <div class='user_position'>{$item['position']}</div>
                <div class='user_small'>$cur_trage</div>
                <div class='user_op'>
                    <select name='status' class='user_status modify_status' data-user='{$item['username']}' style='max-width: 75%;'>
                        <option value='{$item['status']}' selected='selected'>{$item['status']}</option>
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

    /**
     * Render login form
     * @return string|array
     */
    public static function login_form_body() {
        return "
            <form id='login_form' method='post' action='" . URL_TO_APP . 'php/form.php' . "'>
                <input type='hidden' name='login' value='true'/>
                <div class='form-group' style='width: 100%;'>
                    <input type='text' name='username' required autocomplete='on'>
                    <label for='username'>Username</label>
                </div>
                <div class='form-group' style='width: 100%;'>
                    <input type='password' name='password' required>
                    <label for='password'>Password</label>
                </div>
                <div class='action_btns'>
                    <div class='first_half'>
                        <input type='submit' id='login_form' value='Log In' class='processform login'/>
                    </div>
                    <div class='last_half' style='text-align: right;'>
                        <input type='button' class='go_to_section' data-controller='User' data-action='get_view' 
                        data-params='registration_form,modal' data-section='registration_form' value='Sign Up'>
                    </div>
                </div>
            </form>
            <div class='forgot_password'><a href='' class='go_to_section' data-controller='User' data-action='get_view' 
            data-params='change_password_form,modal' data-section='change_password_form'>I forgot my password</a></div>
        ";
    }

    /**
     * Render login form for modal windows
     * @return array
     */
    public static function login_form_modal() {
        return array(
            'id'=>'login_form',
            'content'=>self::login_form_body(),
            'title'=>'Login',
            'buttons'=>null
        );
    }

    /**
     * Render signup form
     * @return string
     */
    public static function registration_form_body() {
        return  "
            <form id='register_form' method='post' action='" . URL_TO_APP . 'php/form.php' . "'>
                <input type='hidden' name='register' value='true'>
                <input type='hidden' name='status' value='member'>
                <div class='form-group' style='width: 100%;'>
                    <input type='text' name='firstname' required autocomplete='on'>
                    <label for='firstname'>First Name</label>
                </div>
                <div class='form-group' style='width: 100%;'>
                    <input type='text' name='lastname' required autocomplete='on'>
                    <label for='lastname'>Last Name</label>
                </div>
                <div class='form-group' style='width: 100%;'>
                    <input type='text' name='username' required autocomplete='on'>
                    <label for='username'>Username</label>
                </div>
                <div class='form-group' style='width: 100%;'>
                    <input type='password' name='password' class='passwordChecker' required>
                    <label for='password'>Password</label>
                </div>
                <div class='form-group' style='width: 100%;'>
                    <input type='password' name='conf_password' required>
                    <label for='conf_password'>Confirm password</label>
                </div>
                <div class='form-group' style='width: 100%;'>
                    <input type='email' name='email' required autocomplete='on'>
                    <label for='email'>Email</label>
                </div>
                <div class='form-group' style='width: 100%;'>
                    <select name='position' id='position' required>
                        <option value='' selected disabled></option>
                        <option value='researcher'>Researcher</option>
                        <option value='post-doc'>Post-doc</option>
                        <option value='phdstudent'>PhD student</option>
                        <option value='master'>Master student</option>
                    </select>   
                    <label>Position</label>    
                </div>
                <div class='action_btns'>
                    <div class='first_half'><input type='button' class='go_to_section' data-controller='User' data-action='get_view' 
                        data-params='login_form,modal' data-section='login_form' value='Log in'></div>
                    <div class='last_half'><input type='submit' class='register processform' value='Sign up'></div>
                </div>
            </form>
        ";
    }

    /**
     * Render registration form for modal windows
     * @return array
     */
    public static function registration_form_modal() {
        return array(
            'id'=>'registration_form',
            'content'=>self::registration_form_body(),
            'title'=>'Sign up',
            'buttons'=>null
        );
    }

    /**
     * Render dialog window to delete account
     * @return string
     */
    public static function delete_account_form_body() {
        return "
            <div>Please, confirm your identity.</div>
            <form id='confirmdeleteuser' method='post' action='" . URL_TO_APP . 'php/form.php' . "' autocomplete='off'>
                <div><input type='hidden' name='delete_user' value='true'></div>
                <div class='form-group'>
                    <input type='text' id='del_username' name='username' value='' required autocomplete='off'/>
                    <label for='del_username'>Username</label>
                </div>
                <div class='form-group'>
                    <input type='password' id='del_password' name='password' value='' required autocomplete='off'/>
                    <label for='del_password'>Password</label>
                </div>
                <div class='action_btns'>
                    <input type='submit' class='confirmdeleteuser' value='Delete my account'>
                </div>
            </form>
        ";
    }

    /**
     * Render registration form for modal windows
     * @return array
     */
    public static function delete_account_form_modal() {
        return array(
            'id'=>'user_delete',
            'content'=>"
                <div>Please, confirm your identity.</div>
                <form id='confirmdeleteuser' method='post' action='" . URL_TO_APP . 'php/form.php' . "' autocomplete='off'>
                    <div><input type='hidden' name='delete_user' value='true'></div>
                    <div class='form-group'>
                        <input type='text' id='del_username' name='username' value='' required autocomplete='off'/>
                        <label for='del_username'>Username</label>
                    </div>
                    <div class='form-group'>
                        <input type='password' id='del_password' name='password' value='' required autocomplete='off'/>
                        <label for='del_password'>Password</label>
                    </div>
                    <div class='action_btns'>
                        <input type='submit' class='confirmdeleteuser' value='Delete my account'>
                    </div>
                </form>
            ",
            'title'=>'Delete account',
            'buttons'=>"Delete my account"
        );
    }

    /**
     * Render change password form
     * @return string
     */
    public static function change_password_form_body() {
        return "
        <!-- Change password section -->
        <div class='page_description'>We will send an email to the provided address with further instructions in order to change your password.</div>
        <form id='modal_change_pwd' method='post' action='" . URL_TO_APP . 'php/form.php' . "'>
            <input type='hidden' name='change_pw' value='true'>
            <div class='form-group'>
                <input type='email' name='email' value='' required/>
                <label for='email'>Email</label>
            </div>
            <div class='action_btns'>
                <div class='first_half'><a href='' class='btn back_btn'><i class='fa fa-angle-double-left'></i> Back</a></div>
                <div class='last_half'><input type='submit' class='processform' value='Send'></div>
            </div>
        </form>
        ";
    }

    /**
     * Render password modification form for modal windows
     * @return array
     */
    public static function change_password_form_modal() {
        return array(
            'id'=>'change_password_form',
            'content'=>self::change_password_form_body(),
            'title'=>'Sign up',
            'buttons'=>null
        );
    }

    /**
     * Render list of upcoming presentations
     * @param $content
     * @return string
     */
    private static function user_assignments($content) {
        if (empty($content)) return "You don't have any upcoming presentations.";
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

    /**
     * Password modification form
     * @param array $data
     * @return string
     */
    private static function password_form(array $data) {
        return "
            <section>
                <div class='section_content'>
                    <h2>Change password</h2>
                    <form action='' method='post'>
                        <input type='hidden' name='password_change' value='true'/>
                        <input type='hidden' name='username' value='{$data[0]['username']}' id='ch_username'/>
                        <div class='form-group'>
                            <input type='password' name='password' class='passwordChecker' value='' required/>
                            <label for='password'>New Password</label>
                        </div>
                        <div class='form-group'>
                            <input type='password' name='conf_password' value='' required/></br>
                            <label for='conf_password'>Confirm password</label>
                        </div>
                        <div class='submit_btns'>
                            <input type='submit' name='login' value='Submit' class='conf_changepw'/>
                        </div>
                    </form>
                </div>
            </section>  
            ";
    }

    /**
     * Error message if hash does not match user information
     * @return string
     */
    private static function incorrect_hash() {
        return "
            <section>
                <div class='section_content'>
                    <h2>Change password</h2>
                    <div class='sys_msg warning'>Incorrect email or hash id.</div>
                </div>
            </section>";
    }
}
