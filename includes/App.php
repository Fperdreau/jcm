<?php

/**
 * Created by PhpStorm.
 * User: Florian
 * Date: 24/04/2017
 * Time: 17:51
 */
class App {

    /**
     * Application info
     */
    const app_name = "Journal Club Manager"; // Application's name
    const version = "1.5.0"; // Application's version
    const author = "Florian Perdreau"; // Application's authors
    const repository = "https://github.com/Fperdreau/jcm"; // Application's sources
    const copyright = "&copy; 2014-2017"; // Copyright
    const license = "GNU AGPL v3"; // License

    /**
     * @var $instance App
     */
    private static $instance;

    /**
     * Database instance
     * @var Db
     */
    private $db;

    /**
     * AppConfig instance
     * @var $config Settings
     */
    private static $config;

    /**
     * Application settings
     * @var $settings array
     */
    private $settings = array(
        'debug'=>'on',
        'status'=>'on',
        'site_url'=>null
    );

    /**
     * @var $site_url string: url to application
     */
    public static $site_url;

    /**
     * App constructor.
     * @param bool $get
     */
    private function __construct($get=true) {
        $this->db = Db::getInstance();
        if ($get) {
            $this->loadSettings();
        }
    }

    /**
     * Get Application instance
     * @param bool $get
     * @return App
     */
    public static function getInstance($get=true) {
        if (is_null(self::$instance)) {
            self::$instance = new self($get);
        }
        return self::$instance;
    }

    /**
     * Factory
     * @return Settings
     */
    private function getSettings() {
        if (is_null(self::$config)) {
            self::$config = new Settings(__CLASS__, $this->settings);
        }
        return self::$config;
    }

    /**
     * Update app settings
     * @param array $post
     * @return mixed
     */
    public function updateSettings(array $post) {
        return array('status' => self::getSettings()->update($post, array()));
    }

    /**
     * Get app setting
     * @param $setting
     * @return mixed|null
     */
    public function getSetting($setting) {
        if (key_exists($setting, $this->settings)) {
            return $this->settings[$setting];
        } else {
            return null;
        }
    }

    /**
     * Loads application settings
     */
    public function loadSettings() {
        $this->settings = self::getSettings()->settings;
        return $this->settings;
    }

