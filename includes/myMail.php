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

// Import html2text class
require_once($_SESSION['path_to_app']."libs/html2text-0.2.2/html2text.php");

require_once($_SESSION['path_to_app'].'/libs/PHPMailer-master/class.phpmailer.php');
require_once($_SESSION['path_to_app'].'/libs/PHPMailer-master/class.smtp.php');

require_once($_SESSION['path_to_app'].'/includes/includes.php');

class myMail {
    public $mail_from = ""; // Sender's email
    public $mail_from_name = ""; // Sender's name
    public $mail_host = ""; // Email host (eg. gmail)
    public $mail_port = "25"; // Email port
    public $SMTPDebug = 0; // To show error messages
    public $mail_username = ""; // Email username
    public $mail_password = ""; // Email password
    public $SMTP_secure = "none"; // Email protocol (ssl)
    public $pre_header = "[Journal Club]"; // Email header
    public $site_url = ""; // Url path to the application

    function __construct() {
        self::get();
    }

    public function get() {
        require($_SESSION['path_to_app'].'config/config.php');
        $db_set = new DB_set();
        $sql = "select variable,value from $config_table";
        $req = $db_set->send_query($sql);
        $class_vars = get_class_vars("myMail");
        while ($row = mysqli_fetch_assoc($req)) {
            $varname = $row['variable'];
            $value = $row["value"];
            if (array_key_exists($varname,$class_vars)) {
                $this->$varname = $value;
            }
        }
        return true;
    }

    public function update($post) {
        require($_SESSION['path_to_app'].'config/config.php');
        $db_set = new DB_set();
        $bdd = $db_set->bdd_connect();
        $class_vars = get_class_vars("site_config");
        foreach ($class_vars as $name=>$value) {
            if (array_key_exists($name,$post)) {
                $value = mysqli_real_escape_string($bdd,$post["$name"]);
            } else {
                $value = mysqli_real_escape_string($bdd,$this->$name);
            }
            $exist = $db_set->getinfo($config_table,"variable",array("variable"),array("'$name'"));
            if (!empty($exist)) {
                $db_set->updatecontent($config_table,"value","'$value'",array("variable"),array("'$name'"));
            } else {
                $db_set->addcontent($config_table,"variable,value","'$name','$value'");
            }
        }

        self::get();
        return true;
    }

    function send_verification_mail($hash,$user_mail,$username) {
        $config = new site_config();
        $admins = $config->getadmin('admin');
        $to = array();
        for ($i=0; $i<count($admins); $i++) {
            $to[] = $admins[$i]['email'];
        }

        $subject = 'Signup | Verification'; // Give the email a subject
        $authorize_url = $this->site_url."index.php?page=verify&email=$user_mail&hash=$hash&result=true";
        $deny_url = $this->site_url."index.php?page=verify&email=$user_mail&hash=$hash&result=false";

        $content = "
        Hello,<br><br>
        <p><b>$username</b> wants to create an account.</p>
        <p><a href='$authorize_url'>Authorize</a></p>
        or
        <p><a href='$deny_url'>Deny</a></p>
        <p>The Journal Club Team</p>
        ";

        $body = $this -> formatmail($content);

        return $this->send_mail($to,$subject,$body);
    }

    function send_confirmation_mail($to,$username) {
        $user = new users();
        $user->get($username);

        $subject = 'Signup | Confirmation'; // Give the email a subject
        $login_url = $this->site_url."index.php";

        $content = "
        Hello $user->fullname,<br><br>
        Thank you for signing up!<br>
        <p>Your account has been created, you can now <a href='$login_url'>log in</a> with the following credentials.</p>
        <p>------------------------<br>
        <b>Username</b>: $username<br>
        <b>Password</b>: Only you know it!<br>
        ------------------------</p>
        <p>The Journal Club Team</p>
        ";
        $body = $this -> formatmail($content);
        return $this->send_mail($to,$subject,$body);
    }

