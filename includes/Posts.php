<?php
/**
 * File for class Posts
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
 * Class Posts
 *
 * Handle creation of posts
 */
class Posts extends AppTable {

    protected $table_data = array(
        "id" => array("INT NOT NULL AUTO_INCREMENT", false),
        "postid" => array("CHAR(30) NOT NULL"),
        "date" => array("DATETIME", False),
        "title" => array("VARCHAR(255) NOT NULL"),
        "content" => array("TEXT(5000) NOT NULL", false, "post"),
        "username" => array("CHAR(30) NOT NULL", false),
        "homepage" => array("INT(1) NOT NULL", 0),
        "primary" => "id");

    public $postid = "";
    public $title = "";
    public $content = "";
    public $date = "";
    public $day;
    public $time;
    public $username = "";
    public $homepage = 0;

    /**
     * Constructor
     * @param $db AppDb
     * @param null $postid
     */
    public function __construct(AppDb $db,$postid=null) {
        parent::__construct($db,'Posts', $this->table_data);
        if (null !== $postid) {
            self::get($postid);
        }
        $this->postid = $postid;
    }

    /**
     * Create or update table
     * @param bool $op
     * @return mixed
     */
    public function setup($op=False) {
        if ($this->db->makeorupdate($this->tablename, $this->table_data, $op)) {
            $result['status'] = True;
            $result['msg'] = "'$this->tablename' created";
        } else {
            $result['status'] = False;
            $result['msg'] = "'$this->tablename' not created";
        }

        if ($op === false) {
            // Give ids to posts that do not have one yet (compatibility with older verions)
            $post = new Posts($this->db);
            $sql = "SELECT postid,date,username FROM " . $this->tablename;
            $req = $this->db->send_query($sql);
            while ($row = mysqli_fetch_assoc($req)) {
                $date = $row['date'];
                if (empty($row['postid']) || $row['postid'] == "NULL") {
                    // Get uploader username
                    $userid = $row['username'];
                    $sql = "SELECT username FROM " . $this->db->tablesname['User'] . " WHERE username='$userid' OR fullname='$userid'";
                    $userreq = $this->db->send_query($sql);
                    $data = mysqli_fetch_assoc($userreq);

                    $username = $data['username'];
                    $post->date = $date;
                    $postid = $post->makeID();
                    $this->db->updatecontent($this->tablename, array('postid'=>$postid, 'username'=>$username), array('date'=>$date));
                }
            }
        }
        return $result;
    }

    /**
     * Create a post and add it to the database
     * @param $post
     * @return bool
     */
    public function make($post) {
        $this->date = date('Y-m-d H:i:s');
        $this->day = date('Y-m-d',strtotime($this->date));
        $this->time = date('H:i',strtotime($this->date));
        $post['postid'] = self::makeID();

        $class_vars = get_class_vars("Posts");
        $content = $this->parsenewdata($class_vars, $post, array('day','time'));

        // Add post to the database
        if ($this->db->addcontent($this->tablename,$content)) {
            $result['status'] = true;
            $result['msg'] = "Thank you for your post!";
        } else {
            $result['status'] = false;
        }
        return $result;
    }

    /**
     * Get post content
     * @param $postid
     * @return bool
     */
    public function get($postid) {
        $sql = "SELECT * FROM $this->tablename WHERE postid='$postid'";
        $req = $this->db->send_query($sql);
        $row = mysqli_fetch_assoc($req);
        if (!empty($row)) {
            foreach ($row as $varname=>$value) {
                $this->$varname = htmlspecialchars_decode($value);
            }
            $this->day = date('Y-m-d',strtotime($this->date));
            $this->time = date('H:i',strtotime($this->date));
            return true;
        } else {
            return false;
        }
    }

    /**
     * Update post's content
     * @param array $post
     * @return bool
     */
    public function update($post=array()) {
        $class_vars = get_class_vars("Posts");
        $content = $this->parsenewdata($class_vars, $post, array('day','time'));
        if ($this->db->updatecontent($this->tablename,$content,array("postid"=>$this->postid))) {
            $result['status'] = true;
            $result['msg'] = "Thank you for your post!";
        } else {
            $result['status'] = false;
        }
        return $result;
    }

