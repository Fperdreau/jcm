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

function check_login($status=null) {
	$cond = !isset($_SESSION['logok']) || $_SESSION['logok'] == false;
	if (null != $status) {
		if (is_array($status)) {
			$cond = $cond || !in_array($_SESSION['status'], $status);
		} else {
			$cond = $cond || $_SESSION['status'] != $status;
		}
	}

    if ($cond) {
        $result = "
		    <div id='content'>
		        <p id='warning'>You must be logged in order to access this page</br>
		        <a rel='leanModal' id='modal_trigger_login' href='#modal' class='modal_trigger'>Log in</a> or <a rel='leanModal' id='modal_trigger_register' href='#modal' class='modal_trigger'>Sign Up</a></p>
		    </div>
		    ";
		echo json_encode($result);
        exit;
    }
}

// Generate submission form and automatically fill it up with data provided by presclass object.
function displayform($user,$presclass,$submit="submit") {
    $config = new site_config('get');
    $date = $presclass->date;

    if ($presclass->link != "") {
        $upload_form = "
        	<label>Uploaded file: </label><a href='uploads/$presclass->link' target='_blank'>$presclass->link</a>
        	<div class='pub_btn'><a href='' data-id='$presclass->id_pres' class='file_replace'>Replace file</a></div>
        	";
    } else {
        $upload_form = "
        <form method='post' action='js/mini-upload-form/upload.php' enctype='multipart/form-data' id='upload'>
	        <div class='upl_note'></div>
	        <div id='drop'>
	            <a>Add a file</a><input type='file' name='upl' id='upl' multiple/> Or drag it here
	        </div>
	        <ul></ul>
		</form>";
    }

    $idpress = "";
    if ($submit == "update") {
        $idpress = "<input type='hidden' name='id_pres' value='$presclass->id_pres'/>";
    }

    if ($submit != "suggest") {
        $dateinput = "<label for='date'>Date </label><input type='text' id='datepicker' name='date' value='$date' size='10'/></br>";
    } else {
        $dateinput = "";
    }

    return "
    <div class='feedback'></div>
    <form method='post' action='' enctype='multipart/form-data' class='form' id='submit_form'>
        <input type='hidden' name='selected_date' id='selected_date' value='$date'/>
        <input type='hidden' name='$submit' id='$submit' value='$submit'/>

        $idpress
        <label for='type'>Type</label>
            <select name='type' id='type'>
                <option value='$presclass->type' selected='selected'>$presclass->type</option>
                <option value='paper'>Paper</option>
                <option value='research'>Research</option>
                <option value='methodology'>Methodology</option>
                <option value='guest'>Guest</option>
            </select>
        $dateinput
        <label for='orator'>Speaker (only for guest speakers) </label><input type='text' id='orator' name='orator' size='30%'/></br>
        <label for='title'>Title </label><input type='text' id='title' name='title' value='$presclass->title' size='90%'/><br>
        <label for='summary'>Abstract </label><br><textarea name='summary' id='summary'>$presclass->summary</textarea><br>
        <label for='authors'>Authors </label><input type='text' id='authors' name='authors' value='$presclass->authors' size='80%'/></br>
        <div style='float: right ; bottom: 5px;'><input type='submit' name='$submit' value='Apply' id='submit' class='$submit'/></div>
    </form>
	<div class='upload_form'>
	$upload_form
	</div>
	";
}

// Generate submission form and automatically fill it up with data provided by presclass object.
function displaypub($user,$presclass) {

    if ($presclass->link != "") {
        $download_button = "<div class='pub_btn'><a href='uploads/$presclass->link' target='_blank'>Download</a></div>";
    } else {
        $download_button = "<div style='width: 100px;'></div>";
    }

    // Add a delete link (only for admin and organizers or the authors)
    if ($user->status != 'member' || $presclass->orator == $user->fullname) {
        $delete_button = "<div class='pub_btn'><a href='#' data-id='$presclass->id_pres' class='delete_ref'>Delete</a></div>";
        $modify_button = "<div class='pub_btn'><a href='#' data-id='$presclass->id_pres' class='modify_ref'>Modify</a></div>";
    } else {
        $delete_button = "<div style='width: 100px'></div>";
        $modify_button = "<div style='width: 100px'></div>";
    }

    $result = "
        <div class='pub_caps'>
            <div id='pub_title'>$presclass->title</div>
            <div id='pub_date'><span style='color:#CF5151; font-weight: bold;'>Date: </span>$presclass->date </div> <div id='pub_orator'><span style='color:#CF5151; font-weight: bold;'>Presented by: </span>$presclass->orator</div>
            <div id='pub_authors'><span style='color:#CF5151; font-weight: bold;'>Authors: </span>$presclass->authors</div>
        </div>

        <div class='pub_abstract'>
            <span style='color:#CF5151; font-weight: bold;'>Abstract: </span>$presclass->summary
        </div>

        <div class='pub_action_btn'>
            <div class='pub_one_half'>$download_button</div>
            <div class='pub_one_half last'>
                $delete_button
                $modify_button
            </div>
        </div>
        ";

    return $result;

}

