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
    <link type="text/css" rel="stylesheet" href="assets/scripts/lib/passwordchecker/css/style.min.css"/>

    <style type="text/css">
        body {
            background: rgb(49, 49, 49);
        }

        .box {
            max-width: 600px;
            min-width: 300px;
            width: 60%;
            padding: 20px;
            margin: auto;
        }

        .box > div {
            margin: auto;
        }

        header {
            position: relative;
            width: 100%;
            height: auto;
            box-sizing: border-box;
            min-width: 100%;
            box-shadow: none !important;
            text-align: center;
            padding: 0;
            background-color: rgba(255, 255, 255, 0.1);
        }

        header .box {
            padding: 10px;
        }

        header > div {
            display: block;
            height: auto;
        }

        #page_title {
            text-align: center;
            font-size: 40px;
            color: rgb(255, 255, 255);
            font-weight: 500;
            box-sizing: border-box;
        }

        main {
            min-height: 400px;
            padding: 0;
        }

        section {
            border-radius: 5px;
        }

        #operation {
            width: 70%;
            margin: 20px auto;
            min-width: 300px;
            box-sizing: border-box;
        }

        #section_title {
            font-size: 30px;
            border: 1px solid #eeeeee;
            padding: 20px;
            box-sizing: border-box;
            border-radius: 5px;
            font-weight: 500;
            text-transform: capitalize;
            background: white;
            color: rgb(49, 49, 49);
            margin-bottom: 50px;
            margin-top: 30px;

        }

        header #appTitle {
            text-transform: uppercase;
            color: rgb(255, 255, 255);
            margin-top: 0;
            font-size: 1.1em;
            font-weight: 500;
        }

        .appname {
            font-weight: 500;
            color: rgb(49, 49, 49);
            font-style: italic;
        }

        #appVersion {
            border-top: 1px solid rgba(255, 255, 255, 0.5);
            color: rgb(212, 212, 212);
            margin-top: 0;
            font-size: .6em;
            font-weight: 200;
        }

        footer {
            background-color: rgba(255, 255, 255, 0.1);
            bottom: 0;
            left: 0;
            width: 100%;
            height: auto;
            line-height: 5vh;
            padding: 0 5px;
            box-sizing: border-box;
            min-height: 0;
            text-align: left;
            margin: 0;
        }

        footer > div {
            display: inline-block;
        }

        footer #appTitle {
            text-transform: uppercase;
            color: rgb(255, 255, 255);
            margin-top: 0;
            font-size: 1.1em;
            font-weight: 500;
        }

        footer #appVersion {
            border-top: none !important;
            color: rgb(212, 212, 212);
            font-size: .8em;
            font-weight: 200;
        }

        footer #sign {
            float: right;
            padding: 0;
            margin: 10px 0 0 0;
        }

        footer #sign a {
            color: white;
        }

        footer #sign a:hover {
            color: rgba(255, 255, 255, .8);
        }

        #page_container {
            overflow: hidden;
            max-width: 100%;
            box-sizing: border-box;
        }

        #hidden_container {
            width: 200%;
        }

        #hidden_container > div {
            display: inline-block;
            width: 49.8%;
            height: 100%;
            padding: 0;
            margin: 0;
            vertical-align: top;
            float: left;
        }

        .progress_layer {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
            margin: 0;
            background: rgba(255, 255, 255, 1);
            z-index: 10;
        }

        .progressText_container {
            width: 100%;
            height: 100%;
            text-align: center;
            color: rgb(49, 49, 49);
            position: relative;
        }

        .progressText_container > div {
            text-align: center;
        }

        .progressText_container > .text {
            text-align: center;
            font-size: 15px;
            font-weight: 500;
            padding: 5px 10px;
        }

        .progressBar_container {
            height: 2px;
            border-radius: 5px;
        }

        .progressBar {
            background: rgba(100, 100, 100, 1);
            height: 100%;
            border-radius: 5px;
        }

        .progressBar_loading {
            width: 30px;
            height: 30px;
            margin: 20px auto;
            background: rgba( 255, 255, 255, .7)
            url('images/spinner.gif')
            50% 50%
            no-repeat;
            background-size: 100%;
        }

    </style>

    <!-- JQuery -->
    <script type="text/javascript" src="assets/scripts/lib/jquery-1.11.1.js"></script>
    <script type="text/javascript" src="assets/scripts/app/form.js"></script>
    <script type="text/javascript" src="assets/scripts/lib/passwordchecker/passwordchecker.min.js"></script>

    <!-- Bunch of jQuery functions -->
    <script type="text/javascript">

        /**
         * Get URL parameters
         */
        function getParams() {
            var url = window.location.href;
            var splitted = url.split("?");
            if(splitted.length === 1) {
                return {};
            }
            var paramList = decodeURIComponent(splitted[1]).split("&");
            var params = {};
            for(var i = 0; i < paramList.length; i++) {
                var paramTuple = paramList[i].split("=");
                params[paramTuple[0]] = paramTuple[1];
            }
            return params;
        }

        var step = 0;
        var next_step = 1;

        /**
         * Get view
         * @param step_to_load: view to load
         * @param op: update or make new installation
         */
        function getpagecontent(step_to_load, op) {
            var step = step_to_load;
            var stateObj = { page: 'install' };
            var div = $('main');

            var callback = function(result) {
                history.pushState(stateObj, 'install', "install.php?step=" + result.step + "&op=" + result.op);
                pageTransition(result);
            };
            var data = {get_page_content: step, op: op};
            processAjax(div,data,callback,'install.php');
        }

        function pageTransition(content) {
            var container = $('#hidden_container');
            var current_content = container.find('#current_content');

            if (container.find('#next_content').length === 0) {
                container.append('<div id="next_content"></div>');
                renderSection(current_content, content);
                return true;
            }

            var next_content = container.find('#next_content');
            renderSection(next_content, content);

            current_content.animate({'margin-left': '-100%', 'opacity': 0}, 1000, function() {
                var next_content = $(this).siblings('#next_content');
                next_content.attr('id', 'current_content');
                next_content.after('<div id="next_content"></div>');
                $(this).remove();

            });

        }

        function renderSection(section, content) {
            var defaultHtml = '<div class="box"><div id="section_title"></div><div id="section_content"></div></div>';
            section.html(defaultHtml);
            section.find('#section_content').html(content.content);
            section.find('#section_title').html(content.title);
        }

        /**
         * Show loading animation
         */
        function loadingDiv(el) {
            el.css('position', 'relative');
            if (el.find('.loadingDiv').length === 0) {
                el.append("<div class='loadingDiv'></div>");
            }
            el.find('.loadingDiv').css('position', 'absolute').fadeIn();
        }

        /**
         * Remove loading animation at the end of an AJAX request
         * @param el: DOM element in which we show the animation
         */
        function removeLoading(el) {
            el.fadeIn(200);
            el.find('.loadingDiv')
                .fadeOut(1000)
                .remove();
        }

        /**
         * Render progression bar
         */
        function progressbar(el, percent, msg) {
            el.css('position', 'relative');
            el.find(".progress_layer").remove();
            el.append('<div class="progress_layer"></div>');
            var layer = el.find('.progress_layer');
            if (layer.find('.progressText_container').length === 0) {
                layer.append('<div class="progressText_container">' +
                    '<div class="text"></div>' +
                    '<div class="progressBar_container"><div class="progressBar"></div>' +
                    '<div class="progressBar_loading"></div></div>');
            }
            var TextContainer = el.find('.text');
            TextContainer.html(msg);

            var progressBar = el.find('.progressBar_container');
            var width = progressBar.width();
            progressBar.children('.progressBar').css({'width': percent * width + 'px'});
        }

        function remove_progressbar(el) {
            el.find(".progress_layer").remove();
        }

        /**
         * Update operation
         */
        function modOperation(data,operation) {
            var i;
            // Find and replace `content` if there
            for (i = 0; i < data.length; ++i) {
                if (data[i].name === "operation") {
                    data[i].value = operation;
                    break;
                }
            }
            return data;
        }

        /**
         * Go to next installation step
         **/
        function gonext() {
            var op = $('.section_content#operation').data('action');
            var step = $('.section_content#operation').data('next');
            console.log(op, step);
            getpagecontent(step, op);
            return true;
        }

        /**
         * Application installation
         * @param input
         * @returns {boolean}
         */
        function process(input) {
            var form = input.length > 0 ? $(input[0].form) : $();
            var operation = form.find('input[name="operation"]').val();
            var op = form.find('input[name="op"]').val();
            var data = form.serializeArray();
            var operationDiv = $('#operation');

            // Check form validity
            if (!checkform(form)) return false;

            var queue = [
                {url: 'php/router.php?controller=Db&action=testdb', operation: 'db_info', data: data, text: 'Connecting to database'},
                {url: 'php/router.php?controller=Config&action=createConfig', operation: 'do_conf', data: data, text: 'Creating configuration file'},
                {url: 'php/router.php?controller=Backup&action=backupDb', operation: 'backup', data: data, text: 'Backup files and database'},
                {url: 'php/router.php?controller=App&action=install', operation: 'install_db', data: data, text: 'Installing application'},
                {url: 'php/router.php?controller=Session&action=checkDb', operation: 'checkDb', data: data, text: 'Checking database integrity'}
            ];

            var lastAction = function() {
                progressbar(operationDiv, 1, 'Installation complete');
                setTimeout(function() {
                    remove_progressbar(operationDiv);
                    gonext();
                    return true;
                }, 1000);
            };
            recursive_ajax(queue, operationDiv, queue.length, lastAction);
            return true;
        }

        /**
         * Run installation steps using recursive call
         * @param queue
         * @param el
         * @param init_queue_length
         * @param lastAction: function to execute once the queue is empty
         */
        function recursive_ajax(queue, el, init_queue_length, lastAction) {
            var percent = 1 - (queue.length / init_queue_length);
            if (queue.length > 0) {
                var dataToProcess = modOperation(queue[0].data, queue[0].operation);
                jQuery.ajax({
                    url: queue[0].url,
                    type: 'post',
                    data: dataToProcess,
                    async: true,
                    timeout: 20000,
                    beforeSend: function() {
                        progressbar(el, percent, queue[0].text);
                    },
                    success: function(data) {
                        var result = jQuery.parseJSON(data);
                        if (result.status) {
                            progressbar(el, percent, result.msg);
                            queue.shift();
                            setTimeout(function() {
                                recursive_ajax(queue, el, init_queue_length, lastAction);
                            }, 1000);
                        } else {
                            progressbar(el, percent, result.msg);
                            setTimeout(function() {
                                remove_progressbar(el);
                                return true;
                            }, 3000);
                            return false;
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        progressbar(el, percent, textStatus);
                        setTimeout(function() {
                            remove_progressbar(el);
                            return true;
                        }, 3000);

                    }
                });
            } else {
                if (lastAction !== undefined) {
                    lastAction();
                }
            }
        }

        // Has the page been loaded already
        var loaded = false;

        /**
         * Get action and step values from URL for the first load
         * @return void
         */
        function first_load() {
            if (!loaded) {
                var params = getParams();
                getpagecontent(params.step, params.op);
                loaded = true;
            }
        }


        $(function () {

            // Get page content for the first load only
            first_load();

            $('body')

                /*%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
                 Installation/Update
                 %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%*/
                // Go to next installation step
                .on('click', '.start', function(e) {
                    e.preventDefault();
                    var op = $(this).attr('data-op');
                    $('.section_content#operation').data('action', op);
                    gonext(op);
                })

                // Go to next installation step
                .on('click', '.finish', function(e) {
                    e.preventDefault();
                    window.location = "index.php";
                })

                .on('click', "input[type='submit']", function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (!$(this).hasClass('process_form') && !$(this).hasClass('test_email_settings')) {
                        process($(this));
                    } else {
                        return false;
                    }
                })

                .on('click',".process_form",function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var form = $(this).length > 0 ? $($(this)[0].form) : $();
                    var op = form.find('input[name="op"]').val();
                    var url = form.attr('action');
                    if (!checkform(form)) {return false;}
                    var callback = function(result) {
                        if (result.status === true) {
                            console.log(step, op);
                            gonext();
                        }
                    };
                    processForm(form,callback,url);
                })

                // Test email host settings
                .on('click', '.test_email_settings', function(e) {
                    e.preventDefault();
                    var input = $(this);
                    var form = input.length > 0 ? $(input[0].form) : $();
                    var data = form.serializeArray();
                    data.push({name: 'test_email_settings', value: true});
                    processAjax(form, data, false, 'php/form.php');
                })

                // Final step: Create admin account
                .on('click','.admin_creation',function(e) {
                    e.preventDefault();
                    var form = $(this).length > 0 ? $($(this)[0].form) : $();
                    var op = form.find('input[name="op"]').val();
                    if (!checkform(form)) {return false;}
                    var callback = function(result) {
                        if (result.status === true) {
                            getpagecontent(5,op);
                        }
                    };
                    processForm(form,callback,'install.php');
                });
        });
    </script>
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
