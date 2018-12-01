<?php
/**
 *
 * @author Florian Perdreau (fp@florianperdreau.fr)
 * @copyright Copyright (C) 2016 Florian Perdreau
 * @license <http://www.gnu.org/licenses/agpl-3.0.txt> GNU Affero General Public License v3
 *
 * This file is part of DropCMS.
 *
 * DropCMS is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * DropCMS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with DropCMS.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace includes;

/**
 * Class Config
 * This class handles application configuration
 */
class Config
{

    /**
     * @var $instance Config
     */
    private static $instance;

    /**
     * Configuration file content
     * @var array|null
     */
    private $settings;

    /**
     * Configuration folders
     * @var array
     */
    private static $folders = array('config','uploads');

    /**
     * Configuration file name
     * @var string
     */
    private $file_name;

    /**
     * Default settings
     * @var array
     */
    private static $default = array(
        "version"=>false,
        "dbname"=>"test",
        "host"=>"localhost",
        "dbprefix"=>"jcm",
        "username"=>"root",
        "passw"=>""
    );

    /**
     * Config constructor.
     */
    private function __construct()
    {
        $this->id = uniqid();
        $this->file_name = PATH_TO_CONFIG . 'config.php';
        $this->settings = $this->load();
    }

    /**
     * Factory for config instance
     * @return Config
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load configuration file
     * @return mixed|null
     */
    private function load()
    {
        if (is_file($this->file_name)) {
            require($this->file_name);
            return $config;
        } else {
            return self::$default;
        }
    }

    /**
     * Get setting
     * @param $key
     * @return mixed|null
     */
    public function get($key)
    {
        if (is_null($this->settings)) {
            return null;
        }

        if (!isset($this->settings[$key])) {
            return null;
        }
        return $this->settings[$key];
    }

    /**
     * Return all settings
     * @return array|mixed|null
     */
    public function getAll()
    {
        if (is_null($this->settings)) {
            return null;
        }
        return $this->settings;
    }

    /**
     * Create configuration files including database credentials and App version
     * @param $post
     * @return array
     */
    public static function createConfig($post)
    {
        $filename = PATH_TO_CONFIG . "config.php";
        $result = array('status'=>true, 'msg'=>null);
        if (is_file($filename)) {
            unlink($filename);
        }

        // Make folders
        foreach (self::$folders as $folder) {
            $dirname = PATH_TO_APP.'/'.$folder;
            if (is_dir($dirname) === false) {
                if (!mkdir($dirname, 0755)) {
                    $result['status'] = false;
                    $result['msg'] = "Could not create '$folder' directory";
                    return $result;
                }
            }
        }

        // Write configuration information to config/config.php
        $fields_to_write = array("version", "host", "username", "passw", "dbname", "dbprefix");
        $config = array();
        foreach ($post as $name => $value) {
            if (in_array($name, $fields_to_write)) {
                $config[] = '"' . $name . '" => "' . $value . '"';
            }
        }
        $config = implode(',', $config);
        $string = '<?php $config = array(' . $config . '); ?>';

        // Create new config file
        if ($fp = fopen($filename, "w+")) {
            if (fwrite($fp, $string) == true) {
                fclose($fp);
                $result['status'] = true;
                $result['msg'] = "Configuration file created!";
            } else {
                $result['status'] = false;
                $result['msg'] = "Impossible to write";
            }
        } else {
            $result['status'] = false;
            $result['msg'] = "Impossible to open the file";
        }

        return $result;
    }
}
