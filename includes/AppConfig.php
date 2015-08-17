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
 * Class AppConfig
 *
 * Handles application configuration information and routines (updates, get).
 */
class AppConfig extends AppTable {

    protected $table_data = array(
        "id" => array("INT NOT NULL AUTO_INCREMENT", false),
        "variable" => array("CHAR(20)", false),
        "value" => array("TEXT", false),
        "primary" => "id");
    /**
     * Application info
     *
     */
    public $status = 'On';
    public $app_name = "Journal Club Manager";
    public $version = "1.3.5";
    public $author = "Florian Perdreau";
    public $repository = "https://github.com/Fperdreau/jcm";
    public $sitetitle = "Journal Club";
    public $site_url = "(e.g. http://www.mydomain.com/Pjc/)";
    public $max_nb_attempt = 5; // Maximum nb of login attempt

    /**
     * Journal club info
     *
     */
    public $jc_day = "thursday";
    public $room = "H432";
    public $jc_time_from = "17:00";
    public $jc_time_to = "18:00";
    public $max_nb_session = 2;

    /**
     * Session info
     *
     */
    public $session_type = array(
        "Journal Club"=>array('TBA'),
        'Business Meeting'=>array('TBA'),
        'No group meeting'=>array('TBA'));
    public $session_type_default = "Journal Club";
    public $pres_type = "paper,research,methodology,guest,minute";

    /**
     * Lab info
     *
     */
    public $lab_name = "Your Lab name";
    public $lab_street = "Your Lab address";
    public $lab_postcode = "Your Lab postal code";
    public $lab_city = "Your Lab city";
    public $lab_country = "Your Lab country";
    public $lab_mapurl = "Google Map";

    /**
     * Mail host information
     *
     */
    public $mail_from = "jc@journalclub.com";
    public $mail_from_name = "Journal Club";
    public $mail_host = "smtp.gmail.com";
    public $mail_port = "465";
    public $mail_username = "";
    public $mail_password = "";
    public $SMTP_secure = "ssl";
    public $pre_header = "[Journal Club]";

    /**
     * Uploads settings
     *
     */
    public $upl_types = "pdf,doc,docx,ppt,pptx,opt,odp";
    public $upl_maxsize = 10000000;

    /**
     * Constructor
     * @param AppDb $db
     * @param bool $get
     */
    public function __construct(AppDb $db,$get=true) {
        parent::__construct($db, 'AppConfig',$this->table_data);
        if ($get) {
            self::get();
        }
    }
    /**
     * Get application settings
     * @return bool
     */
    public function get() {
        $sql = "select variable,value from $this->tablename";
        $req = $this->db->send_query($sql);
        while ($row = mysqli_fetch_assoc($req)) {
            $varname = $row['variable'];
            $value = ($varname == "session_type") ? json_decode($row['value'],true):htmlspecialchars_decode($row['value']);
            $this->$varname = $value;
        }
        return true;
    }

    /**
     * Update application settings
     * @param array $post
     * @return bool
     */
    public function update($post=array()) {
        $class_vars = get_class_vars("AppConfig");
        $postkeys = array_keys($post);

        foreach ($class_vars as $name => $value) {
            if (in_array($name,array("db","tablename","table_data"))) continue;
            $newvalue = (in_array($name,$postkeys)) ? $post[$name]:$this->$name;
            $newvalue = ($name == "session_type") ? json_encode($newvalue):$newvalue;
            $this->$name = $newvalue;

            $exist = $this->db->getinfo($this->tablename,"variable",array("variable"),array("'$name'"));
            if (!empty($exist)) {
                $this->db->updatecontent($this->tablename,array("value"=>$newvalue),array("variable"=>$name));
            } else {
                $this->db->addcontent($this->tablename,array("variable"=>$name,"value"=>$newvalue));
            }
        }
        return true;
    }
}
