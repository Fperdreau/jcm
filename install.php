<?php
/**
 * page for installation
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
 * BOOTING PART
 */

include('includes' . DIRECTORY_SEPARATOR . 'App.php');
App::boot(true);

/**
 * Declare classes
 *
 */
$db = Db::getInstance();

/**
 * Patching database tables for version older than 1.2.
 */
function patching() {
    $db = Db::getInstance();
    $version = (float)$_SESSION['installed_version'];
    if ($version <= 1.2) {

        // Patch Presentation table
        // Set username of the uploader
        $sql = 'SELECT * FROM ' . $db->tablesname['Presentation'];
        $req = $db->send_query($sql);
        while ($row = mysqli_fetch_assoc($req)) {
            $pub = new Presentation($db, $row['id_pres']);
            $data = array();
            $data['summary'] = str_replace('\\', '', htmlspecialchars_decode($row['summary']));
            $data['authors'] = str_replace('\\', '', htmlspecialchars_decode($row['authors']));
            $data['title'] = str_replace('\\', '', htmlspecialchars_decode($row['title']));

            // If publication's submission date is past, we assume it has already been notified
            if ($row['up_date'] < date('Y-m-d H:i:s', strtotime('-2 days', strtotime(date('Y-m-d H:i:s'))))) {
                $data['notified'] = 1;
            }

            if (empty($row['username']) || $row['username'] == "") {
                $sql = "SELECT username FROM " . $db->tablesname['Users'] . " WHERE username='{$row['orator']}' OR fullname LIKE '%{$row['orator']}%'";
                $userreq = $db->send_query($sql);
                $user_data = mysqli_fetch_assoc($userreq);
                if (!empty($data)) {
                    $data['orator'] = $user_data['username'];
                    $data['username'] = $user_data['username'];
                }
            }
            $pub->update($data, array('id_pres'=>$row['id_pres']));
        }

        // Patch POST table
        // Give ids to posts that do not have one yet (compatibility with older versions)
        $post = new Posts();
        $sql = "SELECT postid,date,username FROM " . $db->tablesname['Posts'];
        $req = $db->send_query($sql);
        while ($row = mysqli_fetch_assoc($req)) {
            $date = $row['date'];
            if (empty($row['postid']) || $row['postid'] == "NULL") {
                // Get uploader username
                $userid = $row['username'];
                $sql = "SELECT username FROM " . $db->tablesname['Users'] . " WHERE username='$userid' OR fullname='$userid'";
                $userreq = $db->send_query($sql);
                $data = mysqli_fetch_assoc($userreq);

                $username = $data['username'];
                $post->date = $date;
                $postid = $post->generateID('postid');
                $post->update(array('postid'=>$postid, 'username'=>$username), array('date'=>$date));
            }
        }

        // Patch MEDIA table
        // Write previous uploads to this new table
        $columns = $db->getColumns($db->tablesname['Presentation']);
        $filenames = $db->resultSet($db->tablesname['Media'], array('filename'));
        if (in_array('link', $columns)) {
            $sql = "SELECT up_date,id_pres,link FROM " . $db->tablesname['Presentation'];
            $req = $db->send_query($sql);
            while ($row = mysqli_fetch_assoc($req)) {
                $links = explode(',', $row['link']);
                if (!empty($links)) {
                    foreach ($links as $link) {
                        // Check if uploads does not already exist in the table
                        if (!in_array($link, $filenames)) {
                            // Make a unique id for this link
                            $exploded = explode('.', $link);
                            if (!empty($exploded)) {
                                $id = $exploded[0];
                                $type = $exploded[1];
                                // Add upload to the Media table
                                $content = array(
                                    'date' => $row['up_date'],
                                    'fileid' => $id,
                                    'filename' => $link,
                                    'presid' => $row['id_pres'],
                                    'type' => $type
                                );
                                $db->insert($db->tablesname['Media'], $content);
                            }
                        }
                    }
                }
            }
        }
    }

    // Clean session duplicates
    $Session = new Session();
    $Session->clean_duplicates();
}

