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

class Press {

    public $type = "";
    public $date = "0000-00-00";
    public $jc_time = "17:00,18:00";
    public $up_date = "0000-00-00 00:00:00";
    public $title = "";
    public $authors = "";
    public $summary = "Abstract (2000 characters maximum)";
    public $link = "";
    public $orator = "";
    public $presented = 0;
    public $notification = 0;
    public $id_pres = "";

    function __construct($id_pres=null){
        if (null != $id_pres) {
            self::get($id_pres);
        }
    }

    public function get($id_pres) {
        require($_SESSION['path_to_app'].'config/config.php');

        $db_set = new DB_set();
        $sql = "SELECT * FROM $presentation_table WHERE id_pres='$id_pres'";
        $req = $db_set->send_query($sql);
        $class_vars = get_class_vars("Press");
        $row = mysqli_fetch_assoc($req);

        if (!empty($row)) {
            foreach ($row as $varname=>$value) {
                if (array_key_exists($varname,$class_vars)) {
                    $this->$varname = $value;
                }
            }

            // Check file integrity
            $pdfpath = $_SESSION['path_to_app'].'uploads/';
            if (!is_file($pdfpath.$this->link)) {
                $post['link'] = "";
                self :: update($post);

            }

            $today = date('Y-m-d');
            $pub_day =  date("Y-m-d",strtotime($this->date));
            if ($pub_day != date("Y-m-d",strtotime("0000-00-00")) && $pub_day < $today) {
                $post['presented'] = 1;
                $post['title'] = $this->title;
                $post['date'] = $pub_day;
                self :: update($post);
            }
            return true;

        } else {
            return false;
        }
    }

    // Add a presentation to the database
    function make($post){
        require($_SESSION['path_to_app'].'config/config.php');

        $class_vars = get_class_vars("Press");
        $db_set = new DB_set();
        $config = new site_config('get');
        $bdd = $db_set->bdd_connect();

        $post['up_date'] = date('Y-m-d'); // Date of creation
        $post['jc_time'] = "$config->jc_time_from,$config->jc_time_to";

        if (self::pres_exist($post['title']) == false) {

            // Create an unique ID
            $this->id_pres = self::create_presID();

            $variables = array();
            $values = array();
            foreach ($class_vars as $name=>$value) {
                if (array_key_exists($name,$post)) {
                    $value = mysqli_real_escape_string($bdd,$post["$name"]);
                } else {
                    $value = mysqli_real_escape_string($bdd,$this->$name);
                }
                $variables[] = "$name";
                $values[] = "'$value'";
            }

            $variables = implode(',',$variables);
            $values = implode(',',$values);

            // Add publication to the database
            $db_set->addcontent($presentation_table,$variables,$values);

            // Update presentation object
            self::get($post['title']);

            return "added";
        } else {
            self::get($this->id_pres);
            return "exist";
        }
    }

    // Create an ID for the new presentation
    function create_presID() {
        require($_SESSION['path_to_app'].'config/config.php');
        $db_set = new DB_set();
        $pres_id = date('Ymd').rand(1,10000);

        // Check if random ID does not already exist in our database
        $prev_id = $db_set->getinfo($presentation_table,'id_pres');
        while (in_array($pres_id,$prev_id)) {
            $pres_id = date('Ymd').rand(1,10000);
        }
        return $pres_id;
    }

    // Update a presentation (new info)
    function update($post,$id_pres=null) {
        require($_SESSION['path_to_app'].'config/config.php');
        $db_set = new DB_set();

        if (null!=$id_pres) {
            $this->id_pres = $id_pres;
        } elseif (array_key_exists('id_pres',$post)) {
            $this->id_pres = $_POST['id_pres'];
        }

        $class_vars = get_class_vars("Press");
        foreach ($post as $name => $value) {
            $value = htmlspecialchars($value);
            if (array_key_exists($name,$class_vars)) {
                $db_set->updatecontent($presentation_table,$name,"'$value'",array("id_pres"),array("'$this->id_pres'"));
                $this->$name = $value;
            }
        }
        return "updated";
    }

