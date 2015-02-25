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

date_default_timezone_set('Europe/Paris');
@session_start();
// Includes required files (classes)
require_once($_SESSION['path_to_includes'].'db_connect.php');
require_once($_SESSION['path_to_includes'].'users.php');
require_once($_SESSION['path_to_includes'].'myMail.php');
require_once($_SESSION['path_to_includes'].'Posts.php');
require_once($_SESSION['path_to_includes']."Presentation.php");
require_once($_SESSION['path_to_includes']."Session.php");
require_once($_SESSION['path_to_includes']."site_config.php");
include_once($_SESSION['path_to_includes'].'functions.php');
include_once($_SESSION['path_to_includes'].'PasswordHash.php');


// Get site config
$config_file = $_SESSION['path_to_app']."config/config.php";
if (is_file($config_file)) {
    require_once($config_file);
}
