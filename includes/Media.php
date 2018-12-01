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

namespace includes;

use includes\BaseModel;

class Media extends BaseModel
{

    /**
     * Media settings
     */
    protected $settings = array(
        'upl_types'=>"pdf,doc,docx,ppt,pptx,opt,odp",
        'upl_maxsize'=>10000000
    );

    protected $directory;
    protected $maxsize;
    protected $allowed_types;

    public $obj_id;
    public $file_id;
    public $date;
    public $filename;
    public $name;
    public $type;
    public $obj;

    /**
     * Constructor
     * @param null $file_id
     */
    public function __construct($file_id = null)
    {
        parent::__construct();
        $this->directory = PATH_TO_APP . '/uploads/';
        $this->maxsize = $this->settings['upl_maxsize'];
        $this->allowed_types = explode(',', $this->settings['upl_types']);

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
     * Delete all files corresponding to a presentation
     * @param string $obj_id : unique id of object
     * @param string $controller: reference controller name
     * @return bool
     */
    public function deleteFiles($obj_id, $controller)
    {
        foreach ($this->all(
            array('obj_id'=>$obj_id,
            'obj'=>$controller)
        ) as $key => $item) {
            $result = $this->delete(array('id'=>$item['id']));
            if (!$result['status']) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get uploades associated to an object
     * @param $obj_id: object id
     * @param $obj_name: object name
     * @return array
     */
    public function getUploads($obj_id, $obj_name)
    {
        $uploads = array();
        foreach ($this->db->resultSet(
            $this->tablename,
            array('*'),
            array('obj_id'=>$obj_id, 'obj'=>$obj_name)
        ) as $key => $item) {
            $uploads[$item['id']] = $item;
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
    public function addUpload(array $file_names, $id, $obj_name = null)
    {
        if (is_null($obj_name)) {
            $obj_name = get_called_class();
        }
        foreach ($file_names as $filename) {
            if ($this->addObjId($filename, $id, $obj_name) !== true) {
                return false;
            }
        }
        return true;
    }

    /**
     * Check correspondence between files present on the server and those registered in the db
     * @return bool
     */
    protected function files2db()
    {
        $files = scandir($this->directory);
        foreach ($this->db->resultSet($this->tablename, array('filename')) as $filename) {
            // Delete Db entry if the file does not exit on the server
            if (!in_array($filename, $files)) {
                $sql = "SELECT id FROM {$this->tablename} WHERE filename='{$filename}'";
                $data = $this->db->sendQuery($sql)->fetch_assoc();
                if (!$this->db->delete($this->tablename, array('id'=>$data['id']))) {
                    Logger::getInstance(APP_NAME, get_class($this))->error("Could not remove file '{$filename}' 
                    from database");
                    return false;
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
    public function make($file, $controller = null)
    {
        // First check the file
        $result['error'] = $this->checkupload($file);
        if ($result['error'] !== true) {
            Logger::getInstance(APP_NAME, get_class($this))->error($result['error']);
            return $result;
        }

        // Second: Proceed to upload
        $result = $this->upload($file);
        if ($result['error'] !== true) {
            Logger::getInstance(APP_NAME, get_class($this))->error($result['error']);
            return $result;
        }

        $data = array(
            'date'=>date('Y-m-d h:i:s'),
            'filename'=>$result['filename'],
            'name'=>$result['name'],
            'type'=>$result['type'],
            'obj_id'=>null,
            'obj'=>$controller
        );

        // Third: add to the Media table
        $content = $this->parseData($data, array('directory','maxsize','allowed_types'));
        if (!$this->db->insert($this->tablename, $content)) {
            $data['error'] = 'SQL: Could not add the file to the media table';
            Logger::getInstance(APP_NAME, get_class($this))->error($result['error']);
        } else {
            $data['id'] = $this->db->getLastId();
            $data['error'] = true;
            $data['input'] = self::hiddenInput($data);
            $data['file_div'] = self::fileDiv($data);
        }

        return $data;
    }

    /**
     * @param $file_id
     * @return bool
     */
    public function getInfo($file_id)
    {
        $data = $this->get(array('id'=>$file_id));
        $data['filename'] = PATH_TO_APP . 'uploads' . DS . $data['filename'];
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
    private function checkfiles()
    {
        // First check if the db points to an existing file
        if (!is_file($this->directory . $this->filename)) {
            // If not, we remove the data from the db
            $result = $this->delete(array('id'=>$this->id));
            if (!$result['status']) {
                return false;
            }
        }

        // Second check if files present on the server are registered in the db
        return $this->files2db();
    }

    /**
     * Associates a previously uploaded file to a presentation
     * @param $file_id
     * @param $obj_id
     * @param $obj_name
     * @return mixed
     */
    public function addObjId($file_id, $obj_id, $obj_name)
    {
        $data = $this->get(array('id'=>$file_id, 'obj'=>$obj_name));
        if (!empty($data)) {
            if ($this->db->update(
                $this->tablename,
                array('obj_id'=>$obj_id),
                array('id'=>$file_id, 'obj'=>$obj_name)
            )
            ) {
                Logger::getInstance(APP_NAME, get_class($this))->log(
                    "New id ({$obj_name}: {$obj_id}) associated with file ({$file_id})"
                );
                return true;
            } else {
                Logger::getInstance(APP_NAME, get_class($this))->error(
                    "Could not associate id ({$obj_name}: {$obj_id}) to file ({$file_id})"
                );
                return false;
            }
        } else {
            Logger::getInstance(APP_NAME, get_class($this))->error(
                "Could not associate id ({$obj_name}: {$obj_id}) to file ({$file_id}) 
                because this file does not exist in our database"
            );
            return false;
        }
    }

    /**
     * Delete a file corresponding to the actual presentation
     * @param array $data
     * @return bool|string
     */
    public function delete(array $data)
    {
        $result = array('status'=>true, 'msg'=>null);
        $objData = $this->get($data);
        if (!empty($data)) {
            // Delete file from disk
            if (is_file($this->directory . $objData['filename'])) {
                if (unlink($this->directory . $objData['filename'])) {
                    $result['status'] = true;
                } else {
                    $result['status'] = false;
                    $result['msg'] = "Could not delete file [name: {$objData['filename']}]";
                    Logger::getInstance(APP_NAME, __CLASS__)->error($result['msg']);
                }
            } else {
                $result['status'] = true;
                $result['msg'] = "File does not exist [name: {$objData['filename']}]";
                Logger::getInstance(APP_NAME, __CLASS__)->error($result['msg']);
            }

            // Delete file from DB
            if ($result['status']) {
                if ($this->db->delete($this->tablename, $data)) {
                    $result['status'] = true;
                    $result['msg'] = "File [name: {$objData['filename']}] Deleted";
                    $result['id'] = $data['id'];
                    Logger::getInstance(APP_NAME, __CLASS__)->info($result['msg']);
                } else {
                    $result['status'] = false;
                    $result['msg'] = "Could not remove file entry from database [name: {$objData['filename']}]";
                    Logger::getInstance(APP_NAME, __CLASS__)->error($result['msg']);
                }
            }
        } else {
            $result['status'] = false;
            $result['msg'] = "Could not find media [id: {$id['id']}] in our database";
            Logger::getInstance(APP_NAME, __CLASS__)->error($result['msg']);
        }

        return $result;
    }

    /**
     * Validate upload
     * @param $file
     * @return bool|string
     */
    private function checkupload($file)
    {
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

        if (false === in_array($ext, $this->allowed_types)) {
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
    public function upload($file)
    {
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
    public function makeId($type)
    {
        $rnd = date('Ymd') . "_" . rand(0, 100);
        $new_name = $rnd . '.' . $type;
        while (is_file($this->directory . $new_name)) {
            $rnd = date('Ymd') . "_" . rand(0, 100);
            $new_name = $rnd . '.' . $type;
        }
        return array('file_id' => $rnd, 'filename'=>$new_name);
    }

    // VIEWS

    /**
     * Settings form
     * @param array $settings
     * @return array
     */
    public static function settingsForm(array $settings)
    {
        return array(
            'title'=>'Media settings',
            'body'=>"
                    <form method='post' action='php/router.php?controller=Media&action=updateSettings'>
                        <div class='submit_btns'>
                            <input type='submit' name='modify' value='Modify' class='processform'>
                        </div>
                        <div class='form-group'>
                            <input type='text' name='upl_types' value='{$settings['upl_types']}'>
                            <label>Allowed file types (upload)</label>
                        </div>
                        <div class='form-group'>
                            <input type='text' name='upl_maxsize' value='{$settings['upl_maxsize']}'>
                            <label>Maximum file size (in Kb)</label>
                        </div>
                    </form>
        ");
    }

    /**
     * Generate list of allowed file types
     * @return null|string
     */
    private static function getTypes()
    {
        $self = new self();
        $types = explode(',', $self->settings['upl_types']);

        $type_list = null;
        foreach ($types as $type) {
            $type_list .= "<div class='file_type'>{$type}</div>";
        }
        return $type_list;
    }

    /**
     * Renders upload form
     * @param string $controller: controller name
     * @param array $links
     * @param string $id : uploader id (must be identical to the corresponding submission form)
     * @return string
     */
    public static function uploader($controller, array $links = array(), $id = 'uploader')
    {
        // Get files associated to this publication
        $filesList = "";
        if (!empty($links)) {
            foreach ($links as $file_id => $info) {
                $filesList .= self::fileDiv($info);
            }
        }

        // List of allowed file types
        $type_list = self::getTypes();

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
    private static function hiddenInput(array $data)
    {
        return "<input type='hidden' name='upl_link' class='upl_link' id='{$data['id']}' value='{$data['id']}' />";
    }

    /**
     * Render file div (in uploader files list)
     * @param array $data
     * @return string
     */
    private static function fileDiv(array $data)
    {
        $url = URL_TO_APP . '/uploads/' . $data['filename'];
        return  "<div class='upl_info' id='upl_{$data['id']}'>
                    <div class='upl_name'><a href='{$url}' target='_blank'>{$data['name']}</a></div>
                    <div class='del_upl' id='{$data['id']}'></div>
                </div>";
    }

    /**
     * Render download menu
     * @param array $links
     * @param bool $email
     * @return array
     */
    public static function downloadMenu(array $links, $email = false)
    {
        $content = array('menu'=>null, 'button'=>null);
        if (!empty($links)) {
            if ($email) {
                // Show files list as a drop-down menu
                $menu = null;
                foreach ($links as $file_id => $info) {
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
                foreach ($links as $file_id => $info) {
                    $url_link = App::getAppUrl() . "uploads/".$info['filename'];
                    $menu .= "
                    <div style='display: inline-block; text-align: center; padding: 5px 10px 5px 10px;
                                margin: 2px; cursor: pointer; background-color: #bbbbbb; font-weight: bold;'>
                        <a href='$url_link' target='_blank' style='color: rgba(34,34,34, 1);'>"
                         . strtoupper($info['type']) . "</a>
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

    /**
     * Presentation's attached files (for emails)
     * @param array $links
     * @param $app_url
     * @return string
     */
    public static function downloadMenuEmail(array $links, $app_url)
    {
        $icon_css = "display: inline-block;
            font-size: 10px;
            text-align: center;
            width: 30px;
            height: 30px;
            font-weight: bold;
            background-color: #555555;
            color: #EEEEEE;
            border-radius: 100%;
            line-height: 30px;
            margin: 2px 5px 2px 0px;
        ";

        // Get file list
        $filediv = "";
        if (!empty($links)) {
            $filecontent = "";
            foreach ($links as $file_id => $info) {
                $urllink = $app_url."uploads/".$info['filename'];
                $filecontent .= "
                        <div style='{$icon_css}'>
                            <a href='$urllink' target='_blank' style='color: #EEEEEE;'>".strtoupper($info['type'])."</a>
                        </div>";
            }
            $filediv = "<div style='display: block; text-align: right; width: 95%; min-height: 20px; height: auto;
                margin: auto; border-top: 1px solid rgba(207,81,81,.8);'>{$filecontent}</div>";
        }

        return $filediv;
    }
}
