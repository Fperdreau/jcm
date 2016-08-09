<?php
/**
 * File for class AppMail
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

// Import html2text class
require_once(PATH_TO_LIBS."html2text-0.2.2/html2text.php");
require_once(PATH_TO_LIBS.'PHPMailer-master/class.phpmailer.php');
require_once(PATH_TO_LIBS.'PHPMailer-master/class.smtp.php');

/**
 * Class AppMail
 */
class AppMail {
    /**
     * @inject $db
     */
    protected $db;
    protected $tablename;

    /**
     * @inject $config
     */
    private $config;

    /**
     * @var bool
     */
    private $SMTPDebug = false;

    /**
     * Class constructor
     * @param AppDb $db
     */
    function __construct(AppDb $db){
        $this->db = $db;
        $this->tablename = $this->db->tablesname['User'];
        $this->get_config();
    }

    /**
     * Get app settings
     * @return AppConfig
     */
    private function get_config() {
        if (is_null($this->config)) {
            $this->config = new AppConfig($this->db);
        }
        return $this->config;
    }

    /**
     * Send a verification email to organizers when someone signed up to the application.
     * @param $hash
     * @param $user_mail
     * @param $username
     * @return bool
     */
    function send_verification_mail($hash,$user_mail,$username) {
        $MailManager = new MailManager($this->db);
        $Users = new Users($this->db);
        $admins = $Users->getadmin('admin');
        $to = array();
        foreach ($admins as $key=>$admin) {
            $to[] = $admin['email'];
        }

        $content['subject'] = 'Signup | Verification'; // Give the email a subject
        $authorize_url = $this->get_config()->getAppUrl()."index.php?page=verify&email=$user_mail&hash=$hash&result=true";
        $deny_url = $this->get_config()->getAppUrl()."index.php?page=verify&email=$user_mail&hash=$hash&result=false";

        $content['body'] = "
        Hello,<br><br>
        <p><b>$username</b> wants to create an account.</p>
        <p><a href='$authorize_url'>Authorize</a></p>
        or
        <p><a href='$deny_url'>Deny</a></p>
        ";

        return $MailManager->send($content, $to);
    }

    /**
     * Get list of users' email
     * @param null $type
     * @return array
     */
    function get_mailinglist($type=null) {

        $sql = "select * from $this->tablename where active=1";
        if (!is_null($type)) {
            $sql .= " AND {$type}=1";
        }
        $sql .= " ORDER BY fullname";

        $req = $this->db->send_query($sql);
        $mailing_list = array();
        while ($data = mysqli_fetch_array($req)) {
            $mailing_list[$data['fullname']] = array('email'=>$data['email'], 'username'=>$data['username']);
        }
        return $mailing_list;
    }

