<?php

/**
 * Created by PhpStorm.
 * User: Florian
 * Date: 23/02/2017
 * Time: 08:19
 */
class Suggestion extends AppTable {

    protected $table_data = array(
        "id" => array("INT NOT NULL AUTO_INCREMENT", false),
        "id_pres" => array("BIGINT(15)", false),
        "up_date" => array("DATETIME", false),
        "username" => array("CHAR(255) NOT NULL"),
        "type" => array("CHAR(30)", false),
        "keywords" => array("CHAR(255)", false),
        "title" => array("CHAR(150)", false),
        "authors" => array("CHAR(150)", false),
        "summary" => array("TEXT(5000)", false),
        "notified" => array("INT(1) NOT NULL", 0),
        "vote" => array("INT(10) NOT NULL", 0),
        "primary" => "id"
    );

    public $id;
    public $id_pres;
    public $type;
    public $up_date;
    public $username;
    public $title;
    public $authors;
    public $summary;
    public $link = array();
    public $notified = 0;
    public $vote;
    public $keywords;

    /**
     * Constructor
     */
    function __construct(){
        parent::__construct("Suggestion", $this->table_data);
    }

    /**
     * Add a presentation to the database
     * @param array $post
     * @return bool|string
     */
    public function add_suggestion(array $post){
        $class_vars = get_class_vars(get_called_class());
        if ($this->is_exist(array('title'=>$post['title'])) === false) {

            $post['id_pres'] = $this->generateID('id_pres');
            $post['up_date'] = date('Y-m-d h:i:s');

            // Associates this presentation to an uploaded file if there is one
            if (!empty($post['link'])) {
                $media = new Media();
                $media->add_upload(explode(',', $post['link']), $post['id_pres'], 'Suggestion');
            }

            // Add publication to the database
            if ($this->db->addcontent($this->tablename, $this->parsenewdata($class_vars, $post, array("link")))) {
                return $post['id_pres'];
            } else {
                return false;
            }
        } else {
            return "exist";
        }
    }

    /**
     * Delete a presentation
     * @param $pres_id
     * @return bool
     */
    public function delete_pres($pres_id) {
        $this->getInfo($pres_id);

        // Delete corresponding file
        $uploads = new Uploads();
        $uploads->delete_files($this->id_pres);

        // Delete corresponding entry in the publication table
        return $this->delete(array('id_pres'=>$pres_id));
    }

    /**
     * Render submission editor
     * @param array|null $post
     * @return array
     */
    public function editor(array $post=null) {
        $post = (is_null($post)) ? $_POST : $post;

        $id_Presentation = $post['id'];
        if (!isset($_SESSION['username'])) {
            $_SESSION['username'] = false;
        }
        $operation = (!empty($post['operation']) && $post['operation'] !== 'false') ? $post['operation'] : null;
        $type = (!empty($post['type']) && $post['type'] !== 'false') ? $post['type'] : null;

        $user = new User($_SESSION['username']);
        $destination = (!empty($post['destination'])) ? $post['destination'] : null;

        if ($operation == 'selection_list') {
            $result['content'] = $this->generate_selectwishlist('.selection_container', $destination);
            $result['title'] = "Select a wish";
            $result['description'] = Suggestion::description("wishpick");
            return $result;
        } elseif ($operation == 'select') {
            $Suggestion = new Suggestion();
            $Suggestion->getInfo($id_Presentation);
            return Presentation::form($user, $Suggestion, 'edit', $operation, Session::getJcDates(1)[0]);
        } else {
            $this->getInfo($id_Presentation);
            return Suggestion::form($user, $this, $operation, $type);
        }
    }

    /**
     * Register into DigestMaker table
     */
    public static function registerDigest() {
        $DigestMaker = new DigestMaker();
        $DigestMaker->register('Suggestion');
    }

    /**
     * Get wish list
     * @param null $number: number of wishes to display
     * @return string
     */
    public function getWishList($number=null) {
        $limit = (is_null($number)) ? null : " LIMIT {$number}";
        $vote = new Vote();
        $bookmark = new Bookmark();
        $wish_list = null;
        $username = isset($_SESSION['username']) ? $_SESSION['username'] : null;
        foreach ($this->getAll($limit) as $key=>$item) {
            $vote_icon = $vote->getIcon($item['id_pres'], 'Suggestion', $username);
            $bookmark_icon = $bookmark->getIcon($item['id_pres'], 'Suggestion', $username);
            $wish_list .= self::inList((object)$item, $vote_icon, $bookmark_icon);
        }
        return (is_null($wish_list)) ? self::no_wish() : $wish_list;
    }

