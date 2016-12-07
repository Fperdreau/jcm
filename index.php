<?php
/**
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

// Includes required files (classes)
require_once('includes/boot.php');
$menu = AppPage::menu();
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <META http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <META NAME="viewport" CONTENT="width=device-width, target-densitydpi=device-dpi, initial-scale=1.0, user-scalable=yes">
        <META NAME="description" CONTENT="Journal Club Manager - an efficient way of organizing journal clubs">
        <META NAME="keywords" CONTENT="Journal Club, application, science, tools, research, lab, management">

        <!-- Stylesheets -->
        <link type='text/css' rel='stylesheet' href="css/stylesheet.css"/>
        <link type='text/css' rel='stylesheet' href="css/uploader.min.css"/>

        <!-- JQuery -->
        <script type="text/javascript" src="js/jquery-1.11.1.min.js"></script>
        <script type="text/javascript" src="js/loading.min.js"></script>
        <script type="text/javascript" src="js/jquery-ui.js"></script>
        <title>Journal Club Manager - Organize your journal club efficiently</title>
    </head>

    <body class="mainbody">
        <?php require(PATH_TO_PAGES . 'modal.php'); ?>

        <div class="sideMenu">
            <?php echo $menu; ?>
        </div>

        <!-- Header section -->
        <header class="header">

            <div id="float_menu"><img src='images/menu.png' alt='login'></div><!--
         --><div id="sitetitle">
                <span style="font-size: 30px; font-weight: 400;">JCM</span>
                <span style="font-size: 25px; color: rgba(200,200,200,.8);">anager</span>
            </div><!--
        Menu section -->
            <div class="menu">
                <div class="topnav">
                    <?php echo $menu; ?>
                </div>
            </div><!--
         Login box-->
            <div id='login_box'>
                <?php
                if (!isset($_SESSION['logok']) || !$_SESSION['logok']) {
                    $showlogin = "
                <div class='leanModal' id='user_login' data-section='user_login'><img src='images/login.png' alt='login'></div>
                <div class='leanModal' id='user_register' data-section='user_register'><img src='images/signup.png' alt='signup'></div>
                 ";
                } else {
                    $showlogin = "
                <div class='menu-section'><a href='index.php?page=profile' id='profile'><img src='images/profile.png' alt='profile'></a></div>
                <div class='menu-section'><a href='#' class='menu-section' id='logout'><img src='images/logout.png' alt='logout'></a></div>";
                }
                echo $showlogin;
                ?>
            </div>
        </header>

        <!-- Core section -->
        <main id="core">
            <div id="page_container">
                <div id="hidden_container">
                    <div id="current_content">
                        <div class="wrapper">
                            <div id="section_title"></div>
                            <div id="section_content"></div>
                        </div>
                        <div id="plugins"></div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer section -->
        <footer id="footer">
            <div id="colBar"></div>
            <div id="appTitle"><?php echo AppConfig::app_name; ?></div>
            <div id="appVersion">Version <?php echo AppConfig::version; ?></div>
            <div id="sign">
                <a href="<?php echo AppConfig::repository?>" target='_blank'><?php echo AppConfig::copyright; ?></a>
            </div>
        </footer>

        <!-- Bunch of jQuery functions -->
        <script type="text/javascript" src="js/index.min.js"></script>
        <script type="text/javascript" src="js/form.min.js"></script>
        <script type="text/javascript" src="js/plugins.min.js"></script>
        <script type="text/javascript" src="js/Myupload.min.js"></script>
        <script type="text/javascript" src="js/jquery.leanModal.min.js"></script>
        <script type="text/javascript" src="js/passwordchecker/passwordchecker.min.js"></script>
        <link type='text/css' rel='stylesheet' href='js/passwordchecker/css/style.min.css' />

        <!-- TinyMce (Rich-text textarea) -->
        <script type="text/javascript" src="js/tinymce/tinymce.min.js"></script>
    </body>
</html>
