<?php
/**
 * File for class AppConfig
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
 * Class AppConfig
 *
 * Handles application's settings and routines (updates, get).
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
    public $status = 'On'; // Application's status (on or off)
    public $app_name = "Journal Club Manager"; // Application's name
    public $version = "1.4.4"; // Application's version
    public $author = "Florian Perdreau"; // Application's authors
    public $repository = "https://github.com/Fperdreau/jcm"; // Application's sources
    public $sitetitle = "Journal Club Manager"; //
    public static $site_url; // Web path to application
    public $max_nb_attempt = 5; // Maximum nb of login attempt

    /**
     * Session info
     *
     */
    public $jc_day = "";
    public $room = "";
    public $jc_time_from = "17:00";
    public $jc_time_to = "18:00";
    public $max_nb_session = 1;
    public $session_type = array(
        "Journal Club"=>array('TBA')
    );
    public $session_type_default = "Journal Club";
    public $pres_type = "paper,research,methodology,guest,minute";

    /**
     * Lab info
     *
     */
    public $lab_name;
    public $lab_street;
    public $lab_postcode;
    public $lab_city;
    public $lab_country;
    public $lab_mapurl;

    /**
     * Mail host information
     *
     */
    public $mail_from = "jcm@jcm.com";
    public $mail_from_name = "Journal Club Manager";
    public $mail_host = "";
    public $mail_port = "";
    public $mail_username = "";
    public $mail_password = "";
    public $SMTP_secure = "ssl";
    public $pre_header = "[JCM]";

    /**
     * Uploads settings
     *
     */
    public $upl_types = "pdf,doc,docx,ppt,pptx,opt,odp";
    public $upl_maxsize = 10000000;

    /**
     * Scheduled tasks
     */
    public $notify_admin_task = 'yes';

    /**
     * Constructor
     * @param AppDb $db
     * @param bool $get
     */
    public function __construct(AppDb $db,$get=true) {
        parent::__construct($db, 'AppConfig',$this->table_data);

        // Get App URL if not running in command line
        if (php_sapi_name() !== "cli") {
            $this->getAppUrl();
        }

        if ($get) {
            $this->get();
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
            $prop = new ReflectionProperty(get_class($this), $varname);
            if ($prop->isStatic()) {
                $this::$$varname = $value;
            } else {
                $this->$varname = $value;
            }
        }
        return true;
    }

    /**
     * Gets config value
     * @param string $variable
     * @return array
     */
    public function getConfig($variable) {
        $sql = "SELECT * FROM {$this->tablename} WHERE variable='{$variable}'";
        return $this->db->send_query($sql)->fetch_assoc();
    }

    /**
     * This function gets App's URL to root
     * @param null $lang: language
     * @return string
     */
    public function getAppUrl($lang=null) {
        if (is_null(self::$site_url) || !is_null($lang)) {
            $root = explode('/',  dirname($_SERVER['PHP_SELF']));
            $root = '/' . $root[1];
            self::$site_url = ( (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://' )
                . $_SERVER['HTTP_HOST'] . $root .'/';

            if(substr(self::$site_url, -2) == '//') {
                self::$site_url = substr(self::$site_url, 0, -1);
            }

            if (!(is_null($lang))) {
                self::$site_url .= $lang.'/';
            }
        }
        $_SESSION['BASE_URL'] = self::$site_url;
        return self::$site_url;
    }

    /**
     * Update application settings
     * @param array $post
     * @return bool
     */
    public function update($post=array()) {
        $class_vars = get_class_vars("AppConfig");
        $postkeys = array_keys($post);
        $result = false;
        foreach ($class_vars as $name => $value) {
            if (in_array($name,array("db","tablename","table_data"))) continue;
            $newvalue = (in_array($name,$postkeys)) ? $post[$name] : $this->get_setting($name);
            $newvalue = ($name == "session_type") ? json_encode($newvalue) : $newvalue;
            $this->set_setting($name, $newvalue);

            $exist = $this->db->getinfo($this->tablename,"variable",array("variable"),array("'$name'"));
            if (!empty($exist)) {
                $result = $this->db->updatecontent($this->tablename,array("value"=>$newvalue),array("variable"=>$name));
            } else {
                $result = $this->db->addcontent($this->tablename,array("variable"=>$name,"value"=>$newvalue));
            }
        }
        return $result;
    }

    /**
     * Get app setting
     * @param $setting
     * @return mixed
     */
    public function get_setting($setting) {
        $prop = new ReflectionProperty(get_class($this), $setting);
        if ($prop->isStatic()) {
            return $this::$$setting;
        } else {
            return $this->$setting;
        }
    }

    /**
     * Set app setting
     * @param string $setting
     * @param mixed $value
     */
    public function set_setting($setting, $value) {
        $prop = new ReflectionProperty(get_class($this), $setting);
        if ($prop->isStatic()) {
            $this::$$setting = $value;
        } else {
            $this->$setting = $value;
        }
    }
}
