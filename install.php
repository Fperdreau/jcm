<?php
/**
 * page for installation
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
 * BOOTING PART
 */

/**
 * Define timezone
 *
 */
date_default_timezone_set('Europe/Paris');
if (!ini_get('display_errors')) {
    //error_reporting(E_ALL | E_STRICT);
    ini_set('display_errors', '0');
}

/**
 * Define paths
 */
if(!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
if(!defined('APP_NAME')) define('APP_NAME', basename(__DIR__));
if(!defined('PATH_TO_APP')) define('PATH_TO_APP', dirname(__FILE__ . DS));
if(!defined('PATH_TO_IMG')) define('PATH_TO_IMG', PATH_TO_APP. DS . 'images' . DS);
if(!defined('PATH_TO_INCLUDES')) define('PATH_TO_INCLUDES', PATH_TO_APP . DS . 'includes' . DS);
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
    if (!in_array($includeFile,array('.','..','boot.php','functions.php'))) {
        require_once(PATH_TO_INCLUDES.$includeFile);
    }
}

/**
 * Start session
 *
 */
SessionInstance::initsession();

/**
 * Declare classes
 *
 */
$db = new AppDb();
$AppConfig = new AppConfig($db,false);
if(!defined('URL_TO_APP')) define('URL_TO_APP', $AppConfig->getAppUrl());

/**
 * Browse release content and returns associative array with folders name as keys
 * @param $dir
 * @param array $foldertoexclude
 * @param array $filestoexclude
 * @return mixed
 */
function browsecontent($dir,$foldertoexclude=array(),$filestoexclude=array()) {
    $content[$dir] = array();
    if ($handle = opendir($dir)) {
        while (false !== ($file = readdir($handle))) {
            $filename = $dir."/".$file;
            if ($file != "." && $file != ".." && is_file($filename) && !in_array($filename,$filestoexclude)) {
                $content[$dir][] = $filename;
            } else if ($file != "." && $file != ".." && is_dir($dir.$file) && !in_array($dir.$file, $foldertoexclude) ) {
                $content[$dir] = browsecontent($dir.$file,$foldertoexclude,$filestoexclude);
            }
        }
        closedir($handle);
    }
    return $content;
}

/**
 * Patching database tables for version older than 1.2.
 */
