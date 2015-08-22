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

// Declare classes
require('../includes/boot.php');
$user = new User($db,$_SESSION['username']);

// Send mail
$result = "
    <h1>Mailing list</h1>
    <p class='page_description'>Here you can send an email to users who subscribed to the newsletter.</p>
    <div class='feedback'></div>
    <section>
        <h2>Send an email</h2>
        <form method='post' action=''>
            <div class='submit_btns'>
                <input type='submit' name='send' value='Send' id='submit' class='mailing_send'>
            </div>
            <div class='formcontrol' style='width: 100%;'>
                <label>Subject:</label>
                <input type='text' size='40' id='spec_head' name='spec_head' placeholder='Subject' value='' />
            </div>
            <div class='formcontrol' style='width: 100%;'>
                <label>Message</label>
                <textarea name='spec_msg' id='spec_msg' cols='70' rows='15' class='tinymce'></textarea>
            </div>
        </form>
    </section>";

echo json_encode($result);
exit;