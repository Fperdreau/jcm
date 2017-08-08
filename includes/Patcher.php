<?php

/**
 * Created by PhpStorm.
 * User: Florian
 * Date: 05/05/2017
 * Time: 22:03
 */
class Patcher {

    /**
     * @var $directory string: path to patch files
     */
    private static $directory;

    /**
     * Patcher constructor.
     */
    public function __construct() {
        self::$directory = PATH_TO_APP . DS . 'patches';
    }

    /**
     * Patch application
     * @return mixed
     */
    public function patching() {
        $op = $_POST['op'] === "new";
        if ($op === false) {
            patching();
            Suggestion::patch();
            Presentation::patch_uploads();
            Presentation::patch_session_id();
            Session::patch_time();
            Posts::patch_table();

            $result['msg'] = "Patch successfully applied!";
            $result['status'] = true;
        } else {
            $result['msg'] = 'Skipped, because unnecessary.';
            $result['status'] = true;
        }
        return $result;
    }

}