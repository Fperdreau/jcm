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

class Uploads extends AppTable{

    protected $table_data = array(
        "id" => array("INT NOT NULL AUTO_INCREMENT", false),
        "date" => array('DATETIME', false),
        "fileid" => array('CHAR(20)', false),
        "filename" => array('CHAR(20)', false),
        "presid" => array('CHAR(20)', false),
        "type" => array('CHAR(5)', false),
        "primary" => 'id');
    protected $directory;
    protected $maxsize;
    protected $allowed_types;

    /**
     * Constructor
     * @param DbSet $db
     */
    function __construct(DbSet $db) {
        parent::__construct($db, 'Media', $this->table_data);
        $config = new AppConfig($db);
        $this->directory = PATH_TO_APP.'/uploads/';
        $this->maxsize = $config->upl_maxsize;
        $this->allowed_types = explode(',',$config->upl_types);

        // Create uploads folder if it does not exist yet
        if (!is_dir($this->directory)) {
            mkdir($this->directory);
        }
    }

    /**
     * Delete all files corresponding to the actual presentation
     * @return bool
     */
    public function delete_files($presid) {
        $sql = "SELECT fileid FROM $this->tablename WHERE presid='$presid'";
        $req = $this->db->send_query($sql);
        while ($row=mysqli_fetch_assoc($req)) {
            $up = new Media($this->db, $row['fileid']);
            $result = $up->delete();
            if ($result !== true) {
                return $result;
            }
        }
        return true;
    }

    /**
     * Get uploades associated to a presentation
     * @param $presid
     * @return array
     */
    function get_uploads($presid) {
        $sql = "SELECT fileid FROM " .$this->tablename. " WHERE presid=$presid";
        $req = $this->db->send_query($sql);
        $uploads = array();
        while ($row=mysqli_fetch_assoc($req)) {
            $up = new Media($this->db, $row['fileid']);
            $uploads[$row['fileid']] = array('date'=>$up->date,'filename'=>$up->filename,'type'=>$up->type);
        }
        return $uploads;
    }

    /**
     * Check correspondence between files present on the server and those registered in the db
     * @return bool
     */
    protected function files2db() {
        $dbfiles = $this->db->getinfo($this->tablename, 'filename');
        $files = scandir($this->directory);
        foreach ($dbfiles as $filename) {
            // Delete Db entry if the file does not exit on the server
            if (!in_array($filename,$files)) {
                $sql = "SELECT fileid FROM $this->tablename WHERE filename='$filename'";
                $req = $this->db->send_query($sql);
                $data = mysqli_fetch_assoc($req);
                $file = new Media($this->db,$data['fileid']);
                if  (!$this->db->deletecontent($this->tablename,'fileid',$file->fileid)) {
                    return False;
                }
            }
        }
        return true;
    }
}

class Media extends Uploads {

    public $presid;
    public $fileid;
    public $date;
    public $filename;
    public $type;

    /**
     * @param DbSet $db
     * @param null $fileid
    */
    function __construct(DbSet $db, $fileid=null){
        parent::__construct($db);

        if (null != $fileid) {
            self::get($fileid);
        }
    }

    /**
     * Create Media object
     * @param $file
     * @return bool|mixed|mysqli_result|string
     * @internal param $presid
     */
    public function make($file) {

        // First check the file
        $valid = $this->checkupload($file);
        if ($valid !== true) {
            return $valid;
        }

        // Second: Proceed to upload
        $result = $this->upload($file);
        if ($result['error'] !== true) {
            return $result;
        }

        $this->date = date('Y-m-d h:i:s');

        // Third: add to the Media table
        $class_vars = get_class_vars('Media');
        $content = $this->parsenewdata($class_vars,array(),array('directory','maxsize','allowed_types'));
        $result['error'] = $this->db->addcontent($this->tablename,$content);
        if ($result['error'] !== true) {
            $result['error'] = 'SQL: Could not add the file to the media table';
        }

        return $result;
    }

    /**
     * @param $fileid
     * @return bool
     */
    function get($fileid) {
        $sql = "SELECT * FROM $this->tablename WHERE fileid='$fileid'";
        $req = $this->db->send_query($sql);
        $data = mysqli_fetch_assoc($req);
        if (!empty($data)) {
            foreach ($data as $key=>$value) {
                $this->$key = htmlspecialchars_decode($value);
            }
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
    function checkfiles () {
        // First check if the db points to an existing file
        if (!is_file($this->directory.$this->filename)) {
            // If not, we remove the data from the db
            if ($this->delete() !== true) {
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
     * @return mixed
     */
    function add_presid($filename,$presid) {
        return $this->db->updatecontent($this->tablename,array('presid'=>$presid),array('filename'=>$filename));
    }

    /**
     * Delete a file corresponding to the actual presentation
     * @return bool|string
     */
    function delete() {
        if (is_file($this->directory.$this->filename)) {
            if (unlink($this->directory.$this->filename)) {
                if ($this->db->deletecontent($this->tablename,'fileid',$this->fileid)) {
                    return true;
                } else {
                    return 'table_failed';
                }
            } else {
                return 'not_deleted';
            }
        } else {
            return 'no_file ';
        }
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
                $results['error'] = move_uploaded_file($tmp,$dest);

                if ($results['error'] == false) {
                    $result['error'] = "Uploading process failed";
                } else {
                    $results['error'] = true;
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