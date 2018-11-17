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

namespace includes;

use includes\BaseModel;

/**
 * Class Plugins
 *
 * Handle plugins settings and routines (installation, running, ect.)
 */
class Plugins extends BaseModel
{

    /**
     * Container for plugins instance
     * @var array $instances
     */
    private $instances = array();

    /**
     * @var array $plugins
     */
    private $plugins;

    public function __construct($plugin = false)
    {
        parent::__construct($plugin);
    }

    /**
     * Add plugin to the Plugin table
     * @param array $post
     * @return bool|mysqli_result
     */
    public function make($post = array())
    {
        return $this->add($post);
    }

    /**
     * Install plugin
     *
     * @param string $name: plugin name
     * @return array: array("msg"=>string, "status"=>bool)
     */
    public function install($name)
    {
        if (isset($_POST['name'])) {
            $name = $_POST['name'];
        }
        $result['status'] = false;
        $data = $this->getPlugin($name)->getInfo();
        if ($this->add($data)) {
            $result = $this->getPlugin($name)->install();
        }
        $result['msg'] = $result['status'] ? $name . " has been installed" : "Oops, something went wrong";
        return $result;
    }

    /**
     * Uninstall plugin
     *
     * @param string $name: plugin name
     * @return array: array("msg"=>string, "status"=>bool)
     */
    public function uninstall($name)
    {
        if (isset($_POST['name'])) {
            $name = $_POST['name'];
        }
        $result['status'] = false;
        if ($this->delete(array('name'=>$name))) {
            $result = $this->getPlugin($name)->uninstall();
        }
        $result['msg'] = $result['status'] ? $name . " has been uninstalled" : "Oops, something went wrong";
        return $result;
    }

    /**
     * Activate plugin
     *
     * @param string $name: plugin name
     * @return array: array("msg"=>string, "status"=>bool)
     */
    public function activate($name)
    {
        if (isset($_POST['name'])) {
            $name = $_POST['name'];
        }

        $result['status'] = $this->update(array('status'=>1), array('name'=>$name));
        $result['msg'] = $result['status'] ? $name . " has been activated" : "Oops, something went wrong";
        return $result;
    }

    /**
     * Deactivate plugin
     *
     * @param string $name: plugin name
     * @return array: array("msg"=>string, "status"=>bool)
     */
    public function deactivate($name)
    {
        if (isset($_POST['name'])) {
            $name = $_POST['name'];
        }
        $result['status'] =  $this->update(array('status'=>0), array('name'=>$name));
        $result['msg'] = $result['status'] ? $name . " has been deactivated" : "Oops, something went wrong";
        return $result;
    }

    /**
     * Check if the plugin is registered to the Plugin table
     * @param string $name: plugin name
     * @return bool
     */
    public function isInstalled($name)
    {
        return $this->isExist(array('name'=>$name));
    }

    /**
     * Instantiate a class from class name
     * @param: class name (must be the same as the file name)
     * @return Plugin
     */
    public function getPlugin($pluginName)
    {
        if (empty($this->instances) || !in_array($pluginName, array_keys($this->instances))) {
            $pluginName = '\\Plugins\\' . $pluginName;
            $this->instances[$pluginName] = new $pluginName();
        }
        return $this->instances[$pluginName];
    }

    /**
     * Get list of plugins (associated with the current page)
     * @param $page
     * @return array
     */
    private function getPluginsList($page)
    {
        if ($page == false) {
            $pluginList = array_diff(scandir(PATH_TO_PLUGINS), array('.', '..'));
        } else {
            $pluginList = array();
            foreach ($this->all(array("page" => $page)) as $key => $item) {
                $pluginList[] = $item['name'];
            }
        }
        return $pluginList;
    }

    /**
     * Returns Plugin's options form
     * @param string $name: plugin name
     * @return string: form
     */
    public function getOptions($name = null)
    {
        if (isset($_POST['name'])) {
            $name = $_POST['name'];
        }
        return $this->displayOpt($name, $this->getPlugin($name)->options);
    }

    /**
     * Get list of plugins, their settings and status
     * @param null|string $page
     * @return array
     */
    public function loadAll($page = null)
    {
        $plugins = array();
        foreach ($this->getPluginsList($page) as $key => $plugin_name) {
            if (!empty($plugin_name) && !in_array($plugin_name, array('.','..','Autoloader.php'))) {
                $plugins[$plugin_name] = $this->load($plugin_name, $page);
            }
        }
        return $plugins;
    }

