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
$admin = $Users->getadmin();

$mail_option = "";
$msg = "";
$organizers = "";
for ($i=0; $i<count($admin); $i++) {
    if ($admin[$i]['username'] != "admin") {
        $admin_mail = $admin[$i]['email'];
        $admin_name = $admin[$i]['firstname'].' '.$admin[$i]['lastname'];
        $mail_option .= "<option value='$admin_mail'>$admin_name</option>";
        $organizers .= "<div>$admin_name</div>";
    }
}

$admin_contact = "$organizers";
$jc_day = ucfirst($AppConfig->jc_day);
// Lab information

$result = "
    <div class='page_header'>
    <h1>Contact</h1>
    </div>
    
    <div class='section_container'>
        <section>
            <h2>Where</h2>
            <div class='section_content'>
            $AppConfig->lab_name</br>
            $AppConfig->lab_street</br>
            $AppConfig->lab_postcode, $AppConfig->lab_city</br>
            $AppConfig->lab_country
            </div>
        </section>

        <section>
            <h2>When</h2>
            <div class='section_content'>
            <b>Day:</b> $jc_day<br>
            <b>From</b> $AppConfig->jc_time_from <b>to</b> $AppConfig->jc_time_to<br>
            <b>Room:</b> $AppConfig->room
            </div>
        </section>

        <section>
            <h2>Map</h2>
            <div class='section_content'>
            <iframe src='$AppConfig->lab_mapurl' width='100%' height='auto' frameborder='0' style='border:0'>
            </iframe>
            </div>
        </section>

        <section>
            <h2>Contact us</h2>
            <div class='section_content'>
            <div class='feedback'></div>
            <form id='contact_form' method='post' action='php/form.php'>
                <input type='hidden' name='contact_send' value='true'/>
                <div class='submit_btns'>
                    <input type='submit' name='send' value='Send' class='processform'>
                </div>
                <div class='form-group' style='width: 100%;'>
                    <select name='admin_mail' required>
                        <option value='none' selected='selected'>Select an organizer</option>
                        $mail_option
                    </select>
                    <label for='admin_mail'>Organizer</label>
                </div><br>
                <div class='form-group' style='width: 100%;'>
                    <input type='text' name='name' required>
                    <label for='name'>Your name</label>
                </div>
                <div class='form-group' style='width: 100%;'>
                    <input type='email' name='email' required>
                    <label for='mail'>E-mail</label>
                </div>
                <div class='form-group' style='width: 100%;'>
                    <textarea id='message' name='message' required></textarea>
                    <label for='message'>Message</label>
                </div>
            </form>
            </div>
        </section>
    </div>
";

echo json_encode($result);
exit;