function patching() {
    global $db;
    $version = (float)$_SESSION['installed_version'];
    if ($version <= 1.2) {

        // Patch Presentation table
        // Set username of the uploader
        $sql = 'SELECT * FROM ' . $db->tablesname['Presentation'];
        $req = $db->send_query($sql);
        while ($row = mysqli_fetch_assoc($req)) {
            $pub = new Presentation($db, $row['id_pres']);
            $userid = $row['orator'];
            $pub->summary = str_replace('\\', '', htmlspecialchars_decode($row['summary']));
            $pub->authors = str_replace('\\', '', htmlspecialchars_decode($row['authors']));
            $pub->title = str_replace('\\', '', htmlspecialchars_decode($row['title']));

            // If publication's submission date is past, we assume it has already been notified
            if ($pub->up_date < date('Y-m-d H:i:s', strtotime('-2 days', strtotime(date('Y-m-d H:i:s'))))) {
                $pub->notified = 1;
                $pub->update();
            }

            if (empty($row['username']) || $row['username'] == "") {
                $sql = "SELECT username FROM " . $db->tablesname['User'] . " WHERE username='$userid' OR fullname LIKE '%$userid%'";
                $userreq = $db->send_query($sql);
                $data = mysqli_fetch_assoc($userreq);
                if (!empty($data)) {
                    $pub->orator = $data['username'];
                    $pub->username = $data['username'];
                }
            }
            $pub->update();
        }

        // Patch POST table
        // Give ids to posts that do not have one yet (compatibility with older versions)
        $post = new Posts($db);
        $sql = "SELECT postid,date,username FROM " . $db->tablesname['Posts'];
        $req = $db->send_query($sql);
        while ($row = mysqli_fetch_assoc($req)) {
            $date = $row['date'];
            if (empty($row['postid']) || $row['postid'] == "NULL") {
                // Get uploader username
                $userid = $row['username'];
                $sql = "SELECT username FROM " . $db->tablesname['User'] . " WHERE username='$userid' OR fullname='$userid'";
                $userreq = $db->send_query($sql);
                $data = mysqli_fetch_assoc($userreq);

                $username = $data['username'];
                $post->date = $date;
                $postid = $post->makeID();
                $db->updatecontent($db->tablesname['Posts'], array('postid'=>$postid, 'username'=>$username), array('date'=>$date));
            }
        }

        // Patch MEDIA table
        // Write previous uploads to this new table
        $columns = $db->getcolumns($db->tablesname['Presentation']);
        $filenames = $db->getinfo($db->tablesname['Media'], 'filename');
        if (in_array('link', $columns)) {
            $sql = "SELECT up_date,id_pres,link FROM " . $db->tablesname['Presentation'];
            $req = $db->send_query($sql);
            while ($row = mysqli_fetch_assoc($req)) {
                $links = explode(',', $row['link']);
                if (!empty($links)) {
                    foreach ($links as $link) {
                        // Check if uploads does not already exist in the table
                        if (!in_array($link, $filenames)) {
                            // Make a unique id for this link
                            $exploded = explode('.', $link);
                            if (!empty($exploded)) {
                                $id = $exploded[0];
                                $type = $exploded[1];
                                // Add upload to the Media table
                                $content = array(
                                    'date' => $row['up_date'],
                                    'fileid' => $id,
                                    'filename' => $link,
                                    'presid' => $row['id_pres'],
                                    'type' => $type
                                );
                                $db->addcontent($db->tablesname['Media'], $content);
                            }
                        }
                    }
                }
            }
        }
    }

    // Clean session duplicates
    $Session = new Session($db);
    $Session->clean_duplicates();
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Process Installation
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/

if (!empty($_POST['operation'])) {
    $operation = $_POST['operation'];
    set_time_limit(60);

    switch ($operation) {
        case "db_info":
            // STEP 1: Check database credentials provided by the user
            $result = $db->testdb($_POST);
            break;
        case "do_conf":
            // STEP 2: Write database credentials to config.php file
            $result = $AppConfig::createConfig($_POST);
            break;
        case "backup":
            // STEP 3: Do Backups before making any modifications to the db
            include('cronjobs/DbBackup.php');
            $backup = new DbBackup($db);
            $backup->run();
            $result['msg'] = "Backup is complete!";
            $result['status'] = true;
            AppLogger::get_instance(APP_NAME)->info($result['msg']);
            break;
        case "install_db":
            // STEP 4: Configure database

            $op = htmlspecialchars($_POST['op']);
            $op = $op == "new";

            // Tables to create
            $tables_to_create = $db->tablesname;

            // Get default application settings
            $AppConfig = new AppConfig($db, false);
            $version = $AppConfig::version; // New version number
            if ($op === true) {
                $AppConfig->get();
            }
            $_POST['version'] = $version;

            // Create config table
            $AppConfig->setup($op);
            $AppConfig->get();

            if (is_null($AppConfig->pres_type) && empty($AppConfig->pres_type)) {
                $AppConfig->pres_type = $AppConfig::$pres_type_default;
            }

            if (is_null($AppConfig->session_type) && empty($AppConfig->session_type)) {
                $AppConfig->session_type = $AppConfig::$session_type_default;
            }

            // Patching variable type for AppConfig->session_type and AppConfig->pres_type
            if (!is_array($AppConfig->session_type)) {
                $types = explode(',', $AppConfig->session_type);
                $types = array_filter($types, function($value) { return $value !== ''; });
                $_POST['session_type'] = array_unique(array_merge(AppConfig::$session_type_default, $types));
            } elseif (count($AppConfig->session_type) !== count($AppConfig->session_type, COUNT_RECURSIVE)) {
                $_POST['session_type'] = array_unique(array_merge(AppConfig::$session_type_default,
                    array_keys($AppConfig->session_type)));
            }
            if (!is_array($AppConfig->pres_type)) {
                $types = explode(',', $AppConfig->pres_type);
                $types = array_filter($types, function($value) { return $value !== ''; });
                $_POST['pres_type'] = array_unique(array_merge(AppConfig::$pres_type_default, $types));
            } elseif (count($AppConfig->pres_type) !== count($AppConfig->pres_type, COUNT_RECURSIVE)) {
                $_POST['pres_type'] = array_unique(array_merge(AppConfig::$pres_type_default,
                    array_keys($AppConfig->pres_type)));
            }

            $AppConfig->update($_POST);

            // Create users table
            $Users = new Users($db);
            $Users->setup($op);

            // Create Post table
            $Posts = new Posts($db);
            $Posts->setup($op);

            // Create Media table
            $Media = new Uploads($db);
            $Media->setup($op);

            // Create MailManager table
            $MailManager = new MailManager($db);
            $MailManager->setup($op);

            // Create Presentation table
            $Presentations = new Presentations($db);
            $Presentations->setup($op);

            // Create Session table
            $Sessions = new Sessions($db);
            $Sessions->setup($op);

            // Create Plugins table
            $Plugins = new AppPlugins($db);
            $Plugins->setup($op);

            // Create CronJobs table
            $CronJobs = new AppCron($db);
            $CronJobs->setup($op);

            // Page table
            $AppPage = new AppPage($db);
            $AppPage->setup($op);
            $AppPage->getPages();

            // Digest table
            $DigestMaker = new DigestMaker($db);
            $DigestMaker->setup($op);

            // Reminder table
            $DigestMaker = new ReminderMaker($db);
            $DigestMaker->setup($op);

            // Assignment table
            $Assignment = new Assignment($db);
            $Assignment->setup($op);
            $Assignment->check();
            $Assignment->getPresentations();

            // Availability table
            $Availability = new Availability($db);
            $Availability->setup($op);

            // Apply patch if required
            if ($op == false) {
                patching();
            }

            $result['msg'] = "Database installation complete!";
            $result['status'] = true;
            AppLogger::get_instance(APP_NAME, 'Install')->info($result['msg']);
            break;

        case "checkDb":
            // STEP 5:Check consistency between presentations and sessions table
            $session_date = $db->getinfo($db->tablesname['Session'],'date');

            $sql = "SELECT date,jc_time FROM " . $db->tablesname['Presentation'];
            $req = $db->send_query($sql);
            while ($row = mysqli_fetch_assoc($req)) {
                $date = $row['date'];
                $time = $row['jc_time'];
                if (!in_array($date, $session_date)) {
                    $session = new Session($db);
                    if (!$session->make(array('date'=>$date,'time'=>$time))) {
                        $result['status'] = false;
                        $result['msg'] = "<p class='sys_msg warning'>'" . $db->tablesname['Session'] . "' not updated</p>";
                        echo json_encode($result);
                        exit;
                    }
                }
            }
            $result['status'] = true;
            $result['msg'] = "'" . $db->tablesname['Session'] . "' updated";
            break;

        case "settings":
            $AppConfig = new AppConfig($db);
            $result['status'] = $AppConfig->update($_POST);
            break;
        case "admin_creation":
            // Final step: create admin account (for new installation only)
            $user = new User($db);
            $result = $user->make($_POST);
            break;
        default:
            $result = false;
            break;
    }
    echo json_encode($result);
    exit;

}

/**
 * Get page content
 *
 */
if (!empty($_POST['getpagecontent'])) {
    $step = htmlspecialchars($_POST['getpagecontent']);
    $_SESSION['step'] = $step;
    $op = htmlspecialchars($_POST['op']);
    $new_version = AppConfig::version;

    /**
     * Get configuration from previous installation
     * @var  $config
     *
     */
    $config = $db->get_config();
    $version = ($config['version'] !== false) ? $config['version']: false;
    $_SESSION['installed_version'] = $version;

    if ($step == 1) {
        $title = "Welcome to the Journal Club Manager";
        if ($version == false) {
            $operation = "
                <p>Hello</p>
                <p>It seems that <i>Journal Club Manager</i> has never been installed here before.</p>
                <p>We are going to start from scratch... but do not worry, it is all automatic. We will guide you through the installation steps and you will only be required to provide us with some information regarding the hosting environment.</p>
                <p>Click on the 'next' button once you are ready to start.</p>
                <p>Thank you for your interest in <i>Journal Club Manager</i>
                <p style='text-align: center'><input type='button' value='Start' class='start' data-op='new'></p>";
        } else {
            $operation = "
                <p>Hello</p>
                <p>The current version of <i>Journal Club Manager</i> installed here is $version. You are about to install the version $new_version.</p>
                <p>You can choose to either do an entirely new installation by clicking on 'New installation' or to simply update your current version to the new one by clicking on 'Update'.</p>
                <p class='sys_msg warning'>Please, be aware that choosing to perform a new installation will completely erase all the data present in your <i>Journal Club Manager</i> database!!</p>
                <p style='text-align: center'>
                <input type='button' value='New installation'  class='start' data-op='new'>
                <input type='button' value='Update' class='start' data-op='update'>
                </p>";
        }
    } elseif ($step == 2) {
        $config = $db->get_config();
        foreach ($config as $name=>$value) {
            $$name = $value;
        }
        $dbprefix = str_replace('_','',$config['dbprefix']);

        $title = "Step 1: Database configuration";
        $operation = "
			<form action='install.php' method='post'>
                <input type='hidden' name='version' value='" . AppConfig::version. "'>
                <input type='hidden' name='op' value='$op'/>
                <input type='hidden' name='operation' value='db_info'/>
                <div class='form-group'>
    				<input name='host' type='text' value='$host' required autocomplete='on'>
                    <label for='host'>Host Name</label>
                </div>
                <div class='form-group'>
    				<input name='username' type='text' value='$username' required autocomplete='on'>
                    <label for='username'>Username</label>
                </div>
                <div class='form-group'>
				    <input name='passw' type='password' value='$passw'>
                    <label for='passw'>Password</label>
                </div>
                <div class='form-group'>
				    <input name='dbname' type='text' value='$dbname' required autocomplete='on'>
                    <label for='dbname'>DB Name</label>
                </div>
                <div class='form-group'>
				    <input name='dbprefix' type='text' value='$dbprefix' required autocomplete='on'>
                    <label for='dbprefix'>DB Prefix</label>
                </div>
                <div class='submit_btns'>
                    <input type='submit' value='Next' class='proceed'>
                </div>
			</form>
			<div class='feedback'></div>
		";
    } elseif ($step == 3) {
        $db->get_config();
        if ($op == "update") $AppConfig = new AppConfig($db);
        $AppConfig->getAppUrl();

        $title = "Step 2: Application configuration";
        $operation = "
            <form action='install.php' method='post'>
                <input type='hidden' name='version' value='" . AppConfig::version. "'>
                <input type='hidden' name='op' value='$op'/>
                <input type='hidden' name='operation' value='settings'/>
                <input type='hidden' name='site_url' value='{$AppConfig::$site_url}'/>

                <h3>Mailing service</h3>
                <div class='form-group'>
                    <input name='mail_from' type='email' value='{$AppConfig->mail_from}'>
                    <label for='mail_from'>Sender Email address</label>
                </div>
                <div class='form-group'>
                    <input name='mail_from_name' type='text' value='{$AppConfig->mail_from_name}'>
                    <label for='mail_from_name'>Sender name</label>
                </div>
                <div class='form-group'>
                    <input name='mail_host' type='text' value='{$AppConfig->mail_host}'>
                    <label for='mail_host'>Email host</label>
                </div>
                <div class='form-group'>
                    <select name='SMTP_secure'>
                        <option value='{$AppConfig->SMTP_secure}' selected='selected'>{$AppConfig->SMTP_secure}</option>
                        <option value='ssl'>ssl</option>
                        <option value='tls'>tls</option>
                        <option value='none'>none</option>
                     </select>
                     <label for='SMTP_secure'>SMTP access</label>
                 </div>
                <div class='form-group'>
                    <input name='mail_port' type='text' value='{$AppConfig->mail_port}'>
                    <label for='mail_port'>Email port</label>
                </div>
                <div class='form-group'>
                    <input name='mail_username' type='text' value='{$AppConfig->mail_username}'>
                    <label for='mail_username'>Email username</label>
                </div>
                <div class='form-group'>
                    <input name='mail_password' type='password' value='{$AppConfig->mail_password}'>
                    <label for='mail_password'>Email password</label>
                </div>
                <div class='form-group'>
                    <input name='test_email' type='email' value=''>
                    <label for='test_email'>Your email (for testing only)</label>
                </div>

                <div class='submit_btns'>
                    <input type='submit' value='Test settings' class='test_email_settings'> 
                    <input type='submit' value='Next' class='processform'>
                </div>
            </form>
            <div class='feedback'></div>
        ";
    } elseif ($step == 4) {
        $title = "Step 3: Admin account creation";
        $operation = "
            <div class='feedback'></div>
			<form action='install.php'>
                <input type='hidden' name='op' value='$op'/>
                <input type='hidden' name='operation' value='admin_creation'/>
                <input type='hidden' name='status' value='admin'/>

			    <div class='form-group'>
				    <input type='text' name='username' required autocomplete='on'>
                    <label for='username'>UserName</label>
                </div>
                <div class='form-group'>
				    <input type='password' name='password' class='passwordChecker' required>
                    <label for='password'>Password</label>
                </div>
                <div class='form-group'>
				    <input type='password' name='conf_password' required>
                    <label for='conf_password'>Confirm password</label>
                </div>
                <div class='form-group'>
				    <input type='email' name='email' required autocomplete='on'>
                    <label for='admin_email'>Email</label>
                </div>
                <input type='hidden' name='status' value='admin'>
                <div class='submit_btns'>
                    <input type='submit' value='Next' class='admin_creation'>
                </div>
			</form>
		";
    } elseif ($step == 5) {
        $title = "Installation complete!";
        $operation = "
		<p class='sys_msg success'>Congratulations!</p>
		<p class='sys_msg warning'> Now you can delete the 'install.php' file from the root folder of the application</p>
		<p style='text-align: right'><input type='button' value='Finish' class='finish'></p>";
    }

    $result['content'] = "
		<h2>$title</h2>
		<section>
		    <div class='feedback'></div>
			<div class='section_content' id='operation'>$operation</div>
		</section>
	";
    $result['step'] = $step;
    $result['op'] = $op;
    echo json_encode($result);
    exit;
}

?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <META http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <META NAME="description" CONTENT="Journal Club Manager. The easiest way to manage your lab's journal club.">
    <META NAME="keywords" CONTENT="Journal Club Manager">
    <link href='https://fonts.googleapis.com/css?family=Lato&subset=latin,latin-ext' rel='stylesheet' type='text/css'>
    <link type='text/css' rel='stylesheet' href="css/stylesheet.min.css"/>

    <style type="text/css">
        .box {
            background: #FFFFFF;
            width: 60%;
            padding: 20px;
            margin: 2% auto;
            border: 1px solid #eeeeee;
        }

        .progressText_container {
            width: 250px;
            height: auto;
            border-radius: 5px;
            background: rgba(100, 100, 100, 0.68);
            z-index: 95;
            text-align: center;
            padding: 10px;
            margin: 50px auto;
            color: white;
        }

        .progressText_container > div {
            text-align: center;
        }

        .progressText_container > .text {
            text-align: center;
            font-size: 15px;
            font-weight: 500;
            padding: 5px 10px;
        }

        .progressBar_container {
            height: 30px;
            border: 1px solid white;
            border-radius: 5px;
        }

        .progressBar {
            background: rgba(100, 100, 100, 1);
            height: 100%;
            border-radius: 5px;
        }

    </style>

    <!-- JQuery -->
    <script type="text/javascript" src="js/jquery-1.11.1.js"></script>
    <script type="text/javascript" src="js/form.js"></script>
    <script type="text/javascript" src="js/passwordchecker/passwordchecker.min.js"></script>

    <!-- Bunch of jQuery functions -->
    <script type="text/javascript">

        /**
         * Get URL parameters
         */
        function getParams() {
            var url = window.location.href;
            var splitted = url.split("?");
            if(splitted.length === 1) {
                return {};
            }
            var paramList = decodeURIComponent(splitted[1]).split("&");
            var params = {};
            for(var i = 0; i < paramList.length; i++) {
                var paramTuple = paramList[i].split("=");
                params[paramTuple[0]] = paramTuple[1];
            }
            return params;
        }

        /**
         * Get view
         * @param step: view to load
         * @param op: update or make new installation
         */
        function getpagecontent(step_to_load, op) {
            step = step_to_load;
            var stateObj = { page: 'install' };
            var div = $('#pagecontent');

            var callback = function(result) {
                history.pushState(stateObj, 'install', "install.php?step=" + result.step + "&op=" + result.op);
                $('#pagecontent').html(result.content).fadeIn(200);
            };
            var data = {getpagecontent: step, op: op};
            processAjax(div,data,callback,'install.php');
        }

        /**
         * Show loading animation
         */
        function loadingDiv(el) {
            el.css('position', 'relative');
            if (el.find('.loadingDiv').length == 0) {
                el.append("<div class='loadingDiv'></div>");
            }
            el.find('.loadingDiv').css('position', 'absolute').fadeIn();
        }

        /**
         * Remove loading animation at the end of an AJAX request
         * @param el: DOM element in which we show the animation
         */
        function removeLoading(el) {
            el.fadeIn(200);
            el.find('.loadingDiv')
                .fadeOut(1000)
                .remove();
        }

        /**
         * Render progression bar
         */
        function progressbar(el, percent, msg) {
            el.css('position', 'absolute');
            if (el.find('.progressText_container').length == 0) {
                el.append('<div class="progressText_container">' +
                    '<div class="text"></div>' +
                    '<div class="progressBar_container"><div class="progressBar"></div>' +
                    '</div>');
            }
            var TextContainer = el.find('.text');
            TextContainer.html(msg);

            var progressBar = el.find('.progressBar_container');
            var width = progressBar.width();
            progressBar.children('.progressBar').css({'width': percent * width + 'px'});
        }

        /**
         * Update operation
         */
        function modOperation(data,operation) {
            var i;
            // Find and replace `content` if there
            for (i = 0; i < data.length; ++i) {
                if (data[i].name == "operation") {
                    data[i].value = operation;
                    break;
                }
            }
            return data;
        }

        /**
         * Go to next installation step
         **/
        function gonext() {
            step = parseInt(step) + 1;
            getpagecontent(step, op);
            return true;
        }

        /**
         * Application installation
         * @param input
         * @returns {boolean}
         */
        function process(input) {
            step++;
            var form = input.length > 0 ? $(input[0].form) : $();
            var operation = form.find('input[name="operation"]').val();
            op = form.find('input[name="op"]').val();
            var data = form.serializeArray();
            var operationDiv = $('#operation');
            var url = form.attr('action');
            // Check form validity
            if (!checkform(form)) return false;

            loadingDiv(operationDiv);

            var queue = [
                {url: url, operation: 'db_info', data: data, text: 'Connecting to database'},
                {url: url, operation: 'do_conf', data: data, text: 'Creating configuration file'},
                {url: url, operation: 'backup', data: data, text: 'Backup files and database'},
                {url: url, operation: 'install_db', data: data, text: 'Installing application'},
                {url: url, operation: 'checkDb', data: data, text: 'Checking database integrity'}
            ];
            var fb = $('.loadingDiv');
            var lastAction = function() {
                progressbar(fb, 1, 'Installation complete');
                setTimeout(function() {
                    gonext();
                    return true;
                }, 1000);
            };
            recursive_ajax(queue, fb, queue.length, lastAction);
            return true;
        }

        /**
         * Run installation steps using recursive call
         * @param queue
         * @param el
         * @param init_queue_length
         * @param lastAction: function to execute once the queue is empty
         */
        function recursive_ajax(queue, el, init_queue_length, lastAction) {
            var percent = 1 - (queue.length / init_queue_length);
            if (queue.length > 0) {
                var dataToProcess = modOperation(queue[0].data, queue[0].operation);
                jQuery.ajax({
                    url: queue[0].url,
                    type: 'post',
                    data: dataToProcess,
                    async: true,
                    timeout: 20000,
                    beforeSend: function() {
                        progressbar(el, percent, queue[0].text);
                    },
                    success: function(data) {
                        var result = jQuery.parseJSON(data);
                        if (result.status) {
                            progressbar(el, percent, result.msg);
                            queue.shift();
                            recursive_ajax(queue, el, init_queue_length, lastAction);
                        } else {
                            removeLoading(el);
                            progressbar(el, percent, result.msg);
                            return false;
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        removeLoading(el);
                        progressbar(el, percent, textStatus);
                    }
                });
            } else {
                if (lastAction !== undefined) {
                    lastAction();
                }
            }
        }

        var step = 1;
        var op = 'new';

        $(document).ready(function () {

            $('.mainbody')
                .ready(function() {
                    // Get step
                    var params = getParams();
                    var step = (params.step == undefined) ? 1:params.step;
                    var op = (params.op == undefined) ? false:params.op;
                    getpagecontent(step, op);
                })

                /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
                 Installation/Update
                 %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/

                // Go to next installation step
                .on('click', '.start', function(e) {
                    e.preventDefault();
                    var op = $(this).attr('data-op');
                    getpagecontent(2,op);
                })

                // Go to next installation step
                .on('click', '.finish', function(e) {
                    e.preventDefault();
                    window.location = "index.php";
                })

                .on('click', "input[type='submit']", function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (!$(this).hasClass('processform')) {
                        process($(this));
                    } else {
                        return false;
                    }
                })

                .on('click',".processform",function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var form = $(this).length > 0 ? $($(this)[0].form) : $();
                    var op = form.find('input[name="op"]').val();
                    var url = form.attr('action');
                    if (!checkform(form)) {return false;}
                    var callback = function(result) {
                        if (result.status == true) {
                            if (step == 3 && op == 'new') {
                                getpagecontent(4, op);
                            } else if (step == 3 && op !== 'new') {
                                getpagecontent(5, op);
                            } else {
                                gonext();
                            }
                        }
                    };
                    processForm(form,callback,url);
                })

                // Test email host settings
                .on('click', '.test_email_settings', function(e) {
                    e.preventDefault();
                    var input = $(this);
                    var form = input.length > 0 ? $(input[0].form) : $();
                    var data = form.serializeArray();
                    data.push({name: 'test_email_settings', value: true});
                    processAjax(form, data, false, 'php/form.php');
                })

                // Final step: Create admin account
                .on('click','.admin_creation',function(e) {
                    e.preventDefault();
                    var form = $(this).length > 0 ? $($(this)[0].form) : $();
                    var op = form.find('input[name="op"]').val();
                    if (!checkform(form)) {return false;}
                    var callback = function(result) {
                        if (result.status == true) {
                            getpagecontent(5,op);
                        }
                    };
                    processForm(form,callback,'install.php');
                });
        });
    </script>
    <title>Journal Club Manager - Installation</title>
</head>

<body class="mainbody" style="background: #FdFdFd;">

<div id="bodytable">
    <!-- Header section -->
    <div class="box" style='text-align: center; font-size: 1.7em; color: rgba(68,68,68,1); font-weight: 300;'>
        Journal Club Manager - Installation
    </div>

    <!-- Core section -->
    <div class="box" style="min-height: 400px;">
        <div id="pagecontent" style="padding: 20px 0;"></div>
    </div>

    <!-- Footer section -->
    <footer id="footer"  style='width: 60%; padding: 20px; margin: 2% auto;'>
        <div id="colBar"></div>
        <div id="appTitle"><?php echo AppConfig::app_name; ?></div>
        <div id="appVersion">Version <?php echo AppConfig::version; ?></div>
        <div id="sign">
            <div><a href="<?php echo AppConfig::repository; ?>" target='_blank'>Sources</a></div>
            <div><a href="http://www.gnu.org/licenses/agpl-3.0.html" target='_blank'><?php echo AppConfig::license; ?></a></div>
            <div><a href="http://www.florianperdreau.fr" target='_blank'><?php echo AppConfig::copyright . ' ' .  AppConfig::author; ?></a></div>
        </div>
    </footer>
</div>

</body>

</html>
