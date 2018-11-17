<?php
/**
 * File for class BaseMailMaker
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

/**
 * Class DigestMaker
 */
abstract class BaseMailMaker extends BaseModel
{

    public $name;
    public $position;
    public $display;

    /**
     * Constructor
     * @param bool $name
     */
    public function __construct($name = false)
    {
        parent::__construct();
        if ($name !== false) {
            $this->name = $name;
            $this->get(array('name'=>$name));
        }
    }

    // CONTROLLER

    /**
     * Render Mail maker index page
     *
     * @return string
     */
    public function index()
    {
        $section = Template::section(array(
            'body'=>$this->edit(),
            'title'=>'Email sections'
        ));
        return $this->pageTemplate($section);
    }

    /**
     * Install ReminderMaker
     * @param bool $op
     * @return bool
     */
    public function setup($op = false)
    {
        return self::registerAll();
    }

    /**
     * Modify information
     *
     * @param array $data
     * @return void
     */
    public function modify(array $data)
    {
        return array('status'=> $this->update(
            $data,
            array('name'=>$data['name'])
        ));
    }

    /**
     * Renders digest email
     * @param string $username
     * @return mixed
     */
    public function makeMail($username)
    {
        $user = new Users($username);
        $string = "";
        foreach ($this->all() as $key => $item) {
            if ($item['display'] == 1) {
                if (class_exists($item['name'])) {
                    $section = new $item['name']();
                    if (method_exists($section, 'makeMail')) {
                        $string .= self::showSection($section->makeMail($username));
                    }
                }
            }
        }

        $content['body'] = $this::body($user, $string);
        $content['subject'] = $this::header();

        return $content;
    }

    /**
     * Show form
     * @return string
     */
    public function edit()
    {
        $data = $this->all(
            array(),
            array('dir'=>'asc', 'order'=>'position')
        );
        return self::form($data);
    }

    // MODEL
    
    /**
     * Get info from db
     *
     * @param $name
     * @return $this|bool
     */
    public function getInfo($name)
    {
        $data = $this->get(array('name'=>$name));
        if (!empty($data)) {
            $this->map($data);
            return $this;
        } else {
            return false;
        }
    }

    /**
     * Register module to db
     * @param $name: module name
     * @return void
     */
    public function register($name)
    {
        if (!$this->getInfo($name)) {
            if ($this->add(array('name'=>$name, 'display'=>0, 'position'=>0))) {
                Logger::getInstance(APP_NAME, get_class($this))->info("'{$name}' successfully registered into table");
            } else {
                Logger::getInstance(APP_NAME, get_class($this))->error("'{$name}' NOT registered into table");
            }
        }
    }

    /**
     * Search for module that must be registered.
     * @return bool
     */
    public static function registerAll()
    {
        $includeList = scandir(PATH_TO_INCLUDES);
        foreach ($includeList as $includeFile) {
            if (!in_array($includeFile, array('.', '..'))) {
                $class_name = explode('.', $includeFile);
                if (method_exists($class_name[0], 'registerDigest')) {
                    $class_name[0]::registerDigest();
                }
            }
        }
        return true;
    }

    /**
     * Preview reminder email
     *
     * @param array $data
     * @return array
     */
    public function preview()
    {
        $result = $this->makeMail($_SESSION['username']);
        $AppMail = new MailManager();
        return $AppMail->formatmail($result['body']);
    }

    // VIEW
    /**
     * Index page template
     *
     * @param string $section
     * @return string
     */
    private function pageTemplate($section)
    {
        return "
            <div class='page_header'>
                " . $this::pageHeader() . "
            </div>
            {$section}
            <div class='submit_btns'>
                <input type='submit' value='Preview' class='loadContent' 
                    data-url='php/router.php?controller=" . get_called_class() . "&action=preview' 
                    data-destination='.mail_preview_container' />
            </div>
            <section class='mail_preview_container' style='display: none;'>
            </section> 
            ";
    }

    /**
     * Renders positions input
     * @param array $data
     * @param $position
     * @return string
     */
    private static function getPositions(array $data, $position)
    {
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
    public static function showSection(array $data)
    {
        if (empty($data)) {
            return null;
        }
        return "
           <div style='display: block; padding: 10px; margin: 0 auto 20px auto; border: 1px solid #ddd; 
           background-color: rgba(255,255,255,1);'>
                <div style='color: #444444; margin-bottom: 10px;  border-bottom:1px solid #DDD; font-weight: 500; 
                font-size: 1.2em;'>
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
    public static function form(array $data)
    {
        $content = "";
        foreach ($data as $key => $info) {
            $positions = self::getPositions($data, $info['position']);
            $display = "";
            $opt = array('Yes'=>1, 'No'=>0);
            foreach ($opt as $label => $value) {
                $selected = ($value == $info['display']) ? "selected":null;
                $display .= "<option value='{$value}' {$selected}>{$label}</option>";
            }
            $content .= self::formSection($info, $positions, $display);
        }
        return $content;
    }

    /**
     * Render form section
     *
     * @param array $info
     * @param string $positions: position input
     * @param string $display: display input
     * @return void
     */
    protected static function formSection(array $info, $positions, $display)
    {
        return "
            <div class='digest_section'>
                <div id='name'>{$info['name']}</div>
                <div id='form'>
                    <form method='post' action='php/router.php?controller=" . get_called_class() . "&action=modify'>
                        <input type='hidden' name='name' value='{$info['name']}'>
                        <div class='form-group inline_field field_auto'>
                            <select name='display'>
                                {$display}
                            </select>
                            <label for='display'>Display</label>
                        </div>
                        <div class='form-group inline_field field_auto'>
                            <select name='position'>
                                {$positions}
                            </select>
                            <label for='position'>Position</label>
                        </div>
                        <div id='submit'>
                            <input type='submit' value='Ok' class='processform' />
                        </div>
                    </form>
                </div>
            </div>
            ";
    }

    /**
     * Page description
     *
     * @return string
     */
    protected static function pageHeader()
    {
        throw new Exception('Must be implemented by child class');
    }


    /**
     * Email header
     *
     * @return string
     */
    protected static function header()
    {
        throw new Exception('Must be implemented by child class');
    }

    /**
     * Email body
     *
     * @param Users $user
     * @param string $content: email content
     * @return string
     */
    protected static function body(Users $user, $content)
    {
        throw new Exception('Must be implemented by child class');
    }
}
