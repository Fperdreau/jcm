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

class site_config {
    // Site info
    public $app_name = "Journal Club Manager";
    public $version = "v1.2.1";
    public $author = "Florian Perdreau";
    public $repository = "https://github.com/Fperdreau/jcm";
    public $sitetitle = "Journal Club";
    public $site_url = "(e.g. http://www.mydomain.com/Pjc/)";
    public $clean_day = 10;
    // Journal club info
    public $jc_day = "thursday";
    public $room = "H432";
    public $jc_time_from = "17:00";
    public $jc_time_to = "18:00";
    public $notification = "sunday";
    public $reminder = 1;
    // Lab info
    public $lab_name = "Your Lab name";
    public $lab_street = "Your Lab address";
    public $lab_postcode = "Your Lab postal code";
    public $lab_city = "Your Lab city";
    public $lab_country = "Your Lab country";
    public $lab_mapurl = "";
    // Mail host information
    public $mail_from = "jc@journalclub.com";
    public $mail_from_name = "Journal Club";
    public $mail_host = "smtp.gmail.com";
    public $mail_port = "465";
    public $mail_username = "";
    public $mail_password = "";
    public $SMTP_secure = "ssl";
    public $pre_header = "[Journal Club]";

    // Constructor
    public function __construct($get = null) {
        if ($get == 'get') {
            self::get_config();
        }
    }

    public function get_config() {
        require_once($_SESSION['path_to_includes'].'db_connect.php');
        require($_SESSION['path_to_app']."admin/conf/config.php");
        $db_set = new DB_set();
        $sql = "select variable,value from $config_table";
        $req = $db_set->send_query($sql);
        $class_vars = get_class_vars("site_config");
        while ($row = mysqli_fetch_assoc($req)) {
            $varname = $row['variable'];
            $value = $row["value"];
            if (array_key_exists($varname,$class_vars)) {
                $this->$varname = $value;
            }
        }
        return true;
    }

    // Update config
    public function update_config($post) {
        require_once($_SESSION['path_to_includes'].'db_connect.php');
        require($_SESSION['path_to_app']."admin/conf/config.php");
        $db_set = new DB_set();
        $class_vars = get_class_vars("site_config");
		$class_keys = array_keys($class_vars);
        foreach ($post as $name => $value) {
            if (in_array($name,$class_keys)) {
                $escape_value = htmlspecialchars($value);
				$exist = $db_set->getinfo($config_table,"variable",array("variable"),array("'$name'"));
	            if (!empty($exist)) {
	                $db_set->updatecontent($config_table,"value","'$escape_value'",array("variable"),array("'$name'"));
	            } else {
	            	$db_set->addcontent($config_table,"variable,value","'$name','$escape_value'");
	            }
			}
        }
        self::get_config();
        return true;
    }

    // Get organizers list
    function getadmin($admin=null) {
        require_once($_SESSION['path_to_includes'].'db_connect.php');
        require($_SESSION['path_to_app']."admin/conf/config.php");
        $db_set = new DB_set();
        $sql = "SELECT username,password,firstname,lastname,position,email,status FROM $users_table WHERE status='organizer'";
        if (null != $admin) {
        	$sql .= "or status='admin'";
        }

        $req = $db_set -> send_query($sql);
        $user_info = array();
        $cpt = 0;
        while ($row = mysqli_fetch_assoc($req)) {
            $user_info[]= $row;
            $cpt++;
        }
        return $user_info;
    }

    function generateuserslist($filter = null) {
        require_once($_SESSION['path_to_includes'].'users.php');
        require_once($_SESSION['path_to_includes'].'db_connect.php');
        require($_SESSION['path_to_app']."/admin/conf/config.php");

		if (null == $filter) {
			$filter = 'lastname';
		}

        $db_set = new DB_set();
        $sql = "SELECT username FROM $users_table ORDER BY $filter";

        $req = $db_set -> send_query($sql);
        $result =  "
            <div class='list-container'>
                <div class='list-heading' style='width: 10%'>First Name</div>
                <div class='list-heading' style='width: 10%'>Last Name</div>
                <div class='list-heading' style='width: 10%'>User Name</div>
                <div class='list-heading' style='width: 20%'>Email</div>
                <div class='list-heading' style='width: 10%'>Activated</div>
                <div class='list-heading' style='width: 5%'>Submissions</div>
                <div class='list-heading' style='width: 10%'>Status</div>
            </div>
        ";

        while ($cur_user = mysqli_fetch_array($req)) {
            $user = new users($cur_user['username']);

            $nbpres = $user->get_nbpres();
            // Compute age
            if ($user->active == 1) {
                $from = strtotime($user->date);
                $to   = date('Y-m-d');
                $diff = $to-$from;
	            $cur_age = date('d',$diff);
	            $cur_trage = "$cur_age days ago";
	            if ($cur_age >31) {
	                $cur_age = date('m',$diff);
	                $cur_trage = "$cur_age months ago";
	                if ($cur_age >12) {
	                    $cur_age = date('y',$diff);
	                    $cur_trage = "$cur_age years ago";
	                }
	            }
                $option_active = "<option value='desactivate'>Deactivate</option>";
            } else {
            	$cur_trage = "No";
                $option_active = "<option value='activate'>Activate</option>";
            }

            $result .= "
            <div class='list-container' id='section_$user->username'>
                <div class='list-section' style='width: 10%'>$user->firstname</div>
                <div class='list-section' style='width: 10%'>$user->lastname</div>
                <div class='list-section' style='width: 10%'>$user->username</div>
                <div class='list-section' style='width: 20%'>$user->email</div>
                <div class='list-section' style='width: 10%'>$cur_trage</div>
                <div class='list-section' style='width: 5%'>$nbpres</div>

                <div class='list-section' style='width: 10%'>
                    <select name='status' id='status' data-user='$user->username' class='modify_status'>
                        <option value='$user->status' selected='selected'>$user->status</option>
                        <option value='member'>Member</option>
                        <option value='admin'>Admin</option>
                        <option value='organizer'>Organizer</option>
                        $option_active
                        <option value='delete' style='background-color: rgba(207, 81, 81, .5);'>Delete</option>
                    </select>
                </div>
            </div>
            ";
        }
        return $result;
    }

}
