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

/**
 * Class Sessions
 */
class Sessions extends Table {

    protected $table_data = array(
        "id" => array("INT NOT NULL AUTO_INCREMENT", false),
        "date" => array("DATE", false),
        "status" => array("CHAR(10)", "FREE"),
        "time" => array("VARCHAR(200)", false),
        "type" => array("CHAR(30) NOT NULL"),
        "nbpres" => array("INT(2)", 0),
        "primary" => "id");
    /**
     * Constructor
     * @param DbSet $db
     */
    function __construct(DbSet $db) {
        parent::__construct($db, "Session", $this->table_data);

        /** @var AppConfig $AppConfig */
        $AppConfig = new AppConfig($this->db);
        $this->max_nb_session = $AppConfig->max_nb_session;
    }

    /**
     *  Get all sessions
     * @param null $opt
     * @return array|bool
     * @internal param null $next
     */
    public function getsessions($opt=null) {
        $sql = "SELECT date FROM $this->tablename";
        if ($opt !== null) {
            $sql .= " WHERE date>=CURDATE()";
        }
        $sql .= " ORDER BY date ASC";
        $req = $this->db->send_query($sql);
        $sessions = array();
        while ($row = mysqli_fetch_assoc($req)) {
            $sessions[] = $row['date'];
        }
        if (empty($sessions)) {$sessions = false;}
        return $sessions;
    }

    /**
     * Get journal club days
     * @param int $nsession
     * @param bool $from
     * @return array
     */
    public function getjcdates($nsession=20,$from=false) {
        /** @var AppConfig $AppConfig */
        $AppConfig = new AppConfig($this->db);

        // Get next journal club days
        if ($from === false) {
            $startdate = strtotime("now");
            $year = date('Y'); // Current year
            $month = date('F'); // Current month;
        } else {
            $startdate = $from;
            $exploded = explode('-',$from);
            if (count($exploded) == 3) {
                $monthNb = $exploded[1];
                $month = date('F', mktime(0, 0, 0, $monthNb, 10)); // March
                $year = $exploded[0];
            } else {
                $month = $exploded[0];
                $year = $exploded[1];
            }
        }
        if ($from === false) {
            $first = strtotime("first " . $AppConfig->jc_day . " of $month $year"); // First journal club of the year
            $lastday = mktime(0, 0, 0, 12, 31, $year); // Last day of the year
        } else {
            $first = strtotime($from);
            $lastday = strtotime("$from + $nsession weeks");

        }
        $first = strtotime("first " . $AppConfig->jc_day . " of $month $year"); // First journal club of the year

        $day = $first;
        $jc_days = array();
        $cpt = 0;
        $curdate = date('Y-m-d',$day);
        while ($day < $lastday) {
            if ($day >= $startdate || $from == false) {
                $jc_days[] = $curdate;
                $cpt++;
            }
            if($cpt>=$nsession) { break; }
            $curdate = date('Y-m-d',$day += 7 * 86400);
        }
        return $jc_days;
    }

    /**
     * Check if date already exist
     * @param $date
     * @return bool
     */
    protected function dateexists($date) {
        $dates = $this->getsessions();
        if ($dates === false) {$dates = array();}
        return in_array($date,$dates);
    }

    /**
     * Check if the date of presentation is already booked
     * @param $date
     * @return string
     */
    public function isbooked($date) {
        /** @var Session $session */
        $session = new Session($this->db,$date);

        if ($session === false) {
            return "Free";
        } elseif ($session->nbpres<$this->max_nb_session) {
            if ($session->nbpres == 0) {
                return "Free";
            } else {
                return "Booked";
            }
        } else {
            return "Booked out";
        }
    }

