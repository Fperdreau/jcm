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
 * Class DependencyContainer
 */
class DependencyContainer {

    private $instances = array();
    private $params = array();

    public function __construct($params) {
        $this->params = $params;
    }

    public static function init() {

    }

    /**
     * Create or get Database instance
     *
     * @return mixed
     */
    public function getDb() {
        if (empty($this->instances['db'])
            || !is_a($this->instances['db'], 'MYSQL')
        ) {
            $this->instances['db'] = new DbSet();
        }
        return $this->instances['db'];
    }
}