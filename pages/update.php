<?php
/*
Copyright © 2014, F. Perdreau, Radboud University Nijmegen
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

@session_start();
$_SESSION['root_path'] = $_SERVER['DOCUMENT_ROOT'];
$_SESSION['app_name'] = "/Pjc/";
$_SESSION['path_to_app'] = $_SESSION['root_path'].$_SESSION['app_name'];
$_SESSION['path_to_includes'] = $_SESSION['path_to_app']."includes/";
date_default_timezone_set('Europe/Paris');

// Includes required files (classes)
require_once($_SESSION['path_to_includes'].'includes.php');

$config = new site_config('get');
$version = "1.2";

if (!empty($_POST['proceed'])) {
    // Get site config
    $config_file = $_SESSION['path_to_app']."/admin/conf/config.php";
    if (file_exists($config_file)) {
        require_once($config_file);
    } else {
        die(json_encode("<p id='warning'>Admin configuration file is missing</p>"));
    }

    $config->update_config($_POST);

    /////////// Update database ///////////
    // Connect to database
    $db_set = new DB_set();
    $bdd = $db_set->bdd_connect();

    // Update config table
    $result = "<p id='success'> Processing of '$config_table'</p>";
    $db_set->addcontent($config_table,"variable,value","'clean_day','10'");

    // Update user table
    $result .=  "<p id='success'> Processing of '$users_table'</p>";
    $db_set->addcolumn($users_table,"fullname","CHAR(30)","lastname");	
    $db_set->addcolumn($users_table,"reminder","INT(1)","notification");
    $db_set->addcolumn($users_table,"notification","INT(1)","email");
    $db_set->addcolumn($users_table,"date","DATETIME","id");

    $result .= "<p id='success'> Updating '$users_table'</p>";

    $sql = "SELECT username,date FROM $users_table";
    $req = $db_set -> send_query($sql);
    while ($data = mysqli_fetch_array($req)) {
        $user = new users($data['username']);
        $result .= "<p>Update of $user->username</p>";

        $post['reminder'] = 1;
        $post['notification'] = 1;
        $post['fullname'] = $user->firstname." ".$user->lastname;
        if ($data['date'] == NULL) {
            $post['date'] = date('Y-m-d');
        }
        $user->updateuserinfo($post);
    }

    // Update presentation table
    $result .= "<p id='success'> Processing of '$presentation_table'</p>";
    $db_set->addcolumn($presentation_table,"up_date","DATETIME","id");
    $db_set->addcolumn($presentation_table,"id_pres","BIGINT(20)","id",0);
    $db_set->addcolumn($presentation_table,"jc_time","CHAR(15)","date");

    $sql = "SELECT id,up_date,id_pres,jc_time,date FROM $presentation_table";
    $req = $db_set->send_query($sql);
    while ($data = mysqli_fetch_array($req)) {
        $id = $data['id'];
        if ($data['id_pres'] == 0) {
            $pub = new presclass();
            $id_pres = $pub->create_presID();
            $db_set->updatecontent($presentation_table,"id_pres","'$id_pres'",array("id"),array("'$id'"));
            $data['id_pres'] = $id_pres;
        }

        $pub = new presclass($data['id_pres']);
        $result .= "<p>Update of $pub->id_pres</p>";

        if ($data['up_date'] == NULL) {
            $post['up_date'] = date('Y-m-d');
        }

        if ($data['jc_time'] == NULL) {
            $post['jc_time'] = "$config->jc_time_from,$config->jc_time_to";
        }
        $pub->update_presentation($post);
    }

    $result .= "<p id='success'>Update complete<br>You can now delete the update.php file</p>";
    echo json_encode($result);
} else {

    $result = "
        <div id='content'>
            <div class='section_content'>
                <p id='warning'>
                    Journal Club Manager Update $version (current version: $config->version). Do you want to proceed?
                    <form method='post' action='' id='form'>
                        <input type='hidden' name='version' value='$version'/>
                        <input type='submit' name='proceed' value='Yes' id='submit' class='proceed_update'/>
                        <input type='submit' name='proceed' value='No' id='submit' class='proceed_update'/>
                    </form>
                </p>
            </div>
        </div>
        ";
    echo json_encode($result);
}


