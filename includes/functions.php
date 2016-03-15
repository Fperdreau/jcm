<?php
/**
 * PHP version 5
 *
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
 * Bunch of common function
 */

include('boot.php');

/**
 * Explode anc clean array (remove empty strings and force the variable returned by explode to be an array
 * @param $delimiter
 * @param $var
 * @return array
 */
function explodecontent($delimiter,$var) {
    $newvar = explode($delimiter,$var);
    if (is_array($newvar) === false) {
        $newvar = array($newvar);
    }
    $newvar = array_values(array_diff($newvar,array(''))); // Clean the array from empty strings
    return $newvar;
}

/**
 * Show session/presentation types list (admin -> manage session)
 * @param $types
 * @param $class
 * @param $divid
 * @return string
 */
function showtypelist($types,$class,$divid) {
    $result = "";
    foreach ($types as $type) {
        $result .= "
                <div class='type_div' id='$divid'>
                    <div class='type_name'>$type</div>
                    <div class='type_del' data-type='$type' data-class='$class'>
                    </div>
                </div>
            ";
    }
    return $result;
}

/**
 * Create drag&drop field
 * @param array $links
 * @return string
 */
function uploader($links=array()) {
    global $AppConfig;

    // Get files associated to this publication
    $filesList = "";
    if (!empty($links)) {
        foreach ($links as $fileid=>$info) {
            $filesList .=
                "<div class='upl_info' id='upl_$fileid'>
                <div class='upl_name' id='$fileid'>$fileid</div>
                <div class='del_upl' id='$fileid' data-upl='$fileid'>
                </div>
            </div>";
        }
    }

    $result = "
        <div class='upl_container'>
    	   <div class='upl_form'>
                <form method='post' enctype='multipart/form-data'>
                    <input type='file' name='upl' class='upl_input' multiple style='display: none;' />
                    <div class='upl_btn'>
                        Add Files
                        <br>(click or drop)
                        <div class='upl_filetypes'>($AppConfig->upl_types)</div>
                        <div class='upl_errors'></div>
                    </div>
                </form>
    	   </div>
            <div class='upl_filelist'>$filesList</div>
        </div>";
    return $result;
}

/**
 * Generate submission form and automatically fill it up with data provided by Presentation object.
 * @param $user
 * @param bool $Presentation
 * @param string $submit
 * @param bool $type
 * @param bool $date
 * @return string
 */
function displayform($user,$Presentation=false,$submit="submit", $type=false, $date=false) {
    global $db, $AppConfig;

    if ($Presentation == false) {
        $Presentation = new Presentation($db);
    }
    $date = ($date != false) ? $date:$Presentation->date;
    $type = ($type != false) ? $type:$Presentation->type;

    // Get files associated to this publication
    $links = $Presentation->link;
    $uploader = uploader($links);

    // Presentation ID
    $idPres = ($Presentation->id_pres != "") ? $Presentation->id_pres:'false';
    $idPresentation = "<input type='hidden' id='id_pres' name='id_pres' value='$idPres'/>";

    // Show date input only for submissions and updates
    $dateinput = ($submit != "suggest") ? "<label>Date</label><input type='date' id='datepicker' name='date' value='$date'>":"";

    $authors = ($type !== 'minute') ? "<div class='formcontrol'>
                <label>Authors </label>
                <input type='text' id='authors' name='authors' value='$Presentation->authors' required>
            </div>":"";

    $selectopt = ($submit === "select") ? $Presentation->generate_selectwishlist():"";

    // Make submission's type selection list
    $typeoptions = "";
    $pres_type = explode(',',$AppConfig->pres_type);
    foreach ($pres_type as $types) {
        if ($types == $type) {
            $typeoptions .= "<option value='$types' selected>$types</option>";
        } else {
            $typeoptions .= "<option value='$types'>$types</option>";
        }
    }

    // Text of the submit button
    $submitxt = ucfirst($submit);
    $form = ($submit !== "select") ? "<div class='feedback'></div>
        <form method='post' action='' enctype='multipart/form-data' id='submit_form'>
            <div class='submit_btns'>
                <input type='submit' name='$submit' value='$submitxt' id='submit' class='submit_pres'>
            </div>
            <input type='hidden' name='selected_date' id='selected_date' value='$date'/>
            <input type='hidden' name='$submit' value='true'/>
            <input type='hidden' name='username' value='$user->username'/>
            $idPresentation

            <div class='formcontrol'>
                <label>Type</label>
                <select name='type' id='type' required>
                    $typeoptions
                </select>
            </div>

            <div class='formcontrol'>
                $dateinput
            </div>

            <div class='formcontrol' id='guest' style='display: none;'>
                <label>Speaker</label>
                <input type='text' id='orator' name='orator' required>
            </div>

            <br><div class='formcontrol'>
                <label>Title </label>
                <input type='text' id='title' name='title' value='$Presentation->title' required/>
            </div>

            $authors

            <div class='formcontrol'>
                <label>Abstract</label>
                <textarea name='summary' id='summary' placeholder='Abstract (5000 characters maximum)' style='width: 90%;' required>$Presentation->summary</textarea>
            </div>
        </form>

        $uploader":"";

    return "
    <div>$selectopt</div>
    <div class='submission'>
        $form
    </div>
	";
}

