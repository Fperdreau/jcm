<?php
/**
 * File for class MailManager
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

/**
 * Class MailManager
 */
class MailManager extends BaseModel {

    /**
     * MailManager settings
     * @var array $Settings
     */
    protected $settings = array(
        'mail_from'=>"jcm@jcm.com",
        'mail_from_name'=>"Journal Club Manager",
        'mail_host'=>null,
        'mail_port'=>null,
        'mail_username'=>null,
        'mail_password'=>null,
        'SMTP_secure'=>"ssl",
        'pre_header'=>"[JCM]",
        'SMTP_debug'=>1
    );

    // Entity properties
    public $date; // Date of creation
    public $status; // Email status (1:sent, 0:not sent)
    public $recipients; // Email's recipients (comma-separated list of emails)
    public $mail_id; // Email unique id
    public $content; // Email body content
    public $subject; // Email header
    public $attachments; // Attached files (comma-separated)
    public $logs; // Logs

    /**
     * @var Lab $Lab: Lab instance
     */
    private $Lab;

    /**
     * @var Mail $Mail: Mail instance
     */
    private $Mail;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->setLab();
    }

    /**
     * Add email to db
     * @param null|array $post
     * @return bool|mysqli_result
     */
    public function add(array $post=null) {
        $post = (is_null($post)) ? $_POST:$post;
        $post['date'] = date('Y-m-d h:i:s'); // Date of creation

        $content = $this->parseData($post);
        return $this->db->insert($this->tablename, $content);
    }

    /**
     * Set Mail instance
     * @param array $settings: Mail settings
     * @return void
     */
    private function setMail(array $settings) {
        $this->Mail = new Mail($settings);
    }

    /**
     * Lab setter
     * @return void
     */
    private function setLab() {
        $this->Lab = new Lab();
    }

    /**
     * Set custom settings
     * @param array $settings
     * @return bool|void
     */
    private function setSettings(array $settings) {
        foreach ($settings as $key=>$value) {
            if (in_array($key, array_keys($this->settings))) {
                $this->settings[$key] = $value;
            }
        }

        // Update Mail object if instantiated
        if (!is_null($this->Mail)) {
            $this->Mail->setSettings($this->settings);
        }
    }

    /**
     * Renders email content
     * @param $mail_id
     * @return string
     */
    public function show($mail_id) {
        $data = $this->get(array('mail_id'=>$mail_id));
        if (empty($data)) {
            return null;
        }
        $attachments = (!empty($data['attachments'])) ? explode(',', $data['attachments']) : array();
        $Media = new Media();
        $files = array();
        foreach ($attachments as $file_name) {
            // Get file information
            $file_data = $Media->get(array('fileid'=>$file_name));
            if (!is_null($file_data)) {
                $files[] = array('name'=>$file_data['name'] . '.' . $file_data['type'], "path"=>URL_TO_APP . 'uploads/' . $file_data['filename']);
            }
        }
        return self::showEmail($data, $files);
    }

    /**
     * Sends an email
     * @param array $content : email content
     * @param array $mailing_list : recipients list
     * @param bool $undisclosed : hide (true) or show(false) recipients list
     * @param array $settings : email host settings (for testing)
     * @param bool $add : add email to Db
     * @return mixed
     * @throws Exception
     * @throws phpmailerException
     */
    public function send(array $content, array $mailing_list, $undisclosed=true, array $settings=null, $add=true) {
        // Generate ID
        $data['mail_id'] = $this->generateID('mail_id');

        // Format email content
        $auto = !isset($content['mail_from']); // Is this message sent by a human or automatically
        $body = $this->formatmail($content['body'], $data['mail_id'], $auto);

        $data['status'] = 0;
        $data['content'] = $body;
        $data['recipients'] = implode(',', $mailing_list);
        $data['attachments'] = !empty($content['attachments']) ? $content['attachments'] : null;

        // Set sender
        if (isset($content['mail_from'])) {
            if (is_null($settings)) {
                $settings = $this->settings;
            }

            // Add custom settings
            $settings['mail_from'] = $content['mail_from'];
            $settings['mail_from_name'] = (isset($content['mail_from_name'])) ? $content['mail_from_name'] : $content['mail_from'];

            // Email title
            $content['subject'] = "Sent by {$settings['mail_from_name']} - {$content['subject']}";
        }

        // Update object settings
        if (!is_null($settings)) {
            $this->setSettings($settings);
        }

        $content['subject'] = $this->settings['pre_header'] . ' ' . $content['subject'];
        $data['subject'] = $content['subject'];

        // Add attachments
        if (!is_null($data['attachments'])) {
            $attachments = $this->addAttachments($data['attachments'], $data['mail_id']);
        } else {
            $attachments = null;
        }

        // Add email to the MailManager table
        $result = array('status'=>true, 'msg'=>null);
        if ($add) {
            $result = $this->addAndSend($data, $mailing_list, $content, $body, $attachments, $undisclosed);
        }

        // Output result
        if ($result['status']) {
            $result['msg'] = "Your message has been sent!";
        } else {
            Logger::getInstance(APP_NAME, get_class($this))->error($result['msg']);
            $result['msg'] = 'Oops, something went wrong!';
        }

        return $result;
    }

    /**
     * Helper function to adding email to Db and sending email
     *
     * @param $data
     * @param $mailing_list
     * @param $content
     * @param $body
     * @param $attachments
     * @param $undisclosed
     * @return array
     * @throws Exception
     * @throws phpmailerException
     */
    private function addAndSend($data, $mailing_list, $content, $body, $attachments, $undisclosed) {
        if ($this->add($data)) {

            // Set Email instance
            $this->setMail($this->settings);

            // Send email
            $result = $this->Mail->send_mail($mailing_list, $content['subject'], $body, $attachments, $undisclosed);

            if ($result['status'] === true) {
                // Update MailManager table
                $result['status'] = $this->update(array('status'=>1, 'logs'=>$result['logs']), array('mail_id'=>$data['mail_id']));
            } else {
                $this->update(array('status'=>0, 'logs'=>$result['logs']), array('mail_id'=>$data['mail_id']));
                $result['msg'] = $result['logs'];
            };

        } else {
            $result['status'] = false;
            $result['msg'] = 'Could not add email to database';
        };
        return $result;
    }

    /**
     * Helper function for adding attachments before sending email
     * @param $data
     * @param $mail_id
     * @return array
     */
    private function addAttachments($data, $mail_id) {
        $attachments = array();
        $Media = new Media();
        foreach (explode(',', $data) as $file_id) {

            if (is_file($file_id)) {
                $attachments[] = $file_id;
            } else {
                // Get file information
                $file_data = $Media->get(array('id'=>$file_id));
                if (!empty($file_data)) {
                    if ($Media->update(array('obj_id'=>$mail_id), array('id'=>$file_id))) {
                        $attachments[] = PATH_TO_UPLOADS . $file_data['filename'];
                    }
                } else {
                    Logger::getInstance(APP_NAME, __CLASS__)->warning(
                        "Could not add file '{$file_data['filename']}' in attachment");
                }
            }
        }
        return $attachments;
    }

    /**
     * Format email (html)
     * @param string $content
     * @param null $email_id
     * @param bool $auto : has this email been sent automatically
     * @return string
     * @throws Exception
     */
    public function formatmail($content, $email_id=null, $auto=True) {
        $show_in_browser = (is_null($email_id)) ? null:
            "<a href='" . URL_TO_APP . "pages/mail.php?mail_id={$email_id}"
            . "' target='_blank' style='color: #CF5151; text-decoration: none;'>Show</a> in browser";

        return self::template($content, $this->Lab->getSettings('name'), $show_in_browser, $auto);
    }

    /**
     * Get list of users' email
     * @param null $type
     * @return array
     */
    public static function get_mailinglist($type=null) {
        $User = new Users();
        $criteria = array("active"=>1);
        if (!is_null($type)) {
            $criteria[$type] = 1;
        }
        $mailing_list = array();
        foreach ($User->all($criteria, array('order'=>'fullname')) as $key=>$item) {
            $mailing_list[$item['fullname']] = array('email'=>$item['email'], 'username'=>$item['username']);
        }

        return $mailing_list;
    }

    /**
     * Gets contact form
     * @param string|null $recipients : list of recipients
     * @return string
     */
    public function getContactForm($recipients=null) {
        $user = new Users();

        if (!is_null($recipients)) {
            $recipients_list = array();
            foreach (explode(',', $recipients) as $id) {
                if (!empty($id)) $recipients_list[] = $user->getById($id);
            }
        } else {
            $recipients_list = null;
        }

        $mailing_list = "";
        foreach ( $user->all_but_admin() as $key=>$info) {
            $selected = (!is_null($recipients_list) && $info['fullname'] === $recipients_list[0]['fullname']) ? 'selected' : null;
            if (!empty($info['fullname'])) $mailing_list .= "<option value='{$info['id']}' {$selected}>{$info['fullname']}</option>";
        }

        $sender_obj = new Users($_SESSION['username']);
        $sender = array('mail_from'=>$sender_obj->email, 'mail_from_name'=>$sender_obj->fullname);

        // Upload
        $uploader = Media::uploader(__CLASS__, array(), 'email_form');
        return self::contactForm($uploader, $mailing_list, $recipients_list, $sender);
    }

    /**
     * @param array $data
     * @return mixed
     */
    public static function addRecipients(array $data) {
        $id = htmlspecialchars($data['add_emails']);
        $user = new Users();
        if (strtolower($id) === 'all') {
            $users = $user->all_but_admin();
            $content = "";
            $ids = array();
            foreach ($users as $key=>$info) {
                $ids[] = $info['id'];
                $content .= self::showRecipient($info);
            }
            $result['ids'] = implode(',', $ids);
        } else {
            $info = $user->getById($id);
            $content = self::showRecipient($info);
            $result['ids'] = $id;
        }
        $result['content'] = $content;
        $result['status'] = true;
        return $result;
    }

    /**
     * Send email to selected recipients
     *
     * @param array $data
     * @return mixed
     * @throws Exception
     * @throws phpmailerException
     */
    public static function sendToRecipients(array $data) {
        $content = array();
        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                $value = htmlspecialchars_decode($value);
            }
            $content[$key] = $value;
        }

        $ids = explode(',',$data['emails']); // Recipients list
        $content['attachments'] = $data['attachments']; // Attached files
        $disclose = htmlspecialchars($data['undisclosed']) == 'yes'; // Do we show recipients list?
        $make_news = htmlspecialchars($data['make_news']) == 'yes'; // Do we show recipients list?
        $user = new Users();
        $MailManager = new MailManager();

        // Get emails from the provided list of IDs
        $mailing_list = array();
        foreach ($ids as $id) {
            $tmp = $user->getById($id);
            $mailing_list[] = $tmp['email'];
        }

        $result = $MailManager->send($content, $mailing_list, $disclose);

        if ($make_news) {
            $news = new Posts();
            $news->add(array(
                'title'=>$content['subject'],
                'content'=>$content['body'],
                'username'=>$_SESSION['username'],
                'homepage'=>1
            ));
        }

        return $result;
    }

    /**
     * Send a test email to verify the email host settings
     * @param array $data : email host settings
     * @return mixed
     * @throws Exception
     * @throws phpmailerException
     */
    public function send_test_email(array $data) {
        // Tested settings
        $settings = array();
        foreach ($data as $setting=>$value) {
            $settings[$setting] = htmlspecialchars($value);
        }

        // Get recipient
        $to = (isset($_POST['test_email'])) ? htmlspecialchars($_POST['test_email']) : null;

        if (is_null($to)) {
            $Users = new Users();
            $admins = $Users->getadmin('admin');
            $to = array();
            foreach ($admins as $key=>$admin) {
                $to[] = $admin['email'];
            }
        } else {
            $to = array($to);
        }

        $content['subject'] = 'Test: email host settings'; // Give the email a subject
        $content['body'] = self::test_email();

        return $this->send($content, $to, true, $data);
    }

    /**
     * Send email to selected organizer
     *
     * @return array
     * @throws Exception
     * @throws phpmailerException
     */
    public function sendMessage() {
        $sel_admin_mail = htmlspecialchars($_POST['admin_mail']);
        $usr_msg = htmlspecialchars($_POST["message"]);
        $usr_mail = htmlspecialchars($_POST["email"]);
        $usr_name = htmlspecialchars($_POST["name"]);
        $content = "Message sent by {$usr_name} ($usr_mail):<br><p>$usr_msg</p>";
        $subject = "Contact from $usr_name";

        $settings['mail_from'] = $usr_mail;
        $settings['mail_from_name'] = $usr_mail;

        if ($this->send(array('body'=>$content, 'subject'=>$subject), array($sel_admin_mail),true, $settings)) {
            $result['status'] = true;
            $result['msg'] = "Your message has been sent!";
        } else {
            $result['status'] = false;
        }
        return $result;
    }

    /**
     * Send an email to the whole mailing list (but only to users who agreed upon receiving emails)
     * @param $subject
     * @param $body
     * @param null $type
     * @param null $attachment
     * @return bool
     * @throws Exception
     * @throws phpmailerException
     */
    function send_to_mailinglist($subject,$body,$type=null,$attachment = NULL) {
        $to = array();
        foreach (self::get_mailinglist($type) as $fullname=>$data) {
            $to[] = $data['email'];
        }
        if ($this->send($to, $subject, $body, $attachment)) {
            return true;
        } else {
            return false;
        }
    }

    /* VIEWS */

    /**
     * Email template
     * @param string $content: email content
     * @param string $lab_name: Lab name
     * @param string $show_in_browser: link to show email in web browser
     * @param boolean $auto: was this email sent by the system or by an user
     * @return string
     */
    private static function template($content, $lab_name, $show_in_browser, $auto)
    {
        $profile_url = URL_TO_APP . 'index.php?page=member/profile';

        $css_title = "
                color: rgba(255,255,255,1);
                text-align: left;
                font-size: 0;
                font-weight: 300;
                margin: 0;
                padding: 0;
                position: relative;
        ";

        return "
            <div style='font-family: Ubuntu, Helvetica, Arial, sans-serif sans-serif; 
            color: #444444; font-weight: 300; font-size: 14px; width: 100%; height: auto; margin: 0;'>
                <div style='line-height: 1.2; min-width: 320px; width: 70%;  margin: 50px auto 0 auto;'>
                    <div style='padding: 10px 20px;  margin: 2% auto; width: 100%; 
                    background-color: rgba(68, 68, 68, 1); border: 1px solid #e0e0e0; 
                    font-size: 2em; text-align: center;'>
                        <div style='{$css_title}'>
                            <span style='font-size: 30px; font-weight: 400;'>JCM</span>
                            <span style='font-size: 25px; color: rgba(200,200,200,.8);'>anager</span>
                            <div style='font-size: 14px; font-style: italic; font-weight: 500; text-align: right;'>
                            " . $lab_name . "</div>
                        </div>
                    </div>

                    <div style='padding:20px;  margin: 2% auto; width: 100%; background-color: #F9F9F9; 
                    border: 1px solid #e0e0e0; text-align: justify;'>
                        {$content}
                    </div>

                    " . self::footer($show_in_browser, $profile_url, $auto) . "
                </div>
            </div>";
    }

    /**
     * Display email
     * @param array $email: email information
     * @param array $attachements: list of files name attached to this email
     * @return string
     */
    public static function showEmail(array $email, array $attachements = array())
    {
        $file_list = "";
        foreach ($attachements as $key => $file) {
            $file_list .= "<div class='email_file'><a href='{$file['path']}'>{$file['name']}</a></div>";
        }
        $content = htmlspecialchars_decode($email['content']);
        return "
        <div class='email_files'><div class='email_header'>Files list:</div>{$file_list}</div>
        <div class='email_title'><span class='email_header'>Subject:</span> {$email['subject']}</div>
        <div class='email_content'>{$content}</div>
        ";
    }

    /**
     * Render email footer
     * @param $url_browser
     * @param $profile_url
     * @param bool $auto: has this email been sent automatically
     * @return string
     */
    public static function footer($url_browser, $profile_url, $auto = true)
    {
        $auto_msg = ($auto) ? "
            <div style='border-top: 1px solid #e0e0e0;'>
                This email has been sent automatically. You can choose to no longer receive email 
                notifications by going to your
                <a href='{$profile_url}' style='color: #CF5151; text-decoration: none;' target='_blank' >
                profile</a> page.
            </div>
        " : null;
        return "
        <div style='padding:20px;  margin: 2% auto; width: 100%; border: 1px solid #e0e0e0; min-height: 30px; 
        height: auto; line-height: 30px; text-align: center; background-color: #444444; color: #ffffff'>
            <div style='text-align: center;'>{$url_browser}</div>
            {$auto_msg}
            <div style='text-align: center; border-top: 1px solid #e0e0e0;'>Powered by 
            <a href='" . App::REPOSITORY . "' style='color: #CF5151; text-decoration: none;'>
            " . App::APP_NAME . " " . App::COPYRIGHT . "</a></div>
        </div>
        ";
    }

    /**
     * Renders recipient icon
     * @param array $info
     * @return string
     */
    public static function showRecipient(array $info) {
        return "
            <div class='added_email' id='{$info['id']}'>
                <div class='added_email_name'>{$info['fullname']}</div>
                <div class='added_email_delete' id='{$info['id']}'><img src='". URL_TO_IMG . "trash.png'></div>
            </div>
        ";
    }

    /**
     * Generate recipients list and input field containing recipients id
     * @param array|null $recipients
     * @return array: array('recipients'=>string, 'input'=>string)
     */
    private static function makeRecipientsList(array $recipients=null) {
        $recipients_list = "";
        $emails_input = null;
        if (!is_null($recipients)) {
            $ids = array();
            foreach ($recipients as $key=>$info) {
                $ids[] = $info['id'];
                $recipients_list .= self::showRecipient($info);
            }
            $ids = implode(',', $ids);
            $emails_input = "<input name='emails' type='hidden' value='{$ids}'/>";
        }

        return array(
            'recipients'=>$recipients_list,
            'input'=>$emails_input
        );
    }

    /**
     * Renders contact form
     * @param $uploader
     * @param $mailing_list
     * @param array|null $recipients
     * @param null|array $sender: sender information (full name and email address).
     *      $sender = array('mail_from'=>"email@me.com", 'mail_from_name'=>"John Doe")
     * @return string
     */
    public static function contactForm($uploader, $mailing_list, array $recipients=null, $sender=null) {

        $recipients_div = self::makeRecipientsList($recipients);

        $sender_info = null;
        if (!is_null($sender)) {
            foreach ($sender as $key=>$value) {
                $value = str_replace(' ','', $value);
                if (!empty($value)) {
                    $sender_info .= "<input type='hidden' name='{$key}' value='{$value}' />";
                }
            }
        }

        return "
            <div class='mailing_container'>                 
            <!-- Upload form -->
            <div class='mailing_block'>
                <h3>Attach files (optional)</h3>
                {$uploader}
            </div>
                    
            <form method='post' action='php/form.php' id='email_form'>
                     
                <!-- Recipients list -->
                <div class='mailing_block select_emails_container'>
                    <h3>Select recipients</h3>
                    <div class='mailing_selector_container'>
                        <select class='select_emails_selector' required>
                            <option value='' disabled selected>Select emails</option>
                            <option value='all'>All</option>
                            {$mailing_list}
                        </select>
                        <!--<button type='submit' class='add_email addBtn'></button>-->
                    </div>
                    <div class='select_emails_list'>
                        {$recipients_div['recipients']}
                    </div>
                    <div>
                        <div class='form-group field_auto inline_field email_option'>
                            <select name='undisclosed'>
                                <option value='yes'>Yes</option>
                                <option value='no' selected>No</option>
                            </select>
                            <label>Hide recipients</label>
                        </div>
                        <div class='form-group field_auto inline_field email_option'>
                            <select id='make_news' name='make_news'>
                                <option value='yes'>Yes</option>
                                <option value='no' selected>No</option>
                            </select>
                            <label>Add as news</label>
                        </div>
                    </div>
                </div>
                
                <!-- Text editor -->
                <div class='mailing_lower_container'>
                    <h3>Write your message</h3>
                    <div class='submit_btns'>
                        <input type='hidden' name='attachments' />
                        <input type='hidden' name='mailing_send' value='true' />
                        {$sender_info}
                        {$recipients_div['input']}
                        <input type='submit' name='send' value='Send' class='mailing_send' />
                    </div>
                    <div class='form-group'>
                        <input type='text' name='subject' required />
                        <label>Subject</label>
                    </div>
                    <div class='form-group'>
                        <textarea name='body' id='spec_msg' class='wygiwym' required></textarea>
                    </div>
                </div>
                
            </form>
        </div>
        ";
    }

    /**
     * Generate protocols input list
     *
     * @param array $settings
     * @return string
     */
    private static function protocolsList(array $settings) {
        // Make protocol list
        $protList = '';
        $protocols = ['ssl', 'tls', 'none'];
        foreach ($protocols as $opt) {
            $selected = $settings['SMTP_secure'] == $opt ? 'selected' : null;
            $protList .= "<option value='{$opt}' {$selected}}>" . strtoupper($opt) . "</option>";
        }
        return $protList;
    }

    /**
     * Generate debug options list
     *
     * @param array $settings
     * @return string
     */
    private static function debugOptions(array $settings) {
        $debug_options = '';
        for ($i=0; $i<=5; $i++) {
            $selected = $settings['SMTP_debug'] == $i ? "selected" : null;
            $label = ($i == 0) ? 'Off' : 'Level ' . $i;
            $debug_options .= "
             <option value='{$i}' {$selected}>{$label}</option>
            ";
        }
        return $debug_options;
    }

    /**
     * Render settings form
     * @param array $settings MailManager->settings
     * @return array
     */
    public static function settingsForm(array $settings) {
        // Make SMTP options list
        $debug_options = self::debugOptions($settings);

        // Make protocol list
        $protList = self::protocolsList($settings);

        return  array(
            'title'=>'Email settings',
            'body'=>"
            <form action='php/router.php?controller=MailManager&action=updateSettings' method='post'>
                <h3>Mailing service</h3>
                <div class='form-group'>
                    <input name='mail_from' type='email' value='{$settings['mail_from']}'>
                    <label for='mail_from'>Sender Email address</label>
                </div>
                <div class='form-group'>
                    <input name='mail_from_name' type='text' value='{$settings['mail_from_name']}'>
                    <label for='mail_from_name'>Sender name</label>
                </div>
                <div class='form-group'>
                    <input name='mail_host' type='text' value='{$settings['mail_host']}'>
                    <label for='mail_host'>Email host</label>
                </div>
                <div class='form-group'>
                    <select name='SMTP_secure'>
                        {$protList}
                    </select>
                    <label for='SMTP_secure'>SMTP access</label>
                </div>
                <div class='form-group'>
                    <select name='SMTP_debug'>
                        {$debug_options}
                    </select>
                    <label for='SMTP_secure'>SMTP debug</label>
                </div>
                <div class='form-group'>
                    <input name='mail_port' type='text' value='{$settings['mail_port']}'>
                    <label for='mail_port'>Email port</label>
                </div>
                <div class='form-group'>
                    <input name='mail_username' type='text' value='{$settings['mail_username']}'>
                    <label for='mail_username'>Email username</label>
                </div>
                <div class='form-group'>
                    <input name='mail_password' type='password' value='{$settings['mail_password']}'>
                    <label for='mail_password'>Email password</label>
                </div>
                <div class='form-group'>
                    <input name='pre_header' type='text' value='{$settings['pre_header']}'>
                    <label for='mail_password'>Email tag</label>
                </div>
                <div class='form-group'>
                    <input name='test_email' type='email' value=''>
                    <label for='test_email'>Your email (for testing only)</label>
                </div>

                <div class='submit_btns'>
                    <input type='submit' value='Test settings' class='test_email_settings'> 
                    <input type='submit' value='Next' class='processform process_form'>
                </div>
            </form>
            <div class='feedback'></div>
        ");
    }

    /**
     * Render test email
     * @return string
     */
    private static function test_email() {
        return "
        Hello,<br><br>
        <p>This is just a test email sent to verify your email host settings. If you can read this message, it means 
        that everything went fine and that your settings are valid!</p>
        ";
    }

}