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

require('../includes/boot.php');

// db backup
$backupfile = backup_db(); // backup database
mail_backup($backupfile); // Send backup file to admins

// file backup
$zipfile = file_backup(); // Backup site files (archive)
$filelink = json_encode($zipfile);
echo $filelink;

// Write log only if server request
if (empty($_POST['webproc'])) {
    $cronlog ='fullbackup_log.txt';
    if (!is_file($cronlog)) {
        $fp = fopen($cronlog,"w");
    } else {
        $fp = fopen($cronlog,"a+");
    }
    $string = "[".date('Y-m-d H:i:s')."]: Full Backup successfully done.\r\n";
    fwrite($fp,$string);
    fclose($fp);
}
