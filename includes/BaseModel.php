<?php
/**
 * File for class AppTable
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

 namespace includes;
 
 use Exception;

/**
 * Class AppTable
 *
 * Used by any class that needs to store information in database. Handle creation of tables, updates and retrieval of
 * information from the database.
 */
abstract class BaseModel
{

    /**
     * @var Db
     */
    protected $db; // Instantiation of db

    /**
     * @var $Settings Settings
     */
    protected $Settings;

    /**
     * @var $settings null|array
     */
    protected $settings;

    /**
     * @var string $tablename
     */
    protected $tablename; // Table name

    /**
     * @var array $table_data
     */
    protected $table_data; // Table's data (array)

    /**
     * @var array $default_exclude
     */
    private static $default_exclude = array("id", "db", "tablename", "table_data", "logger", 'Settings', 'settings');

    /**
     * Constructor
     * @param bool|string $plugin: plugin name
     */
    public function __construct($plugin = false)
    {
        $this->db = Db::getInstance();
        if ($plugin !== false) {
            $split = explode('\\', $plugin);
            $this->tablename = $this->db->config['dbprefix'] . '_' . end($split);
        } else {
            $this->tablename = $this->getTableName();
        }
        $this->table_data = $this->getTableData(strtolower(get_class($this)));

        // Load settings
        $this->loadSettings();
    }

    /**
     * Parse new date
     * @param array $post
     * @param array $exclude
     * @return array
     */
    protected function parseData($post = array(), $exclude = array())
    {
        $class_vars = get_class_vars(get_called_class());
        $post_keys = array_keys($post);
        $content = array();
        foreach ($post as $name => $value) {
            if (in_array($name, array_keys($class_vars)) && !in_array($name, $exclude)
            && !in_array($name, self::$default_exclude)) {
                $value = in_array($name, $post_keys) ? $post[$name]: $this->$name;
                $this->$name = $value;
                $value = (is_array($value)) ? json_encode($value) : $value;
                $content[$name] = $value;
            }
        }
        return $content;
    }

    /**
     * Load Settings instance
     * @return Settings
     */
    protected function loadSettings()
    {
        if (!is_null($this->settings) && is_null($this->Settings)) {
            $this->Settings = new Settings(get_called_class(), $this->settings);
            $this->settings = $this->Settings->settings;
        }
        return $this->Settings;
    }

    /**
     * Load Controller settings
     * @param null $setting
     * @return mixed
     * @throws Exception
     */
    public function getSettings($setting = null)
    {
        if (!is_null($this->settings)) {
            $this->loadSettings()->load();

            if (is_null($setting)) {
                return $this->Settings->settings;
            } elseif (!is_null($setting) && key_exists($setting, $this->Settings->settings)) {
                return $this->Settings->settings[$setting];
            } else {
                throw new Exception("Setting '{$setting}' for '" . get_called_class() . "' does not exist!");
            }
        } else {
            return null;
        }
    }

