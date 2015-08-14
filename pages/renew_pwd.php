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

$user = new User($db);

// Modify user password
if (!empty($_GET['hash']) && !empty($_GET['email'])) {
    $hash = htmlspecialchars($_GET['hash']);
    $email = htmlspecialchars($_GET['email']);
    $username = $db ->getinfo($db->tablesname['User'],'username',array("email"),array("'$email'"));
    $user->get($username);
    if ($user->hash == $hash) {
        $content = "
            <input type='hidden' name='username' value='$username' id='ch_username'/>
            <label for='password'>Password</label><input id='ch_password' type='password' name='password' value=''/></br>
            <label for='conf_password'>Confirm password</label><input id='ch_conf_password' type='password' name='conf_password' value=''/></br>
            <p style='text-align: right'><input type='submit' name='login' value='Submit' class='conf_changepw' id='submit'/></p>
            <div class='feedback'></div>";
    } else {
        $content = "<p id='warning'>Incorrect email or hash id.</p>";
    }
    $result = "
            <div id='content'>
                <h2>Change password</h2>
                <section>
                    $content
                </section>
            </div>";
    echo json_encode($result);
}


