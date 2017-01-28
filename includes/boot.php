<?php
/**
 * PHP version 5
 *
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

/**
 * Boot.php
 *
 * Startup of the application.
 */

/**
 * Define timezone
 *
 */
date_default_timezone_set('Europe/Paris');
if (!ini_get('display_errors')) {
    error_reporting(E_ALL | E_STRICT);
    ini_set('display_errors', '0');
}

/**
 * Define paths
 */
if(!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
if(!defined('APP_NAME')) define('APP_NAME', basename(dirname(__DIR__)));
if(!defined('PATH_TO_APP')) define('PATH_TO_APP', dirname(dirname(__FILE__) . DS));
if(!defined('PATH_TO_IMG')) define('PATH_TO_IMG', PATH_TO_APP. DS . 'images' . DS);
if(!defined('PATH_TO_INCLUDES')) define('PATH_TO_INCLUDES', PATH_TO_APP. DS . 'includes' . DS);
if(!defined('PATH_TO_PHP')) define('PATH_TO_PHP', PATH_TO_APP . DS . 'php' . DS);
if(!defined('PATH_TO_PAGES')) define('PATH_TO_PAGES', PATH_TO_APP . DS .'pages' . DS);
if(!defined('PATH_TO_CONFIG')) define('PATH_TO_CONFIG', PATH_TO_APP . DS . 'config' . DS);
if(!defined('PATH_TO_LIBS')) define('PATH_TO_LIBS', PATH_TO_APP . DS . 'libs' . DS);

/**
 * Includes required files (classes)
 */
include_once(PATH_TO_INCLUDES.'AppDb.php');
include_once(PATH_TO_INCLUDES.'AppTable.php');
$includeList = scandir(PATH_TO_INCLUDES);
foreach ($includeList as $includeFile) {
    if (!in_array($includeFile, array('.', '..', 'boot.php'))) {
        require_once(PATH_TO_INCLUDES . $includeFile);
    }
}

/**
 * Start session
 *
 */
SessionInstance::initsession();

/**
 * Get logger
 */

$Corelogger = AppLogger::get_instance('core');

/**
 * Declare classes
 *
 */
$db = new AppDb();
$AppConfig = new AppConfig($db);
if(!defined('URL_TO_APP')) define('URL_TO_APP', $AppConfig->getAppUrl());
if(!defined('URL_TO_IMG')) define('URL_TO_IMG', URL_TO_APP . "/images/");

$AppPage = new AppPage($db);
$Presentations = new Presentations($db);
$Users = new Users($db);
$Sessions = new Sessions($db);
$AppMail = new AppMail($db);
$AppPlugins = new AppPlugins($db);
$AppPlugins->getPlugins();