/**
 * Make hours options list
 * @param string $start
 * @param string $end
 * @return string
 */
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

/**
 * Browse files to backup
 * @param $dir
 * @param array $dirsNotToSaveArray
 * @return array
 */
function browse($dir, $dirsNotToSaveArray = array()) {
    $filenames = array();
    if ($handle = opendir($dir)) {
        while (false !== ($file = readdir($handle))) {
            $filename = $dir."/".$file;
            if ($file != "." && $file != ".." && is_file($filename)) {
                $filenames[] = $filename;
            } else if ($file != "." && $file != ".." && is_dir($dir.$file) && !in_array($dir.$file, $dirsNotToSaveArray) ) {
                $newfiles = browse($dir.$file,$dirsNotToSaveArray);
                $filenames = array_merge($filenames,$newfiles);
            }
        }
        closedir($handle);
    }
    return $filenames;
}

/**
 * Backup database and save it as a *.sql file. Clean backup folder (remove oldest versions) at the end.
 * @param $nbVersion: Number of previous backup versions to keep on the server (the remaining will be removed)
 * @return string : Path to *.sql file
 */
function backupDb($nbVersion){
    global $db;

    // Create Backup Folder
    $mysqlrelativedir = 'backup/mysql';
    $mysqlSaveDir = PATH_TO_APP.'/'.$mysqlrelativedir;
    $fileNamePrefix = 'fullbackup_'.date('Y-m-d_H-i-s');

    if (!is_dir(PATH_TO_APP.'/backup')) {
        mkdir(PATH_TO_APP.'/backup',0777);
    }

    if (!is_dir($mysqlSaveDir)) {
        mkdir($mysqlSaveDir,0777);
    }

    // Do backup
    /* Store All AppTable name in an Array */
    $allTables = $db->getapptables();

    $return = "";
    //cycle through
    foreach($allTables as $table)
    {
        $result = $db->send_query('SELECT * FROM '.$table);
        $num_fields = mysqli_num_fields($result);

        $return.= 'DROP TABLE '.$table.';';
        $row = $db->send_query('SHOW CREATE TABLE '.$table);
        $row2 = mysqli_fetch_row($row);
        $return.= "\n\n".$row2[1].";\n\n";

        for ($i = 0; $i < $num_fields; $i++)
        {
            while($row = mysqli_fetch_row($result))
            {
                $return.= 'INSERT INTO '.$table.' VALUES(';
                for($j=0; $j<$num_fields; $j++)
                {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = preg_replace("/\n/","\\n",$row[$j]);
                    if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
                    if ($j<($num_fields-1)) { $return.= ','; }
                }
                $return.= ");\n";
            }
        }
        $return.="\n\n\n";
    }
    $handle = fopen($mysqlSaveDir."/".$fileNamePrefix.".sql",'w+');
    fwrite($handle,$return);
    fclose($handle);

    cleanBackups($mysqlSaveDir,$nbVersion);
    return "$mysqlrelativedir/$fileNamePrefix.sql";
}

/**
 * Check for previous backup and delete the oldest ones
 * @param $mysqlSaveDir: Path to backup folder
 * @param $nbVersion: Number of backups to keep on the server
 */
function cleanBackups($mysqlSaveDir,$nbVersion) {
    $oldBackup = browse($mysqlSaveDir);
    if (!empty($oldBackup)) {
        $files = array();
        // First get files date
        foreach ($oldBackup as $file) {
            $fileWoExt = explode('.',$file);
            $fileWoExt = $fileWoExt[0];
            $prop = explode('_',$fileWoExt);
            if (count($prop)>1) {
                $back_date = $prop[1];
                $back_time = $prop[2];
                $formatedTime = str_replace('-',':',$back_time);
                $date = $back_date." ".$formatedTime;
                $files[$date] = $file;
            }
        }

        // Sort backup files by date
        krsort($files);

        // Delete oldest files
        $cpt = 0;
        foreach ($files as $date=>$old) {
            // Delete file if too old
            if ($cpt >= $nbVersion) {
                if (is_file($old)) {
                    unlink($old);
                }
            }
            $cpt++;
        }
    }
}

/**
 * Mail backup file to admins
 * @param $backupfile
 * @return bool
 */
function mail_backup($backupfile) {
    global $db, $AppConfig;
    $mail = new AppMail($db,$AppConfig);
    $admin = new User($db);
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

/**
 * Full backup routine (files + database)
 * @return string
 */
function backupFiles() {

    $dirToSave = PATH_TO_APP;
    $dirsNotToSaveArray = array(PATH_TO_APP."backup");
    $mysqlSaveDir = PATH_TO_APP.'/backup/mysql';
    $zipSaveDir = PATH_TO_APP.'/backup/complete';
    $fileNamePrefix = 'fullbackup_'.date('Y-m-d_H-i-s');

    if (!is_dir(PATH_TO_APP.'/backup')) {
        mkdir(PATH_TO_APP.'/backup',0777);
    }

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