    // Upload a file
    function upload_file($file) {
        if (isset($file['tmp_name']) && !empty($file['name'])) {
            $tmp = htmlspecialchars($file['tmp_name']);
            $splitname = explode(".", strtolower($file['name']));
            $extension = end($splitname);

            $directory = $_SESSION['path_to_app'].'uploads/';
            chmod($directory,0777);
            $rnd = date('Ymd')."_".rand(0,100);
            $newname = "pres_".$rnd.".".$extension;
            while (is_file($directory.$newname)) {
                $rnd = date('Ymd')."_".rand(0,100);
                $newname = "pres_".$rnd.".".$extension;
            }

            $dest = $directory.$newname;
            $results = move_uploaded_file($tmp,$dest);
            chmod($directory,0755);

            if ($results == false) {
                return "failed";
            } else {
                return $newname;
            }
        } else {
            $newname = "no_file";
            return $newname;
        }
    }

    // Delete a presentation
    function delete_pres($pres_id) {
        require($_SESSION['path_to_app'].'config/config.php');

        self::get($pres_id);

        // Delete corresponding file
        self::delete_file($this->link);

        // Delete corresponding entry in the publication table
        $db_set = new DB_set();
        $db_set -> deletecontent($presentation_table,array('id_pres'),array("'$pres_id'"));
    }

    // Delete a file corresponding to the actual presentation
    function delete_file($filename) {
        // Delete file
        $pdfpath = $_SESSION['path_to_app'].'uploads/';

        $filelist = explode(',',$this->link);
        foreach ($filelist as $filename) {
            if (is_file($pdfpath.$filename)) {
                return unlink($pdfpath.$filename);
            } else {
                return false;
            }
        }
    }

    // Check if presentation exists in the database
    function pres_exist($prov_pressname) {
        require($_SESSION['path_to_app'].'config/config.php');
        $db_set = new DB_set();
        $presslist = $db_set -> getinfo($presentation_table,'title');
        if (in_array($prov_pressname,$presslist)) {
            return true;
        } else {
            return false;
        }
    }

    // Check if the date of presentation is already booked
    function isbooked($date) {
        require($_SESSION['path_to_app'].'config/config.php');
        $db_set = new DB_set();
        $config = new site_config('get');

        if ($this->id_pres == "") {
            $sql = "SELECT date FROM $presentation_table WHERE date='$date'";
        } else {
            $sql = "SELECT date FROM $presentation_table WHERE id_pres!=$this->id_pres and date='$date'";
        }
        $req = $db_set->send_query($sql);
        $num_results = mysqli_num_rows($req);
        if ($num_results>=$config->max_nb_session) {
            return true;
        } else {
            return false;
        }
    }

    // Get the upcoming presentation
    function getsession($nextdate=false) {
        require($_SESSION['path_to_app'].'config/config.php');
        $db_set = new DB_set();
        if ($nextdate == false) {
            $dates = self::getdates();
            $nextdate = $dates[0];
        }

        $sql = "SELECT id_pres FROM $presentation_table WHERE date='$nextdate'";
        $req = $db_set -> send_query($sql);
        $ids = array();
        while ($row = mysqli_fetch_assoc($req)) {
            $ids[] = $row['id_pres'];
        }
        return $ids;
    }

    // Get next unique dates (remove duplicates)
    private function getdates() {
        require($_SESSION['path_to_app'].'config/config.php');
        $db_set = new DB_set();
        $sql = "SELECT date FROM $presentation_table WHERE type!='wishlist' and date>=CURDATE() ORDER BY date ASC";
        $req = $db_set->send_query($sql);
        $dates = array();
        while ($row = mysqli_fetch_assoc($req)) {
            $date = $row['date'];
            if (!in_array($date, $dates)) {
                $dates[] = $date;
            }
        }
        if (empty($dates)) {$dates=false;}
        return $dates;
    }

