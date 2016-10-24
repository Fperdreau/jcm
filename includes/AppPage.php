<?php
/**
 * File for class AppPage
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
 * Class AppPage
 *
 * Handle pages' settings, meta-information, display and menu
 */
class AppPage extends AppTable {

    protected $table_data = array(
        "id"=>array('INT NOT NULL AUTO_INCREMENT',false),
        "name"=>array('CHAR(20)',false),
        "filename"=>array('CHAR(255)',false),
        "parent"=>array('CHAR(255)',false),
        "status"=>array('INT(2)',false),
        "rank"=>array('INT(2)', 0),
        "show_menu"=>array('INT(1)',false),
        "meta_title"=>array('VARCHAR(255)',false),
        "meta_keywords"=>array('TEXT(1000)',false),
        "meta_description"=>array('TEXT(1000)',false),
        "primary"=>"id"
    );

    public $name; // Page name
    public $filename; // Page file
    public $parent; // Page's parent
    public $rank = 0; // Order of apparition in menu
    public $show_menu = 1; // Show (1) or not in menu
    public $status; // Permission level
    public $meta_title; // Page's title
    public $meta_keywords; // Page's keywords
    public $meta_description; // Page's description

    /**
     * Constructor
     * @param AppDb $db
     * @param bool $name
     */
    public function __construct(AppDb $db, $name=False) {
        parent::__construct($db, 'Pages', $this->table_data);
        if ($name !== False) {
            $this->get($name);
        }
    }

    /**
     * Register cronjobs to the table
     * @param array $post
     * @return bool|mysqli_result
     */
    public function make($post=array()) {
        $class_vars = get_class_vars('AppPage');
        $content = $this->parsenewdata($class_vars,$post);
        return $this->db->addcontent($this->tablename,$content);
    }

    /**
     * Get info from the Page table
     * @param null $name
     */
    public function get($name=null) {
        $this->name = ($name == null) ? $this->name : $name;
        $sql = "SELECT * FROM $this->tablename WHERE name='$this->name'";
        $req = $this->db->send_query($sql);
        $data = mysqli_fetch_assoc($req);
        if (!empty($data)) {
            foreach ($data as $prop => $value) {
                $this->$prop = $value;
            }
        }
    }

    /**
     * Update Page table
     * @param array $post
     * @return bool
     */
    public function update($post=array()) {
        $class_vars = get_class_vars('AppPage');
        $content = $this->parsenewdata($class_vars,$post);
        return $this->db->updatecontent($this->tablename,$content,array("name"=>$this->name));
    }

    /**
     * Check if this plugin is registered to the db
     */
    public function isInstalled() {
        $plugins = $this->db->getinfo($this->db->tablesname['Pages'], 'name');
        return in_array($this->name,$plugins);
    }

    /**
     * Checks if id exists in a column
     * @param string $name: page name
     * @return bool
     */
    public function is_exist($name) {
        $data = $this->db->getinfo($this->tablename, 'name');
        return in_array($name, $data);
    }

    /**
     * Check whether the user is logged in and has permissions
     * @return bool
     */
    public function check_login() {
        if ((!isset($_SESSION['logok']) || $_SESSION['logok'] == false)) {
            if ($this->status > -1) {
                $result['msg'] = "
		    <div id='content'>
        		<p class='sys_msg warning'>You must <a class='leanModal' id='user_login' href='' data-section='user_login'>
        		Sign In</a> or <a class='leanModal' id='user_register' href='' data-section='user_register'>
        		Sign Up</a> in order to access this page!</p>
		    </div>
		    ";
                $result['status'] = false;
            } else {
                $result['status'] = true;
                $result['msg'] = null;
            }
        } else {
            $levels = array('none'=>-1,'member'=>0,'organizer'=>1,'admin'=>2);
            $user = new User($this->db,$_SESSION['username']);
            if ($levels[$user->status]>=$this->status || $this->status == -1) {
                $result['status'] = true;
                $result['msg'] = null;
            } else {
                $result['msg'] = "
                    <div id='content'>
                        <p class='sys_msg warning'>Sorry, you do not have the permission to access this page</p>
                    </div>
                    ";
                $result['status'] = false;
            }
        }
        return $result;
    }

