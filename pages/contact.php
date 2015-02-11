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

@session_start();
require_once($_SESSION['path_to_includes'].'includes.php');

$admins = new users();
$config = new site_config('get');

$admin = $config -> getadmin();
$admin_contact = "";
$mail_option = "";
$msg = "";

for ($i=0; $i<count($admin); $i++) {
    if ($admin[$i]['username'] != "admin") {
        $admin_mail = $admin[$i]['email'];
        $admin_name = $admin[$i]['firstname'].' '.$admin[$i]['lastname'];
        $mail_option .= "<option value='$admin_mail'>$admin_name</option>";
        $admin_contact .= "<li>$admin_name</li>";
    }
}

// Lab information

$result = "
    <div id='content'>
        <span id='pagename'>Contact information</span>

        <div style='display: inline; float: left; width: 40%;'>
            <div class='section_header'>Organizer(s)</div>
            <div class='section_content'>
                $admin_contact
            </div>

            <div class='section_header'>Access</div>
            <div class='section_content' style='width: auto;'>
                $config->lab_name</br>
                $config->lab_street</br>
                $config->lab_postcode, $config->lab_city, $config->lab_country
            </div>

            <div class='section_header'>Journal Club information</div>
            <div class='section_content' style='width: auto;'>
                Day: $config->jc_day<br>
                Time: from $config->jc_time_from to $config->jc_time_to<br>
                Room: $config->room<br>
            </div>

            <div class='section_header'>Map</div>
            <div class='section_content'>
                <iframe src='https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d2625.1625847749524!2d2.3307604!3d4
                8.85511!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47e671d7b93cd93d%3A0xb8d9c2989da20197!2sRue+des+Saints-P
                %C3%A8res%2C+Paris!5e0!3m2!1sfr!2sfr!4v1411491314141' width='100%' height='auto' frameborder='0' style='border:0'></iframe>
            </div>
        </div>

        <div style='display: inline; float: right; clear: right; width: 50%;'>
            <div class='section_header' style='width: auto;'>Send Email to organizers</div>
            <div class='section_content'>
            <div class='feedback'></div>
            <form method='post' action='' class='form' id='contact_form'>
                <label for='admin_mail'>Organizer</label>
                    <select name='admin_mail' id='admin_mail'>
                        <option value='none' selected='selected'>Select an organizer</option>
                        $mail_option
                    </select></br>
                <label for='name'>Your name</label><input type='text' name='name' id='contact_name' value='Your name'><br>
                <label for='mail'>E-mail</label><input type='text' name='mail' id='contact_mail' value='Your email'><br>
                <label for='message'>Message</label><br>
                <textarea id='message' name='message' rows='10' cols='50'>Your message</textarea><br>
                <p style='text-align: right;'><input type='submit' name='send' value='Send' id='submit' class='contact_send'></p>
            </form>
            </div>
        </div>
    </div>
";

echo json_encode($result);
