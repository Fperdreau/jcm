<?php

/**
 * Created by PhpStorm.
 * User: U648170
 * Date: 4-8-2015
 * Time: 12:48
 */
class AppPage extends AppTable {

    protected $table_data = array(
        "id"=>array('INT NOT NULL AUTO_INCREMENT',false),
        "name"=>array('CHAR(20)',false),
        "filename"=>array('CHAR(20)',false),
        "parent"=>array('CHAR(20)',false),
        "status"=>array('CHAR(10)',false),
        "rank"=>array('INT(2)',false),
        "meta_title"=>array('VARCHAR(255)',false),
        "meta_keywords"=>array('TEXT(1000)',false),
        "meta_description"=>array('TEXT(1000)',false),
        "primary"=>"id"
    );

    public $name;
    public $filename;
    public $parent;
    public $rank;
    public $status="";
    public $meta_title;
    public $meta_keywords;
    public $meta_description;

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
        var_dump($content);
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
        if (!isset($_SESSION['logok']) || $_SESSION['logok'] == false) {
            $result['msg'] = "
		    <div id='content'>
        		<p id='warning'>You must <a rel='leanModal' id='modal_trigger_login' href='#modal' class='modal_trigger'>log in</a> to access this page</p>
		        </p>
		    </div>
		    ";
            $result['status'] = false;
        } else {
            $levels = array('admin'=>3,'organizer'=>2,'member'=>1);
            $user = new User($this->db,$_SESSION['username']);
            if ($levels[$user->status]<$this->status) {
                $result['msg'] = "
                    <div id='content'>
                        <p id='warning'>Sorry, you do not have the permission to access this page</p>
                        </p>
                    </div>
                    ";
                $result['status'] = false;
            } else {
                $result['status'] = true;
                $result['msg'] = null;
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
            if (!empty($item) && !in_array($item,array('.','..','.htaccess','modal.php','verify.php','renew_pw.php'))) {
                $filename = explode('.',$item);
                $filename = $filename[0];
                $name = explode('_',$filename);
                if (count($name)>1 && $name[0] == "admin") {
                    $name = $name[1];
                    $status = "admin";
                } else {
                    $name = $name[0];
                    $status = "member";
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

}