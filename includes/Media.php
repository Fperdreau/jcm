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

class Media extends AppTable{

    protected $table_data = array(
        "id" => array("INT NOT NULL AUTO_INCREMENT", false),
        "date" => array('DATETIME', false),
        "fileid" => array('CHAR(20)', false),
        "filename" => array('CHAR(20)', false),
        "name" => array('CHAR(255)', false),
        "presid" => array('CHAR(20)', false),
        "obj" => array('CHAR(255)', false),
        "type" => array('CHAR(5)', false),
        "primary" => 'id');

    protected $directory;
    protected $maxsize;
    protected $allowed_types;

    public $presid;
    public $fileid;
    public $date;
    public $filename;
    public $name;
    public $type;
    public $obj;

    /**
     * Constructor
     * @param null $file_id
     */
    function __construct($file_id=null) {
        parent::__construct('Media', $this->table_data);
        $this->directory = PATH_TO_APP.'/uploads/';
        $AppConfig = new AppConfig();
        $this->maxsize = $AppConfig->upl_maxsize;
        $this->allowed_types = explode(',', $AppConfig->upl_types);

        // Create uploads folder if it does not exist yet
        if (!is_dir($this->directory)) {
            mkdir($this->directory);
        }

        // Get file information
        if (!is_null($file_id)) {
            $this->getInfo($file_id);
        }
    }

