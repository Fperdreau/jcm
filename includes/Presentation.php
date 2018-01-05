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

namespace includes;

use includes\BaseModel;
use includes\Modal;

/**
 * class Presentations.
 *
 * Handle methods to display presentations list (archives, homepage, wish list)
 */
class Presentation extends BaseModel {

    /**
     * Presentation settings
     * @var array
     */
    protected $settings = array(
        'default_type'=>"paper",
        'defaults'=>array("paper", "research", "methodology", "guest", "minute"),
        'types'=>array("paper", "research", "methodology", "guest", "minute")
    );

    public $id;
    public $type;
    public $date;
    public $jc_time;
    public $up_date;
    public $username;
    public $title;
    public $authors;
    public $summary;
    public $media;
    public $orator;
    public $chair;
    public $notified;
    public $id_pres;
    public $session_id;

    private static $default = array();

    /**
     * Constructor
     * @param null $id_pres
     */
    function __construct($id_pres=null){
        parent::__construct();

        // Set types to defaults before loading custom information
        $this->settings['types'] = $this->settings['defaults'];

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
     * Get default presentation settings
     * @return array: default settings
     */
    private function getDefaults() {
        self::$default = array(
            'date'=>self::getJcDates($this->settings['jc_day'],1)[0],
            'frequency'=>null,
            'slot'=>$this->settings['max_nb_session'],
            'type'=>$this->settings['default_type'],
            'types'=>$this->getTypes(),
            'start_time'=>$this->settings['jc_time_from'],
            'end_time'=>$this->settings['jc_time_to'],
            'room'=>$this->settings['room']
        );
        return self::$default;
    }

    /**
     * Add a presentation to the database
     * @param array $data
     * @return bool|string
     * @throws Exception
     */
    public function make(array $data){
        $is_full = Session::isBooked($data['session_id']);
        if ($is_full === false) {
            if ($this->pres_exist($data['title']) === false) {
                // Create an unique ID
                //$post['id_pres'] = $this->generateID('id_pres');

                // Upload datetime
                $data['up_date'] = date('Y-m-d h:i:s');

                // Add publication to the database
                if ($this->db->insert($this->tablename, $this->parseData($data, array("media")))) {
                    $data['id'] = $this->db->getLastId();
                } else {
                    return false;
                }
                
                // Associates this presentation to an uploaded file if there is one
                if (!empty($data['media'])) {
                    $media = new Media();
                    if (!$media->add_upload(explode(',', $data['media']), $data['id'], __CLASS__)) {
                        return false;
                    }
                }
                return $data['id'];
            } else {
                return "exist";
            }
        } else {
            return $is_full;
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
        if (!empty($data['media'])) {
            $media = new Media();
            if (!$media->add_upload(explode(',', $data['media']), $data['id'], __CLASS__)) {
                return false;
            }
        }
        return $this->db->update($this->tablename, $this->parseData($data, array("media","chair")), $id);
    }

    /**
     * Edit Presentation
     * @param array $data
     * @return mixed
     * @throws Exception
     */
    public function edit(array $data) {
        // check entries
        $id_pres = htmlspecialchars($data['id']);

        // IF not a guest presentation, the one who posted is the planned speaker
        if ($data['type'] !== "guest") {
            $data['orator'] = $_SESSION['username'];
        }

        if ($id_pres !== "false") {
            $created = $this->update($data, array('id'=>$id_pres));
        } else {
            $created = $this->make($data);
        }

        $result['status'] = !in_array($created, array(false, 'exist', 'booked', 'no_session'), true);

        if ($created === false || $result['status'] === false) {
            $result['msg'] = 'Oops, something went wrong';
        } elseif ($created === 'exist') {
            $result['msg'] = "Sorry, a presentation with a similar title already exists in our database.";
        } elseif ($created === 'booked') {
            $result['msg'] = "Sorry, the selected session is already full.";
        } elseif ($created === 'no_session') {
            $result['msg'] = "Sorry, there is no session planned on this date.";
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
        $user = SessionInstance::isLogged() ? new Users($_SESSION['username']) : null;
        $show = !is_null($user) && (in_array($user->status, array('organizer', 'admin'))
                || $data['username'] === $user->username);
        if ($data !== false) {
            return self::details($data, $show, $view);
        } else {
            return self::not_found();
        }
    }

    /**
     * Get submission form
     * @param string $view
     * @return mixed
     * @throws Exception
     */
    public function get_form($view='body', $operation='edit', $id=null) {
        if ($view === "body") {
            return self::format_section($this->editor(array(
                'operation'=>$operation, 
                'id'=>$id
            )));
        } else {
            $content = $this->editor(array(
                'operation'=>$operation, 
                'id'=>$id
            ));
            
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
            $content .= $this->show($item['id'], $username);
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
     * @param null $id
     * @return bool
     */
    public function modify($post=array(), $id=null) {
        if (null!=$id) {
            $this->id = $id;
        } elseif (array_key_exists('id',$post)) {
            $this->id = $_POST['id'];
        }

        // Associate the new uploaded file (if there is one)
        if (array_key_exists("media", $post)) {
            $media = new Media();
            $media->add_upload(explode(',', $post['media']), $post['id'], 'Presentation');
        }

        // Get presentation's type
        $this->type = (array_key_exists("type", $post)) ? $post['type']:$this->type;

        // Update table
        if ($this->db->update(
            $this->tablename,
            $this->parseData($post, array('media', 'chair')),
            array('id'=>$this->id))) {

            Logger::getInstance(APP_NAME, get_class($this))->info("Presentation ({$this->id}) updated");
            return true;
        } else {
            Logger::getInstance(APP_NAME, get_class($this))->error("Could not update presentation ({$this->id})");
            return false;
        }
    }

    /**
     * Get associated files
     */
    private function get_uploads() {
        $upload = new Media();
        $links = $upload->get_uploads($this->id, 'Presentation');
        $this->media = $links;
        return $links;
    }

    /**
     * Delete a presentation
     * @param int $id
     * @return bool
     */
    public function delete_pres($id) {
        // Delete corresponding file
        $uploads = new Media();
        if ($uploads->delete_files($id, __CLASS__)) {
            // Delete corresponding entry in the publication table
            return $this->delete(array('id'=>$id));
        } else {
            return false;
        }
    }

    /**
     * Check if presentation exists in the database
     * @param $title
     * @return bool
     * @throws Exception
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
            $speaker = new Users($this->orator);
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

    /**
     * Get presentation types
     * @param $default_type : Default presentation type
     * @param array|null $exclude
     * @return array
     */
    public static function presentation_type($default_type, array $exclude=null) {
        $prestype = "";
        $options = null;
        foreach (AppConfig::getInstance()->pres_type as $type) {
            if (!is_null($exclude) && in_array($type, $exclude)) continue;

            $prestype .= self::render_type($type, 'pres');
            $options .= $type == $default_type ?
                "<option value='$type' selected>$type</option>"
                : "<option value='$type'>$type</option>";
        }
        return array(
            'types'=>$prestype,
            "options"=>$options
        );
    }

    // PATCH
    /**
     * Add session id to presentation info
     */
    public function patch_presentation_id() {
        foreach ($this->all() as $key=>$item) {
            $pres_obj = new Presentation($item['presid']);
            $pres_obj->update(array('session_id'=>$this->get_session_id($pres_obj->date)),
                array('id'=>$item['presid']));
        }
    }

    /**
     * Patch upload table: add object name ('Presentation').
     */
    public static function patch_uploads() {
        $Publications = new self();
        $Media = new Media();
        foreach ($Publications->all() as $key=>$item) {
            if ($Media->is_exist(array('presid'=>$item['id']))) {
                if (!$Media->update(array('obj'=>'Presentation'), array('presid'=>$item['id']))) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Patch Presentation table by adding session ids if missing
     * @return bool
     */
    public static function patch_session_id() {
        $Publications = new self();
        $Session = new Session();
        foreach ($Publications->all() as $key=>$item) {
            if ($Session->is_exist(array('date'=>$item['date']))) {
                $session_info = $Session->getInfo(array('date'=>$item['date']));
                if (!$Publications->update(array('session_id'=>$session_info[0]['id']), array('id'=>$item['id']))) {
                    Logger::getInstance(APP_NAME, __CLASS__)->error('Could not update publication table with new session id');
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
            $publicationList[] = $item['id'];
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
        $sql = "SELECT date,id FROM $this->tablename";
        if ($excluded !== false) $sql .= " WHERE type!='$excluded'";
        $req = $this->db->send_query($sql);
        $dates = array();
        while ($row = mysqli_fetch_assoc($req)) {
            $dates[$row['date']][] = $row['id'];
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

        $data = $this->db->resultSet(
            $this->tablename,
            array('YEAR(date)', 'id'),
            $search,
            'ORDER BY date DESC'
        );

        $years = array();
        foreach ($data as $key=>$item) {
            $years[$item['YEAR(date)']][] = $item['id'];
        }
        return $years;
    }

    /**
     * Get publication's information from the database
     * @param int $id: presentation id
     * @return bool|array
     */
    public function getInfo($id) {
        $sql = "SELECT p.*, u.fullname as fullname 
                FROM {$this->tablename} p
                LEFT JOIN ". Db::getInstance()->getAppTables('Users') . " u
                    ON u.username=p.username
                WHERE p.id='{$id}'";
        $data = $this->db->send_query($sql)->fetch_assoc();

        if (!empty($data)) {
            $this->map($data);

            // Get associated files
            $data['media'] = $this->get_uploads();
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
        $leanModalUrl = Modal::buildUrl(get_class(), 'show_details', array(
            'view'=>'modal',
            'operation'=>'edit',
            'id'=>$presentation->id_pres)
        );
        return "
            <div class='pub_container' style='display: table-row; position: relative; box-sizing: border-box; font-size: 0.85em;  text-align: justify; margin: 5px auto; 
            padding: 0 5px 0 5px; height: 25px; line-height: 25px;'>
                <div style='display: table-cell; vertical-align: top; text-align: left; 
                min-width: 50px; font-weight: bold;'>$date</div>
                
                <div style='display: table-cell; vertical-align: top; text-align: left; 
                width: 60%; overflow: hidden; text-overflow: ellipsis;'>
                    <a href='" . URL_TO_APP . "index.php?page=presentation&id={$presentation->id_pres}" . "' 
                    class='leanModal' data-url='{$leanModalUrl}' data-section='submission'>
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
        $leanModalUrl = Modal::buildUrl(get_class(), 'show_details', array(
            'view'=>'modal',
            'operation'=>'edit',
            'id'=>$data['id'])
        );
        $view_button = "<a href='#' class='leanModal pub_btn icon_btn' data-url='{$leanModalUrl}' data-section='submission' 
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
        $leanModalUrl = Modal::buildUrl(get_class(), 'show_details', array(
            'view'=>'modal',
            'operation'=>'edit',
            'id'=>$data['id_pres'])
        );
        return "<a href='{$url}' class='leanModal' data-url='{$leanModalUrl}' data-section='submission' 
            data-title='Submission'>{$data['title']}</a>";
    }

    /**
     * Render short description of presentation in session list
     * @param array $data
     * @return array
     */
    public static function inSessionSimple(array $data) {
        $show_but = self::RenderTitle($data);
        $Bookmark = new Bookmark();
        return array(
            "name"=>$data['pres_type'],
            "content"=>"
            <div style='display: block !important;'>{$show_but}</div>
            <div>
                <span style='font-size: 12px; font-style: italic;'>Presented by </span>
                <span style='font-size: 14px; font-weight: 500; color: #777;'>{$data['fullname']}</span>
            </div>",
            "button"=>$Bookmark->getIcon(
                $data['id'],
                'Presentation',
                SessionInstance::isLogged() ? $_SESSION['username'] : null)
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
        $file_div = $show ? Media::download_menu_email($data['media'], App::getAppUrl()) : null;

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
        $dl_menu = Media::download_menu($data['media'], $show);

        $file_div = $show ? $dl_menu['menu'] : null;
        $destination = $view === 'modal' ? '#presentation' : '#presentation_container';
        $trigger = $view == 'modal' ? 'leanModal' : 'loadContent';
        $leanModalUrl = Modal::buildUrl(get_class(), 'get_form', array(
            'view'=>$view,
            'operation'=>'edit',
            'id'=>$data['id'])
        );
        // Add a delete link (only for admin and organizers or the authors)
        if ($show) {
            $delete_button = "<div class='pub_btn icon_btn'><a href='#' data-id='{$data['id']}' class='delete'
                data-controller='Presentation' data-action='delete_pres'>
                <img src='" . URL_TO_IMG . "trash.png'></a></div>";
            $modify_button = "<div class='pub_btn icon_btn'><a href='#' class='{$trigger}'
                data-controller='Presentation' data-url='{$leanModalUrl}' data-section='presentation' data-date='{$data['date']}'>
                <img src='" . URL_TO_IMG . "edit.png'></a></div>";
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

            <div class='pub_header_table'>
                <div class='pub_date'>
                    <span class='pub_label'>Date: </span>" . date('d M Y', strtotime($data['date'])) . "
                </div>
                
                <div class='pub_orator'>
                    <span class='pub_label'>Presented by: </span>{$data['fullname']}
                </div>
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

        // Get presentation id (if none, then this is a new presentation)
        $id_Presentation = isset($post['id']) ? $post['id'] : false;

        // Get presentation information
        $this->getInfo($id_Presentation);

        // Get session id
        if (!is_null($this->session_id)) {
            $post['session_id'] = $this->session_id;
        } else {
            $post['session_id'] = (!empty($post['session_id']) && $post['session_id'] !== 'false') ? $post['session_id'] : null;
        }

        // Get presentation date, and if not present, then automatically resultSet next planned session date.
        if (is_null($post['session_id'])) {
            $next = $Session->getNext(1);
            $post['date'] = $next[0]['date'];
            $post['session_id'] = $next[0]['id'];
        } else {
            $data = $Session->get(array('id'=>$post['session_id']));
            $post['date'] = $data['date'];
        }

        // Get operation type
        $operation = (!empty($post['operation']) && $post['operation'] !== 'false') ? $post['operation'] : null;

        // Get presentation type
        $type = (!empty($post['type']) && $post['type'] !== 'false') ? $post['type'] : null;

        // Get user information
        $user = new Users($_SESSION['username']);

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
     * @param Users $user
     * @param Suggestion|Presentation $Presentation
     * @param string $submit
     * @param bool $type
     * @param array $data
     * @return array
     * @internal param bool $date
     */
    public static function form(Users $user, $Presentation=null, $submit="edit", $type=null, array $data=null) {
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
        $controller = !empty($data['controller']) ? $data['controller'] : get_class($Presentation);

        // Submission date
        $dateinput = ($submit !== "suggest") ? "<input type='date' class='datepicker_submission' name='date' 
                    value='{$date}' data-view='view'><label>Date</label>" : null;

        // Submission type
        $type = (is_null($type)) ? $type : $Presentation->type;
        if (empty($type)) $type = 'paper';

        // Presentation ID
        $idPres = ($Presentation->id != "") ? $Presentation->id : 'false';

        // Make submission's type selection list
        $type_options = self::renderTypes($Presentation->getTypes(), $type);

        // Download links
        $links = !is_null($Presentation->media) ? $Presentation->media : array();

        // Text of the submit button
        $form = ($submit !== "wishpick") ? "
            <div class='feedback'></div>
            <div class='form_container'>
                <div class='form_aligned_block matched_bg'>
                    <div class='form_description'>
                        Upload files attached to this presentation
                    </div>
                    " . Media::uploader(__CLASS__, $links, 'presentation_form') . "
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
                            <textarea name='summary' class='wygiwym' id='summary' placeholder='Abstract (5000 characters maximum)' style='width: 90%;' required>$Presentation->summary</textarea>
                        </div>
                    </div>
                    <div class='submit_btns'>
                        <input type='submit' name='$submit' class='submit_pres'>
                        <input type='hidden' name='controller' value='{$controller}'>
                        <input type='hidden' name='operation' value='{$submit}'/>
                        <input type='hidden' name='process_submission' value='true'/>
                        <input type='hidden' name='selected_date' id='selected_date' value='{$date}'/>
                        <input type='hidden' name='session_id' value='{$session_id}'/>
                        <input type='hidden' name='username' value='$user->username'/>
                        <input type='hidden' id='id' name='id' value='{$idPres}'/>
                    </div>
                </form>
            </div>
        ":"";

        if ($submit == "edit") {
            $result['title'] = "Add/Edit presentation";
        } elseif ($submit == "select") {
            $result['title'] = "Select a suggestion";
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
        $leanModalUrl_presentation = Modal::buildUrl('Presentation', 'get_form', array(
            'view'=>'modal',
            'operation'=>'edit')
        );
        $leanModalUrl_suggestion = Modal::buildUrl('Suggestion', 'get_form', array(
            'view'=>'modal',
            'operation'=>'edit')
        );
        $leanModalUrl_select = Modal::buildUrl('Suggestion', 'get_suggestion_list', array('view'=>'modal')
        );
        return "
            <div class='{$style}'>
                <div class='submitMenuContainer'>
                    <div class='submitMenuSection'>
                        <a href='" . App::getAppUrl() . 'index.php?page=member/submission&op=edit' . "' 
                        class='{$modal}' data-url='{$leanModalUrl_presentation}' data-destination='.submission_container'>
                           <div class='icon_container'>
                                <div class='icon'><img src='" . URL_TO_IMG . 'add_paper.png' . "'></div>
                                <div class='text'>Submit</div>
                            </div>
                       </a>
                    </div>
                    <div class='submitMenuSection'>
                        <a href='" . App::getAppUrl() . 'index.php?page=member/submission&op=suggest' . "' 
                        class='{$modal}' data-url='{$leanModalUrl_suggestion}' data-destination='.submission_container'>
                           <div class='icon_container'>
                                <div class='icon'><img src='" . URL_TO_IMG . 'wish_paper.png' . "'></div>
                                <div class='text'>Add a wish</div>
                            </div>
                        </a>
                    </div>
                    <div class='submitMenuSection'>
                        <a href='" . App::getAppUrl() . 'index.php?page=member/submission&op=wishpick' . "' 
                        class='{$modal}' data-url={$leanModalUrl_select}' data-destination='.submission_container'>
                            <div class='icon_container'>
                                <div class='icon'><img src='" . URL_TO_IMG . 'select_paper.png'. "'></div>
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

    /**
     * Get session types
     * @return array
     */
    public function getTypes() {
        if (!empty($this->settings['types'])) {
            return $this->settings['types'];
        } else {
            return $this->settings['defaults'];
        }
    }

    /**
     * Get session types
     * @param array $types: list of default types
     * @param $default_type : default session type
     * @return array
     */
    public static function renderTypes(array $types, $default_type=null) {
        $Sessionstype = "";
        $opttypedflt = "";
        foreach ($types as $type) {
            $Sessionstype .= self::render_type($type);
            $opttypedflt .= $type == $default_type ?
                "<option value='$type' selected>$type</option>"
                : "<option value='$type'>$type</option>";
        }
        return array(
            'types'=>$Sessionstype,
            "options"=>$opttypedflt
        );
    }

    /**
     * Render session/presentation type list
     * @param $data
     * @return string
     */
    private static function render_type($data) {
        return "
                <div class='type_div' id='session_$data'>
                    <div class='type_name'>".ucfirst($data)."</div>
                    <div class='type_del' data-type='$data' data-class='" . strtolower(__CLASS__). "'></div>
                </div>
            ";
    }
}
