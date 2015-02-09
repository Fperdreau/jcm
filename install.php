
<?php
/*
Copyright © 2014, F. Perdreau, Radboud University Nijmegen
=======
This file is part of RankMyDrawings.

RankMyDrawings is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

RankMyDrawings is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with RankMyDrawings.  If not, see <http://www.gnu.org/licenses/>.

*/

session_set_cookie_params(3600);
session_start();
$_SESSION['app_name'] = basename(__DIR__);
$_SESSION['path_to_app'] = dirname(__FILE__)."/";
$_SESSION['path_to_img'] = $_SESSION['path_to_app'].'images/';
$_SESSION['path_to_includes'] = $_SESSION['path_to_app']."includes/";
$_SESSION['path_to_html'] = $_SESSION['path_to_app']."php/";
$_SESSION['path_to_pages'] = $_SESSION['path_to_app']."pages/";
date_default_timezone_set('Europe/Paris');

// Includes required files (classes)
require_once($_SESSION['path_to_includes'].'includes.php');
$config = new site_config();

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Process Installation
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/

if (!empty($_POST['inst_admin'])) {
    $pass_crypte = htmlspecialchars($_POST['password']);
    $username = htmlspecialchars($_POST['username']);
    $email = htmlspecialchars($_POST['email']);

    $user = new users();
    $adduser = $user -> create_user($username,$pass_crypte,"","","",$email,"admin");
    $result = "<p id='success'>Admin account created</p>";

    echo json_encode($result);
	exit;
}

if (!empty($_POST['install_db'])) {

    $filename = $_SESSION['path_to_app']."admin/conf/config.php";
	$result = "";
	if (is_file($filename)) {
		unlink($filename);
	}

	// Delete old config file (e.g. from previous installation)
	if (is_dir($_SESSION['path_to_app']."admin") == false) {
		if (!mkdir($_SESSION['path_to_app']."admin")) {
            json_encode("Could not create admin directory");
            exit;
        }
	}

	if (is_dir($_SESSION['path_to_app']."admin/conf/") == false) {
		if (!mkdir($_SESSION['path_to_app']."admin/conf/")) {
            json_encode("Could not create conf directory");
            exit;
        }
	}

    $string = '<?php
	$host = "'. $_POST["host"]. '";
	$username = "'. $_POST["username"]. '";
	$passw = "'. $_POST["passw"]. '";
	$dbname = "'. $_POST["dbname"]. '";
	$db_prefix = "'. $_POST["dbprefix"]. '_";
	$sitetitle = "'. $_POST["sitetitle"].'";
    $site_url = "'. $_POST["site_url"].'";
	$users_table = "'. $_POST["dbprefix"].'_users";
	$presentation_table = "'. $_POST["dbprefix"].'_presentations";
	$mailinglist_table = "'. $_POST["dbprefix"].'_mailinglist";
    $config_table = "'. $_POST["dbprefix"].'_config";
    $post_table = "'. $_POST["dbprefix"].'_post";
	?>';

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
    chmod($filename,0644);

    // Tables to create
    $users_table = $_POST["dbprefix"]."_users";
    $presentation_table = $_POST["dbprefix"]."_presentations";
    $mailing_list = $_POST["dbprefix"]."_mailinglist";
    $config_table = $_POST["dbprefix"]."_config";
    $post_table = $_POST["dbprefix"]."_post";

    // Connect to database
    $db_set = new DB_set();
    $bdd = $db_set->bdd_connect();

    // Remove any pre-existent tables
    $sql = "SHOW TABLES FROM ".$_POST["dbname"]." LIKE '".$_POST["dbprefix"]."_%'";
    $req = $db_set->send_query($sql);
    while ($row = mysqli_fetch_row($req)) {
        $table_name = $row[0];
        $db_set -> deletetable($table_name);
    }

    // Create users table
    $cols_name = "`id` INT NOT NULL AUTO_INCREMENT,
        `date` DATETIME,
        `firstname` CHAR(20),
        `lastname` CHAR(20),
        `fullname` CHAR(30),
        `username` CHAR(50),
        `password` CHAR(50),
        `position` CHAR(10),
        `email` CHAR(50),
        `notification` INT(1) NOT NULL,
        `reminder` INT(1) NOT NULL,
        `nbpres` INT(4) NOT NULL,
        `status` CHAR(10),
        `hash` CHAR(32),
        `active` INT(1) NOT NULL,
        PRIMARY KEY(id)";
    if ($db_set->createtable($users_table,$cols_name,1)) {
	    $result .= "<p id='success'>'$users_table' created</p>";
    }

    // Create Post table
    $cols_name = "`id` INT NOT NULL AUTO_INCREMENT,
        `date` DATETIME,
        `post` TEXT(2000),
        `username` CHAR(50),
        PRIMARY KEY(id)";
    if ($db_set->createtable($post_table,$cols_name,1)) {
	    $result .= "<p id='success'> '$post_table' created</p>";
    }

    // Create config table
    $cols_name = "`id` INT NOT NULL AUTO_INCREMENT,
        `variable` CHAR(20),
        `value` CHAR(100),
        PRIMARY KEY(id)";
    if ($db_set->createtable($config_table,$cols_name,1) == true) {
    	$result .= "<p id='success'> '$config_table' created</p>";
    }
	$config = new site_config();
    $config->update_config($_POST);
	$result .= "<p id='success'> '$config_table' updated</p>";

    // Create presentations table
    $cols_name = "`id` INT NOT NULL AUTO_INCREMENT,
        `up_date` DATETIME,
        `id_pres` BIGINT(15) NOT NULL,
        `type` CHAR(20),
        `date` DATE,
        `jc_time` CHAR(15),
        `title` CHAR(100),
        `authors` CHAR(50),
        `summary` TEXT(2000),
        `link` CHAR(100),
        `orator` CHAR(50),
        `presented` INT(1) NOT NULL,
        `notification` INT(1) NOT NULL,
	  PRIMARY KEY(id)";
    if ($db_set->createtable($presentation_table,$cols_name,1)) {
    	$result .= "<p id='success'>'$presentation_table' created</p>";
    }

    // Create mailing_list table
    $cols_name = "`id` INT NOT NULL AUTO_INCREMENT,
        `username` CHAR(50),
        `email` CHAR(50),
        PRIMARY KEY(id)";
    if ($db_set->createtable($mailing_list,$cols_name,1)) {
    	$result .= "<p id='success'>'$mailing_list' created</p>";
    }
    echo json_encode($result);
    exit;
}


