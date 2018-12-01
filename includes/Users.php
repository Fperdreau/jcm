<?php
/**
 * File for class Users
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

namespace includes;

use includes\BaseModel;
use includes\MailManager;

/**
 * Class Users
 *
 * Handle user-related methods (generate users list/ get organizers list)
 */
class Users extends BaseModel
{

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
     * Users constructor.
     * @param null $username
     */
    public function __construct($username = null)
    {
        parent::__construct();
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
    public function make(array $post)
    {
        $post = self::sanitize($post); // Escape $_POST content

        $post['date'] = date("Y-m-d H:i:s"); // Date of creation (today)

        // Reformat first and last names
        if (!empty($post['firstname'])) {
            $post['firstname'] = ucfirst(strtolower($_POST['firstname']));
            $post['lastname'] = ucfirst(strtolower($_POST['lastname']));
            $post['fullname'] = $post['firstname']." ".$post['lastname'];
        }

        $post['hash'] = $this->createHash(); // Create an unique hash for this user
        $post['password']= Auth::cryptPwd($post['password']); // Encrypt password
        $post['active'] = ($post['status'] == "admin") ? 1 : 0; // Automatically activate the account if the user has an
        // admin level

        if (!$this->isExist(array('username'=>$post['username'], 'active'=>1))
        && !$this->isExist(array('email'=>$post['email']))) {
            // Add user information to Db
            if ($this->db->insert($this->tablename, $this->parseData($post))) {
                $result = $this->sendAccountCreationEmail($this->status, $post);
            } else {
                $result['status'] = false;
                $result['msg'] = "Oops, something went wrong";
            }

            Logger::getInstance(APP_NAME, get_class($this))->log($result);
        } else {
            $result['status'] = false;
            $result['msg'] = "This username/email address already exist in our database";
        }
        return $result;
    }

    /**
     * Send verification/confirmation email after account creation
     *
     * @param string $status: account status
     * @param array|null $post
     * @throws Exception
     * @return void
     */
    private function sendAccountCreationEmail($status, array $post = null)
    {
        try {
            if ($status !=  "admin") {
                // Send verification email to admins/organizer
                if ($this->sendVerificationMail($this->hash, $this->email, $this->fullname)) {
                    $result['status'] = true;
                    $result['msg'] = "Your account has been created. You will receive an email after
                    its validation by our admins.";
                } else {
                    $this->deleteUser($this->username);
                    $result['status'] = false;
                    $result['msg'] = "Sorry, we have not been able to send a verification email to the organizers.
                    Your registration cannot be validated for the moment. Please try again later.";
                }
            } else {
                // Send confirmation email to the user directly
                if ($this->sendConfirmationMail($post)) {
                    $result['status'] = true;
                    $result['msg'] = "Your account has been successfully created!";
                } else {
                    $result['status'] = true;
                    $result['msg'] = "Sorry, we have not been able to send a verification email to the organizers.
                    Your registration cannot be validated for the moment. Please try again later.";
                }
            }
        } catch (Exception $e) {
            Logger::getInstance('jcm')->error($e->getMessage());
            $this->deleteUser($this->username);
            $result['status'] = false;
            $result['msg'] = "Sorry, something went wrong and we were not able to create your account.
             Please try again later.";
        }
        return $result;
    }

    /**
     * Get user's information from the database
     *
     * @param $prov_username
     * @return bool
     */
    public function getUser($prov_username)
    {
        $data = $this->get(array('username'=>$prov_username));
        if (!empty($data)) {
            $this->map($data);
            $this->firstname = ucfirst(strtolower($this->firstname));
            $this->lastname = ucfirst(strtolower($this->lastname));
            $this->fullname = $this->firstname." ".$this->lastname;
            $this->nbpres = self::getNbPres($prov_username);
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
    public function getAdmin($admin = false)
    {
        $search = $admin ? array('admin', 'organizer') : 'organizer';
        return $this->all(array('status'=>$search));
    }

    /**
     * Get user list
     * @param bool $assign
     * @return array
     */
    public function getAll($assign = false)
    {
        $search = array('active'=>1, 'status !='=>'admin');
        if ($assign) {
            $search['assign'] = 1;
        }
        return $this->all($search);
    }

    /**
     * Get all activated members except admins
     * @return array
     */
    public function allButAdmin()
    {
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
    public function generateuserslist($filter = 'lastname')
    {
        return self::usersList($this->all(array(), array('dir'=>'ASC', 'order'=>$filter)));
    }

    /**
     * Creates an unique hash for the user (used in emails),
     * i.e. Generate random 32 character hash and assign it to a local variable
     *
     * @return string
     */
    private function createHash()
    {
        $hash = md5(rand(0, 1000));
        return $hash;
    }

    /**
     * Activate the user's account
     *
     * @param $hash
     * @param $email
     * @param $activate
     * @return array
     */
    public function validateAccount($hash, $email, $activate)
    {
        // Get user data from DB
        $data = $this->get(array('email'=>$email));

        if (is_null($data)) {
            return array('status'=>false, 'msg'=>"Sorry, this account does not exist");
        }

        // Activate or delete account based on admin decision
        if ($activate === "true") {
            if ($data['active'] == 0) {
                if ($data['hash'] === $hash) {
                    $result = $this->activate($data);
                } else {
                    $result['status'] = false;
                    $result['msg'] = "Wrong hash code for user.";
                }
            } else {
                $result['status'] = false;
                $result['msg'] = "This account has already been activated.";
            }
        } else {
            $result = $this->deleteUser($data['username']);
            $result['msg'] = "Permission denied by the admin. Account successfully deleted.";
        }
        Logger::getInstance(APP_NAME, get_class($this))->log($result);
        return $result;
    }

    /**
     * Activate account and notify user of the activation
     * @param array $data: array('username'=>string)
     * @return mixed
     */
    public function activate(array $data)
    {
        $data = $this->get(array('username'=>$data['username']));
        if ($this->db->update($this->tablename, array('active'=>1), array("username"=>$data['username']))) {
            if ($this->sendConfirmationMail($data)) {
                $result['status'] = true;
                $result['msg'] = "Account successfully activated. An email has been sent to the user!";
            } else {
                $result['status'] = false;
                $result['msg'] = "Account successfully activated, but we could not send a confirmation email to
                        the user.";
            }
        } else {
            $result['status'] = false;
            $result['msg'] = "Oops, something went wrong";
        }

        Logger::getInstance(APP_NAME, get_class($this))->log($result['msg']);

        return $result;
    }

    /**
     * Activate account and notify user of the activation
     * @param array $data: array('username'=>string)
     * @return mixed
     */
    public function deactivate(array $data)
    {
        $data = $this->get(array('username'=>$data['username']));
        if ($this->db->update($this->tablename, array('active'=>0), array("username"=>$data['username']))) {
            if ($this->sendActivationMail($data)) {
                $result['status'] = true;
                $result['msg'] = "Account successfully deactivated. An email has been sent to the user!";
            } else {
                $result['status'] = false;
                $result['msg'] = "Account successfully deactivated, but we could not send a notification email to
                        the user.";
            }
        } else {
            $result['status'] = false;
            $result['msg'] = "Oops, something went wrong";
        }

        Logger::getInstance(APP_NAME, get_class($this))->log($result['msg']);

        return $result;
    }

    /**
     * Activate or deactivate the user's account
     *
     * @param null $username
     * @param bool $option : activate (true) or deactivate account (false)
     * @return array
     */
    public function activation(array $post)
    {
        $username = $post['username'];
        $option = $post['action'];

        $data = $this->get(array('username'=>$username));
        if ($option === true) {
            $result = $this->activate($data);
        } else {
            $result = $this->deactivate($data);
        }
        Logger::getInstance(APP_NAME, get_class($this))->log($result['msg']);
        return $result;
    }

    /**
     * Send a verification email to organizers when someone signed up to the application.
     * @param $hash
     * @param $user_mail
     * @param $username
     * @return bool
     * @throws Exception
     * @throws phpmailerException
     */
    public function sendVerificationMail($hash, $user_mail, $username)
    {
        $Users = new self();
        $admins = $Users->getadmin('admin');
        $to = array();
        foreach ($admins as $key => $admin) {
            $to[] = $admin['id'];
        }

        $content['subject'] = 'Sign up | Verification';
        $authorize_url = URL_TO_APP . "index.php?page=organizer/verify&email={$user_mail}&hash={$hash}&result=true";
        $deny_url = URL_TO_APP . "index.php?page=organizer/verify&email={$user_mail}&hash={$hash}&result=false";

        $content['body'] = "
        Hello,<br><br>
        <p><b>{$username}</b> wants to create an account.</p>
        <p><a href='{$authorize_url}'>Authorize</a></p>
        or
        <p><a href='{$deny_url}'>Deny</a></p>
        ";

        $mail = new MailManager();
        $content['emails'] = $to;
        return $mail->addToQueue($content);
    }

    /**
     * Send a confirmation email to the new user once his/her registration has been validated by an organizer
     * @param array $data: user data
     * @return bool
     */
    public function sendConfirmationMail(array $data)
    {
        $MailManager = new MailManager();
        $fullname = isset($data['fullname']) ? $data['fullname'] : $data['username'];
        $body = self::confirmationMail($fullname, $data['username']);
        return $MailManager->addToQueue(array(
            'body'=>$body,
            'subject'=>'Sign up | Confirmation',
            'emails'=>$data['id']
        ));
    }

    /**
     * Send an email to the user if his/her account has been deactivated due to too many login attempts.
     * @param array $data: user data
     * @return bool
     */
    public function sendActivationMail(array $data)
    {
        $MailManager = new MailManager();
        $body = self::deactivationEmail($data['fullname'], $data['email'], $data['hash']);
        return $MailManager->addToQueue(array(
            'body'=>$body,
            'subject'=>'Your account has been deactivated',
            'emails'=>$data['id']
        ));
    }

    /**
     * Procedure for password modification request
     * @param $email
     * @return array: array('status'=>bool, 'msg"=>string)
     */
    public function requestPasswordChange($email)
    {
        if ($this->isExist(array('email'=>$email))) {
            $username = $this->db->single($this->tablename, array('username'), array("email"=>$email));
            $this->getUser($username['username']);

            $MailManager = new MailManager();
            $body = $MailManager->formatmail(
                self::passwordRequestEmail($this->fullname, $this->hash, $this->email)
            );
            $data = array('body'=>$body, 'subject'=>'Change password request', 'emails'=>$this->id);
            if ($MailManager->addToQueue($data)) {
                $result['msg'] = "An email has been sent to your address with further instructions";
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
    public function getPasswordForm($hash, $email, $view = 'body')
    {
        // Modify user password
        if (!empty($hash) && !empty($email)) {
            $hash = htmlspecialchars($hash);
            $email = htmlspecialchars($email);
            $data = $this->get(array('email'=>$email));
            if ($data) {
                if ($hash === $hash) {
                    $result = self::passwordForm($data, $view);
                } else {
                    $result = self::incorrectHash();
                }
            } else {
                $result = self::incorrectHash();
            }
        } else {
            $result = self::incorrectHash();
        }
        return $result;
    }

    /**
     * Modify user's password
     * @param $username
     * @param $password
     * @return mixed
     */
    public function passwordChange($username, $password)
    {
        if ($this->isExist(array('username'=>$username))) {
            if ($this->update(array('password' => Auth::cryptPwd($password)), array('username' => $username))) {
                $result['msg'] = "Your password has been changed!";
                $result['status'] = true;
            } else {
                $result['status'] = false;
            }
        } else {
            $result['status'] = false;
            $result['msg'] = 'This account does not exist';
        }
        return $result;
    }

    /**
     * Test is user has admin status
     * @param string $username: user's name
     * @return bool
     */
    public function isAdmin($username)
    {
        foreach ($this->all(array('status'=>'admin')) as $key => $admin) {
            if ($admin['username'] == $username) {
                return true;
            }
        }
        return false;
    }

    /**
     * Delete user's account
     *
     * @param $current_user: logged user
     * @param null $username: provided username
     * @return array
     */
    public function deleteUser($username = null, $current_user = null)
    {
        if (is_null($current_user) && isset($_SESSION['username'])) {
            $current_user = $_SESSION['username'];
        }

        // check if user has admin status and if there will be remaining admins after we delete this account.
        $isAdmin = false;
        $data = $this->get(array('username'=>$username));
        $isAdmin = $data['status'] == 'admin';
        $admins = $this->all(array('status'=>'admin'));

        // Check if current user has necessary credentials
        $is_authorized = !is_null($current_user) && Account::isAuthorized($current_user, 'organizer');

        if (!is_null($current_user) && ($current_user === $username || $is_authorized)) {
            if ($isAdmin and count($admins) === 1) {
                return array(
                    'status'=>false,
                    'msg'=>'This account has an admin status and there must be at least one admin account registered'
                );
            } else {
                $result['status'] = $this->delete(array("username"=>$username));
                $result['msg'] = $result['status'] ? 'Account successfully deleted' : 'Oops, something went wrong';
                return $result;
            }
        } else {
            return array('status'=>false, 'msg'=>"You cannot delete another user's account");
        }
    }

    /**
     * Change the user's status (admin/organizer/member)
     *
     * @param array $data: array('username'=>string, 'status'=>string)
     * @return array
     */
    public function setStatus(array $data)
    {
        // check if user has admin status and if there will be remaining admins after we delete this account.
        $isAdmin = false;
        $admins = $this->all(array('status'=>'admin'));
        foreach ($admins as $key => $admin) {
            if ($admin['username'] == $data['username']) {
                $isAdmin = true;
                break;
            }
        }

        // If target account has admin status, check if there is at least one admin account
        // remaining after status modification
        if ($isAdmin and count($admins) <= 1) {
            return array(
                'status'=>false,
                'msg'=>'This account has an admin status and there must be at least one admin account registered'
            );
        } else {
            $result['status'] = $this->db->update(
                $this->tablename,
                array('status'=>$data['status']),
                array("username"=>$data['username'])
            );
            if ($result['status']) {
                $result['msg'] = "{$data['username']} status is now {$data['status']}!";
            } else {
                $result['msg'] = "Oops, something went wrong";
            }
        }

        Logger::getInstance(APP_NAME, get_class($this))->log($result);
        $result['content'] = $this->generateuserslist();

        return $result;
    }

    /**
     * Change the user's assignment status (Yes/No)
     *
     * @param array $data: array('username'=>string, 'assign'=>int)
     * @return array
     */
    public function setAssign(array $data)
    {
        $result['status'] = $this->db->update(
            $this->tablename,
            array('assign'=>$data['assign']),
            array("username"=>$data['username'])
        );

        $user = $this->get(array('username'=>$data['username']));
        if ($result['status']) {
            if ($data['assign'] == 1) {
                $result['msg'] = "{$user['fullname']} can be assigned";
            } else {
                $result['msg'] = "{$user['fullname']} cannot be assigned anymore";
            }
        } else {
            $result['msg'] = "Oops, something went wrong";
        }
        
        Logger::getInstance(APP_NAME, get_class($this))->log($result);
        $result['content'] = $this->generateuserslist();

        return $result;
    }

    /**
     * Get user's list of publications to display it on his/her profile page
     *
     * @return string
     */
    public function getPublicationList()
    {
        $pub = new Presentation();
        return self::userAssignments($pub->getUserPresentations($this->username, 'previous'));
    }

    /**
     * Gets user's assignments list
     * @return string
     */
    public function getAssignments()
    {
        $pub = new Presentation();
        return self::userAssignments($pub->getUserPresentations($this->username, 'next'));
    }

    /**
     * Get list of bookmarks
     */
    public function getBookmarks()
    {
        $Bookmark = new Bookmark();
        return $Bookmark->getList($_SESSION['username']);
    }

    /**
     * Getter for login/registration forms
     *
     * @param string $type: form type (login, registration)
     * @param string $view: view type (modal, body)
     * @param string $section: destination DOM element id
     *
     * @return string
     */
    public function getForm($type, $view = 'body', $section = null)
    {
        $methodName = $type . '_' . $view;
        if (method_exists($this, $methodName)) {
            return self::$methodName();
        } else {
            return Page::notFound();
        }
    }

    /**
     * @param $id
     * @return array|null
     */
    public function getById($id)
    {
        return $this->get(array('id'=>$id));
    }

    /**
     * Get the number of presentations submitted by the user
     * @param string $username: user name
     * @return int
     */
    private static function getNbPres($username)
    {
        $pub = new Presentation();
        return count($pub->all(array('orator'=>$username, 'type !='=>'wishlist')));
    }

    // View
    /**
     * Render users list
     * @param array $data
     * @return string
     */
    private static function usersList(array $data = array())
    {
        $content = null;
        foreach ($data as $key => $item) {
            $content .= self::userInList($item);
        }
        return "
            <div class='list-container list-heading'>
                <div class='user_select user_firstname' data-filter='firstname'>First Name</div>
                <div class='user_select user_lastname' data-filter='lastname'>Last Name</div>
                <div class='user_select user_email' data-filter='email'>Email</div>
                <div class='user_select user_position' data-filter='position'>Position</div>
                <div class='user_select user_small' data-filter='active'>Activated</div>
                <div class='user_select user_status' data-filter='status'>Status</div>
                <div class='user_select user_small' data-filter='assign'>Assignable</div>
                <div class='user_op' data-filter='action'>Action</div>
            </div>
            {$content}
        ";
    }

    /**
     * Display time passed since account activation
     *
     * @param array $item
     * @return string
     */
    private static function ageOfSubscription(array $item)
    {
        if ($item['active'] == 1) {
            $to   = new \DateTime(date('Y-m-d'));
            $from = new \DateTime(date("Y-m-d", strtotime($item['date'])));
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
        } else {
            $cur_trage = 'Not activated';
        }
        return $cur_trage;
    }

    /**
     * Display list of actions
     *
     * @param array $item
     * @return string: select input
     */
    private static function actionsList(array $item)
    {
        // Account activation/deactivation option
        $activOption = $item['active'] == 1 ? "<option value='deactivate'>Deactivate</option>"
        : "<option value='activate'>Activate</option>";
        
        return "
        <form action='php/router.php?controller=Users&action=activation' method='post'>
            <input type='hidden' name='username' value='{$item['username']}' />
            <select name='action' class='actionOnSelect' 
            data-destination='#user_list' style='max-width: 75%;'>
                <option selected disabled>Select an action</option>
                {$activOption}
                <option value='deleteUser' style='background-color: rgba(207, 81, 81, 1); 
                color: white;'>Delete</option>
            </select>
        </form>
        ";
    }

    /**
     * Display list of account status
     *
     * @param array $item
     * @return string: select input
     */
    private static function statusList(array $item)
    {
        // Generate actions list
        $statusList = '';
        $status = ['member', 'admin', 'organizer'];
        foreach ($status as $value) {
            $selected = $value == $item['status'] ? 'selected' : null;
            $statusList .= "<option value='{$value}' {$selected}> ". ucfirst($value). "</option>";
        }
        
        $url = Router::buildUrl('Users', 'setStatus');
        return "
        <form action='{$url}' method='post'>
            <input type='hidden' name='username' value='{$item['username']}' />
            <select name='status' class='actionOnSelect' data-destination='#user_list' style='max-width: 75%;'>
                {$statusList}
            </select>
        </form>
        ";
    }

    /**
     * Display list of account status
     *
     * @param array $item: info
     * @param string $variable: variable name
     * @return string: select input
     */
    private static function assignList(array $item)
    {
        // Generate actions list
        $list = '';
        $status = [1, 0];
        foreach ($status as $value) {
            $selected = $value == $item['assign'] ? 'selected' : null;
            $str = $value == 1 ? 'Yes': 'No';
            $list .= "<option value='{$value}' {$selected}> ". ucfirst($str). "</option>";
        }
        
        $url = Router::buildUrl('Users', 'setAssign');
        return "
        <form action='{$url}' method='post'>
            <input type='hidden' name='username' value='{$item['username']}' />
            <select name='assign' class='actionOnSelect' data-destination='#user_list' style='max-width: 75%;'>
                {$list}
            </select>
        </form>
        ";
    }

    /**
     * Display user information in list
     *
     * @param array $item
     * @return string
     */
    private static function userInList(array $item)
    {
        return "
            <div class='list-container' id='section_{$item['username']}->username'>
                <div class='user_firstname'>{$item['firstname']}</div>
                <div class='user_lastname'>{$item['lastname']}</div>
                <div class='user_email'>{$item['email']}</div>
                <div class='user_position'>{$item['position']}</div>
                <div class='user_small'>" . self::ageOfSubscription($item) . "</div>
                <div class='user_status'>
                    " . self::statusList($item) . "
                </div>
                <div class='user_assign'>
                " . self::assignList($item) . "
                </div>
                <div class='user_action'>
                    " . self::actionsList($item) . "
                </div>
            </div>
            ";
    }

    /**
     * Render login form to be displayed in full page
     *
     * @return string|array
     */
    public static function loginFormBody()
    {
        $leanModalUrlPwd = Router::buildUrl(
            'Users',
            'getForm',
            array(
            'view'=>'modal',
            'type'=>'change_password_form')
        );
        $leanModalUrlReg = Router::buildUrl(
            'Users',
            'getForm',
            array(
            'view'=>'modal',
            'type'=>'registration_form')
        );
        return "
            <form id='login_form' method='post' action='"
            . URL_TO_APP . 'php/router.php?controller=Auth&action=login' . "'>
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
                        <input type='submit' value='Log In' class='processform reload'/>
                    </div>
                    <div class='last_half' style='text-align: right;'>
                        <input type='button' class='go_to_section' data-url='{$leanModalUrlReg}' 
                        data-section='registration_form' value='Sign Up'>
                    </div>
                </div>
            </form>
            <div class='forgot_password'><a href='' class='go_to_section' data-url='{$leanModalUrlPwd}' 
            data-section='change_password_form'>I forgot my password</a></div>
        ";
    }

    /**
     * Render login form for modal windows
     *
     * @return array
     */
    public static function loginFormModal()
    {
        return array(
            'id'=>'login_form',
            'content'=>self::loginFormBody(),
            'title'=>'Login',
            'buttons'=>null
        );
    }

    /**
     * Render signup form
     * @return string
     */
    public static function registrationFormBody()
    {
        $leanModalUrlLogin = Router::buildUrl(
            'Users',
            'getForm',
            array(
            'view'=>'modal',
            'type'=>'login_form')
        );
        return  "
            <form method='post' action='" . URL_TO_APP . 'php/router.php?controller=Users&action=make' . "'>
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
                    <div class='first_half'><input type='button' class='go_to_section' data-url='{$leanModalUrlLogin}'
                     data-section='login_form' value='Log in'></div>
                    <div class='last_half'><input type='submit' class='processform' value='Sign up'></div>
                </div>
            </form>
        ";
    }

    /**
     * Render admin creation form (for installation)
     * @return string
     */
    public static function adminCreationForm()
    {
        return "
            <div class='feedback'></div>
			<form action='php/router.php?controller=Users&action=make' method='post'>
                <input type='hidden' name='status' value='admin'/>
                <input type='hidden' name='firstname' value='The'>
                <input type='hidden' name='lastname' value='Admin'>

			    <div class='form-group'>
				    <input type='text' name='username' required autocomplete='on'>
                    <label for='username'>UserName</label>
                </div>
                <div class='form-group'>
				    <input type='password' name='password' class='passwordChecker' required>
                    <label for='password'>Password</label>
                </div>
                <div class='form-group'>
				    <input type='password' name='conf_password' required>
                    <label for='conf_password'>Confirm password</label>
                </div>
                <div class='form-group'>
				    <input type='email' name='email' required autocomplete='on'>
                    <label for='admin_email'>Email</label>
                </div>
                <input type='hidden' name='status' value='admin'>
                <div class='submit_btns'>
                    <input type='submit' value='Next' class='process_form'>
                </div>
			</form>
		";
    }

    /**
     * Render registration form for modal windows
     * @return array
     */
    public static function registrationFormModal()
    {
        return array(
            'id'=>'registration_form',
            'content'=>self::registrationFormBody(),
            'title'=>'Sign up',
            'buttons'=>null
        );
    }

    /**
     * Render change password form
     * @return string
     */
    public static function changePasswordFormBody()
    {
        return "
        <!-- Change password section -->
        <div class='page_description'>We will send an email to the provided address with further instructions
         in order to change your password.</div>
        <form id='modal_change_pwd' method='post' 
        action='" . URL_TO_APP . "php/router.php?controller=Users&action=requestPasswordChange'>
            <div class='form-group'>
                <input type='email' name='email' value='' required/>
                <label for='email'>Email</label>
            </div>
            <div class='action_btns'>
                <div class='first_half'>
                    <a href='' class='btn back_btn'><i class='fa fa-angle-double-left'>
                    </i> Back</a>
                    </div>
                <div class='last_half'><input type='submit' class='processform' value='Send'></div>
            </div>
        </form>
        ";
    }

    /**
     * Render password modification form for modal windows
     * @return array
     */
    public static function changePasswordFormModal()
    {
        return array(
            'id'=>'change_password_form',
            'content'=>self::changePasswordFormBody(),
            'title'=>'Sign up',
            'buttons'=>null
        );
    }

    /**
     * Render list of upcoming presentations
     * @param $content
     * @return string
     */
    private static function userAssignments($content)
    {
        if (empty($content)) {
            return "You don't have any upcoming presentations.";
        }
        return "
            <div class='table_container' style='display: table; width: 100%;'>
                <div style='display: table-row; text-align: left; font-weight: 600; 
                text-transform: uppercase; font-size: 0.9em;'>
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
    private static function passwordForm(array $data, $view)
    {
        return "
            <section>
                <div class='section_content'>
                    <h2>Change password</h2>
                    <form action='php/router.php?controller=Users&action=passwordChange' method='post'>
                        <input type='hidden' name='password_change' value='true'/>
                        <input type='hidden' name='username' value='{$data['username']}' id='ch_username'/>
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
    private static function incorrectHash()
    {
        return "
            <section>
                <div class='section_content'>
                    <h2>Change password</h2>
                    <div class='sys_msg warning'>Incorrect email or hash id.</div>
                </div>
            </section>";
    }

    /**
     * Render account confirmation email
     * @param $fullname: user full name
     * @param $username: user username
     * @return string
     */
    private static function confirmationMail($fullname, $username)
    {
        $login_url = App::$site_url . "index.php";

        return "
        <div style='width: 100%; margin: auto;'>
            <p>Hello {$fullname},</p>
            <p>Thank you for signing up!</p>
        </div>
        <div style='display: block; padding: 10px; margin: 0 30px 20px 0; 
        border: 1px solid #ddd; background-color: rgba(255,255,255,1);'>
            Your account has been created, you can now <a href='{$login_url}'>log in</a> with the following credentials.
            <p><b>Username</b>: {$username}</p>
            <p></p><b>Password</b>: Only you know it!</p>
        </div>";
    }

    /**
     * Render activation request email
     * @param $fullname
     * @param $email
     * @param $hash
     * @return string
     */
    private static function deactivationEmail($fullname, $email, $hash)
    {
        $authorize_url = App::getAppUrl() . "index.php?page=organizer/verify&email={$email}&hash={$hash}&result=true";
        $newpwurl = App::getAppUrl() . "index.php?page=renew_pwd&hash={$hash}&email={$email}";

        return "
        <div style='width: 100%; margin: auto;'>
            <p>Hello {$fullname},</p>
            <p>We have the regret to inform you that your account has been 
            deactivated due to too many login attempts.</p>
        </div>
        <div style='display: block; padding: 10px; margin: 0 30px 20px 0; border: 1px solid #ddd; 
        background-color: rgba(255,255,255,1);'>
            <p>You can reactivate your account by following this link:</br>
                <a href='{$authorize_url}'>{$authorize_url}</a>
            </p>
            <p>If you forgot your password, you can ask for another one here:<br>
                <a href='{$newpwurl}'>{$newpwurl}</a>
            </p>
        </div>";
    }

    /**
     * Render change password request email
     * @param $full_name
     * @param $hash
     * @param $email
     * @return string
     */
    private static function passwordRequestEmail($full_name, $hash, $email)
    {
        $reset_url = URL_TO_APP . "index.php?page=renew&hash={$hash}&email={$email}";
        return "
            Hello {$full_name},<br>
            <p>You requested us to change your password.</p>
            <p>To reset your password, click on this link:
            <br><a href='{$reset_url}'>{$reset_url}</a></p>
            <br>
            <p>If you did not request this change, please ignore this email.</p>
            ";
    }
}
