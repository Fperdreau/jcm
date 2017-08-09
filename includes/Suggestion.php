<?php

/**
 * Created by PhpStorm.
 * User: Florian
 * Date: 23/02/2017
 * Time: 08:19
 */
class Suggestion extends BaseModel {

    public $id;
    public $id_pres;
    public $type;
    public $up_date;
    public $username;
    public $title;
    public $authors;
    public $summary;
    public $link;
    public $notified;
    public $vote;
    public $keywords;

    /**
     * Constructor
     */
    function __construct(){
        parent::__construct();
    }

    /**
     * Render suggestion index page
     * @param null $id
     * @return string
     */
    public function index($id=null) {
        if (!is_null($id)) {
            if (isset($_SESSION['username'])) {
                $user = new Users($_SESSION['username']);
            } elseif (!empty($_POST['Users'])) {
                $user = new Users($_POST['Users']);
            } else {
                $user = false;
            }

            $data = $this->getInfo(htmlspecialchars($_POST['id']));
            $show = $user !== false && (in_array($user->status, array('organizer', 'admin')) || $data['orator'] === $user->username);
            if ($show && isset($_POST['operation']) && $_POST['operation'] === 'edit') {
                $content = $this->get_form('body');
            } else {
                $content = $this->show_details($_POST['id'], 'body');
            }
        } else {
            $content = "Nothing to show here";
        }

        return self::container($content);

    }

