<?php

/**
 * Created by PhpStorm.
 * User: Florian
 * Date: 23/02/2017
 * Time: 08:19
 */
class Bookmark extends AppTable {

    protected $table_data = array(
        "id" => array("INT NOT NULL AUTO_INCREMENT", false),
        "ref_id" => array("BIGINT(15)", false),
        "ref_obj" => array("CHAR(55)", false),
        "username" => array("CHAR(255) NOT NULL"),
        "primary" => "id"
    );

    public $id;
    public $ref_id;
    public $ref_obj;
    public $username;

    /**
     * Constructor
     */
    public function __construct(){
        parent::__construct(__CLASS__, $this->table_data);
    }

    /**
     * Add vote to db if it does not already exist
     * @param array $post
     * @return bool
     */
    public function add(array $post) {
        if (User::is_logged() && !$this->is_exist(array('ref_id'=>$post['ref_id'], 'ref_obj'=>$post['ref_obj']))) {
            $post['username'] = $_SESSION['username'];
            return $this->db->addcontent($this->tablename, $this->parsenewdata(get_class_vars(get_called_class()),
                $post));
        } else {
            return false;
        }
    }

    /**
     * Add vote to db if it does not already exist
     * @param array $post
     * @return bool
     */
    public function delete(array $post) {
        if (User::is_logged()) {
            $id = array(
                'ref_id'=>$post['ref_id'],
                'ref_obj'=>$post['ref_obj'],
                'username'=>$_SESSION['username']
            );
            return $this->db->delete($this->tablename, $id);
        } else {
            return false;
        }
    }

    /**
     * Get bookmark icon
     * @param $id
     * @param $ref_obj
     * @param $username
     * @return string
     */
    public static function getIcon($id, $ref_obj, $username) {
        $self = new self();
        $status = $self->is_exist(array('ref_id'=>$id, 'ref_obj'=>$ref_obj, 'username'=>$username));
        return self::show(array('ref_id'=>$id, 'ref_obj'=>$ref_obj), $status);
    }

    // VIEWS
    /**
     * Render bookmark button/icon
     * @param array $data
     * @param bool $status : bookmarked or not by current user
     * @return string
     */
    public static function show(array $data, $status) {
        if ($status) {
            $css_icon = 'bookmark_on';
            $operation = 'delete';
        } else {
            $css_icon = 'bookmark_off';
            $operation = 'add';
        }
        return "
        <div class='tiny_icon bookmark_container {$css_icon}' data-controller='Bookmark' data-ref_id='{$data['ref_id']}' data-ref_obj='{$data['ref_obj']}' data-operation='{$operation}'>
        </div>";
    }

}