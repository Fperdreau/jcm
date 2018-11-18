<?php

/**
 * Created by PhpStorm.
 * User: Florian
 * Date: 23/02/2017
 * Time: 08:19
 */

namespace includes;

use includes\BaseModel;

class Vote extends BaseModel
{

    public $id;
    public $ref_id;
    public $ref_obj;
    public $date;
    public $username;

    /**
     * Add vote to db if it does not already exist
     * @param array $post
     * @return array
     */
    public function add(array $post)
    {
        $result = array('status'=>false, 'msg'=>null);
        if (SessionInstance::isLogged() && !$this->isExist(array('ref_id'=>$post['ref_id'], 'ref_obj'=>$post['ref_obj'],
                'username'=>$_SESSION['username']))) {
            $post['date'] = date('Y-m-d');
            $post['username'] = $_SESSION['username'];
            $result['status'] = $this->db->insert($this->tablename, $this->parseData($post)) !== false;
            if ($result['status']) {
                $info = $this->getSummary($post['ref_id'], $post['ref_obj'], $_SESSION['username']);
                $result['count'] = $info['count'];
                $result['state'] = $info['status'];
            }
        }

        return $result;
    }


    /**
     * Add vote to db if it does not already exist
     * @param array $post
     * @return array
     */
    public function delete(array $post)
    {
        $result = array('status'=>false, 'msg'=>null);
        if (SessionInstance::isLogged()) {
            $id = array(
                'ref_id'=>$post['ref_id'],
                'ref_obj'=>$post['ref_obj'],
                'username'=>$_SESSION['username']
            );
            $result['status'] = $this->db->delete($this->tablename, $id);
            if ($result['status']) {
                $info = $this->getSummary($post['ref_id'], $post['ref_obj'], $_SESSION['username']);
                $result['count'] = $info['count'];
                $result['state'] = $info['status'];
            }
        }
        return $result;
    }

    /**
     * Get summary information
     * @param $id: reference id
     * @param $ref_obj: reference class
     * @param $username: username
     * @return array: array('count'=>int, 'status'=>boolean)
     */
    public function getSummary($id, $ref_obj, $username)
    {
        $data = $this->all(array('ref_id'=>$id, 'ref_obj'=>$ref_obj));
        return array(
            'count' => !empty($data) ? count($data) : 0,
            'status' => $this->isExist(array('ref_id'=>$id, 'ref_obj'=>$ref_obj, 'username'=>$username))
        );
    }

    /**
     * Get vote icon
     * @param $id
     * @param $ref_obj
     * @param $username
     * @return string
     */
    public function getIcon($id, $ref_obj, $username)
    {
        $info = $this->getSummary($id, $ref_obj, $username);
        return self::show(array('ref_id'=>$id, 'ref_obj'=>$ref_obj, 'count'=>$info['count']), $info['status']);
    }

    // VIEWS
    /**
     * Render vote button/icon
     * @param array $data
     * @param bool $status : liked or not by current user
     * @return string
     */
    public static function show(array $data, $status)
    {
        if ($status) {
            $css_icon = 'vote_liked';
            $operation = 'delete';
        } else {
            $css_icon = 'vote_default';
            $operation = 'add';
        }
        return "
        <div class='vote_container' data-controller='Vote' data-ref_id='{$data['ref_id']}' 
        data-ref_obj='{$data['ref_obj']}' data-operation='{$operation}'>
            <div class='tiny_icon vote_icon {$css_icon}'></div>
            <div class='vote_count'>{$data['count']}</div>
        </div>";
    }

}