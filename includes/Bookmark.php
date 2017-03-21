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

    /**
     * @param $username
     * @return null|string
     */
    public function getList($username) {
        $content = null;
        foreach ($this->all(array('username'=>$username)) as $key=>$item) {
            $Controller = new $item['ref_obj']();
            $data = $Controller->get(array('id_pres'=>$item['ref_id']));
            $content .= self::inList($item['ref_obj'], $data[0]);
        }
        return $content;
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

    /**
     * Render bookmark in My Bookmarks list
     * @param string $controller: Controller name
     * @param array $data : bookmark information
     * @return string
     */
    public static function inList($controller, array $data) {
        return "
            <div>
                <div class='bookmark_title'><a href='" . URL_TO_APP . "index.php?page={$controller}&id={$data['id_pres']}" . "'>{$data['title']}</a></div>
                <div class='bookmark_action'>
                    <div class='pub_btn icon_btn'><a href='#' data-id='{$data['id']}' 
                            data-controller='Bookmark' class='delete_ref'>
                <img src='".AppConfig::$site_url."images/trash.png'></a></div>
                </div>              
            </div>
        ";
    }

}