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

namespace includes;

use includes\BaseModel;

/**
 * Class Posts
 *
 * Handle creation of posts
 *
 * @package Include
 * @author  Florian Perdreau <fp@florianperdreau.fr>
 * @license AGPL <http://www.gnu.org/licenses/>
 */
class Posts extends BaseModel
{

    public $id = "";
    public $title = "";
    public $content = "";
    public $date = "";
    public $day;
    public $time;
    public $username = "";
    public $homepage = 0;

    /**
     * Constructor
     *
     * @param string|null $id
     */
    public function __construct($id = null)
    {
        parent::__construct();
        if (null !== $id) {
            $this->getInfo($id);
        }
        $this->postid = $id;
    }

    /**
     * Register into DigestMaker table
     *
     * @return void
     */
    public static function registerDigest()
    {
        $DigestMaker = new DigestMaker();
        $DigestMaker->register('Posts');
    }

    /**
     * Add a post to the database
     *
     * @param array $post
     * @return bool|string
     */
    public function add(array $post)
    {
        if ($this->isExist(array('title'=>$post['title'])) === false) {
            //$post['postid'] = $this->generateID('postid');
            $post['date'] = date('Y-m-d H:i:s');

            // Add publication to the database
            if ($this->db->insert($this->tablename, $this->parseData($post, array("link")))) {
                $id = $this->db->getLastId();

                // Associates this presentation to an uploaded file if there is one
                if (!empty($post['link'])) {
                    $media = new Media();
                    $media->addUpload(explode(',', $post['link']), $post['id'], __CLASS__);
                }
                return $id;
            } else {
                return false;
            }
        } else {
            return "exist";
        }
    }

    /**
     * Show post on the homepage
     * @param null $category
     * @param int $page_number: current page number
     * @return string
     */
    public function index($category = null, $page_number = 1)
    {
        $pp = 10;
        $page_index = ($page_number == 1) ? $page_number - 1 : $page_number;
        $base_url = URL_TO_APP . "index.php?page=news&curr_page=";
        $count = $this->getCount($category);
        $posts_ids = $this->getLimited($category, $page_index, $pp, 'date DESC');
        $pagination = new Pagination();

        if (!empty($posts_ids)) {
            $news = "<div class='paging_container'>" . $pagination::getPaging($count, $pp, $page_number, $base_url) . "</div>";
            foreach ($posts_ids as $id) {
                $data = $this->getInfo($id);
                $user = new Users($data['username']);
                $news .= self::display($data, $user->fullname, true);
            }
        } else {
            $news = self::nothing();
        }

        // Add news section
        if (SessionInstance::isLogged()) {
            $news .= self::formSection(self::editor());
        }

        return $news;
    }

