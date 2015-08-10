<?php
/**
 * Created by PhpStorm.
 * User: Florian
 * Date: 08/03/2015
 * Time: 20:18
 */

require('../includes/boot.php');
require('calendar.php');
$db = new DbSet();
$AppConfig = new AppConfig($db);
$session = new Sessions($db);

$booked = $session->getjcdates();
$today = date('Y-m-d');

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
    <head>
        <META http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <META NAME="description" CONTENT="Journal Club Manager. Organization. Submit or suggest a presentation. Archives.">
        <META NAME="keywords" CONTENT="Journal Club">
        <link href='https://fonts.googleapis.com/css?family=Lato&subset=latin,latin-ext' rel='stylesheet' type='text/css'>
        <link type='text/css' rel='stylesheet' href="../css/stylesheet.css"/>
        <link type='text/css' rel='stylesheet' href="calendar.css"/>

        <!-- JQuery -->
        <script type="text/javascript" src="../js/jquery-1.11.1.js"></script>
        <script type="text/javascript" src="../js/jquery.leanModal.min.js"></script>
        <script type="text/javascript" src="../js/jquery-ui.js"></script>
        <script type="text/javascript" src="../js/Myupload.js"></script>
        <title>Calendar</title>
</head>

<body class="mainbody">
<?php require(PATH_TO_PAGES.'modal.php'); ?>

<div class="menubar" style="width: 100%; height: 40px; background-color: #222222; color: #FFFFFF;">
    <div class="menudrop" style="display: inline-block; vertical-align: middle; cursor: pointer;">..::MENU::..</div>
    <div style="display: inline-block;"><div id="sitetitle" style="font-size: 25px;">Journal Club Manager</div></div>
</div>

<!-- Core section -->
<div style="display: inline-block; width: 100%; vertical-align: top;">
    <div id="loading"></div>
    <div id="pagecontent" style="display: block;">
        <div id="content" style="width: 100%;">
        </div>
    </div>
</div>


<!-- Bunch of jQuery functions -->
<script type="text/javascript" src="calendar.js"></script>

<!-- TinyMce (Rich-text textarea) -->
<script type="text/javascript" src="../js/tinymce/tinymce.min.js"></script>
</body>
</html>



