<?php
/**
 * Created by PhpStorm.
 * User: Florian
 * Date: 11/08/2017
 * Time: 19:28
 */

namespace includes;

/**
 * Undocumented class
 */
class Task
{

    /**
     * @var string $name: Task's name
     */
    public $name;

    /**
     * @var string $version: Task version
     */
    public $version;

    /**
     * @var datetime $time: running time
     */
    public $time;

    /**
     * @var string $frequency: running frequency (format: 'month,days,hours,minutes')
     */
    public $frequency = '0,0,0,0';

    /**
     * @var string $path: path to script
     */
    public $path;

    /**
     * @var int $status: Task's status (0=>Off, 1=>On)
     */
    public $status = 0;

    /**
     * Is this task registered into the database?
     * @var bool $installed
     */
    public $installed = 0;

    /**
     * Is this task currently running
     * @var int
     */
    public $running = 0;

    /**
     * Task's settings
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
    public $options=array();

    /**
     * Task's description
     * @var string $description
     */
    public $description;

    /**
     * Task constructor.
     */
    public function __construct()
    {
        // If time is default, set time to now
        $this->time = (is_null($this->time)) ? date('Y-m-d H:i:s', time()) : $this->time;
        $this->path = basename(__FILE__);
    }

    /**
     * Set task information
     * @param array $data
     */
    public function setInfo(array $data)
    {
        foreach ($data as $prop => $value) {
            // Check if property exists and is static
            if (property_exists(get_class($this), $prop)) {
                $property = new \ReflectionProperty(get_class($this), $prop);
                $new_value = ($prop == 'options') ? json_decode($value, true) : $value;
                if ($property->isStatic()) {
                    $this::$$prop = $new_value;
                } else {
                    $this->$prop = $new_value;
                }
            }
        }
    }

    /**
     * Get task information
     * @return array
     */
    public function getInfo()
    {
        return array(
            'name'=>$this->name,
            'version'=>$this->version,
            'description'=>$this->description,
            'options'=>json_encode($this->options),
            'status'=>$this->status,
            'time'=>$this->time,
            'frequency'=>$this->frequency,
            'running'=>$this->running
        );
    }

    public function setOption($option, $value)
    {
        if (in_array($option, array_keys($this->options))) {
            $this->options[$option]['value'] = $value;
        }
    }

    /**
     * Execute task
     * @return mixed
     */
    public function run()
    {
        return array('status'=>true, 'msg'=>null);
    }
}