    /**
     * Create an ID for the new post
     * @return string
     */
    function makeID() {
        $id = md5($this->date.rand(1,10000));

        // Check if random ID does not already exist in our database
        $prev_id = $this->db->getinfo($this->tablename,'postid');
        while (in_array($id,$prev_id)) {
            $id = md5($this->date.rand(1,10000));
        }
        return $id;
    }

    /**
     * Delete a post
     * @param $postid
     * @return bool
     */
    public function delete($postid) {
        return $this->db->deletecontent($this->tablename,array('postid'),array($postid));
    }

    /**
     * Get the newest posts
     * @param bool $homepage
     * @return bool|array
     */
    public function getlastnews($homepage=false) {
        $sql = "SELECT postid from $this->tablename";
        if ($homepage == true) $sql .= " WHERE homepage='1'";
        $sql .= " ORDER BY date DESC";
        $req = $this->db->send_query($sql);
        $data = mysqli_fetch_array($req);
        if (!empty($data)) {
            if ($homepage == true) {
                $post_ids = array($data[0]);
                while ($row = mysqli_fetch_array($req)) {
                    $post_ids[] = $row[0];
                }
            } else {
                $post_ids = $data[0];
            }
            return $post_ids;
        } else {
            return false;
        }
    }

    /**
     * Show post on the homepage
     * @return string
     */
    public function show() {
        $posts_ids = self::getlastnews(true);
        if ($posts_ids !== false) {
            $news = "";
            foreach ($posts_ids as $id) {
                $post = new self($this->db,$id);
                $news .= "
                <div style='width: 100%; box-sizing: border-box; padding: 5px; margin: 10px auto 0 auto; background-color: rgba(255,255,255,.5); border: 1px solid #bebebe;'>
                    <div style='width: 60%; height: 20px; line-height: 20px; margin: 0; text-align: left; font-size: 15px; font-weight: bold; border-bottom: 1px solid #555555;'>$post->title</div>
                    <div style='text-align: justify; margin: auto; background-color: rgba(220,220,220,.2); padding: 10px;'>
                        $post->content
                    </div>
                    <div style='width: auto; padding: 2px 10px 2px 10px; background-color: rgba(60,60,60,.9); margin: auto; text-align: right; color: #ffffff; font-size: 13px;'>
                                $post->day at $post->time, Posted by <span id='author_name'>$post->username</span>
                    </div>
                </div>";
            }
        } else {
            $news = "No recent news";
        }
        return $news;
    }

    /**
     * Show post form
     * @param $username
     * @param bool $postid
     * @return mixed
     */
    public function showpost($username,$postid=false) {
        if (false == $postid) {
            $post = new self($this->db);
            $op = "post_add";
            $submit = "Add";
            $del_btn = "";
        } else {
            $post = new self($this->db,$postid);
            $op = "post_mod";
            $submit = "Modify";
            $del_btn = "<input type='button' class='post_del' id='submit' data-id='$post->postid' value='Delete'/>";
        }

        if ($post->homepage == 0) {
            $homepage = "No";
        } else {
            $homepage = "Yes";
        }
        $result['form'] = "
            <form id='post_form'>
                <div class='submit_btns'>
                    $del_btn
                    <input type='submit' name='$op' value='$submit' class='submit_post'/>
                </div>
                <input type='hidden' name='postid' value='$post->postid'>
                <input type='hidden' name='post_add' value='$op'>
                <input type='hidden' name='username' value='$username'/>
                <div class='formcontrol'>
                    <label>Title</label>
                    <input type='text' name='title' placeholder='Your title (max 255 characters)' value='$post->title' required>
                </div>
                <div class='formcontrol'>
                    <label>Homepage</label>
                    <select name='homepage'>
                        <option value='$post->homepage'>$homepage</option>
                        <option value='1'>Yes</option>
                        <option value='0'>No</option>
                    </select>
                </div>
                <div class='formcontrol'>
                    <label>Message</label>
                    <div class='post_txtarea' style='display: block; text-align: right;'>
                    </div>
                </div>
            </form>";
        $result['content'] = $post->content;
        return $result;
    }
}