    // Display the upcoming presentation(home page/mail)
    function shownextsession() {
        require($_SESSION['path_to_app'].'config/config.php');
        $dates = self::getdates();
        if ($dates !== false) {
            $date = $dates[0];
            $ids = self::getsession();
            $nb_pres = count($ids);
            $content = "<div style='background-color: rgba(207,101,101,.7); padding: 5px; margin-bottom: 10px;'>
                    <span style='font-weight: bold; margin: 0 0 5px 0;'>Date:</span> $date<br>
                    Our next session will host $nb_pres presentations.
                </div>";
            foreach ($ids as $presid) {
                $pres = new Press($presid);
                $link = "./uploads/".$pres->link;
                if ($_SESSION['logok'] == true && $pres->link != "") {
                    $filecontent = "<div class='link'><a href='$link' target='_blank'>Get File</a></div>";
                } else {
                    $filecontent = "";
                }
                $type = ucfirst($pres->type);
                $content .= "
                <div style='width: 100%; padding-bottom: 5px; margin: auto auto 10px auto; background-color: rgba(255,255,255,.5); border-top: 1px solid #adadad; border-left: 1px solid #adadad;'>
                    <div style='display: block; position: relative; margin: 0 0 5px; text-align: center; height: 20px; line-height: 20px; width: 100px; background-color: rgba(175,175,175,.8); color: #FFF; padding: 5px;'>
                        $type
                    </div>
                    <div style='width: 95%; margin: auto; padding: 5px 10px 0px 10px; background-color: rgba(250,250,250,1); border-bottom: 5px solid #aaaaaa;'>
                        <span style='font-weight: bold;'>Title:</span> $pres->title<br>
                        <div style='display: inline-block; margin-left: 0;'><b>Authors:</b> $pres->authors</div>
                        <div style='display: inline-block; float:right;'><b>Presented by:</b> $pres->orator</div>
                    </div>
                    <div style='width: 95%; text-align: justify; margin: auto; background-color: #eeeeee; padding: 10px;'>
                        <span style='font-style: italic; font-size: 13px;'>$pres->summary</span>
                    </div>
                    <div>
                        $filecontent
                    </div>
                </div>
                ";
            }
        } else {
            $content="Nothing planned for this session yet";
        }
        return $content;
    }

