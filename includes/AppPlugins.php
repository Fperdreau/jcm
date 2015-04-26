<?php
/*
Copyright © 2014, Florian Perdreau
This file is part of Journal Club Manager.

Journal Club Manager is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Journal Club Manager is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with Journal Club Manager.  If not, see <http://www.gnu.org/licenses/>.
*/

class AppPlugins extends Table {

    protected $table_data = array(
        "id"=>array("INT NOT NULL AUTO_INCREMENT",false),
        "name"=>array("CHAR(20)",false),
        "version"=>array("CHAR(5)",false),
        "page"=>array("CHAR(20)",false),
        "status"=>array("CHAR(3)",false),
        "options"=>array("TEXT",false),
        "primary"=>'id'
    );
    public $name;
    public $version;
    public $page;
    public $status;
    public $installed;
    public $options;

    /**
     * Constructor
     * @param DbSet $db
     * @param bool $name
     */
    public function __construct(DbSet $db, $name=False) {
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
        $content = $this->parsenewdata($class_vars,$post, array('installed'));
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
        $content = $this->parsenewdata($class_vars,$post,array('installed'));
        return $this->db->updatecontent($this->db->tablesname['Plugins'],$content,array("name"=>$this->name));
    }

    /**
     * Check if the plugin is registered to the Plugin table
     * @return bool
     */
    public function isInstalled() {
        /**
         * Check if this plugin is registered to the db
         */
        $plugins = $this->db->getinfo($this->db->tablesname['Plugins'],'name');
        return in_array($this->name,$plugins);
    }

    /**
     * Create instance of the plugin from its name
     * @param $pluginName
     * @return mixed
     */
    public function instantiatePlugin($pluginName) {
        /**
         * Instantiate a class from class name
         * @param: class name (must be the same as the file name)
         * @return: object
         */
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
            $req = $this->db->send_query($sql);
            $pluginList = array();
            while ($row = mysqli_fetch_assoc($req)) {
                $pluginList[] = $row['name'];
            }
        }
        $plugins = array();
        foreach ($pluginList as $pluginfile) {
            if (!empty($pluginfile) && !in_array($pluginfile,array('.','..'))) {
                $thisPlugin = $this->instantiatePlugin($pluginfile);
                if ($thisPlugin->isInstalled()) {
                    $thisPlugin->get();
                }
                $plugins[$pluginfile] = array(
                    'installed' => $thisPlugin->installed,
                    'status' => $thisPlugin->status,
                    'page'=>$thisPlugin->page,
                    'options'=>$thisPlugin->options,
                    'version'=>$thisPlugin->version);

                $plugins[$pluginfile]['display'] = ($thisPlugin->isInstalled()) ? $thisPlugin->show():'';

            }
        }
        return $plugins;
    }

}