<?php
/**
 * File for classes Presentations and Presentation
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
 * class Presentations.
 *
 * Handle methods to display presentations list (archives, homepage, wish list)
 */
class Presentation extends AppTable {

    protected $table_data = array(
        "id" => array("INT NOT NULL AUTO_INCREMENT", false),
        "up_date" => array("DATETIME", false),
        "id_pres" => array("BIGINT(15)", false),
        "username" => array("CHAR(30) NOT NULL"),
        "type" => array("CHAR(30)", false),
        "date" => array("DATE", false),
        "jc_time" => array("CHAR(15)", false),
        "title" => array("CHAR(150)", false),
        "authors" => array("CHAR(150)", false),
        "summary" => array("TEXT(5000)", false),
        "orator" => array("CHAR(50)", false),
        "notified" => array("INT(1) NOT NULL", 0),
        "session_id" => array("INT", false),
        "primary" => "id"
    );

    public $type = "";
    public $date = "1970-01-01";
    public $jc_time = "17:00,18:00";
    public $up_date = "1970-01-01 00:00:00";
    public $username = "";
    public $title = "";
    public $authors = "";
    public $summary = "";
    public $link = array();
    public $orator = "";
    public $chair = "TBA";
    public $notified = 0;
    public $id_pres = "";
    public $session_id;

    /**
     * Constructor
     */
    function __construct($id_pres=null){
        parent::__construct("Presentation", $this->table_data);
        /** @var AppConfig $config */
        $config = AppConfig::getInstance();
        $this->date = Session::getJcDates(1)[0]; // Set next planned session date as default
        if (!is_null($id_pres)) {
            $this->getInfo($id_pres);
        }
    }