    /**
     * Send an email to the whole mailing list (but only to users who agreed upon receiving emails)
     * @param $subject
     * @param $body
     * @param null $type
     * @param null $attachment
     * @return bool
     */
    function send_to_mailinglist($subject,$body,$type=null,$attachment = NULL) {
        $to = array();
        foreach ($this->get_mailinglist($type) as $fullname=>$data) {
            $to[] = $data['email'];
        }
        if ($this->send_mail($to,$subject,$body,$attachment)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Send an email
     * @param array|string $to: recipients list
     * @param string $subject: email title
     * @param string $body: body text
     * @param array|null $attachment: list of attached files
     * @param bool $undisclosed: hide (true) recipients list
     * @return bool: success or failure
     */
    function send_mail($to, $subject, $body, $attachment = null, $undisclosed=true) {
        $mail = new PHPMailer();
        $mail->CharSet = 'UTF-8';
        $mail->isSMTP();                                      // set mailer to use SMTP
        $mail->SMTPDebug  = $this->SMTPDebug;         // enables SMTP debug information (for testing)

        $mail->Mailer = "smtp";
        $mail->Host = $this->config->mail_host;
        $mail->Port = $this->config->mail_port;

        if ($this->config->SMTP_secure != "none") {
            $mail->SMTPAuth = true;     // turn on SMTP authentication
            $mail->SMTPSecure = $this->config->SMTP_secure; // secure transfer enabled REQUIRED for GMail
            $mail->Username = $this->config->mail_username;
            $mail->Password = $this->config->mail_password;
        }

        $mail->From = $this->config->mail_from;
        $mail->FromName = $this->config->mail_from_name;

        if ($undisclosed) {
            $mail->addAddress("undisclosed-recipients:;");
        }
        $mail->addReplyTo($this->config->mail_from, $this->config->mail_from_name);

        if (is_array($to)) {
            foreach($to as $to_add){
                if ($undisclosed) {
                    $mail->addBCC($to_add);                  // name is optional
                } else {
                    $mail->addAddress($to_add);
                }
            }
        } else {
            if ($undisclosed) {
                $mail->addBCC($to);                  // name is optional
            } else {
                $mail->addAddress($to);
            }
        }

        $mail->WordWrap = 50;                                 // set word wrap to 50 characters
        $mail->isHTML(true);

        $mail->Subject = $this->config->pre_header." ".$subject;
        $mail->Body    = $body;
        $mail->AltBody= @convert_html_to_text($body); // Convert to plain text for email viewers non-compatible with HTML content

        if(!is_null($attachment)){
            if (!is_array($attachment)) $attachment = array($attachment);
            foreach ($attachment as $path) {
                $split = explode('/', $path);
                $file_name = end($split);
                if (!$mail->addAttachment($path, $file_name)) {
                    return false;
                }
            }
        }

        if ($rep = $mail->send()) {
            $mail->clearAddresses();
            $mail->clearAttachments();
            return true;
        } else {
            return false;
        }

    }

    /**
     * Format email (html)
     * @param string $content
     * @param null $email_id
     * @return string
     */
    function formatmail($content, $email_id=null) {
        $show_in_browser = (is_null($email_id)) ? null:
            "<a href='" . $this->get_config()->getAppUrl()."pages/mail.php?mail_id={$email_id}"
            . "' target='_blank' style='color: #CF5151; text-decoration: none;'>Show</a> in browser";
        $profile_url = $this->get_config()->getAppUrl().'index.php?page=profile';
        $sitetitle = $this->config->sitetitle;
        $body = "
            <div style='font-family: Ubuntu, Helvetica, Arial, sans-serif sans-serif; color: #444444; font-weight: 300; font-size: 14px; width: 100%; height: auto; margin: 0;'>
                <div style='line-height: 1.2; min-width: 320px; width: 70%;  margin: 50px auto 0 auto;'>
                    <div style='padding:20px;  margin: 2% auto; width: 100%; background-color: #F9F9F9; border: 1px solid #e0e0e0; font-size: 2em; line-height: 40px; height: 40px; text-align: center;'>
                        {$sitetitle}
                    </div>

                    <div style='padding:20px;  margin: 2% auto; width: 100%; background-color: #F9F9F9; border: 1px solid #e0e0e0; text-align: justify;'>
                        {$content}
                    </div>

                    <div style='padding:20px;  margin: 2% auto; width: 100%; border: 1px solid #e0e0e0; min-height: 30px; height: auto; line-height: 30px; text-align: center; background-color: #444444; color: #ffffff'>
                        <div style='text-align: center;'>{$show_in_browser}</div>
                        <div style='border-top: 1px solid #e0e0e0;'>This email has been sent automatically. You can choose to no longer receive email 
                        notifications by going to your
                        <a href='{$profile_url}' style='color: #CF5151; text-decoration: none;' target='_blank' >profile</a> page.
                        </div>
                        <div style='text-align: center; border-top: 1px solid #e0e0e0;'>Powered by <a href='{$this->config->repository}' style='color: #CF5151; text-decoration: none;'>Journal Club Manager &copy 2014</a></div>
                    </div>
                </div>
            </div>";
        return $body;
    }
}
