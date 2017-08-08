<?php
/**
 * File for class AppPlugins
 *
 * PHP version 5
 *
 * @author Florian Perdreau (fp@florianperdreau.fr)
 * @copyright Copyright (C) 2014 Florian Perdreau
 * @license <http://www.gnu.org/licenses/agpl-3.0.txt> GNU Affero General Public License v3
 *
 * This file is part of Journal Club Manager.
 *
 * Journal Club Manager is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Journal Club Manager is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Journal Club Manager.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Class AppPlugins
 *
 * Handle plugins settings and routines (installation, running, ect.)
 */
class Plugins extends BaseModel {

    /**
     * @var string $tablename: Table name
     */
    protected $tablename;

    /**
     * @var string $name: plugin name
     */
    public $name;

    /**
     * @var string $version: plugin version
     */
    public $version;

    /**
     * @var string $page: page on which the plugin is displayed
     */
    public $page;

    /**
     * @var string $status: plugin status ('On' or 'Off')
     */
    public $status = 'Off';

    /**
     * @var bool $installed: is the plugin registered into the database?
     */
    public $installed = False;

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
    public $options=array();

    /**
     * @var string $description: plugins description
     */
    public static $description;

    /**
     * Constructor
     * @param bool $name
     */
    public function __construct($name=False) {
        parent::__construct();
        if ($name !== False) {
            $this->name = $name;
            $this->getInfo();
        }
    }

    /**
     * Add plugin to the Plugin table
     * @param array $post
     * @return bool|mysqli_result
     */
    public function make($post=array()) {
        $class_vars = get_class_vars('Plugins');
        $content = $this->parsenewdata($class_vars,$post, array('installed', 'description'));
        return $this->db->insert($this->db->tablesname['Plugins'],$content);
    }

