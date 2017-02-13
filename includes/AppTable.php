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

/**
 * Class AppTable
 *
 * Used by any class that needs to store information in database. Handle creation of tables, updates and retrieval of
 * information from the database.
 */
class AppTable {

    protected $db; // Instantiation of db
    protected $tablename; // Table name
    protected $table_data; // Table's data (array)
    private static $default_exclude = array("id", "db", "tablename", "table_data", "logger");

    /**
     * Constructor
     * @param $table_name
     * @param $table_data
     * @param bool $plugin
     */
    function __construct($table_name, $table_data, $plugin=False) {
        $this->db = AppDb::getInstance();
        if ($plugin !== False) {
            $this->tablename = $this->db->dbprefix.'_'.$plugin;
        } else {
            $this->tablename = $this->db->tablesname[$table_name];
        }
        $this->table_data = $table_data;
        $correct_config = $this->db->testdb($this->db->get_config());
        if ($correct_config['status'] && !$this->db->tableExists($this->tablename)) {
            $this->setup();
        }
    }

    /**
     * Parse new date
     * @param $class_vars
     * @param array $post
     * @param array $exclude
     * @return array
     */
    protected function parsenewdata($class_vars, $post=array(), $exclude=array()) {
        $post_keys = array_keys($post);
        $content = array();
        foreach ($class_vars as $name=>$value) {
            if (!in_array($name,$exclude) && !in_array($name, self::$default_exclude)) {
                $value = in_array($name,$post_keys) ? $post[$name]: $this->$name;
                $this->$name = $value;
                $value = (is_array($value)) ? json_encode($value):$value;
                $content[$name] = $value;
            }
        }
        return $content;
    }

    /**
     * Create or update table
     * @param bool $op
     * @return mixed
     */
    public function setup($op=False) {
        try {
            if ($this->db->makeorupdate($this->tablename, $this->table_data, $op)) {
                $result['status'] = True;
                $result['msg'] = "'$this->tablename' created";
                AppLogger::get_instance(APP_NAME, get_class($this))->info($result['msg']);
            } else {
                $result['status'] = False;
                $result['msg'] = "'$this->tablename' not created";
                AppLogger::get_instance(APP_NAME, get_class($this))->critical($result['msg']);
            }
            return $result;
        } catch (Exception $e) {
            AppLogger::get_instance(APP_NAME, get_class($this))->critical($e);
            return false;
        }
    }

    /**
     * Sanitize $_POST content
     * @param array $post
     * @return mixed
     */
    public function sanitize(array $post) {
        foreach ($post as $key=>$value) {
            if (!is_array($value)) {
                $post[$key] = htmlspecialchars($value);
            }
        }
        return $post;
    }

    /**
     * Retrieve all elements from the selected table
     * @param array $id
     * @param array $filter
     * @return array|mixed
     */
    public function all(array $id=null, array $filter=null) {
        $dir = (!is_null($filter) && isset($filter['dir'])) ? strtoupper($filter['dir']):'DESC';
        $param = (!is_null($filter) && isset($filter['order'])) ? "ORDER BY `{$filter['order']}` ".$dir:null;

        if (!is_null($id)) {
            $search = array();
            foreach ($id as $field=>$value) {
                $search[] = "{$field}='{$value}'";
            }
            $search = "WHERE " . implode('AND ', $search);
        } else {
            $search = null;
        }

        $sql = "SELECT * FROM {$this->tablename} {$search} {$param}";
        $req = $this->db->send_query($sql);
        $data = array();
        while ($row = $req->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    /**
     * @param $id
     * @return array
     */
    public function search($id) {
        $search = array();
        foreach ($id as $field=>$value) {
            $search[] = "{$field}='{$value}'";
        }
        $search = implode('AND ', $search);
        $sql = "SELECT * FROM {$this->tablename} WHERE {$search}";
        $req = $this->db->send_query($sql);
        $data = array();
        while ($row = mysqli_fetch_assoc($req)) {
            $data[] = $row;
        }
        return $data;
    }

}