    function get_mailinglist($type=null) {
        require($_SESSION['path_to_app'].'config/config.php');
        $db_set = new DB_set();
        $sql = "select username,email from $users_table where active=1";
        if (null!=$type) {
            $sql .= " and $type=1";
        }

        $req = $db_set->send_query($sql);
        $mailing_list = array();
        while ($data = mysqli_fetch_array($req)) {
            $cur_mail = $data['email'];
            $mailing_list[] = $cur_mail;
        }
        return $mailing_list;
    }

    function send_to_mailinglist($subject,$body,$type=null,$attachment = NULL) {
        $to = $this->get_mailinglist($type);
        if ($this->send_mail($to,$subject,$body,$attachment)) {
            return true;
        } else {
            return false;
        }
    }

    function send_mail($to,$subject,$body,$attachment = NULL) {
        require($_SESSION['path_to_app'].'config/config.php');

        $mail = new PHPMailer();

        $mail->IsSMTP();                                      // set mailer to use SMTP
        $mail->SMTPDebug  = $this->SMTPDebug;                     // enables SMTP debug information (for testing)

        $mail->Mailer = "smtp";
        $mail->Host = $this->mail_host;
        $mail->Port = $this->mail_port;

        if ($this->SMTP_secure != "none") {
            $mail->SMTPAuth = true;     // turn on SMTP authentication
            $mail->SMTPSecure = $this->SMTP_secure; // secure transfer enabled REQUIRED for GMail
            $mail->Username = $this->mail_username;
            $mail->Password = $this->mail_password;
        }

        $mail->From = $this->mail_from;
        $mail->FromName = $this->mail_from_name;

        $mail->AddAddress("undisclosed-recipients:;");
        $mail->AddReplyTo($this->mail_from, $this->mail_from_name);

        if (is_array($to)) {
            foreach($to as $to_add){
                $mail->AddBCC($to_add);                  // name is optional
            }
        } else {
            $mail->AddBCC($to);                  // name is optional
        }

        $mail->WordWrap = 50;                                 // set word wrap to 50 characters
        $mail->IsHTML(true);

        $mail->Subject = $this->pre_header." ".$subject;
        $mail->Body    = $body;
        $mail->AltBody= @convert_html_to_text($body); // Convert to plain text for email viewers non-compatible with HTML content

        if($attachment != null){
            if (!$mail->AddAttachment($attachment)) {
                return false;
            }
        }

        if ($rep = $mail->Send()) {
            $mail->ClearAddresses();
            $mail->ClearAttachments();
            return true;
        } else {
            return false;
        }

    }

