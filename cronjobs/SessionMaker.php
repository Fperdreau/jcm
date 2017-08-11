<?php
/**
 * File for class SessionMaker
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
 * Class SessionMaker
 *
 * Automatically repeatAll and create sessions based on user defined rules
 */
class SessionMaker extends Tasks {

    public $name = 'SessionMaker';
    public $status = 'Off';
    public $installed = False;
    public $options = array(
        'session_to_plan'=>array(
            'options'=>array(),
            'value'=>10)
    );
    public static $description = "Automatically create sessions based on user defined rules.";

    /**
     * Mailing constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->path = basename(__FILE__);
    }

    /**
     * Install schedule task
     * @return bool|mysqli_result
     */
    public function install() {
        $class_vars = get_class_vars($this->name);
        return $this->make($class_vars);
    }

    /**
     * Run scheduled task
     * @param null|string $max_date: date until which session should be repeated
     * @return string
     */
    public function run($max_date=null) {
        $logs = null;
        $Sessions = new Session();

        $Sessions->repeatAll($max_date, $this->options['session_to_plan']['value']);
        return $logs;
    }

}