    /**
     * Get plugin info from the Plugin table
     */
    public function getInfo() {
        $sql = "SELECT * FROM ".$this->db->tablesname['Plugins']." WHERE name='$this->name'";
        $req = $this->db->send_query($sql);
        $data = mysqli_fetch_assoc($req);
        if (!empty($data)) {
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
    }

    /**
     * Uninstall plugin: delete entry from the Plugin table and delete corresponding tables
     * @param array $id
     * @return bool|mysqli_result
     */
    public function delete(array $id) {
        $this->db->delete($this->db->tablesname['Plugins'], $id);
        if ($this->db->tableExists($this->tablename)) {
            return $this->db->deletetable($this->tablename);
        } else {
            return true;
        }
    }

    /**
     * Check if the plugin is registered to the Plugin table
     * @return bool
     */
    public function isInstalled() {
        return $this->is_exist(array('name'=>$this->name));
    }

    /**
     * Instantiate a class from class name
     * @param: class name (must be the same as the file name)
     * @return Plugins
     */
    public function instantiate($pluginName) {
        $folder = PATH_TO_APP.'/plugins/';
        include_once($folder . $pluginName .'/'. $pluginName .'.php');
        return new $pluginName($this->db);
    }

    /**
     * Get list of plugins, their settings and status
     * @param bool $page
     * @return array
     */
    public function getPlugins($page=False) {
        $folder = PATH_TO_APP.'/plugins/';
        if ($page == False) {
            $pluginList = array_diff(scandir($folder), array('.', '..'));
        } else {
            $sql = "SELECT * FROM $this->tablename WHERE page='$page'";
            $req = $this->db->send_query($sql);
            $pluginList = array();
            while ($item = $req->fetch_assoc()) {
                $pluginList[] = $item['name'];
            }
        }

        $plugins = array();
        foreach ($pluginList as $key=>$pluginfile) {
            if (!empty($pluginfile) && !in_array($pluginfile,array('.','..'))) {
                /**
                 * @var Plugins $thisPlugin
                 */

                $thisPlugin = $this->instantiate($pluginfile);

                if ($thisPlugin->isInstalled()) {
                    $thisPlugin->getInfo();
                }
                $plugins[$pluginfile] = array(
                    'installed' => $thisPlugin->installed,
                    'status' => $thisPlugin->status,
                    'page'=>$thisPlugin->page,
                    'options'=>$thisPlugin->options,
                    'version'=>$thisPlugin->version,
                    'description'=>$thisPlugin::$description
                );

                $plugins[$pluginfile]['display'] = ($thisPlugin->isInstalled() && $page !== false) ? $thisPlugin->show():'';
            }
        }
        return $plugins;
    }

    /**
     * Display Plugin's settings
     * @return string
     */
    public function displayOpt() {
        $content = "<h4 style='font-weight: 600;'>Options</h4>";
        if (!empty($this->options)) {
            $opt = '';
            foreach ($this->options as $optName=>$settings) {
                if (isset($settings['options']) && !empty($settings['options'])) {
                    $options = "";
                    foreach ($settings['options'] as $prop=>$value) {
                        $options .= "<option value='{$value}'>{$prop}</option>";
                    }
                    $optProp = "<select name='{$optName}'>{$options}</select>";
                } else {
                    $optProp = "<input type='text' name='$optName' value='{$settings['value']}'/>";
                }
                $opt .= "
                    <div class='form-group inline_field field_auto'>
                        {$optProp}
                        <label for='{$optName}'>{$optName}</label>
                    </div>
                ";
            }
            $content .= "
                <form method='post' action='php/form.php'>
                    {$opt}
                    <div class='submit_btns'>
                        <input type='submit' class='modOpt' data-op='plugin' value='Modify'>
                    </div>
                </form>
                
                ";
        } else {
            $content = "No settings available for this task.";
        }
        return $content;
    }

    /**
     * Show plugins list
     * @return string
     */
    public function show() {
        $pluginsList = $this->getPlugins();
        $plugin_list = "";
        foreach ($pluginsList as $pluginName => $info) {
            $installed = $info['installed'];
            $pluginDescription = (!empty($info['description'])) ? $info['description']:null;
            if ($installed) {
                $install_btn = "<div class='installDep workBtn uninstallBtn' data-type='plugin' data-op='uninstall' data-name='$pluginName'></div>";
            } else {
                $install_btn = "<div class='installDep workBtn installBtn' data-type='plugin' data-op='install' data-name='$pluginName'></div>";
            }

            if ($info['status'] === 'On') {
                $activate_btn = "<div class='activateDep workBtn deactivateBtn' data-type='plugin' data-op='Off' data-name='$pluginName'></div>";
            } else {
                $activate_btn = "<div class='activateDep workBtn activateBtn' data-type='plugin' data-op='On' data-name='$pluginName'></div>";
            }

            $plugin_list .= "
            <div class='plugDiv' id='plugin_$pluginName'>
                <div class='plugHeader'>
                    <div class='plug_header_panel'>
                        <div class='plugName'>$pluginName</div>
                    </div>
                    <div class='optBar'>
                        <div class='optShow workBtn settingsBtn' data-op='plugin' data-name='$pluginName'></div>
                        $install_btn
                        $activate_btn
                    </div>
                </div>

                <div class='plugSettings'>
                    <div class='description'>
                        <div class='version'>Version: {$info['version']}</div>
                        {$pluginDescription}
                    </div>
                    
                    <div>                        
                        <div class='plugOpt' id='$pluginName'></div>
                    </div>

                </div>
                
            </div>
            ";
        }
        return $plugin_list;
    }

    /**
     * Registers plugin into the database
     * @return bool|mysqli_result
     */
    public function install() {
        // Create corresponding table
        $table = new BaseModel(strtolower($this->name), $this->table_data);
        $table->setup();

        // Register the plugin in the db
        $class_vars = get_class_vars($this->name);
        return $this->make($class_vars);
    }
}