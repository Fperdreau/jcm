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

$Users = new Users();

$mail_option = "";
$msg = "";
$organizers = "";
foreach ($Users->getAdmin() as $key=>$item) {
    if ($item['username'] != "admin") {
        $admin_mail = $item['email'];
        $admin_name = $item['firstname'].' '.$item['lastname'];
        $mail_option .= "<option value='$admin_mail'>$admin_name</option>";
        $organizers .= "<div>$admin_name</div>";
    }
}

$Lab = new Lab();
$Session = new Session();
$admin_contact = "$organizers";
$jc_day = ucfirst($Session->getSettings('jc_day'));
// Lab information

$result = "   
    <div class='section_container'>
        <section>
            <h2>Where</h2>
            <div class='section_content'>
            " . $Lab->getSettings('name') ."</br>
            " . $Lab->getSettings('street') ."</br>
            " . $Lab->getSettings('city') ."</br>
            " . $Lab->getSettings('country') ."
            </div>
        </section>

        <section>
            <h2>When</h2>
            <div class='section_content'>
            <b>Day:</b> " . $Session->getSettings('jc_day'). "<br>
            <b>From</b> " . $Session->getSettings('jc_time_from'). " <b>to</b> " . $Session->getSettings('jc_time_to'). "<br>
            <b>Room:</b> " . $Session->getSettings('room'). "
            </div>
        </section>

        <section>
            <h2>Map</h2>
            <div class='section_content'>
            <iframe src='" . $Lab->getSettings('url'). "' width='100%' height='auto' frameborder='0' style='border:0'>
            </iframe>
            </div>
        </section>

        <section>
            <h2>Contact us</h2>
            <div class='section_content'>
            <div class='feedback'></div>
            <form method='post' action='php/router.php?controller=MailManager&action=sendMessage'>
                <div class='submit_btns'>
                    <input type='submit' name='send' value='Send' class='processform'>
                </div>
                <div class='form-group' style='width: 100%;'>
                    <select name='admin_mail' required>
                        <option value='none' selected disabled>Select an organizer</option>
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

echo $result;