    /**
     * Load plugin
     * @param string $name
     * @param null $page
     * @return array
     */
    public function load($name, $page = null)
    {
        // Get plugin info if installed
        $installed = $this->isInstalled($name);

        if ($installed) {
            $this->getPlugin($name)->setInfo($this->get(array('name'=>$name)));
        }

        // Instantiate plugin
        $thisPlugin = $this->getPlugin($name);

        return array(
            'installed'=>$installed,
            'status'=>intval($thisPlugin->status),
            'page'=>$thisPlugin->page,
            'options'=>$thisPlugin->options,
            'version'=>$thisPlugin->version,
            'description'=>$thisPlugin->description,
            'display'=>($installed && !is_null($page)) ? $thisPlugin->show() : null
        );
    }

    /**
     * Update plugin's options
     *
     * @param array $data
     * @return mixed
     */
    public function updateOptions(array $data)
    {
        $name = htmlspecialchars($data['name']);
        if ($this->isInstalled($name)) {
            foreach ($data as $key => $setting) {
                $this->getPlugin($name)->setOption($key, $setting);
            }

            if ($this->update($this->getPlugin($name)->getInfo(), array('name'=>$name))) {
                $result['status'] = true;
                $result['msg'] = "{$name}'s settings successfully updated!";
            } else {
                $result['status'] = false;
            }
        } else {
            $result['status'] = false;
            $result['msg'] = "You must install this plugin before modifying its settings";
        }

        return $result;
    }

    /**
     * Display Plugin's settings
     * @param string $name: plugin name
     * @param array $options
     * @return string
     */
    public function displayOpt($name, array $options)
    {
        $content = "<h4 style='font-weight: 600;'>Options</h4>";
        if (!empty($options)) {
            $content .= "
                <form method='post' action='php/router.php?controller=". __CLASS__ .
                 "&action=updateOptions&name={$name}'>
                    " . self::renderOptions($options) . "
                    <div class='submit_btns'>
                        <input type='submit' class='processform' value='Modify'>
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
    public function show()
    {
        return self::showAll($this->loadAll());
    }

    /* VIEWS */

    private static function renderOptions(array $options)
    {
        $opt = '';
        foreach ($options as $optName => $settings) {
            if (isset($settings['options']) && !empty($settings['options'])) {
                $options = "";
                foreach ($settings['options'] as $prop => $value) {
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
        return $opt;
    }

    /**
     * Render install/uninstall button
     * @param $pluginName
     * @param $installed
     * @return string
     */
    private static function installButton($pluginName, $installed)
    {
        if ($installed) {
            return "<div class='installDep workBtn uninstallBtn' data-controller='" . __CLASS__ .
             "' data-action='uninstall' 
            data-name='$pluginName'></div>";
        } else {
            return "<div class='installDep workBtn installBtn' data-controller='" . __CLASS__ .
             "' data-action='install' 
            data-name='$pluginName'></div>";
        }
    }

    /**
     * Render activate/deactivate button
     * @param $pluginName
     * @param $status
     * @return string
     */
    private static function activateButton($pluginName, $status)
    {
        if ($status === 1) {
            return "<div class='activateDep workBtn deactivateBtn' data-controller='" . __CLASS__ .
            "' data-action='deactivate' 
            data-name='$pluginName'></div>";
        } else {
            return "<div class='activateDep workBtn activateBtn' data-controller='" . __CLASS__ .
            "' data-action='activate' 
            data-name='$pluginName'></div>";
        }
    }

    /**
     * Show list of plugins
     * @param array $pluginsList
     * @return string
     */
    private static function showAll(array $pluginsList)
    {
        $plugin_list = "";
        foreach ($pluginsList as $pluginName => $info) {
            $pluginDescription = (!empty($info['description'])) ? $info['description'] : null;
            $install_btn = self::installButton($pluginName, $info['installed']);
            $activate_btn = self::activateButton($pluginName, $info['status']);

            $plugin_list .= "
            <div class='plugDiv' id='plugin_{$pluginName}'>
                <div class='plugHeader'>
                    <div class='plug_header_panel'>
                        <div class='plugName'>{$pluginName}</div>
                    </div>
                    <div class='optBar'>
                        <div class='loadContent workBtn settingsBtn' data-controller='" . __CLASS__ .
                        "' data-action='getOptions' data-destination='.plugOpt#{$pluginName}' 
                        data-name='{$pluginName}'></div>
                        {$install_btn}
                        {$activate_btn}
                    </div>
                </div>

                <div class='plugSettings'>
                    <div class='description'>
                        <div class='version'>Version: {$info['version']}</div>
                        {$pluginDescription}
                    </div>
                    
                    <div>                        
                        <div class='plugOpt' id='{$pluginName}'></div>
                    </div>

                </div>
                
            </div>
            ";
        }
        return $plugin_list;
    }
}
