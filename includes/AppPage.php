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
        "filename"=>array('CHAR(20)',false),
        "parent"=>array('CHAR(20)',false),
        "status"=>array('INT(2)',false),
        "rank"=>array('INT(2)',false),
        "show_menu"=>array('INT(1)',false),
        "meta_title"=>array('VARCHAR(255)',false),
        "meta_keywords"=>array('TEXT(1000)',false),
        "meta_description"=>array('TEXT(1000)',false),
        "primary"=>"id"
    );

    public $name; // Page name
    public $filename; // Page file
    public $parent; // Page's parent
    public $rank; // Order of apparition in menu
    public $show_menu; // Show (1) or not in menu
    public $status=""; // Permission level
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
        $this->name = ($name == null) ? $this->name:$name;
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
     * Delete tasks from the Page table
     * @return bool|mysqli_result
     */
    public function delete() {
        $this->db->deletecontent($this->tablename,array('name'),array($this->name));
        return $this->db->deletetable($this->tablename);
    }

    /**
     * Check if this plugin is registered to the db
     */
    public function isInstalled() {
        $plugins = $this->db->getinfo($this->db->tablesname['Pages'],'name');
        return in_array($this->name,$plugins);
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
     * Get list of pages, their settings and status
     * @return array
     */
    public function getPages() {
        // First cleanup Page table
        $this->cleanup();

        // Second, install new pages if there are any
        $folder = PATH_TO_PAGES;
        $content = scandir($folder);
        $pages = array();
        foreach ($content as $item) {
            if (!empty($item) && !in_array($item,array('.','..','.htaccess','modal.php'))) {
                $filename = explode('.',$item);
                $filename = $filename[0];
                $name = explode('_',$filename);
                if (count($name)>1 && $name[0] == "admin") {
                    $name = $name[1];
                    $status = 2;
                } else {
                    $name = $name[0];
                    $status = -1;
                }
                $thisPage = new self($this->db, $name);
                if (!$thisPage->isInstalled()) {
                    $thisPage->make(array('filename'=>$filename,'status'=>$status));
                }
                $pages[] = $name;
            }
        }
        return $pages;
    }

    /**
     * Clean up Pages table. Remove pages from the DB if their are not present in the Pages folder
     */
    public function cleanup() {
        $folder = PATH_TO_PAGES;
        $content = scandir($folder);
        $sql = "SELECT name from $this->tablename";
        $req = $this->db->send_query($sql);
        while ($row = mysqli_fetch_assoc($req)) {
            $page = new self($this->db,$row['name']);
            if (!in_array($page->filename.".php",$content)) {
                $page->delete();
            }
        }
    }

    /**
     * Build submenus
     * @param $page
     */
    private function buildMenuSection($page) {
        //todo:create recursive function sorting pages hierarchically
    }

    /**
     * Build Menu & sub-menus
     * @return string
     */
    public function buildMenu() {
        $userStatus = $this->check_login();
        $sql = "SELECT * from $this->tablename ORDER by rank";
        $req = $this->db->send_query($sql);
        $sections = "";
        $processed_pages = array();
        $subMenus = "";
        while ($row = mysqli_fetch_assoc($req)) {
            $page = $row['name'];

            // Only process the page if we haven't done it already
            if (!in_array($page,$processed_pages)) {
                $curPage = new self($this->db,$page);

                // Get children pages
                $sql = "SELECT name from $this->tablename WHERE parent='$curPage->name' ORDER by rank";
                $data = $this->db->send_query($sql);
                $children = array();
                while ($child = mysqli_fetch_assoc($data)) {
                    $children[] = $child['name'];
                }

                if (!empty($children)) {
                    $navContent = "";
                    foreach ($children as $child) {
                        $thisChild = new self($this->db, $child);
                        $url = "index.php?page=$thisChild->filename";
                        $navContent .= "<li id='$thisChild->filename'><a href='$url' class='menu_section' id='$thisChild->filename'>$thisChild->name</a></li>";
                        $processed_pages[] = $thisChild->name;
                    }
                    $subMenus .= "<nav class='submenu' id='$curPage->name'>$navContent</nav>";
                    $url = "index.php?page=$curPage->filename";
                    $sections .= "<li id='$curPage->filename'><a href='$url' class='submenu_trigger' id='$curPage->filename'>$curPage->name</a></li>";
                } else {
                    $url = "index.php?page=$curPage->filename";
                    $sections .= "<li id='$curPage->filename'><a href='$url' class='menu_section' id='$curPage->filename'>$curPage->name</a></li>";
                }
                $processed_pages[] = $page;
            }
        }
        $result = "
        <nav>
            <ul>
                $sections
            </ul>
        </nav>
        $subMenus";
        return $result;
    }

    /**
     * Get Page content
     * @return mixed
     */
    public function display() {
        // Check if user is logged in and has permissions to access the page
        $userStatus = $this->check_login();
        if ($userStatus['status'] === false) {
            return $userStatus['msg'];
        } else {
            include(PATH_TO_PAGES."$this->filename.php");
            return $result;
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

}