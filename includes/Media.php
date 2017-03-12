<?php
/**
 * File for class Uploads and class Media
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

class Uploads extends AppTable{

    protected $table_data = array(
        "id" => array("INT NOT NULL AUTO_INCREMENT", false),
        "date" => array('DATETIME', false),
        "fileid" => array('CHAR(20)', false),
        "filename" => array('CHAR(20)', false),
        "presid" => array('CHAR(20)', false),
        "obj" => array('CHAR(255)', false),
        "type" => array('CHAR(5)', false),
        "primary" => 'id');
    protected $directory;
    protected $maxsize;
    protected $allowed_types;

    /**
     * Constructor
     */
    function __construct() {
        parent::__construct('Media', $this->table_data);
        $this->directory = PATH_TO_APP.'/uploads/';
        $AppConfig = new AppConfig();
        $this->maxsize = $AppConfig->upl_maxsize;
        $this->allowed_types = explode(',', $AppConfig->upl_types);

        // Create uploads folder if it does not exist yet
        if (!is_dir($this->directory)) {
            mkdir($this->directory);
        }
    }

    /**
     * Delete all files corresponding to the actual presentation
     * @param $pres_id: unique id of presentation
     * @return bool
     */
    public function delete_files($pres_id) {
        foreach ($this->all(array('fileid'=>$pres_id)) as $key=>$item) {
            $up = new Media($item['fileid']);
            $result = $up->delete(array('fileid'=>$item['fileid']));
            if (!$result['status']) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get uploades associated to a presentation
     * @param $presid: presentation id
     * @param $obj_name: object name
     * @return array
     */
    public function get_uploads($presid, $obj_name) {
        $uploads = array();
        foreach ($this->db->select($this->tablename, array('fileid'), array('presid'=>$presid, 'obj'=>$obj_name)) as $key=>$item) {
            $up = new Media($item['fileid']);
            $uploads[$item['fileid']] = array('date'=>$up->date,'filename'=>$up->filename,'type'=>$up->type);
        }
        return $uploads;
    }

    /**
     * Add media associated to presentations to db
     * @param array $file_names
     * @param $id : Presentation id
     * @param $obj_name: reference object name (e.g. 'Presentation', 'Suggestion')
     * @return bool
     */
    public function add_upload(array $file_names, $id, $obj_name=null) {
        if (is_null($obj_name)) $obj_name = get_called_class();

        $upload = new Media();
        foreach ($file_names as $filename) {
            if ($upload->add_presid($filename, $id, $obj_name) !== true) {
                return False;
            }
        }
        return true;
    }

    /**
     * Check correspondence between files present on the server and those registered in the db
     * @return bool
     */
    protected function files2db() {
        $dbfiles = $this->db->select($this->tablename, array('filename'));
        $files = scandir($this->directory);
        foreach ($dbfiles as $filename) {
            // Delete Db entry if the file does not exit on the server
            if (!in_array($filename,$files)) {
                $sql = "SELECT fileid FROM $this->tablename WHERE filename='$filename'";
                $req = $this->db->send_query($sql);
                $data = mysqli_fetch_assoc($req);
                $file = new Media($data['fileid']);
                if  (!$this->db->delete($this->tablename, array('fileid'=>$file->fileid))) {
                    AppLogger::get_instance(APP_NAME, get_class($this))->error("Could not remove file '{$filename}' from database");
                    return False;
                }
            }
        }
        return true;
    }

    /**
     * Renders upload form
     * @param array $links
     * @return string
     */
    public static function uploader(array $links=array()) {
        // Get files associated to this publication
        $filesList = "";
        if (!empty($links)) {
            foreach ($links as $fileid=>$info) {
                $filesList .=
                    "<div class='upl_info' id='upl_$fileid'>
                        <div class='upl_name' id='$fileid'>$fileid</div>
                        <div class='del_upl' id='$fileid' data-upl='$fileid'></div>
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
                        <div class='upl_filetypes'>(" . AppConfig::getInstance()->upl_types . ")</div>
                        <div class='upl_errors'></div>
                    </div>
                </form>
    	   </div>
           <div class='upl_filelist'>$filesList</div>
        </div>";
        return $result;
    }

}

/**
 * Class Media
 *
 * Handles properties and methods specific to individual medium
 */
class Media extends Uploads {

    public $presid;
    public $fileid;
    public $date;
    public $filename;
    public $type;
    public $obj;

    /**
     * Media constructor
     * @param null $fileid
    */
    public function __construct($fileid=null){
        parent::__construct();

        if (!is_null($fileid)) {
            $this->getInfo($fileid);
        }
    }

    /**
     * Create Media object
     * @param $file
     * @return bool|mixed|mysqli_result|string
     */
    public function make($file) {

        // First check the file
        $result['error'] = $this->checkupload($file);
        if ($result['error'] != true) {
            AppLogger::get_instance(APP_NAME, get_class($this))->error($result['error']);
            return $result;
        }

        // Second: Proceed to upload
        $result = $this->upload($file);
        if ($result['error'] !== true) {
            AppLogger::get_instance(APP_NAME, get_class($this))->error($result['error']);
            return $result;
        }

        $this->date = date('Y-m-d h:i:s');

        // Third: add to the Media table
        $class_vars = get_class_vars('Media');
        $content = $this->parsenewdata($class_vars,array(),array('directory','maxsize','allowed_types'));
        $result['error'] = $this->db->addcontent($this->tablename,$content);
        if ($result['error'] !== true) {
            $result['error'] = 'SQL: Could not add the file to the media table';
            AppLogger::get_instance(APP_NAME, get_class($this))->error($result['error']);
        }
        return $result;
    }

    /**
     * @param $fileid
     * @return bool
     */
    public function getInfo($fileid) {
        $data = $this->get(array('fileid'=>$fileid));
        if (!empty($data)) {
            $this->map($data);
        } else {
            return false;
        }
        $this->checkfiles();
        return true;
    }

    /**
     * Check consistency between the media table and the files actually stored on the server
     * @return bool
     */
    private function checkfiles () {
        // First check if the db points to an existing file
        if (!is_file($this->directory.$this->filename)) {
            // If not, we remove the data from the db
            $result = $this->delete(array('fileid'=>$this->fileid));
            if (!$result['status']) {
                return False;
            }
        }

        // Second check if files present on the server are registered in the db
        return $this->files2db();
    }

    /**
     * Associates a previously uploaded file to a presentation
     * @param $filename
     * @param $presid
     * @param $obj_name
     * @return mixed
     */
    function add_presid($filename, $presid, $obj_name) {
        if ($this->db->updatecontent($this->tablename,array('presid'=>$presid),array('filename'=>$filename, 'obj'=>$obj_name))) {
            AppLogger::get_instance(APP_NAME, get_class($this))->log("New id ({$presid}) associated with file ({$filename})");
            return true;
        } else {
            AppLogger::get_instance(APP_NAME, get_class($this))->error("Could not associate id ({$presid}) to file ({$filename})");
            return false;
        }
    }

    /**
     * Delete a file corresponding to the actual presentation
     * @param array $id
     * @return bool|string
     */
    public function delete(array $id) {
        if (is_file($this->directory.$this->filename)) {
            if (unlink($this->directory.$this->filename)) {
                if ($this->db->delete($this->tablename, $id)) {
                    $result['status'] = true;
                    $result['msg'] = "File Deleted";
                    AppLogger::get_instance(APP_NAME, __CLASS__)->info($result['msg']);
                } else {
                    $result['status'] = false;
                    $result['msg'] = "Could not remove file entry from database";
                    AppLogger::get_instance(APP_NAME, __CLASS__)->error($result['msg']);
                }
            } else {
                $result['status'] = false;
                $result['msg'] = "Could not delete file";
                AppLogger::get_instance(APP_NAME, __CLASS__)->error($result['msg']);
            }
        } else {
            $result['status'] = false;
            $result['msg'] = "File does not exist";
            AppLogger::get_instance(APP_NAME, __CLASS__)->error($result['msg']);
        }
        return $result;
    }

    /**
     * Validate upload
     * @param $file
     * @return bool|string
     */
    private function checkupload($file) {
        // Check $_FILES['upfile']['error'] value.
        if ($file['error'][0] != 0) {
            switch ($file['error'][0]) {
                case UPLOAD_ERR_OK:
                    break;
                case UPLOAD_ERR_NO_FILE:
                    return "No file to upload";
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    return 'File Exceeds size limit';
                default:
                    return "Unknown error";
            }
        }

        // You should also check file size here.
        if ($file['size'][0] > $this->maxsize) {
            return "File Exceeds size limit";
        }

        // Check extension
        $filename = basename($file['name'][0]);
        $ext = substr($filename, strrpos($filename, '.') + 1);

        if (false === in_array($ext,$this->allowed_types)) {
            return "Invalid file type";
        } else {
            return true;
        }
    }

    /**
     * Upload a file
     * @param $file
     * @return mixed
     */
    public function upload($file) {
        $result['status'] = false;
        if (isset($file['tmp_name'][0]) && !empty($file['name'][0])) {
            $result['error'] = self::checkupload($file);
            if ($result['error'] === true) {
                $tmp = htmlspecialchars($file['tmp_name'][0]);
                $splitname = explode(".", strtolower($file['name'][0]));
                $this->type = end($splitname);

                // Create a unique filename
                $newname = $this->makeId();

                // Move file to the upload folder
                $dest = $this->directory.$newname;
                $result['error'] = move_uploaded_file($tmp,$dest);

                if ($result['error'] == false) {
                    $result['error'] = "Uploading process failed";
                } else {
                    $result['error'] = true;
                    $result['status'] = $newname;
                }
            }
        } else {
            $result['error'] = "No File to upload";
        }
        return $result;
    }

    public function makeId() {
        $rnd = date('Ymd')."_".rand(0,100);
        $newname = $rnd.".".$this->type;
        while (is_file($this->directory.$newname)) {
            $rnd = date('Ymd')."_".rand(0,100);
            $newname = $rnd.".".$this->type;
        }
        $this->fileid = $rnd;
        $this->filename = $newname;
        return $this->filename;
    }
}