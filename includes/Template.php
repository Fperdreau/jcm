<?php

namespace includes;

/**
 * Page Template class
 *
 * @package Includes
 * @author  Florian Perdreau <fp@florianperdreau.fr>
 * @license AGPL
 */
class Template
{

    /**
     * Render page layout
     *
     * @return string
     */
    public static function layout()
    {
        $menu = Page::menu();

        return "
        <!DOCTYPE html>
            <html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' lang='en'>
                <head>
                    <META http-equiv='Content-Type' content='text/html; charset=utf-8' />
                    <META NAME='viewport' CONTENT='width=device-width, initial-scale=1.0, user-scalable=yes'>
                    <META NAME='description' CONTENT='" . APP::DESCRIPTION . "'>

                    <!-- Stylesheets -->
                    " . self::cssScripts() . "

                    <link rel='shortcut icon' href=''>

                    <title>Journal Club Manager - Organize your journal club efficiently</title>
                </head>

                <body class='mainbody'>
                    " . Modal::template() . "

                    <div class='sideMenu'>
                        {$menu}
                    </div>

                    <!-- Header section -->
                    <header class='header'>

                        <div id='float_menu'><img src='assets/images/menu.png' alt='login'></div><!--
                    --><div id='sitetitle'>
                            <span style='font-size: 30px; font-weight: 400;'>JCM</span>
                            <span style='font-size: 25px; color: rgba(200,200,200,.8);'>anager</span>
                        </div><!--
                    Menu section -->
                        <div class='menu'>
                            <div class='topnav'>
                                {$menu}
                            </div>
                        </div><!--
                    Login box-->
                        <div id='login_box'>
                            " . self::loginMenu() . "
                        </div>
                    </header>

                    <!-- Core section -->
                    <main id='core'>
                        <div id='page_container'>
                            <div id='hidden_container'>
                                <div id='current_content'>
                                    <div class='wrapper'>
                                        <div id='section_title'></div>
                                        <div id='section_content'></div>
                                    </div>
                                    <div class='plugins'></div>
                                </div>
                            </div>
                        </div>
                    </main>

                    <!-- Footer section -->
                    <footer id='footer'>
                        <div id='colBar'></div>
                        <div id='appTitle'>" . App::APP_NAME . "</div>
                        <div id='appVersion'>Version " . App::VERSION . "</div>
                        <div id='sign'>
                            <a href='". App::REPOSITORY . "' target='_blank'>" . App::COPYRIGHT . "</a>
                        </div>
                    </footer>


                </body>
            </html>
            " . self::jsScripts();
    }

    /**
     * Render Login menu
     *
     * @return string
     */
    private static function loginMenu()
    {
        if (!SessionInstance::isLogged()) {
            $leanModalUrlLogin = Router::buildUrl(
                'Users',
                'getForm',
                array(
                    'type'=>'loginForm', 'view'=>'modal'
                    )
            );
            $leanModalUrlRegistration = Router::buildUrl(
                'Users',
                'getForm',
                array(
                    'type'=>'registrationForm', 'view'=>'modal'
                    )
            );
            $showlogin = "
            <div class='leanModal' data-url='{$leanModalUrlLogin}' 
            data-section='login_form'>
                <img src='assets/images/login_bk.png' alt='login'>
            </div>
            <div class='leanModal' data-url='{$leanModalUrlRegistration}' 
            data-section='registration_form'>
                <img src='assets/images/signup_bk.png' alt='signup'>
            </div>
            ";
        } else {
            $showlogin = "
            <div class='menu-section'>
                <a href='index.php?page=member/profile' id='profile'>
                    <img src='assets/images/profile_bk_25x25.png' alt='profile'>
                </a>
            </div>
            <div class='menu-section'>
                <a href='#' class='menu-section' id='logout'>
                    <img src='assets/images/logout_bk.png' alt='logout'>
                </a>
            </div>";
        }
        return $showlogin;
    }

    /**
     * List of css scripts that must be loaded
     *
     * @return void
     */
    private static function cssScripts()
    {
        $list = self::loadCssScripts(PATH_TO_ASSETS . DS . 'styles', array('install.min.css'), 'min');
        $list .= self::loadCssScripts(PATH_TO_PLUGINS);
        $list .= "<link type='text/css' rel='stylesheet' 
        href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css'>";

        return $list;
    }

    /**
     * Scan for css scripts
     *
     * @param string $path: path to folder to scan
     * @return string
     */
    private static function loadCssScripts($path, array $exclude = array(), $filter=null)
    {
        $content = self::browse($path, 'css', $exclude, $filter);
        $links = "";
        foreach ($content as $file) {
            $path = str_replace(PATH_TO_APP . DS, '', $file);
            $links .= "<link type='text/css' rel='stylesheet' href='{$path}'/>";
        }
        return $links;
    }

    /**
     * Browse directories
     * @param string $dir
     * @param array $dirsNotToSaveArray
     * @return array
     */
    private static function browse($dir, $extension, array $exclude = array(), $filter = null)
    {
        $filenames = array();
        if ($handle = opendir($dir)) {
            while (false !== ($file = readdir($handle))) {
                $split = explode('.', $file);
                $filename = $dir ."/". $file;

                if (!is_null($filter)) {
                    $match = \preg_match("/" . $filter . "/", $file) == 1;
                } else {
                    $match = true;
                }
                if (end($split) == $extension && !in_array($file, $exclude) && $match) {
                    $filenames[] = $filename;
                } elseif ($file != "." && $file != ".." && is_dir($dir.$file)) {
                    $newfiles = self::browse($dir.$file, $extension);
                    $filenames = array_merge($filenames, $newfiles);
                }
            }
            closedir($handle);
        }
        return $filenames;
    }
    
    /**
     * List of js scripts that must be loaded
     *
     * @return void
     */
    private static function jsScripts()
    {
        return "
        <!-- JQuery -->
        <script type='text/javascript' src='assets/scripts/lib/jquery-1.11.1.min.js'></script>
        <script type='text/javascript' src='assets/scripts/lib/jquery-ui.min.js'></script>
        <script type='text/javascript' src='assets/scripts/index.js'></script>
        <script type='text/javascript' src='assets/scripts/app/loading.js'></script>

        <!-- Bunch of jQuery functions -->
        <script type='text/javascript' src='assets/scripts/app/form.js' defer ></script>
        <script type='text/javascript' src='assets/scripts/app/plugins.js' defer ></script>

        <script type='text/javascript' src='assets/scripts/lib/DrpUploader/DrpUploader.js' defer ></script>
        <script type='text/javascript' src='assets/scripts/lib/leanModal/jquery.leanModal.js' defer ></script>

        <script type='text/javascript' src='assets/scripts/lib/passwordchecker/passwordchecker.min.js' defer ></script>
        <link type='text/css' rel='stylesheet' href='assets/scripts/lib/passwordchecker/css/style.min.css'/>

        <!-- CKeditor (Rich-text textarea) -->
        <script type='text/javascript' src='vendor/ckeditor/ckeditor/ckeditor.js' defer ></script>
        ";
    }

    /**
     * Render Section
     *
     * @param array $content: section content
     * @param null $id: section
     *
     * @return string
     */
    public static function section(array $content, $id = null)
    {
        return "
            <section id='{$id}'>
                <h2>{$content['title']}</h2>
                <div class='section_content'>
                    {$content['body']}
                </div>
            </section>
        ";
    }
}
