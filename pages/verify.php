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

session_start();
$_SESSION['root_path'] = $_SERVER['DOCUMENT_ROOT'];
$_SESSION['app_name'] = "/jcm/";
$_SESSION['path_to_app'] = $_SESSION['root_path'].$_SESSION['app_name'];
$_SESSION['path_to_img'] = $_SESSION['path_to_app'].'images/';
$_SESSION['path_to_includes'] = $_SESSION['path_to_app']."includes/";
$_SESSION['path_to_html'] = $_SESSION['path_to_app']."php/";
$_SESSION['path_to_pages'] = $_SESSION['path_to_app']."pages/";
date_default_timezone_set('Europe/Paris');

// Includes required files (classes)
include_once($_SESSION['path_to_includes'].'includes.php');

if (!empty($_GET['hash']) && !empty($_GET['email']) && !empty($_GET['result'])) {
    require_once($_SESSION['path_to_app'].'/includes/users.php');
    $hash = htmlspecialchars($_GET['hash']);
    $email = htmlspecialchars($_GET['email']);
    $result = htmlspecialchars($_GET['result']);
    $user = new users();
    $valid = $user -> check_account_activation($hash,$email,$result);
    $result = "
    <div id='content'>
        <div class='section_header' style='width: auto; margin: auto'>Activation</div>
        <div class='section_content' style='width: auto; margin: auto'>
			<span id='warning'>$valid</span>
    	</div>
    </div>";
	
	echo json_encode($result);
}