/* %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
Process Installation
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
/**
 * Get page content
 *
 */
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    $result = null;

    if ((empty($_POST['get_page_content']) || empty($_SESSION['step']))) {
        $_POST['get_page_content'] = 1;
    } elseif (empty($_POST['get_page_content']) && !empty($_SESSION['step'])) {
        $_POST['get_page_content'] = $_SESSION['step'];
    }

    if (!empty($_POST['get_page_content'])) {
        $step = !empty($_POST['get_page_content']) ? htmlspecialchars($_POST['get_page_content']) : 1;
        $_SESSION['step'] = $step;
        $next_step = $step + 1;

        $new_version = App::version;
        $operation = null;
        $title = null;
        $result = array();

        if ($step == 1) {
            unset($_SESSION['op']);
        }

        if ($step == 2) {
            $_SESSION['op'] = htmlspecialchars($_POST['op']);
        }

        if (!isset($_SESSION['op'])) {
            $op = false;
        } else {
            $op = $_SESSION['op'];
        }

        // Get configuration from previous installation
        $config = Config::getInstance();
        $version = ($config->get('version') !== false) ? $config->get('version') : false;
        $_SESSION['installed_version'] = $version;

        if ($step == 1) {
            $title = "Welcome!";
            if ($version == false) {
                $operation = "
                    <p>Hello</p>
                    <p>It seems that <span class='appname'>" . App::app_name . "</span>  has never been installed here before.</p>
                    <p>We are going to start from scratch... but do not worry, it is all automatic. We will guide you through the installation steps and you will only be required to provide us with some information regarding the hosting environment.</p>
                    <p>Click on the 'next' button once you are ready to start.</p>
                    <p>Thank you for your interest in <span class='appname'>" . App::app_name . "</span> 
                    <p style='text-align: center'><input type='button' value='Start' class='start' data-op='new'></p>";
            } else {
                $operation = "
                    <p>Hello</p>
                    <p>The current version of <span class='appname'>" . App::app_name . "</span>  installed here is <span style='font-weight: 500'>{$version}</span>. You are about to install the version <span style='font-weight: 500'>{$new_version}</span>.</p>
                    <p>You can choose to either do an entirely new installation by clicking on 'New installation' or to simply update your current version to the new one by clicking on 'Update'.</p>
                    <p class='sys_msg warning'>Please, be aware that choosing to perform a new installation will completely erase all the data present in your <span class='appname'>" . App::app_name . "</span>  database!!</p>
                    <p style='text-align: center'>
                    <input type='button' value='New installation'  class='start' data-op='new'>
                    <input type='button' value='Update' class='start' data-op='update'>
                    </p>";
            }
            $next_step = 2;
        } elseif ($step == 2) {
            $config = $db->get_config();
            $db_prefix = str_replace('_', '', $config['dbprefix']);

            $title = "Step 1: Database configuration";
            $operation = "
                <form action='install.php' method='post'>
                    <input type='hidden' name='version' value='" . App::version. "'>
                    <input type='hidden' name='op' value='{$op}'/>
                    <input type='hidden' name='operation' value='db_info'/>
                    <div class='form-group'>
                        <input name='host' type='text' value='{$config['host']}' required autocomplete='on'>
                        <label for='host'>Host Name</label>
                    </div>
                    <div class='form-group'>
                        <input name='username' type='text' value='{$config['username']}' required autocomplete='on'>
                        <label for='username'>Username</label>
                    </div>
                    <div class='form-group'>
                        <input name='passw' type='password' value='{$config['passw']}'>
                        <label for='passw'>Password</label>
                    </div>
                    <div class='form-group'>
                        <input name='dbname' type='text' value='{$config['dbname']}' required autocomplete='on'>
                        <label for='dbname'>DB Name</label>
                    </div>
                    <div class='form-group'>
                        <input name='dbprefix' type='text' value='{$db_prefix}' required autocomplete='on'>
                        <label for='dbprefix'>DB Prefix</label>
                    </div>
                    <div class='submit_btns'>
                        <input type='submit' value='Next' class='proceed'>
                    </div>
                </form>
                <div class='feedback'></div>
            ";
            $next_step = 3;
        } elseif ($step == 3) {
            $title = "Step 2: Application configuration";
            $MailManager = new MailManager();
            $operation = Template::section($MailManager::settingsForm($MailManager->getSettings()));

            if ($op === 'new') {
                $next_step = 4;
            } else {
                $next_step = 5;
            }

        } elseif ($step == 4) {
            $title = "Step 3: Admin account creation";
            $operation = Users::admin_creation_form();
            $next_step = 5;

        } elseif ($step == 5) {
            $title = "Installation complete!";
            $operation = "
            <p class='sys_msg success'>Congratulations!</p>
            <p class='sys_msg warning'> Now you can delete the 'install.php' file from the root folder of the application</p>
            <p style='text-align: right'><input type='button' value='Finish' class='finish'></p>";
            $next_step = 5;
        }

        $action = $op === false ? 'false' : $op;
        $result['title'] = $title;
        $result['content'] = "
            <section>
                <div class='section_content' id='operation' data-action={$action} data-step={$step} data-next={$next_step}>{$operation}</div>
            </section>
        ";
        $result['step'] = $step;
        $result['op'] = $op;
        $result['next_step'] = $next_step;
    }

    echo json_encode($result);
    exit;
}

?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <META http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <META NAME="description" CONTENT="Journal Club Manager. The easiest way to manage your lab's journal club.">
    <META NAME="keywords" CONTENT="Journal Club Manager">
    <link href='https://fonts.googleapis.com/css?family=Lato&subset=latin,latin-ext' rel='stylesheet' type='text/css'>
    <link type='text/css' rel='stylesheet' href="assets/styles/stylesheet.min.css"/>
    <link type='text/css' rel='stylesheet' href="assets/styles/install.min.css"/>
    <link type="text/css" rel="stylesheet" href="assets/scripts/lib/passwordchecker/css/style.min.css"/>

    <!-- JQuery -->
    <script type="text/javascript" src="assets/scripts/lib/jquery-1.11.1.js"></script>
    <script type="text/javascript" src="assets/scripts/app/form.js"></script>
    <script type="text/javascript" src="assets/scripts/install.js"></script>
    <script type="text/javascript" src="assets/scripts/lib/passwordchecker/passwordchecker.min.js"></script>

    <title><?php echo App::app_name; ?>  - Installation</title>
</head>

<body>

    <!-- Header section -->
    <header>
        <div class="box" id="page_title">
            <div id="appTitle"><?php echo App::app_name; ?></div>
            <div id="appVersion">Version <?php echo App::version; ?></div>
        </div>
    </header>

    <!-- Core section -->
    <main>
        <div id="page_container">
            <div id="hidden_container">
                <div id="current_content">
                    <div class="box">
                        <div id="section_title"></div>
                        <div id="section_content"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div id="appTitle"><?php echo App::app_name; ?></div>
        <div id="appVersion">Version <?php echo App::version; ?></div>
        <div id="sign">
            <a href="<?php echo App::repository?>" target='_blank'><?php echo App::copyright; ?></a>
        </div>
    </footer>

</body>

</html>