    // CONTROLLER
    /**
     * Render suggestion index page
     * @param null $id
     * @return string
     */
    public function index($id=null) {
        if (!is_null($id)) {
            if (isset($_SESSION['username'])) {
                $user = new User($_SESSION['username']);
            } elseif (!empty($_POST['user'])) {
                $user = new User($_POST['user']);
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
    public function make(array $post){
        if ($post['title'] === "TBA" || $this->pres_exist($post['title']) === false) {
            // Create an unique ID
            $post['id_pres'] = $this->generateID('id_pres');

            // Upload datetime
            $post['up_date'] = date('Y-m-d h:i:s');

            // Associates this presentation to an uploaded file if there is one
            if (!empty($post['link'])) {
                $media = new Media();
                $media->add_upload(explode(',', $post['link']), $post['id_pres'], 'Presentation');
            }

            $content = $this->parsenewdata(get_class_vars(get_called_class()), $post, array("link","chair"));
            // Add publication to the database
            if ($this->db->addcontent($this->tablename,$content)) {
                return $this->id_pres;
            } else {
                return false;
            }
        } else {
            return "exist";
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
            if (!$media->add_upload(explode(',', $data['link']), $data['id_pres'], 'Presentation')) {
                return false;
            }
        }
        $content = $this->parsenewdata(get_class_vars(get_called_class()), $data, array("link","chair"));
        return $this->db->updatecontent($this->tablename, $content, $id);
    }

    /**
     * Edit Presentation
     * @param array $data
     * @return mixed
     */
    public function edit(array $data) {
        // check entries
        $presid = htmlspecialchars($data['id_pres']);

        // IF not a guest presentation, the one who posted is the planned speaker
        if ($data['type'] !== "guest") {
            $data['orator'] = $_SESSION['username'];
        }

        if ($presid !== "false") {
            $created = $this->update($data, array('id_pres'=>$this->id_pres));
        } else {
            $created = $this->make($data);
        }

        $result['status'] = $created === true;
        if ($created === false) {
            $result['msg'] = 'Oops, something went wrong';
        } elseif ($created === 'exist') {
            $result['msg'] = "Sorry, a presentation with a similar title already exists in our database.";
        } else {
            $result['msg'] = "Thank you for your submission!";
        }

        return $result;
    }

    /**
     * Show suggestion details
     * @param bool $id: suggestion unique id
     * @param string $view: requested view
     * @return string: view
     */
    public function show_details($id=false, $view='body') {
        $data = $this->getInfo($id);
        $user = User::is_logged() ? new User($_SESSION['username']) : null;
        $show = !is_null($user) && (in_array($user->status, array('organizer', 'admin'))
                || $data['username'] === $user->username);
        if ($data !== false) {
            return self::details($data, $show, $view);
        } else {
            return self::not_found();
        }    }

    /**
     * Get submission form
     * @param string $view
     * @return string
     */
    public function get_form($view='body') {
        if ($view === "body") {
            return Presentation::format_section($this->editor($_POST));
        } else {
            $content = $this->editor($_POST);
            return array(
                'content'=>$content['content'],
                'id'=>'presentation',
                'buttons'=>null,
                'title'=>$content['title']);
        }
    }

    /**
     * Render list with all next user's presentations
     * @param $username: user name
     * @param string $filter: 'previous' or 'next'
     * @return null|string
     */
    public function getUserPresentations($username, $filter='next') {
        $content = null;
        $search = $filter == 'next' ? 'date >' : 'date <';
        foreach ($this->all(array('username'=>$username, $search=>'CURDATE()')) as $key=>$item) {
            $content .= $this->show($item['id_pres'], $username);
        }
        return $content;
    }

    /**
     * Get session id from presentation date
     * @param $date
     * @return mixed
     */
    private function get_session_id($date) {
        $session = new Session($date);
        return $session->id;
    }

    /**
     * Register into DigestMaker table
     */
    public static function registerDigest() {
        $DigestMaker = new DigestMaker();
        $DigestMaker->register('Presentations');
    }

    /**
     * Get user's publications list
     * @param string $username
     * @return array
     */
    public function getList($username) {
        return $this->all(array('username'=>$username));
    }

    /**
     * Get list of publications (sorted/archives page)
     * @param null $filter
     * @param null $user
     * @return string
     */
    public function getAllList($filter = NULL, $user = NULL) {
        $year_pub = $this->getByYears($filter,$user);
        if (empty($year_pub)) {
            return "Sorry, there is nothing to display here.";
        }

        $content = null;
        foreach ($year_pub as $year=>$list) {
            $year_content = null;
            foreach ($list as $id) {
                $year_content .= $this->show($id);
            }
            $content .= self::year_content($year, $year_content);

        }
        return $content;
    }

    /**
     * Update a presentation (new info)
     * @param array $post
     * @param null $id_pres
     * @return bool
     */
    public function modify($post=array(), $id_pres=null) {
        if (null!=$id_pres) {
            $this->id_pres = $id_pres;
        } elseif (array_key_exists('id_pres',$post)) {
            $this->id_pres = $_POST['id_pres'];
        }

        // Associate the new uploaded file (if there is one)
        if (array_key_exists("link", $post)) {
            $media = new Media();
            $media->add_upload(explode(',', $post['link']), $post['id_pres'], 'Presentation');
        }

        // Get presentation's type
        $this->type = (array_key_exists("type", $post)) ? $post['type']:$this->type;

        // Update table
        $class_vars = get_class_vars("Presentation");
        $content = $this->parsenewdata($class_vars,$post,array('link','chair'));
        if ($this->db->updatecontent($this->tablename,$content,array('id_pres'=>$this->id_pres))) {
            AppLogger::get_instance(APP_NAME, get_class($this))->info("Presentation ({$this->id_pres}) updated");
            return true;
        } else {
            AppLogger::get_instance(APP_NAME, get_class($this))->error("Could not update presentation ({$this->id_pres})");
            return false;
        }
    }

    /**
     * Get associated files
     */
    private function get_uploads() {
        $upload = new Media();
        $links = $upload->get_uploads($this->id_pres, 'Presentation');
        $this->link = $links;
        return $links;
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
     * Check if presentation exists in the database
     * @param $title
     * @return bool
     */
    private function pres_exist($title) {
        $titlelist = $this->db->column($this->tablename, 'title');
        return in_array($title,$titlelist);
    }

    /**
     * Show this presentation (in archives)
     * @param $id: presentation id
     * @param bool $profile : adapt the display for the profile page
     * @return string
     */
    public function show($id, $profile=false) {
        $data = $this->getInfo($id);
        if ($profile === false) {
            $speaker = new User($this->orator);
            $speakerDiv = "<div class='pub_speaker warp'>$speaker->fullname</div>";
        } else {
            $speakerDiv = "";
        }
        return self::show_in_list((object)$data, $speakerDiv);
    }

    /**
     * Generate years selection list
     * @return string
     */
    public function generateYearsList() {
        return self::yearsSelectionList($this->get_years());
    }

    // PATCH
    /**
     * Add session id to presentation info
     */
    public function patch_presentation_id() {
        foreach ($this->all() as $key=>$item) {
            $pres_obj = new Presentation($item['presid']);
            $pres_obj->update(array('session_id'=>$this->get_session_id($pres_obj->date)),
                array('id_pres'=>$item['presid']));
        }
    }

    /**
     * Patch upload table: add object name ('Presentation').
     */
    public static function patch_uploads() {
        $Publications = new self();
        $Media = new Media();
        foreach ($Publications->all() as $key=>$item) {
            if ($Media->is_exist(array('presid'=>$item['id_pres']))) {
                $Media->update(array('obj'=>'Presentation'), array('presid'=>$item['id_pres']));
            }
        }
    }

    /**
     *
     */
    public static function patch_session_id() {
        $Publications = new self();
        $Session = new Session();
        foreach ($Publications->all() as $key=>$item) {
            if ($Session->is_exist(array('date'=>$item['date']))) {
                $session_info = $Session->getInfo($item['date']);
                if (!$Publications->update(array('session_id'=>$session_info[0]['id']), array('id_pres'=>$item['id_pres']))) {
                    AppLogger::get_instance(APP_NAME, __CLASS__)->error('Could not update publication table with new session id');
                    return false;
                }
            }
        }
        return true;
    }

    // MODEL

    /**
     * Get latest submitted presentations
     * @return array
     */
    public function getLatest() {
        $publicationList = array();
        foreach ($this->all(array('notified'=>0, 'title !='=>'TBA')) as $key=>$item) {
            $publicationList[] = $item['id_pres'];
        }
        return $publicationList;
    }

    /**
     * Get publications by date
     * @param bool $excluded
     * @return array
     */
    public function getByDate($excluded=false) {
        // Get presentations dates
        $sql = "SELECT date,id_pres FROM $this->tablename";
        if ($excluded !== false) $sql .= " WHERE type!='$excluded'";
        $req = $this->db->send_query($sql);
        $dates = array();
        while ($row = mysqli_fetch_assoc($req)) {
            $dates[$row['date']][] = $row['id_pres'];
        }
        return $dates;
    }

    /**
     * Collect years of presentations present in the database
     * @return array
     */
    public function get_years() {
        $dates = $this->db->column($this->tablename, 'date', array('type'=>'wishlist'), array('!='));
        if (is_array($dates)) {
            $years = array();
            foreach ($dates as $date) {
                $formated_date = explode('-',$date);
                $years[] = $formated_date[0];
            }
            $years = array_unique($years);
        } else {
            $formated_date = explode('-',$dates);
            $years[] = $formated_date[0];
        }

        return $years;
    }

    /**
     * Get publication list by years
     * @param null $filter
     * @param null $username
     * @return array
     */
    public function getByYears($filter = NULL, $username = NULL) {
        $search = array(
            'title !='=>'TBA',
            'type !='=>'wishlist');
        if (!is_null($filter)) $search['YEAR(date)'] = $filter;
        if (!is_null($username)) $search['username'] = $username;

        $data = $this->db->select(
            $this->tablename,
            array('YEAR(date)', 'id_pres'),
            $search,
            'ORDER BY date DESC'
        );

        $years = array();
        foreach ($data as $key=>$item) {
            $years[$item['YEAR(date)']][] = $item['id_pres'];
        }
        return $years;
    }

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

    // VIEW

    /**
     * Render list of presentations for a specific year
     * @param $year
     * @param $data
     * @return string
     */
    private static function year_content($year, $data) {
        return "
        <section>
            <h2 class='section_header'>$year</h2>
            <div class='section_content'>
                <div class='table_container'>
                <div class='list-container list-heading'>
                    <div>Date</div>
                    <div>Title</div>
                    <div>Speakers</div>
                </div>
                {$data}
                </div>
            </div>
        </section>";

    }

    /**
     * Render presentation in list
     * @param stdClass $presentation
     * @param $speakerDiv
     * @return string
     */
    public static function show_in_list(stdClass $presentation, $speakerDiv) {
        $date = date('d M y', strtotime($presentation->date));
        return "
            <div class='pub_container' style='display: table-row; position: relative; box-sizing: border-box; font-size: 0.85em;  text-align: justify; margin: 5px auto; 
            padding: 0 5px 0 5px; height: 25px; line-height: 25px;'>
                <div style='display: table-cell; vertical-align: top; text-align: left; 
                min-width: 50px; font-weight: bold;'>$date</div>
                
                <div style='display: table-cell; vertical-align: top; text-align: left; 
                width: 60%; overflow: hidden; text-overflow: ellipsis;'>
                    <a href='" . URL_TO_APP . "index.php?page=presentation&id={$presentation->id_pres}" . "' 
                    class='leanModal' data-controller='Presentation' data-action='show_details' 
                    data-section='submission' data-params='{$presentation->id_pres},modal'>
                        $presentation->title
                    </a>
                </div>
                {$speakerDiv}
            </div>
        ";
    }

    /**
     * Render list of available speakers
     * @param string $cur_speaker: username of currently assigned speaker
     * @return string
     */
    public static function speakerList($cur_speaker=null) {
        $Users = new User();
        // Render list of available speakers
        $speakerOpt = (is_null($cur_speaker)) ? "<option selected disabled>Select a speaker</option>" : null;
        foreach ($Users->getAll() as $key=>$speaker) {
            $selectOpt = ($speaker['username'] == $cur_speaker) ? 'selected' : null;
            $speakerOpt .= "<option value='{$speaker['username']}' {$selectOpt}>{$speaker['fullname']}</option>";
        }
        return "<select class='modSpeaker select_opt' style='max-width: 150px;'>{$speakerOpt}</select>";
    }

    /**
     * Show editable publication information in session list
     * @param array $data
     * @return array
     */
    public static function inSessionEdit(array $data) {
        $view_button = "<a href='#' class='leanModal pub_btn icon_btn' data-controller='Presentation' 
            data-action='show_details' data-params='{$data['id_pres']},modal' data-section='submission' 
            data-title='Submission'><img src='" . URL_TO_IMG . 'view_bk.png' . "' /></a>";
        return array(
            "content"=>"  
                <div style='display: block !important;'>{$data['title']}</div>
                <div>
                    <span style='font-size: 12px; font-style: italic;'>Presented by </span>
                    <span style='font-size: 14px; font-weight: 500; color: #777;'>" . self::speakerList($data['username']) ."</span>
                </div>
                ",
            "name"=>$data['pres_type'],
            "button"=>$view_button
            );
    }

    /**
     * Render (clickable) presentation title.
     * @param array $data
     * @return string
     */
    private static function RenderTitle(array $data) {
        $url = URL_TO_APP . "index.php?page=presentation&id=" . $data['id_pres'];
        return "<a href='{$url}' class='leanModal' data-controller='Presentation' 
            data-action='show_details' data-params='{$data['id_pres']},modal' data-section='submission' 
            data-title='Submission'>{$data['title']}</a>";
    }

    /**
     * Render short description of presentation in session list
     * @param array $data
     * @return array
     */
    public static function inSessionSimple(array $data) {
        $show_but = self::RenderTitle($data);
        return array(
            "name"=>$data['pres_type'],
            "content"=>"
            <div style='display: block !important;'>{$show_but}</div>
            <div>
                <span style='font-size: 12px; font-style: italic;'>Presented by </span>
                <span style='font-size: 14px; font-weight: 500; color: #777;'>{$data['fullname']}</span>
            </div>",
            "button"=>Bookmark::getIcon($data['id_pres'], 'Presentation', User::is_logged() ? $_SESSION['username'] : null)
        );
    }

    /**
     * Show presentation details
     * @param array $data: presentation information
     * @param bool $show : show list of attached files
     * @return string
     */
    public static function mail_details(array $data, $show=false) {
        // Make download menu if required
        $file_div = $show ? Media::download_menu_email($data['link'], AppConfig::getInstance()->getAppUrl()) : null;

        // Format presentation's type
        $type = ucfirst($data['type']);

        // Abstract
        $abstract = (!empty($data['summary'])) ? "
            <div style='width: 95%; box-sizing: border-box; border-top: 3px solid rgba(207,81,81,.5); text-align: justify; margin: 5px auto; 
            padding: 10px;'>
                <span style='font-style: italic; font-size: 13px;'>{$data['summary']}</span>
            </div>
            " : null;

        // Build content
        $content = "
        <div style='width: 100%; padding-bottom: 5px; margin: auto auto 10px auto; background-color: rgba(255,255,255,.5); border: 1px solid #bebebe;'>
            <div style='display: block; margin: 0 0 15px 0; padding: 0; text-align: justify; min-height: 20px; height: auto; line-height: 20px; width: 100%;'>
                <div style='vertical-align: top; text-align: left; margin: 5px; font-size: 16px;'>
                    <span style='color: #222; font-weight: 900;'>{$type}</span>
                    <span style='color: rgba(207,81,81,.5); font-weight: 900; font-size: 20px;'> . </span>
                    <span style='color: #777; font-weight: 600;'>{$data['fullname']}</span>
                </div>
            </div>
            <div style='width: 100%; text-align: justify; margin: auto; box-sizing: border-box;'>
                <div style='max-width: 80%; margin: 5px;'>
                    <div style='font-weight: bold; font-size: 20px;'>{$data['title']}</div>
                </div>
                <div style='margin-left: 5px; font-size: 15px; font-weight: 400; font-style: italic;'>
                    {$data['authors']}
                </div>
            </div>
           {$abstract}
           {$file_div}
        </div>
        ";
        return $content;
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
        $destination = $view === 'modal' ? '#presentation' : '#presentation_container';
        $trigger = $view == 'modal' ? 'leanModal' : 'loadContent';

        // Add a delete link (only for admin and organizers or the authors)
        if ($show) {
            $delete_button = "<div class='pub_btn icon_btn'><a href='#' data-id='{$data['id']}' class='delete'
                data-controller='Presentation' data-operation='edit'>
                <img src='".AppConfig::$site_url."images/trash.png'></a></div>";
            $modify_button = "<div class='pub_btn icon_btn'><a href='#' class='{$trigger}'
                data-controller='Presentation' data-section='presentation' data-action='get_form' data-params='{$view}' 
                data-id='{$data['id_pres']}' data-operation='edit' data-date='{$data['date']}' data-destination='{$destination}'>
                <img src='".AppConfig::$site_url."images/edit.png'></a></div>";
        } else {
            $delete_button = "<div style='width: 100px'></div>";
            $modify_button = "<div style='width: 100px'></div>";
        }

        // Presentation type
        $type = ucfirst($data['type']);

        // Action buttons
        $buttons = "
            <div class='first_half'>
            </div>
            <div class='last_half'>
                {$delete_button}
                {$modify_button}
            </div>
        ";

        $buttons_body = $view !== 'modal' ? "
           <div class='first_half'>
           </div>
            <div class='last_half'>
                {$delete_button}
                {$modify_button}
            </div>" : null;

        // Header
        $header = "
            <span style='color: #222; font-weight: 900;'>Presentation</span>
            <span style='color: rgba(207,81,81,.5); font-weight: 900; font-size: 20px;'> . </span>
            <span style='color: #777; font-weight: 600;'>{$type}</span>
        ";

        $type_in_body = $view !== 'modal' ? "<div class='pub_type'>{$type}</div>" : null;

        $result = "
        <div class='pub_caps' itemscope itemtype='http://schema.org/ScholarlyArticle'>
            <div class='pub_title' itemprop='name'>{$data['title']}</div>
            {$type_in_body}
            <div class='pub_authors' itemprop='author'>{$data['authors']}</div>

            <div class='pub_date'>
                <span class='pub_label'>Date: </span>" . date('d M Y', strtotime($data['date'])) . "
            </div>
            <div class='pub_orator'>
                <span class='pub_label'>Presented by: </span>{$data['fullname']}
            </div>
        </div>

        <div class='pub_abstract'>
            <span class='pub_label'>Abstract: </span>
            {$data['summary']}
        </div>
        
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
                'id'=>'submission'
            );
        }
    }

    /**
     * Render submission editor
     * @param array|null $post
     * @return array
     */
    public function editor(array $post=null) {
        $post = (is_null($post)) ? $_POST : $post;
        $Session = new Session();

        $id_Presentation = isset($post['id']) ? $post['id'] : false;
        if (!isset($_SESSION['username'])) {
            $_SESSION['username'] = false;
        }

        // Get presentation date, and if not present, then automatically select next planned session date.
        $post['session_id'] = (!empty($post['session_id']) && $post['session_id'] !== 'false') ? $post['session_id'] : null;
        if (is_null($post['session_id'])) {
            $next = $Session->getNext(1);
            $post['date'] = $next[0]['date'];
            $post['session_id'] = $next[0]['id'];
        } else {
            $data = $Session->get(array('id'=>$post['session_id']));
            $post['date'] = $data[0]['date'];
        }
        $operation = (!empty($post['operation']) && $post['operation'] !== 'false') ? $post['operation'] : null;
        $type = (!empty($post['type']) && $post['type'] !== 'false') ? $post['type'] : null;
        $user = new User($_SESSION['username']);
        $this->getInfo($id_Presentation);
        return Presentation::form($user, $this, $operation, $type, $post);

    }

    /**
     * @param array $content
     * @return string
     */
    public static function format_section(array $content) {
        return "
        <section id='presentation_form'>
            <h2>{$content['title']}</h2>
            <p class='page_description'>{$content['description']}</p>       
            <div class='section_content'>{$content['content']}</div>
        </section>
        ";
    }

    /**
     * View for modal windows
     * @param string $content
     * @return string
     */
    public static function format_modal($content) {
        return "<div class='section_content'>{$content}</div>";
    }

    /**
     * Submission form instruction
     * @return string
     */
    public static function description() {
        return "
        Book a Journal Club session to present a paper, your research, or a
            methodology topic. <br>
            Fill in the form below, select a date (only available dates can be selected) and it's all done!
            Your submission will be automatically added to our database.<br>
            If you want to edit or delete your submission, you can find it on your <a href='index.php?page=member/profile'>profile page</a>!
                ";
    }

    /**
     * Generate submission form and automatically fill it up with data provided by Presentation object.
     * @param User $user
     * @param Suggestion|Presentation $Presentation
     * @param string $submit
     * @param bool $type
     * @param array $data
     * @return array
     * @internal param bool $date
     */
    public static function form(User $user, $Presentation=null, $submit="edit", $type=null, array $data=null) {
        if (is_null($Presentation)) {
            $Presentation = new self();
            $date = !is_null($data) && isset($data['date']) ? $data['date'] : null;
            $session_id = !is_null($data) && isset($data['session_id']) ? $data['session_id'] : null;
        } elseif (isset($data['date'])) {
            $date = !is_null($data) && isset($data['date']) ? $data['date'] : null;
            $session_id = !is_null($data) && isset($data['session_id']) ? $data['session_id'] : null;
        } else {
            $date = $Presentation->date;
            $session_id = $Presentation->session_id;
        }

        // Get class of instance
        $controller = get_class($Presentation);

        // Submission date
        $dateinput = ($submit !== "suggest") ? "<input type='date' class='datepicker_submission' name='date' 
                    value='{$date}' data-view='view'><label>Date</label>" : null;

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
                    " . Media::uploader($Presentation->link, 'presentation_form', 'Presentation') . "
                </div>
                
                <form method='post' action='php/form.php' enctype='multipart/form-data' id='presentation_form'>
                    
                    <div class='form_aligned_block matched_bg'>
                        
                        <div class='form_description'>
                            Select a presentation type and pick a date
                        </div>
                        <div class='form-group'>
                            <select class='change_pres_type' name='type' id='{$controller}_{$idPres}' required>
                                {$type_options['options']}
                            </select>
                            <label>Type</label>
                        </div>
                        
                        <div class='form-group'>
                            $dateinput
                        </div>
                    </div>
                
                    <div class='form_lower_container'>
                        <div class='special_inputs_container'>
                        " . self::get_form_content($Presentation, $type) . "
                        </div>
                        <div class='form-group'>
                            <label>Abstract</label>
                            <textarea name='summary' class='tinymce' placeholder='Abstract (5000 characters maximum)' style='width: 90%;' required>$Presentation->summary</textarea>
                        </div>
                    </div>
                    <div class='submit_btns'>
                        <input type='submit' name='$submit' class='submit_pres'>
                        <input type='hidden' name='controller' value='". __CLASS__ . "'>
                        <input type='hidden' name='selected_date' id='selected_date' value='{$date}'/>
                        <input type='hidden' name='session_id' value='{$session_id}'/>
                        <input type='hidden' name='$submit' value='true'/>
                        <input type='hidden' name='username' value='$user->username'/>
                        <input type='hidden' id='id_pres' name='id_pres' value='{$idPres}'/>
                    </div>
                </form>
            </div>
        ":"";

        if ($submit == 'suggest') {
            $result['title'] = "Make a wish";
        } elseif ($submit == "edit") {
            $result['title'] = "Add/Edit presentation";
        } elseif ($submit == "wishpick") {
            $result['title'] = "Select a wish";
        }
        $result['content'] = "
            <div class='submission'>
                $form
            </div>
            ";
        $result['description'] = self::description();
        return $result;
    }

    /**
     * Get form content based on selected presentation type
     * @param Suggestion|Presentation $Presentation
     * @param string $type: Presentation type
     * @return string
     */
    public static function get_form_content($Presentation, $type) {
        $form_name = $type . "_form";
        if (method_exists(__CLASS__, $form_name)) {
            return self::$form_name($Presentation);
        } else {
            return self::paper_form($Presentation);
        }
    }

    /**
     * Render form for wishes
     * @param Suggestion|Presentation $Presentation
     * @return string
     */
    private static function suggest_form($Presentation) {
        return "
        <div class='form_description'>
            Provide presentation information
        </div>

        <div class='form-group'>
            <input type='text' id='title' name='title' value='$Presentation->title' required/>
            <label>Title</label>
        </div>
        <div class='form-group'>
            <input type='text' id='authors' name='authors' value='$Presentation->authors' required>
            <label>Authors</label>
        </div>
        ";
    }

    /**
     * Render form for research article
     * @param Suggestion|Presentation $Presentation
     * @return string
     */
    private static function paper_form($Presentation) {
        return "
        <div class='form_description'>
            Provide presentation information
        </div>

        <div class='form-group'>
            <input type='text' id='title' name='title' value='$Presentation->title' required/>
            <label>Title</label>
        </div>
        <div class='form-group'>
            <input type='text' id='authors' name='authors' value='$Presentation->authors' required>
            <label>Authors</label>
        </div>
        ";
    }

    /**
     * Render form for guest speakers
     * @param Suggestion|Presentation $Presentation
     * @return string
     */
    private static function guest_form($Presentation) {
        return "
        <div class='form_description'>
            Provide presentation information
        </div>

        <div class='form-group'>
            <input type='text' id='title' name='title' value='$Presentation->title' required/>
            <label>Title</label>
        </div>
        <div class='form-group'>
            <input type='text' id='authors' name='authors' value='$Presentation->authors' required>
            <label>Authors </label>
        </div>
        <div class='form-group' id='guest'>
            <input type='text' id='orator' name='orator' required>
            <label>Speaker</label>
        </div>
        ";
    }

    /**
     * Render form for guest speakers
     * @param Suggestion|Presentation $Presentation
     * @return string
     */
    private static function minute_form($Presentation) {
        return "
        <div class='form_description'>
            Provide presentation information
        </div>

        <div class='form-group'>
            <input type='text' id='title' name='title' value='Minutes for session held on {$Presentation->date}' disabled/>
            <label>Title</label>
        </div>
        ";
    }

    /**
     * Submission menu
     * @param string $destination : body or modal
     * @param string $style: submitMenuFloat or submitMenu_fixed
     * @return string
     */
    public static function submitMenu($destination='body', $style='submitMenu_fixed') {
        $modal = $destination == 'body' ? 'loadContent' : "leanModal";
        return "
            <div class='{$style}'>
                <div class='submitMenuContainer'>
                    <div class='submitMenuSection'>
                        <a href='" . AppConfig::$site_url . 'index.php?page=submission&op=edit' . "' 
                        class='{$modal}' data-controller='Presentation' data-action='get_form'
                        data-destination='.submission_container' data-params='{$destination}' data-operation='edit'>
                           <div class='icon_container'>
                                <div class='icon'><img src='" . AppConfig::$site_url.'images/add_paper.png'. "'></div>
                                <div class='text'>Submit</div>
                            </div>
                       </a>
                    </div>
                    <div class='submitMenuSection'>
                        <a href='" . AppConfig::$site_url . 'index.php?page=submission&op=suggest' . "' 
                        class='{$modal}' data-controller='Suggestion' data-action='get_form' data-params='{$destination}'
                        data-destination='.submission_container' data-operation='suggest'>
                           <div class='icon_container'>
                                <div class='icon'><img src='" . AppConfig::$site_url.'images/wish_paper.png'. "'></div>
                                <div class='text'>Add a wish</div>
                            </div>
                        </a>
                    </div>
                    <div class='submitMenuSection'>
                        <a href='" . AppConfig::$site_url . 'index.php?page=submission&op=wishpick' . "' 
                        class='{$modal}' data-controller='Suggestion' data-action='get_suggestion_list' 
                        data-destination='.submission_container' data-params='{$destination}'>
                            <div class='icon_container'>
                                <div class='icon'><img src='" . AppConfig::$site_url.'images/select_paper.png'. "'></div>
                                <div class='text'>Select a wish</div>
                            </div>
                        </a>
                    </div>
                </div>
        </div>";
    }

    /**
     * Render years selection list
     * @param array $data
     * @return string
     */
    private static function yearsSelectionList(array $data) {
        $options = "<option value='all'>All</option>";
        foreach ($data as $year) {
            $options .= "<option value='$year'>$year</option>";
        }
        return "
            <div class='form-group inline_field' style='width: 200px'>
                <select name='year' class='archive_select'>
                    {$options}
                </select>
                <label>Filter by year</label>
            </div>
        ";
    }

    /**
     * Render presentation container
     * @param $content
     * @return string
     */
    private static function container($content) {
        return "<section><div class='section_content' id='presentation_container'>{$content}</div></section>";
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
}