    // Make notification email (weekly digest inludind last news, information about the upcoming session, about future sessions, and the wish list)
    function advertise_mail() {
        require($_SESSION['path_to_app'].'config/config.php');

        $db_set = new DB_set();
        $db_set->bdd_connect();
        $config = new site_config('get');

        // Get recent news
        $last_news = new Posts();
        $last_news->getlastnews();
        $today = date('Y-m-d');
        if ( date('Y-m-d',strtotime($last_news->date)) < date('Y-m-d',strtotime("$today - 7 days"))) {
            $last_news->content = "No recent news this week";
        }

        // Get future presentations
        $future_session = new Press();
        $pres_list = $future_session->get_futuresession(4,'mail');

        // Get wishlist
        $wish = new Press();
        $wish_list = $wish->getwishlist(4,true);

        // Get next session
        $nextpub = new Press();
        $next_session = $nextpub->shownextsession();

        $content['body'] = "

                <div style='width: 95%; margin: auto; font-size: 16px;'>
                    <p>Hello,</p>
                    <p>This is your Journal Club weekly digest.</p>
                </div>

                <div style='width: 95%; margin: 10px auto; border: 1px solid #aaaaaa;'>
                    <div style='background-color: #CF5151; color: #eeeeee; padding: 5px; text-align: left; font-weight: bold; font-size: 16px;'>
                        Last News
                    </div>

                    <div style='font-size: 14px; padding: 5px; background-color: rgba(255,255,255,.5);'>
                        $last_news->content
                    </div>
                </div>

                <div style='width: 95%; margin: 10px auto; border: 1px solid #aaaaaa;'>
                    <div style='background-color: #CF5151; color: #eeeeee; padding: 5px; text-align: left; font-weight: bold; font-size: 16px;'>
                        Upcoming session
                    </div>
                    <div style='font-size: 14px; padding: 5px; background-color: rgba(255,255,255,.5);'>
                        $next_session
                    </div>
                </div>

                <div style='width: 95%; margin: 10px auto; border: 1px solid #aaaaaa;'>
                    <div style='background-color: #CF5151; color: #eeeeee; padding: 5px; text-align: left; font-weight: bold; font-size: 16px;'>
                        Future sessions
                    </div>

                    <div style='font-size: 14px; padding: 5px; background-color: rgba(255,255,255,.5); display: block;'>
                        $pres_list
                    </div>
                </div>

                <div style='width: 95%; margin: 10px auto; border: 1px solid #aaaaaa;'>
                    <div style='background-color: #CF5151; color: #eeeeee; padding: 5px; text-align: left; font-weight: bold; font-size: 16px;'>
                        Wish list
                    </div>

                    <div style='font-size: 14px; padding: 5px; background-color: rgba(255,255,255,.5);'>
                        $wish_list
                    </div>
                </div>

                <div style='width: 95%; margin: auto; font-size: 16px;'>
                    <p>Cheers,<br>
                    The Journal Club Team</p>
                </div>
        ";

        $content['subject'] = "Last News - ".date('d M Y');

        return $content;
    }

    // Make reminder notification email (including only information about the upcoming session)
    function reminder_Mail() {
        require($_SESSION['path_to_app'].'config/config.php');

        $db_set = new DB_set();
        $db_set->bdd_connect();
        $config = new site_config('get');
        $nextpub = new Press();
        $next_session = $nextpub->shownextsession();
        $dates = $nextpub->getdates();
        $date = $dates[0];

        $content['body'] = "
            <div style='width: 95%; margin: auto;'>
                <p>Hello,<br>
                This is a reminder for the next Journal Club session.</p>
            </div>

            <div style='width: 95%; margin: auto;'>
                <div style='background-color: #CF5151; width: 100%; color: #eeeeee; padding: 5px; text-align: left; font-weight: bold; font-size: 16px; border-bottom: 2px solid #CF5151; margin-top: 2px;'>
                    Next session
                </div>
                <div style='font-size: 14px; width: 100%; padding: 5px; background-color: rgba(127,127,127,.1);'>
                    $next_session
                </div>
            </div>

            <div style='width: 95%; margin: auto;'>
                <p>Cheers,<br>
                The Journal Club Team</p>
            </div>
        ";

        $content['subject'] = "Next session: $date -reminder";

        return $content;
    }

    function formatmail($content) {
        $config = new site_config('get');
        $profile_url = $config->site_url.'index.php?page=profile';
        $body = "
            <div style='font-family: Helvetica Neue, Helvetica, Arial, sans-serif sans-serif; color: #000000; font-weight: 300; font-size: 15px; width: 95%; margin: auto;'>
                <div style='line-height: 1.2; width: 100%; color: #000000;'>
                    <div style='font-size: 30px; color: #cccccc; line-height: 40px; height: 40px; text-align: center; background-color: #555555;'>$config->sitetitle
                    </div>

                    <div style='padding: 10px; margin: auto; text-align: justify; background-color: #dddddd;'>
                        $content
                    </div>

                    <div style='color: #EEEEEE; width: 100%; min-height: 30px; line-height: 30px; text-align: center; background-color: #555555;'>
                        This email has been sent automatically. You can choose to no longer receive notification emails from your
                        <a href='$profile_url' style='color: #CF5151; text-decoration: none;' target='_blank' >profile</a> page.
                    </div>
                </div>
            </div>";

        return $body;
    }

}
