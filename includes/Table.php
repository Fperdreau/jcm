<?php
/**
 * Created by PhpStorm.
 * User: Florian
 * Date: 29/03/2015
 * Time: 14:51
 */

class Table {

    protected $db;
    protected $tablename;
    protected $table_data;

    /**
     * Constructor
     * @param DbSet $db
     * @param $tablename
     */
    function __construct(DbSet $db, $tablename, $table_data, $plugin=False) {
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
            $result['msg'] = "<p id='success'> '" . $this->tablename . "' created</p>";
        } else {
            $result['status'] = False;
            $result['msg'] = "<p id='warning'>'$this->tablename' not created</p>";
        }
        return $result;
    }

}