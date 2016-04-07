<?php
/**
 * File for class MakeGroup
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

require('../includes/boot.php');
require_once('../plugins/Groups/Groups.php');

/**
 * Class MakeGroup
 *
 * Scheduled task that creates users groups according to the number of presentations for a particular session
 * (1 group/presentation)
 */
class MakeGroup extends AppCron {

    public $name='MakeGroup';
    public $path;
    public $status='Off';
    public $installed=False;
    public $time;
    public $dayName;
    public $dayNb;
    public $hour;
    public $options;

    /**
     * MakeGroup constructor.
     * @param AppDb $db
     */
    public function __construct(AppDb $db) {
        parent::__construct($db);
        $this->path = basename(__FILE__);
        $this->time = AppCron::parseTime($this->dayNb, $this->dayName, $this->hour);
    }
    
    /**
     * Run scheduled task
     * @return array|string
     */
    public function run() {
        global $db;
        $groups = new Groups($db);
        return $groups->run();
    }
}

