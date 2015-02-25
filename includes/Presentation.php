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
require_once($_SESSION['path_to_includes'].'includes.php');

class Presentations {
// Mother class Presentations.
// Handle methods to display presentations list (archives, homepage, wish list)

    // Constructor
    function __construct(){
    }

    // Collect years of presentations present in the database
    function get_years() {
        require($_SESSION['path_to_app'].'config/config.php');

        $db_set = new DB_set();
        $dates = $db_set -> getinfo($presentation_table,'date',array('type'),array("'wishlist'"),array('!='));
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

    // Display a delete button
    function display_deletebutton() {
        return "<a href='#modal' rel='leanModal' data-id='$this->id_pres' class='modal_trigger' id='modal_trigger_deleteref'><img src='images/delete.png' alt='delete_button'></a>";
    }

    // Display a modify button
    function display_modifybutton() {
        return "<img src='images/modify.png' alt='modify_button' data-id='$this->id_pres' class='modifyref'>";
    }

    // Get publication list by years
    function getyearspub($filter = NULL,$user = NULL) {
        require($_SESSION['path_to_app'].'config/config.php');

        $db_set = new DB_set();
        $sql = "SELECT YEAR(date),id_pres FROM $presentation_table WHERE ";
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
        $req = $db_set -> send_query($sql);

        $yearpub = array();
        $content = "";
        while ($data = mysqli_fetch_array($req)) {
            $year = $data['YEAR(date)'];
            $yearpub[$year][] = $data['id_pres'];
        }
        return $yearpub;
    }

    // Get list of publications (sorted)
    function getpublicationlist($filter = NULL,$user = NULL) {
        require($_SESSION['path_to_app'].'config/config.php');
        $yearpub = self::getyearspub($filter,$user);
        if (empty($yearpub)) {
            return "Nothing submitted yet!";
        }

        $content = "";
        foreach ($yearpub as $year=>$publist) {
            $yearcontent = "";
            foreach ($publist as $pubid) {
                $pres = new Presentation($pubid);
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

    // Get wish list
    function getwishlist($number=null,$mail=false) {
        require($_SESSION['path_to_app'].'config/config.php');

        $show = $mail == false && (!empty($_SESSION['logok']) && $_SESSION['logok'] == true);

        $db_set = new DB_set();
        $sql = "SELECT id_pres FROM $presentation_table WHERE type='wishlist' ORDER BY date DESC";
        if (null !== $number) {
            $sql .= " LIMIT $number";
        }

        $req = $db_set -> send_query($sql);
        $wish_list = "";
        if ($req->num_rows > 0) {
            while ($data = mysqli_fetch_array($req)) {
                $pub = new Presentation($data['id_pres']);
                $wish_list .= $pub->showwish($show);
            }
        } else {
            $wish_list = "Nothing has been suggested for the moment. Be the first!";
        }
        return $wish_list;
    }

    // Generate wish list (option menu)
    function generate_selectwishlist() {
        require_once($_SESSION['path_to_includes'].'db_connect.php');
        require($_SESSION['path_to_app'].'config/config.php');

        $db_set = new DB_set();
        $db_set->bdd_connect();
        $sql = "SELECT id_pres FROM $presentation_table WHERE type='wishlist' ORDER BY title DESC";
        $req = $db_set -> send_query($sql);

        $option = "<option value=''>";
        while ($data = mysqli_fetch_array($req)) {
            $pres = new Presentation($data['id_pres']);
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

    function __construct($id_pres=null){
        if (null != $id_pres) {
            self::get($id_pres);
        }
    }

    // Add a presentation to the database
    function make($post){
        require($_SESSION['path_to_app'].'config/config.php');

        $class_vars = get_class_vars("Presentation");
        $db_set = new DB_set();
        $config = new site_config('get');

        $post['up_date'] = date('Y-m-d h:i:s'); // Date of creation
        $post['jc_time'] = "$config->jc_time_from,$config->jc_time_to";
        $postkeys = array_keys($post);
        if (self::pres_exist($post['title']) == false) {

            // Create an unique ID
            $this->id_pres = self::create_presID();

            $variables = array();
            $values = array();
            foreach ($class_vars as $name=>$value) {
                if (in_array($name,$postkeys)) {
                    $escaped = $db_set->escape_query($post[$name]);
                } else {
                    $escaped = $db_set->escape_query($this->$name);
                }
                $this->$name = $escaped;
                $variables[] = "$name";
                $values[] = "'$escaped'";
            }

            $variables = implode(',',$variables);
            $values = implode(',',$values);

            // Add publication to the database
            if ($db_set->addcontent($presentation_table,$variables,$values)) {
                return $this->id_pres;
            } else {
                return false;
            }
        } else {
            self::get($this->id_pres);
            return "exist";
        }
    }

    public function get($id_pres) {
        require($_SESSION['path_to_app'].'config/config.php');

        $class_vars = get_class_vars("Presentation");
        $classkeys = array_keys($class_vars);

        $db_set = new DB_set();
        $sql = "SELECT * FROM $presentation_table WHERE id_pres='$id_pres'";
        $req = $db_set->send_query($sql);
        $row = mysqli_fetch_assoc($req);
        if (!empty($row)) {
            foreach ($row as $varname=>$value) {
                $this->$varname = htmlspecialchars_decode($value);
            }

            // Check if files are still where they are supposed to be
            self::fileintegrity();

            // If publication's date is past, assumes it has already been presented
            $pub_day = $this->date;
            if ($this->date != "0000-00-00" && $pub_day < date('Y-m-d')) {
                $this->presented = 1;
                self::update();
            }
            return true;
        } else {
            return false;
        }
    }

    // Update a presentation (new info)
    function update($post=array(),$id_pres=null) {
        require($_SESSION['path_to_app'].'config/config.php');
        $db_set = new DB_set();

        if (null!=$id_pres) {
            $this->id_pres = $id_pres;
        } elseif (array_key_exists('id_pres',$post)) {
            $this->id_pres = $_POST['id_pres'];
        }

        if (array_key_exists("link", $post)) {
            $post['link'] = implode(',',array($this->link,$post['link']));
        }

        $class_vars = get_class_vars("Presentation");
        $postkeys = array_keys($post);
        foreach ($class_vars as $name => $value) {
            if (in_array($name,$postkeys)) {
                $escaped = $db_set->escape_query($post[$name]);
            } else {
                $escaped = $db_set->escape_query($this->$name);
            }
            $this->$name = $escaped;

            if (!$db_set->updatecontent($presentation_table,$name,"'$escaped'",array("id_pres"),array("'$this->id_pres'"))) {
                return false;
            }
        }
        return true;
    }

    // Create an ID for the new presentation
    function create_presID() {
        require($_SESSION['path_to_app'].'config/config.php');
        $db_set = new DB_set();
        $id_pres = date('Ymd').rand(1,10000);

        // Check if random ID does not already exist in our database
        $prev_id = $db_set->getinfo($presentation_table,'id_pres');
        while (in_array($id_pres,$prev_id)) {
            $id_pres = date('Ymd').rand(1,10000);
        }
        return $id_pres;
    }

    // Check if associated files are still present on the server
    private function fileintegrity() {
        $pdfpath = $_SESSION['path_to_app'].'uploads/';
        $links = explode(',',$this->link);
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

    // Validate upload
    private function checkupload($file) {
        $config = new site_config('get');
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

        // You should also check filesize here.
        if ($file['size'][0] > $config->upl_maxsize) {
            return "File Exceeds size limit";
        }

        // Check extension
        $allowedtypes = explode(',',$config->upl_types);
        $filename = basename($file['name'][0]);
        $ext = substr($filename, strrpos($filename, '.') + 1);

        if (false === in_array($ext,$allowedtypes)) {
            return "Invalid file type";
        } else {
            return true;
        }
    }

    // Upload a file
    public function upload_file($file) {
        $result['status'] = false;
        if (isset($file['tmp_name'][0]) && !empty($file['name'][0])) {
            $result['error'] = self::checkupload($file);
            if ($result['error'] === true) {
                $tmp = htmlspecialchars($file['tmp_name'][0]);
                $splitname = explode(".", strtolower($file['name'][0]));
                $extension = end($splitname);

                $directory = $_SESSION['path_to_app'].'uploads/';
                // Create uploads folder if it does not exist yet
                if (!is_dir($directory)) {
                    mkdir($directory);
                }
                chmod($directory,0777);

                $rnd = date('Ymd')."_".rand(0,100);
                $newname = "pres_".$rnd.".".$extension;
                while (is_file($directory.$newname)) {
                    $rnd = date('Ymd')."_".rand(0,100);
                    $newname = "pres_".$rnd.".".$extension;
                }

                // Move file to the upload folder
                $dest = $directory.$newname;
                $results['error'] = move_uploaded_file($tmp,$dest);
                chmod($directory,0755);

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

    // Delete a presentation
    function delete_pres($pres_id) {
        require($_SESSION['path_to_app'].'config/config.php');
        self::get($pres_id);

        // Delete corresponding file
        self::delete_files();

        // Delete corresponding entry in the publication table
        $db_set = new DB_set();
        if ($db_set -> deletecontent($presentation_table,array('id_pres'),array("'$pres_id'"))) {
            $session = new Session($this->date);
            return $session->delete_pres($this->id_pres);
        } else {
            return false;
        }
    }

    // Delete all files corresponding to the actual presentation
    function delete_files() {
        $filelist = explode(',',$this->link);
        foreach ($filelist as $filename) {
            if (self::delete_file($filename) === false) {
                return false;
            }
        }
        return true;
    }

    // Delete a file corresponding to the actual presentation
    function delete_file($filename) {
        $pdfpath = $_SESSION['path_to_app'].'uploads/';
        if (is_file($pdfpath.$filename)) {
            return unlink($pdfpath.$filename);
        } else {
            return "no_file";
        }
    }

    // Check if presentation exists in the database
    function pres_exist($title) {
        require($_SESSION['path_to_app'].'config/config.php');
        $db_set = new DB_set();
        $titlelist = $db_set -> getinfo($presentation_table,'title');
        return in_array($title,$titlelist);
    }

    // Show this presentation (list)
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

    // Show details about this presentation
    public function showinsession($chair) {
        if ($chair !== 'TBA') {
            $chairman = new users($chair);
            $chairman = $chairman->fullname;
        } else {
            $chairman = $chair;
        }

        $speaker = new users($this->orator);
        return "
        <div id='$this->id_pres' style='display: block; width: 100%; margin: 5px auto 0 0; font-size: 11px; font-weight: 300; overflow: hidden;'>
            <div style='display: inline-block; vertical-align: middle; text-align: left; width: 40%; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;'>
                <label style='position: relative; left:0; top: 0; bottom: 0; background-color: rgba(207,81,81,.8); text-align: center; font-size: 11px; font-weight: 300; color: #EEE; padding: 7px 6px; z-index: 0;'>$this->type</label>
                <div style='display: block; position: relative; width: 100%; border: 0; z-index: 1; background-color: #cccccc;padding: 5px;'>
                    <a href='#pub_modal' class='modal_trigger' id='modal_trigger_pubcontainer' rel='pub_leanModal' data-id='$this->id_pres'>$this->title ($this->authors)</a>
                </div>
            </div>
            <div style='display: inline-block; vertical-align: middle; text-align: left; width: 20%; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;'>
                <label style='position: relative; left:0; top: 0; bottom: 0; background-color: rgba(207,81,81,.8); text-align: center; font-size: 11px; font-weight: 300; color: #EEE; padding: 7px 6px; z-index: 0;'>Speaker</label>
                <div style='display: block; position: relative; width: 100%; border: 0; z-index: 1;background-color: #cccccc;padding: 5px;'>$speaker->fullname
                </div>
            </div>
            <div style='display: inline-block; vertical-align: middle; text-align: left; width: 20%; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;'>
                <label style='position: relative; left:0; top: 0; bottom: 0; background-color: rgba(207,81,81,.8); text-align: center; font-size: 11px; font-weight: 300; color: #EEE; padding: 7px 6px; z-index: 0;'>Chair</label>
                <div style='display: block; position: relative; width: 100%; border: 0; z-index: 1;background-color: #cccccc;padding: 5px;'>$chairman
                </div>
            </div>
        </div>
        ";
    }

    // Show in wish list (if I'm a wish)
    public function showwish($show) {
        $config = new site_config('get');

        $url = $config->site_url."index.php?page=presentations&op=wishpick&id=$this->id_pres";

        // Make a show button (modal trigger) if not in email. Otherwise, a simple href.
        if ($show == true) {
            $pick_url = "<a href='#pub_modal' class='modal_trigger' id='modal_trigger_pubmod' rel='pub_leanModal' data-id='$this->id_pres'><b>Make it true!</b></a>";
        } else {
            $pick_url = "<a href='$url' style='text-decoration: none;'><b>Make it true!</b></a>";
        }

        $uploader = new users($this->username);

        return "
        <div style='display: block; padding: 5px; text-align: justify; background-color: #eeeeee;'>
            <div style='display: block; border-bottom: 1px solid #bbbbbb;'>
                <div style='display: inline-block; padding: 0; width: 90%; max-width: 90%; overflow: hidden; white-space: nowrap; text-overflow: ellipsis;'>$this->title ($this->authors) suggested by <span style='color: #CF5151;'>$uploader->fullname</span>
                </div>
                <div style='display: inline-block; text-align: right;'>
                    $pick_url
                </div>
            </div>
            <div style='display: block; padding: 0; text-align: center; color: #555555; font-weight: 300; font-style: italic; width: auto; font-size: 12px; text-align: left;'>
                    $this->up_date
            </div>
        </div>";
    }
}
