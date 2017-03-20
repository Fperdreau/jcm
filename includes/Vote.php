<?php

/**
 * Created by PhpStorm.
 * User: Florian
 * Date: 23/02/2017
 * Time: 08:19
 */
class Vote extends AppTable {

    protected $table_data = array(
        "id" => array("INT NOT NULL AUTO_INCREMENT", false),
        "ref_id" => array("BIGINT(15)", false),
        "ref_obj" => array("CHAR(55)", false),
        "date" => array("DATETIME", false),
        "username" => array("CHAR(255) NOT NULL"),
        "primary" => "id"
    );

    public $id;
    public $ref_id;
    public $ref_obj;
    public $date;
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
     * @return array
     */
    public function add(array $post) {
        $result = array('status'=>false, 'msg'=>null);
        if (User::is_logged() && !$this->is_exist(array('ref_id'=>$post['ref_id'], 'ref_obj'=>$post['ref_obj'],
                'username'=>$_SESSION['username']))) {
            $post['date'] = date('Y-m-d');
            $post['username'] = $_SESSION['username'];
            $result['status'] = $this->db->addcontent($this->tablename, $this->parsenewdata(get_class_vars(get_called_class()),
                $post));
            if ($result['status']) {
                $result['msg'] = self::getIcon($post['ref_id'], $post['ref_obj'], $post['username']);
            }
        }

        return $result;
    }


    /**
     * Add vote to db if it does not already exist
     * @param array $post
     * @return array
     */
    public function delete(array $post) {
        $result = array('status'=>false, 'msg'=>null);
        if (User::is_logged()) {
            $id = array(
                'ref_id'=>$post['ref_id'],
                'ref_obj'=>$post['ref_obj'],
                'username'=>$_SESSION['username']
            );
            $result['status'] = $this->db->delete($this->tablename, $id);
            if ($result['status']) {
                $result['msg'] = self::getIcon($post['ref_id'], $post['ref_obj'], $_SESSION['username']);
            }
        }
        return $result;
    }

    /**
     * Get votes associated to an object
     * @param string $id
     * @param string $ref_obj
     * @return array
     */
    public function get_votes($id, $ref_obj) {
        return $this->get(array('ref_id'=>$id, 'ref_obj'=>$ref_obj));
    }

    /**
     * Get vote icon
     * @param $id
     * @param $ref_obj
     * @param $username
     * @return string
     */
    public static function getIcon($id, $ref_obj, $username) {
        $self = new self();
        $data = $self->get(array('ref_id'=>$id, 'ref_obj'=>$ref_obj));
        $vote = !empty($data) ? count($data) : 0;
        $status = $self->is_exist(array('ref_id'=>$id, 'ref_obj'=>$ref_obj, 'username'=>$username));
        return self::show(array('ref_id'=>$id, 'ref_obj'=>$ref_obj, 'count'=>$vote), $status);
    }

    // VIEWS
    /**
     * Render vote button/icon
     * @param array $data
     * @param bool $status : liked or not by current user
     * @return string
     */
    public static function show(array $data, $status) {
        if ($status) {
            $css_icon = 'vote_liked';
            $operation = 'delete';
        } else {
            $css_icon = 'vote_default';
            $operation = 'add';
        }
        return "
        <div class='vote_container' data-controller='Vote' data-ref_id='{$data['ref_id']}' data-ref_obj='{$data['ref_obj']}' data-operation='{$operation}'>
            <div class='tiny_icon vote_icon {$css_icon}'></div>
            <div class='vote_count'>{$data['count']}</div>
        </div>";
    }

}