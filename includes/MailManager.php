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
            $file_list .= "<div class='email_file'><a href='" . AppConfig::$site_url . 'uploads/' . $file_name . "'>{$file_name}</a></div>";
        }
        $content = htmlspecialchars_decode($email['content']);
        return "
        <div class='email_files'><div class='email_header'>Files list:</div>{$file_list}</div>
        <div class='email_title'><span class='email_header'>Subject:</span> {$email['subject']}</div>
        <div class='email_content'>{$content}</div>
        ";
    }

    /**
     * @param array $content
     * @param array $mailing_list
     * @return mixed
     */
    public function send(array $content, array $mailing_list) {
        $AppMail = new AppMail($this->db);

        // Generate ID
        $data['mail_id'] = $this->generateID();

        // Format email content
        $body = $AppMail->formatmail($content['body'], $data['mail_id']);

        $data['content'] = $body;
        $data['subject'] = $content['subject'];
        $data['recipients'] = implode(',', $mailing_list);
        $data['attachments'] = !empty($content['attachments']) ? $content['attachments'] : null;

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
            if ($AppMail->send_mail($mailing_list, $content['subject'], $body, $attachments)) {

                // Update MailManager table
                $result['status'] = $this->update(array('status'=>1), $data['mail_id']);
            } else {
                $result['status'] = false;
            };

        } else {
            $result['status'] = false;
        };

        if ($result['status']) {
            $result['msg'] = "Your message has been sent!";
        }
        return $result;

    }

    /**
     * Gets contact form
     * @param array|null $recipients : list of recipients
     * @return string
     */
    public function getContactForm($recipients=null) {
        $user = new User($this->db, $_SESSION['username']);

        if (!is_null($recipients)) {
            $recipients_list = array();
            foreach (explode(',', $recipients) as $id) {
                $user = new User($this->db);
                $recipients_list[] = $user->getById($id);
            }
        } else {
            $recipients_list = null;
        }

        $mailing_list = "";
        foreach ( $user->all() as $key=>$info) {
            $selected = (!is_null($recipients_list) && $info['fullname'] === $recipients_list[0]['fullname']) ? 'selected' : null;
            if (!empty($info['fullname'])) $mailing_list .= "<option value='{$info['id']}' {$selected}>{$info['fullname']}</option>";
        }

        // Upload
        $uploader = Media::uploader();
        return self::contactForm($uploader, $mailing_list, $recipients_list);
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
                <div class='added_email_delete' id='{$info['id']}'><img src='images/close.png'></div>
            </div>
        ";
    }

    /**
     * Renders contact form
     * @param $uploader
     * @param $mailing_list
     * @param array|null $recipients
     * @return string
     */
    public static function contactForm($uploader, $mailing_list, array $recipients=null) {

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

        return "
            <div class='mailing_container'>                 
            <!-- Upload form -->
            <div class='mailing_block'>
                <h3>Attach files (optional)</h3>
                {$uploader}
            </div>
                    
            <form method='post' id='submit_form'>
                     
                <div class='mailing_block select_emails_container'>
                    <h3>Select recipients</h3>
                    <div>
                        <select class='select_emails_selector' required>
                            <option value='' disabled selected>Select emails</option>
                            <option value='all'>All</option>
                            {$mailing_list}
                        </select>
                        <button type='submit' class='add_email addBtn'>
                            <img src='" . AppConfig::$site_url . 'images/add.png' . "'>
                        </button>
                    </div>
                    <div class='select_emails_list'>
                        {$recipients_list}
                    </div>
                </div>
                
                <div class='mailing_lower_container'>
                    <h3>Write your message</h3>
                    <div class='submit_btns'>
                        <input type='hidden' name='attachments' />
                        <input type='hidden' name='mailing_send' value='true' />
                        {$emails_input}
                        <input type='submit' name='send' value='Send' class='mailing_send' />
                    </div>
                    <div class='formcontrol'>
                        <label>Subject:</label>
                        <input type='text' name='spec_head' placeholder='Subject' required />
                    </div>
                    <div class='formcontrol'>
                        <textarea name='spec_msg' id='spec_msg' class='tinymce' required></textarea>
                    </div>
                </div>
                
            </form>
        </div>
        ";
    }

}