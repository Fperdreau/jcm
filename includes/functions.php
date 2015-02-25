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

// Generate submission form and automatically fill it up with data provided by Presentation object.
function displayform($user,$Presentation,$submit="submit") {
    $config = new site_config('get');
    if ($Presentation == false) {
        $Presentation = new Presentation();
    }

    $date = $Presentation->date;

    // Get files associated to this publication
    $filelist = "";
    if (!empty($Presentation->link)) {
        $links = explode(',',$Presentation->link);
        foreach ($links as $link) {
            $name = explode('.',$link);
            $name = $name[0];
            $filelist .=
            "<div class='upl_info' id='$name'>
                <div class='upl_name' id='$link'>$link</div>
                <div class='del_upl' id='$link' data-upl='$name'>
                    <img src='images/delete.png' style='width: 15px; height: 15px;' alt='delete'>
                </div>
            </div>";
        }
    } else {
        $filelist .= "";
    }

    $idPresentation = "";
    if ($submit == "update") {
        $idPresentation = "<input type='hidden' name='id_pres' value='$Presentation->id_pres'/>";
    }

    // Show date input only for submissions and updates
    if ($submit != "suggest") {
        $dateinput = "<label>Date</label><input type='text' id='datepicker' name='date' value='$date'>
            ";
    } else {
        $dateinput = "";
    }

    // Make submission's type selection list
    $typeoptions = "";
    $pres_type = explode(',',$config->pres_type);
    foreach ($pres_type as $type) {
        if ($type == $Presentation->type) {
            $typeoptions .= "<option value='$type' selected>$type</option>";
        } else {
            $typeoptions .= "<option value='$type'>$type</option>";
        }
    }

    // Text of the submit button
    $submitxt = ucfirst($submit);

    return "
    <div class='submission'>
        <div class='feedback'></div>
        <form method='post' action='' enctype='multipart/form-data' class='form' id='submit_form'>
            <div class='submit_btns'>
                <input type='submit' name='$submit' value='$submitxt' id='submit' class='submit_pres'>
            </div>
            <input type='hidden' name='selected_date' id='selected_date' value='$date'/>
            <input type='hidden' name='$submit' value='true'/>
            <input type='hidden' name='username' value='$user->username'/>
            $idPresentation

            <div class='formcontrol' style='width: 15%;'>
                <label>Type</label>
                <select name='type' id='type'>
                    $typeoptions
                </select>
            </div>

            <div class='formcontrol' style='width: 10%;'>
                $dateinput
            </div>

            <div class='formcontrol' id='guest' style='width: 30%; display: none;'>
                <label>Speaker</label>
                <input type='text' id='orator' name='orator'>
            </div>

            <br><div class='formcontrol' style='width: 50%;'>
                <label>Title </label>
                <input type='text' id='title' name='title' value='$Presentation->title'/>
            </div>

            <div class='formcontrol' style='width: 50%;'>
                <label>Authors </label>
                <input type='text' id='authors' name='authors' value='$Presentation->authors'>
            </div>

            <div class='formcontrol' style='width: 80%;'>
                <label>Abstract</label>
                <textarea name='summary' id='summary' placeholder='Abstract (5000 characters maximum)'>$Presentation->summary</textarea>
            </div>
        </form>

        <div class='upl_container'>
    	   <div class='upl_form'>
                <form method='post' enctype='multipart/form-data'>
                <input type='file' name='upl' class='upl_input' multiple style='display: none;' />
                <div class='upl_btn'>
                    Add Files
                    <br>(click or drop)
                    <div class='upl_filetypes'>($config->upl_types)</div>
                    <div class='upl_errors'></div>
                </div>
                </form>
    	   </div>
            <div class='upl_filelist'>$filelist</div>
        </div>
    </div>
	";
}

