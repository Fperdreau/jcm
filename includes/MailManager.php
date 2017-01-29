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

/**
 * Class MailManager
 */
class MailManager extends AppTable {

    protected $table_data = array(
        "id" => array("INT NOT NULL AUTO_INCREMENT", false),
        "date" => array("DATETIME", false),
        "mail_id" => array("CHAR(15)", false),
        "status" => array("INT(1)", 0),
        "recipients" => array("TEXT NOT NULL", false),
        "attachments" => array("TEXT NOT NULL", false),
        "content" => array("TEXT NOT NULL"),
        "subject" => array("TEXT(500)", false),
        "primary" => "id");

    public $date; // Date of creation
    public $status; // Email status (1:sent, 0:not sent)
    public $recipients; // Email's recipients (comma-separated list of emails)
    public $mail_id; // Email unique id
    public $content; // Email body content
    public $subject; // Email header
    public $attachments; // Attached files (comma-separated)

    /**
     * Constructor
     * @param AppDb $db
     */
    function __construct(AppDb $db) {
        parent::__construct($db, "MailManager", $this->table_data);
    }

    /**
     *  Create an unique ID for the new presentation
     * @return string
     */
    function generateID() {
        $id_pres = date('Ymd').rand(1,10000);

        // Check if random ID does not already exist in our database
        $prev_id = $this->db->getinfo($this->tablename,'mail_id');
        while (in_array($id_pres,$prev_id)) {
            $id_pres = date('Ymd').rand(1,10000);
        }
        return $id_pres;
    }

    /**
     * Add email to db
     * @param null|array $post
     * @return bool|mysqli_result
     */
    public function add($post=null) {
        $post = (is_null($post)) ? $_POST:$post;
        //$post['mail_id'] = (!isset($post['mail_id'])) ? $this->generateID() : $post['mail_id'];
        $post['date'] = date('Y-m-d h:i:s'); // Date of creation
        $class_vars = get_class_vars("MailManager");

        $content = $this->parsenewdata($class_vars, $post);
        return $this->db->addcontent($this->tablename, $content);
    }

    /**
     * Gets email
     * @param $mail_id
     * @return mixed
     */
    public function get($mail_id) {
        $sql = "SELECT * FROM {$this->tablename} WHERE mail_id='{$mail_id}'";
        return $this->db->send_query($sql)->fetch_assoc();
    }