function browse($dir, $dirsNotToSaveArray = array()) {
    $filenames = array();
    if ($handle = opendir($dir)) {
        while (false !== ($file = readdir($handle))) {
            $filename = $dir."/".$file;
            if ($file != "." && $file != ".." && is_file($filename)) {
                $filenames[] = $filename;
            }

            else if ($file != "." && $file != ".." && is_dir($dir.$file) && !in_array($dir.$file, $dirsNotToSaveArray) ) {
                $newfiles = browse($dir.$file,$dirsNotToSaveArray);
                $filenames = array_merge($filenames,$newfiles);
            }
        }
        closedir($handle);
    }
    return $filenames;
}

// Export target db to xls file
function exportdbtoxls($tablename) {
    /***** EDIT BELOW LINES *****/
    $db_set = new DB_set();
    $DB_Server = $db_set->host; // MySQL Server
    $DB_Username = $db_set->username; // MySQL Username
    $DB_Password = $db_set->password; // MySQL Password
    $DB_DBName = $db_set->dbname; // MySQL Database Name
    $DB_TBLName = $db_set->dbprefix.$tablename; // MySQL Table Name
    $xls_filename = 'backup/export_'.$tablename.date('Y-m-d').'.xls'; // Define Excel (.xls) file name
	$out = "";

    /***** DO NOT EDIT BELOW LINES *****/
    // Create MySQL connection
    $sql = "Select * from $DB_TBLName";
    $Connect = @mysql_connect($DB_Server, $DB_Username, $DB_Password) or die("Failed to connect to MySQL:<br />" . mysql_error() . "<br />" . mysql_errno());
    // Select database
    $Db = @mysql_select_db($DB_DBName, $Connect) or die("Failed to select database:<br />" . mysql_error(). "<br />" . mysql_errno());
    // Execute query
    $result = @mysql_query($sql,$Connect) or die("Failed to execute query:<br />" . mysql_error(). "<br />" . mysql_errno());

    // Header info settings
    header("Content-Type: application/xls");
    header("Content-Disposition: attachment; filename=$xls_filename");
    header("Pragma: no-cache");
    header("Expires: 0");

    /***** Start of Formatting for Excel *****/
    // Define separator (defines columns in excel &amp; tabs in word)
    $sep = "\t"; // tabbed character

    // Start of printing column names as names of MySQL fields
    for ($i = 0; $i<mysql_num_fields($result); $i++) {
        $out .= mysql_field_name($result, $i) . "\t";
    }
    $out .= "\n";
    // End of printing column names

    // Start while loop to get data
    while($row = mysql_fetch_row($result))
    {
        $schema_insert = "";
        for($j=0; $j<mysql_num_fields($result); $j++)
        {
            if(!isset($row[$j])) {
                $schema_insert .= "NULL".$sep;
            }
            elseif ($row[$j] != "") {
                $schema_insert .= "$row[$j]".$sep;
            }
            else {
                $schema_insert .= "".$sep;
            }
        }
        $schema_insert = str_replace($sep."$", "", $schema_insert);
        $schema_insert = preg_replace("/\r\n|\n\r|\n|\r/", " ", $schema_insert);
        $schema_insert .= "\t";
        $out .= trim($schema_insert);
        $out .=  "\n";
    }

	if ($fp = fopen($_SESSION['path_to_app'].$xls_filename, "w+")) {
        if (fwrite($fp, $out) == true) {
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
    chmod($xls_filename,0644);

    return $xls_filename;
}

// Backup routine
function backup_db(){
    require_once($_SESSION['path_to_includes'].'db_connect.php');
    require_once($_SESSION['path_to_includes'].'site_config.php');
    require_once($_SESSION['path_to_includes'].'users.php');

    // Declare classes
    $db_set = new DB_set();
    $config = new site_config();
    $config->get_config();

    // Create Backup Folder
    $mysqlSaveDir = $_SESSION['path_to_app'].'backup/Mysql';
    $fileNamePrefix = 'fullbackup_'.date('Y-m-d_H-i-s');

    if (!is_dir($mysqlSaveDir)) {
        mkdir($mysqlSaveDir,0777);
    }

    // Do backup
    /* Store All Table name in an Array */
    $allTables = array();
    $result = $db_set->send_query('SHOW TABLES');
    while($row = mysqli_fetch_row($result)){
        $allTables[] = $row[0];
    }

    $return = "";
    foreach($allTables as $table){
        $result = $db_set->send_query('SELECT * FROM '.$table);
        $num_fields = mysqli_num_fields($result);

        $return.= 'DROP TABLE IF EXISTS '.$table.';';
        $row2 = mysqli_fetch_row($db_set->send_query('SHOW CREATE TABLE '.$table));
        $return.= "\n\n".$row2[1].";\n\n";

        for ($i = 0; $i < $num_fields; $i++) {
            while($row = mysqli_fetch_row($result)){
                $return.= 'INSERT INTO '.$table.' VALUES(';
                for($j=0; $j<$num_fields; $j++){
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = str_replace("\n","\\n",$row[$j]);
                    if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; }
                    else { $return.= '""'; }
                    if ($j<($num_fields-1)) { $return.= ','; }
                }
                $return.= ");\n";
            }
        }
        $return.="\n\n";
    }
    $handle = fopen($mysqlSaveDir."/".$fileNamePrefix.".sql",'w+');
    fwrite($handle,$return);
    fclose($handle);

    // Check for previous backup and delete old ones
    $oldbackup = browse($mysqlSaveDir);
    $cpt = 0;
    foreach ($oldbackup as $old) {
        $prop = explode('_',$old);
        $back_date = $prop[1];
        $today = date('Y-m-d');
        $lim_date = date("Y-m-d",strtotime($today." - $config->clean_day days"));
        // Delete file if too old
        if ($back_date <= $lim_date) {
            if (is_file($old)) {
                $cpt++;
                unlink($old);
            }
        }
    }
    return "backup/Mysql/$fileNamePrefix.sql";
}

// Mail backup file to admins
function mail_backup($backupfile) {
    require_once($_SESSION['path_to_includes'].'myMail.php');
    $mail = new myMail();
    $admin = new users();
    $admin->getuserinfo('admin');

    // Send backup via email
    $content = "
    Hello, <br>
    <p>This message has been sent automatically by the server. You may find a backup of your database in attachment.</p>
    ";
    $body = $mail -> formatmail($content);
    $subject = "Automatic Database backup";
    if ($mail->send_mail($admin->email,$subject,$body,$backupfile)) {
    }
}

// Full backup routine
function file_backup() {

    $dirToSave = $_SESSION['path_to_app'];
    $dirsNotToSaveArray = array($_SESSION['path_to_app']."backup");
    $mysqlSaveDir = $_SESSION['path_to_app'].'backup/Mysql';
    $zipSaveDir = $_SESSION['path_to_app'].'backup/Complete';
    $fileNamePrefix = 'fullbackup_'.date('Y-m-d_H-i-s');

    if (!is_dir($zipSaveDir)) {
        mkdir($zipSaveDir,0777);
    }

    system("gzip ".$mysqlSaveDir."/".$fileNamePrefix.".sql");
    system("rm ".$mysqlSaveDir."/".$fileNamePrefix.".sql");

    $zipfile = $zipSaveDir.'/'.$fileNamePrefix.'.zip';

    // Check if backup does not already exist
    $filenames = browse($dirToSave,$dirsNotToSaveArray);

    $zip = new ZipArchive();

    if ($zip->open($zipfile, ZIPARCHIVE::CREATE)!==TRUE) {
        return "cannot open <$zipfile>";
    } else {
        foreach ($filenames as $filename) {
            $zip->addFile($filename,$filename);
        }

        $zip->close();
        return "backup/complete/$fileNamePrefix.zip";
    }
}