    // Get list of future presentations (home page/mail)
    public function get_futuresession($nsession = 4,$mail=null) {
        require($_SESSION['path_to_app'].'config/config.php');
        $db_set = new DB_set();
        $config = new site_config('get');

        // Get next journal club days
        $today = strtotime("now");
        $year = date('Y'); // Current year
        $month = date('F'); // Current month;
        $first = strtotime("first $config->jc_day of $month $year"); // First journal club of the year
        $lastday = mktime(0, 0, 0, 12, 31, $year); // Last day of the year

        $day = $first;
        $jc_days = array();
        $cpt = 0;
        while ($day < $lastday) {
            $curdate = date('Y-m-d',$day += 7 * 86400);
            if ($day >= $today) {
                $jc_days[] = $curdate;
                $cpt++;
            }
            if($cpt>=$nsession) { break; }
        }

        // Get future planned dates
        $dates = self::getdates();

        // Get futures journal club sessions
        $content = "";
        foreach ($jc_days as $day) {
            if (in_array($day,$dates)) {
                $ids = self::getsession($day); // Get presentations of this day
                $pubcontent = "";
                foreach ($ids as $presid) {
                    $pub = new Press($presid);

                    // Make "Show" button
                    if (null != $mail) {
                        $show_but = "";
                    } else {
                        $show_but = "<a href='#pub_modal' class='modal_trigger' id='modal_trigger_pubcontainer' rel='pub_leanModal' data-id='$pub->id_pres'><b>MORE</b></a>";
                    }

                    $pubcontent .= "
                    <div style='display: table-row; text-align: justify; border-bottom: 1px solid #bbbbbb; height: 25px; line-height: 25px; padding: 5px; margin-left: 20%;'>
                        <div style='display: table-cell; width: 40%; padding-left: 10px; text-align: left;'>
                            $pub->title ($pub->authors) presented by <span style='color: #CF5151;'>$pub->orator</span>
                        </div>
                        <div style='display: table-cell; width: 5%;'>
                            $show_but
                        </div>
                    </div>";
                }
            } else {
                $pubcontent = "
                <div style='display: table-row; text-align: justify; border-bottom: 1px solid #bbbbbb; height: 25px; line-height: 25px; padding: 5px; margin-left: 20%;'>
                    <div style='display: table-cell; width: 40%; padding-left: 10px; text-align: left;'>
                        FREE!
                    </div>
                </div>";
            }

            $content .= "
            <div style='display: block; margin: 0 auto 10px auto; border-top: 1px solid rgba(175,175,175,.8);'>
                <div style='display: block; position: relative; margin: 0 0 5px; text-align: center; height: 20px; line-height: 20px; width: 100px; background-color: rgba(175,175,175,.8); color: #FFF; padding: 5px;'>
                        $day
                </div>
                <div>
                    $pubcontent
                </div>
            </div>";
        }

        return $content;
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

    function getyearspub($filter = NULL,$user_fullname = NULL) {
        require($_SESSION['path_to_app'].'config/config.php');

        $db_set = new DB_set();
        $sql = "SELECT YEAR(date),id_pres FROM $presentation_table WHERE ";
        $cond = array();
        if (null != $user_fullname) {
            $cond[] = "orator='$user_fullname'";
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
    function getpublicationlist($filter = NULL,$user_fullname = NULL) {
        require($_SESSION['path_to_app'].'config/config.php');
        $yearpub = self::getyearspub($filter = NULL,$user_fullname = NULL);
        if (empty($yearpub)) {
            return "Nothing submitted yet!";
        }

        $content = "";
        foreach ($yearpub as $year=>$publist) {
            $yearcontent = "";
            foreach ($publist as $pubid) {
                self::get($pubid);
                $yearcontent .= "
                    <div class='pub_container' id='$this->id_pres'>
                        <div class='list-container'>
                            <div style='text-align: center; width: 10%;'>$this->date</div>
                            <div style='text-align: left; width: 50%;'>$this->title</div>
                            <div style='text-align: center; width: 20%;'>$this->authors</div>
                            <div style='text-align: center; width: 10%; vertical-align: middle;'>
                                <div class='show_btn'><a href='#pub_modal' class='modal_trigger' id='modal_trigger_pubcontainer' rel='pub_leanModal' data-id='$this->id_pres'>MORE</a>
                                </div>
                            </div>
                        </div>
                    </div>
                ";
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
    function getwishlist($number = null,$mail = false) {
        require($_SESSION['path_to_app'].'config/config.php');
        $config = new site_config('get');
        $db_set = new DB_set();
        $sql = "SELECT id_pres FROM $presentation_table WHERE type='wishlist' ORDER BY date DESC";
        $req = $db_set -> send_query($sql);
        $wish_list = "";
        $cpt = 0;
        if ($req->num_rows > 0) {
            while ($data = mysqli_fetch_array($req)) {
                $nb = $cpt + 1;
                $pub = new Press($data['id_pres']);
                $url = $config->site_url."index.php?page=presentations&op=wishpick&id=$pub->id_pres";
                if (!$mail) {
                    $pick_url = "<a href='#pub_modal' class='modal_trigger' id='modal_trigger_pubmod' rel='pub_leanModal' data-id='$pub->id_pres'><b>Choose it!</b></a>";
                } else {
                    $pick_url = "<a href='$url'>Choose it!</a>";
                }

                $wish_list .= "
                <div class='list-container' style='border-top: 1px solid #bbbbbb; width: 95%; min-height: 20px; height: auto; line-height: 20px; padding: 0;'>

                    <div style='padding: 0; text-align: center; border-right: 1px solid #999999; width: 5%;'><b>$nb</b></div>
                    <div style='padding: 0; text-align: justify; width: 80%;'>$pub->title ($pub->authors) suggested by $pub->orator</div>
                    <div style='text-align: center; width: 10%;'>$pick_url</div>
                </div>";

                $cpt++;
                if(null!=$number && $cpt>=$number) { break; };
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
        $nextcontent = "<form method='get' action='' class='form'>
              <input type='hidden' name='page' value='presentations'/>
              <input type='hidden' name='op' value='wishpick'/>
              <label for='id'>Select a wish</label><br><select name='id' id='select_wish'>";

        while ($data = mysqli_fetch_array($req)) {
            self::get($data['id_pres']);
            $option = "$this->authors | $this->title";
            $nextcontent .= "<option value='$this->id_pres'>$option</option>";
        }
        $nextcontent .= "<input type='submit' name='select' id='submit'/></select></form>";
        return $nextcontent;
    }

}
