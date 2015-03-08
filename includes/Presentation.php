<?php
/*
Copyright © 2014, Florian Perdreau
This file is part of Journal Club Manager.

Journal Club Manager is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Journal Club Manager is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with Journal Club Manager.  If not, see <http://www.gnu.org/licenses/>.
*/

/** Mother class Presentations.
 * Handle methods to display presentations list (archives, homepage, wish list)
 */
class Presentations {

    protected $db;
    protected $tablename;

    /**
     * Constructor
     * @param DbSet $db
     */
    function __construct(DbSet $db){
        $this->db = $db;
        $this->tablename = $this->db->tablesname["Presentation"];
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
     * Get publication list by years
     * @param null $filter
     * @param null $user
     * @return array
     */
    function getyearspub($filter = NULL,$user = NULL) {
        $sql = "SELECT YEAR(date),id_pres FROM $this->tablename WHERE ";
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
            <div class='section_header'>$year</div>
            <div class='section_content'>
                <div class='list-container' id='pub_labels'>
                    <div style='text-align: center; font-weight: bold; width: 10%;'>Date</div>
                    <div style='text-align: center; font-weight: bold; width: 50%;'>Title</div>
                    <div style='text-align: center; font-weight: bold; width: 20%;'>Authors</div>
                    <div style='text-align: center; font-weight: bold; width: 10%;'></div>
                </div>
                $yearcontent
            </div>";
        }
        return $content;
    }

