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

/**
 * Assign chairmen for the next n sessions
 * @return bool
 */
function assignchairs() {
    global $db, $AppConfig, $Sessions;
    if ($AppConfig->chair_assign == "manual") {echo "<p>Chair assignment is set to Manual. We have nothing to do!</p>"; return false;}

    $AppConfig = new AppConfig($db);
    $nbsessions = $AppConfig->nbsessiontoplan; // Number of sessions to plan in advance

    $jc_days = $Sessions->getjcdates($nbsessions);
    foreach ($jc_days as $day) {
        $session = new Session($db, $day);
        $chairs = explodecontent(',',$session->chairs);
        $presids = explodecontent(',',$session->presid);
        $speakers = explodecontent(',',$session->speakers);
        $truechairs = array_values(array_diff($chairs,array("TBA"))); // Remove to be announced chairs
        $chairstoplan = $AppConfig->max_nb_session - count($truechairs); // Number of chairs to plan for this session
        if ($chairstoplan > 0 && $session->type !== "none") {
            for ($p = 1; $p <= $AppConfig->max_nb_session; $p++) {
                $presid = (!empty($presids[$p]) ? $presids[$p]:false);
                $speaker = (!empty($speakers[$p]) ? $speakers[$p]:false);

                if (empty($chairs[$p]) || $chairs[$p]=="TBA") {
                    $chair = $Sessions->getchair($day);
                    $sessionpost = array(
                        "chairs" => $chair,
                        "date" => $day,
                        "presid"=>$presid,
                        "speakers" => $speaker
                    );
                    $session = new Session($db);
                    $session->make($sessionpost);
                }
            }
            echo "<p><b>$session->date</b> | Chairs: $session->chairs | Speakers : $session->speakers</p>";
        } else {
            echo "<p><b>$session->date</b> | Nothing to plan for this session</p>";
        }
    }
    return true;
}

/** Run chair assignement */
assignchairs();
