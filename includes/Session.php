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
class Sessions {

    protected $db;
    protected $tablename;

    /**
     * Constructor
     * @param DbSet $db
     */
    function __construct(DbSet $db) {
        $this->db = $db;
        $this->tablename = $this->db->tablesname["Session"];
        /** @var AppConfig $AppConfig */
        $AppConfig = new AppConfig($this->db);
        $this->max_nb_session = $AppConfig->max_nb_session;
    }

    /**
     *  Get all sessions
     * @param null $next
     * @return array|bool
     */
    public function getsessions($next=null) {
        $sql = "SELECT date FROM $this->tablename";
        if ($next !== null) {
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
     * @return array
     */
    public function getjcdates($nsession=20) {
        /** @var AppConfig $AppConfig */
        $AppConfig = new AppConfig($this->db);

        // Get next journal club days
        $today = strtotime("now");
        $year = date('Y'); // Current year
        $month = date('F'); // Current month;
        $first = strtotime("first ".$AppConfig->jc_day." of $month $year"); // First journal club of the year
        $lastday = mktime(0, 0, 0, 12, 31, $year); // Last day of the year

        $day = $first;
        $jc_days = array();
        $cpt = 0;
        $curdate = date('Y-m-d',$day);
        while ($day < $lastday) {
            if ($day >= $today) {
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
     * Get a chairman for the session
     * @param $sessionid
     * @param null $speaker
     * @return string
     */
    public function getchair($sessionid,$speaker=null) {
        /** @var Session $session */
        $session = new Session($this->db,$sessionid);

        /** @var AppConfig $AppConfig */
        $AppConfig = new AppConfig($this->db);

        /** Get speakers planned for this session */
        $speakers = explodecontent(',',$session->speakers);
        $speakers[] = $speaker; // Add the current speaker to the list
        $speakers = array_values(array_diff($speakers,array("")));

        /** Get chairmen planned for this session */
        $cur_chairs = explodecontent(',',$session->chairs);
        $cur_chairs = array_values(array_diff($cur_chairs,array("")));

        $exclude = array_merge($speakers,$cur_chairs);

         /** Get list of organizers */
        $Users = new Users($this->db);
        $organizers = $Users->getadmin();
        $chairs = array();
        if (!empty($organizers)) {
            foreach ($organizers as $organizer) {
                $chairs[] = $organizer['username'];
            }
        }

        /** @var  $prevchairs */
        $prevchairs = explodecontent(',',$AppConfig->session_chairs);

        if (empty($chairs)) {
            /** If no users have the organizer status, the chairman is to be announced.*/
            /** To Be Announced as a default */
            $chair = 'TBA';
            /** We start a new list of previous chairmen*/
            $prevchairs = array();
        } else {
            $allexclude = array_merge($exclude,$prevchairs);
            $possiblechairs = array_values(array_diff($chairs,$allexclude));
            /** We randomly pick a chairman among organizers who have not chaired a session yet,
             * apart from the speakers and the other chairmen of this session.
             */
            if (!empty($possiblechairs)) {
                $ind = rand(0,count($possiblechairs)-1);
                $chair = $possiblechairs[$ind];
            /** Otherwise, if all organizers already have been chairman once,
            * we randomly pick one among all the organizers,
            * apart from the speakers and the other chairmen of this session
             */
            } else {
                $possiblechairs = array_values(array_diff($chairs,$exclude));
                if (empty($possiblechairs)) {
                    /** RARE: in case all organizers are speakers and chairmen for this session, the chairman is to be announced.*/
                    $chair = 'TBA';
                } else {
                    $ind = rand(0,count($possiblechairs)-1);
                    $chair = $possiblechairs[$ind];
                }
                /** We start a new list of previous chairmen */
                $prevchairs = array();
            }
        }

        // Update the previous chairmen list
        $prevchairs[] = $chair;
        $AppConfig->session_chairs = implode(',',$prevchairs);
        $AppConfig->update();

        return $chair;
    }

    /**
     * Check consistency between session/presentation tables
     * @return bool
     */
    public function checkcorrespondence() {
        /** @var Presentations $presentations */
        $presentations = new Presentations($this->db);
        $dates = $presentations->getpubbydates("wishlist");

        // See if dates are missing in the session table
        foreach ($dates as $date=>$id_pres) {
            /** @var Session $session */
            $session = new Session($this->db,$date);

            /** First drop non existent presentations */
            if ($session->date != "") {
                $presids = explodecontent(',',$session->presid);
                $chairs = explodecontent(',',$session->chairs);
                $speakers = explodecontent(',',$session->speakers);
                $speakertodel = array();
                $prestodel = array();
                $chairtodel = array();
                for ($i=0;$i<$session->nbpres;$i++) {
                    $presid = $presids[$i];
                    $pub = new Presentation($this->db,$presid);
                    if ($pub->id_pres === '') {
                        $prestodel[] = $presid;
                        $chairtodel[] = $chairs[$i];
                        $speakertodel[] = $speakers[$i];
                    }
                }
                $speakers = array_values(array_diff($speakers,$speakertodel));
                $chairs = array_values(array_diff($chairs,$chairtodel));
                $presids = array_values(array_diff($presids,$prestodel));
                $session->speakers = implode(',',$speakers);
                $session->chairs = implode(',',$chairs);
                $session->presids = implode(',',$presids);
                $session->update();
            }

            /**
             * Add missing presentation to this session
             * @var $id
             *
             */
            for ($i=0;$i<count($id_pres);$i++) {
                /** @var Presentation $pres */
                $pres = new Presentation($this->db,$id_pres[$i]);
                $chair = (empty($chairs[$i]) ? $chair=self::getchair($session->date,$pres->orator):$chairs[$i]);
                $sessionpost = array(
                    'date'=>$date,
                    "speakers"=>$pres->orator,
                    "presid"=>$id_pres[$i],
                    "chairs"=>$chair);
                if (!$session->make($sessionpost)) {
                    return false;
                }
            }
        }
        return true;
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

        $session_type = explodecontent(',',$AppConfig->session_type);
        $sessions = self::getjcdates($nbsession);

        $content = "";
        foreach ($sessions as $date) {
            if (self::dateexists($date)) {
                $session = new Session($this->db,$date);
            } else {
                $session = new Session($this->db);
                $session->date = $date;
            }
            // Get type options
            $typeoptions = "";
            foreach ($session_type as $type) {
                if ($type === $session->type) {
                    $typeoptions .= "<option value='$type' selected>$type</option>";
                } else {
                    $typeoptions .= "<option value='$type'>$type</option>";
                }
            }

            // Get time
            $time = explodecontent(',',$session->time);
            $timefrom = $time[0];
            $timeto = $time[1];

            // Get presentations
            $presentations = "";
            $presids = explodecontent(',',$session->presid);
            $chairs = explodecontent(',',$session->chairs);
            for ($i=0;$i<$AppConfig->max_nb_session;$i++) {
                $presid = (isset($presids[$i]) ? $presids[$i] : false);
                $chair = (isset($chairs[$i]) ? $chairs[$i] : "TBA");
                $pres = new Presentation($this->db,$presid);
                $presentations .= $pres->showinsessionmanager($chair,$session->date);
            }

            $content .= "
            <div class='session_div'>
                <div class='session_header'>
                    <div class='session_date'>$session->date</div>
                    <div class='session_status'>$session->status</div>
                    <div class='feedback_$session->date' style='width: auto;'>
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
        $AppConfig = new AppConfig($this->db);

        // Get journal club days
        $jc_days = $this->getjcdates($nsession);

        // Get future planned dates
        $dates = $this->getsessions($nsession);
        if ($dates == false) {$dates = array();}

        // Get futures journal club sessions
        $content = "";
        foreach ($jc_days as $day) {
            if (in_array($day,$dates)) {
                $session = new Session($this->db,$day);
                $sessioncontent = $session->showsession($mail);
            } else {
                $session = new Session($this->db);
                $sessioncontent = "
                <div style='display: block; text-align: justify; background-color: rgba(255,255,255,.5); padding: 5px; margin: 0;'>
                    <div style='display: inline-block; width: 100%; padding-left: 10px; text-align: left; vertical-align: middle;'>
                        <span style='font-weight: bold; color: #CF5151;'>$AppConfig->max_nb_session presentation(s) available</span>
                    </div>
                </div>";
            }
            $type = ucfirst($session->type);
            $content .= "
            <div style='display: block; margin: 5px auto 0 auto;'>
                <div style='display: block; margin: 0;'>
                    <div style='display: inline-block; position: relative; text-align: center; height: 20px; line-height: 20px; width: 100px; background-color: #555555; color: #FFF; padding: 5px;'>
                        $day
                    </div>
                    <div style='display: inline-block; position: relative; text-align: center; height: 20px; line-height: 20px; width: 100px; background-color: rgba(207,81,81,.7); color: #FFF; padding: 5px;'>
                        $type
                    </div>
                </div>
                <div style='padding: 10px 20px 10px 10px; background-color: #eee; margin: 0; border: 1px solid rgba(175,175,175,.8);'>
                $sessioncontent
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
    public $presid = "";
    public $speakers = "";
    public $chairs = "";
    public $nbpres = 0;

    /**
     * @param DbSet $db
     * @param null $date
     */
    public function __construct(DbSet $db,$date=null) {
        parent::__construct($db);
        $this->db = $db;
        $this->tablename = $this->db->tablesname["Session"];

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
    public function make($post) {
        if (!parent::dateexists($post['date'])) {

            $class_vars = get_class_vars("Session");
            if (array_key_exists("presid", $post)) {
                // Pseudo randomly choose a chairman for this presentation
                if (empty($post['speakers'])) $post['speakers'] = null;
                $post['chairs'] = Sessions::getchair($post['date'],$post['speakers']);
            }

            $postkeys = array_keys($post);

            $variables = array();
            $values = array();
            foreach ($class_vars as $name=>$value) {
                if (in_array($name,array("db","tablename"))) continue;

                if (in_array($name,$postkeys)) {
                    $escaped = $this->db->escape_query($post[$name]);
                } else {
                    $escaped = $this->db->escape_query($this->$name);
                }
                $variables[] = "$name";
                $values[] = "'$escaped'";
            }

            $variables = implode(',',$variables);
            $values = implode(',',$values);

            // Add publication to the database
            if (!$this->db->addcontent($this->tablename,$variables,$values)) {
                return false;
            }
        } else {
            self::get($post['date']);
            if (!self::update($post)) {
                return false;
            };
        }
        return self::get($post['date']);
    }

    /**
     * Update session status
     * @return bool
     */
    function updatestatus() {
        $pres = explodecontent(',',$this->presid);
        $this->nbpres = count($pres);
        if ($this->nbpres == 0) {
            $status = "Free";
        } elseif ($this->nbpres<$this->max_nb_session) {
            $status = "Booked";
        } else {
            $status = "Booked out";
        }
        $escapedstatus = $this->db->escape_query($status);
        $escapednbpres = $this->db->escape_query($this->nbpres);
        return $this->db->updatecontent($this->tablename,array('nbpres','status'),array($escapednbpres,$escapedstatus),array("date"),array("'$this->date'"));
    }

    /**
     * Get session info
     * @param null $date
     * @return bool
     */
    public function get($date=null) {
        if ($date == null) $date = $this->date;

        $class_vars = get_class_vars("Session");
        $sql = "SELECT * FROM $this->tablename WHERE date='$date'";
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
     * Update session info
     * @param array $post
     * @return bool
     */
    public function update($post=array()) {
        $this->status = parent::isbooked($this->date);

        // Check if presentation does not already exist for this day
        if (array_key_exists('presid', $post)) {
            $oldpres = explodecontent(',',$this->presid);
            if (in_array($post['presid'],$oldpres)) {
                if (!self::chairexist()) $post['chairs'] = Sessions::getchair($post['date'],$post['speakers']);
                return self::update_pres($post);
            }
        }

        $class_vars = get_class_vars("Session");
        $postkeys = array_keys($post);
        foreach ($class_vars as $name => $value) {
            if (in_array($name,array("db","tablename"))) continue;

            if (in_array($name,$postkeys)) {
                if (in_array($name,array("presid","chairs","speakers"))) {
                    $oldval = explodecontent(',',$this->$name);
                    $oldval[] = $post[$name];
                    $oldval = implode(',',$oldval);
                    $escaped = $this->db->escape_query($oldval);
                } else {
                    $escaped = $this->db->escape_query($post[$name]);
                }
            } else {
                $escaped = $this->db->escape_query($this->$name);
            }

            if (!$this->db->updatecontent($this->tablename,$name,"'$escaped'",array("date"),array("'$this->date'"))) {
                return false;
            }
        }

        self::get();
        return true;
    }

    /**
     * Check if a chair has already been assigned
     * @return bool
     */
    public function chairexist() {
        $chairs = explodecontent(',',$this->chairs);
        $presids = explodecontent(',',$this->presid);
        if (empty($chairs)) return false;
        return count($chairs)<count($presids);
    }

    /**
     * Update presentation information (speaker, chairman) in this session
     *
     * @param $post
     * @return bool
     */
    private function update_pres($post) {
        $presid = $post['presid'];
        $oldpres = explodecontent(',',$this->presid);
        $oldchair = explodecontent(',',$this->chairs);
        $oldspeaker = explodecontent(',',$this->speakers);
        $i = 0;
        foreach ($oldpres as $id) {
            if ($id == $presid) {
                if (!empty($post['chairs'])) $oldchair[$i] = $post['chairs'];
                if (!empty($post['speakers'])) $oldspeaker[$i] = $post['speakers'];
                break;
            }
            $i++;
        }
        $escapedpresid = $this->db->escape_query(implode(',',$oldpres));
        $escapedchairs = $this->db->escape_query(implode(',',$oldchair));
        $escapedspeakers = $this->db->escape_query(implode(',',$oldspeaker));
        return $this->db->updatecontent($this->tablename,array('presid','chairs','speakers'),array("$escapedpresid","$escapedchairs","$escapedspeakers"),array("date"),array("'$this->date'"));
    }

    /**
     * Delete a presentation from this session
     * @param $presid
     * @return bool
     */
    public function delete_pres($presid) {
        $presids = explodecontent(',',$this->presid);
        $key = 0;
        if (in_array($presid,$presids)) {
            $chairs = explodecontent(',',$this->chairs);
            $speakers = explodecontent(',',$this->speakers);
            foreach ($presids as $id) {
                if ($id == $presid) {
                    break;
                }
                $key += 1;
            }

            $chairs = array_values(array_diff($chairs,array($chairs[$key])));
            $speakers = array_values(array_diff($speakers,array($presids[$key])));
            $presids = array_values(array_diff($presids,array($presids[$key])));
            $this->presid = implode(',',$presids);
            $this->chairs = implode(',',$chairs);
            $this->speakers = implode(',',$speakers);
            return $this->update();
        } else {
            var_dump("not in session");
            return true;
        }
    }

    /**
     * Show session (list)
     * @param bool $mail
     * @return string
     */
    public function showsession($mail=true) {
        $content = "";
        $presids = explodecontent(',',$this->presid);
        $chairs = explodecontent(',',$this->chairs);
        $maxtopres = ($this->max_nb_session >= $this->nbpres) ? $this->max_nb_session:$this->nbpres;
        for ($i=0;$i<$maxtopres;$i++) {
            $presid = (isset($presids[$i]) ? $presids[$i] : false);
            $chair = (isset($chairs[$i]) ? $chairs[$i] : "TBA");
            $pub = new Presentation($this->db,$presid);
            $content .= $pub->showinsession($chair,$mail);
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

        $presids = explodecontent(',',$this->presid);
        $chairs = explodecontent(',',$this->chairs);
        $time = explodecontent(',',$this->time);
        $time_from = $time[0];
        $time_to = $time[1];
        if (count($presids) == 0) return "Nothing planned yet";

        $content = "<div style='background-color: rgba(255,255,255,.5); padding: 5px; margin-bottom: 10px; border: 1px solid #bebebe;'>
                <div style='display: inline-block; margin: 0 0 5px 0;'><b>Date: </b>$this->date</div>
                <div style='display: inline-block; margin: 0 5px 5px 0;'><b>From: </b>$time_from <b>To: </b>$time_to</div>
                <div style='display: inline-block; margin: 0 5px 5px 0;'><b>Room: </b> $AppConfig->room</div><br>
                Our next session will host $this->nbpres presentations.
            </div>";
        $i = 0;
        foreach ($presids as $presid) {
            if ($prestoshow != false && $presid !=$prestoshow) continue;

            $pres = new Presentation($this->db,$presid);
            $chair = $chairs[$i];
            if ($chair !== 'TBA') {
                $chair = new User($this->db,$chair);
                $chair = $chair->fullname;
            }
           // Get file list
            $filediv = "";
            if ($show && !empty($pres->link)) {
                $filelist = explodecontent(',',$pres->link);
                $filecontent = "";
                foreach ($filelist as $file) {
                    $ext = explode('.',$file);
                    $ext = strtoupper($ext[1]);
                    $urllink = $AppConfig->site_url."uploads/".$file;
                    $filecontent .= "<div style='display: inline-block; height: 15px; line-height: 15px; text-align: center; padding: 5px; white-space: pre-wrap; min-width: 40px; width: auto; margin: 5px; cursor: pointer; background-color: #bbbbbb; font-weight: bold;'><a href='$urllink' target='_blank'>$ext</a></div>";
                }
                $filediv = "<div style='display: block; text-align: justify; width: 95%; min-height: 20px; height: auto; margin: auto; background-color: #444444;'>
                    $filecontent
                </div>";
            }

            $type = ucfirst($pres->type);
            $content .= "
            <div style='width: 100%; padding-bottom: 5px; margin: auto auto 10px auto; background-color: rgba(255,255,255,.5); border: 1px solid #bebebe;'>
                <div style='display: block; margin: 0 0 15px 0; padding: 0; text-align: justify; height: 20px; line-height: 20px; width: 100%;'>
                    <div style='display: inline-block; margin: 0; text-align: center; width: 100px; background-color: #555555; color: #FFF; padding: 5px;'>
                        $type
                    </div>
                    <div style='display: inline-block; width: auto; padding: 5px; margin-left: 30px;'>
                        <div style='font-weight: bold; font-size: 18px;'>$pres->title</div>
                    </div>
                </div>
                <div style='width: 95%; text-align: justify; margin: auto; padding: 5px 10px 0 10px; background-color: rgba(250,250,250,1); border-bottom: 5px solid rgba(207,81,81,.5);'>
                    <div style='display: inline-block; margin-left: 0; font-size: 15px; font-weight: 300; width: 50%;'>
                        <b>Authors:</b> $pres->authors
                    </div>
                    <div style='display: inline-block; width: 45%; margin: 0 auto 0 0; text-align: right;'>
                        <div style='display: inline-block; font-size: 15px; font-weight: 300;'><b>Speaker:</b> $pres->orator</div>
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
