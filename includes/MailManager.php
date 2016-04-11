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
        $req = "SELECT * FROM {$this->tablename} {$where} ORDER BY date";
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
        return htmlspecialchars_decode($data['content']);
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
        $attachments = isset($content['attachments']) ? $content['attachments'] : null;

        $data['content'] = $body;
        $data['subject'] = $content['subject'];
        $data['recipients'] = implode(',', $mailing_list);

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

}