    /**
     * Add a presentation to the database
     * @param array $post
     * @return bool|string
     */
    public function add_suggestion(array $post){
        if ($this->is_exist(array('title'=>$post['title'])) === false) {

            $post['id_pres'] = $this->generateID('id_pres');
            $post['up_date'] = date('Y-m-d h:i:s');

            // Associates this presentation to an uploaded file if there is one
            if (!empty($post['link'])) {
                $media = new Media();
                $media->add_upload(explode(',', $post['link']), $post['id_pres'], __CLASS__);
            }

            $content = $this->parsenewdata(get_class_vars(get_called_class()), $post, array("link"));
            // Add publication to the database
            if ($this->db->insert($this->tablename, $content)) {
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
        $uploads = new Media();
        $uploads->delete_files($pres_id, __CLASS__);

        // Delete corresponding entry in the publication table
        return $this->delete(array('id_pres'=>$pres_id));
    }

    /**
     * Alias for editor()
     * @return array|string
     */
    public function get_suggestion_list($view) {
        $_POST['operation'] = 'selection_list';
        if ($view === "body") {
            return self::format_section($this->editor($_POST, $view));
        } else {
            return $this->editor($_POST, $view);
        }
    }

    /**
     * Update information
     * @param array $data
     * @param array $id
     * @return bool
     */
    public function update(array $data, array $id) {
        // Associates this presentation to an uploaded file if there is one
        if (!empty($data['link'])) {
            $media = new Media();
            if (!$media->add_upload(explode(',', $data['link']), $data['id_pres'], __CLASS__)) {
                return false;
            }
        }
        $content = $this->parsenewdata(get_class_vars(get_called_class()), $data, array("link"));
        return $this->db->update($this->tablename, $content, $id);
    }

    /**
     * Edit Presentation
     * @param array $data
     * @return mixed
     */
    public function edit(array $data) {
        // check entries
        $id_pres = htmlspecialchars($data['id_pres']);

        // IF not a guest presentation, the one who posted is the planned speaker
        if ($data['type'] !== "guest") {
            $data['orator'] = $_SESSION['username'];
        }

        if ($id_pres !== "false") {
            $created = $this->update($data, array('id_pres'=>$id_pres));
        } else {
            $created = $this->add_suggestion($data);
        }

        $result['status'] = $created !== false;
        if ($created === false) {
            $result['msg'] = 'Oops, something went wrong';
        } elseif ($created === 'exist') {
            $result['msg'] = "Sorry, a suggestion with a similar title already exists in our database.";
        } else {
            $result['msg'] = "Thank you for your suggestion!";
        }

        return $result;
    }

    /**
     * Select suggestion to present it
     * @param array $data
     * @return array: returns status and msg
     */
    public function select(array $data) {
        $id_pres = $data['id_pres'];
        $data['id_pres'] = "false";
        $Presentation = new Presentation();
        $result = $Presentation->edit($data);
        if ($result['status'] == true) {
            $result['status'] = $this->delete_pres($id_pres);
        }
        return $result;
    }

    /**
     * Render submission editor
     * @param array|null $post
     * @param string $view
     * @return array
     */
    public function editor(array $post=null, $view='body') {
        $post = (is_null($post)) ? $_POST : $post;
        $operation = (!empty($post['operation']) && $post['operation'] !== 'false') ? $post['operation'] : null;
        $type = (!empty($post['type']) && $post['type'] !== 'false') ? $post['type'] : null;
        $id = isset($post['id']) ? $post['id'] : false;
        $user = new Users($_SESSION['username']);
        $destination = (!empty($post['destination'])) ? $post['destination'] : null;
        if ($operation === 'selection_list') {
            $result['content'] = $this->generate_selectwishlist($destination, $view);
            $result['title'] = "Select a wish";
            $result['description'] = Suggestion::description("wishpick");
            return $result;
        } elseif ($operation === 'select') {
            $Suggestion = new Suggestion();
            $Suggestion->getInfo($id);
            $Session = new Session();
            $next = $Session->getNext(1);
            return Presentation::form($user, $Suggestion, 'select', $operation, array(
                    'date'=>$next[0]['date'],
                    'session_id'=>$next[0]['id'],
                    'controller'=>__CLASS__)
            );

        } elseif ($operation === 'edit') {
            $this->getInfo($id);
            return Suggestion::form($user, $this, $operation, $type);
        } else {
            return null;
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
        $wish_list = null;
        $username = isset($_SESSION['username']) ? $_SESSION['username'] : null;

        $Vote = new Vote();
        $Bookmark = new Bookmark();

        foreach ($this->getAll($limit) as $key=>$item) {
            $vote_icon = $Vote->getIcon($item['id_pres'], 'Suggestion', $username);
            $bookmark_icon = $Bookmark->getIcon($item['id_pres'], 'Suggestion', $username);
            $wish_list .= self::inList((object)$item, $vote_icon, $bookmark_icon);
        }

        $wish_list = is_null($wish_list) ? self::no_wish() : $wish_list;
        $add_button = Auth::is_logged() ? self::add_button() : null;

        return $add_button . $wish_list;
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

    /**
     * Show suggestion details
     * @param bool $id: suggestion unique id
     * @param string $view: requested view
     * @return mixed: view
     */
    public function show_details($id=false, $view='body') {
        $data = $this->getInfo($id);
        $user = Auth::is_logged() ? new Users($_SESSION['username']) : null;
        $show = !is_null($user) && (in_array($user->status, array('organizer', 'admin'))
                || $data['username'] === $user->username);
        if ($data !== false) {
            return self::details($data, $show, $view);
        } else {
            if ($view === 'body') {
                return  self::not_found();
            } else {
                return array(
                    'content'=> self::not_found(),
                    'title'=> 'Not found',
                    'buttons'=>null,
                    'id'=>'suggestion'
                );
            }
        }
    }

    /**
     * Get submission form
     * @param string $view
     * @return mixed
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
     * Renders digest section (called by DigestMaker)
     * @param null|string $username
     * @return mixed
     */
    public function makeMail($username=null) {
        $content['body'] = $this->getWishList(4);
        $content['title'] = "Wish list";
        return $content;
    }

    /**
     * Get Presentation types
     * @return array
     */
    public function getTypes() {
        $Presentation = new Presentation();
        if (!empty($Presentation->getSettings('types'))) {
            return $Presentation->getSettings('types');
        } else {
            return $Presentation->getSettings('defaults');
        }
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
                LEFT JOIN ". Db::getInstance()->getAppTables('Users') . " u
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
                  LEFT JOIN " . Db::getInstance()->getAppTables('Users'). " u 
                    ON p.username = u.username
                  LEFT JOIN " . Db::getInstance()->getAppTables('Media') . " m
                    ON p.id_pres = m.presid
                  LEFT JOIN " . Db::getInstance()->getAppTables('Vote') . " v
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
        $upload = new Media();
        $this->link = $upload->get_uploads($this->id_pres, 'Suggestion');
        return $this->link;
    }

    // VIEWS

    /**
     * Render "Add" button
     * @return string
     */
    private static function add_button() {
        return "
            <div>
                <a href='" . App::getAppUrl() . 'index.php?page=submission&op=suggest' . "' 
                            class='leanModal' data-controller='Suggestion' data-action='get_form'
                            data-params='modal' data-operation='edit' data-section='suggestion'>
                    <input type='submit' value='Add' />
                </a>
            </div>
        ";
    }

    /**
     * @param array $content
     * @return string
     */
    public static function format_section(array $content) {
        return "
        <section id='suggestion_form'>
            <h2>{$content['title']}</h2>
            <p class='page_description'>{$content['description']}</p>       
            <div class='section_content'>{$content['content']}</div>
        </section>
        ";
    }

    /**
     * Render keywords list
     * @param string $keywords: list of keywords (comma-separated)
     * @return null|string
     */
    private static function keywords_list($keywords) {
        $content = null;
        if (!empty($keywords)) {
            foreach (explode(',', $keywords) as $keyword) {
                $content .= "<div>{$keyword}</div>";
            }
        }
        return "<div class='keywords_container'>{$content}</div>";
    }

    /**
     * Render suggestion in list
     * @param stdClass $item
     * @param null|string $vote
     * @param null|string $bookmark
     * @return string
     */
    public static function inList(stdClass $item, $vote=null, $bookmark=null) {
        $update = date('d M y', strtotime($item->up_date));
        $url = App::getAppUrl() . "index.php?page=suggestion&id={$item->id_pres}";
        $keywords = self::keywords_list($item->keywords);

        return "
        <div class='suggestion_container' id='{$item->id_pres}''>
            <div class='suggestion_date'>
                {$update}
            </div>
            <div class='suggestion_details_container'>
                <div class='suggestion_details'>
                   <a href='$url' class='leanModal' data-controller='Suggestion' data-action='show_details' 
                   data-section='suggestion' data-params='{$item->id_pres},modal' data-id='{$item->id_pres}'>
                        <div style='font-size: 16px;'>{$item->title}</div>
                        <div style='font-style: italic; color: #000000; font-size: 12px;'>Suggested by <span style='color: #CF5151; font-size: 14px;'>{$item->fullname}</span></div>
                   </a>
                </div>
                {$keywords}
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
                <select name='id' id='select_wish' data-controller='Suggestion' data-action='get_form' 
                data-operation='select' data-section='suggestion' data-params='{$destination}' 
                data-destination='{$target}'>
                    {$option}
                </select>
                <label for='id'>Select a suggestion</label>
              </div>
          </form>
          <div class='selection_container'></div> ";
    }

    /**
     * Generate submission form and automatically fill it up with data provided by Presentation object.
     * @param Users $user
     * @param null|Suggestion $Suggestion
     * @param string $submit
     * @param bool $type
     * @return array
     */
    public static function form(Users $user, Suggestion $Suggestion=null, $submit="edit", $type=null) {
        if (is_null($Suggestion)) {
            $Suggestion = new self();
        }

        // Get class of instance
        $controller = get_class($Suggestion);

        // Presentation ID
        $idPres = ($Suggestion->id_pres != "") ? $Suggestion->id_pres : 'false';

        // Make submission's type selection list
        $Presentation = new Presentation();
        $type_options = $Presentation::renderTypes(
            $Presentation->getSettings('types'),
            $Presentation->getSettings('default_type'),
            array('minute')
        );

        // Download links
        $links = !is_null($Suggestion->link) ? $Suggestion->link : array();

        // Text of the submit button
        $form = ($submit !== "wishpick") ? "
            <div class='feedback'></div>
            <div class='form_container'>
                <div class='form_aligned_block matched_bg'>
                    <div class='form_description'>
                        Upload files attached to this presentation
                    </div>
                    " . Media::uploader($links, 'suggestion_form', __CLASS__) . "
                </div>
                
                <form method='post' action='php/form.php' enctype='multipart/form-data' id='suggestion_form'>
                    
                    <div class='form_aligned_block matched_bg'>
                        
                        <div class='form_description'>
                            Select a presentation type
                        </div>
                        <div class='form-group'>
                            <select class='change_pres_type' name='type' id='{$controller}_{$idPres}' required>
                                {$type_options['options']}
                            </select>
                            <label>Type</label>
                        </div>
                        
                    </div>
                
                    <div class='form_lower_container'>
                        <div class='special_inputs_container'>
                        " . Presentation::get_form_content($Suggestion, $type) . "
                        </div>
                        
                        <div class='form-group'>
                            <input type='text' id='keywords' name='keywords' value='$Suggestion->keywords'>
                            <label>Keywords (comma-separated)</label>
                        </div>
                        
                        <div class='form-group'>
                            <label>Abstract</label>
                            <textarea name='summary' id='summary' class='wygiwym' placeholder='Abstract (5000 characters maximum)' 
                            style='width: 90%;' required>$Suggestion->summary</textarea>
                        </div>
                    </div>
                    <div class='submit_btns'>
                        <input type='submit' name='$submit' class='submit_pres'>
                        <input type='hidden' name='controller' value='". __CLASS__ . "'>
                        <input type='hidden' name='operation' value='{$submit}'/>
                        <input type='hidden' name='process_submission' value='true'/>
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
            case "edit":
                $result = "
                Here you can edit a suggestion.
                ";
                break;

        }
        return $result;

    }

    /**
     * Display presentation details.
     * @param array $data : presentation information
     * @param bool $show : show buttons (true)
     * @param string $view: requested view ('modal' or 'body')
     * @return string
     */
    public static function details(array $data, $show=false, $view='modal') {

        $dl_menu = Media::download_menu($data['link'], $show);
        $file_div = $show ? $dl_menu['menu'] : null;
        $destination = $view === 'modal' ? '#suggestion' : '#suggestion_container';
        $trigger = $view == 'modal' ? 'leanModal' : 'loadContent';

        // Add a delete link (only for admin and organizers or the authors)
        if ($show) {
            $delete_button = "<div class='pub_btn icon_btn'><a href='#' data-id='{$data['id']}' class='delete'
                data-controller='Suggestion' data-action='delete'>
                <img src='".App::getAppUrl()."images/trash.png'></a>
                </div>";
            $modify_button = "<div class='pub_btn icon_btn'><a href='#' class='{$trigger}' data-controller='Suggestion' 
                data-action='get_form' data-section='suggestion' data-params='{$view}' data-id='{$data['id_pres']}' 
                data-operation='edit' data-destination='{$destination}'>
                <img src='".App::getAppUrl()."images/edit.png'></a>
                </div>";
        } else {
            $delete_button = "<div style='width:100px'></div>";
            $modify_button = "<div style='width:100px'></div>";
        }

        // Suggestion type
        $type = ucfirst($data['type']);
        $type_in_body = $view !== 'modal' ? "<div class='pub_type'>{$type}</div>" : null;

        // Present button
        $present_button = (Auth::is_logged()) ? "<div>
            <input type='submit' class='{$trigger}' value='Present it' data-controller='Suggestion' 
            data-action='get_form' data-params='{$view}' data-section='select_suggestion' data-id='{$data['id_pres']}' 
            data-view='{$view}' data-destination='{$destination}' data-operation='select'/>
        </div>" : null;

        // Action buttons
        $buttons = "
            <div class='first_half'>
                {$present_button}
            </div>
            <div class='last_half'>
                {$delete_button}
                {$modify_button}
            </div>
        ";

        // Header
        $header = "
            <span style='color: #222; font-weight: 900;'>Suggestion</span>
            <span style='color: rgba(207,81,81,.5); font-weight: 900; font-size: 20px;'> . </span>
            <span style='color: #777; font-weight: 600;'>{$type}</span>
        ";

        $buttons_body = $view !== 'modal' ? "
           <div class='first_half'>
               {$present_button}
           </div>
            <div class='last_half'>
                {$delete_button}
                {$modify_button}
            </div>" : null;


        $result = "
        <div class='pub_caps' itemscope itemtype='http://schema.org/ScholarlyArticle'>
            <div class='pub_title' itemprop='name'>{$data['title']}</div>
            {$type_in_body}
            <div class='pub_authors' itemprop='author'>{$data['authors']}</div>
            <div class='pub_orator'>
                <span style='color:#CF5151; font-weight: bold;'>Suggested by: </span>{$data['fullname']}
            </div>
        </div>

        <div class='pub_abstract'>
            <span style='color:#CF5151; font-weight: bold;'>Abstract: </span>{$data['summary']}
        </div>
        
        ". self::keywords_list($data['keywords']) . " 

        {$file_div}

        <div class='pub_action_btn'>
            {$buttons_body}
        </div>
        ";

        if ($view === 'body') {
            return $result;
        } else {
            return array(
                'content'=>$result,
                'title'=>$header,
                'buttons'=>$buttons,
                'id'=>'suggestion'
            );
        }
    }

    /**
     * Render error message (not found)
     * @return string
     */
    public static function not_found() {
        return "
            <section>
                <div class='section_content'>
                    <div style='color: rgb(105,105,105); font-size: 50px; text-align: center; font-weight: 600; margin-bottom: 20px;'>Oops</div>
                    <div style=\"color: rgb(105,105,105); text-align: center; font-size: 1.2em; font-weight: 400;\">
                        If you were looking for the answer to the question:
                        <p style='font-size: 1.4em; text-align: center;'>What is the universe?</p>
                        We can tell you it is 42.
                        <p>But since you were looking for a page that does not exist, then we must tell you:</p>
                        <p style='font-size: 2em; text-align: center;'>ERROR 404!</p>
                    </div>
                </div>
            </section>
            ";
    }

    /**
     * Render suggestion container
     * @param $content
     * @return string
     */
    private static function container($content) {
        return "<section><div class='section_content' id='suggestion_container'>{$content}</div></section>";
    }

}