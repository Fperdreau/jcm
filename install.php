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

require 'includes/boot.php';

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

if (!empty($_POST['inst_admin'])) {
    $encrypted_pw = htmlspecialchars($_POST['password']);
    $username = htmlspecialchars($_POST['username']);
    $email = htmlspecialchars($_POST['email']);

    $user = new User($db);
    if ($user -> make($username,$encrypted_pw,$username,"","",$email,"admin")) {
        $result = "<p id='success'>Admin account created</p>";
    } else {
        $result = "<p id='warning'>We could not create the admin account</p>";
    }
    echo json_encode($result);
	exit;
}

/**
 * Write application settings to config.php
 *
 */
if (!empty($_POST['do_conf'])) {

    $filename = PATH_TO_CONFIG."config.php";
	$result = "";
	if (is_file($filename)) {
		unlink($filename);
	}

    // Make config folder
    $dirname = PATH_TO_CONFIG;
	if (is_dir($dirname) === false) {
		if (!mkdir($dirname,0755)) {
            json_encode("Could not create config directory");
            exit;
        }
	}

    // Make uploads folder
    $dirname = PATH_TO_APP."/uploads/";
    if (is_dir($dirname) === false) {
        if (!mkdir($dirname,0755)) {
            json_encode("Could not create uploads directory");
            exit;
        }
    }

    // Write configuration information to config/config.php
    $config = array();
    foreach ($_POST as $name=>$value) {
        if (!in_array($name,array("do_conf","op"))) {
            $config[] = '"'.$name.'" => "'.$value.'"';
        }
    }
    $config = implode(',',$config);
    $string = '<?php $config = array('.$config.'); ?>';

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

// Do Back ups
if (!empty($_POST['backup'])) {
    $backup_file = backup_db();
    echo json_encode($backup_file);
    exit;
}

// Configure database
if (!empty($_POST['install_db'])) {

    $op = htmlspecialchars($_POST['op']);
    $op = $op == "new";
    $result = "";
    
    // Tables to create
    $tables_to_create = $db->tablesname;

    // First we remove any deprecated tables
    $old_tables = $db->apptables;
    foreach ($old_tables as $old_table) {
        if (!in_array($old_table,$tables_to_create)) {
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
    $table_data = array(
        "id"=>array("INT NOT NULL AUTO_INCREMENT",false),
        "date"=>array("DATETIME",date('Y-m-d h:i:s')),
        "firstname"=>array("CHAR(30)",false),
        "lastname"=>array("CHAR(30)",false),
        "fullname"=>array("CHAR(30)",false),
        "username"=>array("CHAR(30)",false),
        "password"=>array("CHAR(50)",false),
        "position"=>array("CHAR(10)",false),
        "email"=>array("CHAR(100)",false),
        "notification"=>array("INT(1)",1),
        "reminder"=>array("INT(1)",1),
        "nbpres"=>array("INT(3)",0),
        "status"=>array("CHAR(10)",false),
        "hash"=>array("CHAR(32)",false),
        "active"=>array("INT(1)",0),
        "attempt"=>array("INT(1)",0),
        "last_login"=>array("DATETIME","NOT NULL"),
        "primary"=>"id"
        );

    if ($db->makeorupdate($db->tablesname['User'],$table_data,$op)) {
	    $result .= "<p id='success'>'".$db->tablesname['User']."' created</p>";
    } else {
        echo json_encode("<p id='warning'>'".$db->tablesname['User']."' not created</p>");
        exit;
    }

    // Create Post table
    $table_data = array(
        "id"=>array("INT NOT NULL AUTO_INCREMENT",false),
        "postid"=>array("CHAR(30)","NOT NULL"),
        "date"=>array("DATETIME",date('Y-m-d h:i:s')),
        "title"=>array("VARCHAR(255)","NOT NULL"),
        "content"=>array("TEXT(5000)",false),
        "username"=>array("CHAR(30)",false),
        "homepage"=>array("INT(1)",0),
        "primary"=>"id");
    if ($db->makeorupdate($db->tablesname['Posts'],$table_data,$op)) {
	    $result .= "<p id='success'> '".$db->tablesname['Posts']."' created</p>";
    } else {
        echo json_encode("<p id='warning'>'$db->tablesname['Posts']' not created</p>");
        exit;
    }

    // Give ids to posts that do not have one yet (compatibility with older verions)
    $sql = "SELECT postid,date FROM ".$db->tablesname['Posts'];
    $req = $db->send_query($sql);
    while ($row = mysqli_fetch_assoc($req)) {
        if (empty($row['postid'])) {
            $post = new Posts($db);
            $post->date = $row['date'];
            $post->postid = $post->makeID();
            $post->update();
        }
    }

    // Create config table
    $table_data = array(
        "id"=>array("INT NOT NULL AUTO_INCREMENT",false),
        "variable"=>array("CHAR(20)",false),
        "value"=>array("TEXT",false),
        "primary"=>"id");
    if ($db->makeorupdate($db->tablesname['AppConfig'],$table_data,$op) == true) {
    	$result .= "<p id='success'> '".$db->tablesname['AppConfig']."' created</p>";
    } else {
        echo json_encode("<p id='warning'>'".$db->tablesname['AppConfig']."' not created</p>");
        exit;
    }

    // Get default application settings
    if ($op === false) {
        $config = new AppConfig($db,false);
    } else {
        $config = new AppConfig($db);
    }

    if ($config->update($_POST) === true) {
        $result .= "<p id='success'> '".$db->tablesname['AppConfig']."' updated</p>";
    } else {
        echo json_encode("<p id='warning'>'".$db->tablesname['AppConfig']."' not updated</p>");
        exit;
    }

    // Create presentations table
    $table_data = array(
        "id"=>array("INT NOT NULL AUTO_INCREMENT",false),
        "up_date"=>array("DATETIME",false),
        "id_pres"=>array("BIGINT(15)",false),
        "username"=>array("CHAR(30)","NOT NULL"),
        "type"=>array("CHAR(30)",false),
        "date"=>array("DATE",false),
        "jc_time"=>array("CHAR(15)",false),
        "title"=>array("CHAR(150)",false),
        "authors"=>array("CHAR(150)",false),
        "summary"=>array("TEXT(5000)",false),
        "link"=>array("TEXT(500)",false),
        "orator"=>array("CHAR(50)",false),
        "presented"=>array("INT(1)",0),
        "primary"=>"id"
        );

    if ($db->makeorupdate($db->tablesname['Presentation'],$table_data,$op)) {
    	$result .= "<p id='success'>'".$db->tablesname['Presentation']."' created</p>";
    } else {
        echo json_encode("<p id='warning'>'".$db->tablesname['Presentation']."' not created</p>");
        exit;
    }

    // Set username of the uploader
    $sql = 'SELECT id_pres,username,orator FROM '.$db->tablesname['Presentation'];
    $req = $db->send_query($sql);
    while ($row = mysqli_fetch_assoc($req)) {
        if (empty($row['username'])) {
            $pub = new Presentation($db,$row['id_pres']);
            $pub->username = $row['orator'];
            $pub->update();
        }
    }

    // Create Session table
    $table_data = array(
        "id"=>array("INT NOT NULL AUTO_INCREMENT",false),
        "date"=>array("DATE",false),
        "status"=>array("CHAR(10)","FREE"),
        "time"=>array("VARCHAR(200)",false),
        "type"=>array("CHAR(30)","NOT NULL"),
        "presid"=>array("VARCHAR(200)","NOT NULL"),
        "speakers"=>array("VARCHAR(200)","NOT NULL"),
        "chairs"=>array("VARCHAR(200)","NOT NULL"),
        "nbpres"=>array("INT(2)",0),
        "primary"=>"id");
    if ($db->makeorupdate($db->tablesname['Session'],$table_data,$op)) {
        $result .= "<p id='success'> '".$db->tablesname['Session']."' created</p>";
    } else {
        echo json_encode("<p id='warning'>'".$db->tablesname['Session']."' not created</p>");
        exit;
    }

    echo json_encode($result);
    exit;
}

if (!empty($_POST['checkdb'])) {
    // Check consistency between presentations and sessions table
    $result = $Sessions->checkcorrespondence()
        ? "<p id='success'> '" . $db->tablesname['Session'] . "' updated</p>"
        : "<p id='warning'>'" . $db->tablesname['Session'] . "' not updated</p>";
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
    $new_version = $AppConfig->version;

    if ($op == "update") $AppConfig = new AppConfig($db);

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
        if ($version !== false) {
            $config = $db->get_config();
            foreach ($config as $name=>$value) {
                $$name = $value;
            }
            $dbprefix = str_replace('_','',$config['dbprefix']);
        } else {
            $host = "localhost";
            $username = "root";
            $passw = "";
            $dbname = "test";
            $dbprefix = "jcm";
        }

		$title = "Step 1: Database configuration";
		$operation = "
			<form action='' method='post' name='install' id='do_conf'>
                <input type='hidden' name='version' value='$AppConfig->version'>
                <input type='hidden' name='op' value='$op'/>
				<input class='field' type='hidden' name='do_conf' value='true' />
				<label for='host' class='label'>Host Name</label><input class='field' name='host' type='text' value='$host'></br>
				<label for='username' class='label'>Username</label><input class='field' name='username' type='text' value='$username'></br>
				<label for='passw' class='label'>Password</label><input class='field' name='passw' type='password' value='$passw'></br>
				<label for='dbname' class='label'>DB Name</label><input class='field' name='dbname' type='text' value='$dbname'></br>
				<label for='dbprefix' class='label'>DB Prefix</label><input class='field' name='dbprefix' type='text' value='$dbprefix'></br>
				<p style='text-align: right'><input type='submit' name='do_conf' value='Next' id='submit' class='do_conf' data-op='$op'></p>
			</form>
			<div class='feedback'></div>
		";
    } elseif ($step == 3) {
        $AppConfig->site_url = ( (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']).'/';

        $title = "Step 2: Application configuration";
        $operation = "
            <form action='' method='post' name='install' id='install_db'>
                <input type='hidden' name='version' value='$AppConfig->version'>
                <input type='hidden' name='op' value='$op'/>
                <input class='field' type='hidden' name='install_db' value='true' />

                <div style='display: block; padding: 5px; margin-left: 10px; background-color: #CF5151; color: #EEEEEE; font-size: 16px; width: 300px;'> About your Journal Club Manager</div>
                <label for='sitetitle' class='label'>Site title</label><input class='field' name='sitetitle' type='text' value='$AppConfig->sitetitle'></br>
                <label for='site_url' class='label'>Web path to root</label><input class='field' name='site_url' type='text' value='$AppConfig->site_url' size='30'></br>

                <div style='display: block; padding: 5px; margin-left: 10px; background-color: #CF5151; color: #EEEEEE; font-size: 16px; width: 300px;'> About the mailing service</div>
                <label for='mail_from' class='label'>Sender Email address</label><input class='field' name='mail_from' type='text' value='$AppConfig->mail_from'></br>
                <label for='mail_from_name' class='label'>Sender name</label><input class='field' name='mail_from_name' type='text' value='$AppConfig->mail_from_name'></br>
                <label for='mail_host' class='label'>Email host</label><input class='field' name='mail_host' type='text' value='$AppConfig->mail_host'></br>
                <label for='SMTP_secure' class='label'>SMTP access</label>
                    <select name='SMTP_secure'>
                        <option value='$AppConfig->SMTP_secure' selected='selected'>$AppConfig->SMTP_secure</option>
                        <option value='ssl'>ssl</option>
                        <option value='tls'>tls</option>
                        <option value='none'>none</option>
                     </select><br>
                <label for='mail_port' class='label'>Email port</label><input class='field' name='mail_port' type='text' value='$AppConfig->mail_port'></br>
                <label for='mail_username' class='label'>Email username</label><input class='field' name='mail_username' type='text' value='$AppConfig->mail_username'></br>
                <label for='mail_password' class='label'>Email password</label><input class='field' name='mail_password' type='password' value='$AppConfig->mail_password'></br>

                <p style='text-align: right'><input type='submit' name='install_db' value='Next' id='submit' class='install_db' data-op='$op'></p>
            </form>
            <div class='feedback'></div>
        ";
	} elseif ($step == 4) {
		$title = "Step 2: Admin account creation";
		$operation = "
            <div class='feedback'></div>
			<form method='post' id='admin_creation'>
				<label for='admin_username' class='label'>UserName : </label><input class='field' id='admin_username' type='text' name='username'><br/>
				<label for='admin_password' class='label'>Password : </label><input class='field' id='admin_password' type='password' name='password'><br/>
				<label for='admin_confpassword' class='label'>Confirm password: </label><input class='field' id='admin_confpassword' type='password' name='admin_confpassword'><br/>
				<label for='admin_email' class='label'>Email: </label><input class='field' type='text' name='email' id='admin_email'><br/>
				<input type='hidden' name='inst_admin' value='true'>
				<p style='text-align: right;'><input type='submit' name='submit' value='Next' id='submit' class='admin_creation' data-op='$op'></p>
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
	<div id='content'>
		<span id='pagename'>Installation</span>
		<div class='section_header' style='width: 300px'>$title</div>
		<div class='section_content'>
			<div id='operation'>$operation</div>
		</div>
	</div>";

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
        <link href='http://fonts.googleapis.com/css?family=Lato&subset=latin,latin-ext' rel='stylesheet' type='text/css'>
        <link type='text/css' rel='stylesheet' href="css/stylesheet.css"/>

        <!-- JQuery -->
        <script type="text/javascript" src="js/jquery-1.11.1.js"></script>

        <!-- Bunch of jQuery functions -->
        <script type="text/javascript">
            // Spin animation when a page is loading
            var $loading = $('#loading').hide();

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
                jQuery.ajax({
                    url: 'install.php',
                    type: 'POST',
                    async: true,
                    data: {
                        getpagecontent: step,
                        op: op},
                    beforeSend: function() {
                        $('#loading').show();
                    },
                    complete: function() {
                        $('#loading').hide();
                    },
                    success: function(data){
                        var result = jQuery.parseJSON(data);
                        $('#loading').hide();
                        $('#pagecontent')
                            .html('<div>'+result+'</div>')
                            .fadeIn('slow');
                    }
                });
            };

            // Do a backup of the db before making any modification
            var dobackup = function() {
                $('#operation')
                    .hide()
                    .html('<p id="status">Backup previous database</p>')
                    .fadeIn(200);
                // Make configuration file
                jQuery.ajax({
                    url: 'install.php',
                    type: 'POST',
                    async: true,
                    data: {backup: true},
                    beforeSend: function() {
                        $('#loading').show();
                    },
                    complete: function() {
                        $('#loading').hide();
                    },
                    success: function(data){
                        var result = jQuery.parseJSON(data);
                        var html = "<p id='success'>Backup created: "+result+"</p>";
                        $('#operation')
                            .hide()
                            .append(html)
                            .fadeIn(200);
                    }
                });
                return false;
            };

            // Check consistency between session/presentation tables
            var checkdb = function() {
                $('#operation')
                    .hide()
                    .html('<p id="status">Check consistency between session/presentation tables</p>')
                    .fadeIn(200);
                jQuery.ajax({
                    url: 'install.php',
                    type: 'POST',
                    async: false,
                    data: {checkdb: true},
                    success: function(data){
                        var result = jQuery.parseJSON(data);
                        var html = "<p id='success'>result</p>";
                        $('#operation')
                            .hide()
                            .append(html)
                            .fadeIn(200);
                    }
                });
                return false;
            };

            $(document).ready(function () {
                $('.mainbody')
                    .ready(function() {
                        // Get step
                        getpagecontent(1,false);
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

                    // Launch database setup
                    .on('click','.do_conf',function(e) {
                        e.preventDefault();
                        var op = $(this).attr('data-op');
                        var data = $("#do_conf").serializeArray();

                        // Backup the database before updating it
                        if (op == "update") {
                            dobackup();
                        }

                        // Make configuration file
                        jQuery.ajax({
                            url: 'install.php',
                            type: 'POST',
                            async: false,
                            data: data,
                            beforeSend: function() {
                                $('#loading').show();
                            },
                            complete: function() {
                                $('#loading').hide();
                            },
                            success: function(data){
                                var result = jQuery.parseJSON(data);
                                var html;
                                if (result === true) {
                                    html = "<p id='success'>Configuration file created/updated</p>";
                                } else {
                                    html = "<p id='warning'>"+result+"</p>";
                                }
                                $('#operation')
                                    .hide()
                                    .append(html)
                                    .fadeIn(200);
                                // Go to the next step
                                setTimeout(function(){
                                    getpagecontent(3,op);
                                },2000);
                            }
                        });
                    })

                    // Launch database setup
                    .on('click','.install_db',function(e) {
                        e.preventDefault();
                        var op = $(this).attr('data-op');
                        var data = $("#install_db").serializeArray();

                        // Configure database
                        jQuery.ajax({
                            url: 'install.php',
                            type: 'POST',
                            async: true,
                            data: data,
                            beforeSend: function() {
                                $('#loading').show();
                            },
                            complete: function() {
                                $('#loading').hide();
                            },
                            success: function(data){
                                var result = jQuery.parseJSON(data);
                                $('#operation')
                                    .hide()
                                    .html(result)
                                    .fadeIn(200);

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

                    // Create admin account
                    .on('click','.admin_creation',function(e) {
                        e.preventDefault();
                        var op = $(this).attr('data-op');
                        var username = $("input#admin_username").val();
                        var password = $("input#admin_password").val();
                        var conf_password = $("input#admin_confpassword").val();
                        var email = $("input#admin_email").val();

                        if (username == "") {
                            showfeedback('<p id="warning">This field is required</p>','.feedback');
                            $("input#admin_username").focus();
                            return false;
                        }

                        if (password == "") {
                            showfeedback('<p id="warning">This field is required</p>','.feedback');
                            $("input#admin_password").focus();
                            return false;
                        }

                        if (conf_password == "") {
                            showfeedback('<p id="warning">This field is required</p>','.feedback');
                            $("input#admin_confpassword").focus();
                            return false;
                        }

                        if (conf_password != password) {
                            showfeedback('<p id="warning">Password must match</p>');
                            $("input#admin_confpassword").focus();
                            return false;
                        }

                        if (email == "") {
                            showfeedback('<p id="warning">This field is required</p>','.feedback');
                            $("input#admin_email").focus();
                            return false;
                        }

                        if (!checkemail(email)) {
                            showfeedback('<p id="warning">Oops, this is an invalid email</p>','.feedback');
                            $("input#admin_email").focus();
                            return false;
                        }

                        jQuery.ajax({
                            url: 'install.php',
                            type: 'POST',
                            async: true,
                            data: {
                                inst_admin: true,
                                username: username,
                                password: password,
                                email: email,
                                conf_password: conf_password},
                            beforeSend: function() {
                                $('#loading').show();
                            },
                            complete: function() {
                                $('#loading').hide();
                            },
                            success: function(data){
                                var result = jQuery.parseJSON(data);
                                showfeedback(result);
                                getpagecontent(5,op);
                            }
                        });
                    });
            });
        </script>
        <title>Journal Club Manager - Installation</title>
    </head>

    <body class="mainbody">
        <!-- Header section -->
        <div id="mainheader">
            <!-- Header section -->
            <div class="header">
                <div class='header_container'>
                    <div id='title'>
                        <span id='sitetitle'>Journal Club Manager</span>
                    </div>
                </div>
            </div>

            <!-- Menu section -->
            <div class='menu'>
                <div class='menu-container'>
                </div>
            </div>
        </div>

        <!-- Core section -->
        <div id="core">
        	<div id="loading"></div>
        	<div id="pagecontent">
        	</div>
        </div>

        <!-- Footer section -->
        <div id="footer">
            <span id="sign"><?php echo "<a href='$AppConfig->repository' target='_blank'>$AppConfig->app_name $AppConfig->version</a>
             | <a href='http://www.gnu.org/licenses/agpl-3.0.html' target='_blank'>GNU AGPL v3 </a>
             | <a href='http://www.florianperdreau.fr' target='_blank'>&copy2014 $AppConfig->author</a>" ?></span>
        </div>
    </body>
</html>
