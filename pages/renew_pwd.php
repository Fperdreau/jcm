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

@session_start();
$_SESSION['root_path'] = $_SERVER['DOCUMENT_ROOT'];
$_SESSION['app_name'] = "/jcm/";
$_SESSION['path_to_app'] = $_SESSION['root_path'].$_SESSION['app_name'];
$_SESSION['path_to_includes'] = $_SESSION['path_to_app']."includes/";
date_default_timezone_set('Europe/Paris');

require_once($_SESSION['path_to_includes'].'includes.php');
require_once($_SESSION['path_to_app']."/admin/conf/config.php");
$user = new users();
$db_set = new DB_set();

// Modify user password
if (!empty($_GET['hash']) && !empty($_GET['email'])) {
    $hash = htmlspecialchars($_GET['hash']);
    $email = htmlspecialchars($_GET['email']);
    $username = $db_set ->getinfo($users_table,'username',array("email"),array("'$email'"));
    $user->getuserinfo($username);
    if ($user->hash == $hash) {
        $result = "
            <div id='content'>
                <div class='section_header'>Change password</div>
                <div class='section_content'>
                    <input type='hidden' name='username' value='$username' id='ch_username'/>
                    <label for='password'>Password</label><input id='ch_password' type='password' name='password' value=''/></br>
                    <label for='conf_password'>Confirm password</label><input id='ch_conf_password' type='password' name='conf_password' value=''/></br>
                    <p style='text-align: right'><input type='submit' name='login' value='Submit' class='conf_changepw' id='submit'/></p>
                    <div class='error' id='missfield'>This field is required</div>
                </div>
                <div class='feedback'></div>
            </div>
    ";
    } else {
        $result = "
            <div id='content'>
                <div class='section_header'>Change password</div>
                <div class='section_content'>
                    <p id='warning'>Incorrect email or hash id.</p>
                </div>
            </div>
    ";
    }
    echo json_encode($result);
}


