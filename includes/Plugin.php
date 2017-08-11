<?php
/**
 * Created by PhpStorm.
 * User: Florian
 * Date: 11/08/2017
 * Time: 09:53
 */

class Plugin extends BaseModel {

    public $name = null;
    public $version = null;
    public $description = null;

    protected $schema = array();

    /**
     * Plugin's settings
     * Must be formatted as follows:
     *     $options = array(
     *                       'setting_name'=>array(
     *                     'options'=>array(),
     *                     'value'=>0)
     *                );
     *     'options': if not an empty array, then the settings will be displayed as select input. In this case, options
     * must be an associative array: e.g. array('Yes'=>1, 'No'=>0). If it is empty, then it will be displayed as a text
     * input.
     * @var array $options
     */
    public $options = array();

    public $page;

    public $status = 0;

    protected static $model;

    public function __construct() {
        parent::__construct(get_class($this));
    }

    protected function getModel() {
        if (is_null(self::$model)) {
            self::$model = new Plugins();
        }
        return self::$model;
    }

    public function setInfo(array $data) {
        foreach ($data as $prop=>$value) {
            // Check if property exists and is static
            if (property_exists(get_class($this), $prop)) {
                $property = new ReflectionProperty(get_class($this), $prop);
                $new_value = ($prop == 'options') ? json_decode($value,true) : $value;
                if ($property->isStatic()) {
                    $this::$$prop = $new_value;
                } else {
                    $this->$prop = $new_value;
                }
            }
        }
    }

    /**
     * Get plugin information
     * @return array
     */
    public function getInfo() {
        return array(
            'name'=>$this->name,
            'version'=>$this->version,
            'description'=>$this->description,
            'options'=>json_encode($this->options),
            'status'=>$this->status,
            'page'=>$this->page
        );
    }

    public function setOption($option, $value) {
        if (in_array($option, array_keys($this->options))) {
            $this->options[$option]['value'] = $value;
        }
    }

    /**
     * Create or update table
     * @return array
     */
    public function install() {
        try {
            if ($this->db->makeorupdate($this->tablename, $this->schema)) {
                $result['status'] = True;
                $result['msg'] = "'{$this->tablename}' table created";
                Logger::get_instance(APP_NAME, get_class($this))->info($result['msg']);
            } else {
                $result['status'] = False;
                $result['msg'] = "'{$this->tablename}' table not created";
                Logger::get_instance(APP_NAME, get_class($this))->critical($result['msg']);
            }
            return $result;
        } catch (Exception $e) {
            Logger::get_instance(APP_NAME, get_class($this))->critical($e);
            $result['status'] = false;
            $result['msg'] = $e;
            return $result;
        }
    }

    /**
     * Uninstall plugin: drop all tables related to this plugin
     * @return mixed
     */
    public function uninstall() {
        try {
            if ($this->db->deletetable($this->tablename)) {
                $result['status'] = True;
                $result['msg'] = "'{$this->tablename}' table deleted";
                Logger::get_instance(APP_NAME, get_class($this))->info($result['msg']);
            } else {
                $result['status'] = False;
                $result['msg'] = "'{$this->tablename}' table could not be deleted";
                Logger::get_instance(APP_NAME, get_class($this))->critical($result['msg']);
            }
            return $result;
        } catch (Exception $e) {
            Logger::get_instance(APP_NAME, get_class($this))->critical($e);
            $result['status'] = false;
            $result['msg'] = $e;
            return $result;
        }
    }

    /**
     * @return string
     */
    public function show() {}

}