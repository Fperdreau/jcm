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
require_once($_SESSION['path_to_app'].'/includes/includes.php');

class Posts {
    public $postid = "";
    public $title = "";
    public $content = "";
    public $date = "";
    public $username = "";
    public $homepage = 0;

    public function __construct($postid=null) {
        if (null !== $postid) {
            self::get($postid);
        }
    }

    public function make($post) {
        require($_SESSION['path_to_app'].'config/config.php');
        $db_set = new DB_set();
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
                $escaped = $db_set->escape_query($post[$name]);
            } else {
                $escaped = $db_set->escape_query($this->$name);
            }
            $this->$name = $escaped;
            $variables[] = "$name";
            $values[] = "'$escaped'";
        }
        $variables = implode(',',$variables);
        $values = implode(',',$values);

        // Add post to the database
        if ($db_set->addcontent($post_table,$variables,$values)) {
            return true;
        } else {
            return false;
        }
    }

    public function get($postid) {
        require($_SESSION['path_to_app'].'config/config.php');

        $class_vars = get_class_vars("Posts");
        $classkeys = array_keys($class_vars);

        $db_set = new DB_set();
        $sql = "SELECT * FROM $post_table WHERE postid='$postid'";
        $req = $db_set->send_query($sql);
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

    public function update($post=array()) {
        require($_SESSION['path_to_app'].'config/config.php');
        $db_set = new DB_set();

        $class_vars = get_class_vars("Posts");
        $postkeys = array_keys($post);
        foreach ($class_vars as $name => $value) {
            if (in_array($name,$postkeys)) {
                $escaped = $db_set->escape_query($post[$name]);
            } else {
                $escaped = $db_set->escape_query($this->$name);
            }
            $this->$name = $escaped;

            if (!$db_set->updatecontent($post_table,$name,"'$escaped'",array("postid"),array("'$this->postid'"))) {
                return false;
            }
        }
        return true;
    }

    // Create an ID for the new presentation
    function makeID() {
        require($_SESSION['path_to_app'].'config/config.php');
        $db_set = new DB_set();
        $id = md5($this->date.rand(1,10000));

        // Check if random ID does not already exist in our database
        $prev_id = $db_set->getinfo($post_table,'postid');
        while (in_array($id,$prev_id)) {
            $id = md5($this->date.rand(1,10000));
        }
        return $id;
    }

    // Delete a post
    function delete($postid) {
        require($_SESSION['path_to_app'].'config/config.php');
        // Delete corresponding entry in the post table
        $db_set = new DB_set();
        return $db_set -> deletecontent($post_table,array('postid'),array("'$postid'"));
    }

    public function getlastnews($homepage=false) {
        require($_SESSION['path_to_app'].'config/config.php');
        $db_set = new DB_set();
        $sql = "SELECT postid from $post_table";
        if ($homepage == true) {
            $sql .= " WHERE homepage='1'";
        }
        $sql .= " ORDER BY date DESC";
        $req = $db_set->send_query($sql);
        $data = mysqli_fetch_array($req);
        if (!empty($data)) {
            $postid = $data[0];
            self::get($postid);
            return true;
        } else {
            return false;
        }
    }

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

    public function showpost($username,$postid=false) {
        if (false == $postid) {
            $post = new Posts();
            $op = "post_add";
            $submit = "Add";
            $del_btn = "";
        } else {
            $post = new Posts($postid);
            $op = "post_mod";
            $submit = "Modify";
            $del_btn = "<input type='button' class='post_del' id='submit' data-id='$post->postid' value='Delete'/>";
        }

        if ($post->homepage == 0) {
            $homepage = "No";
        } else {
            $homepage = "Yes";
        }
        $result['form'] = "<form id='post_form'>
                <input type='hidden' id='post_username' value='$username'/>
                <label for='title' class='label'>Title</label>
                    <input type='text' id='post_title' value='$post->title' style='width: 70%;'/><br>
                <label for='homepage' class='label'>Show it on the homepage</label>
                <select id='post_homepage'>
                    <option value='$post->homepage'>$homepage</option>
                    <option value='1'>Yes</option>
                    <option value='0'>No</option>
                </select><br>
                <label for='content' class='label'>Message</label>
                <div class='post_txtarea' style='display: block; width: 80%; margin: 10px auto; text-align: right;'>
                </div>
                <div class='action_btns'>
                    <div class='one_half'>$del_btn</div>
                    <div  class='one_half last'>
                        <p style='text-align: right'><input type='submit' name='$op' value='$submit' id='submit' class='$op' data-id='$post->postid'/></p>
                    </div>
                </div>
            </form>";
        $result['content'] = $post->content;
        return $result;
    }

}
