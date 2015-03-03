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

/**
 * Define timezone
 *
 */
date_default_timezone_set('Europe/Paris');

/**
 * Define paths
 */
if(!defined('APP_NAME')) define('APP_NAME', basename(__DIR__));
if(!defined('PATH_TO_APP')) define('PATH_TO_APP', dirname(dirname(__FILE__).'/'));
if(!defined('PATH_TO_IMG')) define('PATH_TO_IMG', PATH_TO_APP.'/images/');
if(!defined('PATH_TO_INCLUDES')) define('PATH_TO_INCLUDES', PATH_TO_APP.'/includes/');
if(!defined('PATH_TO_PHP')) define('PATH_TO_PHP', PATH_TO_APP.'/php/');
if(!defined('PATH_TO_PAGES')) define('PATH_TO_PAGES', PATH_TO_APP.'/pages/');
if(!defined('PATH_TO_CONFIG')) define('PATH_TO_CONFIG', PATH_TO_APP.'/config/');
if(!defined('PATH_TO_LIBS')) define('PATH_TO_LIBS', PATH_TO_APP.'/libs/');


/**
 * Include dependencies
 *
 */
set_include_path(PATH_TO_INCLUDES);

/**
 * Includes required files (classes)
 */
require_once('SessionInstance.php');
require_once('DbSet.php');
require_once('User.php');
require_once('AppMail.php');
require_once('Posts.php');
require_once("Presentation.php");
require_once("Session.php");
require_once("AppConfig.php");
include_once('functions.php');
include_once('PasswordHash.php');

/**
 * Start session
 *
 */
SessionInstance::initsession();

/**
 * Declare classes
 *
 */
$db = new DbSet();
$Presentations = new Presentations($db);
$Users = new Users($db);
$Sessions = new Sessions($db);
$AppConfig = new AppConfig($db);
$AppMail = new AppMail($db,$AppConfig);