// Get page content
if (!empty($_POST['getpagecontent'])) {
    $step = htmlspecialchars($_POST['getpagecontent']);

    if ($step == 1) {
		$title = "Step 1: Database configuration";
		$operation = "
			<form action='' method='post' name='install' id='install_db'>
				<input class='field' type='hidden' name='install_db' value='true' />
				<label for='host' class='label'>Host Name</label><input class='field' name='host' type='text' value='localhost'></br>
				<label for='username' class='label'>Username</label><input class='field' name='username' type='text' value='root'></br>
				<label for='passw' class='label'>Password</label><input class='field' name='passw' type='password' value='root'></br>
				<label for='dbname' class='label'>DB Name</label><input class='field' name='dbname' type='text' value='test'></br>
				<label for='dbprefix' class='label'>DB Prefix</label><input class='field' name='dbprefix' type='text' value='pjc'></br>
				<label for='sitetitle' class='label'>Site title</label><input class='field' name='sitetitle' type='text' value='$config->sitetitle'></br>
				<label for='site_url' class='label'>Web path to root</label><input class='field' name='site_url' type='text' value='$config->site_url' size='30'></br>
				<label for='mail_from' class='label'>Sender Email address</label><input class='field' name='mail_from' type='text' value='$config->mail_from'></br>
				<label for='mail_from_name' class='label'>Sender name</label><input class='field' name='mail_from_name' type='text' value='$config->mail_from_name'></br>
				<label for='mail_host' class='label'>Email host</label><input class='field' name='mail_host' type='text' value='$config->mail_host'></br>
				<label for='SMTP_secure' class='label'>SMTP access</label>
					<select name='SMTP_secure'>
						<option value='$config->SMTP_secure' selected='selected'>$config->SMTP_secure</option>
						<option value='ssl'>ssl</option>
						<option value='tls'>tls</option>
						<option value='none'>none</option>
					 </select><br>
				<label for='mail_port' class='label'>Email port</label><input class='field' name='mail_port' type='text' value='$config->mail_port'></br>
				<label for='mail_username' class='label'>Email username</label><input class='field' name='mail_username' type='text' class='$config->mail_username'></br>
				<label for='mail_password' class='label'>Email password</label><input class='field' name='mail_password' type='password' value='$config->mail_password'></br>
				<p style='text-align: right'><input type='submit' name='install_db' value='Next' id='submit' class='install_db'></p>
			</form>
			<div class='feedback'></div>
		";
	} elseif ($step == 2) {
		$title = "Step 2: Admin account creation";
		$operation = "
		<div id='form' class='admin_login'>
			<form method='post' id='admin_creation'>
				<label for='admin_username' class='label'>UserName : </label><input class='field' id='admin_username' type='text' name='admin_username'><br/>
				<label for='admin_password' class='label'>Password : </label><input class='field' id='admin_password' type='password' name='admin_password'><br/>
				<label for='admin_confpassword' class='label'>Confirm password: </label><input class='field' id='admin_confpassword' type='password' name='admin_confpassword'><br/>
				<label for='admin_email' class='label'>Email: </label><input class='field' type='text' name='admin_email' id='admin_email'><br/>
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
		<p id='warning'> Now you can delete the 'install.php' file from the root folder of the application</p>
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
    exit;
}

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
    <head>
        <META http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <META NAME="description" CONTENT="Journal Club Manager. Organization. Submit or suggest a presentation. Archives.">
        <META NAME="keywords" CONTENT="Journal Club">
        <link href='http://fonts.googleapis.com/css?family=Lato&subset=latin,latin-ext' rel='stylesheet' type='text/css'>
        <link type='text/css' rel='stylesheet' href="css/stylesheet.css"/>
        <link type='text/css' rel='stylesheet' href="css/jquery-ui.css"/>
        <link type='text/css' rel='stylesheet' href="css/jquery-ui.theme.css"/>

        <!-- JQuery -->
        <script type="text/javascript" src="js/jquery-1.11.1.js"></script>
        <script type="text/javascript" src="js/jquery-ui.js"></script>
        <script type="text/javascript" src="js/spin.js"></script>

        <!-- Bunch of jQuery functions -->
        <script type="text/javascript">
            // Spin animation when a page is loading
            var $loading = $('#loading').hide();

            // Check email validity
            function checkemail(email) {
                var pattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);
                return pattern.test(email);
            };

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

            // Process installation forms
            var step = 1;
            var processinstallform = function(formid) {
                var data = $("#" + formid).serialize();
                jQuery.ajax({
                    url: 'install.php',
                    type: 'POST',
                    async: false,
                    data: data,
                    success: function(data){
                        var result = jQuery.parseJSON(data);
                        console.log("returned result:"+result);
                        $('#operation')
                            .html(result)
                            .append("<input type='submit' id='submit' class='next' value='Next'/>");
                    }
                });
            };

            var getpagecontent = function(step) {
                jQuery.ajax({
                    url: 'install.php',
                    type: 'POST',
                    async: false,
                    data: {
                        getpagecontent: step},
                    success: function(data){
                        var result = jQuery.parseJSON(data);
                        $('#loading').hide();
                        $('#pagecontent')
                            .html('<div>'+result+'</div>')
                            .fadeIn('slow');
                    }
                });
            }

            $(document).ready(function () {
                $('.mainbody')
                    .ready(function() {
                        // Get step
                        $.post(window.location, function( data ) {
                            console.log(data);
                            if (data.getpagecontent != undefined) {
                                    getpagecontent(params.step);
                                } else {
                                    getpagecontent(1);
                                }
                            })
                        })

                    /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
                     Installation/Update
                     %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/

                    // Go to next installation step
                    .on('click', '.next', function(e) {
                        e.preventDefault();
                        step += 1;
                        getpagecontent(step);
                    })

                    // Go to next installation step
                    .on('click', '.finish', function(e) {
                        e.preventDefault();
                        window.location = "index.php";
                    })

                    // Create admin account
                    .on('click','.admin_creation',function(e) {
                        e.preventDefault();
                        var username = $("input#admin_username").val();
                        var password = $("input#admin_password").val();
                        var conf_password = $("input#admin_confpassword").val();
                        var email = $("input#admin_email").val();

                        if (username == "") {
                            showfeedback('<p id="warning">This field is required</p>');
                            $("input#admin_username").focus();
                            return false;
                        }

                        if (password == "") {
                            showfeedback('<p id="warning">This field is required</p>');
                            $("input#admin_password").focus();
                            return false;
                        }

                        if (conf_password == "") {
                            showfeedback('<p id="warning">This field is required</p>');
                            $("input#admin_confpassword").focus();
                            return false;
                        }

                        if (conf_password != password) {
                            showfeedback('<p id="warning">Password must match</p>');
                            $("input#admin_confpassword").focus();
                            return false;
                        }

                        if (email == "") {
                            showfeedback('<p id="warning">This field is required</p>');
                            $("input#admin_email").focus();
                            return false;
                        }

                        if (!checkemail(email)) {
                            showfeedback('<p id="warning">Oops, this is an invalid email</p>');
                            $("input#admin_email").focus();
                            return false;
                        }

                        jQuery.ajax({
                            url: 'install.php',
                            type: 'POST',
                            async: false,
                            data: {
                                inst_admin: true,
                                username: username,
                                password: password,
                                email: email,
                                conf_password: conf_password},
                            success: function(data){
                                var result = jQuery.parseJSON(data);
                                console.log(result);
                                showfeedback(result);
                                getpagecontent(3);
                            }
                        });
                    })

                    // Launch database setup
                    .on('click','.install_db',function(e) {
                        e.preventDefault();
                        processinstallform("install_db");
                    })

                    // Update
                    .on('click','.proceed_update',function(e) {
                        e.preventDefault();
                        var val = $(this).val();
                        console.log(val);
                        if (val == 'Yes') {
                            jQuery.ajax({
                                url: 'pages/update.php',
                                type: 'POST',
                                async: false,
                                data: {proceed: true},
                                success: function(data){
                                    var json = jQuery.parseJSON(data);
                                    console.log(json);
                                    $('.section_content').append(json);
                                }
                            });
                        } else {
                            loadpageonclick('home',false);
                        }
                    });
            }).ajaxStart(function(){
                $loading.show();
            }).ajaxStop(function() {
                $loading.hide();
            });
        </script>

        <title>RankMyDrawing - Installation</title>
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
            <span id="sign"><?php echo "<a href='$config->repository' target='_blank'>$config->app_name $config->version</a>
             | <a href='http://www.gnu.org/licenses/agpl-3.0.html' target='_blank'>GNU AGPL v3 </a>
             | <a href='http://www.florianperdreau.fr' target='_blank'>&copy2014 $config->author</a>" ?></span>
        </div>
    </body>
</html>
