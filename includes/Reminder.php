<?php
/**
 * Created by PhpStorm.
 * User: Florian
 * Date: 20/08/2017
 * Time: 16:17
 */

namespace includes;


class Reminder extends \BaseModel {

    public $controller;
    public $ref_id;
    public $reminded = 0;

    protected $settings = array(
        'days'=>0,
        'hours'=>0,
        'minutes'=>0
    );

    /**
     * Session instance
     * @var \Session $Session
     */
    private static $Session;

    /**
     * Test if
     * @param $id
     * @return bool
     */
    public function is_reminded($id) {
        return $this->get(array('id'=>$id)) == 1;
    }

    /**
     * Add upcoming sessions to Reminder table
     * @return array: array('status'=>bool, 'msg'=>string)
     */
    public function addSessions() {
        $result = array('status'=>true, 'msg'=>null);
        $counter = 0;
        foreach (self::getSession()->getNext() as $key=>$item) {
            if (!($this->is_exist(array('ref_id'=>$item['id'])))) {
                if ($result['status'] = $this->add(array('ref_id'=>$item['id'], 'reminded'=>0))) {
                    $counter++;
                    $result['msg'] = "{$counter} items have been added to Reminder";
                } else {
                    break;
                }
            }
        }
        return $result;
    }

    private static function getSession() {
        if (is_null(self::$Session)) {
            self::$Session = new \Session();
        }
        return self::$Session;
    }

    }