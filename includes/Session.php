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
require($_SESSION['path_to_includes'].'includes.php');

class Sessions {
// Mother class Sessions
// Management/display of sessions

    // Constructor
    function __construct() {
    }

    // Get all sessions
    public function getsessions($next=null) {
        require($_SESSION['path_to_app'].'config/config.php');
        $db_set = new DB_set();

        $sql = "SELECT date FROM $session_table";
        if ($next !== null) {
            $sql .= " WHERE date>=CURDATE()";
        }
        $sql .= " ORDER BY date ASC";
        $req = $db_set->send_query($sql);
        $sessions = array();
        while ($row = mysqli_fetch_assoc($req)) {
            $sessions[] = $row['date'];
        }
        if (empty($sessions)) {$sessions = false;}
        return $sessions;
    }

    // Get journal club days
    public function getjcdates($nsession=20) {
        // Get application settings
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
        return $jc_days;
    }

    // Check if date already exist
    protected function dateexists($date) {
        $dates = self::getsessions();
        if ($dates === false) {$dates = array();}
        return in_array($date,$dates);
    }

    // Check if the date of presentation is already booked
    public function isbooked($date) {
        $config = new site_config('get');
        $session = new Session($date);
        if ($session === false) {
            return "Free";
        } elseif ($session->nbpres<$config->max_nb_session) {
            if ($session->nbpres == 0) {
                return "Free";
            } else {
                return "Booked";
            }
        } elseif ($session->nbpres>=$config->max_nb_session) {
            return "Booked out";
        }
    }

    // Get a chairman for the session
    public function getchair($sessionid,$speaker) {
        $session = new Session($sessionid);

        // Get speakers planned for this session
        $speakers = explode(',',$session->speakers);
        $speakers[] = $speaker; // Add the current speaker to the list
        $speakers = array_diff($speakers,array(""));

        // Get chairmen planned for this sesion
        $cur_chairs = explode(',',$session->chairs);
        $cur_chairs = array_diff($cur_chairs,array(""));

        $exclude = array_merge($speakers,$cur_chairs);

         // Get list of organizers
        $organizers = site_config::getadmin();
        $chairs = array();
        if (!empty($organizers)) {
            foreach ($organizers as $organizer) {
                $chairs[] = $organizer['username'];
            }
        }

        $config = new site_config('get');
        if (empty($chairs)) {
            // If no users have the organizer status, the charman is to be announced.
            $chair = 'TBA';  // To Be Announced as a default
            $prevchairs = array(); // We start a new list of previous chairmen
        } else {
            $prevchairs = explode(',',$config->session_chairs);
            $prevchairs = array_diff($prevchairs,array(""));
            $allexclude = array_merge($exclude,$prevchairs);
            $possiblechairs = array_diff($chairs,$allexclude);
            if (!empty($possiblechairs)) {
                // We randomly pick a chairman among organizers who have not chaired a session yet, appart from the speakers and the other chairmen of this session.
                $possiblechairs = array_diff($possiblechairs,$exclude);
                $ind = rand(0,count($possiblechairs)-1);
                $chair = $possiblechairs[$ind];
            } else {
                // Otherwise, if all organizers already have been chairman once, we randomly pick one among all the organizers, appart from the speakers and the other chairmen of this session
                $possiblechairs = array_diff($chairs,$exclude);
                if (empty($possiblechairs)) {
                    // RARE: in case organizers are speakers and chairmen for this session, the chairman is to be announced.
                    $chair = 'TBA';
                } else {
                    $ind = rand(0,count($possiblechairs)-1);
                    $chair = $possiblechairs[$ind];
                }
                $prevchairs = array(); // We start a new list of previous chairmen
            }
        }

        // Update the previous chairmen list
        $prevchairs[] = $chair;
        $config->session_chairs = implode(',',$prevchairs);
        $config->update();

        return $chair;
    }

