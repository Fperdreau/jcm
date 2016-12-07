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
        "postid" => array("CHAR(50) NOT NULL"),
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
        $this->registerDigest();
        if (null !== $postid) {
            self::get($postid);
        }
        $this->postid = $postid;
    }

    /**
     * Register into DigestMaker table
     */
    private function registerDigest() {
        $DigestMaker = new DigestMaker($this->db);
        $DigestMaker->register('Posts');
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
            $result['msg'] = 'We could not add your post';
        }
        AppLogger::get_instance(APP_NAME, get_class($this))->info($result);
        return $result;
    }

    /**
     * Get post content
     * @param $postid
     * @return bool
     */
    public function get($postid) {
        $sql = "SELECT * FROM {$this->tablename} WHERE postid='{$postid}'";
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
            $result['msg'] = 'Sorry, we could not update your post';
        }
        AppLogger::get_instance(APP_NAME, get_class($this))->log($result);
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
        if ($this->db->deletecontent($this->tablename,array('postid'),array($postid))) {
            AppLogger::get_instance(APP_NAME, get_class($this))->info("Post ({$postid}) deleted");
            return true;
        } else {
            AppLogger::get_instance(APP_NAME, get_class($this))->error("Could not delete post ({$postid})");
            return false;
        }
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
        $data = array();
        while ($row = $req->fetch_assoc()) {
            $data[] = $row;
        }
        if (!empty($data)) {
            $post_ids = array();
            foreach ($data as $key=>$info) {
                $post_ids[] = $info['postid'];
            }
            return $post_ids;
        } else {
            return false;
        }
    }

    /**
     * Show post on the homepage
     * @param bool $homepage: only display news for homepage
     * @return string
     */
    public function show($homepage=false) {
        $posts_ids = self::getlastnews($homepage);
        if ($posts_ids !== false) {
            $news = "";
            foreach ($posts_ids as $id) {
                $post = new self($this->db,$id);
                $day = date('d M y',strtotime($post->date));
                $news .= "
                <div style='width: 100%; box-sizing: border-box; padding: 0; margin: 10px auto 0 auto; background-color: rgba(255,255,255,1); border: 1px solid #bebebe;'>
                    <div style='width: 60%; height: 20px; line-height: 20px; padding: 5px; margin: 0; text-align: left; font-size: 15px; font-weight: bold; border-bottom: 1px solid #555555;'>$post->title</div>
                    <div style='text-align: left; margin: auto; background-color: white; padding: 10px;'>
                        $post->content
                    </div>
                    <div style='position:relative; width: auto; padding: 2px 10px 2px 10px; background-color: rgba(60,60,60,.9); margin: auto; text-align: right; color: #ffffff; font-size: 13px;'>
                        <div style='text-align: left'>$day at $post->time</div>
                        <div style='text-align: right'>Posted by <span id='author_name'>$post->username</span></div>
                    </div>
                </div>";
            }
        } else {
            $news = "<section style='width: 100%; box-sizing: border-box; padding: 5px; margin: 10px auto 0 auto; 
                    background-color: rgba(255,255,255,1); border: 1px solid #bebebe;'>
                    No recent news</section>";
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
            <form method='post' action='php/form.php' id='post_form'>
                <div class='submit_btns'>
                    $del_btn
                    <input type='submit' name='$op' value='$submit' class='submit_post'/>
                </div>
                <input type='hidden' name='postid' value='$post->postid'>
                <input type='hidden' name='post_add' value='$op'>
                <input type='hidden' name='username' value='$username'/>
                <div class='form-group'>
                    <input type='text' name='title' value='$post->title' required>
                    <label>Title (255 c. max)</label>
                </div>
                <div class='form-group'>
                    <select name='homepage'>
                        <option value='$post->homepage'>$homepage</option>
                        <option value='1'>Yes</option>
                        <option value='0'>No</option>
                    </select>
                    <label>Homepage</label>
                </div>
                <div class='form-group'>
                    <div class='post_txtarea' style='display: block; text-align: right;'>
                    </div>
                    <label>Message</label>
                </div>
            </form>";
        $result['content'] = $post->content;
        return $result;
    }

    /**
     *
     * @param null $username
     * @return mixed
     */
    public function makeMail($username=null) {
        $last = $this->getlastnews();
        $last_news = new self($this->db, $last[0]);
        $today = date('Y-m-d');
        if ( date('Y-m-d',strtotime($last_news->date)) < date('Y-m-d',strtotime("$today - 7 days"))) {
            $last_news->content = "No recent news this week";
        }
        
        $content['body'] = $last_news->content;
        $content['title'] = 'Last News';
        return $content;
    }
}
