<?php
/**
 * File for class Backup
 *
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

namespace includes;

use \ZipArchive;

/**
 * Class Backup
 * @package Includes\Backup
 */
class Backup
{

    /**
     * Backup database and save it as a *.sql file. Clean backup folder (remove oldest versions) at the end.
     * @param $nbVersion: Number of previous backup versions to keep on the server (the remaining will be removed)
     * @return mixed: array('status'=>boolean, 'msg'=>string)
     */
    public static function backupDb($nbVersion)
    {
        // Create Backup Folder
        $mysqlrelativedir = 'backup/mysql';
        $mysqlSaveDir = PATH_TO_APP . DS . $mysqlrelativedir;
        $fileNamePrefix = 'fullbackup_' . date('Y-m-d_H-i-s');
        $filename = $mysqlSaveDir. DS .$fileNamePrefix.".sql";

        if (!is_dir(PATH_TO_APP . '/backup')) {
            mkdir(PATH_TO_APP . '/backup', 0777);
        }

        if (!is_dir($mysqlSaveDir)) {
            mkdir($mysqlSaveDir, 0777);
        }

        try {
            // Export DB
            self::performBackup($filename);

            // Compress output file
            self::gzipBackupFile($filename, $level = 9);

            // Remove old versions
            self::cleanBackups($mysqlSaveDir, $nbVersion);

            return array(
                'status'=>true,
                'msg'=>'Database Backup completed',
                'filename'=>"$mysqlrelativedir/$fileNamePrefix.sql"
            );
        } catch (\Exception $e) {
            Logger::getInstance(APP_NAME, __CLASS__)->error($e);
            return array(
                'status'=>false,
                'msg'=>'Sorry, something went wrong',
                'filename'=>null
            );
        }
    }

    /**
     * Perform backup
     *
     * @param string performBackup
     */
    private static function performBackup($filename)
    {
        set_time_limit(600);

        // Do backup
        $db = Db::getInstance();

        // Batch size
        $batchSize = 100;

        $sql = "CREATE DATABASE IF NOT EXISTS `{$db->config['dbname']}`;\n\n";
        $sql .= "USE `{$db->config['dbname']}`;\n\n";
        self::saveToFile($filename, $sql);

        //cycle through
        foreach ($db->getAppTables() as $table) {
            // Get number of rows
            $result = $db->sendQuery('SELECT count(*) FROM ' . $table)->fetch_row();
            $totRows = max(intval($result[0]), 1);
            $maxRowNb = min($batchSize, $totRows);

            $nBatch = max(ceil($totRows/($maxRowNb)), 1);

            self::saveToFile($filename, "DROP TABLE IF EXISTS `{$table}`;");

            $row = $db->sendQuery("SHOW CREATE TABLE " . $table)->fetch_row();
            self::saveToFile($filename, "\n\n" . $row[1] . ";\n\n");

            // Loop over batch
            for ($b = 0; $b <= $nBatch; $b++) {
                $sql = "SELECT * FROM `{$table}` LIMIT " . ((($b+1) * $maxRowNb) - $maxRowNb) . ", {$maxRowNb}";
                $req = $db->sendQuery($sql);
                $num_fields = $req->field_count;

                $out = '';
                // Write rows
                while ($row = $req->fetch_row()) {
                    $j = 0;
                    $rowStr = "";
                    foreach ($row as $field) {
                        $field = addslashes($field);
                        $field = preg_replace("/\n/", "\\n", $field);
                        $field = json_encode($field);

                        if (isset($field)) {
                            $rowStr .= $field;
                        } else {
                            $rowStr .= "''";
                        }
                        if ($j<($num_fields-1)) {
                            $rowStr .= ',';
                        }
                        $j += 1;
                    }
                    $out .= "INSERT INTO `{$table}` VALUES({$rowStr});\n";
                }
                $out .= "\n\n\n";

                // Write to SQL file
                self::saveToFile($filename, $out);
            }
        }

        //$handle = fopen($filename, 'w+');
        //fwrite($handle, $return);
        //fclose($handle);
    }