    /**
     * Boot application
     * @param bool $debug
     */
    public static function boot($debug=false) {

        /**
         * Define timezone
         *
         */
        date_default_timezone_set('Europe/Paris');

        /**
         * Show PHP errors (debug mode only)
         */
        if ($debug) {
            error_reporting(E_ALL | E_STRICT);
            ini_set('display_errors', true);
        } else {
            ini_set('display_errors', false);
        }

        /**
         * Define paths
         */
        if(!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
        if(!defined('APP_NAME')) define('APP_NAME', basename(dirname(__DIR__)));
        if(!defined('PATH_TO_APP')) define('PATH_TO_APP', dirname(dirname(__FILE__)) . DS);
        if(!defined('PATH_TO_INCLUDES')) define('PATH_TO_INCLUDES', PATH_TO_APP. DS . 'includes' . DS);

        // Register autoloader
        require PATH_TO_INCLUDES . DS . 'Autoloader.php';
        Autoloader::register();

        // Register session and App url if not running in command line
        if (php_sapi_name() !== "cli") {

            // Start session
            SessionInstance::initsession();

            /**
             * Get App url
             */
            self::getAppUrl();
        }

        // Define paths
        if(!defined('PATH_TO_IMG')) define('PATH_TO_IMG', PATH_TO_APP. DS . 'images' . DS);
        if(!defined('PATH_TO_PHP')) define('PATH_TO_PHP', PATH_TO_APP . DS . 'php' . DS);
        if(!defined('PATH_TO_PAGES')) define('PATH_TO_PAGES', PATH_TO_APP . DS .'pages' . DS);
        if(!defined('PATH_TO_CONFIG')) define('PATH_TO_CONFIG', PATH_TO_APP . DS . 'config' . DS);
        if(!defined('PATH_TO_LIBS')) define('PATH_TO_LIBS', PATH_TO_APP . DS . 'libs' . DS);
        if(!defined('PATH_TO_TASKS')) define('PATH_TO_TASKS', PATH_TO_APP . DS . 'cronjobs' . DS);
        if(!defined('PATH_TO_PLUGINS')) define('PATH_TO_PLUGINS', PATH_TO_APP . DS . 'plugins' . DS);

        if(!defined('URL_TO_APP')) define('URL_TO_APP', self::$site_url);
        if(!defined('URL_TO_IMG')) define('URL_TO_IMG', URL_TO_APP . "/images/");

    }

    /**
     * This function gets App's URL to root
     * @param null $lang: language
     * @return string
     */
    public static function getAppUrl($lang=null) {
        if (php_sapi_name() !== 'cli') {
            if (is_null(self::$site_url) || !is_null($lang)) {
                $root = explode('/',  dirname($_SERVER['PHP_SELF']));
                $root = '/' . $root[1];
                self::$site_url = ( (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://' )
                    . $_SERVER['HTTP_HOST'] . $root .'/';

                if(substr(self::$site_url, -2) == '//') {
                    self::$site_url = substr(self::$site_url, 0, -1);
                }

                if (!(is_null($lang))) {
                    self::$site_url .= $lang.'/';
                }
            }
        } else {
            self::$site_url = self::getInstance()->getSettings()->settings['site_url'];
        }

        $_SESSION['BASE_URL'] = self::$site_url;
        return self::$site_url;
    }

    /**
     * Check if application is installed
     */
    public function isInstalled() {

    }

    /**
     * Install application
     * @return array
     */
    public function install() {
        // STEP 4: Configure database
        $op = $_POST['op'] === "new";
        $App = self::getInstance(!$op);

        // Install database
        $result = self::install_db($op);
        if ($result['status'] === false) {
            return $result;
        }

        // Install of components
        $result = self::setup($op);
        if ($result['status'] === false) {
            return $result;
        }

        $_POST['version'] = self::version;
        $_POST{"site_url"} = URL_TO_APP;

        // Load and update application settings
        $App->loadSettings();
        $App->updateSettings($_POST);

        self::register_settings($_POST);

        $result['msg'] = "Database installation complete!";
        $result['status'] = true;
        Logger::get_instance(APP_NAME, 'Install')->info($result['msg']);

        return $result;
    }

    /**
     * Register all models to db
     * @param $op : do we make a new installation (overwriting pre-existent data)
     * @return array
     */
    public static function install_db($op) {
        $result = array('status'=>true, 'msg'=>null);
        // Install all tables
        $includeList = scandir(PATH_TO_INCLUDES);
        foreach ($includeList as $includeFile) {
            if (!in_array($includeFile, array('.', '..', 'App.php', 'BaseModel.php'))) {
                $class_name = explode('.', $includeFile);
                Logger::get_instance(APP_NAME)->debug("Calling {$class_name[0]}->install_db()");
                $result = self::call($class_name[0], 'install_db', $op);
                if ($result['status'] === false) {
                    return $result;
                }
            }
        }
        return $result;
    }

    /**
     * Execute post-installation
     * @param $op
     * @return array
     */
    public static function setup($op) {
        $result = array('status'=>true, 'msg'=>null);
        // Install all tables
        $includeList = scandir(PATH_TO_INCLUDES);
        foreach ($includeList as $includeFile) {
            if (!in_array($includeFile, array('.', '..', 'App.php', 'BaseModel.php'))) {
                $class_name = explode('.', $includeFile);
                Logger::get_instance(APP_NAME)->debug("Calling {$class_name[0]}->setup()");
                $result = self::call($class_name[0], 'setup', $op);
                if ($result['status'] === false) {
                    return $result;
                }
            }
        }
        return $result;
    }

    /**
     * @param $class_name
     * @param $action
     * @param null $param
     * @return array
     */
    public static function call($class_name, $action, $param=null) {
        $result = array('status'=>true, 'msg'=>null);
        if (class_exists($class_name, true)) {
            if (method_exists($class_name, $action)) {
                $obj = new $class_name();
                try {
                    $obj->$action($param);
                } catch (Exception $e) {
                    $result['msg'] = "Something went wrong while calling {$class_name}->{$action}: {$e}";
                    $result['status'] = false;
                    return $result;
                }
            }
        }
        return $result;
    }

    /**
     * Install application
     * @param $op : do we make a new installation (overwriting pre-existent data)
     * @return array
     */
    public static function register_settings($op) {
        $result = array('status'=>true, 'msg'=>null);
        // Install all tables
        $includeList = scandir(PATH_TO_INCLUDES);
        foreach ($includeList as $includeFile) {
            if (!in_array($includeFile, array('.', '..', 'BaseModel.php'))) {
                $class_name = explode('.', $includeFile);
                Logger::get_instance(APP_NAME)->debug("Calling {$class_name[0]}->updateSettings()");

                $result = self::call($class_name[0], 'updateSettings', $op);
                if ($result['status'] === false) {
                    return $result;
                }
            }
        }
        return $result;
    }

    /**
     * Browse release content and returns associative array with folders name as keys
     * @param $dir
     * @param array $foldertoexclude
     * @param array $filestoexclude
     * @return mixed
     */
    private static function browsecontent($dir, $foldertoexclude=array(), $filestoexclude=array()) {
        $content[$dir] = array();
        if ($handle = opendir($dir)) {
            while (false !== ($file = readdir($handle))) {
                $filename = $dir."/".$file;
                if ($file != "." && $file != ".." && is_file($filename) && !in_array($filename,$filestoexclude)) {
                    $content[$dir][] = $filename;
                } else if ($file != "." && $file != ".." && is_dir($dir.$file) && !in_array($dir.$file, $foldertoexclude) ) {
                    $content[$dir] = self::browsecontent($dir.$file,$foldertoexclude,$filestoexclude);
                }
            }
            closedir($handle);
        }
        return $content;
    }

    // VIEWS
    /**
     * Render settings form
     * @param array $settings
     * @return array
     */
    public static function settingsForm(array $settings) {
        return array(
            'title'=>'JCM settings',
            'body'=>"
                    <form method='post' action='php/router.php?controller=App&action=updateSettings'>
                        <div class='submit_btns'>
                            <input type='submit' name='modify' value='Modify' class='processform'>
                        </div>
                        <input type='hidden' name='config_modify' value='true'/>
                        <div class='form-group'>
                            <select name='status'>
                                <option value='{$settings['status']}' selected>{$settings['status']}</option>
                                <option value='On'>On</option>
                                <option value='Off'>Off</option>
                            </select>
                            <label>Status</label>
                        </div>
                        <div class='form-group'>
                            <select name='debug'>
                                <option value='{$settings['debug']}' selected>{$settings['debug']}</option>
                                <option value='On'>On</option>
                                <option value='Off'>Off</option>
                            </select>
                            <label>Debug</label>
                        </div>
                        <div class='feedback' id='feedback_site'></div>
                    </form>
            ");
    }

}