    /**
     * Delete all files corresponding to the actual presentation
     * @param string $pres_id : unique id of presentation
     * @param string $controller: reference controller name
     * @return bool
     */
    public function delete_files($pres_id, $controller) {
        foreach ($this->all(array('fileid'=>$pres_id, 'obj'=>$controller)) as $key=>$item) {
            $up = new Media();
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
        foreach ($this->db->select($this->tablename, array('*'), array('presid'=>$presid, 'obj'=>$obj_name)) as $key=>$item) {
            $uploads[$item['fileid']] =$item;
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
        foreach ($file_names as $filename) {
            if ($this->add_presid($filename, $id, $obj_name) !== true) {
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
     * Create Media object
     * @param $file
     * @param null $controller: reference controller
     * @return bool|mixed|mysqli_result|string
     */
    public function make($file, $controller=null) {

        // First check the file
        $result['error'] = $this->checkupload($file);
        if ($result['error'] !== true) {
            AppLogger::get_instance(APP_NAME, get_class($this))->error($result['error']);
            return $result;
        }

        // Second: Proceed to upload
        $result = $this->upload($file);
        if ($result['error'] !== true) {
            AppLogger::get_instance(APP_NAME, get_class($this))->error($result['error']);
            return $result;
        }

        $data = array(
            'date'=>date('Y-m-d h:i:s'),
            'filename'=>$result['filename'],
            'name'=>$result['name'],
            'fileid'=>$result['file_id'],
            'type'=>$result['type'],
            'obj'=>$controller
        );

        // Third: add to the Media table
        $content = $this->parsenewdata(get_class_vars(get_called_class()), $data,
            array('directory','maxsize','allowed_types'));
        $result['error'] = $this->db->addcontent($this->tablename,$content);
        if ($result['error'] !== true) {
            $result['error'] = 'SQL: Could not add the file to the media table';
            AppLogger::get_instance(APP_NAME, get_class($this))->error($result['error']);
        }
        $data['error'] = $result['error'];
        $data['input'] = self::hidden_input($data);
        $data['file_div'] = self::file_div($data);
        return $data;
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
    public function add_presid($filename, $presid, $obj_name) {
        $data = $this->get(array('fileid'=>$filename, 'obj'=>$obj_name));
        if (!empty($data)) {
            if ($this->db->updatecontent($this->tablename, array('presid'=>$presid), array('fileid'=>$filename, 'obj'=>$obj_name))) {
                AppLogger::get_instance(APP_NAME, get_class($this))->log("New id ({$obj_name}: {$presid}) associated with file ({$filename})");
                return true;
            } else {
                AppLogger::get_instance(APP_NAME, get_class($this))->error("Could not associate id ({$obj_name}: {$presid}) to file ({$filename})");
                return false;
            }
        } else {
            AppLogger::get_instance(APP_NAME, get_class($this))->error(
                "Could not associate id ({$obj_name}: {$presid}) to file ({$filename}) because this file does not exit in our database");
            return false;
        }

    }

    /**
     * Delete a file corresponding to the actual presentation
     * @param array $id
     * @return bool|string
     */
    public function delete(array $id) {
        $data = $this->get(array('fileid'=>$id));
        if (!empty($data)) {
            if (is_file($this->directory . $data[0]['filename'])) {
                if (unlink($this->directory . $data[0]['filename'])) {
                    if ($this->db->delete($this->tablename, $id)) {
                        $result['status'] = true;
                        $result['msg'] = "File [name: {$data[0]['filename']}] Deleted";
                        AppLogger::get_instance(APP_NAME, __CLASS__)->info($result['msg']);
                    } else {
                        $result['status'] = false;
                        $result['msg'] = "Could not remove file entry from database [name: {$data[0]['filename']}]";
                        AppLogger::get_instance(APP_NAME, __CLASS__)->error($result['msg']);
                    }
                } else {
                    $result['status'] = false;
                    $result['msg'] = "Could not delete file [name: {$data[0]['filename']}]";
                    AppLogger::get_instance(APP_NAME, __CLASS__)->error($result['msg']);
                }
            } else {
                $result['status'] = false;
                $result['msg'] = "File does not exist [name: {$data[0]['filename']}]";
                AppLogger::get_instance(APP_NAME, __CLASS__)->error($result['msg']);
            }
        } else {
            $result['status'] = false;
            $result['msg'] = "Could not find media [id: {$id}] in our database";
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
                $split_name = explode(".", strtolower($file['name'][0]));
                $result['type'] = end($split_name);

                // Create a unique filename
                $file_info = $this->makeId($result['type']);

                // Move file to the upload folder
                $result['error'] = move_uploaded_file($tmp, $this->directory . $file_info['filename']);

                if ($result['error'] == false) {
                    $result['error'] = "Uploading process failed";
                } else {
                    $result['error'] = true;
                    $result['status'] = $file_info['filename'];
                }
                $name = explode('.', $file['name'][0]);
                $result['name'] = $name[0];
                $result['file_id'] = $file_info['fileid'];
                $result['filename'] = $file_info['filename'];
            }
        } else {
            $result['error'] = "No File to upload";
        }
        return $result;
    }

    /**
     * Generate unique ID for file
     * @param $type: file extension
     * @return array: file information (id and name)
     */
    public function makeId($type) {
        $rnd = date('Ymd')."_".rand(0,100);
        $new_name = $rnd . '.' . $type;
        while (is_file($this->directory . $new_name)) {
            $rnd = date('Ymd')."_".rand(0,100);
            $new_name = $rnd . '.' . $type;
        }
        return array('fileid' => $rnd, 'filename'=>$new_name);
    }

    // VIEWS

    /**
     * Renders upload form
     * @param array $links
     * @param string $id: uploader id (must be identical to the corresponding submission form)
     * @return string
     */
    public static function uploader(array $links=array(), $id='uploader', $controller=null) {
        // Get files associated to this publication
        $filesList = "";
        if (!empty($links)) {
            foreach ($links as $fileid=>$info) {
                $filesList .= self::file_div($info);
            }
        }

        $types = explode(',', AppConfig::getInstance()->upl_types);
        $type_list = null;
        foreach ($types as $type) {
            $type_list .= "<div class='file_type'>{$type}</div>";
        }


        $result = "
        <div class='upl_container' id='{$id}' data-controller='{$controller}'>
           <div class='upl_errors'></div>
    	   <div class='upl_form'>
                <div class='text'>Drop here</div>
                <form method='post' enctype='multipart/form-data'>
                    <input type='file' name='upl' class='upl_input' multiple style='display: none;' />
                    <div class='upl_btn'>
                        <div>Browse</div>
                        <div class='upl_file_types'>$type_list</div>
                    </div>
                </form>
    	   </div>
           <div class='upl_filelist'>$filesList</div>
        </div>";
        return $result;
    }

    /**
     * Render hidden input
     * @param array $data
     * @return string
     */
    private static function hidden_input(array $data) {
        return "<input type='hidden' class='upl_link' id='{$data['fileid']}' value='{$data['fileid']}' />";
    }

    /**
     * Render file div (in uploader files list)
     * @param array $data
     * @return string
     */
    private static function file_div(array $data) {
        $url = URL_TO_APP . '/uploads/' . $data['filename'];
        return  "<div class='upl_info' id='upl_{$data['fileid']}'>
                    <div class='upl_name'><a href='{$url}' target='_blank'>{$data['name']}</a></div>
                    <div class='del_upl' id='{$data['fileid']}'></div>
                </div>";
    }

    /**
     * Render download menu
     * @param array $links
     * @param bool $email
     * @return array
     */
    public static function download_menu(array $links, $email=false) {
        $content = array('menu'=>null, 'button'=>null);
        if (!empty($links)) {
            if ($email) {
                // Show files list as a drop-down menu
                $menu = null;
                foreach ($links as $file_id=>$info) {
                    $menu .= "
                        <div class='dl_info'>
                            <div class='dl_type'>".strtoupper($info['type'])."</div>
                            <div class='dl_name' id='{$info['filename']}'>{$info['name']}</div>
                            <div class='icon_btn dl_btn link_name' id='{$info['filename']}'></div>
                        </div>";
                }
                $content['menu'] = "
                        <div class='dl_menu'>
                            <div class='dl_menu_content'>{$menu}</div>
                        </div>";
            } else {
                // Show files list as links
                $menu = null;
                foreach ($links as $file_id=>$info) {
                    $url_link = AppConfig::$site_url."uploads/".$info['filename'];
                    $menu .= "
                    <div style='display: inline-block; text-align: center; padding: 5px 10px 5px 10px;
                                margin: 2px; cursor: pointer; background-color: #bbbbbb; font-weight: bold;'>
                        <a href='$url_link' target='_blank' style='color: rgba(34,34,34, 1);'>".strtoupper($info['type'])."</a>
                    </div>";
                }
                $content['menu'] = "<div style='display: block; text-align: justify; width: 95%; min-height: 20px; 
                    height: auto; margin: auto; border-top: 1px solid rgba(207,81,81,.8);'>{$menu}</div>";
            }
        } else {
            $content['button'] = "<div style='width: 100px'></div>";
            $content['menu'] = null;
        }
        return $content;
    }
}