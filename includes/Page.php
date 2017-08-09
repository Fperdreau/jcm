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
class Page extends BaseModel {

    // Access levels
    public static $levels = array('none'=>-1,'member'=>0,'organizer'=>1,'admin'=>2);

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
     * @param bool $name
     */
    public function __construct($name=False) {
        parent::__construct();
        if ($name !== False) {
            $this->getInfo($name);
        }
    }

    /**
     * Install Page Table
     * @param bool $op
     * @return bool
     */
    public function setup($op = False) {
        return $this->getPages() !== false;
    }

    /**
     * Register page to the table
     * @param array $post
     * @return bool|mysqli_result
     */
    public function make($post=array()) {
        $class_vars = get_class_vars('Page');
        $content = $this->parsenewdata($class_vars,$post, array('levels'));
        return $this->db->insert($this->tablename,$content);
    }

    /**
     * Get info from the Page table
     * @param null $name
     */
    public function getInfo($name=null) {
        $this->name = ($name == null) ? $this->name : $name;
        $data = $this->get(array('name'=>$this->name));
        if (!empty($data)) {
            $this->map($data);
        }
    }

    /**
     * Check if this plugin is registered to the db
     */
    public function isInstalled() {
        return $this->is_exist(array('name'=>$this->name));
    }

    /**
     * Check whether the user is logged in and has permissions
     * @return bool
     */
    public function check_login() {
        $split = explode('\\', $this->name);
        $page_level = (in_array($split[0], array_keys(self::$levels))) ? $split[0] : 'none';
        if (!Auth::is_logged()) {
            if (self::$levels[$page_level] > -1) {
                $result['msg'] = self::login_required();
                $result['status'] = false;
            } else {
                $result['status'] = true;
                $result['msg'] = null;
            }
        } else {
            $user = new Users($_SESSION['username']);
            if (self::$levels[$user->status]>=self::$levels[$page_level] || self::$levels[$page_level] == -1) {
                $result['status'] = true;
                $result['msg'] = $user->status;
            } else {
                $result['msg'] = self::forbidden();
                $result['status'] = false;
            }
        }
        return $result;
    }

    /**
     * Check if page exists
     * @param $page: page name
     * @return bool
     */
    public static function exist($page) {
        return is_file(PATH_TO_PAGES . $page . '.php');
    }

    /**
     * Forbidden access
     * @return array
     */
    public static function forbidden() {
        $result['content'] = self::render('error/403');
        $result['title'] = 'Error 403';
        $result['header'] = self::header('Error 403', 'error');
        return $result;
    }

    /**
     * Render Error 404 - page not found
     * @return array
     */
    public static function notFound() {
        $result['content'] = self::render('error/404');
        $result['title'] = 'Error 404';
        $result['header'] = self::header('Error 404', 'error');
        return $result;
    }

    /**
     * Render Error 503
     * @return array
     */
    public static function maintenance() {
        $result['content'] = self::render('error/503');
        $result['title'] = 'Error 503';
        $result['header'] = self::header('Error 503', 'error');
        return $result;
    }

    /**
     * Render page header
     * @param string $icon: icon name
     * @param string $title: page title
     * @return string
     */
    public static function header($title, $icon=null) {
        if (is_null($icon)) {
            $icon = $title;
        }

        return "<div id='page_icon'><img src='images/{$icon}_bk_40x40.png'></div><div><h1>{$title}</h1></div>";
    }

    /**
     * Render page content
     * @param $page
     * @return mixed|string
     */
    public static function render($page) {

        // Start buffering
        ob_start("ob_gzhandler");

        require(PATH_TO_PAGES . $page . '.php');

        // End of buffering
        return ob_get_clean();

    }

    /**
     * Login requested
     * @return string
     */
    public static function login_required() {
        $result['content'] = self::render('error/401');
        $result['title'] = 'Restricted area';
        $result['header'] = self::header('Restricted area', 'error');
        return $result;
    }

