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
along with Journal Club Manager. If not, see <http://www.gnu.org/licenses/>.
*/

class Posts {
    private $db;
    private $tablename;

    public $postid = "";
    public $title = "";
    public $content = "";
    public $date = "";
    public $username = "";
    public $homepage = 0;

    /**
     * Constructor
     * @param $db DbSet
     * @param null $postid
     */
    public function __construct($db,$postid=null) {
        $this->db = $db;
        $this->tablename = $this->db->tablesname["Posts"];
        if (null !== $postid) {
            self::get($postid);
        }
        $this->postid = $postid;
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
        $postkeys = array_keys($post);
        $variables = array();
        $values = array();
        foreach ($class_vars as $name=>$value) {
            if (in_array($name,$postkeys)) {
                $escaped = $this->db->escape_query($post[$name]);
            } else {
                $escaped = $this->db->escape_query($this->$name);
            }
            $this->$name = $escaped;
            $variables[] = "$name";
            $values[] = "'$escaped'";
        }
        $variables = implode(',',$variables);
        $values = implode(',',$values);

        // Add post to the database
        if ($this->db->addcontent($this->tablename,$variables,$values)) {
            return true;
        } else {
            return false;
        }
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
        $postkeys = array_keys($post);
        foreach ($class_vars as $name => $value) {
            if (in_array($name,$postkeys)) {
                $escaped = $this->db->escape_query($post[$name]);
            } else {
                $escaped = $this->db->escape_query($this->$name);
            }
            $this->$name = $escaped;

            if (!$this->db->updatecontent($this->tablename,$name,"'$escaped'",array("postid"),array("'$this->postid'"))) {
                return false;
            }
        }
        return true;
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
        return $this->db->deletecontent($this->tablename,array('postid'),array("'$postid'"));
    }

    /**
     * Get the newest post
     * @param bool $homepage
     * @return bool
     */
    public  function getlastnews($homepage=false) {
        $sql = "SELECT postid from $this->tablename";
        if ($homepage == true) {
            $sql .= " WHERE homepage='1'";
        }
        $sql .= " ORDER BY date DESC";
        $req = $this->db->send_query($sql);
        $data = mysqli_fetch_array($req);
        if (!empty($data)) {
            $postid = $data[0];
            self::get($postid);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Show post on the homepage
     * @return string
     */
    public function show() {
        if (self::getlastnews(true)) {
            $news = "
            <div style='width: 95%; padding: 5px; margin: 10px auto 0 auto; background-color: rgba(255,255,255,.5); border: 1px solid #bebebe;'>
                <div style='widht: 100%; height: 20px; line-height: 20px; margin: 5px 10px auto; text-align: left; font-size: 15px; font-weight: bold; border-bottom: 1px solid #555555;'>$this->title</div>
                <div style='width: 95%; text-align: justify; margin: auto; background-color: #eeeeee; padding: 10px;'>
                    $this->content
                </div>
                <div style='widht: 100%; background-color: #aaaaaa; padding: 2px; margin: 0; text-align: right; font-size: 13px;'>
                            $this->day at $this->time, Posted by <span id='author_name'>$this->username</span>
                </div>
            </div>";
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
                    <input type='submit' name='$op' value='$submit' id='submit' class='$op' data-id='$post->postid'/>
                </div>
                <input type='hidden' id='post_username' value='$username'/>
                <div class='formcontrol' style='width: 70%;'>
                    <label>Title</label>
                    <input type='text' id='post_title' placeholder='Your title (max 255 characters)' value='$post->title' style='width: 70%;'>
                </div>
                <div class='formcontrol' style='width: 10%;'>
                    <label>Homepage</label>
                    <select id='post_homepage'>
                        <option value='$post->homepage'>$homepage</option>
                        <option value='1'>Yes</option>
                        <option value='0'>No</option>
                    </select>
                </div>
                <div class='formcontrol' style='width: 100%;'>
                    <label>Message</label>
                    <div class='post_txtarea' style='display: block; text-align: right;'>
                    </div>
                </div>
            </form>";
        $result['content'] = $post->content;
        return $result;
    }
}