    /**
     * Get all sessions
     * @param int $nbsession
     * @return string
     */
    public function managesessions($nbsession=4) {
        /** @var AppConfig $AppConfig */
        $AppConfig = new AppConfig($this->db);
        $timeopt = maketimeopt();

        $session_type = array_keys($AppConfig->session_type);

        $dates = $this->getsessions(1);
        $dates = ($dates == false) ? false: $dates[0];
        $sessions = self::getjcdates($nbsession,$dates);

        $content = "";
        foreach ($sessions as $date) {
            if (self::dateexists($date)) {
                $session = new Session($this->db,$date);
            } else {
                $session = new Session($this->db);
                $session->make(array('date'=>$date));
                $session->get();
            }

            // Get type options
            $typeoptions = "<option value='none' style='background-color: rgba(200,0,0,.5); color:#fff;'>NONE</option>";
            foreach ($session_type as $type) {
                if ($type === $session->type) {
                    $typeoptions .= "<option value='$type' selected>$type</option>";
                } else {
                    $typeoptions .= "<option value='$type'>$type</option>";
                }
            }

            // Get time
            $time = explode(',',$session->time);
            $timefrom = $time[0];
            $timeto = $time[1];

            // Get presentations
            $presentations = "";
            for ($i=0;$i<$AppConfig->max_nb_session;$i++) {
                $presid = (isset($session->presids[$i]) ? $session->presids[$i] : false);
                $chair = (isset($session->chairs[$i]) ? $session->chairs[$i] : array('chair'=>'TBA','id'=>false));
                $pres = new Presentation($this->db,$presid);
                $presentations .= $pres->showinsessionmanager($chair,$session->date);
            }

            $content .= "
            <div class='session_div'>
                <div class='session_header'>
                    <div class='session_date'>$session->date</div>
                    <div class='session_status'>$session->status</div>
                    <div class='feedback' id='feedback_$session->date' style='width: auto;'>
                    </div>
                </div>
                <div class='session_core'>
                    <div class='session_type'>
                        <div class='formcontrol' style='width: 100%;'>
                            <label>Type</label>
                            <select class='set_sessiontype' id='$session->date'>
                            $typeoptions
                            </select>
                        </div>
                    </div>
                    <div class='session_time'>
                        <div class='formcontrol' style='width: 100%;'>
                            <label>From</label>
                            <select class='set_sessiontime' id='timefrom_$session->date' data-session='$session->date'>
                                <option value='$timefrom' selected>$timefrom</option>
                                $timeopt
                            </select>
                        </div>
                    </div>
                    <div class='session_time'>
                        <div class='formcontrol' style='width: 100%;'>
                            <label>To</label>
                            <select class='set_sessiontime' id='timeto_$session->date' data-session='$session->date'>
                                <option value='$timeto' selected>$timeto</option>
                                $timeopt
                            </select>
                        </div>
                    </div>
                    <div class='session_presentations'>$presentations</div>
                </div>
            </div>
            ";
        }
        return $content;
    }

    /**
     * Display the upcoming presentation(home page/mail)
     * @param bool $mail
     * @return string
     */
    public function shownextsession($mail=false) {
        $show = $mail === true || (!empty($_SESSION['logok']) && $_SESSION['logok'] === true);

        $dates = $this->getsessions(1);
        if ($dates !== false) {
            $session = new Session($this->db,$dates[0]);
            $content = $session->showsessiondetails($show);
        } else {
            $content = "Nothing planned yet.";
        }
        return $content;
    }

    /**
     * Get list of future presentations (home page/mail)
     * @param int $nsession
     * @param null $mail
     * @return string
     */
    public function showfuturesession($nsession = 4,$mail=null) {
        // Get future planned dates
        $dates = $this->getsessions(1);
        $dates = ($dates == false) ? false: $dates[0];

        // Get journal club days
        $jc_days = $this->getjcdates($nsession, $dates);

        // Get futures journal club sessions
        $content = "";
        foreach ($jc_days as $day) {
            $session = new Session($this->db,$day);
            $sessioncontent = $session->showsession($mail);

            $type = ($session->type == "none") ? "No Meeting":ucfirst($session->type);
            $content .= "
            <div style='display: block; margin: 5px auto 0 auto;'>
                <div style='display: block; margin: 0;'>
                    <div style='display: inline-block; position: relative; text-align: center; height: 20px; line-height: 20px; width: 100px; background-color: #555555; color: #FFF; padding: 5px;'>
                        $day
                    </div>
                    <div style='display: inline-block; position: relative; text-align: center; height: 20px; line-height: 20px; min-width: 100px; width: auto; background-color: rgba(207,81,81,.7); color: #FFF; padding: 5px;'>
                        $type
                    </div>
                </div>
                <div style='padding: 10px 20px 10px 10px; background-color: #eee; margin: 0; border: 1px solid rgba(175,175,175,.8);'>
                    $sessioncontent
                    <div style='text-align: right; width: 100%;'>
                        <div class='show_btn' style='width: 80px; vertical-align: middle; padding: 5px 7px 5px 7px;'><a href='#pub_modal' class='modal_trigger' id='addminute' rel='pub_leanModal' data-date='$session->date'>Add a minute</a></div>
                    </div>
                </div>

            </div>";
        }
        return $content;
    }
}