    /**
     * Get application pages
     * @return array
     */
    public function getPages() {
        // First cleanup Page table
        $this->cleanup();

        // Second, install new pages if there are any
        $pages = $this->browse(PATH_TO_PAGES, null, array('modal'));
        return $pages;
    }

    /**
     * Gets list of pages registered in the database
     * @return mixed
     */
    public function getInstalledPages() {
        return $this->db->getinfo($this->tablename, 'name');
    }

    /**
     * Browse the View directory
     * @param $dir
     * @param null $parent
     * @param array $excludes
     * @return array
     */
    private function browse($dir, $parent=null, array $excludes=array()) {
        $content = scandir($dir);
        $temp_dir = array();
        $rank = 0;
        foreach ($content as $element) {
            if (is_file($dir . DS . $element) && !in_array($element, array_merge(array('.', '..'), $excludes))) {
                // Register page into the database if it is not
                $split = explode('.', $element);
                $element = $split[0];
                $page_name = (is_null($parent)) ? $element: $parent . DS . $element;
                $url = (is_null($parent)) ? URL_TO_APP . $element : URL_TO_APP . $parent . DS . $element;
                if (!$this->is_exist($page_name)) {
                    if ($element == "admin" || $parent == "admin") {
                        $status = 2;
                    } elseif ($element == "organizer" || $parent == "organizer") {
                        $status = 1;
                    } else {
                        $status = -1;
                    }
                    $name = (!is_null($parent)) ? $parent . DS .$element : $element;
                    $this->make(array(
                        'name'=>$name,
                        'filename'=>$url,
                        'status'=>$status,
                        'parent'=>$parent,
                        'show_menu'=>1,
                        'rank'=>$rank));
                }
            } elseif (is_dir($dir . DS . $element) && !in_array($element, array_merge(array('.', '..'), $excludes))) {
                $temp_dir[$element] = $this->browse($dir . DS . $element, $element, $excludes);
            }
            ++$rank;
        }
        return $temp_dir;
    }

    /**
     * Clean up Pages table. Remove pages from the DB if they are not present in the Views folder
     */
    public function cleanup() {
        $folder = PATH_TO_PAGES;
        foreach ($this->getInstalledPages() as $page) {
            $path = $folder . strtolower($page) .DS;
            if (!is_dir($path)) {
                $this->db->deletecontent($this->tablename, 'name', $page);
            }
        }
    }

    /**
     * Show page settings
     * @return string
     */
    public function showOpt() {
        $pages = $this->getPages();
        $sql = "SELECT name FROM ".$this->tablename;
        $req = $this->db->send_query($sql);
        $pageSettings = "";
        while ($row = mysqli_fetch_assoc($req)) {
            $pageName = $row['name'];
            $thisPage = new AppPage($this->db,$pageName);
            $pageList = "<option value='none'>None</option>";
            foreach ($pages as $name) {
                $selectOpt = ($name == $thisPage->parent) ? "selected":"";
                $pageList .= "<option value='$name' $selectOpt>$name</option>";
            }

            $statusList = "";
            $status = array('none'=>-1,'member'=>0,'organizer'=>1,'admin'=>2);
            foreach ($status as $statusName=>$int) {
                $selectOpt = ($int == $thisPage->status) ? "selected":"";
                $statusList .= "<option value='$int' $selectOpt>$statusName</option>";
            }

            $rankList = "";
            for ($i=0;$i<count($pages);$i++) {
                $selectOpt = ($i == $thisPage->rank) ? "selected":"";
                $rankList .= "<option value='$i' $selectOpt>$i</option>";
            }

            $showList = "";
            $showOpt = array("no"=>0,"yes"=>1);
            foreach ($showOpt as $opt=>$value) {
                $selectOpt = ($value == $thisPage->show_menu) ? "selected":"";
                $showList .= "<option value='$value' $selectOpt>$opt</option>";
            }

            $pageSettings .= "
            <div class='plugDiv' id='page_$pageName'>
                <div class='plugLeft' style='width: 200px;'>
                    <div class='plugName'>$pageName</div>
                </div>

                <div class='plugSettings'>
                    <form method='post' action='php/form.php' id='config_page_$pageName'>
                        <input type='hidden' value='true' name='modPage' />
                        <input type='hidden' value='$pageName' name='name' />
                        <div style='display: inline-block'>
                            <div class='formcontrol'>
                                <label>Status</label>
                                <select class='select_opt' name='status'>
                                    $statusList
                                </select>
                            </div>
                            <div class='formcontrol'>
                                <label>Rank</label>
                                <select class='select_opt' name='rank'>
                                    $rankList
                                </select>
                            </div>
                            <div class='formcontrol'>
                                <label>Show in menu</label>
                                <select class='select_opt' name='show_menu'>
                                    $showList
                                </select>
                            </div>

                            <div class='formcontrol'>
                                <label>Title</label>
                                <input type='text' name='meta_title' value='$thisPage->meta_title'>
                            </div>
                            <div class='formcontrol'>
                                <label>Description</label>
                                <input type='text' name='meta_description' value='$thisPage->meta_description'>
                            </div>
                            <div class='formcontrol'>
                                <label>Keywords</label>
                                <input type='text' name='meta_keywords' value='$thisPage->meta_keywords'>
                            </div>
                        </div>

                        <div class='submit_btns'>
                            <input type='submit' value='Modify' class='processform'/>
                        </div>
                    </form>
                </div>
            </div>
            ";
        }
        return $pageSettings;
    }

