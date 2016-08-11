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

$user = new User($db);

// Modify user password
if (!empty($_GET['hash']) && !empty($_GET['email'])) {
    $hash = htmlspecialchars($_GET['hash']);
    $email = htmlspecialchars($_GET['email']);
    $username = $db ->getinfo($db->tablesname['User'],'username',array("email"),array("'$email'"));
    $user->get($username);
    if ($user->hash == $hash) {
        $content = "
            <form id='conf_changepw'>
                <input type='hidden' name='conf_changepw' value='true'/>
                <input type='hidden' name='username' value='$username' id='ch_username'/>
                <div class='formcontrol'>
                    <label for='password'>New Password</label>
                    <input type='password' name='password' value='' required/>
                </div>
                <div class='formcontrol'>
                    <label for='conf_password'>Confirm password</label>
                    <input type='password' name='conf_password' value='' required/></br>
                </div>
                <div class='submit_btns'>
                    <input type='submit' name='login' value='Submit' class='conf_changepw'/>
                </div>
            </form>";
    } else {
        $content = "<div class='sys_msg warning'>Incorrect email or hash id.</div>";
    }
    $result = "
        <section>
            <h2>Change password</h2>
            <div class='section_content'>$content</div>
        </section>";
} else {
    $result = "
        <section>
            <h2>Change password</h2>
            <div class='section_content'><div class='sys_msg warning'>Incorrect email or hash id.</div></div>
        </section>";
}

echo json_encode($result);
exit;