    /**
     * Show last posted news
     * @return string
     */
    public function showLast()
    {
        $posts_ids = self::getlastnews();
        if (!empty($posts_ids)) {
            $news = "";
            foreach ($posts_ids as $id) {
                $data = $this->getInfo($id);
                $user = new Users($data['username']);
                $news .= self::display($data, $user->fullname);
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
    public function show($id)
    {
        $data = $this->getInfo($id);
        $user = new Users($data['username']);
        return self::display($data, $user->fullname, false);
    }

    /**
     * Show post on the homepage
     * @param null $category
     * @param int $page_number: current page number
     * @return string
     */
    public function getSelectionList(Users $user, $page_number = 1)
    {
        $pp = 10;
        $page_index = ($page_number == 1) ? $page_number - 1 : $page_number;
        $base_url = URL_TO_APP . "index.php?page=member/news&curr_page=";
        $count = $this->getCount();
        $post_list = (Page::$levels[$user->status] >= 1) ?
        $this->all() : $this->all(array('username'=>$user->username));
        $posts_ids = $this->getLimited(null, $page_index, $pp, 'date DESC');
        $pagination = new Pagination();

        if (!empty($posts_ids)) {
            $news = "<div class='paging_container'>" . $pagination::getPaging($count, $pp, $page_number, $base_url) . "</div>";
            $data = array();
            foreach ($posts_ids as $key => $item) {
                $data[$key] = $this->getInfo($item);
            }
            $news .= self::selectionMenu($data);
        } else {
            $news = self::nothing();
        }
        return $news;
    }

    /**
     * Generate selection list of news (for editing)
     * @param Users $user
     * @return string
     */
    public function getSelectionList_old(Users $user)
    {
        // Get all posted news if user has at least the organizer level, otherwise only get user's posts.
        $post_list = (Page::$levels[$user->status] >= 1) ?
        $this->all() : $this->all(array('username'=>$user->username));
        if (!empty($post_list)) {
            return self::selectionMenu($post_list);
        } else {
            return self::nothing();
        }
    }

    /**
     * Get submission form
     * @param string $view
     * @return array|string
     */
    public function getForm(array $data)
    {
        $view = isset($data['view']) ? $data['view'] : 'body';
        if ($view === "body") {
            return self::formatSection($this->editor($data));
        } else {
            $content = $this->editor($data);
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
     * @return array
     */
    public function editor(array $post = null)
    {
        $post = (is_null($post)) ? $_POST : $post;
        $id = isset($post['id']) ? $post['id'] : false;
        $user = new Users($_SESSION['username']);
        $data = $this->getInfo($id);
        return self::form($user, $data ? (object)$data : null);
    }

    /**
     * Edit Post
     *
     * @param array $data
     * @return mixed
     */
    public function edit(array $data)
    {
        // check entries
        $id = htmlspecialchars($data['id']);
        if (!empty($id) && $id !== "false") {
            $created = $this->update($data, array('id'=>$id));
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
     * @param $id: post id
     * @return array|bool
     */
    public function getInfo($id)
    {
        $data = $this->get(array('id'=>$id));
        if (!empty($data)) {
            $this->map($data);
            $data['day'] = date('Y-m-d', strtotime($data['date']));
            $data['time'] = date('H:i', strtotime($data['date']));
            $data['link'] = $this->getUploads();
            return $data;
        } else {
            return false;
        }
    }

    /**
     * Display last news in digest email
     * @param null $username
     * @return mixed
     */
    public function makeMail($username = null)
    {
        $last = $this->getlastnews();
        if (empty($last)) {
            $content['body'] = "No news this week";
        } else {
            $last_news = new self($last[0]);
            $today = date('Y-m-d');
            if (date('Y-m-d', strtotime($last_news->date)) < date('Y-m-d', strtotime("$today - 7 days"))) {
                $last_news->content = "No recent news this week";
            }
            $content['body'] = $last_news->content;
        }

        $content['title'] = 'Last News';
        return $content;
    }

    /**
     * Get associated files
     */
    private function getUploads()
    {
        $upload = new Media();
        $link = $upload->getUploads($this->id, __CLASS__);
        return $link;
    }

    // MODEL //

    /**
     * Get the newest posts
     * @param int $limit: number of items
     * @return array|bool
     */
    public function getlastnews($limit = 3)
    {
        $sql = "SELECT id from {$this->tablename} ORDER BY date DESC LIMIT 0, {$limit}";
        $req = $this->db->sendQuery($sql);
        $data = array();
        while ($row = $req->fetch_assoc()) {
            $data[] = $row['id'];
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
    public function getLimited($category, $page, $pp, $order = 'date ASC')
    {
        $sql = "
            SELECT id
            FROM {$this->tablename}
            ORDER BY {$order} LIMIT $page, $pp";
        $req = $this->db->sendQuery($sql);
        $data = array();
        while ($row = $req->fetch_assoc()) {
            $data[] = $row{'id'};
        }
        return $data;
    }

    /**
     * Count total number of entries
     * @param null|string $id: category name
     * @return int
     */
    public function getCount($id = null)
    {
        $req = $this->db->sendQuery("SELECT * FROM {$this->tablename}");
        $data = array();
        while ($row = $req->fetch_assoc()) {
            $data[] = $row;
        }
        return count($data);
    }

    // VIEWS //

    /**
     * Render section
     *
     * @param array $content
     *
     * @return string
     */
    public static function formatSection(array $content)
    {
        return "<div class='post_edit_container'>{$content['content']}</div>";
    }

    /**
     * Render "Nothing to show" message
     * @return string
     */
    public static function nothing()
    {
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
    public static function display(array $data, $user_name, $limit = true)
    {
        $char_limit = 1000;
        $url = URL_TO_APP . 'index.php?page=news&show=' . $data['id'];
        $day = date('d M y', strtotime($data['date']));
        $txt_content = htmlspecialchars_decode($data['content']);
        $content = ($limit && strlen($txt_content) > $char_limit) ?
                        substr($txt_content, 0, $char_limit) . "..." : $txt_content;
        $show_more = ($limit && strlen($txt_content) > $char_limit) ?
                        "<span class='blog_more'><a href='{$url}'>"._('Show more')."</a></span>" : null;
        return "
            <div style='width: 100%; box-sizing: border-box; padding: 0; margin: 10px auto 0 auto; 
            background-color: rgba(255,255,255,1); 
            border: 1px solid; border-color: #e5e6e9 #dfe0e4 #d0d1d5;'>
                <div style='width: 100%; min-height: 20px; line-height: 20px; padding: 5px; margin: 0; 
                text-align: left; font-size: 15px; font-weight: bold;'><a href='{$url}'>{$data['title']}</a></div>
                <div style='width: 60%; min-width: 300px; box-sizing: border-box; height: 5px; 
                border-bottom: 1px solid #d8d8d8'></div>

                <div style='text-align: left; margin: auto; background-color: white; padding: 10px;'>
                    {$content}
                    {$show_more}
                </div>
                <table style='position:relative; width: 100%; padding: 2px 10px 2px 10px; 
                background-color: rgba(50,50,50,1); margin: auto; text-align: right; color: #ffffff; font-size: 13px;'>
                    <tr>
                    <td class='news_time_container'>
                        {$day} at {$data['time']}
                    </td>
                    <td style='text-align: right'>Posted by <span id='author_name'>{$user_name}</span></td>
                    /<tr>
                </table>
            </div>";
    }

    /**
     * Render selection list
     * @param array $data
     * @return string
     */
    public static function selectionList(array $data)
    {
        $options = "";
        foreach ($data as $key => $item) {
            $day = date('d M y', strtotime($item['date']));
            $options .= "<option value='{$item['id']}'><b><strong>{$day}</strong> |</b> {$item['title']}</option>";
        }
        return "
            <select class='select_post'>
                <option value='' selected disabled>Select a post to modify</option>
                {$options}
            </select>";
    }

    /**
     * render selection menu for edit page
     * @param array $data
     * @return string
     */
    public static function selectionMenu(array $data)
    {
        $content = null;
        foreach ($data as $key => $item) {
            $day = date('d M y', strtotime($item['date']));
            $url = Router::buildUrl(
                self::getClassName(),
                'getForm',
                array('id'=>$item['id'])
            );
            $content .= "
                <div class='table_container'>
                    <div class='list-container-row news-details el_to_del' id='{$item['id']}'>
                        <div>{$day}</div>
                        <div>{$item['username']}</div>
                        <div>{$item['title']}</div>
                        <div class='action_cell'>
                        
                            <!-- Edit button -->
                            <div class='action_icon'>
                                <a class='loadContent' data-url='{$url}' 
                                    data-destination='.post_edit_target#post_{$item['id']}'>
                                    <img src='" . URL_TO_IMG . 'edit.png' . "' />
                                </a>
                            </div>
                            
                            <!-- Delete button -->
                            <div class='action_icon'>
                                <a href='' class='delete' data-controller='" . __CLASS__ . "' data-id='{$item['id']}'>
                                    <img src='" . URL_TO_IMG . 'trash.png' . "' />
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='list-container-row post_edit_target' id='post_{$item['id']}'></div>
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
     * Render add news section
     *
     * @param null $options
     * @return string
     */
    public static function addNewsSection($options = null)
    {
        $url = Router::buildUrl(
            self::getClassName(),
            'getForm',
            array(
                'destination'=>'.post_edit_target#main')
        );
        return "
                <section>
                    <h2>Post a news</h2>
                    <div class='section_content'>
                        <div class='action_btns'>
                            <input type='button' id='submit' class='loadContent' data-url='{$url}' 
                            data-destination='.post_edit_target#main' value='Add a news'/>
                        </div>
                        <div class='post_edit_target' id='main'></div>
                        <div class='feedback'></div>
                        {$options}
                    </div>
                </section>
        ";
    }

    /**
     * Show post form
     * @param Users $user
     * @param null|\StdClass $Post
     * @return mixed
     */
    public static function form(Users $user, $Post = null)
    {
        if (is_null($Post)) {
            $Post = new self();
            $del_btn = "";
        } else {
            $del_btn = "<input type='button' class='delete' data-controller='Posts' id='submit' 
            data-id='$Post->id' value='Delete'/>";
        }

        $result['content'] = "
            <form method='post' action='php/router.php?controller=Posts&action=edit'>
                <div class='submit_btns'>
                    $del_btn
                    <input type='submit' class='processform'/>
                </div>
                <input type='hidden' name='id' value='$Post->id'>
                <input type='hidden' name='username' value='$user->username'/>

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

        $result['title'] = "Add/Edit news";
        $result['description'] = self::description();
        return $result;
    }

    /**
     * Render add news section
     *
     * @param array $data
     * @return string
     */
    private static function formSection(array $data)
    {
        return "
            <section>
                <h2>Post a news</h2>
                <div class='section_content'>
                 {$data['content']}
                </div>
            </section>
        ";
    }

    /**
     * Submission form instruction
     * @return string
     */
    public static function description()
    {
        return "";
    }
}