    /**
     * Set settings
     * @param array $data
     * @return bool
     */
    private function setSettings(array $data)
    {
        if (!is_null($this->settings)) {
            foreach ($data as $key => $value) {
                if (key_exists($key, $this->settings)) {
                    $this->settings[$key] = $value;
                }
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Update controller settings
     *
     * @param array $data: post data
     * @return array
     */
    public function updateSettings(array $data)
    {
        if ($this->setSettings($data)) {
            if ($this->Settings->update($this->settings, array('object'=>__CLASS__))) {
                return array('status'=>true, 'msg'=>'Settings updated');
            } else {
                return array('status'=>false, 'msg'=>'Sorry, something went wrong!');
            }
        } else {
            return array('status'=>true, 'msg'=>'Nothing to update');
        }
    }

    /**
     * Create or update table
     * @param bool $op
     * @return array
     */
    public function installDb($op = false)
    {
        if (is_null($this->getTableData(get_class($this)))) {
            return array('status'=>true, 'msg'=>null);
        }

        try {
            if ($this->db->makeorupdate($this->getTableName(), $this->getTableData(get_class($this)), $op)) {
                $result['status'] = true;
                $result['msg'] = "'{$this->tablename}' table created";
                Logger::getInstance(APP_NAME, get_class($this))->info($result['msg']);
            } else {
                $result['status'] = false;
                $result['msg'] = "'{$this->tablename}' table not created";
                Logger::getInstance(APP_NAME, get_class($this))->critical($result['msg']);
            }
            return $result;
        } catch (Exception $e) {
            Logger::getInstance(APP_NAME, get_class($this))->critical($e);
            $result['status'] = false;
            $result['msg'] = $e;
            return $result;
        }
    }

    /**
     * This function returns database table name from class name
     * @return string
     */
    protected function getTableName()
    {
        $split = explode('\\', get_class($this));
        return Db::getInstance()->genName(end($split));
    }

    /**
     * This function returns table information from schema.php file
     * @param string $tableName
     * @return array
     */
    protected static function getTableData($tableName)
    {
        $tableName = strtolower($tableName);
        $tables_data = require(PATH_TO_APP . 'config' . DS . 'schema.php');
        if (key_exists($tableName, $tables_data)) {
            return $tables_data[$tableName];
        } else {
            return null;
        }
    }

    /**
     * Sanitize $_POST content
     * @param array $post
     * @return mixed
     */
    protected function sanitize(array $post)
    {
        foreach ($post as $key => $value) {
            if (!is_array($value)) {
                $post[$key] = htmlspecialchars($value);
            }
        }
        return $post;
    }

    /**
     * Retrieve all elements from the selected table
     * @param array $ref
     * @param array $filter
     * @return array|mixed
     */
    public function all(array $ref = array(), array $filter = null)
    {
        $dir = (!is_null($filter) && isset($filter['dir'])) ? strtoupper($filter['dir']):'DESC';
        $param = (!is_null($filter) && isset($filter['order'])) ? "ORDER BY `{$filter['order']}` ".$dir : null;
        $limit = (!is_null($filter) && isset($filter['limit'])) ? " LIMIT `{$filter['limit']}` " : null;
        return $this->db->resultSet($this->tablename, array('*'), $ref, $param . $limit);
    }

    /**
     * Update table entry
     * @param array $data
     * @param array $ref
     * @return bool
     */
    public function update(array $data, array $ref)
    {
        return $this->db->update($this->tablename, $data, $ref);
    }

    /**
     * Delete table entry
     * @param array $ref
     * @return bool
     */
    public function delete(array $ref)
    {
        return $this->db->delete($this->tablename, $ref);
    }

    /**
     * Add to the db
     * @param array $post
     * @return mixed
     */
    public function add(array $post)
    {
        return $this->db->insert($this->tablename, $post);
    }

    /**
     * Get information from db
     * @param array $ref
     * @return array
     */
    public function get(array $ref)
    {
        return $this->db->single($this->tablename, array('*'), $ref);
    }

    /**
     * Get id of last inserted row
     * @return int
     */
    public function getLastID()
    {
        $data = $this->db->sendQuery("SELECT max(id) from {$this->tablename}");
        return (int)$data->fetch_row()[0];
    }

    /**
     * @param $ref
     * @return array
     */
    public function search($ref)
    {
        $search = array();
        foreach ($ref as $field => $value) {
            $search[] = "{$field}='{$value}'";
        }
        $search = implode('AND ', $search);
        $sql = "SELECT * FROM {$this->tablename} WHERE {$search}";
        $req = $this->db->sendQuery($sql);
        $data = array();
        while ($row = mysqli_fetch_assoc($req)) {
            $data[] = $row;
        }
        return $data;
    }

    /**
     * Map associative array to given object
     * @param $data
     * @return $this
     */
    protected function map($data)
    {
        $class_name = get_class($this);
        foreach ($data as $var_name => $value) {
            if (property_exists($class_name, $var_name)) {
                $prop = new \ReflectionProperty($class_name, $var_name);
                if (!$prop->isStatic()) {
                    $this->$var_name = (is_array($value)) ? $value : htmlspecialchars_decode($value);
                }
            }
        }
        return $this;
    }

    /**
     * Checks if id exists in a column
     * @param array $ref: array('column_name'=>'id')
     * @param null $tablename
     * @return bool
     */
    public function isExist(array $ref, $tablename = null)
    {
        $table_name = (is_null($tablename)) ? $this->tablename : $tablename;
        return !empty($this->db->single($table_name, array('*'), $ref));
    }

    /**
     * Create specific ID for new item
     * @param $refId
     * @return string
     */
    public function generateID($refId)
    {
        $id = date('Ymd').rand(1, 10000);

        // Check if random ID does not already exist in our database
        $prev_id = $this->db->column($this->tablename, $refId);
        while (in_array($id, $prev_id)) {
            $id = date('Ymd').rand(1, 10000);
        }
        return $id;
    }

    /**
     * Get class name without namespace
     *
     * @param string $className
     * @return string
     */
    protected static function getClassName($className = null)
    {
        if (is_null($className)) {
            $className = get_called_class();
        }
        $reflect = new \ReflectionClass($className);
        return $reflect->getShortName();
    }
}