    /**
     * Save DB to sql file
     *
     * @param string $filename: full path to SQL file
     * @param string $sql: content to write
     * @return bool
     */
    private static function saveToFile($filename, $sql)
    {
        try {
            file_put_contents($filename, $sql, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            print_r($e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Delete directories
     * @param string $dir
     * @return bool
     */
    public static function deleteDirectory($dir)
    {
        try {
            if (!file_exists($dir)) {
                return true;
            }

            if (!is_dir($dir)) {
                return unlink($dir);
            }

            foreach (scandir($dir) as $item) {
                if ($item == '.' || $item == '..') {
                    continue;
                }

                if (!self::deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                    return false;
                }
            }
            return rmdir($dir);
        } catch (\Exception $e) {
            Logger::getInstance(APP_NAME, __CLASS__)->error($e);
            return false;
        }
    }

    /**
     * Browse directories
     * @param string $dir
     * @param array $dirsNotToSaveArray
     * @return array
     */
    private static function browse($dir, $dirsNotToSaveArray = array())
    {
        $filenames = array();
        if ($handle = opendir($dir)) {
            while (false !== ($file = readdir($handle))) {
                $filename = $dir."/".$file;
                if ($file != "." && $file != ".." && is_file($filename)) {
                    $filenames[] = $filename;
                } elseif ($file != "." && $file != ".." && is_dir($dir.$file)
                && !in_array($dir.$file, $dirsNotToSaveArray) ) {
                    $newfiles = self::browse($dir.$file, $dirsNotToSaveArray);
                    $filenames = array_merge($filenames, $newfiles);
                }
            }
            closedir($handle);
        }
        return $filenames;
    }

    /**
     * Check for previous backup and delete the oldest ones
     * @param $mysqlSaveDir : Path to backup folder
     * @param $nbVersion : Number of backups to keep on the server
     * @return bool
     */
    private static function cleanBackups($mysqlSaveDir, $nbVersion)
    {
        try {
            $oldBackup = self::browse($mysqlSaveDir);
            if (!empty($oldBackup)) {
                $files = array();
                // First get files date
                foreach ($oldBackup as $file) {
                    $fileWoExt = explode('.', $file);
                    $fileWoExt = $fileWoExt[0];
                    $prop = explode('_', $fileWoExt);
                    if (count($prop)>1) {
                        $back_date = $prop[1];
                        $back_time = $prop[2];
                        $formatedTime = str_replace('-', ':', $back_time);
                        $date = $back_date." ".$formatedTime;
                        $files[$date] = $file;
                    }
                }

                // Sort backup files by date
                krsort($files);

                // Delete oldest files
                $cpt = 0;
                foreach ($files as $date => $old) {
                    // Delete file if too old
                    if ($cpt >= $nbVersion) {
                        if (is_file($old)) {
                            unlink($old);
                        }
                    }
                    $cpt++;
                }
            }
            return true;
        } catch (\Exception $e) {
            Logger::getInstance(APP_NAME, __CLASS__)->error($e);
            return false;
        }
    }

    /**
     * Mail backup file to admins
     * @param array $data
     * @return bool
     */
    public static function mailBackup(array $data)
    {
        $mail = new MailManager();
        $user = new Users();

        foreach ($user->all(array('status'=>'admin')) as $key => $item) {
            try {
                // Send backup via email
                $content = array(
                    'attachments'=> PATH_TO_APP . $data['filename'],
                    'body'=> "Hello {$item['fullname']}, <br>
                        <p>This message has been sent automatically by the server. You may find a backup of your 
                        database in attachment.</p>",
                    'subject'=>"Automatic Database backup"
                );
                $content['emails'] = $item['id'];
                $mail->addToQueue($content);
            } catch (\Exception $e) {
                Logger::getInstance(APP_NAME, __CLASS__)->error($e);
            }
        }
        return true;
    }

    /*
     * Gzip backup file
     *
     * @param integer $level GZIP compression level (default: 9)
     * @return string New filename (with .gz appended) if success, or false if operation fails
     */
    private static function gzipBackupFile($sqlFileName, $level = 9)
    {
        $dest =  $sqlFileName . '.gz';

        $mode = 'wb' . $level;
        if ($fpOut = gzopen($dest, $mode)) {
            if ($fpIn = fopen($sqlFileName, 'rb')) {
                while (!feof($fpIn)) {
                    gzwrite($fpOut, fread($fpIn, 1024 * 256));
                }
                fclose($fpIn);
            } else {
                return false;
            }
            gzclose($fpOut);
            if (!unlink($sqlFileName)) {
                return false;
            }
        } else {
            return false;
        }
        
        return $dest;
    }

    /**
     * Full backup routine (files + database)
     * @return string
     */
    public static function backupFiles()
    {
        try {
            $dirToSave = PATH_TO_APP;
            $dirsNotToSaveArray = array(PATH_TO_APP."backup");
            $mysqlSaveDir = PATH_TO_APP.'/backup/mysql';
            $zipSaveDir = PATH_TO_APP.'/backup/complete';
            $fileNamePrefix = 'fullbackup_'.date('Y-m-d_H-i-s');

            if (!is_dir(PATH_TO_APP.'/backup')) {
                mkdir(PATH_TO_APP.'/backup', 0777);
            }

            if (!is_dir($zipSaveDir)) {
                mkdir($zipSaveDir, 0777);
            }

            system("gzip ".$mysqlSaveDir."/".$fileNamePrefix.".sql");
            system("rm ".$mysqlSaveDir."/".$fileNamePrefix.".sql");

            $zipfile = $zipSaveDir.'/'.$fileNamePrefix.'.zip';

            // Check if backup does not already exist
            $filenames = self::browse($dirToSave, $dirsNotToSaveArray);

            $zip = new ZipArchive();

            if ($zip->open($zipfile, ZIPARCHIVE::CREATE)!==true) {
                return "cannot open <$zipfile>";
            } else {
                foreach ($filenames as $filename) {
                    $zip->addFile($filename, $filename);
                }
                $zip->close();
                return "backup/complete/$fileNamePrefix.zip";
            }
        } catch (\Exception $e) {
            Logger::getInstance(APP_NAME, __CLASS__)->error($e);
            return false;
        }
    }
}
