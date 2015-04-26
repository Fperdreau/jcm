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

/**
 * todo: implement a dependency container that handles instances of configuration, database, plugins,
 * todo: all linked to a particular session instance
 * Class AppCore
 */
class AppCore {

    public $config;
    public $plugins;
    public $db;

    /**
     * Constructor
     */
    public function __construct() {

    }

    /**
     * Get application's configuration
     * @return mixed
     */
    public function getConfig() {
        $this->config = new AppConfig($this->db);
        return $this->config;
    }

    /**
     * Get list of installed plugins
     */
    public function getPlugins() {
        $this->plugins;
    }

}