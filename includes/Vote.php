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
        "ref_obj" => array("BIGINT(15)", false),
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
     * Get votes associated to an object
     * @param string $id
     * @param string $ref_obj
     * @return array
     */
    public static function get_votes($id, $ref_obj) {
        $vote = new self();
        return $vote->get(array('ref_id'=>$id, 'ref_obj'=>$ref_obj));
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
            $operation = 'dislike';
        } else {
            $css_icon = 'vote_default';
            $operation = 'like';
        }
        return "
        <div class='vote_container' data-refid='{$data['ref_id']}' data-refobj='{$data['ref_obj']}' data-op='{$operation}'>
            <div class='vote_icon {$css_icon}'></div>
            <div class='vote_count'>{$data['count']}</div>
        </div>";
    }

}