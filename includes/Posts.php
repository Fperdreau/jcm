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
class Posts extends BaseModel {

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
        parent::__construct();
        if (null !== $id) {
            $this->getInfo($id);
        }
        $this->postid = $id;
    }

    /**
     * Register into DigestMaker table
     */
    public static function registerDigest() {
        $DigestMaker = new DigestMaker();
        $DigestMaker->register('Posts');
    }

    /**
     * Add a post to the database
     * @param array $post
     * @return bool|string
     */
    public function add(array $post){
        if ($this->is_exist(array('title'=>$post['title'])) === false) {

            $post['postid'] = $this->generateID('postid');
            $post['date'] = date('Y-m-d H:i:s');
            $post['day'] = date('Y-m-d',strtotime($this->date));
            $post['time'] = date('H:i',strtotime($this->date));

            // Associates this presentation to an uploaded file if there is one
            if (!empty($post['link'])) {
                $media = new Media();
                $media->add_upload(explode(',', $post['link']), $post['postid'], __CLASS__);
            }

            // Add publication to the database
            if ($this->db->insert($this->tablename, $this->parseData($post, array("link")))) {
                return $post['postid'];
            } else {
                return false;
            }
        } else {
            return "exist";
        }
    }

    /**
     * Edit Post
     * @param array $data
     * @return mixed
     */
    public function edit(array $data) {
        // check entries
        $post_id = htmlspecialchars($data['postid']);

        if ($post_id !== "false") {
            $created = $this->update($data, array('postid'=>$post_id));
        } else {
            $created = $this->add($data);
        }

        $result['status'] = $created !== false;
        if ($created === false) {
            $result['msg'] = 'Oops, something went wrong';
        } elseif ($created === 'exist') {
            $result['msg'] = "Sorry, a post with a similar title already exists in our database.";
        } else {
            $result['msg'] = "Thank you for your post!";
        }

        return $result;
    }

    /**
     * Get post information
     * @param $id
     * @return array|bool
     */
    public function getInfo($id) {
        $data = $this->get(array('postid'=>$id));
        if (!empty($data)) {
            $this->map($data);
            $this->day = date('Y-m-d',strtotime($this->date));
            $this->time = date('H:i',strtotime($this->date));
            $data['link'] = $this->get_uploads();
            return $data;
        } else {
            return false;
        }
    }

    /**
     * Get associated files
     */
    private function get_uploads() {
        $upload = new Media();
        $link = $upload->get_uploads($this->postid, __CLASS__);
        return $link;
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
                $user = new Users($post->username);
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
                $user = new Users($post->username);
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
        $this->getInfo($id);
        $user = new Users($this->username);
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
     * @param Users $user
     * @return string
     */
    public function get_selection_list(Users $user) {
        // Get all posted news if user has at least the organizer level, otherwise only get user's posts.
        $post_list = (Page::$levels[$user->status] >= 1) ? $this->all() : $this->all(array('username'=>$user->username));
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

    /**
     * Get submission form
     * @param string $view
     * @return string
     */
    public function get_form($view='body') {
        if ($view === "body") {
            return self::format_section($this->editor($_POST));
        } else {
            $content = $this->editor($_POST);
            return array(
                'content'=>$content['content'],
                'id'=>'suggestion',
                'buttons'=>null,
                'title'=>$content['title']);
        }
    }

    /**
     * Render submission editor
     * @param array|null $post
     * @param string $view
     * @return array
     */
    public function editor(array $post=null, $view='body') {
        $post = (is_null($post)) ? $_POST : $post;
        $id = isset($post['id']) ? $post['id'] : false;
        $user = new Users($_SESSION['username']);
        $data = $this->getInfo($id);
        return self::form($user, (object)$data[0]);
    }

    /**
     * @param array $content
     * @return string
     */
    public static function format_section(array $content) {
        return "
        {$content['content']}
        ";
    }

    /**
     * render selection menu for edit page
     * @param array $data
     * @return string
     */
    public static function selection_menu(array $data) {
        $content = null;
        foreach ($data as $key=>$item) {
            $day = date('d M y', strtotime($item['date']));
            $content .= "
                <div class='table_container'>
                    <div class='list-container-row news-details el_to_del' id='{$item['postid']}'>
                        <div>{$day}</div>
                        <div>{$item['username']}</div>
                        <div>{$item['title']}</div>
                        <div class='action_cell'>
                            <div class='action_icon'><a href='' class='loadContent' data-destination='.post_edit_container#{$item['postid']}' data-controller='Posts' data-action='get_form' data-id='{$item['postid']}'><img src='" . URL_TO_IMG . 'edit.png' . "' /></a></div>
                            <div class='action_icon'><a href='' class='delete' id='{$item['postid']}' 
                            data-params='Posts/delete/{$item['postid']}'><img src='" . URL_TO_IMG . 'trash.png' . "' /></a></div>
                        </div>
                    </div>
                </div>
                <div class='list-container-row post_edit_container' id='{$item['postid']}'></div>
                ";
        }
        return "<div class='table_container'>
                    <div class='list-container-row list-container-header'>
                        <div>Date</div>
                        <div>Author</div>
                        <div>Title</div>
                        <div>Actions</div>
                    </div>
                </div>
                {$content}
                ";
    }

    /**
     * Show post form
     * @param Users $user
     * @param null|Posts $Post
     * @return mixed
     */
    public static function form(Users $user, $Post=null) {
        if (is_null($Post)) {
            $Post = new self();
            $del_btn = "";
        } else {
            $del_btn = "<input type='button' class='post_del' id='submit' data-id='$Post->postid' value='Delete'/>";
        }

        $result['content'] = "
            <form method='post' action='php/form.php' id='post_form'>
                <div class='submit_btns'>
                    $del_btn
                    <input type='submit' class='process_post'/>
                </div>
                <input type='hidden' name='controller' value='Posts'>
                <input type='hidden' name='operation' value='edit'/>
                <input type='hidden' name='postid' value='$Post->postid'>
                <input type='hidden' name='username' value='$user->username'/>
                <input type='hidden' name='process_submission' value='true'/>

                <div class='form-group'>
                    <input type='text' name='title' value='$Post->title' required>
                    <label>Title (255 c. max)</label>
                </div>
                <div class='form-group'>
                    <textarea name='content' id='content' class='wygiwym' style='display: block; text-align: right;'>
                    {$Post->content}
                    </textarea>
                    <label>Message</label>
                </div>
            </form>";

        $result['title'] = "Add/Edit presentation";
        $result['description'] = self::description();
        return $result;
    }

    /**
     * Submission form instruction
     * @return string
     */
    public static function description() {
        return "";
    }

    /**
     * Display last news in digest email
     * @param null $username
     * @return mixed
     */
    public function makeMail($username=null) {
        $last = $this->getlastnews();
        if (empty($last)) {
            $content['body'] = "No news this week";
        } else {
            $last_news = new self($last[0]);
            $today = date('Y-m-d');
            if ( date('Y-m-d',strtotime($last_news->date)) < date('Y-m-d',strtotime("$today - 7 days"))) {
                $last_news->content = "No recent news this week";
            }
            $content['body'] = $last_news->content;
        }

        $content['title'] = 'Last News';
        return $content;
    }

    /**
     * Convert post username
     */
    public static function patch_table () {
        $self = new self();
        $sql = "SELECT * FROM {$self->tablename}";
        $req = $self->db->send_query($sql);
        $user = new Users();
        while ($row = mysqli_fetch_assoc($req)) {
            $data = $user->get(array('fullname'=>$row['username']));
            if (!empty($data)) {
                $cur_post = new self($row['postid']);
                $cur_post->update(array('username'=>$data['username']), array('postid'=>$row['postid']));
            }
        }
    }
}
