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

// Declare classes
$user = new User($_SESSION['username']);
$Sessions = new Sessions();
$AppConfig = AppConfig::getInstance();
$Sessionslist = $Sessions->sessionManager();
$timeopt = maketimeopt();
$session_types = Session::session_type();
$presentation_types = Session::presentation_type();

$result = "
<div class='page_header'>
<p class='page_description'>Here you can manage the journal club sessions, change their type, time, etc.</p>
</div>
<div class='section_container'>
    <div class='section_left'>
    <section>
        <h2>Default Session Settings</h2>
        <div class='section_content'>
            <form method='post' action='php/form.php' class='form' id='config_form_session'>
                <div class='feedback' id='feedback_jcsession'></div>
                <input type='hidden' name='config_modify' value='true'>
                <div class='form-group'>
                    <input type='text' name='room' value='{$AppConfig->room}'>
                    <label>Room</label>
                </div>
                <div class='form-group'>
                    <select name='jc_day'>
                        <option value='{$AppConfig->jc_day}' selected>{$AppConfig->jc_day}</option>
                        <option value='monday'>Monday</option>
                        <option value='tuesday'>Tuesday</option>
                        <option value='wednesday'>Wednesday</option>
                        <option value='thursday'>Thursday</option>
                        <option value='friday'>Friday</option>
                    </select>
                    <label for='jc_day'>Day</label>
                </div>
                <div class='form-group'>
                    <input type='time' name='jc_time_from' value='{$AppConfig->jc_time_from}' />
                    <label>From</label>
                </div>
                <div class='form-group'>
                    <input type='time' name='jc_time_to' value='{$AppConfig->jc_time_to}' />
                    <label>To</label>
                </div>
                <div class='form-group'>
                    <input type='number' name='max_nb_session' value='{$AppConfig->max_nb_session}'/>
                    <label>Slots/Session</label>
                </div>
                <p style='text-align: right'><input type='submit' name='modify' value='Modify' id='submit' class='processform'/></p>
            </form>
        </div>
    </section>

    <section>
        <h2>Session/Presentation</h2>
        <div class='section_content'>
            <h3>Sessions</h3>
            <div id='session_type' style='position: relative; margin-bottom: 20px;'>
                <div class='form-group'>
                    <select class='session_type_default'>
                        {$session_types['default']}
                    </select>
                    <label>Default session type </label>
                </div>
            </div>
            <div style='font-size: 0;'>
                <button class='type_add addBtn' data-class='session' value='+'/>
                <input id='new_session_type' type='text' placeholder='New Category'/>
            </div>
            <div class='feedback' id='feedback_session'></div>
            <div class='type_list' id='session'>{$session_types['types']}</div>
            <h3>Presentations</h3>
            <div  style='font-size: 0;'>
                <button class='type_add addBtn' data-class='pres' value='+'/>
                <input id='new_pres_type' type='text' placeholder='New Category'/>
            </div>
            <div class='feedback' id='feedback_pres'></div>
            <div class='type_list' id='pres'>{$presentation_types}</div>
        </div>
    </section>
    </div>

    <div class='section_right'>
        <section>
            <h2>Manage Sessions</h2>
            <div class='section_content'>
                <div class='form-group'>
                    <input type='date' class='selectSession' id='datepicker' name='date' data-status='admin'>
                    <label>Session to show</label>
                </div>
                <div id='sessionlist'>
                {$Sessionslist}
                </div>
            </div>
        </section>
    </div>
</div>";

echo $result;