    /**
     * Generate wish list (select menu)
     * @param string $target: div in which the returned edit form will be loaded
     * @param string $destination: body or modal window
     * @return string
     */
    public function generate_selectwishlist($target='.submission', $destination='body') {

        $option = "<option disabled selected>Select a suggestion</option>";
        foreach ($this->getAll() as $key=>$item) {
            $option .= "<option value='{$item['id_pres']}'>{$item['authors']} | {$item['title']}</option>";
        }

        return self::select_menu($option, $target, $destination);
    }

    /**
     * Copy wishes from presentation table to suggestion table
     * @return bool: success or failure
     */
    public static function patch() {
        $self = new self();
        $Presentations = new Presentation();

        foreach ($Presentations->all(array('type' => 'wishlist')) as $key => $item) {
            $item['type'] = 'paper'; // Set type as paper by default
            if ($self->add_suggestion($item) === false) {
                return false;
            } else {
                $Presentations->delete(array('id_pres'=>$item['id_pres']));
            }
        }

        return true;
    }

    // MODEL

    /**
     * Get publication's information from the database
     * @param $id_pres
     * @return bool|array
     */
    public function getInfo($id_pres) {
        $sql = "SELECT p.*, u.fullname as fullname 
                FROM {$this->tablename} p
                LEFT JOIN ". AppDb::getInstance()->getAppTables('Users') . " u
                    ON u.username=p.username
                WHERE id_pres='{$id_pres}'";
        $data = $this->db->send_query($sql)->fetch_assoc();

        if (!empty($data)) {
            $this->map($data);

            // Get associated files
            $data['link'] = $this->get_uploads();
            return $data;
        } else {
            return false;
        }
    }


