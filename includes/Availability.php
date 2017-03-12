<?php

/**
 * Created by PhpStorm.
 * User: Florian
 * Date: 15/04/2016
 * Time: 19:24
 */
class Availability extends AppTable {

    /**
     * @var array
     */
    protected $table_data = array(
        "id"=>array("INT NOT NULL AUTO_INCREMENT",false),
        "username"=>array("CHAR(255)",false),
        "date"=>array("DATE NOT NULL", false),
        "primary"=>'id'
    );

    /**
     * @var string $date
     */
    public $date;

    /**
     * @var string $username
     */
    public $username;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct('Availability', $this->table_data);
    }

    /**
     * @param $post
     * @return bool|mysqli_result
     */
    public function add(array $post) {
        $class_vars = get_class_vars(get_class());
        return $this->db->addcontent($this->tablename, $this->parsenewdata($class_vars, $post, array('session')));
    }

    /**
     * @param array $id
     * @return bool|mysqli_result
     */
    public function edit(array $id) {
        if ($this->isexist($id)) {
            return $this->db->delete($this->tablename, $id);
        } else {
            return $this->add($id);
        }
    }

    /**
     * @param array $id
     * @return bool
     */
    public function isexist(array $id) {
        $where = array();
        foreach ($id as $field=>$value) {
            $where[] = "{$field}='{$value}'";
        }
        $where = implode(' AND ', $where);
        $sql = "SELECT * FROM {$this->tablename} WHERE {$where}";
        $data = $this->db->send_query($sql)->fetch_assoc();
        return !empty($data);
    }
}