    /**
     * Get application pages
     * @return bool|array: pages information if success, False otherwise
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
        return $this->db->column($this->tablename, 'name');
    }

    /**
     * Browse the View directory
     * @param $dir
     * @param null $parent
     * @param array $excludes
     * @return bool|array
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
                if (!$this->is_exist(array('name'=>$page_name))) {
                    if ($element == "admin" || $parent == "admin") {
                        $status = 2;
                    } elseif ($element == "organizer" || $parent == "organizer") {
                        $status = 1;
                    } elseif ($element == "member") {
                        $status = 0;
                    } else {
                        $status = -1;
                    }
                    $name = (!is_null($parent)) ? $parent . DS .$element : $element;
                    if (!$this->make(array(
                        'name'=>$name,
                        'filename'=>$url,
                        'status'=>$status,
                        'parent'=>$parent,
                        'show_menu'=>1,
                        'rank'=>$rank))) {
                        return False;
                    };
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
                $this->db->delete($this->tablename, array('name'=>$page));
            }
        }
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
                    <li class='menu-section'><a href='index.php?page=organizer/sessions' id='sessions'>Sessions</a></li>
                    <li class='menu-section'><a href='index.php?page=organizer/digest' id='digest'>Digest</a></li>
                    <li class='menu-section'><a href='index.php?page=organizer/reminder' id='reminder'>Reminder</a></li>
                    <li class='menu-section'><a href='index.php?page=organizer/assignment' id='assignment'>Assignments</a></li>
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
                <li class='menu-section'><a href='index.php?page=admin/settings' id='settings'>Settings</a></li>
                <li class='menu-section'><a href='index.php?page=admin/users' id='users'>Users</a></li>
                <li class='menu-section'><a href='index.php?page=admin/plugins' id='plugins'>Plugins</a></li>
                <li class='menu-section'><a href='index.php?page=admin/tasks' id='tasks'>Scheduled Tasks</a></li>
                <li class='menu-section'><a href='index.php?page=admin/logs' id='logs'>System logs</a></li>
            </ul>
        </nav>
        ";
    }

    /**
     * Render menu section for registered members
     * @return string
     */
    public static function member_menu() {
        return "
        <li class='main_section' id='tools'><a href='#' class='submenu_trigger' id='addmenu-member'>My tools</a></li>
        <nav class='submenu' id='addmenu-member'>
            <ul>
                <li class='menu-section'><a href='index.php?page=member/submission' id='submission'>submit a presentation</a></li>
                <li class='menu-section'><a href='index.php?page=member/email' id='email'>email</a></li>
                <li class='menu-section'><a href='index.php?page=member/news' id='news'>News</a></li>
                <li class='menu-section'><a href='index.php?page=member/archives' id='archives'>archives</a></li>
            </ul>
        </nav>
        ";
    }

    /**
     * Render help menu
     * @return string
     */
    public static function help_menu() {
        return "
        <li class='main_section' id='help'><a href='#' class='submenu_trigger' id='addmenu-help'>Help</a></li>
        <nav class='submenu' id='addmenu-help'>
            <ul>
                <li class='menu-section'><a href='index.php?page=help' id='help'>Help</a></li>
                <li class='menu-section'><a href='index.php?page=about' id='email'>About JCM</a></li>
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
        $member_menu = null;
        if (isset($_SESSION['logok']) && $_SESSION['logok']) {
            $member_menu = self::member_menu();
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
                    <li class='main_section menu-section' id='home'><a href='index.php?page=home' id='home'>home</a></li>
                    <li class='main_section menu-section' id='news'><a href='index.php?page=news' id='news'>news</a></li>
                    <li class='main_section menu-section' id='contact'><a href='index.php?page=contact' id='contact'>contact</a></li>
                    {$member_menu}
                    {$organizer}
                    {$admin}
                    " . self::help_menu() . "
                </ul>
            </nav>
            
        ";
    }

}