    /**
     * Render organizer menu
     * @return string
     */
    public static function organizer_menu() {
        return "
            <li class='main_section' id='organizer'><a href='#' class='submenu_trigger' id='addmenu-organizer'>organizer</a></li>   
            <nav class='submenu' id='addmenu-organizer'>
                <ul>
                    <li><a href='index.php?page=sessions' class='menu-section' id='sessions'>Sessions</a></li>
                    <li><a href='index.php?page=users' class='menu-section' id='users'>Users</a></li>
                    <li><a href='index.php?page=email' class='menu-section' id='email'>Mailing</a></li>
                    <li><a href='index.php?page=post' class='menu-section' id='post'>Posts</a></li>
                    <li><a href='index.php?page=digest' class='menu-section' id='digest'>Digest</a></li>
                    <li><a href='index.php?page=reminder' class='menu-section' id='reminder'>Reminder</a></li>
                    <li><a href='index.php?page=assignment' class='menu-section' id='assignment'>Assignments</a></li>
                </ul>
            </nav>
        ";
    }

    /**
     * Render admin menu
     * @return string
     */
    public static function admin_menu() {
        return "
        <li class='main_section' id='admin'><a href='#' class='submenu_trigger' id='addmenu-admin'>admin</a></li>
        <nav class='submenu' id='addmenu-admin'>
            <ul>
                <li><a href='index.php?page=settings' class='menu-section' id='settings'>Settings</a></li>
                <li><a href='index.php?page=pages' class='menu-section' id='pages'>Pages</a></li>
                <li><a href='index.php?page=plugins' class='menu-section' id='plugins'>Plugins</a></li>
                <li><a href='index.php?page=tasks' class='menu-section' id='tasks'>Scheduled Tasks</a></li>
                <li><a href='index.php?page=logs' class='menu-section' id='logs'>System logs</a></li>
            </ul>
        </nav>
        ";
    }

    /**
     * Render main menu
     * @return string
     */
    public static function menu() {
        $organizer = null;
        $admin = null;
        if (isset($_SESSION['logok']) && $_SESSION['logok']) {
            if (isset($_SESSION['status']) && in_array($_SESSION['status'], array('organizer', 'admin'))) {
                $organizer = self::organizer_menu();
            }

            if (isset($_SESSION['status']) && $_SESSION['status'] === 'admin') {
                $admin = self::admin_menu();
            }
        }

        return "
            <nav>
                <ul>
                    <li class='main_section' id='home'><a href='index.php?page=home' class='menu-section' id='home'>home</a></li>
                    <li class='main_section' id='submission'><a href='index.php?page=submission' class='menu-section' id='submission'>submit</a></li>
                    <li class='main_section' id='news'><a href='index.php?page=news' class='menu-section' id='news'>news</a></li>
                    <li class='main_section' id='archives'><a href='index.php?page=archives' class='menu-section' id='archives'>archives</a></li>
                    <li class='main_section' id='contact'><a href='index.php?page=contact' class='menu-section' id='contact'>contact</a></li>
                    {$organizer}
                    {$admin}
                </ul>
            </nav>
            
        ";
    }

}