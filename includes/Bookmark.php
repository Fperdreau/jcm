<?php

namespace includes;

use includes\BaseModel;

/**
 * Created by PhpStorm.
 * User: Florian
 * Date: 23/02/2017
 * Time: 08:19
 */
class Bookmark extends BaseModel {

    public $id;
    public $ref_id;
    public $ref_obj;
    public $username;

    /**
     * Constructor
     */
    public function __construct(){
        parent::__construct();
    }

    /**
     * Add vote to db if it does not already exist
     * @param array $post
     * @return mixed
     */
    public function add(array $post) {
        if (SessionInstance::isLogged() && !$this->is_exist(array('ref_id'=>$post['ref_id'], 'ref_obj'=>$post['ref_obj']))) {
            $post['username'] = $_SESSION['username'];
            $result['status'] = $this->db->insert($this->tablename, $this->parseData($post)) !== false;
            if ($result['status']) {
                $info = $this->get_summary($post['ref_id'], $post['ref_obj'], $_SESSION['username']);
                $result['state'] = $info['status'];
            }
        } else {
            $result['status'] = false;
        }
        return $result;
    }

    /**
     * delete vote from db
     * @param array $post
     * @return mixed
     */
    public function delete(array $post) {
        $result = array();
        if (SessionInstance::isLogged()) {
            $result['status'] = $this->db->delete($this->tablename, array('id'=>$post['id']));
        } else {
            $result['status'] = false;
        }
        $result['state'] = $this->is_exist(array('id'=>$post['id']));
        return $result;
    }

    /**
     * Get summary information
     * @param $id: reference id
     * @param $ref_obj: reference class
     * @param $username: username
     * @return array: array('count'=>int, 'status'=>boolean)
     */
    public function get_summary($id, $ref_obj, $username) {
        return array(
            'status' => $this->is_exist(array('ref_id'=>$id, 'ref_obj'=>$ref_obj, 'username'=>$username))
        );
    }

    /**
     * Get bookmark icon
     * @param $id
     * @param $ref_obj
     * @param $username
     * @return string
     */
    public function getIcon($id, $ref_obj, $username) {
        return self::show(
            array('ref_id'=>$id, 'ref_obj'=>$ref_obj),
            $this->is_exist(array('ref_id'=>$id, 'ref_obj'=>$ref_obj, 'username'=>$username))
        );
    }

    /**
     * @param $username
     * @return null|string
     */
    public function getList($username) {
        $content = null;
        foreach ($this->all(array('username'=>$username)) as $key=>$item) {

            /**
             * @var $Controller BaseModel
             */
            $Controller = new $item['ref_obj']();
            $data = $Controller->get(array('id_pres'=>$item['ref_id']));
            if (!empty($data)) {
                $content .= self::inList($item, $data);
            } else {
                $this->delete(array('id'=>$item['id']));
            }
        }

        if (is_null($content)) $content = self::nothing();
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
     * @param array $bookmark: bookmark information
     * @param array $data : target information
     * @return string
     */
    public static function inList(array $bookmark, array $data) {
        $url = App::getAppUrl() . "index.php?page=". strtolower($bookmark['ref_obj']). "&id={$bookmark['ref_id']}";
        return "
            <div class='bookmark_list_container'>
                <div class='bookmark_title'>
                   <a href='$url' class='leanModal' data-controller='{$bookmark['ref_obj']}' data-action='show_details' 
                   data-section='suggestion' data-params='{$bookmark['ref_id']},modal' data-id='{$bookmark['ref_id']}'>
                    {$data['title']}</a>
                </div>
                <div class='bookmark_action'>
                    <div class='pub_btn icon_btn'><a href='#' data-id='{$bookmark['id']}' 
                            data-controller='Bookmark' class='delete'>
                <img src='" . URL_TO_IMG . "trash.png'></a></div>
                </div>              
            </div>
        ";
    }

    private static function nothing() {
        return "
        <div class='bookmark_list_container'>Sorry, there is nothing to show here yet.</div>
        ";
    }

}