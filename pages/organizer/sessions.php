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
$Session = new Session();
$Presentation = new Presentation();
$session_types = Session::renderTypes($Session->getSettings('types'), $Session->getSettings('default_type'));
$presentation_types = Presentation::renderTypes($Presentation->getSettings('types'), $Presentation->getSettings('default_type'));

$result = "
<div class='page_header'>
<p class='page_description'>Here you can manage the journal club sessions, change their type, time, etc.</p>
</div>
<div class='section_container'>
    <div class='section_left'>
        " . Session::default_settings_form($Session->getSettings()) . "
    
        <section>
            <h2>Session/Presentation</h2>
            <div class='section_content'>
                
                <div id='session_types_options'>
                    <h3>Sessions</h3>
                    <div id='renderTypes' style='position: relative; margin-bottom: 20px;'>
                        <div class='form-group'>
                            <select name='default_type' class='type_default' id='session'>
                                {$session_types['options']}
                            </select>
                            <label>Default session type</label>
                        </div>
                    </div>
                    <div style='font-size: 0;'>
                        <button class='type_add addBtn' data-class='session' value='+'/>
                        <input id='new_session_type' type='text' placeholder='New Category'/>
                    </div>
                    <div class='feedback' id='feedback_session'></div>
                    <div class='type_list' id='session'>{$session_types['types']}</div>
                </div>
               
               <div id='presentation_types_options'>
                    <h3>Presentations</h3>
                    <div id='renderTypes' style='position: relative; margin-bottom: 20px;'>
                        <div class='form-group'>
                            <select name='default_type' class='type_default' id='presentation'>
                                {$presentation_types['options']}
                            </select>
                            <label>Default presentation type</label>
                        </div>
                    </div>
                    <div  style='font-size: 0;'>
                        <button class='type_add addBtn' data-class='presentation' value='+'/>
                        <input id='new_presentation_type' type='text' placeholder='New Category'/>
                    </div>
                    <div class='feedback' id='feedback_pres'></div>
                    <div class='type_list' id='presentation'>{$presentation_types['types']}</div>
                </div>
                
            </div>
        </section>
    </div>

    <div class='section_right'>
        <section>
            <h2>Session Manager</h2>
            " . $Session->getSessionManager() . "
        </section>
    </div>
</div>";

echo $result;
