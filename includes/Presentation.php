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

/** class Presentations.
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
     * @param DbSet $db
     */
    function __construct(DbSet $db){
        parent::__construct($db, "Presentation", $this->table_data);
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
            <div class='section_header'>$year</div>
            <div class='section_content'>
                <div class='list-container' id='pub_labels'>
                    <div style='text-align: center; font-weight: bold; width: 5%;'>Date</div>
                    <div style='text-align: center; font-weight: bold; width: 60%;'>Title</div>
                    <div style='text-align: center; font-weight: bold; width: 25%;'>Authors</div>
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

    /**
     * Get latest submitted presentations
     * @return array
     */
    public function getLatest() {
        $sql = "SELECT id_pres FROM $this->tablename WHERE notified='0'";
        $req = $this->db->send_query($sql);
        $publicationList = array();
        while ($row = mysqli_fetch_assoc($req)) {
            $publicationList[] = $row['id_pres'];
        }
        return $publicationList;
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
     * @param DbSet $db
     * @param null $id_pres
     */
    function __construct(DbSet $db, $id_pres=null){
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
     * @param $post
     * @return bool|string
     */
    function make($post){
        $class_vars = get_class_vars("Presentation");

        if ($post['title'] == "TBA" || $this->pres_exist($post['title']) == false) {

            // Create an unique ID
            $this->id_pres = self::create_presID();

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

            // Get associated files
            $this->get_uploads();

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

        // Associate the new uploaded file (if there is one)
        if (array_key_exists("link", $post)) {
            $this->add_upload($post['link']);
        }

        // Get presentation's type
        $this->type = (array_key_exists("type", $post)) ? $post['type']:$this->type;

        // Update table
        $class_vars = get_class_vars("Presentation");
        $content = $this->parsenewdata($class_vars,$post,array('link','chair'));
        if (!$this->db->updatecontent($this->tablename,$content,array('id_pres'=>$this->id_pres))) {
            return false;
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
     * @param bool $user: adapt the display for the profile page
     * @return string
     */
    public function show($user=false) {
        if (!$user) {
            $speaker = new User($this->db,$this->orator);
            $speakerDiv = "<div style='text-align: center; width: 25%; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;'>$speaker->fullname</div>";
            $datewidth = "10%";
            $titlewidth = "60%";
        } else {
            $datewidth = "20%";
            $titlewidth = "70%";
            $speakerDiv = "";
        }
        return "
        <div class='pub_container' id='$this->id_pres'>
        <a href='#modal' class='modal_trigger' id='modal_trigger_pubcontainer' rel='leanModal' data-id='$this->id_pres'>
            <div class='list-container'>
                <div style='text-align: center; width: $datewidth;'>$this->date</div>
                <div style='text-align: left; width: $titlewidth; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;'>$this->title</div>
                $speakerDiv
            </div>
        </a>
        </div>
        ";
    }

    /**
     * Show publication details in session list
     * @param bool $opt
     * @param $date
     * @return string
     */
    public function showinsession($opt=false,$date) {

        if ($this->id_pres === "") {
            $speaker = 'TBA';
            $show_but = "<a href='#modal' class='modal_trigger' id='modal_trigger_pubmod' rel='leanModal' data-date='$date'>FREE</a>";
            $type = "TBA";
        } else {
            /** @var User $speaker */
            $speaker = new User($this->db,$this->orator);
            $speaker = $speaker->fullname;

            // Make "Show" button
            if ($opt == 'mail') {
                $show_but = "$this->title";
            } else {
                $show_but = "<a href='#modal' class='modal_trigger' id='modal_trigger_pubcontainer' rel='leanModal' data-id='$this->id_pres'>$this->title</a>";
            }
            $type = ucfirst($this->type);
        }

        // Either simply show speaker's name or option list of users (admin interface)
        if ($opt == 'admin' && $opt != 'mail') {
            /** Get list of organizers */
            $Users = new Users($this->db);
            $organizers = $Users->getUsers();
            $organizers[] = 'TBA';

            $speakerOpt = "";
            foreach ($organizers as $organizer) {
                if ($speaker == 'TBA') {
                    $speakerOpt .= "<option value='TBA' selected>TBA</option>";
                } else {
                    $orga = new User($this->db,$organizer);
                    $selectOpt = ($orga->fullname == $speaker) ? 'selected':"";
                    $speakerOpt .= "<option value='$orga->username' $selectOpt>$orga->fullname</option>";
                }
            }
            $speaker = "<select class='modSpeaker'>$speakerOpt</select>";
        }

        return "
        <div id='$this->id_pres' style='display: block; margin: 0 auto 10px 0; padding-left: 10px; font-size: 14px; font-weight: 300; overflow: hidden;'>
            <div style='display: inline-block; min-width: 60px;font-weight: 600; color: #222222; vertical-align: top;'>$type</div>
            <div style='display: inline-block; margin-left: 20px;'>
                <div>$show_but</div>
                <div style='font-style: italic;'>Presented by $speaker</div>
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
                $urllink = $AppConfig->site_url."uploads/".$info['filename'];
                $filecontent .= "
                        <div style='display: inline-block; text-align: center; padding: 5px 10px 5px 10px;
                                    margin: 2px; cursor: pointer; background-color: #bbbbbb; font-weight: bold;'>
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
                <div style='display: inline-block; vertical-align: top; margin: 0; text-align: center; width: 100px; background-color: #555555; color: #FFF; padding: 5px;'>
                    $type
                </div>
                <div style='display: inline-block; max-width: 80%; padding: 0 5px 0 5px; margin-left: 30px; height: auto;'>
                    <div style='font-weight: bold; font-size: 16px;'>$this->title</div>
                </div>
            </div>
            <div style='width: 95%; text-align: justify; margin: auto; padding: 5px 10px 0 10px; background-color: rgba(250,250,250,1); border-bottom: 5px solid rgba(207,81,81,.5);'>
                <div style='display: inline-block; margin-left: 0; font-size: 15px; font-weight: 300; width: 50%;'>
                    <b>Authors:</b> $this->authors
                </div>
                <div style='display: inline-block; width: 45%; margin: 0 auto 0 0; text-align: right;'>
                    <div style='display: inline-block; font-size: 15px; font-weight: 300;'><b>Speaker:</b> $orator->fullname</div>
                </div>
            </div>
            <div style='width: 95%; text-align: justify; margin: auto; background-color: #eeeeee; padding: 10px;'>
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

        // Make a show button (modal trigger) if not in email. Otherwise, a simple href.
        if ($show == true) {
            $pick_url = "<a href='#modal' class='modal_trigger' id='modal_trigger_pubmod' rel='leanModal' data-id='$this->id_pres'><b>Make it true!</b></a>";
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
