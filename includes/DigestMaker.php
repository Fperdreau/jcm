<?php
/**
 * File for class DigestMaker
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

/**
 * Class DigestMaker
 */
class DigestMaker extends AppTable {

    /**
     * @var array $table_data: Table schema
     */
    protected $table_data = array(
        "id"=>array("INT NOT NULL AUTO_INCREMENT",false),
        "name"=>array("CHAR(20)",false),
        "position"=>array("INT(5) NOT NULL", 0),
        "display"=>array("INT(1) NOT NULL", 1),
        "primary"=>'id'
    );
    
    public $name;
    public $position;
    public $display;

    /**
     * Constructor
     * @param AppDb $db
     * @param bool $name
     */
    public function __construct(AppDb $db, $name=False) {
        parent::__construct($db, 'DigestMaker', $this->table_data);
        if ($name !== False) {
            $this->name = $name;
            $this->get($name);
        }
    }
    
    // MODEL

    /**
     * @return mixed
     */
    public function all() {
        $sql = "SELECT * FROM {$this->tablename} ORDER BY position ASC";
        $req = $this->db->send_query($sql);
        $data = array();
        while ($row = $req->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    /**
     * @param array $post
     * @return bool|mysqli_result
     */
    public function add(array $post) {
        $class_vars = get_class_vars(get_class());
        $content = $this->parsenewdata($class_vars,$post);
        return $this->db->addcontent($this->tablename,$content);
    }

    /**
     * @param $name
     * @return $this
     */
    public function get($name) {
        $sql = "SELECT * FROM {$this->tablename} WHERE name='{$name}'";
        $data = $this->db->send_query($sql)->fetch_assoc();
        if (!empty($data)) {
            foreach ($data as $prop=>$value) {
                $this->$prop = $value;
            }
            return $this;
        } else {
            return false;
        }
    }

    /**
     * @param $name
     */
    public function register($name) {
        if (!$this->get($name)) {
            $this->add(array('name'=>$name, 'display'=>0, 'position'=>0));
        }
    }
    
    /**
     * Update info
     * @param array $post
     * @param $name
     * @return bool
     */
    public function update(array $post, $name) {
        $class_vars = get_class_vars(get_class());
        $content = $this->parsenewdata($class_vars, $post);
        return $this->db->updatecontent($this->tablename,$content,array("name"=>$name));
    }

    /**
     * Delete from table
     * @param $name
     * @return bool|mysqli_result
     */
    public function delete($name) {
        return $this->db->deletecontent($this->tablename, array('name'), array($name));
    }
    
    
    // CONTROLLER

    /**
     * Renders digest email
     * @param string $username
     * @return mixed
     */
    public function makeDigest($username) {
        $user = new User($this->db, $username);
        $string = "";
        $data = $this->all();
        foreach ($data as $key=>$item) {
            if ($item['display'] == 1) {
                $section = new $item['name']($this->db);
                $string .= self::showSection($section->makeMail($username));
            }
        }

        $content['body'] = "
                <div style='width: 100%; margin: auto;'>
                    <p>Hello {$user->firstname},</p>
                    <p>This is your Journal Club weekly digest.</p>
                </div>
                {$string}
                ";
        $content['subject'] = "Reminder - ".date('d M Y');
        
        return $content;
    }

    /**
     * Show form
     * @return string
     */
    public function edit() {
        $data = $this->all();
        return self::form($data);
    }
    
    // VIEW

    /**
     * Renders positions input
     * @param array $data
     * @param $position
     * @return string
     */
    private static function getPositions(array $data, $position) {
        $nb_sections = count($data);
        $content = "";
        for ($i=0; $i<$nb_sections; $i++) {
            $selected = ($i == $position) ? "selected":null;
            $content .= "<option value='{$i}' {$selected}>{$i}</option>";
        }
        return $content;
    }

    /**
     * Renders digest section
     * @param array $data
     * @return string
     */
    public static function showSection(array $data) {
        return "
           <div style='display: block; padding: 10px; margin: 0 auto 20px auto; border: 1px solid #ddd; background-color: rgba(255,255,255,1);'>
                <div style='color: #444444; margin-bottom: 10px;  border-bottom:1px solid #DDD; font-weight: 500; font-size: 1.2em;'>
                    {$data['title']}
                </div>

                <div style='padding: 5px; background-color: rgba(255,255,255,.5); display: block;'>
                    {$data['body']}
                </div>
            </div>
        ";
    }

    /**
     * Renders Edit form
     * @param array $data
     * @return string
     */
    public static function form(array $data) {
        $content = "";
        foreach ($data as $key=>$info) {
            $positions = self::getPositions($data, $info['position']);
            $display = "";
            $opt = array('Yes'=>1, 'No'=>0);
            foreach ($opt as $label=>$value) {
                $selected = ($value == $info['display']) ? "selected":null;
                $display .= "<option value='{$value}' {$selected}>{$label}</option>";
            }
            $content .= "
            <div class='digest_section'>
                <div id='name'>{$info['name']}</div>
                <div id='form'>
                    <form method='post' action='php/form.php'>
                        <input type='hidden' name='modDigest' value='true'>
                        <input type='hidden' name='name' value='{$info['name']}'>
                        <div class='formcontrol'>
                            <label for='display'>Display</label>
                            <select name='display'>
                                {$display}
                            </select>
                        </div>
                        <div class='formcontrol'>
                            <label for='position'>Position</label>
                            <select name='position'>
                                {$positions}
                            </select>
                        </div>
                        <div id='submit'>
                            <input type='submit' class='processform' />
                        </div>
                    </form>
                </div>
            </div>
            ";
        }
        return $content;
    }
    
}