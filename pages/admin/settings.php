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

require('../../includes/boot.php');

// Declare classes
$user = new User($db,$_SESSION['username']);

// Configuration
// Make hours options list
$timeopt = maketimeopt();

$result = "
    <h1>Settings</h1>
    <div class='section_container'>
            <section>
                <h2>JCM settings</h2>
                <div class='section_content'>
                    <form method='post' action='php/form.php' class='form' id='config_form_site'>
                        <div class='submit_btns'>
                            <input type='submit' name='modify' value='Modify' class='processform'>
                        </div>
                        <input type='hidden' name='config_modify' value='true'/>
                        <div class='form-group'>
                            <select name='status'>
                                <option value='$AppConfig->status' selected>$AppConfig->status</option>
                                <option value='On'>On</option>
                                <option value='Off'>Off</option>
                            </select>
                            <label>Status</label>
                        </div>
                        <div class='form-group'>
                            <input type='text' name='upl_types' value='$AppConfig->upl_types'>
                            <label>Allowed file types (upload)</label>
                        </div>
                        <div class='form-group'>
                            <input type='text' name='upl_maxsize' value='$AppConfig->upl_maxsize'>
                            <label>Maximum file size (in Kb)</label>
                        </div>
                        <div class='feedback' id='feedback_site'></div>
                    </form>
                </div>
            </section>

            <section>
                <h2>Email host information</h2>
                <div class='section_content'>
                    <form method='post' action='php/form.php' class='form' id='config_form_mail'>
                        <div class='submit_btns'>
                            <input type='submit' value='Test settings' class='test_email_settings'> 
                            <input type='submit' name='modify' value='Modify' class='processform'>
                        </div>
                        <input type='hidden' name='config_modify' value='true'/>
                        <div class='form-group'>
                            <input name='mail_from' type='text' value='$AppConfig->mail_from'>
                            <label for='mail_from'>Sender Email address</label>
                        </div>
                        <div class='form-group'>
                            <input name='mail_from_name' type='text' value='$AppConfig->mail_from_name'>
                            <label for='mail_from_name'>Sender name</label>
                        </div>
                        <div class='form-group'>
                            <input name='mail_host' type='text' value='$AppConfig->mail_host'>
                            <label for='mail_host'>Email host</label>
                        </div>
                        <div class='form-group'>
                            <select name='SMTP_secure'>
                                <option value='$AppConfig->SMTP_secure' selected='selected'>$AppConfig->SMTP_secure</option>
                                <option value='ssl'>ssl</option>
                                <option value='tls'>tls</option>
                                <option value='none'>none</option>
                             </select>
                             <label for='SMTP_secure'>SMTP access</label>
                         </div>
                         <div class='form-group'>
                            <input name='mail_port' type='text' value='$AppConfig->mail_port'>
                            <label for='mail_port'>Email port</label>
                        </div>
                        <div class='form-group'>
                            <input name='mail_username' type='text' value='$AppConfig->mail_username'>
                            <label for='mail_username'>Email username</label>
                        </div>
                        <div class='form-group'>
                            <input name='mail_password' type='password' value='$AppConfig->mail_password'>
                            <label for='mail_password'>Email password</label>
                        </div>
                        <div class='form-group'>
                            <input name='pre_header' type='text' value='$AppConfig->pre_header'>
                            <label for='pre_header'>Email header prefix</label>
                        </div>
                        <div class='feedback' id='feedback_mail'></div>
                    </form>
                </div>
            </section>

            <section>
                <h2>Lab information</h2>
                <div class='section_content'>
                    <form method='post' action='php/form.php' class='form' id='config_form_lab'>
                        <div class='submit_btns'>
                            <input type='submit' name='modify' value='Modify' class='processform'>
                        </div>
                        <input type='hidden' name='config_modify' value='true'/>
                        <div class='form-group'>
                            <input type='text' name='lab_name' placeholder='Name of your Lab' value='$AppConfig->lab_name'>
                            <label for='lab_name'>Name</label>
                        </div>
                        <div class='form-group'>
                            <input type='text' name='lab_street' placeholder='Street of your Lab' value='$AppConfig->lab_street'>
                            <label for='lab_street'>Street</label>
                        </div>
                        <div class='form-group'>
                            <input type='text' name='lab_postcode' placeholder='Postcode of your lab' value='$AppConfig->lab_postcode'>
                            <label for='lab_postcode'>Post Code</label>
                        </div>
                        <div class='form-group'>
                            <input type='text' name='lab_city' placeholder='Your city' value='$AppConfig->lab_city'>
                            <label for='lab_city'>City</label>
                        </div>
                        <div class='form-group'>
                            <input type='text' name='lab_country' placeholder='Your country' value='$AppConfig->lab_country'>
                            <label for='lab_country'>Country</label>
                        </div>
                        <div class='form-group'>
                            <input type='text' name='lab_mapurl' placeholder='URL to the Google map' value='$AppConfig->lab_mapurl'>
                            <label for='lab_mapurl'>Google Map's URL</label>
                        </div>
                        <div class='feedback' id='feedback_lab'></div>
                    </form>
                </div>
            </section>
";

echo json_encode($result);
exit;