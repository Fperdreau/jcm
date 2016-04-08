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
class AppPlugins extends AppTable {

    /**
     * @var array $table_data: Table schema
     */
    protected $table_data = array(
        "id"=>array("INT NOT NULL AUTO_INCREMENT",false),
        "name"=>array("CHAR(20)",false),
        "version"=>array("CHAR(5)",false),
        "page"=>array("CHAR(20)",false),
        "status"=>array("CHAR(3)",false),
        "options"=>array("TEXT",false),
        "description"=>array("TEXT",false),
        "primary"=>'id'
    );

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
     * @var array $options: plugins options (saved as JSON format into the database)
     * e.g. $options = array('option_name'=>$option_value)
     */
    public $options;

    /**
     * @var string $description: plugins description
     */
    public static $description;

    /**
     * Constructor
     * @param AppDb $db
     * @param bool $name
     */
    public function __construct(AppDb $db, $name=False) {
        parent::__construct($db, 'Plugins', $this->table_data);
        if ($name !== False) {
            $this->name = $name;
            $this->get($name);
        }
    }

    /**
     * Add plugin to the Plugin table
     * @param array $post
     * @return bool|mysqli_result
     */
    public function make($post=array()) {
        $class_vars = get_class_vars('AppPlugins');
        $content = $this->parsenewdata($class_vars,$post, array('installed', 'description'));
        return $this->db->addcontent($this->db->tablesname['Plugins'],$content);
    }

    /**
     * Get plugin info from the Plugin table
     */
    public function get() {
        $sql = "SELECT name,version,page,status,options FROM ".$this->db->tablesname['Plugins']." WHERE name='$this->name'";
        $req = $this->db->send_query($sql);
        $data = mysqli_fetch_assoc($req);
        if (!empty($data)) {
            foreach ($data as $prop=>$value) {
                $this->$prop = ($prop == 'options') ? json_decode($value,true):$value;
            }
        }
    }

    /**
     * Uninstall plugin: delete entry from the Plugin table and delete corresponding tables
     * @return bool|mysqli_result
     */
    public function delete() {
        $this->db->deletecontent($this->db->tablesname['Plugins'],array('name'),array($this->name));
        if ($this->db->tableExists($this->tablename)) {
            return $this->db->deletetable($this->tablename);
        } else {
            return true;
        }
    }

    /**
     * Update plugin's info
     * @param array $post
     * @return bool
     */
    public function update($post=array()) {
        $class_vars = get_class_vars('AppPlugins');
        $content = $this->parsenewdata($class_vars,$post,array('installed', 'description'));
        return $this->db->updatecontent($this->db->tablesname['Plugins'],$content,array("name"=>$this->name));
    }

    /**
     * Check if the plugin is registered to the Plugin table
     * @return bool
     */
    public function isInstalled() {
        $plugins = $this->db->getinfo($this->db->tablesname['Plugins'],'name');
        return in_array($this->name,$plugins);
    }

    /**
     * Instantiate a class from class name
     * @param: class name (must be the same as the file name)
     * @return AppPlugins
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
        if ($page === False) {
            $pluginList = scandir($folder);
        } else {
            $sql = "SELECT * FROM $this->tablename WHERE page='$page'";
            $pluginList = $this->db->send_query($sql)->fetch_all(MYSQLI_ASSOC);
        }

        $plugins = array();
        foreach ($pluginList as $pluginfile) {
            if (!empty($pluginfile) && !in_array($pluginfile,array('.','..'))) {
                /**
                 * @var AppPlugins $thisPlugin
                 */
                $thisPlugin = $this->instantiate($pluginfile);
                if ($thisPlugin->isInstalled()) {
                    $thisPlugin->get();
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
     * Display job's settings
     * @return string
     */
    public function displayOpt() {
        $opt = "<div style='font-weight: 600;'>Options</div>";
        if (!empty($this->options)) {
            foreach ($this->options as $optName => $settings) {
                if (count($settings) > 1) {
                    $optProp = "";
                    foreach ($settings as $prop) {
                        $optProp .= "<option value='$prop'>$prop</option>";
                    }
                    $optProp = "<select name='$optName'>$optProp</select>";
                } else {
                    $optProp = "<input type='text' name='$optName' value='$settings' style='width: auto;'/>";
                }
                $opt .= "
                <div class='formcontrol'>
                    <label for='$optName'>$optName</label>
                    $optProp
                </div>";
            }
            $opt .= "<input type='submit' class='modOpt' data-op='plugin' value='Modify'>";
        } else {
            $opt = "No settings are available for this job.";
        }
        return $opt;
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
            $status = $info['status'];

            $plugin_list .= "
            <div class='plugDiv' id='plugin_$pluginName'>
                <div class='plugLeft'>
                    <div class='plugName'>$pluginName</div>
                    <div class='optbar'>
                        <div class='optShow workBtn settingsBtn' data-op='plugin' data-name='$pluginName'></div>
                        $install_btn
                    </div>
                </div>

                <div class='plugSettings'>
                    <div class='description'>
                        <div class='version'>Version: {$info['version']}</div>
                        {$pluginDescription}
                    </div>
                    
                    <div>
                        <div class='optbar'>
                            <div class='formcontrol'>
                                <label>Status</label>
                                <select class='select_opt modSettings' data-op='plugin' data-option='status' data-name='$pluginName'>
                                <option value='$status' selected>$status</option>
                                <option value='On'>On</option>
                                <option value='Off'>Off</option>
                                </select>
                            </div>
                        </div>
    
                        <div class='settings'>
                            <div class='formcontrol'>
                                <label>Page</label>
                                <input type='text' class='modSettings' data-name='$pluginName' data-op='plugin' data-option='page' value='" . $info['page'] . "' style='width: 20%'/>
                            </div>
                        </div>
                        
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
        $table = new AppTable($this->db, $this->name, $this->table_data, strtolower($this->name));
        $table->setup();

        // Register the plugin in the db
        $class_vars = get_class_vars($this->name);
        return $this->make($class_vars);
    }
}