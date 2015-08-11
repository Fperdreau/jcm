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

if (empty($_SESSION['logok'])) { $_SESSION['logok'] = false;}

// Includes required files (classes)
require_once('includes/boot.php');
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <META http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <META NAME="description" CONTENT="Journal Club Manager. Organization. Submit or suggest a presentation. Archives.">
        <META NAME="keywords" CONTENT="Journal Club">
        <link href='https://fonts.googleapis.com/css?family=Lato&subset=latin,latin-ext' rel='stylesheet' type='text/css'>
        <link type='text/css' rel='stylesheet' href="css/stylesheet.css"/>

        <!-- JQuery -->
        <script type="text/javascript" src="js/jquery-1.11.1.js"></script>
        <script type="text/javascript" src="js/jquery.leanModal.min.js"></script>
        <script type="text/javascript" src="js/jquery-ui.js"></script>
        <script type="text/javascript" src="js/Myupload.js"></script>
        <title><?php echo $AppConfig->sitetitle; ?></title>
    </head>

    <body class="mainbody">
        <?php require(PATH_TO_PAGES.'modal.php'); ?>

        <!-- Header section -->
        <header id="mainheader">
            <div id="title">
                <span id='sitetitle'><?php echo $AppConfig->sitetitle;?></span>
            </div>

            <!-- Menu section -->
            <div style="display: inline-block;">
                <?php require(PATH_TO_PHP.'page_menu.php'); ?>
            </div>

            <!-- Login box -->
            <div id='login_box'>
                <?php
                if (!isset($_SESSION['logok']) || !$_SESSION['logok']) {
                    $showlogin = "<span style='font-size: 16px; color: #FFFFFF;'>
                    <a rel='leanModal' id='modal_trigger_login' href='#modal' class='modal_trigger'>Log in</a>
                     | <a rel='leanModal' id='modal_trigger_register' href='#modal' class='modal_trigger'>Sign up</a>
                     </span>";
                } else {
                    $showlogin = "<span style='font-size: 16px;' class='menu-section' data-url='profile'>My profile</span>
                    | <span style='font-size: 16px;' class='menu-section' id='logout'>Log out</span>";
                }
                echo $showlogin;
                ?>
            </div>

        </header>

        <!-- Core section -->
        <div id="core">
        	<div id="loading"></div>
        	<div id="pagecontent">
                <div id="plugins"></div>
            </div>
        </div>

        <!-- Footer section -->
        <footer id="footer">
            <span id="sign"><?php echo "<a href='$config->repository' target='_blank'>$config->app_name $config->version</a>
             | <a href='http://www.gnu.org/licenses/agpl-3.0.html' target='_blank'>GNU AGPL v3 </a>
             | <a href='http://www.florianperdreau.fr' target='_blank'>&copy2014 $config->author</a>" ?></span>
        </footer>

        <!-- Bunch of jQuery functions -->
        <script type="text/javascript" src="js/index.js"></script>
        <script type="text/javascript" src="js/plugins.js"></script>
        <script type="text/javascript" src="js/cronjobs.js"></script>

        <!-- TinyMce (Rich-text textarea) -->
        <script type="text/javascript" src="js/tinymce/tinymce.min.js"></script>
    </body>
</html>
