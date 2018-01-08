<?php
/**
 * Database table schema
 * Used by install.php
 *
 * usage: $tables = array(
 *      "table_name_without_prefix"=>array(
 *          "column_name"=>array("DATA_TYPE", default_value)
 *      );
 */

return array(
    "assignment"=> array(
        "id"=>array("INT NOT NULL AUTO_INCREMENT",false),
        "username"=>array("CHAR(255)",false),
        "primary"=>'id'
    ),

    "auth" => array(
        "id" => array("INT NOT NULL AUTO_INCREMENT", false),
        "date" => array("DATETIME", false),
        "username" => array("CHAR(30)", false),
        "attempt" => array("INT(1) NOT NULL", 0),
        "last_login" => array("DATETIME NOT NULL"),
        "primary" => "id"
    ),

    "availability"=>array(
        "id"=>array("INT NOT NULL AUTO_INCREMENT",false),
        "username"=>array("CHAR(255)",false),
        "date"=>array("DATE NOT NULL", false),
        "primary"=>'id'
    ),

    "bookmark"=>array(
        "id" => array("INT NOT NULL AUTO_INCREMENT", false),
        "ref_id" => array("BIGINT(15)", false),
        "ref_obj" => array("CHAR(55)", false),
        "username" => array("CHAR(255) NOT NULL"),
        "primary" => "id"
    ),

    "digestmaker"=>array(
        "id"=>array("INT NOT NULL AUTO_INCREMENT",false),
        "name"=>array("CHAR(20)",false),
        "position"=>array("INT(5) NOT NULL", 0),
        "display"=>array("INT(1) NOT NULL", 1),
        "primary"=>'id'
    ),

    "mailmanager"=>array(
        "id" => array("INT NOT NULL AUTO_INCREMENT", false),
        "date" => array("DATETIME", false),
        "mail_id" => array("CHAR(15)", false),
        "status" => array("INT(1)", 0),
        "recipients" => array("TEXT NOT NULL", false),
        "attachments" => array("TEXT NOT NULL", false),
        "content" => array("TEXT NOT NULL"),
        "subject" => array("TEXT(500)", false),
        "logs" => array("TEXT", false),
        "primary" => "id"
    ),

    "media"=>array(
        "id" => array("INT NOT NULL AUTO_INCREMENT", false),
        "date" => array('DATETIME', false),
        "file_id" => array('CHAR(20)', false),
        "filename" => array('CHAR(20)', false),
        "name" => array('CHAR(255)', false),
        "obj_id" => array('CHAR(20)', false),
        "obj" => array('CHAR(255)', false),
        "type" => array('CHAR(5)', false),
        "primary" => 'id'
    ),

    "page"=>array(
        "id"=>array('INT NOT NULL AUTO_INCREMENT',false),
        "name"=>array('CHAR(20)',false),
        "filename"=>array('CHAR(255)',false),
        "parent"=>array('CHAR(255)',false),
        "status"=>array('INT(2)',false),
        "rank"=>array('INT(2)', 0),
        "show_menu"=>array('INT(1)',false),
        "meta_title"=>array('VARCHAR(255)',false),
        "meta_keywords"=>array('TEXT(1000)',false),
        "meta_description"=>array('TEXT(1000)',false),
        "primary"=>"id"
    ),

    "plugins"=>array(
        "id"=>array("INT NOT NULL AUTO_INCREMENT",false),
        "name"=>array("CHAR(20)",false),
        "version"=>array("CHAR(5)",false),
        "page"=>array("CHAR(20)",false),
        "status"=>array("INT(1)",false),
        "options"=>array("TEXT",false),
        "description"=>array("TEXT",false),
        "primary"=>'id'
    ),

    "posts"=>array(
        "id" => array("INT NOT NULL AUTO_INCREMENT", false),
        "postid" => array("CHAR(50) NOT NULL"),
        "date" => array("DATETIME", False),
        "title" => array("VARCHAR(255) NOT NULL"),
        "content" => array("TEXT(5000) NOT NULL", false, "post"),
        "username" => array("CHAR(30) NOT NULL", false),
        "homepage" => array("INT(1) NOT NULL", 0),
        "primary" => "id"
    ),

    "presentation"=>array(
        "id" => array("INT NOT NULL AUTO_INCREMENT", false),
        "up_date" => array("DATETIME", false),
        "id_pres" => array("BIGINT(15)", false),
        "username" => array("CHAR(30) NOT NULL"),
        "type" => array("CHAR(30)", false),
        "date" => array("DATE", false),
        "jc_time" => array("CHAR(15)", false),
        "title" => array("CHAR(150)", false),
        "keywords" => array("CHAR(255)", false),
        "authors" => array("CHAR(150)", false),
        "summary" => array("TEXT(5000)", false),
        "orator" => array("CHAR(50)", false),
        "notified" => array("INT(1) NOT NULL", 0),
        "session_id" => array("INT", false),
        "primary" => "id"
    ),

    "remindermaker"=>array(
        "id"=>array("INT NOT NULL AUTO_INCREMENT",false),
        "name"=>array("CHAR(20)",false),
        "position"=>array("INT(5) NOT NULL", 0),
        "display"=>array("INT(1) NOT NULL", 1),
        "primary"=>'id'
    ),

    "session"=>array(
        "id" => array("INT NOT NULL AUTO_INCREMENT", false),
        "date" => array("DATE", false),
        "status" => array("CHAR(10)", "FREE"),
        "room" => array("CHAR(10)", false),
        "time" => array("VARCHAR(200)", false),
        "type" => array("CHAR(30) NOT NULL"),
        "nbpres" => array("INT(2)", 0),
        "slots" => array("INT(2)", 0),
        "repeated" => array("INT(1) NOT NULL", 0),
        "to_repeat" => array("INT(1) NOT NULL", 0),
        "frequency" => array("INT(2)", 0),
        "start_date" => array("DATE", false),
        "end_date" => array("DATE", false),
        "start_time" => array("TIME", false),
        "end_time" => array("TIME", false),
        "event_id" => array("INT", false),
        "primary" => "id"
    ),

    "settings"=>array(
        "id" => array("INT NOT NULL AUTO_INCREMENT", false),
        "object" => array("CHAR(20)", false),
        "variable" => array("CHAR(20)", false),
        "value" => array("TEXT", false),
        "primary" => "id"
    ),

    "suggestion"=>array(
        "id" => array("INT NOT NULL AUTO_INCREMENT", false),
        "id_pres" => array("BIGINT(15)", false),
        "up_date" => array("DATETIME", false),
        "username" => array("CHAR(255) NOT NULL"),
        "type" => array("CHAR(30)", false),
        "keywords" => array("CHAR(255)", false),
        "title" => array("CHAR(150)", false),
        "authors" => array("CHAR(150)", false),
        "summary" => array("TEXT(5000)", false),
        "notified" => array("INT(1) NOT NULL", 0),
        "vote" => array("INT(10) NOT NULL", 0),
        "primary" => "id"
    ),

    "tasks"=>array(
        "id"=>array('INT NOT NULL AUTO_INCREMENT',false),
        "name"=>array('CHAR(20)',false),
        "version"=>array('CHAR(10)',false),
        "description"=>array('TEXT',false),
        "time"=>array('DATETIME',false),
        "frequency"=>array('CHAR(15)',false),
        "path"=>array('VARCHAR(255)',false),
        "status"=>array('CHAR(3)',false),
        "options"=>array('TEXT',false),
        "running"=>array('INT(1) NOT NULL',0),
        "primary"=>"id"
    ),

    "users"=>array(
        "id" => array("INT NOT NULL AUTO_INCREMENT", false),
        "date" => array("DATETIME", false),
        "firstname" => array("CHAR(30)", false),
        "lastname" => array("CHAR(30)", false),
        "fullname" => array("CHAR(30)", false),
        "username" => array("CHAR(30)", false),
        "password" => array("CHAR(255)", false),
        "position" => array("CHAR(10)", false),
        "email" => array("CHAR(100)", false),
        "notification" => array("INT(1) NOT NULL", 1),
        "reminder" => array("INT(1) NOT NULL", 1),
        "assign" => array("INT(1) NOT NULL", 1),
        "nbpres" => array("INT(3) NOT NULL", 0),
        "status" => array("CHAR(10)", false),
        "hash" => array("CHAR(32)", false),
        "active" => array("INT(1) NOT NULL", 0),
        "primary" => "id"
    ),

    "vote"=>array(
        "id" => array("INT NOT NULL AUTO_INCREMENT", false),
        "ref_id" => array("BIGINT(15)", false),
        "ref_obj" => array("CHAR(55)", false),
        "date" => array("DATETIME", false),
        "username" => array("CHAR(255) NOT NULL"),
        "primary" => "id"
    )
);