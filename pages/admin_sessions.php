<?php
/**
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

// Declare classes
$user = new User($db,$_SESSION['username']);

$Sessionslist = $Sessions->managesessions();
$timeopt = maketimeopt();

//Get session types
$Sessionstype = "";
$opttypedflt = "";
foreach ($AppConfig->session_type as $type=>$chairs) {
    $Sessionstype .= "
        <div class='type_div' id='session_$type'>
            <div class='type_name'>".ucfirst($type)."</div>
            <div class='type_del' data-type='$type' data-class='session'>
            </div>
        </div>
    ";
    if ($type == $AppConfig->session_type_default) {
        $opttypedflt .= "<option value='$type' selected>$type</option>";
    } else {
        $opttypedflt .= "<option value='$type'>$type</option>";
    }
}

/**  Get session types */
$prestype = "";
$prestypes = explode(',',$AppConfig->pres_type);
foreach ($prestypes as $type) {
    $prestype .= "
        <div class='type_div' id='pres_$type'>
            <div class='type_name'>".ucfirst($type)."</div>
            <div class='type_del' data-type='$type' data-class='pres'>
            </div>
        </div>
    ";
}

$result = "
<h1>Manage Sessions</h1>
<p class='page_description'>Here you can manage the journal club sessions, change their type, time, etc.</p>
<div class='section_container'>
    <div class='section_left'>
    <section>
        <h2>Sessions settings</h2>
        <form method='post' action='php/form.php' class='form' id='config_form_session'>
            <div class='feedback' id='feedback_jcsession'></div>
            <input type='hidden' name='config_modify' value='true'>
            <div class='formcontrol'>
                <label>Room</label>
                <input type='text' name='room' value='$AppConfig->room'>
            </div>
            <div class='formcontrol'>
                <label for='jc_day'>Day</label>
                <select name='jc_day'>
                    <option value='$AppConfig->jc_day' selected>$AppConfig->jc_day</option>
                    <option value='monday'>Monday</option>
                    <option value='tuesday'>Tuesday</option>
                    <option value='wednesday'>Wednesday</option>
                    <option value='thursday'>Thursday</option>
                    <option value='friday'>Friday</option>
                </select>
            </div>
            <div class='formcontrol'>
                <label>From</label>
                <select name='jc_time_from'>
                    <option value='$AppConfig->jc_time_from' selected>$AppConfig->jc_time_from</option>
                    $timeopt;
                </select>
            </div>
            <div class='formcontrol'>
                <label>To</label>
                <select name='jc_time_to'>
                    <option value='$AppConfig->jc_time_to' selected>$AppConfig->jc_time_to</option>
                    $timeopt;
                </select>
            </div>
            <div class='formcontrol'>
                <label>Presentations/Session</label>
                <input type='text' name='max_nb_session' value='$AppConfig->max_nb_session'/>
            </div>
            <p style='text-align: right'><input type='submit' name='modify' value='Modify' id='submit' class='processform'/></p>
        </form>
    </section>

    <section>
        <h2>Session/Presentation</h2>
        <h3>Sessions</h3>
        <div id='session_type' style='position: relative; margin-bottom: 20px;'>
            <div class='formcontrol'>
                <label>Default session type </label>
                <select class='session_type_default'>
                    $opttypedflt
                </select>
            </div>
        </div>
        <div style='font-size: 0;'>
            <input type='button' class='type_add addBtn' data-class='session' value='+'/>
            <input id='new_session_type' type='text' placeholder='New Category'/>
        </div>
        <div class='feedback' id='feedback_session'></div>
        <div class='type_list' id='session'>$Sessionstype</div>
        <h3>Presentations</h3>
        <div  style='font-size: 0;'>
            <input type='button' class='type_add addBtn' data-class='pres' value='+'/>
            <input id='new_pres_type' type='text' placeholder='New Category'/>
        </div>
        <div class='feedback' id='feedback_pres'></div>
        <div class='type_list' id='pres'>$prestype</div>
    </section>
    </div>

    <div class='section_right'>
    <section>
        <h2>Manage Sessions</h2>
        <div class='formcontrol'>
            <label>Session to show</label>
            <input type='date' class='selectSession' id='datepicker' name='date' data-status='admin'>
        </div>
        <div id='sessionlist'>
        $Sessionslist
        </div>
    </section>
    </div>
</div>";

echo json_encode($result);
exit;