class Session extends Sessions {
/**
 * Child class of Sessions
 * Instantiates session objects
 */

    public $date = "";
    public $status = "FREE";
    public $time = "";
    public $type = "Journal Club";
    public $nbpres = 0;
    public $presids = array();
    public $speakers = array();
    public $chairs = array();

    /**
     * @param DbSet $db
     * @param null $date
     */
    public function __construct(DbSet $db,$date=null) {
        parent::__construct($db);
        $AppConfig = new AppConfig($this->db);
        $this->time = "$AppConfig->jc_time_from,$AppConfig->jc_time_to";
        $this->type = $AppConfig->session_type_default;

        $this->date = $date;
        if ($date != null) {
            self::get($date);
        }
    }

    /**
     * Create session
     * @param $post
     * @return bool
     */
    public function make($post=array()) {
        if (!$this::dateexists($post['date'])) {
            $class_vars = get_class_vars("Session");
            $content = $this->parsenewdata($class_vars, $post, array('presids','speakers','chairs'));

            // Add session to the database
            if ($this->db->addcontent($this->tablename,$content)) {
                // Assign chairs for this session
                return $this->addChairs();
            } else {
                return False;
            }
        } else {
            self::get($post['date']);
            return self::update($post);
        }
    }

    /**
     * Update session status
     * @return bool
     */
    function updatestatus() {
        $this->nbpres = count($this->presids);
        if ($this->type=="none") {
            $status = "Booked out";
        } elseif ($this->nbpres == 0) {
            $status = "Free";
        } elseif ($this->nbpres<$this->max_nb_session) {
            $status = "Booked";
        } else {
            $status = "Booked out";
        }
        return $this->db->updatecontent($this->tablename,array("status"=>$status, "nbpres"=>$this->nbpres),array('date'=>$this->date));
    }

    /**
     * Get session info
     * @param null $date
     * @return bool
     */
    public function get($date=null) {
        $this->date = ($date !== null) ? $date : $this->date;

        // Get the associated presentations
        $this->getChairs();
        $this->getPresids();

        $class_vars = get_class_vars("Session");
        $sql = "SELECT * FROM $this->tablename WHERE date='$this->date'";
        $req = $this->db -> send_query($sql);
        $data = mysqli_fetch_assoc($req);
        if (!empty($data)) {
            foreach ($data as $varname=>$value) {
                if (array_key_exists($varname,$class_vars)) {
                    $this->$varname = htmlspecialchars_decode($value);
                }
            }
            return self::updatestatus();
        } else {
            return false;
        }
    }

    /**
     * Get planned chairs of this session
     * @return array
     */
    public function getChairs() {
        $sql = "SELECT id,chair FROM ".$this->db->tablesname['Chairs']." WHERE date='$this->date'";
        $req = $this->db->send_query($sql);
        $this->chairs = array();
        while ($row = mysqli_fetch_assoc($req)) {
            $this->chairs[] = array('chair'=>$row['chair'],'id'=>$row['id']);
        }
        if (count($this->chairs) < $this->max_nb_session) {
            if ($this->addChairs()) {
                $this->getChairs();
            }
        }
    }

    /**
     * Add temporary chairs ('TBA') to the chair table
     * @return bool
     */
    function addChairs() {
        $nbChairs = count($this->chairs);
        $Chairs = new Chairs($this->db);
        for ($p=$nbChairs;$p<$this->max_nb_session;$p++) {
            if (!$Chairs->make($this->date,'TBA')) {
                return False;
            }
        }
        return True;
    }

    /**
     * Get presentations and speakers
     * @return array
     */
    public function getPresids() {
        $sql = "SELECT id_pres,orator FROM ".$this->db->tablesname['Presentation']." WHERE date='$this->date'";
        $req = $this->db->send_query($sql);
        $this->presids = array();
        $this->speakers = array();
        while ($row = mysqli_fetch_assoc($req)) {
            $this->presids[] = $row['id_pres'];
            $this->speakers[] = $row['orator'];
        }
    }

    /**
     * Update session info
     * @param array $post
     * @return bool
     */
    public function update($post=array()) {
        $this->status = parent::isbooked($this->date);

        $class_vars = get_class_vars("Session");
        $content = $this->parsenewdata($class_vars,$post, array('speakers','presids','chairs'));
        if (!$this->db->updatecontent($this->tablename,$content,array('date'=>$this->date))) {
            return false;
        }

        self::get();
        return true;
    }

