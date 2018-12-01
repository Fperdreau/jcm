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

use includes\BaseModel;
use includes\Template;

/**
 * Class Settings
 * @package Core
 */
class Settings extends BaseModel
{

    /**
     * Object name
     * @var null|string
     */
    public $object;

    /**
     * Object settings
     * e.g.: array('setting1'=>value)
     * @var array|null
     */
    public $settings;

    /**
     * @var array $all: All settings
     */
    private $all;

    /**
     * Constructor
     * @param string $class_name : object name
     * @param null|array $settings : object's settings
     */
    public function __construct($class_name = null, $settings = null)
    {
        parent::__construct();
        $this->object = $class_name;
        $this->settings = $settings;

        $this->loadAll();
        $this->load();
    }

    /**
     * Get application settings
     */
    public function load()
    {
        $this->loadAll();
        if (!is_null($this->settings) && !is_null($this->all)) {
            foreach ($this->settings as $setting => $value) {
                if (key_exists($this->object, $this->all) and key_exists($setting, $this->all[$this->object])) {
                    $new_value = $this->all[$this->object][$setting];
                    if (is_array($value)) {
                        $new_value = json_decode($new_value);
                    }
                    $this->settings[$setting] = $new_value;
                }
            }
        }
    }

    /**
     * Get Default settings
     *
     * @return void
     */
    private function getDefaults()
    {
        $includeList = scandir(PATH_TO_INCLUDES);
        $result = array();
        foreach ($includeList as $includeFile) {
            if (!in_array(
                $includeFile,
                array('.', '..', 'Settings.php', 'PasswordHash.php', 'Autoloader.php', 'BaseModel.php')
            )
            ) {
                $split = explode('.', $includeFile);
                $className = $split[0];
                $instClassName = '\\includes\\' . $className;
                if (class_exists($instClassName, true)) {
                    if (property_exists($instClassName, 'settings') && defined("{$instClassName}::CONSTANT_NAME")) {
                        try {
                            $result[$className]= $instClassName::settings;
                        } catch (Exception $e) {
                            Logger::getInstance(APP_NAME)->error("Calling {$instClassName[0]}->settings");
                            return null;
                        }
                    }
                }
            }
        }
    }

    /**
     * Load all settings and group them by controller
     */
    private function loadAll()
    {
        foreach ($this->getAll() as $object => $item) {
            foreach ($item as $setting => $value) {
                $this->all[$object][$setting] = $value;
            }
        }
        return $this->all;
    }

    /**
     * Get settings from object name
     * @param $object
     * @param null $setting
     * @return null|mixed
     */
    public function getByObject($object, $setting = null)
    {
        if (key_exists($object, $this->loadAll())) {
            if (!is_null($setting)) {
                if (key_exists($setting, $this->all[$object])) {
                    return $this->all[$object][$setting];
                } else {
                    return null;
                }
            } else {
                return $this->all[$object];
            }
        } else {
            return null;
        }
    }

    /**
     * Update settings
     * @param array $post : associative array providing new data (varName=>value)
     * @param array $id
     * @return bool
     */
    public function update(array $post, array $id)
    {
        // Sanitize posted data
        $result = true;
        foreach ($this->settings as $varName => $value) {
            $new_value = isset($post[$varName]) ? $post[$varName] : $value;
            if (is_array($value)) {
                $new_value = json_encode($new_value);
            }

            if ($this->isExist(array("variable"=>$varName, "object"=>$this->object))) {
                $result = $this->db->update(
                    $this->tablename,
                    array("value"=>$new_value),
                    array("variable"=>$varName, "object"=>$this->object)
                );
            } else {
                $result =$this->db->insert(
                    $this->tablename,
                    array("object"=>$this->object,"variable"=>$varName,"value"=>$new_value)
                );
            }
        }
        return $result;
    }

    /**
     * Get all settings grouped by controller from Db
     */
    private function getAll()
    {
        $results_array = array();
        if ($this->db->tableExists($this->tablename)) {
            $sql = "SELECT * FROM {$this->tablename}";
            $req = $this->db->sendQuery($sql);
            while ($row = $req->fetch_assoc()) {
                $results_array[$row['object']][$row['variable']] = $row['value'];
            }
        }
        return $results_array;
    }

    /**
     * Render Settings index page
     * @return string|null
     */
    public function index()
    {
        $includeList = scandir(PATH_TO_INCLUDES);
        $result = null;
        foreach ($includeList as $includeFile) {
            if (!in_array($includeFile, array('.', '..', 'PasswordHash.php', 'Autoloader.php', 'BaseModel.php'))) {
                $split = explode('.', $includeFile);
                $className = $split[0];
                $instClassName = '\\includes\\' . $className;
                if (class_exists($instClassName, true)) {
                    if (method_exists($instClassName, 'SettingsForm')) {
                        try {
                            $result .= Template::section($instClassName::settingsForm($this->getByObject($className)));
                        } catch (\Exception $e) {
                            Logger::getInstance(APP_NAME)->error("Calling {$instClassName[0]}->SettingsForm()");
                            return null;
                        }
                    }
                }
            }
        }
        return $result;
    }
}
