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

    /**
     * Constructor
     * @param AppDb $db
     * @param $tablename
     */
    function __construct(AppDb $db, $tablename, $table_data, $plugin=False) {
        $this->db = $db;
        if ($plugin !== False) {
            $this->tablename = $db->dbprefix.'_'.$plugin;
        } else {
            $this->tablename = $db->tablesname[$tablename];
        }
        $this->table_data = $table_data;
        if (!$this->db->tableExists($this->tablename)) {
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
        $default_exclude = array("db","tablename","table_data");
        $post_keys = array_keys($post);
        $content = array();
        foreach ($class_vars as $name=>$value) {
            if (!in_array($name,$exclude) && !in_array($name, $default_exclude)) {
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
        if ($this->db->makeorupdate($this->tablename, $this->table_data, $op)) {
            $result['status'] = True;
            $result['msg'] = "'$this->tablename' created";
        } else {
            $result['status'] = False;
            $result['msg'] = "'$this->tablename' not created";
        }
        return $result;
    }

    /**
     * Sanitize $_POST content
     * @param $post
     */
    public function sanitize($post) {
        foreach ($post as $key=>$value) {
            if (!is_array($value)) {
                $post[$key] = htmlspecialchars($value);
            }
        }
        return $post;
    }

}