    /**
     * Get wish list
     * @param null $number
     * @param bool $mail
     * @return string
     */
    public function getwishlist($number=null,$mail=false) {
        $show = $mail == false && (!empty($_SESSION['logok']) && $_SESSION['logok'] == true);

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
         <form method='' action='' class='form'>
              <input type='hidden' name='page' value='presentations'/>
              <input type='hidden' name='op' value='wishpick'/>
              <div class='formcontrol' style='width: 50%;'>
                <label for='id'>Select a wish</label>
                <select name='id' id='select_wish'>
                    $option
                </select>
              </div>
          </form>";

        return $nextcontent;
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
    public $link = "";
    public $orator = "";
    public $presented = 0;
    public $id_pres = "";

    /**
     * @param DbSet $db
     * @param null $id_pres
     */
    function __construct(DbSet $db, $id_pres=null){
        $this->db = $db;
        $this->tablename = $this->db->tablesname["Presentation"];
        if (null != $id_pres) {
            self::get($id_pres);
        }
    }

    /**
     * Add a presentation to the database
     * @param $post
     * @return bool|string
     */
    function make($post){
        $class_vars = get_class_vars("Presentation");
        /** @var AppConfig $config */
        $config = new AppConfig($this->db);

        $post['up_date'] = date('Y-m-d h:i:s'); // Date of creation
        $post['jc_time'] = "$config->jc_time_from,$config->jc_time_to";
        $postkeys = array_keys($post);
        if ($this->pres_exist($post['title']) == false) {

            // Create an unique ID
            $this->id_pres = self::create_presID();

            $variables = array();
            $values = array();
            foreach ($class_vars as $name=>$value) {
                if (in_array($name,array("db","tablename"))) continue;

                if (in_array($name,$postkeys)) {
                    $escaped = $this->db->escape_query($post[$name]);
                } else {
                    $escaped = $this->db->escape_query($this->$name);
                }
                $this->$name = $escaped;
                $variables[] = "$name";
                $values[] = "'$escaped'";
            }

            $variables = implode(',',$variables);
            $values = implode(',',$values);

            // Add publication to the database
            if ($this->db->addcontent($this->tablename,$variables,$values)) {
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
     * Get publication's information from the database
     * @param $id_pres
     * @return bool
     */
    public function get($id_pres) {
        $sql = "SELECT * FROM $this->tablename WHERE id_pres='$id_pres'";
        $req = $this->db->send_query($sql);
        $row = mysqli_fetch_assoc($req);
        if (!empty($row)) {
            foreach ($row as $varname=>$value) {
                $this->$varname = htmlspecialchars_decode($value);
            }

            // Check if files are still where they are supposed to be
            $this->fileintegrity();

            // If publication's date is past, assumes it has already been presented
            $pub_day = $this->date;
            if ($this->date != "0000-00-00" && $pub_day < date('Y-m-d')) {
                $this->presented = 1;
                $this->update();
            }
            return true;
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

        if (array_key_exists("link", $post)) {
            $post['link'] = implode(',',array($this->link,$post['link']));
        }
        $this->type = (array_key_exists("type", $post)) ? $post['type']:$this->type;

        // Update session table if the user changed the date of presentation
        $updatesession = !empty($post['date']) && ($post['date'] !== $this->date) && ($this->type !== "wishlist");
        $olddate = $this->date;

        $class_vars = get_class_vars("Presentation");

        $postkeys = array_keys($post);
        foreach ($class_vars as $name => $value) {
            if (in_array($name,array("db","tablename"))) continue;
            $escaped = in_array($name, $postkeys) ? $this->db->escape_query($post[$name]) : $this->db->escape_query($this->$name);
            $this->$name = $escaped;

            if (!$this->db->updatecontent($this->tablename,$name,"'$escaped'",array("id_pres"),array("'$this->id_pres'"))) {
                return false;
            }
        }

        // If the user changed the date of presentation, we remove the presentation from its previous session and update the new session
        if (true === $updatesession) {
            $sessionpost = array(
                "date"=>$post['date'],
                "speakers"=>$this->orator,
                "presid"=>$this->id_pres
                );
            $session = new Session($this->db);
            $session->make($sessionpost);
            $session = new Session($this->db,$olddate);
            $session->delete_pres($this->id_pres);
        }
        return true;
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
     * Check if associated files are still present on the server
     * @return bool
     */
    private function fileintegrity() {
        $pdfpath = PATH_TO_APP.'/uploads/';
        $links = explodecontent(",",$this->link);
        $newlinks = array();
        foreach($links as $link) {
            if (is_file($pdfpath.$link)) {
                $newlinks[] = $link;
            }
        }
        $this->link = implode(',',$newlinks);
        self::update();
        return true;
    }

    /**
     * Validate upload
     * @param $file
     * @return bool|string
     */
    private function checkupload($file) {
        $config = new AppConfig($this->db);
        // Check $_FILES['upfile']['error'] value.
        if ($file['error'][0] != 0) {
            switch ($file['error'][0]) {
                case UPLOAD_ERR_OK:
                    break;
                case UPLOAD_ERR_NO_FILE:
                    return "No file to upload";
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    return 'File Exceeds size limit';
                default:
                    return "Unknown error";
            }
        }

        // You should also check file size here.
        if ($file['size'][0] > $config->upl_maxsize) {
            return "File Exceeds size limit";
        }

        // Check extension
        $allowedtypes = explodecontent(',',$config->upl_types);
        $filename = basename($file['name'][0]);
        $ext = substr($filename, strrpos($filename, '.') + 1);

        if (false === in_array($ext,$allowedtypes)) {
            return "Invalid file type";
        } else {
            return true;
        }
    }

    /**
     * Upload a file
     * @param $file
     * @return mixed
     */
    public function upload_file($file) {
        $result['status'] = false;
        if (isset($file['tmp_name'][0]) && !empty($file['name'][0])) {
            $result['error'] = self::checkupload($file);
            if ($result['error'] === true) {
                $tmp = htmlspecialchars($file['tmp_name'][0]);
                $splitname = explode(".", strtolower($file['name'][0]));
                $extension = end($splitname);

                $directory = PATH_TO_APP.'/uploads/';
                // Create uploads folder if it does not exist yet
                if (!is_dir($directory)) {
                    mkdir($directory);
                }

                $rnd = date('Ymd')."_".rand(0,100);
                $newname = "pres_".$rnd.".".$extension;
                while (is_file($directory.$newname)) {
                    $rnd = date('Ymd')."_".rand(0,100);
                    $newname = "pres_".$rnd.".".$extension;
                }

                // Move file to the upload folder
                $dest = $directory.$newname;
                $results['error'] = move_uploaded_file($tmp,$dest);

                if ($results['error'] == false) {
                    $result['error'] = "Uploading process failed";
                } else {
                    $results['error'] = true;
                    $result['status'] = $newname;
                }
            }
        } else {
            $result['error'] = "No File to upload";
        }
        return $result;
    }

    /**
     * Delete a presentation
     * @param $pres_id
     * @return bool
     */
    function delete_pres($pres_id) {
        self::get($pres_id);

        // Delete corresponding file
        self::delete_files();

        // Delete corresponding entry in the publication table
        if ($this->db->deletecontent($this->tablename,array('id_pres'),array("'$pres_id'"))) {
            $session = new self($this->db,$this->date);
            return $session->delete_pres($this->id_pres);
        } else {
            return false;
        }
    }

    /**
     * Delete all files corresponding to the actual presentation
     * @return bool
     */
    function delete_files() {
        $filelist = explode(',',$this->link);
        foreach ($filelist as $filename) {
            if (self::delete_file($filename) === false) {
                return false;
            }
        }
        return true;
    }

    /**
     * Delete a file corresponding to the actual presentation
     * @param $filename
     * @return bool|string
     */
    function delete_file($filename) {
        $pdfpath = PATH_TO_APP.'/uploads/';
        if (is_file($pdfpath.$filename)) {
            return unlink($pdfpath.$filename);
        } else {
            return "no_file";
        }
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
     * @return string
     */
    public function show() {
        return "
        <div class='pub_container' id='$this->id_pres'>
            <div class='list-container'>
                <div style='text-align: center; width: 10%;'>$this->date</div>
                <div style='text-align: left; width: 50%; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;'>$this->title</div>
                <div style='text-align: center; width: 20%; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;'>$this->authors</div>
                <div style='text-align: center; width: 10%; vertical-align: middle;'>
                    <div class='show_btn'><a href='#pub_modal' class='modal_trigger' id='modal_trigger_pubcontainer' rel='pub_leanModal' data-id='$this->id_pres'>MORE</a>
                    </div>
                </div>
            </div>
        </div>
        ";
    }

    /**
     * Show publication details in session list
     * @param $chair
     * @param $mail
     * @return string
     */
    public function showinsession($chair,$mail) {
        if ($chair !== 'TBA') {
            $chair = new User($this->db, $chair);
            $chair = $chair->fullname;
        }

        if ($this->id_pres === "") {
            $speaker = 'TBA';
            $show_but = "<a href='index.php?page=submission&op=new'>Free</a>";
            $type = "TBA";
        } else {
            /** @var User $speaker */
            $speaker = new User($this->db,$this->orator);
            $speaker = $speaker->fullname;
            // Make "Show" button
            if (null != $mail) {
                $show_but = "$this->title ($this->authors)";
            } else {
                $show_but = "<a href='#pub_modal' class='modal_trigger' id='modal_trigger_pubcontainer' rel='pub_leanModal' data-id='$this->id_pres'>$this->title ($this->authors)</a>";
            }
            $type = ucfirst($this->type);
        }

        return "
        <div id='$this->id_pres' style='display: block; margin: 0 auto 10px 0; padding-left: 10px; font-size: 14px; font-weight: 300; overflow: hidden;'>
            <div style='display: inline-block; vertical-align: middle; text-align: left; width: 55%; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; margin: 0;'>
                <label style='position: relative; left:0; top: 0; bottom: 0; background-color: rgba(207,81,81,.8); text-align: center; font-size: 13px; font-weight: 300; color: #EEE; padding: 7px 6px; z-index: 0;'>$type</label>
                <div style='display: block; position: relative; width: 100%; border: 0; z-index: 1; background-color: #dddddd; padding: 5px; border-bottom: 1px solid rgba(207,81,81,.5);' class='show_pres'>
                    $show_but
                </div>
            </div>
            <div style='display: inline-block; vertical-align: middle; text-align: left; width: 20%; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; margin: 0;'>
                <label style='position: relative; left:0; top: 0; bottom: 0; background-color: rgba(207,81,81,.8); text-align: center; font-size: 13px; font-weight: 300; color: #EEE; padding: 7px 6px; z-index: 0;'>Speaker</label>
                <div style='display: block; position: relative; width: 100%; border: 0; z-index: 1;background-color: #dddddd; padding: 5px; border-bottom: 1px solid rgba(207,81,81,.5);'>$speaker
                </div>
            </div>
            <div style='display: inline-block; vertical-align: middle; text-align: left; min-width: 20%; flex-grow: 1; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; margin: 0;'>
                <label style='position: relative; left:0; top: 0; bottom: 0; background-color: rgba(207,81,81,.8); text-align: center; font-size: 13px; font-weight: 300; color: #EEE; padding: 7px 6px; z-index: 0;'>Chair</label>
                <div style='display: block; position: relative; width: 100%; border: 0; z-index: 1; background-color: #dddddd; padding: 5px; border-bottom: 1px solid rgba(207,81,81,.5);'>$chair
                </div>
            </div>
        </div>";
    }

    /**
     * Show details about this presentation
     * @param $chair
     * @return string
     */
    public function showinsessionmanager($chair,$sessionid) {
        if ($chair !== 'TBA') {
            $chairman = new User($this->db,$chair);
            $chairman = $chairman->fullname;
        } else {
            $chairman = $chair;
        }

        /** Get list of organizers */
        $Users = new Users($this->db);
        $organizers = $Users->getadmin();
        $chairopt = "<option value='TBA'>TBA</option>";
        foreach ($organizers as $key=>$organizer) {
            $orguser = $organizer['username'];
            $orgfull = $organizer['fullname'];
            if ($orgfull === $chairman) {
                $chairopt .= "<option value='$orguser' selected>$orgfull</option>";
            } else {
                $chairopt .= "<option value='$orguser'>$orgfull</option>";
            }
        }

        /** title link */
        if ($this->id_pres !== "") {
            /** @var User $speaker */
            $speaker = new User($this->db,$this->orator);
            $type = $this->type;
            $speaker = $speaker->fullname;
            $titlelink = "<a href='#pub_modal' class='modal_trigger' id='modal_trigger_pubcontainer' rel='pub_leanModal' data-id='$this->id_pres'>$this->title ($this->authors)</a>";
        } else {
            $speaker = "TBA";
            $type = "TBA";
            $titlelink = "TBA";
        }

        return "
        <div id='$this->id_pres' style='display: block; width: 100%; margin: 0; font-size: 11px; font-weight: 300;'>
            <div style='display: inline-block; vertical-align: middle; text-align: left; width: 40%; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;'>
                <label style='position: relative; left:0; top: 0; bottom: 0; background-color: rgba(207,81,81,.8); text-align: center; font-size: 11px; font-weight: 300; color: #EEE; padding: 7px 6px; z-index: 0;'>$type</label>
                <div style='display: block; position: relative; width: 100%; border: 0; z-index: 1; background-color: #cccccc;padding: 5px;'>
                    $titlelink
                </div>
            </div>
            <div style='display: inline-block; vertical-align: middle; text-align: left; width: 20%; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;'>
                <label style='position: relative; left:0; top: 0; bottom: 0; background-color: rgba(207,81,81,.8); text-align: center; font-size: 11px; font-weight: 300; color: #EEE; padding: 7px 6px; z-index: 0;'>Speaker</label>
                <div style='display: block; position: relative; width: 100%; border: 0; z-index: 1;background-color: #cccccc;padding: 5px;'>$speaker
                </div>
            </div>
            <div style='display: inline-block; vertical-align: middle; text-align: left; width: 25%; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;'>
                <label style='position: relative; left:0; top: 0; bottom: 0; background-color: rgba(207,81,81,.8); text-align: center; font-size: 11px; font-weight: 300; color: #EEE; padding: 7px 6px; z-index: 0;'>Chair</label>
                <div style='display: block; position: relative; width: 100%; border: 0; z-index: 1;background-color: #cccccc;padding: 5px;'>
                    <select class='mod_chair' data-pres='$this->id_pres' data-session='$sessionid' style='font-size: 10px; padding: 0;'>$chairopt</select>
                </div>
            </div>
        </div>
        ";
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

        // Make a show button (modal trigger) if not in email. Otherwise, a simple href.
        if ($show == true) {
            $pick_url = "<a href='#pub_modal' class='modal_trigger' id='modal_trigger_pubmod' rel='pub_leanModal' data-id='$this->id_pres'><b>Make it true!</b></a>";
        } else {
            $pick_url = "<a href='$url' style='text-decoration: none;'><b>Make it true!</b></a>";
        }

        $uploader = new User($this->db,$this->username);

        return "
        <div style='display: block; padding: 5px; text-align: justify; background-color: #eeeeee;'>
            <div style='display: block; border-bottom: 1px solid #bbbbbb;'>
                <div style='display: inline-block; padding: 0; width: 90%; max-width: 90%; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;'>$this->title ($this->authors) suggested by <span style='color: #CF5151;'>$uploader->fullname</span>
                </div>
                <div style='display: inline-block; text-align: right;'>
                    $pick_url
                </div>
            </div>
            <div style='display: block; padding: 0; width: auto; font-size: 12px; text-align: left;'>
                <div style='color: #555555; font-weight: 300; font-style: italic; '>$this->up_date</div>
            </div>
        </div>";
    }
}
