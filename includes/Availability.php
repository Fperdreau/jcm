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
     * @var Session
     */
    private static $session;

    /**
     * Constructor
     * @param AppDb $db
     */
    public function __construct(AppDb $db) {
        parent::__construct($db, 'Availability', $this->table_data);
    }

    /**
     * Gets user's availability
     * @param $username
     * @return array
     */
    public function get($username) {
        $sql = "SELECT * FROM {$this->tablename} WHERE username='{$username}'";
        $req = $this->db->send_query($sql);
        $data = array();
        while ($row = $req->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    /**
     * Update user's availability
     * @param $post
     * @param $id
     * @return bool
     */
    public function update($post, $id) {
        return $this->db->updatecontent($this->tablename, $this->parsenewdata($post), $id);
    }

    /**
     * @param $post
     * @return bool|mysqli_result
     */
    public function add($post) {
        $class_vars = get_class_vars(get_class());
        return $this->db->addcontent($this->tablename, $this->parsenewdata($class_vars, $post, array('session')));
    }

    /**
     * @param array $id
     * @return bool|mysqli_result
     */
    public function edit(array $id) {
        if ($this->isexist($id)) {
            return $this->db->deletecontent($this->tablename, array_keys($id), array_values($id));
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