    /**
     * Fetch list of suggestions
     * @param null $limit
     * @param string $order
     * @param string $dir
     * @return array
     */
    public function getAll($limit=null, $order='count_vote', $dir='DESC') {
        $sql = "SELECT *, COUNT((v.ref_id)) as count_vote
                  FROM {$this->tablename} p 
                  LEFT JOIN " . AppDb::getInstance()->getAppTables('Users'). " u 
                    ON p.username = u.username
                  LEFT JOIN " . AppDb::getInstance()->getAppTables('Media') . " m
                    ON p.id_pres = m.presid
                  LEFT JOIN " . AppDb::getInstance()->getAppTables('Vote') . " v
                    ON v.ref_id = p.id_pres
                  GROUP BY id_pres
                  ORDER BY {$order} {$dir}" . $limit;
        $req = $this->db->send_query($sql);
        $data = array();
        while ($row = $req->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    /**
     * Get associated files
     */
    private function get_uploads() {
        $upload = new Uploads();
        $this->link = $upload->get_uploads($this->id_pres, 'Suggestion');
    }

    // VIEWS
    /**
     * Render suggestion in list
     * @param stdClass $item
     * @param null|string $vote
     * @param null|string $bookmark
     * @return string
     */
    public static function inList(stdClass $item, $vote=null, $bookmark=null) {
        $update = date('d M y',strtotime($item->up_date));
        $url = AppConfig::getInstance()->getAppUrl() . "index.php?page=submission&op=wishpick&id={$item->id_pres}";
        return "
        <div class='wish_container' id='{$item->id_pres}' style='display: block; position: relative; margin: 10px auto; 
        font-size: 0.9em; font-weight: 300; overflow: hidden; padding: 5px; border-radius: 5px;'>
            <div style='display: inline-block;font-weight: 600; color: #222222; vertical-align: top; font-size: 0.9em;'>
                {$update}
            </div>
            <div style='display: inline-block; margin-left: 20px; max-width: 70%;'>
               <div>
                   <a href='$url' class='leanModal show_submission_details' data-section='submission_form' data-controller='Suggestion' data-id='{$item->id_pres}'>
                        <div style='font-size: 16px;'>{$item->title}</div>
                        <div style='font-style: italic; color: #000000; font-size: 12px;'>Suggested by <span style='color: #CF5151; font-size: 14px;'>{$item->fullname}</span></div>
                   </a>
               </div>
            </div>
            <div class='tiny_icon_container'>
                {$vote}
                {$bookmark}            
            </div>
        </div>";
    }

    /**
     * Empty suggestion list
     * @return string
     */
    private static function no_wish() {
        return "<p>Were you looking for suggestions? Sorry, there is none yet.</p>";
    }

    /**
     * Render select menu
     * @param $option
     * @param string $target
     * @param string $destination
     * @return string
     */
    private static function select_menu($option, $target='.submission', $destination='body') {
        return "
          <form method='post' action='php/form.php'>
              <input type='hidden' name='page' value='presentations'/>
              <input type='hidden' name='op' value='wishpick'/>
              <div class='form-group field_auto' style='margin: auto; width: 250px;'>
                <select name='id' id='select_wish' data-target='{$target}' data-destination='{$destination}'>
                    {$option}
                </select>
                <label for='id'>Select a suggestion</label>
              </div>
          </form>
          <div class='selection_container'></div> ";
    }

    /**
     * Generate submission form and automatically fill it up with data provided by Presentation object.
     * @param User $user
     * @param null|Suggestion $Presentation
     * @param string $submit
     * @param bool $type
     * @return array
     */
    public static function form(User $user, Suggestion $Presentation=null, $submit="edit", $type=null) {
        if (is_null($Presentation)) {
            $Presentation = new self();
        }

        // Submission type
        $type = (is_null($type)) ? $type : $Presentation->type;
        if (empty($type)) $type = 'paper';

        // Presentation ID
        $idPres = ($Presentation->id_pres != "") ? $Presentation->id_pres : 'false';

        // Make submission's type selection list
        $type_options = Session::presentation_type();
        // Text of the submit button
        $form = ($submit !== "wishpick") ? "
            <div class='feedback'></div>
            <div class='form_container'>
                <div class='form_aligned_block matched_bg'>
                    <div class='form_description'>
                        Upload files attached to this presentation
                    </div>
                    " . Media::uploader($Presentation->link) . "
                </div>
                
                <form method='post' action='php/form.php' enctype='multipart/form-data' id='submit_form'>
                    
                    <div class='form_aligned_block matched_bg'>
                        
                        <div class='form_description'>
                            Select a presentation type
                        </div>
                        <div class='form-group'>
                            <select class='change_pres_type' name='type' id='type' required>
                                {$type_options['options']}
                            </select>
                            <label>Type</label>
                        </div>
                        
                    </div>
                
                    <div class='form_lower_container'>
                        " . Presentation::get_form_content($Presentation, $submit) . "
                    </div>
                    <div class='submit_btns'>
                        <input type='submit' name='$submit' class='submit_pres processform'>
                        <input type='hidden' name='$submit' value='true'/>
                        <input type='hidden' name='username' value='$user->username'/>
                        <input type='hidden' id='id_pres' name='id_pres' value='{$idPres}'/>
                    </div>
                </form>
            </div>
        ":"";

        if ($submit == 'suggest') {
            $result['title'] = "Add a suggestion";
        } elseif ($submit == "edit") {
            $result['title'] = "Edit suggestion";
        } elseif ($submit == "wishpick") {
            $result['title'] = "Select a wish";
        }
        $result['content'] = "
            <div class='submission'>
                $form
            </div>
            ";
        $result['description'] = self::description($submit);
        return $result;
    }

    /**
     * Submission form instruction
     * @param $type
     * @return null|string
     */
    public static function description($type) {
        $result = null;
        switch ($type) {
            case "suggest":
                $result = "
                Here you can suggest a paper that somebody else could present at a Journal Club session.
                Fill in the form below and that's it! Your suggestion will immediately appear in the wishlist.<br>
                If you want to edit or delete your submission, you can find it on your <a href='index.php?page=member/profile'>profile page</a>!
                ";
                break;
            case "wishpick":
                $result = "
                Here you can choose a suggested paper from the wishlist that you would like to present.<br>
                The form below will be automatically filled in with the data provided by the user who suggested the selected paper.
                Check that all the information is correct and modify it if necessary, choose a date to present and it's done!<br>
                If you want to edit or delete your submission, you can find it on your <a href='index.php?page=member/profile'>profile page</a>!
                ";
                break;

        }
        return $result;

    }

    /**
     * Render download menu
     * @param array $links
     * @param bool $email
     * @return array
     */
    private static function download_menu(array $links, $email=false) {
        $content = array();
        if (!empty($links)) {
            if ($email) {
                // Show files list as a drop-down menu
                $content['button'] = "<div class='dl_btn pub_btn icon_btn'>
                    <img src='".AppConfig::$site_url."images/download.png'></div>";
                $menu = null;
                foreach ($links as $file_id=>$info) {
                    $menu .= "
                        <div class='dl_info'>
                            <div class='dl_type'>".strtoupper($info['type'])."</div>
                            <div class='link_name dl_name' id='".$info['filename']."'>$file_id</div>
                        </div>";
                }
                $content['menu'] .= "<div class='dlmenu'>{$menu}</div>";
            } else {
                // Show files list as links
                $menu = null;
                foreach ($links as $file_id=>$info) {
                    $url_link = AppConfig::$site_url."uploads/".$info['filename'];
                    $menu .= "
                    <div style='display: inline-block; text-align: center; padding: 5px 10px 5px 10px;
                                margin: 2px; cursor: pointer; background-color: #bbbbbb; font-weight: bold;'>
                        <a href='$url_link' target='_blank' style='color: rgba(34,34,34, 1);'>".strtoupper($info['type'])."</a>
                    </div>";
                }
                $content['menu'] = "<div style='display: block; text-align: justify; width: 95%; min-height: 20px; 
                    height: auto; margin: auto; border-top: 1px solid rgba(207,81,81,.8);'>{$menu}</div>";
            }
        } else {
            $content['button'] = "<div style='width: 100px'></div>";
            $content['menu'] = null;
        }
        return $content;
    }

    /**
     * Display presentation details.
     * @param array $data: presentation information
     * @param bool $show : show buttons (true)
     * @return string
     */
    public static function details(array $data, $show=false) {

        $dl_menu = (!is_null($data['link'])) ? self::download_menu($data['link'], $show) : null;
        $file_div = $show ? $dl_menu['menu'] : null;

        // Add a delete link (only for admin and organizers or the authors)
        if ($show) {
            $delete_button = "<div class='pub_btn icon_btn'><a href='#' data-id='{$data['id_pres']}' data-controller='Suggestion' class='delete_ref'>
                <img src='".AppConfig::$site_url."images/trash.png'></a></div>";
            $modify_button = "<div class='pub_btn icon_btn'><a href='#' data-id='{$data['id_pres']}' class='modify_ref' data-controller='Suggestion'>
                <img src='".AppConfig::$site_url."images/edit.png'></a></div>";
        } else {
            $delete_button = "<div style='width: 100px'></div>";
            $modify_button = "<div style='width: 100px'></div>";
        }

        $type = ucfirst($data['type']);
        $result = "
        <div class='pub_caps' itemscope itemtype='http://schema.org/ScholarlyArticle'>
            <div style='display: block; position: relative; float: right; margin: 0 auto 5px 0; text-align: center; height: 20px; line-height: 20px; width: 100px; background-color: #555555; color: #FFF; padding: 5px;'>
                {$type}
            </div>
            <div id='pub_title' style='font-size: 1.1em; font-weight: bold; margin-bottom: 10px; display: inline-block;' itemprop='name'>{$data['title']}</div>
            <div id='pub_orator'>
                <span style='color:#CF5151; font-weight: bold;'>Suggested by: </span>{$data['fullname']}
            </div>
            <div id='pub_authors' itemprop='author'><span style='color:#CF5151; font-weight: bold;'>Authors: </span>{$data['authors']}</div>
        </div>

        <div class='pub_abstract'>
            <span style='color:#CF5151; font-weight: bold;'>Abstract: </span>{$data['summary']}
        </div>

        <div class='pub_action_btn'>
            <div class='pub_one_half'>
                {$dl_menu['button']}
                {$dl_menu['menu']}
            </div>
            <div class='pub_one_half last'>
                {$delete_button}
                {$modify_button}
            </div>
        </div>
        {$file_div}
        ";
        return $result;
    }

}