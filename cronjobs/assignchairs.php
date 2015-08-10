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

require('../includes/boot.php');

class AssignChairs extends AppCron {
    /**
     * Assign chairmen for the next n sessions
     * @return bool
     */

    public $name = 'AssignChairs';
    public $path;
    public $status = 'Off';
    public $installed = False;
    public $time;
    public $dayName;
    public $dayNb;
    public $hour;
    public $options;

    public function __construct(DbSet $db) {
        parent::__construct($db);
        $this->path = basename(__FILE__);
        $this->time = AppCron::parseTime($this->dayNb, $this->dayName, $this->hour);
    }

    public function install() {
        // Register the plugin in the db
        $class_vars = get_class_vars($this->name);
        return $this->make($class_vars);
    }

    /**
     * Run scheduled task: assign chairmen
     * @return bool|string
     */
    public function run() {
        global $db, $AppConfig, $Sessions;
        if ($AppConfig->speakerAssign == "manual") {
            echo "<p>Chair assignment is set to Manual. We have nothing to do!</p>";
            return "Chair assignment is set to Manual. We have nothing to do!";
        }

        // Get sessions dates
        $Presentation = new Presentation($this->db);

        $jc_days = $Sessions->getjcdates($AppConfig->nbsessiontoplan);
        $created = 0;
        $updated = 0;
        foreach ($jc_days as $day) {
            $session = new Session($db, $day);

            if ($session->type !== "none") {
                // If a session is planned for this day, we assign X speakers (1 speaker by presentation)
                echo "<p><b>Session: $session->date | $session->type</b></p>";
                for ($p = 0; $p < $session->max_nb_session; $p++) {

                    $Newspeaker = $Presentation->getSpeakers($day);
                    $Presentation->make(array('speaker'=>$Newspeaker));

                    echo "<p>Chair: $Newspeaker</p>";
                    $updated += 1;

                    // Update session info
                    $session->get();
                }
            }
        }
        $result = "$created chair(s) created<br>$updated chair(s) updated";
        $this->logger("$this->name.txt",$result);
        return $result;
    }
}
