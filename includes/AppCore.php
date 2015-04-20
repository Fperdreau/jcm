<?php
/**
 * Created by PhpStorm.
 * User: Florian
 * Date: 30/03/2015
 * Time: 09:22
 */

class AppCore {

    public $config;
    public $plugins;
    public $db;

    public function __construct() {

    }

    /**
     * @return mixed
     */
    public function getConfig() {
        $this->config = new AppConfig($this->db);
        return $this->config;
    }

    public function getPlugins() {
        $this->plugins;
    }

}