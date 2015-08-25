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
    ini_set('display_errors', '1');
}

/**
 * Define paths
 */
if(!defined('APP_NAME')) define('APP_NAME', basename(__DIR__));
if(!defined('PATH_TO_APP')) define('PATH_TO_APP', dirname(__FILE__));
if(!defined('PATH_TO_IMG')) define('PATH_TO_IMG', PATH_TO_APP.'/images/');
if(!defined('PATH_TO_INCLUDES')) define('PATH_TO_INCLUDES', PATH_TO_APP.'/includes/');
if(!defined('PATH_TO_PHP')) define('PATH_TO_PHP', PATH_TO_APP.'/php/');
if(!defined('PATH_TO_PAGES')) define('PATH_TO_PAGES', PATH_TO_APP.'/pages/');
if(!defined('PATH_TO_CONFIG')) define('PATH_TO_CONFIG', PATH_TO_APP.'/config/');
if(!defined('PATH_TO_LIBS')) define('PATH_TO_LIBS', PATH_TO_APP.'/libs/');

/**
 * Includes required files (classes)
 */
include_once(PATH_TO_INCLUDES.'AppDb.php');
include_once(PATH_TO_INCLUDES.'AppTable.php');
$includeList = scandir(PATH_TO_INCLUDES);
foreach ($includeList as $includeFile) {
    if (!in_array($includeFile,array('.','..','boot.php'))) {
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
 * Check release integrity (presence of folders/files and file content)
 * @return bool
 */
function check_release_integrity() {
    $releasefolder = PATH_TO_APP.'/jcm/';
    $releasecontentfile = PATH_TO_APP.'/jcm/content.json';
    if (is_dir($releasefolder)) {
        require $releasecontentfile;
        $release_content = json_decode($content);
        $foldertoexclude = array('config','uploads','dev');
        $copied_release_content = browsecontent($releasefolder,$foldertoexclude);
        $diff = array_diff_assoc($release_content,$copied_release_content);
        $result['status'] = empty($diff) ? true:false;
        $result['msg'] = "";
    } else {
        $result['status'] = false;
        $result['msg'] = "<p id='warning'>The jcm folder containing the new release files should be placed at the root of your website</p>";
    }
    return json_encode($result);
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Process Installation
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/

if (!empty($_POST['operation'])) {
    $operation = $_POST['operation'];

    // STEP 1: Check database credentials provided by the user
    if ($operation == "db_info") {
        $result = $db->testdb($_POST);
        echo json_encode($result);
        exit;
    }

    // STEP 2: Write database credentials to config.php file
    if ($operation == "do_conf") {

        $filename = PATH_TO_CONFIG . "config.php";
        $result = "";
        if (is_file($filename)) {
            unlink($filename);
        }

        // Make config folder
        $dirname = PATH_TO_CONFIG;
        if (is_dir($dirname) === false) {
            if (!mkdir($dirname, 0755)) {
                json_encode("Could not create config directory");
                exit;
            }
        }

        // Make uploads folder
        $dirname = PATH_TO_APP . "/uploads/";
        if (is_dir($dirname) === false) {
            if (!mkdir($dirname, 0755)) {
                json_encode("Could not create uploads directory");
                exit;
            }
        }

        // Write configuration information to config/config.php
        $fields_to_write = array("version", "host", "username", "passw", "dbname", "dbprefix");
        $config = array();
        foreach ($_POST as $name => $value) {
            if (in_array($name, $fields_to_write)) {
                $config[] = '"' . $name . '" => "' . $value . '"';
            }
        }
        $config = implode(',', $config);
        $string = '<?php $config = array(' . $config . '); ?>';

        // Create new config file
        if ($fp = fopen($filename, "w+")) {
            if (fwrite($fp, $string) == true) {
                fclose($fp);
            } else {
                $result = "Impossible to write";
                echo json_encode($result);
                exit;
            }
        } else {
            $result = "Impossible to open the file";
            echo json_encode($result);
            exit;
        }
        echo json_encode(true);
        exit;
    }

    // STEP 3: Do Backups before making any modifications to the db
    if ($operation == "backup") {
        $backup_file = backup_db();
        echo json_encode($backup_file);
        exit;
    }

    // STEP 4: Configure database
    if ($operation == "install_db") {

        $op = htmlspecialchars($_POST['op']);
        $op = $op == "new";
        $result = "";

        // Tables to create
        $tables_to_create = $db->tablesname;

        // First we remove any deprecated tables
        $old_tables = $db->getapptables();
        foreach ($old_tables as $old_table) {
            if (!in_array($old_table, $tables_to_create)) {
                if ($db->deletetable($old_table) == true) {
                    $result .= "<p id='success'>$old_table has been deleted because we do not longer need it</p>";
                } else {
                    $result .= "<p id='warning'>We could not remove $old_table although we do not longer need it</p>";
                    echo json_encode($result);
                    exit;
                }
            }
        }

        // Create users table
        $Users = new Users($db);
        $Users->setup($op);

        // Create Post table
        $Posts = new Posts($db);
        $Posts->setup($op);

        // Give ids to posts that do not have one yet (compatibility with older verions)
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

        // Create config table
        // Get default application settings
        if ($op === false) {
            $AppConfig = new AppConfig($db, false);
            $version = $AppConfig->version;
        } else {
            $AppConfig = new AppConfig($db,false);
            $version = $AppConfig->version;
            $AppConfig->get();
        }
        $_POST['version'] = $version;
        $AppConfig->setup($op);
        if ($AppConfig->update($_POST) === true) {
            $result .= "<p id='success'> '" . $db->tablesname['AppConfig'] . "' updated</p>";
        } else {
            echo json_encode("<p id='warning'>'" . $db->tablesname['AppConfig'] . "' not updated</p>");
            exit;
        }

        // Create Media table
        $Media = new Uploads($db);
        $Media->setup($op);

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

        // Create Presentation table
        $Presentations = new Presentations($db);
        $Presentations->setup($op);

        // Set username of the uploader
        $sql = 'SELECT id_pres,username,orator,summary,authors,title,notified FROM ' . $db->tablesname['Presentation'];
        $req = $db->send_query($sql);
        while ($row = mysqli_fetch_assoc($req)) {
            $pub = new Presentation($db, $row['id_pres']);
            $userid = $row['orator'];
            $pub->summary = str_replace('\\', '', htmlspecialchars($row['summary']));
            $pub->authors = str_replace('\\', '', htmlspecialchars($row['authors']));
            $pub->title = str_replace('\\', '', htmlspecialchars($row['title']));

            // If publication's submission date is past, we assume it has already been notified
            if ($pub->up_date < date('Y-m-d H:i:s', strtotime('-2 days', strtotime(date('Y-m-d H:i:s'))))) {
                $pub->notified = 1;
                $pub->update();
            }

            if (empty($row['username']) || $row['username'] == "") {
                $sql = "SELECT username FROM " . $db->tablesname['User'] . " WHERE username='$userid' OR fullname='$userid'";
                $userreq = $db->send_query($sql);
                $data = mysqli_fetch_assoc($userreq);
                if (!empty($data)) {
                    $pub->orator = $data['username'];
                    $pub->username = $data['username'];
                }
            }
            $pub->update();
        }

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

        echo json_encode($result);
        exit;
    }

    // STEP 5:Check consistency between presentations and sessions table
    if ($operation == "checkdb") {
        $session_date = $db->getinfo($db->tablesname['Session'],'date');

        $sql = "SELECT date,jc_time FROM " . $db->tablesname['Presentation'];
        $req = $db->send_query($sql);
        while ($row = mysqli_fetch_assoc($req)) {
            $date = $row['date'];
            $time = $row['time'];
            if (!in_array($date,$session_date)) {
                $session = new Session($db);
                if (!$session->make(array('date'=>$date,'time'=>$time))) {
                    $result = "<p id='warning'>'" . $db->tablesname['Session'] . "' not updated</p>";
                    echo json_encode($result);
                    exit;
                }
            }
        }
        $result = "<p id='success'> '" . $db->tablesname['Session'] . "' updated</p>";
        echo json_encode($result);
        exit;
    }

    // Final step: create admin account (for new installation only)
    if ($operation == 'inst_admin') {
        $user = new User($db);
        $result = $user->make($_POST);
        echo json_encode($result);
        exit;
    }
}

/**
 * Get page content
 *
 */
if (!empty($_POST['getpagecontent'])) {
    $step = htmlspecialchars($_POST['getpagecontent']);
    $_SESSION['step'] = $step;
    $op = htmlspecialchars($_POST['op']);
    $new_version = $AppConfig->version;

    /**
     * Get configuration from previous installation
     * @var  $config
     *
     */
    $config = $db->get_config();
    $version = ($config['version'] !== false) ? $config['version']: false;

    if ($step == 1) {
        $title = "Welcome to the Journal Club Manager";
        if ($version == false) {
            $operation = "
                <p>Hello</p>
                <p>It seems that <i>Journal Club Manager</i> has never been installed here before.</p>
                <p>We are going to start from scratch... but do not worry, it is all automatic. We will guide you through the installation steps and you will only be required to provide us with some information regarding the hosting environment.</p>
                <p>Click on the 'next' button once you are ready to start.</p>
                <p>Thank you for your interest in <i>Journal Club Manager</i>
                <p style='text-align: center'><input type='button' id='submit' value='Start' class='start' data-op='new'></p>";
        } else {
            $operation = "
                <p>Hello</p>
                <p>The current version of <i>Journal Club Manager</i> installed here is $version. You are about to install the version $new_version.</p>
                <p>You can choose to either do an entirely new installation by clicking on 'New installation' or to simply update your current version to the new one by clicking on 'Update'.</p>
                <p id='warning'>Please, be aware that choosing to perform a new installation will completely erase all the data present in your <i>Journal Club Manager</i> database!!</p>
                <p style='text-align: center'>
                <input type='button' id='submit' value='New installation'  class='start' data-op='new'>
                <input type='button' id='submit' value='Update'  class='start' data-op='update'>
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
			<form action='' method='post' name='install' id='db_info'>
                <input type='hidden' name='version' value='$AppConfig->version'>
                <input type='hidden' name='op' value='$op'/>
				<input type='hidden' name='db_info' value='true' />
                <div class='formcontrol'>
    				<label for='host'>Host Name</label>
    				<input name='host' type='text' value='$host' required autocomplete='on'>
                </div>
                <div class='formcontrol'>
    				<label for='username'>Username</label>
    				<input name='username' type='text' value='$username' required autocomplete='on'>
                </div>
                <div class='formcontrol'>
				    <label for='passw'>Password</label>
				    <input name='passw' type='password' value='$passw' required>
                </div>
                <div class='formcontrol'>
				    <label for='dbname'>DB Name</label>
				    <input name='dbname' type='text' value='$dbname' required autocomplete='on'>
                </div>
                <div class='formcontrol'>
				    <label for='dbprefix'>DB Prefix</label>
				    <input name='dbprefix' type='text' value='$dbprefix' required autocomplete='on'>
                </div>
				<p style='text-align: right'><input type='submit' name='db_info' value='Next' id='submit' class='db_info' data-op='$op'></p>
			</form>
			<div class='feedback'></div>
		";
    } elseif ($step == 3) {
        $db->get_config();
        if ($op == "update") $AppConfig = new AppConfig($db);
        $AppConfig->site_url = ( (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']).'/';

        $title = "Step 2: Application configuration";
        $operation = "
            <form action='' method='post' name='install' id='install_db'>
                <input type='hidden' name='version' value='$AppConfig->version'>
                <input type='hidden' name='op' value='$op'/>
                <input type='hidden' name='install_db' value='true' />
                <input type='hidden' name='site_url' value='$AppConfig->site_url'/>
                <h3>Journal Club Manager - Website</h3>
                <div class='formcontrol'>
                    <label for='sitetitle'>Site title</label>
                    <input name='sitetitle' type='text' value='$AppConfig->sitetitle' required autocomplete='on'>
                </div>

                <h3>Journal Club Manager - Mailing service</h3>
                <div class='formcontrol'>
                    <label for='mail_from'>Sender Email address</label>
                    <input name='mail_from' type='email' value='$AppConfig->mail_from'>
                </div>
                <div class='formcontrol'>
                    <label for='mail_from_name'>Sender name</label>
                    <input name='mail_from_name' type='text' value='$AppConfig->mail_from_name'>
                </div>
                <div class='formcontrol'>
                    <label for='mail_host'>Email host</label>
                    <input name='mail_host' type='text' value='$AppConfig->mail_host'>
                </div>
                <div class='formcontrol'>
                    <label for='SMTP_secure'>SMTP access</label>
                    <select name='SMTP_secure'>
                        <option value='$AppConfig->SMTP_secure' selected='selected'>$AppConfig->SMTP_secure</option>
                        <option value='ssl'>ssl</option>
                        <option value='tls'>tls</option>
                        <option value='none'>none</option>
                     </select>
                 </div>
                <div class='formcontrol'>
                    <label for='mail_port'>Email port</label>
                    <input name='mail_port' type='text' value='$AppConfig->mail_port'>
                </div>
                <div class='formcontrol'>
                    <label for='mail_username'>Email username</label>
                    <input name='mail_username' type='text' value='$AppConfig->mail_username'>
                </div>
                <div class='formcontrol'>
                    <label for='mail_password'>Email password</label>
                    <input name='mail_password' type='password' value='$AppConfig->mail_password'>
                </div>

                <p style='text-align: right'><input type='submit' name='install_db' value='Next' id='submit' class='install_db' data-op='$op'></p>
            </form>
            <div class='feedback'></div>
        ";
    } elseif ($step == 4) {
        $title = "Step 3: Admin account creation";
        $operation = "
            <div class='feedback'></div>
			<form id='admin_creation'>
			    <div class='formcontrol'>
				    <label for='username'>UserName</label>
				    <input type='text' name='username' required autocomplete='on'>
                </div>
                <div class='formcontrol'>
				    <label for='password'>Password</label>
				    <input type='password' name='password' required>
                </div>
                <div class='formcontrol'>
				    <label for='conf_password'>Confirm password</label>
				    <input type='password' name='conf_password' required>
                </div>
                <div class='formcontrol'>
				    <label for='admin_email'>Email</label>
				    <input type='email' name='email' required autocomplete='on'>
                </div>
                <input type='hidden' name='status' value='admin'>
				<input type='hidden' name='operation' value='inst_admin'>
				<p style='text-align: right;'><input type='submit' value='Next' class='admin_creation' data-op='$op'></p>
			</form>
		";
    } elseif ($step == 5) {
        $title = "Installation complete!";
        $operation = "
		<p id='success'>Congratulations!</p>
		<p id='warning'> Now you can delete the 'install.php' file from the root folder of the application</p>
		<p style='text-align: right'><input type='submit' name='submit' value='Finish' id='submit' class='finish'></p>";
    }

    $result = "
		<h2>$title</h2>
		<section>
		    <div class='feedback'></div>
			<div id='operation'>$operation</div>
		</section>
	";

    echo json_encode($result);
    exit;
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
<head>
    <META http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <META NAME="description" CONTENT="Journal Club Manager. The easiest way to manage your lab's journal club.">
    <META NAME="keywords" CONTENT="Journal Club Manager">
    <link href='https://fonts.googleapis.com/css?family=Lato&subset=latin,latin-ext' rel='stylesheet' type='text/css'>
    <link type='text/css' rel='stylesheet' href="css/stylesheet.css"/>

    <style type="text/css">
        .box {
            background: #FFFFFF;
            width: 60%;
            padding: 20px;
            margin: 2% auto;
            border: 1px solid #eeeeee;
        }
    </style>

    <!-- JQuery -->
    <script type="text/javascript" src="js/jquery-1.11.1.js"></script>
    <script type="text/javascript" src="js/form.js"></script>

    <!-- Bunch of jQuery functions -->
    <script type="text/javascript">
        // Show loading animation
        function loadingDiv(divId) {
            $(""+divId)
                .fadeOut(200)
                .append("<div class='loadingDiv' style='width: 100%; height: 100%;'></div>")
                .show();
        }

        // Remove loading animation
        function removeLoading(divId) {
            $(''+divId).find('.loadingDiv').hide();
        }

        // Check email validity
        function checkemail(email) {
            var pattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);
            return pattern.test(email);
        }

        //Show feedback
        var showfeedback = function(message,selector) {
            if (typeof selector == "undefined") {
                selector = ".feedback";
            }
            $(""+selector)
                .show()
                .html(message)
                .fadeOut(5000);
        };

        // Get page content
        var getpagecontent = function(step,op) {
            var stateObj = { page: 'install' };

            jQuery.ajax({
                url: 'install.php',
                type: 'POST',
                async: true,
                data: {
                    getpagecontent: step,
                    op: op},
                beforeSend: function() {
                    loadingDiv('#pagecontent');
                },
                complete: function() {
                    removeLoading('#pagecontent');
                },
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    history.pushState(stateObj, 'install', 'install.php?step='+step+'&op='+op);
                    $('#pagecontent')
                        .fadeOut(100)
                        .html('<div>'+result+'</div>')
                        .fadeIn(200);
                }
            });
        };

        var makeconfigfile = function(data) {
            data = modifyopeation(data,"do_conf");
            $('#operation').append("<p id='status'>Creation of configuration file</p>");
            // Make configuration file
            jQuery.ajax({
                url: 'install.php',
                type: 'POST',
                async: false,
                data: data,
                beforeSend: function() {
                    loadingDiv('#pagecontent');
                },
                complete: function() {
                    removeLoading('#pagecontent');
                },
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    var html;
                    if (result === true) {
                        html = "<p id='success'>Configuration file created/updated</p>";
                    } else {
                        html = "<p id='warning'>"+result+"</p>";
                    }
                    $('#operation').append(html);
                }
            });
        };

        // Do a backup of the db before making any modification
        var dobackup = function() {
            $('#operation').append('<p id="status">Backup previous database</p>');
            // Make configuration file
            jQuery.ajax({
                url: 'install.php',
                type: 'POST',
                async: true,
                data: {operation: "backup"},
                beforeSend: function() {
                    loadingDiv('#pagecontent');
                },
                complete: function() {
                    removeLoading('#pagecontent');
                },
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    var html = "<p id='success'>Backup created: "+result+"</p>";
                    $('#operation')
                        .empty()
                        .html(html)
                        .fadeIn(200);
                }
            });
            return false;
        };

        // Check consistency between session/presentation tables
        var checkdb = function() {
            $('#operation').append('<p id="status">Check consistency between session/presentation tables</p>');

            jQuery.ajax({
                url: 'install.php',
                type: 'POST',
                async: true,
                data: {operation: "checkdb"},
                beforeSend: function() {
                    loadingDiv('#pagecontent');
                },
                complete: function() {
                    removeLoading('#pagecontent');
                },
                success: function(data){
                    var result = jQuery.parseJSON(data);
                    var html = "<p id='success'>"+result+"</p>";
                    $('#operation')
                        .append(html)
                        .fadeIn(200);
                }
            });
            return false;
        };

        function modifyopeation(data,operation) {
            var index;
            // Find and replace `content` if there
            for (index = 0; index < data.length; ++index) {
                if (data[index].name == "operation") {
                    data[index].value = operation;
                    break;
                }
            }
            return data;
        }

        // Get url params ($_GET)
        var getParams = function() {
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
        };

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
                .on('click', '.next', function(e) {
                    var op = $(this).attr('data-op');
                    e.preventDefault();
                    getpagecontent(2,op);
                })

                // Go to next installation step
                .on('click', '.finish', function(e) {
                    e.preventDefault();
                    window.location = "index.php";
                })

                // Step 1->3: Launch database setup
                .on('click','.db_info',function(e) {
                    e.preventDefault();
                    var op = $(this).attr('data-op');
                    var formdata = $("#db_info").serializeArray();
                    formdata.push({name:"operation",value:"db_info"});

                    jQuery.ajax({
                        url: 'install.php',
                        type: 'POST',
                        async: true,
                        data: formdata,
                        success: function(data){
                            var result = jQuery.parseJSON(data);
                            if (result.status == false) {
                                showfeedback(result.msg);
                            } else {
                                showfeedback(result.msg);
                                $('#operation').empty();

                                // Make config.php file
                                makeconfigfile(formdata);

                                // Go to the next step
                                setTimeout(function(){
                                    getpagecontent(3,op);
                                },2000);
                            }
                        }
                    });
                })

                // Launch database setup
                .on('click','.install_db',function(e) {
                    e.preventDefault();
                    var op = $(this).attr('data-op');
                    var formdata = $("#install_db").serializeArray();
                    formdata.push({name:"operation",value:"install_db"});
                    $('#operation').empty();

                    // First we backup the db before making any modifications
                    dobackup();

                    // Next, configure database
                    jQuery.ajax({
                        url: 'install.php',
                        type: 'POST',
                        async: true,
                        data: formdata,
                        beforeSend: function() {
                            loadingDiv('#pagecontent');
                        },
                        complete: function() {
                            removeLoading('#pagecontent');
                        },
                        success: function(data){
                            var result = jQuery.parseJSON(data);
                            $('#operation').append(result);

                            // Check database consistency
                            checkdb();

                            // Go to next step
                            setTimeout(function() {
                                if (op !== "update") {
                                    getpagecontent(4,op);
                                } else {
                                    getpagecontent(5,op);
                                }
                            },2000);
                        }
                    });
                })

                // Final step: Create admin account
                .on('click','.admin_creation',function(e) {
                    e.preventDefault();
                    var op = $(this).attr('data-op');
                    var form = $(this).length > 0 ? $($(this)[0].form) : $();
                    if (!checkform(form)) {return false;}
                    var data = form.serialize();
                    var callback = function(result) {
                        if (result.status == true) {
                            getpagecontent(5,op);
                        }
                    };
                    jQuery.ajax({
                        url: 'install.php',
                        type: 'POST',
                        data: data,
                        beforeSend: function() {
                            loadingDiv('#admin_creation');
                        },
                        complete: function() {
                            removeLoading('#admin_creation');
                        },
                        success: function(data){
                            validsubmitform(form,data,callback);
                        }
                    });
                });
        });
    </script>
    <title>Journal Club Manager - Installation</title>
</head>

<body class="mainbody" style="background: #FdFdFd;">

<div id="bodytable">
    <!-- Header section -->
    <div class="box" style='text-align: center; font-size: 1.7em; color: #336699; font-weight: 300;'>
        Journal Club Manager - Installation
    </div>

    <!-- Core section -->
    <div class="box" style="min-height: 300px;">
        <div id="pagecontent"></div>
    </div>

    <!-- Footer section -->
    <div class="box" style="text-align: center">
        <span id="sign"><?php echo "<a href='$AppConfig->repository' target='_blank'>$AppConfig->app_name $AppConfig->version</a>
             | <a href='http://www.gnu.org/licenses/agpl-3.0.html' target='_blank'>GNU AGPL v3 </a>
             | <a href='http://www.florianperdreau.fr' target='_blank'>&copy2014 $AppConfig->author</a>" ?></span>
    </div>
</div>

</body>

</html>
