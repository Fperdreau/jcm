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
class PluginsManager extends BaseModel {

    private $instances = array();



    /**
     * Add plugin to the Plugin table
     * @param array $post
     * @return bool|mysqli_result
     */
    public function make($post=array()) {
        return $this->add($post);
    }

    /**
     * Uninstall plugin: delete entry from the Plugin table and delete corresponding tables
     * @param array $id
     * @return bool|mysqli_result
     */
    public function delete(array $id) {
        $this->db->delete($this->db->tablesname['PluginsManager'], $id);
        if ($this->db->tableExists($this->tablename)) {
            return $this->db->deletetable($this->tablename);
        } else {
            return true;
        }
    }

    /**
     * Check if the plugin is registered to the Plugin table
     * @param string $name: plugin name
     * @return bool
     */
    public function isInstalled($name) {
        return $this->is_exist(array('name'=>$name));
    }

    /**
     * Instantiate a class from class name
     * @param: class name (must be the same as the file name)
     * @return Plugin
     */
    public function getInstance($pluginName) {
        if (empty($this->instances) || !in_array($pluginName, array_keys($this->instances))) {
            include_once(PATH_TO_PLUGINS . $pluginName . DS . $pluginName . '.php');
            $this->instances[$pluginName] = new $pluginName();
        }
        return $this->instances[$pluginName];
    }

    /**
     * Get list of plugins (associated with the current page)
     * @param $page
     * @return array
     */
    private function getPluginsList($page) {
        if ($page == False) {
            $pluginList = array_diff(scandir(PATH_TO_PLUGINS), array('.', '..'));
        } else {
            $pluginList = array();
            foreach ($this->all(array("page"=>$page)) as $key=>$item) {
                $pluginList[] = $item['name'];
            }
        }
        return $pluginList;
    }

    /**
     * Get list of plugins, their settings and status
     * @param bool $page
     * @return array
     */
    public function getPlugins($page=False) {

        $plugins = array();
        foreach ($this->getPluginsList($page) as $key=>$plugin_name) {
            if (!empty($plugin_name) && !in_array($plugin_name,array('.','..'))) {

                // Instantiate plugin
                $thisPlugin = $this->getInstance($plugin_name);

                // Get plugin info if installed
                $installed = $this->isInstalled($plugin_name);

                if ($installed) {
                    $this->getInstance($plugin_name)->setInfo($this->get(array('name'=>$plugin_name)));
                }

                $plugins[$plugin_name] = array(
                    'installed' => $installed,
                    'status' => $thisPlugin->status,
                    'page'=>$thisPlugin->page,
                    'options'=>$thisPlugin->options,
                    'version'=>$thisPlugin::version,
                    'description'=>$thisPlugin::description
                );

                $plugins[$plugin_name]['display'] = ($installed && $page !== false) ? $thisPlugin::show():'';
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
     * @param array $pluginsList
     * @return string
     */
    public function show(array $pluginsList) {
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

}