    /**
     * Get all emails
     * @param null $status
     * @return mixed
     */
    public function all($status=null) {
        $where = (!is_null($status)) ? "WHERE status=0":null;
        $req = $this->db->send_query("SELECT * FROM {$this->tablename} {$where} ORDER BY date");
        $data = array();
        while ($row = $req->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    /**
     * Update email
     * @param $post
     * @param $id
     * @return bool
     */
    public function update($post, $id) {
        return $this->db->updatecontent($this->tablename, $post, array('mail_id'=>$id));
    }

    /**
     * Renders email content
     * @param $mail_id
     * @return string
     */
    public function show($mail_id) {
        $data = $this->get($mail_id);
        if (is_null($data)) {
            return null;
        }
        $attachments = (!empty($data['attachments'])) ? explode(',', $data['attachments']):array();
        return self::showEmail($data, $attachments);
    }

    /**
     * Display email
     * @param array $email: email information
     * @param array $attachements: list of files name attached to this email
     * @return string
     */
    public static function showEmail(array $email, array $attachements=array()) {
        $file_list = "";
        foreach ($attachements as $file_name) {
            $file_list .= "<div class='email_file'><a href='" . URL_TO_APP . 'uploads/' . $file_name . "'>{$file_name}</a></div>";
        }
        $content = htmlspecialchars_decode($email['content']);
        return "
        <div class='email_files'><div class='email_header'>Files list:</div>{$file_list}</div>
        <div class='email_title'><span class='email_header'>Subject:</span> {$email['subject']}</div>
        <div class='email_content'>{$content}</div>
        ";
    }

    /**
     * Sends an email
     * @param array $content : email content
     * @param array $mailing_list : recipients list
     * @param bool $undisclosed : hide (true) or show(false) recipients list
     * @param array $settings: email host settings (for testing)
     * @return mixed
     */
    public function send(array $content, array $mailing_list, $undisclosed=true, array $settings=null) {
        $AppMail = new AppMail($this->db);

        // Generate ID
        $data['mail_id'] = $this->generateID();

        // Format email content
        $auto = !isset($content['mail_from']); // Is this message sent by a human or automatically
        $body = $AppMail->formatmail($content['body'], $data['mail_id'], $auto);

        $data['status'] = 0;
        $data['content'] = $body;
        $data['recipients'] = implode(',', $mailing_list);
        $data['attachments'] = !empty($content['attachments']) ? $content['attachments'] : null;

        // Set sender
        if (isset($content['mail_from'])) {
            if (is_null($settings)) {
                $settings = array();
            }
            $settings['mail_from'] = $content['mail_from'];
            $settings['mail_from_name'] = (isset($content['mail_from_name'])) ? $content['mail_from_name'] : $content['mail_from'];
            $content['subject'] = "Sent by {$settings['mail_from_name']} - {$content['subject']}";
        }
        $data['subject'] = $content['subject'];

        if (!is_null($data['attachments'])) {
            $attachments = array();
            foreach (explode(',', $data['attachments']) as $file_name) {
                $attachments[] = PATH_TO_APP . '/uploads/' . $file_name;
            }
        } else {
            $attachments = null;
        }

        // Add email to the MailManager table
        if ($this->add($data)) {

            // Send email
            if ($AppMail->send_mail($mailing_list, $content['subject'], $body, $attachments, $undisclosed, $settings)) {

                // Update MailManager table
                $result['status'] = $this->update(array('status'=>1), $data['mail_id']);
            } else {
                $result['status'] = false;
                $result['msg'] = 'Could not send email';
            };

        } else {
            $result['status'] = false;
            $result['msg'] = 'Could not add email to database';
        };

        if ($result['status']) {
            $result['msg'] = "Your message has been sent!";
        }
        AppLogger::get_instance(APP_NAME, get_class($this))->log($result);
        return $result;
    }

    /**
     * Gets contact form
     * @param array|null $recipients : list of recipients
     * @return string
     */
    public function getContactForm($recipients=null) {
        $user = new User($this->db);

        if (!is_null($recipients)) {
            $recipients_list = array();
            foreach (explode(',', $recipients) as $id) {
                $user = new User($this->db);
                if (!empty($id)) $recipients_list[] = $user->getById($id);
            }
        } else {
            $recipients_list = null;
        }

        $mailing_list = "";
        foreach ( $user->all() as $key=>$info) {
            $selected = (!is_null($recipients_list) && $info['fullname'] === $recipients_list[0]['fullname']) ? 'selected' : null;
            if (!empty($info['fullname'])) $mailing_list .= "<option value='{$info['id']}' {$selected}>{$info['fullname']}</option>";
        }

        $sender_obj = new User($this->db, $_SESSION['username']);
        $sender = array('mail_from'=>$sender_obj->email, 'mail_from_name'=>$sender_obj->fullname);
        // Upload
        $uploader = Media::uploader();
        return self::contactForm($uploader, $mailing_list, $recipients_list, $sender);
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
                <div class='added_email_delete' id='{$info['id']}'><img src='images/trash.png'></div>
            </div>
        ";
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
                    
            <form method='post' action='php/form.php' id='submit_form'>
                     
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
                        {$recipients_list}
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
                        {$emails_input}
                        <input type='submit' name='send' value='Send' class='mailing_send' />
                    </div>
                    <div class='form-group'>
                        <input type='text' name='subject' required />
                        <label>Subject</label>
                    </div>
                    <div class='form-group'>
                        <textarea name='body' id='spec_msg' class='tinymce' required></textarea>
                    </div>
                </div>
                
            </form>
        </div>
        ";
    }

}