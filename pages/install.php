<?php
/*
Copyright © 2014, F. Perdreau, Radboud University Nijmegen
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

// Defaut parameters
session_start();
require_once($_SESSION['path_to_includes'].'includes.php');
$config = new site_config();

if (!empty($_GET['step'])) {
    $step = $_GET['step'];
} else {
    $step = 1;
}

if ($step == 1) {
    $title = "Step 1: Database configuration";
    $operation = "
		<form action='' method='post' name='install' id='install_db'>
            <input type='hidden' name='install_db' value='true' />
			<label for='host'>Host Name</label><input name='host' type='text' value='localhost'></br>
			<label for='username'>Username</label><input name='username' type='text' value='root'></br>
			<label for='passw'>Password</label><input name='passw' type='password' value='root'></br>
			<label for='dbname'>DB Name</label><input name='dbname' type='text' value='test'></br>
			<label for='dbprefix'>DB Prefix</label><input name='dbprefix' type='text' value='pjc'></br>
            <label for='sitetitle'>Site title</label><input name='sitetitle' type='text' value='$config->sitetitle'></br>
            <label for='site_url'>Web path to root</label><input name='site_url' type='text' value='$config->site_url' size='30'></br>
            <label for='mail_from'>Sender Email address</label><input name='mail_from' type='text' value='$config->mail_from'></br>
			<label for='mail_from_name'>Sender name</label><input name='mail_from_name' type='text' value='$config->mail_from_name'></br>
			<label for='mail_host'>Email host</label><input name='mail_host' type='text' value='$config->mail_host'></br>
            <label for='SMTP_secure'>SMTP access</label>
                <select name='SMTP_secure'>
                    <option value='$config->SMTP_secure' selected='selected'>$config->SMTP_secure</option>
                    <option value='ssl'>ssl</option>
                    <option value='tls'>tls</option>
                    <option value='none'>none</option>
                 </select>
			<label for='mail_port'>Email port</label><input name='mail_port' type='text' value='$config->mail_port'></br>
			<label for='mail_username'>Email username</label><input name='mail_username' type='text' class='$config->mail_username'></br>
            <label for='mail_password'>Email password</label><input name='mail_password' type='password' value='$config->mail_password'></br>
			<p style='text-align: right'><input type='submit' name='install_db' value='Next' id='submit' class='install_db'></p>
		</form>
		<div class='feedback'></div>
	";
} elseif ($step == 2) {
    $title = "Step 2: Admin account creation";
    $operation = "
    <div id='form' class='admin_login'>
        <form method='post' id='admin_creation'>
            <label for='admin_username'>UserName : </label><input id='admin_username' type='text' name='admin_username'><br/>
            <label for='admin_password'>Password : </label><input id='admin_password' type='password' name='admin_password'><br/>
            <label for='admin_confpassword'>Confirm password: </label><input id='admin_confpassword' type='password' name='admin_confpassword'><br/>
            <label for='admin_email'>Email: </label><input type='text' name='admin_email' id='admin_email'><br/>
            <input type='hidden' name='inst_admin' value='true' />
            <input type='submit' name='submit' value='Next' id='submit' class='admin_creation'>
        </form>
        <div class='feedback'></div>
    </div>
    ";
} else {
    $title = "Installation complete!";
    $operation = "
    <p id='success'>Congratulations!</p>
    <p id='warning'> Now, you must delete the 'install.php' file and the '/install/' directory from the root of your website</p>
    <p style='text-align: right'><input type='submit' name='submit' value='Finish' id='submit' class='finish'></p>";
}

$result = "
<div id='content'>
    <span id='pagename'>Installation</span>
    <div class='section_header' style='width: auto;'>$title</div>
    <div class='section_content'>
        <div id='operation'>$operation</div>
    </div>
</div>";

echo json_encode($result);