    // Check consistency between session/presentation tables
    public function checkcorrespondence() {
        require($_SESSION['path_to_app'].'config/config.php');
        $db_set = new DB_set();

        // Get presentations dates
        $sql = "SELECT date,id_pres FROM $presentation_table";
        $req = $db_set->send_query($sql);
        $dates = array();
        while ($row = mysqli_fetch_assoc($req)) {
            $dates[$row['date']][] = $row['id_pres'];
        }

        // See if dates are missing in the session table
        $sessions = self::getsessions();
        if ($sessions === false) {$sessions = array();}
        foreach ($dates as $date=>$id_pres) {
            foreach ($id_pres as $id) {
                $pres = new Presentation($id);
                $session = new Session();
                $chair = self::getchair($date,$pres->orator);
                $sessionpost = array(
                    'date'=>$date,
                    "status"=>"Booked",
                    "speakers"=>$pres->orator,
                    "presid"=>$id,
                    "chairs"=>$chair);
                if (!$session->make($sessionpost)) {
                    return false;
                }
            }
        }
        return true;
    }

    // Get all sessions
    public function managesessions($nbsession=4) {
        require($_SESSION['path_to_app'].'config/config.php');
        $db_set = new DB_set();
        $config = new site_config('get');
        $timeopt = maketimeopt();

        $session_type = explode(',',$config->session_type);
        $sessions = self::getjcdates($nbsession);

        $content = "";
        foreach ($sessions as $date) {
            if (self::dateexists($date)) {
                $session = new Session($date);
            } else {
                $session = new Session();
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
            $time = explode(',',$session->time);
            $timefrom = $time[0];
            $timeto = $time[1];

            // Get presentations
            $presentations = "";
            if ($session->status !== "FREE") {
                $i = 0;
                $presids = explode(',',$session->presid);
                $chairs = explode(',',$session->chairs);
                foreach ($presids as $presid) {
                    $chair = $chairs[$i];
                    $pres = new Presentation($presid);
                    $presentations .= $pres->showinsession($chair);
                    $i++;
                }
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

    // Display the upcoming presentation(home page/mail)
    function shownextsession($mail=false) {
        require($_SESSION['path_to_app'].'config/config.php');
        $show = $mail === true || (!empty($_SESSION['logok']) && $_SESSION['logok'] === true);

        $config = new site_config('get');
        $dates = self::getsessions(1);
        if ($dates !== false) {
            $session = new Session($dates[0]);
            $content = $session->showsessiondetails($show);
        } else {
            $content = "Nothing planned yet.";
        }
        return $content;
    }

    // Get list of future presentations (home page/mail)
    public function showfuturesession($nsession = 4,$mail=null) {
        require($_SESSION['path_to_app'].'config/config.php');
        $db_set = new DB_set();
        $config = new site_config('get');

        // Get journal club days
        $jc_days = self::getjcdates($nsession);

        // Get future planned dates
        $dates = self::getsessions($nsession);
        if ($dates == false) {$dates = array();}

        // Get futures journal club sessions
        $content = "";
        foreach ($jc_days as $day) {
            if (in_array($day,$dates)) {
                $session = new Session($day);
                $sessioncontent = $session->showsession($mail);
            } else {
                $session = new Session();
                $sessioncontent = "
                <div style='display: block; text-align: justify; background-color: rgba(255,255,255,.5); padding: 5px; margin: 0;'>
                    <div style='display: inline-block; width: 100%; padding-left: 10px; text-align: left; vertical-align: middle;'>
                        <span style='font-weight: bold; color: #CF5151;'>$config->max_nb_session presentation(s) available</span>
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
// Child class of Sessions
// Instantiates session objects

    public $date = "";
    public $status = "FREE";
    public $time = "";
    public $type = "Journal Club";
    public $presid = "";
    public $speakers = "";
    public $chairs = "";
    public $nbpres = 0;

    function __construct($date=null) {
        $config = new site_config('get');
        $this->time = "$config->jc_time_from,$config->jc_time_to";
        $this->type = $config->session_type_default;
        if ($date != null) {
            self::get($date);
        }
    }

    // Create session
    public function make($post) {
        require($_SESSION['path_to_app'].'config/config.php');

        $class_vars = get_class_vars("Session");
        $db_set = new DB_set();
        $config = new site_config('get');
        if (array_key_exists("presid", $post)) {
            $post['status'] = "Booked";
        }
        $postkeys = array_keys($post);

        $exist = parent::dateexists($post['date']);
        if ($exist === false) {
            $variables = array();
            $values = array();
            foreach ($class_vars as $name=>$value) {
                if (in_array($name,$postkeys)) {
                    $escaped = $db_set->escape_query($post[$name]);
                } else {
                    $escaped = $db_set->escape_query($this->$name);
                }
                $variables[] = "$name";
                $values[] = "'$escaped'";
            }

            $variables = implode(',',$variables);
            $values = implode(',',$values);

            // Add publication to the database
            if ($db_set->addcontent($session_table,$variables,$values)) {
                return true;
            } else {
                return false;
            }
        } else {
            self::get($post['date']);
            return self::update($post);
        }
    }

    // Get session info
    public function get($date=null) {
        require($_SESSION['path_to_app'].'config/config.php');

        if ($date == null) {
            $date = $this->date;
        }
        $class_vars = get_class_vars("Session");
        $db_set = new DB_set();
        $sql = "SELECT * FROM $session_table WHERE date='$date'";
        $req = $db_set -> send_query($sql);
        $data = mysqli_fetch_assoc($req);

        if (!empty($data)) {
            foreach ($data as $varname=>$value) {
                if (array_key_exists($varname,$class_vars)) {
                    $this->$varname = htmlspecialchars_decode($value);
                }
            }

            // Update nb of presentations in the db
            $pres = explode(',',$this->presid);
            $this->nbpres = count($pres);
            return true;
        } else {
            return false;
        }
    }

    // Update session info
    public function update($post=array()) {
        require($_SESSION['path_to_app'].'config/config.php');
        $db_set = new DB_set();

        $this->status = parent::isbooked($this->date);

        // Check if presentation does not already exist for this day
        if (array_key_exists('presid', $post)) {
            $oldpres = explode(',',$this->presid);
            if (in_array($post['presid'],$oldpres)) {
                return true;
            }
        }

        $class_vars = get_class_vars("Session");
        $postkeys = array_keys($post);
        foreach ($class_vars as $name => $value) {
            if (in_array($name,$postkeys)) {
                if (in_array($name,array("presid","chairs","speakers"))) {
                    $oldval = explode(',',$this->$name);
                    $oldval[] = $post[$name];
                    $oldval = implode(',',$oldval);
                    $escaped = $db_set->escape_query($oldval);
                } else {
                    $escaped = $db_set->escape_query($post[$name]);
                }
            } else {
                $escaped = $db_set->escape_query($this->$name);
            }

            if (!$db_set->updatecontent($session_table,$name,"'$escaped'",array("date"),array("'$this->date'"))) {
                return false;
            }
        }

        // Update nb of presentation
        self::get();

        $presid = explode(',',$this->presid);
        $escaped = $db_set->escape_query(count($presid));
        if (!$db_set->updatecontent($session_table,'nbpres',"'$escaped'",array("date"),array("'$this->date'"))) {
            return false;
        }
        return true;
    }

    // Delete a presentation from this session
    public function delete_pres($presid) {
        $presids = explode(',',$this->presid);
        if (in_array($presid,$presids)) {
            $chairs = explode(',',$this->chairs);
            $speakers = explode(',',$this->speakers);
            $key = array_search($presid,$presids);
            unset($chairs[$key]);
            unset($speakers[$key]);
            unset($presids[$key]);
            $this->presid = implode(',',$presids);
            $this->chairs = implode(',',$chairs);
            $this->speakers = implode(',',$speakers);
            return $this->update();
        } else {
            return false;
        }
    }

    // Show session (list)
    public function showsession($mail=true) {
        $config = new site_config('get');
        $content = "";
        $presids = explode(',',$this->presid);
        $chairs = explode(',',$this->chairs);
        $i = 0;
        foreach ($presids as $presid) {
            $pub = new Presentation($presid);

            // Get the chairman
            $chair = $chairs[$i];
            if ($chair !== 'TBA') {
                $chairman = new users($chair);
                $chairman = $chairman->fullname;
            } else {
                $chairman = $chair;
            }

            // Get the speaker
            $speaker = new users($pub->orator);

            // Make "Show" button
            if (null != $mail) {
                $show_but = "";
            } else {
                $show_but = "<div class='show_btn'><a href='#pub_modal' class='modal_trigger' id='modal_trigger_pubcontainer' rel='pub_leanModal' data-id='$pub->id_pres'>MORE</a></div>";
            }
            $type = ucfirst($pub->type);
            $content .= "
            <div id='$pub->id_pres' style='display: block; margin: 0 auto 10px 0; padding-left: 10px; font-size: 14px; font-weight: 300; overflow: hidden;'>
                <div style='display: inline-block; vertical-align: middle; text-align: left; width: 55%; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; margin: 0;'>
                    <label style='position: relative; left:0; top: 0; bottom: 0; background-color: rgba(207,81,81,.8); text-align: center; font-size: 13px; font-weight: 300; color: #EEE; padding: 7px 6px; z-index: 0;'>$type</label>
                    <div style='display: block; position: relative; width: 100%; border: 0; z-index: 1; background-color: #dddddd; padding: 5px; border-bottom: 1px solid rgba(207,81,81,.5);' class='show_pres'>
                        <a href='#pub_modal' class='modal_trigger' id='modal_trigger_pubcontainer' rel='pub_leanModal' data-id='$pub->id_pres'>$pub->title ($pub->authors)</a>
                    </div>
                </div>
                <div style='display: inline-block; vertical-align: middle; text-align: left; width: 20%; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; margin: 0;'>
                    <label style='position: relative; left:0; top: 0; bottom: 0; background-color: rgba(207,81,81,.8); text-align: center; font-size: 13px; font-weight: 300; color: #EEE; padding: 7px 6px; z-index: 0;'>Speaker</label>
                    <div style='display: block; position: relative; width: 100%; border: 0; z-index: 1;background-color: #dddddd; padding: 5px; border-bottom: 1px solid rgba(207,81,81,.5);'>$speaker->fullname
                    </div>
                </div>
                <div style='display: inline-block; vertical-align: middle; text-align: left; min-width: 20%; flex-grow: 1; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; margin: 0;'>
                    <label style='position: relative; left:0; top: 0; bottom: 0; background-color: rgba(207,81,81,.8); text-align: center; font-size: 13px; font-weight: 300; color: #EEE; padding: 7px 6px; z-index: 0;'>Chair</label>
                    <div style='display: block; position: relative; width: 100%; border: 0; z-index: 1; background-color: #dddddd; padding: 5px; border-bottom: 1px solid rgba(207,81,81,.5);'>$chairman
                    </div>
                </div>
            </div>
        ";
            /*$content .= "
            <div style='display: block; text-align: justify; background-color: rgba(255,255,255,.5); padding: 5px; margin: 0;'>
                <div style='display: block; width: 50px; background-color: #CF5151; color:#EEE; padding: 5px 2px 5px 2px; text-align: center;'>
                    $type
                </div>
                <div style='display: inline-block; width: 90%; padding-left: 10px; text-align: left; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; vertical-align: middle;'>
                    $pub->title ($pub->authors) presented by <span style='color: #CF5151;'>$pub->orator</span>
                </div>
                <div style='display: inline-block; vertical-align: middle;'>
                    $show_but
                </div>
            </div>";*/
            $i++;
        }
        if ($this->nbpres < $config->max_nb_session) {
            $rem = $config->max_nb_session - $this->nbpres;
            $content .= "
            <div style='display: block; text-align: justify; background-color: rgba(255,255,255,.5); padding: 5px; margin: 0;'>
                <div style='display: inline-block; width: 90%; padding-left: 10px; text-align: left; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; vertical-align: middle;'>
                    <span style='font-weight: bold; color: #CF5151;'>$rem presentation(s) available</span>
                </div>
            </div>
            ";
        }
        return $content;
    }

    // Show session details
    public function showsessiondetails($show=true) {
        $config = new site_config('get');

        $time = explode(',',$this->time);
        $time_from = $time[0];
        $time_to = $time[1];
        $content = "<div style='background-color: rgba(255,255,255,.5); padding: 5px; margin-bottom: 10px; border: 1px solid #bebebe;'>
                <div style='display: inline-block; margin: 0 0 5px 0;'><b>Date: </b>$this->date</div>
                <div style='display: inline-block; margin: 0 5px 5px 0;'><b>From: </b>$time_from <b>To: </b>$time_to</div>
                <div style='display: inline-block; margin: 0 5px 5px 0;'><b>Room: </b> $config->room</div><br>
                Our next session will host $this->nbpres presentations.
            </div>";
        $presids = explode(',',$this->presid);
        $chairs = explode(',',$this->chairs);
        $i = 0;
        foreach ($presids as $presid) {
            $pres = new Presentation($presid);
            $chair = $chairs[$i];
            if ($chair !== 'TBA') {
                $chair = new users($chair);
                $chair = $chair->fullname;
            }
           // Get file list
            $filecontent = "";
            if ($show && !empty($pres->link)) {
                $filelist = explode(',',$pres->link);
                foreach ($filelist as $file) {
                    $ext = explode('.',$file);
                    $ext = strtoupper($ext[1]);
                    $urllink = $config->site_url."uploads/".$file;
                    $filecontent .= "<div style='display: inline-block; height: 15px; line-height: 15px; text-align: center; padding: 5px; white-space: pre-wrap; min-width: 40px; width: auto; margin: 5px; cursor: pointer; background-color: #bbbbbb; font-weight: bold;'><a href='$urllink' target='_blank'>$ext</a></div>";
                }
            }
            $type = ucfirst($pres->type);
            $content .= "
            <div style='width: 100%; padding-bottom: 5px; margin: auto auto 10px auto; background-color: rgba(255,255,255,.5); border: 1px solid #bebebe;'>
                <div style='display: block; position: relative; margin: 0 0 5px; text-align: center; height: 20px; line-height: 20px; width: 100px; background-color: #555555; color: #FFF; padding: 5px;'>
                    $type
                </div>
                <div style='width: 95%; margin: auto; padding: 5px 10px 0px 10px; background-color: rgba(250,250,250,1); border-bottom: 5px solid #aaaaaa;'>
                    <div style='font-weight: bold; font-size: 18px;'>$pres->title</div>
                    <div style='display: inline-block; margin-left: 0; font-size: 15px; font-weight: 300;'><b>Authors:</b> $pres->authors</div>
                    <div style='display: inline-block; font-size: 15px; font-weight: 300;'><b>Presented by:</b> $pres->orator</div>
                    <div style='display: inline-block; font-size: 15px; font-weight: 300;'><b>Chaired by:</b> $chair</div>
                </div>
                <div style='width: 95%; text-align: justify; margin: auto; background-color: #eeeeee; padding: 10px;'>
                    <span style='font-style: italic; font-size: 13px;'>$pres->summary</span>
                </div>
                <div style='display: block; test-align: justify; width: 95%; min-height: 20px; height: auto; margin: auto; background-color: #444444;'>
                    $filecontent
                </div>
            </div>
            ";
            $i++;
        }
        return $content;
    }

}
