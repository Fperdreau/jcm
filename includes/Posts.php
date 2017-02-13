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
     * @param null $id
     */
    public function __construct($id=null) {
        parent::__construct('Posts', $this->table_data);
        $this->registerDigest();
        if (null !== $id) {
            $this->get($id);
        }
        $this->postid = $id;
    }

    /**
     * Register into DigestMaker table
     */
    private function registerDigest() {
        $DigestMaker = new DigestMaker();
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
     * @param $id
     * @return bool
     */
    public function get($id) {
        $sql = "SELECT * FROM {$this->tablename} WHERE postid='{$id}'";
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
     * @param int $limit: number of items
     * @return array|bool
     */
    public function getlastnews($limit=3) {
        $sql = "SELECT postid from {$this->tablename} ORDER BY date DESC LIMIT 0, {$limit}";
        $req = $this->db->send_query($sql);
        $data = array();
        while ($row = $req->fetch_assoc()) {
            $data[] = $row['postid'];
        }
        return $data;
    }

    /**
     * Get all or a subset of the posted news
     * @param $category: news category
     * @param $order: order of display (asc, desc)
     * @param $page: current page number
     * @param $pp: number of items shown per page
     * @return array
     */
    public function getLimited($category, $order='date ASC', $page, $pp) {
        $sql = "
            SELECT postid
            FROM {$this->tablename}
            ORDER BY {$order} LIMIT $page, $pp";
        $req = $this->db->send_query($sql);
        $data = array();
        while ($row = $req->fetch_assoc()) {
            $data[] = $row{'postid'};
        }
        return $data;
    }

    /**
     * Count total number of entries
     * @param null|string $id: category name
     * @return int
     */
    public function getCount($id=null) {
        $req = $this->db->send_query("SELECT * FROM {$this->tablename}");
        $data = array();
        while ($row = $req->fetch_assoc()) {
            $data[] = $row;
        }
        return count($data);
    }

    /**
     * Show post on the homepage
     * @param null $category
     * @param int $page_number: current page number
     * @return string
     */
    public function index($category=null, $page_number=1) {
        $pp = 10;
        $page_index = ($page_number == 1) ? $page_number - 1 : $page_number;
        $base_url = URL_TO_APP . "index.php?page=news&curr_page=";
        $count = $this->getCount($category);
        $posts_ids = $this->getLimited($category, 'date DESC', $page_index, $pp);
        $pagination = new Pagination();
        if (!empty($posts_ids)) {
            $news = "<div class='paging_container'>" . $pagination::getPaging($count, $pp, $page_number, $base_url) . "</div>";
            foreach ($posts_ids as $id) {
                $post = new self($id);
                $user = new User($post->username);
                $news .= self::display($post, $user->fullname, true);
            }
        } else {
            $news = self::nothing();
        }
        return $news;
    }

    /**
     * Show last posted news
     * @return string
     */
    public function show_last() {
        $posts_ids = self::getlastnews();
        if (!empty($posts_ids)) {
            $news = "";
            foreach ($posts_ids as $id) {
                $post = new self($id);
                $user = new User($post->username);
                $news .= self::display($post, $user->fullname);
            }
        } else {
            $news = self::nothing();
        }
        return $news;
    }

    /**
     * Show news details
     * @param $id
     * @return string
     */
    public function show($id) {
        $this->get($id);
        $user = new User($this->username);
        return self::display($this, $user->fullname, false);
    }

    /**
     * Render "Nothing to show" message
     * @return string
     */
    public static function nothing() {
        return "<section style='width: 100%; box-sizing: border-box; padding: 5px; margin: 10px auto 0 auto; 
                    background-color: rgba(255,255,255,1); border: 1px solid #bebebe;'>
                    No recent news</section>";
    }

    /**
     * Render news
     * @param Posts $post : post information
     * @param $user_name : full name of author
     * @param bool $limit
     * @return string
     */
    public static function display(Posts $post, $user_name, $limit=true) {
        $char_limit = 1000;
        $url = URL_TO_APP . 'index.php?page=news&show=' . $post->postid;
        $day = date('d M y',strtotime($post->date));
        $txt_content = htmlspecialchars_decode($post->content);
        $content = ($limit && strlen($txt_content) > $char_limit) ? substr($txt_content, 0, $char_limit) . "..." : $txt_content;
        $show_more = ($limit && strlen($txt_content) > $char_limit) ? "<span class='blog_more'><a href='{$url}'>"._('Show more')."</a></span>" : null;
        return "
            <div style='width: 100%; box-sizing: border-box; padding: 0; margin: 10px auto 0 auto; background-color: rgba(255,255,255,1); border: 1px solid #bebebe;'>
                <div style='width: 100%; min-height: 20px; line-height: 20px; padding: 5px; margin: 0; text-align: left; font-size: 15px; font-weight: bold;'><a href='{$url}'>{$post->title}</a></div>
                <div style='width: 60%; min-width: 300px; box-sizing: border-box; height: 5px; border-bottom: 2px solid #555555;'></div>

                <div style='text-align: left; margin: auto; background-color: white; padding: 10px;'>
                    $content
                    $show_more
                </div>
                <div style='position:relative; width: auto; padding: 2px 10px 2px 10px; background-color: rgba(50,50,50,1); margin: auto; text-align: right; color: #ffffff; font-size: 13px;'>
                    <div class='news_time_container'>
                        $day at $post->time
                    </div>
                    <div style='text-align: right'>Posted by <span id='author_name'>$user_name</span></div>
                </div>
            </div>";
    }

    /**
     * Generate selection list of news (for editing)
     * @param User $user
     * @return string
     */
    public function get_selection_list(User $user) {
        // Get all posted news if user has at least the organizer level, otherwise only get user's posts.
        $post_list = (AppPage::$levels[$user->status] >= 1) ? $this->all() : $this->all(array('username'=>$user->username));
        if (!empty($post_list)) {
            return self::selection_menu($post_list);
        } else {
            return self::nothing();
        }
    }

    /**
     * Render selection list
     * @param array $data
     * @return string
     */
    public static function selection_list(array $data) {
        $options = "";
        foreach ($data as $key=>$item) {
            $day = date('d M y',strtotime($item['date']));
            $options .= "<option value='{$item['postid']}'><b><strong>{$day}</strong> |</b> {$item['title']}</option>";
        }
        return "
            <select class='select_post'>
                <option value='' selected disabled>Select a post to modify</option>
                {$options}
            </select>";
    }

    public static function selection_menu(array $data) {
        $content = null;
        foreach ($data as $key=>$item) {
            $day = date('d M y', strtotime($item['date']));
            $content .= "
                <tr class='list-container-row news-details el_to_del' id='{$item['postid']}'>
                    <td>{$day}</td>
                    <td>{$item['username']}</td>
                    <td>{$item['title']}</td>
                    <td class='action_cell'>
                        <div class='action_icon'><a href='' class='edit_post'><img src='" . URL_TO_IMG . 'edit.png' . "' /></a></div>
                        <div class='action_icon'><a href='' class='delete' id='{$item['postid']}' 
                        data-params='Posts/delete/{$item['postid']}'><img src='" . URL_TO_IMG . 'trash.png' . "' /></a></div>
                    </td>
                </tr>";
        }
        return "<table class='table_container'>
                    <tr class='list-container-header'>
                        <td>Date</td>
                        <td>Author</td>
                        <td>Title</td>
                        <td>Actions</td>
                    </tr>
                    {$content}
                </table>";
    }

    /**
     * Show post form
     * @param $username
     * @param bool $postid
     * @return mixed
     */
    public function form($username, $postid=false) {
        if (false == $postid) {
            $post = new self();
            $op = "post_add";
            $submit = "Add";
            $del_btn = "";
        } else {
            $post = new self($postid);
            $op = "post_mod";
            $submit = "Modify";
            $del_btn = "<input type='button' class='post_del' id='submit' data-id='$post->postid' value='Delete'/>";
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
                    <div class='post_txtarea' style='display: block; text-align: right;'>
                    </div>
                    <label>Message</label>
                </div>
            </form>";
        $result['content'] = $post->content;
        return $result;
    }

    /**
     * Display last news in digest email
     * @param null $username
     * @return mixed
     */
    public function makeMail($username=null) {
        $last = $this->getlastnews();
        $last_news = new self($last[0]);
        $today = date('Y-m-d');
        if ( date('Y-m-d',strtotime($last_news->date)) < date('Y-m-d',strtotime("$today - 7 days"))) {
            $last_news->content = "No recent news this week";
        }
        
        $content['body'] = $last_news->content;
        $content['title'] = 'Last News';
        return $content;
    }

    /**
     * Convert post username
     */
    public function patch_table () {
        $sql = "SELECT * FROM {$this->tablename}";
        $req = $this->db->send_query($sql);
        $user = new User();
        while ($row = mysqli_fetch_assoc($req)) {
            $data = $user->search(array('fullname'=>$row['username']));
            if (!empty($data)) {
                $cur_post = new self($row['postid']);
                $cur_post->update(array('username'=>$data[0]['username']));
            }
        }
    }
}