// Generate submission form and automatically fill it up with data provided by Presentation object.
function displaypub($user,$Presentation) {
    if (!(empty($Presentation->link))) {
        $download_button = "<div class='dl_btn' id='$Presentation->id_pres'>Download</div>";
        $filelist = explode(',',$Presentation->link);
        $dlmenu = "<div class='dlmenu'>";
        foreach ($filelist as $file) {
            $dlmenu .= "<div class='dl_info'><div class='upl_name' id='$file'>$file</div></div>";
        }
        $dlmenu .= "</div>";
    } else {
        $download_button = "<div style='width: 100px'></div>";
        $dlmenu = "";
    }

    // Add a delete link (only for admin and organizers or the authors)
    if ($user->status != 'member' || $Presentation->orator == $user->fullname) {
        $delete_button = "<div class='pub_btn'><a href='#' data-id='$Presentation->id_pres' class='delete_ref'>Delete</a></div>";
        $modify_button = "<div class='pub_btn'><a href='#' data-id='$Presentation->id_pres' class='modify_ref'>Modify</a></div>";
    } else {
        $delete_button = "<div style='width: 100px'></div>";
        $modify_button = "<div style='width: 100px'></div>";
    }
    $type = ucfirst($Presentation->type);
    $result = "
        <div class='pub_caps'>
            <div style='display: block; position: relative; float: right; margin: 0 auto 5px 0px; text-align: center; height: 20px; line-height: 20px; width: 100px; background-color: #555555; color: #FFF; padding: 5px;'>
                $type
            </div>
            <div id='pub_title'>$Presentation->title</div>
            <div id='pub_date'><span style='color:#CF5151; font-weight: bold;'>Date: </span>$Presentation->date </div> <div id='pub_orator'><span style='color:#CF5151; font-weight: bold;'>Presented by: </span>$Presentation->orator</div>
            <div id='pub_authors'><span style='color:#CF5151; font-weight: bold;'>Authors: </span>$Presentation->authors</div>
        </div>

        <div class='pub_abstract'>
            <span style='color:#CF5151; font-weight: bold;'>Abstract: </span>$Presentation->summary
        </div>

        <div class='pub_action_btn'>
            <div class='pub_one_half'>
                $download_button
                $dlmenu
            </div>
            <div class='pub_one_half last'>
                $delete_button
                $modify_button
            </div>
        </div>
        ";

    return $result;
}

// Make hours options list
function maketimeopt($start="07:00",$end="20:00") {
    $tStart = strtotime($start);
    $tEnd = strtotime($end);
    $tNow = $tStart;
    $timeopt = "";
    while($tNow <= $tEnd){
        $opt =  date("H:i",$tNow);
        $timeopt .= "<option value='$opt'>$opt</option>";
        $tNow = strtotime('+30 minutes',$tNow);
    }
    return $timeopt;
}

// Browse files for backup
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
    $config = new site_config('get');

    // Create Backup Folder
    $mysqlrelativedir = 'backup/mysql';
    $mysqlSaveDir = $_SESSION['path_to_app'].$mysqlrelativedir;
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

    cleanbackups($mysqlSaveDir);

    return "$mysqlrelativedir/$fileNamePrefix.sql";
}

// Check for previous backup and delete old ones
function cleanbackups($mysqlSaveDir) {
    $config = new site_config('get');
    $oldbackup = browse($mysqlSaveDir);
    if (!empty($oldbackup)) {
        $files = array();
        // First get files date
        foreach ($oldbackup as $file) {
            $filewoext = explode('.',$file);
            $filewoext = $filewoext[0];
            $prop = explode('_',$filewoext);
            if (count($prop)>1) {
                $back_date = $prop[1];
                $back_time = $prop[2];
                $formatedtime = str_replace('-',':',$back_time);
                $date = $back_date." ".$formatedtime;
                $files[$date] = $file;
            }
        }

        // Sort backup files by date
        krsort($files);

        // Delete oldest files
        $cpt = 0;
        foreach ($files as $date=>$old) {
            // Delete file if too old
            if ($cpt >= $config->clean_day) {
                if (is_file($old)) {
                    unlink($old);
                }
            }
            $cpt++;
        }
    }
}

// Mail backup file to admins
function mail_backup($backupfile) {
    require_once($_SESSION['path_to_includes'].'myMail.php');
    $mail = new myMail();
    $admin = new users();
    $admin->get('admin');

    // Send backup via email
    $content = "
    Hello, <br>
    <p>This message has been sent automatically by the server. You may find a backup of your database in attachment.</p>
    ";
    $body = $mail -> formatmail($content);
    $subject = "Automatic Database backup";
    if ($mail->send_mail($admin->email,$subject,$body,$backupfile)) {
        return true;
    } else {
        return false;
    }
}

// Full backup routine
function file_backup() {

    $dirToSave = $_SESSION['path_to_app'];
    $dirsNotToSaveArray = array($_SESSION['path_to_app']."backup");
    $mysqlSaveDir = $_SESSION['path_to_app'].'backup/mysql';
    $zipSaveDir = $_SESSION['path_to_app'].'backup/complete';
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
