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
        "primary" => "id"
    );

    /**
     * Constructor
     * @param AppDb $db
     */
    function __construct(AppDb $db){
        parent::__construct($db, "Presentation", $this->table_data);
        $this->registerDigest();
    }

    /**
     * Register into DigestMaker table
     */
    private function registerDigest() {
        $DigestMaker = new DigestMaker($this->db);
        $DigestMaker->register('Presentations');
    }

    /**
     * Collect years of presentations present in the database
     * @return array
     */
    function get_years() {
        $dates = $this->db->getinfo($this->tablename,'date',array('type'),array("'wishlist'"),array('!='));
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
    function getyearspub($filter = NULL,$user = NULL) {
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
    function getpublicationlist($filter = NULL,$user = NULL) {
        $yearpub = self::getyearspub($filter,$user);
        if (empty($yearpub)) {
            return "Nothing submitted yet!";
        }

        $content = "";
        foreach ($yearpub as $year=>$publist) {
            $yearcontent = "";
            foreach ($publist as $pubid) {
                $pres = new Presentation($this->db,$pubid);
                $yearcontent .= $pres->show();
            }

            $content.= "
            <section>
                <h2 class='section_header'>$year</h2>
                <div class='table_container'>
                <div class='list-container list-heading'>
                    <div>Date</div>
                    <div>Title</div>
                    <div>Speakers</div>
                </div>
                $yearcontent
                </div>
            </section>";
        }
        return $content;
    }

    /**
     * Get wish list
     * @param null $number: number of wishes to display
     * @param bool $show: if true, presentations links are modal triggers. If false, they are regular URLs (to display
     * in email)
     * @return string
     */
    public function getwishlist($number=null,$show=false) {

        $sql = "SELECT id_pres FROM $this->tablename WHERE type='wishlist' ORDER BY date DESC";
        if (null !== $number) {
            $sql .= " LIMIT $number";
        }

        $req = $this->db->send_query($sql);
        $wish_list = "";
        if ($req->num_rows > 0) {
            while ($data = mysqli_fetch_array($req)) {
                $pub = new Presentation($this->db,$data['id_pres']);
                $wish_list .= $pub->showwish($show);
            }
        } else {
            $wish_list = "Nothing has been suggested for the moment. Be the first!";
        }
        return $wish_list;
    }

    /**
     *  Generate wish list (option menu)
     * @return string
     */
    function generate_selectwishlist() {
        $sql = "SELECT id_pres FROM $this->tablename WHERE type='wishlist' ORDER BY title DESC";
        $req = $this->db->send_query($sql);

        $option = "<option value=''>";
        while ($data = mysqli_fetch_array($req)) {
            $pres = new Presentation($this->db,$data['id_pres']);
            $option .= "<option value='$pres->id_pres'>$pres->authors | $pres->title</option>";
        }

        $nextcontent = "
         <form method='post' action='php/form.php' class='form'>
              <input type='hidden' name='page' value='presentations'/>
              <input type='hidden' name='op' value='wishpick'/>
              <div class='formcontrol'>
                <label for='id'>Select a wish</label>
                <select name='id' id='select_wish'>
                    $option
                </select>
              </div>
          </form>";

        return $nextcontent;
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
        $content['body'] = $this->getwishlist(4,true);
        $content['title'] = "Wish list";
        return $content;
    }

}


/**
 * Class Presentation
 * Handle attributes and methods proper to a single presentation
 *
 */
class Presentation extends Presentations {

    public $type = "";
    public $date = "0000-00-00";
    public $jc_time = "17:00,18:00";
    public $up_date = "0000-00-00 00:00:00";
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
     * @param AppDb $db
     * @param null $id_pres
     */
    function __construct(AppDb $db, $id_pres=null){
        parent::__construct($db);

        /** @var AppConfig $config */
        $config = new AppConfig($db);
        $this->jc_time = "$config->jc_time_from,$config->jc_time_to";
        $this->up_date = date('Y-m-d h:i:s'); // Date of creation

        if (null != $id_pres) {
            self::get($id_pres);
        }
    }

    /**
     * Add a presentation to the database
     * @param array $post
     * @return bool|string
     */
    function make(array $post){
        $class_vars = get_class_vars("Presentation");
        if ($post['title'] == "TBA" || $this->pres_exist($post['title']) == false) {

            // Create an unique ID
            $post['id_pres'] = self::create_presID();
            $this->id_pres = $post['id_pres'];

            // Associates this presentation to an uploaded file if there is one
            if (!empty($post['link'])) {
                $this->add_upload($post['link']);
            }

            // If not a wish, add date
            if ($post['type'] !== 'wishlist') {
                $this->date = $post['date'];
            }

            $content = $this->parsenewdata($class_vars, $post, array("link","chair"));
            // Add publication to the database
            if ($this->db->addcontent($this->tablename,$content)) {
                return $this->id_pres;
            } else {
                return false;
            }
        } else {
            $this->get($this->id_pres);
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

        if ($data['type'] === 'minute') {
            $data['title'] = "Minutes of session held on {$data['date']}";
        }

        // Create or update the presentation
        if ($presid !== "false") {
            $created = $this->update($data);
        } else {
            $created = $this->make($data);
        }

        $result['status'] = false;
        if ($created !== false && $created !== 'exists') {
            // Add to sessions table
            $session = new Session($this->db);
            if ($session->make(array("date"=>$data['date']))) {
                $result['status'] = true;
                $result['msg'] = "Thank you for your submission!";
            } else {
                $this->delete_pres($created);
                $result['msg'] = "Sorry, we could not create/update the session";
            }
        } elseif ($created == "exists") {
            $result['msg'] = "This presentation already exist in our database.";
        }
        return $result;
    }

    /**
     * Get publication's information from the database
     * @param $id_pres
     * @return bool|Presentation
     */
    public function get($id_pres) {
        $sql = "SELECT * FROM $this->tablename WHERE id_pres='$id_pres'";
        $req = $this->db->send_query($sql);
        $row = mysqli_fetch_assoc($req);
        if (!empty($row)) {
            foreach ($row as $varname=>$value) {
                $this->$varname = htmlspecialchars_decode($value);
            }

            // Get associated files
            $this->get_uploads();

            return $this;
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
    function update($post=array(),$id_pres=null) {
        if (null!=$id_pres) {
            $this->id_pres = $id_pres;
        } elseif (array_key_exists('id_pres',$post)) {
            $this->id_pres = $_POST['id_pres'];
        }

        // Associate the new uploaded file (if there is one)
        if (array_key_exists("link", $post)) {
            $this->add_upload($post['link']);
        }

        // Get presentation's type
        $this->type = (array_key_exists("type", $post)) ? $post['type']:$this->type;

        // Update table
        $class_vars = get_class_vars("Presentation");
        $content = $this->parsenewdata($class_vars,$post,array('link','chair'));
        return $this->db->updatecontent($this->tablename,$content,array('id_pres'=>$this->id_pres));
    }

    /**
     *  Create an unique ID for the new presentation
     * @return string
     */
    function create_presID() {
        $id_pres = date('Ymd').rand(1,10000);

        // Check if random ID does not already exist in our database
        $prev_id = $this->db->getinfo($this->tablename,'id_pres');
        while (in_array($id_pres,$prev_id)) {
            $id_pres = date('Ymd').rand(1,10000);
        }
        return $id_pres;
    }

    /**
     * Associates this presentation to an uploaded file
     * @param $filenames
     * @return bool
     * @internal param $filename
     */
    function add_upload($filenames) {
        $filenames = explode(',',$filenames);
        $upload = new Media($this->db);
        foreach ($filenames as $filename) {
            if ($upload->add_presid($filename,$this->id_pres) !== true) {
                return False;
            }
        }
        return true;
    }

    /**
     * Get associated files
     */
    function get_uploads() {
        $upload = new Uploads($this->db);
        $this->link = $upload->get_uploads($this->id_pres);
    }

    /**
     * Delete a presentation
     * @param $pres_id
     * @return bool
     */
    function delete_pres($pres_id) {
        self::get($pres_id);

        // Delete corresponding file
        $uploads = new Uploads($this->db);
        $uploads->delete_files($this->id_pres);

        // Delete corresponding entry in the publication table
        return $this->db->deletecontent($this->tablename,array('id_pres'),array($pres_id));
    }

    /**
     * Check if presentation exists in the database
     * @param $title
     * @return bool
     */
    function pres_exist($title) {
        $titlelist = $this->db -> getinfo($this->tablename,'title');
        return in_array($title,$titlelist);
    }

    /**
     * Show this presentation (in archives)
     * @param bool $user : adapt the display for the profile page
     * @return string
     */
    public function show($user=false) {
        if (!$user) {
            $speaker = new User($this->db, $this->orator);
            $speakerDiv = "<div class='pub_speaker warp'>$speaker->fullname</div>";
        } else {
            $speakerDiv = "";
        }
        $date = date('d M y',strtotime($this->date));
        return "
            <div class='pub_container' style='display: table-row; position: relative; box-sizing: border-box; font-size: 0.85em;  text-align: justify; margin: 5px auto; 
            padding: 0 5px 0 5px; height: 25px; line-height: 25px;'>
                <div style='display: table-cell; vertical-align: top; text-align: left; 
                min-width: 50px; font-weight: bold;'>$date</div>
                
                <div style='display: table-cell; vertical-align: top; text-align: left; 
                width: 60%; overflow: hidden; text-overflow: ellipsis;'>
                    <a href='' class='leanModal' id='modal_trigger_pubcontainer' data-section='submission_form' data-id='$this->id_pres'>
                        $this->title
                    </a>
                </div>
                {$speakerDiv}
            </div>
        ";
    }

    /**
     * Show publication details in session list
     * @param bool $opt
     * @param $date
     * @return string
     */
    public function showinsession($opt=false, $date) {

        if ($this->id_pres === "") {
            $speaker = 'TBA';
            $show_but = "<a href='' class='leanModal' id='modal_trigger_pubmod' data-section='submission_form' 
                        data-date='$date'>FREE</a>";
            $type = "TBA";
        } else {
            /** @var User $speaker */
            $speaker = new User($this->db, $this->orator);
            $speaker = (!empty($speaker->fullname)) ? $speaker->fullname : $this->orator;

            // Make "Show" button
            if ($opt == 'mail') {
                $show_but = "$this->title";
            } else {
                $show_but = "<a href='' class='leanModal' id='modal_trigger_pubcontainer' data-section='submission_form' data-id='$this->id_pres'>$this->title</a>";
            }
            $type = ucfirst($this->type);
        }

        // Either simply show speaker's name or option list of users (admin interface)
        if ($opt == 'admin' && $opt != 'mail') {
            /** Get list of organizers */
            $Users = new Users($this->db);
            $organizers = array('TBA');
            foreach ($Users->getUsers() as $key=>$user) {
                $organizers[] = $user['username'];
            }

            $speakerOpt = "";
            foreach ($organizers as $organizer) {
                if ($organizer == 'TBA') {
                    $speakerOpt .= "<option value='TBA' selected>TBA</option>";
                } else {
                    $orga = new User($this->db,$organizer);
                    $selectOpt = ($orga->fullname == $speaker) ? 'selected':null;
                    $speakerOpt .= "<option value='$orga->username' $selectOpt>$orga->fullname</option>";
                }
            }
            $speaker = "<select class='modSpeaker select_opt' style='max-width: 150px;'>$speakerOpt</select>";
        }

        return "
        <div class='pres_container' id='$this->id_pres' style='display: block; position: relative; margin: auto; font-size: 0.9em; font-weight: 300; overflow: hidden;'>
            <div style='display: inline-block;font-weight: 600; color: #222222; vertical-align: top;'>$type</div>
            <div style='display: inline-block; margin-left: 20px; max-width: 70%;'>
                <div>$show_but</div>
                <div style='font-style: italic;'>
                    <div style='display: inline-block;'>Presented by </div>
                    <div style='display: inline-block;'>$speaker</div>
                </div>
            </div>
        </div>";
    }

    /**
     * Show presentation details
     * @param bool $show: show list of attached files
     * @return string
     */
    public function showDetails($show=false) {
        $AppConfig = new AppConfig($this->db);
        $orator = new User($this->db,$this->orator);

        // Get file list
        $filediv = "";
        if ($show && !empty($this->link)) {
            $filecontent = "";
            foreach ($this->link as $fileid=>$info) {
                $urllink = $AppConfig->getAppUrl()."uploads/".$info['filename'];
                $filecontent .= "
                        <div style='display: inline-block; text-align: center; padding: 5px 10px 5px 10px;
                                    margin: 5px 2px 0 2px; cursor: pointer; background-color: #bbbbbb; font-weight: bold; border-radius: 5px;'>
                            <a href='$urllink' target='_blank' style='color: rgba(34,34,34, 1);'>".strtoupper($info['type'])."</a>
                        </div>";
            }
            $filediv = "<div style='display: block; text-align: justify; width: 95%; min-height: 20px; height: auto;
                margin: auto; border-top: 1px solid rgba(207,81,81,.8);'>$filecontent</div>";
        }

        // Format presentation's type
        $type = ucfirst($this->type);

        // Build content
        $content = "
        <div style='width: 100%; padding-bottom: 5px; margin: auto auto 10px auto; background-color: rgba(255,255,255,.5); border: 1px solid #bebebe;'>
            <div style='display: block; margin: 0 0 15px 0; padding: 0; text-align: justify; min-height: 20px; height: auto; line-height: 20px; width: 100%;'>
                <div style='vertical-align: top; text-align: left; margin: 5px; font-size: 16px;'>
                    <span style='color: #222; font-weight: 900;'>$type</span>
                    <span style='color: rgba(207,81,81,.5); font-weight: 900; font-size: 20px;'> . </span>
                    <span style='color: #777; font-weight: 600;'>$orator->fullname</span>
                </div>
            </div>
            <div style='width: 100%; text-align: justify; margin: auto; box-sizing: border-box;'>
                <div style='max-width: 80%; margin: 5px;'>
                    <div style='font-weight: bold; font-size: 20px;'>$this->title</div>
                </div>
                <div style='margin-left: 5px; font-size: 15px; font-weight: 400; font-style: italic;'>
                    $this->authors
                </div>
            </div>
            <div style='width: 95%; border-top: 3px solid rgba(207,81,81,.5); text-align: justify; margin: 5px auto; padding: 10px;'>
                <span style='font-style: italic; font-size: 13px;'>$this->summary</span>
            </div>
            $filediv
        </div>
        ";
        return $content;
    }

    /**
     * Show in wish list (if instance is a wish)
     * @param $show
     * @return string
     */
    public function showwish($show) {
        /** @var AppConfig $config */
        $config = new AppConfig($this->db);
        $url = $config->site_url."index.php?page=submission&op=wishpick&id=$this->id_pres";
        $uploader = new User($this->db,$this->username);
        $update = date('d M y',strtotime($this->up_date));
        return "
        <div class='wish_container' id='$this->id_pres' style='display: block; position: relative; margin: 10px auto; font-size: 0.9em; font-weight: 300; overflow: hidden;'>
            <div style='display: inline-block;font-weight: 600; color: #222222; vertical-align: top; font-size: 0.9em;'>
                $update
            </div>
            <div style='display: inline-block; margin-left: 20px; max-width: 70%;'>
               <a href='$url' class='leanModal' id='modal_trigger_pubmod' data-section='submission_form' data-id='$this->id_pres'>
                    <div>$this->title</div>
                    <div style='font-style: italic; color: #000000;'>Suggested by <span style='color: #CF5151;'>$uploader->fullname</span></div>
                </a>
            </div>
        </div>";
    }

    /**
     * Generate submission form and automatically fill it up with data provided by Presentation object.
     * @param $user
     * @param bool $show
     * @return string
     */
    public function displaypub($user=false, $show=false) {
        $user = ($user == false) ? new User($this->db):$user;
        $download_button = "";
        $dlmenu = "";
        $filediv = "";
        if (!(empty($this->link))) {
            if ($show) {
                // Show files list as a dropdown menu
                $download_button = "<div class='dl_btn pub_btn icon_btn' id='$this->id_pres'><img src='".AppConfig::$site_url."images/download.png'></div>";
                $filelist = $this->link;
                $dlmenu = "<div class='dlmenu'>";
                foreach ($filelist as $fileid=>$info) {
                    $dlmenu .= "
                <div class='dl_info'>
                    <div class='dl_type'>".strtoupper($info['type'])."</div>
                    <div class='link_name dl_name' id='".$info['filename']."'>$fileid</div>
                </div>";
                }
                $dlmenu .= "</div>";
            } else {
                // Show files list as links
                $filecontent = "";
                foreach ($this->link as $fileid=>$info) {
                    $urllink = AppConfig::$site_url."uploads/".$info['filename'];
                    $filecontent .= "
                    <div style='display: inline-block; text-align: center; padding: 5px 10px 5px 10px;
                                margin: 2px; cursor: pointer; background-color: #bbbbbb; font-weight: bold;'>
                        <a href='$urllink' target='_blank' style='color: rgba(34,34,34, 1);'>".strtoupper($info['type'])."</a>
                    </div>";
                }
                $filediv = "<div style='display: block; text-align: justify; width: 95%; min-height: 20px; height: auto;
            margin: auto; border-top: 1px solid rgba(207,81,81,.8);'>$filecontent</div>";
            }
        } else {
            $download_button = "<div style='width: 100px'></div>";
            $dlmenu = "";
        }

        // Add a delete link (only for admin and organizers or the authors)
        if ($user->status != 'member' || $this->orator == $user->username) {
            $delete_button = "<div class='pub_btn icon_btn'><a href='#' data-id='$this->id_pres' class='delete_ref'><img src='".AppConfig::$site_url."images/trash.png'></a></div>";
            $modify_button = "<div class='pub_btn icon_btn'><a href='#' data-id='$this->id_pres' class='modify_ref'><img src='".AppConfig::$site_url."images/edit.png'></a></div>";
        } else {
            $delete_button = "<div style='width: 100px'></div>";
            $modify_button = "<div style='width: 100px'></div>";
        }
        $orator = new User($this->db, $this->orator);
        if (empty($orator->fullname)) $orator->fullname = $this->orator;
        $type = ucfirst($this->type);
        $result = "
        <div class='pub_caps' itemscope itemtype='http://schema.org/ScholarlyArticle'>
            <div style='display: block; position: relative; float: right; margin: 0 auto 5px 0; text-align: center; height: 20px; line-height: 20px; width: 100px; background-color: #555555; color: #FFF; padding: 5px;'>
                $type
            </div>
            <div id='pub_title' style='font-size: 1.1em; font-weight: bold; margin-bottom: 10px; display: inline-block;' itemprop='name'>$this->title</div>
            <div id='pub_date'>
                <span style='color:#CF5151; font-weight: bold;'>Date: </span>$this->date 
            </div>
            <div id='pub_orator'>
                <span style='color:#CF5151; font-weight: bold;'>Presented by: </span>$orator->fullname
            </div>
            <div id='pub_authors' itemprop='author'><span style='color:#CF5151; font-weight: bold;'>Authors: </span>$this->authors</div>
        </div>

        <div class='pub_abstract'>
            <span style='color:#CF5151; font-weight: bold;'>Abstract: </span>$this->summary
        </div>

        <div class='pub_action_btn'>
            <div class='pub_one_half'>
                $download_button
                $dlmenu
            </div>
            <div class='pub_one_half last'>
                $delete_button
                $modify_button
            </div>
        </div>
        $filediv
        ";
        return $result;
    }

    /**
     * Generate submission form and automatically fill it up with data provided by Presentation object.
     * @param User $user
     * @param bool $Presentation
     * @param string $submit
     * @param bool $type
     * @param bool $date
     * @return string
     */
    public static function displayform(User $user, $Presentation=false, $submit="submit", $type=false, $date=false) {
        global $AppConfig, $db;

        if ($Presentation == false) {
            $Presentation = new self($db);
        }
        $date = ($date != false) ? $date:$Presentation->date;
        $type = ($type != false) ? $type:$Presentation->type;

        // Get files associated to this publication
        $links = $Presentation->link;
        $uploader = uploader($links);

        // Presentation ID
        $idPres = ($Presentation->id_pres != "") ? $Presentation->id_pres:'false';
        $idPresentation = "<input type='hidden' id='id_pres' name='id_pres' value='$idPres'/>";

        // Show date input only for submissions and updates
        $dateinput = ($submit != "suggest") ? "<label>Date</label><input type='date' id='datepicker' name='date' value='$date'>":"";

        $authors = ($type !== 'minute') ? "<div class='formcontrol'>
                <label>Authors </label>
                <input type='text' id='authors' name='authors' value='$Presentation->authors' required>
            </div>":"";

        $selectopt = ($submit === "select") ? $Presentation->generate_selectwishlist():"";

        // Make submission's type selection list
        $typeoptions = "";
        $pres_type = explode(',', $AppConfig->pres_type);
        foreach ($pres_type as $types) {
            if ($types == $type) {
                $typeoptions .= "<option value='$types' selected>$types</option>";
            } else {
                $typeoptions .= "<option value='$types'>$types</option>";
            }
        }

        // Text of the submit button
        $submitxt = ucfirst($submit);
        $form = ($submit !== "select") ? "<div class='feedback'></div>
        <form method='post' action='php/form.php' enctype='multipart/form-data' id='submit_form'>
            <div class='submit_btns'>
                <input type='submit' name='$submit' value='$submitxt' id='submit' class='submit_pres'>
            </div>
            <input type='hidden' name='selected_date' id='selected_date' value='$date'/>
            <input type='hidden' name='$submit' value='true'/>
            <input type='hidden' name='username' value='$user->username'/>
            $idPresentation

            <div class='formcontrol'>
                <label>Type</label>
                <select name='type' id='type' required>
                    $typeoptions
                </select>
            </div>

            <div class='formcontrol'>
                $dateinput
            </div>

            <div class='formcontrol' id='guest' style='display: none;'>
                <label>Speaker</label>
                <input type='text' id='orator' name='orator' required>
            </div>

            <br><div class='formcontrol'>
                <label>Title </label>
                <input type='text' id='title' name='title' value='$Presentation->title' required/>
            </div>

            $authors

            <div class='formcontrol'>
                <label>Abstract</label>
                <textarea name='summary' class='tinymce' id='summary' placeholder='Abstract (5000 characters maximum)' style='width: 90%;' required>$Presentation->summary</textarea>
            </div>
        </form>

        $uploader":"";

        return "
    <div>$selectopt</div>
    <div class='submission'>
        $form
    </div>
	";
    }
}