    /**
     * Check if a chair has already been assigned
     * @return bool
     */
    public function chairexist() {
        if (empty($this->chairs)) return false;
        return count($this->chairs)<count($this->presids);
    }

    /**
     * Show session (list)
     * @param bool $mail
     * @return string
     */
    public function showsession($mail=true) {
        if ($this->type == 'none')
            return "<div style='display: block; margin: 0 auto 10px 0; padding-left: 10px; font-size: 14px; font-weight: 300; overflow: hidden;'>
                    <b>No Journal Club this day</b></div>";
        $content = "";
        $max = (count($this->presids) < $this->max_nb_session) ? $this->max_nb_session:count($this->presids);
        for ($i=0;$i<$max;$i++) {
            $presid = (isset($this->presids[$i]) ? $this->presids[$i] : false);
            $chair['chair'] = (isset($this->chairs[$i])) ? $this->chairs[$i]['chair']:'';
            $pub = new Presentation($this->db,$presid);
            $content .= $pub->showinsession($chair,$mail,$this->date);
        }
        return $content;
    }

    /**
     * Show session details
     * @param bool $show
     * @return string
     */
    public function showsessiondetails($show=true,$prestoshow=false) {
        $AppConfig = new AppConfig($this->db);

        $time = explode(',',$this->time);
        $time_from = $time[0];
        $time_to = $time[1];
        if (count($this->presids) == 0) return "Nothing planned yet";

        $content = "<div style='background-color: rgba(255,255,255,.5); padding: 5px; margin-bottom: 10px; border: 1px solid #bebebe;'>
                <div style='display: inline-block; margin: 0 0 5px 0;'><b>Date: </b>$this->date</div>
                <div style='display: inline-block; margin: 0 5px 5px 0;'><b>From: </b>$time_from <b>To: </b>$time_to</div>
                <div style='display: inline-block; margin: 0 5px 5px 0;'><b>Room: </b> $AppConfig->room</div><br>
                Our next session is a <span style='font-weight: 500'>$this->type</span> and will host $this->nbpres presentations.
            </div>";
        $i = 0;
        foreach ($this->presids as $presid) {
            if ($prestoshow != false && $presid != $prestoshow) continue;

            $pres = new Presentation($this->db,$presid);
            $orator = new User($this->db,$pres->orator);
            $chair = (isset($this->chairs[$i])) ? $this->chairs[$i]['chair']:'TBA';
            if ($chair !== 'TBA') {
                $chair = new User($this->db,$chair);
                $chair = $chair->fullname;
            }
           // Get file list
            $filediv = "";
            if ($show && !empty($pres->link)) {
                $filecontent = "";
                foreach ($pres->link as $fileid=>$info) {
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
            $type = ucfirst($pres->type);
            $content .= "
            <div style='width: 100%; padding-bottom: 5px; margin: auto auto 10px auto; background-color: rgba(255,255,255,.5); border: 1px solid #bebebe;'>
                <div style='display: block; margin: 0 0 15px 0; padding: 0; text-align: justify; height: 20px; line-height: 20px; width: 100%;'>
                    <div style='display: inline-block; margin: 0; text-align: center; width: 100px; background-color: #555555; color: #FFF; padding: 5px;'>
                        $type
                    </div>
                    <div style='display: inline-block; width: auto; padding: 5px; margin-left: 30px;'>
                        <div style='font-weight: bold; font-size: 16px;'>$pres->title</div>
                    </div>
                </div>
                <div style='width: 95%; text-align: justify; margin: auto; padding: 5px 10px 0 10px; background-color: rgba(250,250,250,1); border-bottom: 5px solid rgba(207,81,81,.5);'>
                    <div style='display: inline-block; margin-left: 0; font-size: 15px; font-weight: 300; width: 50%;'>
                        <b>Authors:</b> $pres->authors
                    </div>
                    <div style='display: inline-block; width: 45%; margin: 0 auto 0 0; text-align: right;'>
                        <div style='display: inline-block; font-size: 15px; font-weight: 300;'><b>Speaker:</b> $orator->fullname</div>
                        <div style='display: inline-block; margin-left: 30px; font-size: 15px; font-weight: 300;'><b>Chair:</b> $chair</div>
                    </div>
                </div>
                <div style='width: 95%; text-align: justify; margin: auto; background-color: #eeeeee; padding: 10px;'>
                    <span style='font-style: italic; font-size: 13px;'>$pres->summary</span>
                </div>
                $filediv
            </div>
            ";
            $i++;
        }
        return $content;
    }
}
