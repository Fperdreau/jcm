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
class Presentations extends AppTable {

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

    /**
     * Constructor
     */
    function __construct(){
        parent::__construct("Presentation", $this->table_data);
    }

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
     * Collect years of presentations present in the database
     * @return array
     */
    function get_years() {
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
     * Get publications by date
     * @param bool $excludetype
     * @return array
     */
    public function getpubbydates($excludetype=false) {
        // Get presentations dates
        $sql = "SELECT date,id_pres FROM $this->tablename";
        if ($excludetype !== false) $sql .= " WHERE type!='$excludetype'";
        $req = $this->db->send_query($sql);
        $dates = array();
        while ($row = mysqli_fetch_assoc($req)) {
            $dates[$row['date']][] = $row['id_pres'];
        }
        return $dates;
    }

    /**
     * Get user's publications list
     * @param string $username
     * @return array
     */
    public function getList($username) {
        $sql = "SELECT * FROM {$this->tablename} WHERE username='{$username}'";
        $req = $this->db->send_query($sql);
        $data = array();
        while ($row = $req->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    /**
     * Get publication list by years
     * @param null $filter
     * @param null $user
     * @return array
     */
    public function getyearspub($filter = NULL,$user = NULL) {
        $sql = "SELECT YEAR(date),id_pres FROM $this->tablename WHERE title!='TBA' and ";
        $cond = array();
        if (null != $user) {
            $cond[] = "username='$user'";
        } else {
            if (null != $filter) {
                $cond[] = "YEAR(date)=$filter";
            }
            $cond[] = "type!='wishlist'";
        }
        $cond = implode(' and ',$cond);
        $sql .= $cond." ORDER BY date DESC";
        $req = $this->db->send_query($sql);

        $yearpub = array();
        while ($data = mysqli_fetch_array($req)) {
            $year = $data['YEAR(date)'];
            $yearpub[$year][] = $data['id_pres'];
        }
        return $yearpub;
    }

    /**
     * Get list of publications (sorted/archives page)
     * @param null $filter
     * @param null $user
     * @return string
     */
    public function getpublicationlist($filter = NULL,$user = NULL) {
        $yearpub = $this->getyearspub($filter,$user);
        if (empty($yearpub)) {
            return "Nothing submitted yet!";
        }

        $content = "";
        foreach ($yearpub as $year=>$publist) {
            $yearcontent = "";
            foreach ($publist as $pubid) {
                $pres = new Presentation();
                $yearcontent .= $pres->show($pubid);
            }

            $content.= "
            <section>
                <h2 class='section_header'>$year</h2>
                <div class='section_content'>
                    <div class='table_container'>
                    <div class='list-container list-heading'>
                        <div>Date</div>
                        <div>Title</div>
                        <div>Speakers</div>
                    </div>
                    $yearcontent
                    </div>
                </div>
            </section>";
        }
        return $content;
    }

    /**
     * Get latest submitted presentations
     * @return array
     */
    public function getLatest() {
        $sql = "SELECT id_pres FROM $this->tablename WHERE notified='0' and title!='TBA'";
        $req = $this->db->send_query($sql);
        $publicationList = array();
        while ($row = mysqli_fetch_assoc($req)) {
            $publicationList[] = $row['id_pres'];
        }
        return $publicationList;
    }

    /**
     * Renders digest section (called by DigestMaker)
     * @param null|string $username
     * @return mixed
     */
    public function makeMail($username=null) {
        $content['body'] = $this->getWishList(4,true);
        $content['title'] = "Wish list";
        return $content;
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

}


/**
 * Class Presentation
 * Handle attributes and methods proper to a single presentation
 *
 */
class Presentation extends Presentations {

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

    /**
     * @param null $id_pres
     */
    function __construct($id_pres=null){
        parent::__construct();

        /** @var AppConfig $config */
        $config = AppConfig::getInstance();
        $this->jc_time = "$config->jc_time_from,$config->jc_time_to";
        $this->up_date = date('Y-m-d h:i:s'); // Date of creation
        if (!is_null($id_pres)) {
            $this->getInfo($id_pres);
        }
    }

    /**
     * Add a presentation to the database
     * @param array $post
     * @return bool|string
     */
    function make(array $post){
        if ($post['title'] === "TBA" || $this->pres_exist($post['title']) === false) {

            // Create an unique ID
            $post['id_pres'] = $this->generateID('id_pres');
            $this->id_pres = $post['id_pres'];

            // Associates this presentation to an uploaded file if there is one
            if (!empty($post['link'])) {
                $media = new Media();
                $media->add_upload($post['link'], $post['id_pres'], 'Presentation');
            }

            // If not a wish, add date
            if ($post['type'] !== 'wishlist') {
                $this->date = $post['date'];
            }

            $content = $this->parsenewdata(get_class_vars(get_called_class()), $post, array("link","chair"));
            // Add publication to the database
            if ($this->db->addcontent($this->tablename,$content)) {
                return $this->id_pres;
            } else {
                return false;
            }
        } else {
            $this->getInfo($this->id_pres);
            return "exist";
        }
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

        // Create or update the presentation
        $content = $this->parsenewdata(get_class_vars(get_called_class()), $data, array("link","chair"));
        if ($presid !== "false") {
            $created = $this->update($content, array('id_pres'=>$this->id_pres));
        } else {
            $created = $this->make($content);
        }

        $result['status'] = $created === true;
        if ($created === false) {
            $result['msg'] = 'Oops, something went wrong';
        } elseif ($created == 'exists') {
            $result['msg'] = "This presentation already exist in our database.";
        } else {
            $result['msg'] = "Thank you for your submission!";
        }

        return $result;
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
            $media->add_upload($post['link'], $post['id_pres'], 'Presentation');
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
        $upload = new Uploads();
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
        $uploads = new Uploads();
        $uploads->delete_files($this->id_pres);

        // Delete corresponding entry in the publication table
        return $this->delete(array('id_pres'=>$pres_id));
    }

    /**
     * Check if presentation exists in the database
     * @param $title
     * @return bool
     */
    private function pres_exist($title) {
        $titlelist = $this->db -> select($this->tablename, array('title'));
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
        return self::show_in_list((object)$data[0], $speakerDiv);
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
                    <a href='" . URL_TO_APP . "index.php?page=presentation?id={$presentation->id_pres}" . "' class='leanModal' 
                    id='modal_trigger_pubcontainer' data-section='submission_form' data-id='$presentation->id_pres'>
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
        $Users = new Users();
        // Render list of available speakers
        $speakerOpt = (is_null($cur_speaker)) ? "<option selected disabled>Select a speaker</option>" : null;
        foreach ($Users->getUsers() as $key=>$speaker) {
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
        $view_button = "<a href='' class='leanModal modal_trigger_pubcontainer pub_btn icon_btn' data-section='submission_form' 
            data-id='{$data['id_pres']}'><img src='" . URL_TO_IMG . 'view_bk.png' . "' /></a>";
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
        return "<a href='{$url}' class='leanModal' id='modal_trigger_pubcontainer' data-section='submission_form' 
            data-id='{$data['id_pres']}'>{$data['title']}</a>";
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
            "button"=>null
        );
    }

    /**
     * Presentation's attached files (for emails)
     * @param array $links
     * @param $app_url
     * @return string
     */
    private static function downloadMenu(array $links, $app_url) {
        $icon_css = "display: inline-block;
            font-size: 10px;
            text-align: center;
            width: 30px;
            height: 30px;
            font-weight: bold;
            background-color: #555555;
            color: #EEEEEE;
            border-radius: 100%;
            line-height: 30px;
            margin: 2px 5px 2px 0px;
        ";

        // Get file list
        $filediv = "";
        if (!empty($links)) {
            $filecontent = "";
            foreach ($links as $fileid=>$info) {
                $urllink = $app_url."uploads/".$info['filename'];
                $filecontent .= "
                        <div style='{$icon_css}'>
                            <a href='$urllink' target='_blank' style='color: #EEEEEE;'>".strtoupper($info['type'])."</a>
                        </div>";
            }
            $filediv = "<div style='display: block; text-align: right; width: 95%; min-height: 20px; height: auto;
                margin: auto; border-top: 1px solid rgba(207,81,81,.8);'>{$filecontent}</div>";
        }

        return $filediv;
    }

    /**
     * Show presentation details
     * @param array $data: presentation information
     * @param bool $show : show list of attached files
     * @return string
     */
    public static function mail_details(array $data, $show=false) {
        // Make download menu if required
        $file_div = $show ? self::downloadMenu($data['link'], AppConfig::getInstance()->getAppUrl()) : null;

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

        $dl_menu = self::download_menu($data['link'], $show);
        $file_div = $show ? $dl_menu['menu'] : null;

        // Add a delete link (only for admin and organizers or the authors)
        if ($show) {
            $delete_button = "<div class='pub_btn icon_btn'><a href='#' data-id='{$data['id_pres']}' class='delete_ref'>
                <img src='".AppConfig::$site_url."images/trash.png'></a></div>";
            $modify_button = "<div class='pub_btn icon_btn'><a href='#' data-id='{$data['id_pres']}' class='modify_ref'>
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
            <div id='pub_date'>
                <span style='color:#CF5151; font-weight: bold;'>Date: </span>" . date('d M Y', strtotime($data['date'])) . "
            </div>
            <div id='pub_orator'>
                <span style='color:#CF5151; font-weight: bold;'>Presented by: </span>{$data['fullname']}
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

    /**
     * Render submission editor
     * @param array|null $post
     * @return array
     */
    public function editor(array $post=null) {
        $post = (is_null($post)) ? $_POST : $post;

        $id_Presentation = $post['getpubform'];
        $pub = $id_Presentation == "false" ? null : new self($id_Presentation);
        if (!isset($_SESSION['username'])) {
            $_SESSION['username'] = false;
        }
        $date = (!empty($post['date']) && $post['date'] !== 'false') ? $post['date'] : null;
        $type = (!empty($post['type']) && $post['type'] !== 'false') ? $post['type'] : null;
        $prestype = (!empty($post['prestype']) && $post['prestype'] !== 'false') ? $post['prestype'] : null;

        $user = new User($_SESSION['username']);
        if ($type === 'edit') {
            return Presentation::form($user, $pub, $type, $prestype, $date);
        } else {
            return Suggestion::form($user, $pub, $type, $prestype);
        }
    }

    /**
     * @param array $content
     * @return string
     */
    public static function format_section(array $content) {
        return "
        <section id='submission_form'>
            <h2>{$content['title']}</h2>
            <p class='page_description'>{$content['description']}</p>       
            <div class='section_content'>{$content['content']}</div>
        </section>
        ";
    }

    /**
     * View for modal windows
     * @param array $content
     * @return string
     */
    public static function format_modal(array  $content) {
        return "
            <p class='page_description'>{$content['description']}</p>       
            <div class='section_content'>{$content['content']}</div>
        ";
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
     * @param null|Presentation $Presentation
     * @param string $submit
     * @param bool $type
     * @param bool $date
     * @return array
     */
    public static function form(User $user, Presentation $Presentation=null, $submit="edit", $type=null, $date=null) {
        if (is_null($Presentation)) {
            $Presentation = new self();
        }

        // Submission date
        $date = (!is_null($date)) ? $date : $Presentation->date;
        $dateinput = ($submit !== "suggest") ? "<input type='date' class='datepicker' name='date' value='{$date}' 
                    data-view='view'>
                    <label>Date</label>" : null;

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
                            Select a presentation type and pick a date
                        </div>
                        <div class='form-group'>
                            <select class='change_pres_type' name='type' id='type' required>
                                {$type_options['options']}
                            </select>
                            <label>Type</label>
                        </div>
                        
                        <div class='form-group'>
                            $dateinput
                        </div>
                    </div>
                
                    <div class='form_lower_container'>
                        " . self::get_form_content($Presentation, $type) . "
                    </div>
                    <div class='submit_btns'>
                        <input type='submit' name='$submit' class='submit_pres processform'>
                        <input type='hidden' name='selected_date' id='selected_date' value='$date'/>
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
    private static function wish_form($Presentation) {
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
        <div class='form-group'>
            <input type='text' id='keywords' name='keywords' value='$Presentation->keywords' required>
            <label>Keywords (comma-separated)</label>
        </div>
        <div class='form-group'>
            <label>Abstract</label>
            <textarea name='summary' class='tinymce' id='summary' placeholder='Abstract (5000 characters maximum)' style='width: 90%;' required>$Presentation->summary</textarea>
        </div>
        ";
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
        <div class='form-group'>
            <input type='text' id='keywords' name='keywords' value='$Presentation->keywords' required>
            <label>Keywords (comma-separated)</label>
        </div>
        <div class='form-group'>
            <label>Abstract</label>
            <textarea name='summary' class='tinymce' id='summary' placeholder='Abstract (5000 characters maximum)' style='width: 90%;' required>$Presentation->summary</textarea>
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
        <div class='form-group'>
            <label>Abstract</label>
            <textarea name='summary' class='tinymce' id='summary' placeholder='Abstract (5000 characters maximum)' style='width: 90%;' required>$Presentation->summary</textarea>
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
        <div class='form-group'>
            <label>Abstract</label>
            <textarea name='summary' class='tinymce' id='summary' placeholder='Abstract (5000 characters maximum)' style='width: 90%;' required>$Presentation->summary</textarea>
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
        <div class='form-group'>
            <label>Minutes</label>
            <textarea name='summary' class='tinymce' id='summary' placeholder='Abstract (5000 characters maximum)' style='width: 90%;' required>$Presentation->summary</textarea>
        </div>
        ";
    }

    /**
     * Submission menu
     * @param $style
     * @return string
     */
    public static function submitMenu($style) {
        $class = ($style === 'float') ? "submitMenuFloat" : "submitMenu_fixed";
        return "
            <div class='{$class}'>
                <div class='submitMenuContainer'>
                    <div class='submitMenuSection'>
                        <a href='' class='leanModal' id='modal_trigger_newpub' data-section='submission_form' data-type='edit'>
                           <div class='icon_container'>
                                <div class='icon'><img src='" . AppConfig::$site_url.'images/add_paper.png'. "'></div>
                                <div class='text'>Submit</div>
                            </div>
                       </a>
                    </div>
                    <div class='submitMenuSection'>
                        <a href='' class='leanModal' id='modal_trigger_newpub' data-section='submission_form' data-type='suggest'>
                           <div class='icon_container'>
                                <div class='icon'><img src='" . AppConfig::$site_url.'images/wish_paper.png'. "'></div>
                                <div class='text'>Add a wish</div>
                            </div>
                        </a>
                    </div>
                    <div class='submitMenuSection'>
                        <a href='' class='leanModal' id='modal_trigger_newpub' data-section='submission_form' data-type='wishpick'>
                            <div class='icon_container'>
                                <div class='icon'><img src='" . AppConfig::$site_url.'images/select_paper.png'. "'></div>
                                <div class='text'>Select a wish</div>
                            </div>
                        </a>
                    </div>
                </div>
        </div>";
    }
}
