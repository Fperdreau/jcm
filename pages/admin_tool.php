<?php
/*
Copyright © 2014, Florian Perdreau
This file is part of Journal Club Manager.

Journal Club Manager is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Journal Club Manager is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with Journal Club Manager.  If not, see <http://www.gnu.org/licenses/>.
*/

require('../includes/boot.php');
check_login(array("organizer","admin"));

// Declare classes
$user = new User($db,$_SESSION['username']);

// Manage Sessions
if (!empty($_GET['op']) && $_GET['op'] == 'sessions') {
    $Sessionslist = $Sessions->managesessions();
    $timeopt = maketimeopt();

    //Get session types
    $Sessionstype = "";
    $opttypedflt = "";
    foreach ($AppConfig->session_type as $type=>$chairs) {
        $Sessionstype .= "
            <div class='type_div' id='session_$type'>
                <div class='type_name'>$type</div>
                <div class='type_del' data-type='$type' data-class='session'>
                <img src='images/delete.png' style='width: 15px; height: auto;'>
                </div>
            </div>
        ";
        if ($type == $AppConfig->session_type_default) {
            $opttypedflt .= "<option value='$type' selected>$type</option>";
        } else {
            $opttypedflt .= "<option value='$type'>$type</option>";
        }
    }

    /**  Get session types */
    $prestype = "";
    $prestypes = explode(',',$AppConfig->pres_type);
    foreach ($prestypes as $type) {
        $prestype .= "
            <div class='type_div' id='pres_$type'>
                <div class='type_name'>$type</div>
                <div class='type_del' data-type='$type' data-class='pres'>
                    <img src='images/delete.png' style='width: 15px; height: auto;'>
                </div>
            </div>
        ";
    }

    $chair_assignopt = array('auto'=>'In advance', 'manual'=>'On submission');
    $chair_assignsel = "<option value='$AppConfig->chair_assign' selected>".$chair_assignopt[$AppConfig->chair_assign]."</option>";

    $content = "
    <span id='pagename'>Manage Sessions</span>
    <p class='page_description'>Here you can manage the journal club sessions, change their type, time, etc.</p>

    <div class='section_header'>Sessions settings</div>
    <div class='section_content'>
        <form method='post' action='' class='form' id='config_form_session'>
            <div class='feedback' id='feedback_jcsession'></div>
            <input type='hidden' name='config_modify' value='true'>
            <div class='formcontrol' style='width: 100px;'>
                <label>Room</label>
                <input type='text' name='room' value='$AppConfig->room'>
            </div>
            <div class='formcontrol' style='width: 20%;'>
                <label for='jc_day'>Day</label>
                <select name='jc_day'>
                    <option value='$AppConfig->jc_day' selected>$AppConfig->jc_day</option>
                    <option value='monday'>Monday</option>
                    <option value='tuesday'>Tuesday</option>
                    <option value='wednesday'>Wednesday</option>
                    <option value='thursday'>Thursday</option>
                    <option value='friday'>Friday</option>
                </select>
            </div>
            <div class='formcontrol' style='width: 10%;'>
                <label>From</label>
                <select name='jc_time_from'>
                    <option value='$AppConfig->jc_time_from' selected>$AppConfig->jc_time_from</option>
                    $timeopt;
                </select>
            </div>
            <div class='formcontrol' style='width: 10%;'>
                <label>To</label>
                <select name='jc_time_to'>
                    <option value='$AppConfig->jc_time_to' selected>$AppConfig->jc_time_to</option>
                    $timeopt;
                </select>
            </div>
            <div class='formcontrol' style='width: 30%;'>
                <label>Presentations/Session</label>
                <input type='text' size='3' name='max_nb_session' value='$AppConfig->max_nb_session'/>
            </div>
            <div class='formcontrol' style='width: 30%;'>
                <label>Chair assignment</label>
                <select name='chair_assign'>
                    $chair_assignsel
                    <option value='auto'>In advance</option>
                    <option value='manual'>On submission</option>
                </select>
            </div>
            <div class='formcontrol' style='width: 30%;'>
                <label>Sessions to plan in advance</label>
                <input type='text' size='3' name='nbsessiontoplan' value='$AppConfig->nbsessiontoplan'/>
            </div>
            <p style='text-align: right'><input type='submit' name='modify' value='Modify' id='submit' class='config_form_session'/></p>
        </form>
    </div>

    <div class='section_header'>Session/Presentation</div>
    <div class='section_content'>
        <div class='section_sub'>Sessions</div>
        <div class='formcontrol' style='width: 30%;'>
            <label>Default session type </label>
            <select class='session_type_default'>
                $opttypedflt
            </select>
        </div><br>
        <input type='button' id='submit' class='type_add' data-class='session' value='Add a category'/>
        <input id='new_session_type' type='text' placeholder='New Category'/>
        <div class='feedback' id='feedback_session'></div>
        <div class='type_list' id='session'>$Sessionstype</div>
        <div class='section_sub'>Presentations</div>
        <input type='button' id='submit' class='type_add'  data-class='pres' value='Add a category'/>
        <input id='new_pres_type' type='text' placeholder='New Category'/>
        <div class='feedback' id='feedback_pres'></div>
        <div class='type_list' id='pres'>$prestype</div>
    </div>

    <div class='section_header'>Manage Sessions</div>
    <div class='section_content'>
        <div class='formcontrol' style='width: 30%;'>
        <label>Number of sessions to show</label>
            <select class='show_sessions'>
                <option value='1'>1</option>
                <option value='4' selected>4</option>
                <option value='8'>8</option>
                <option value='10'>10</option>
            </select>
        </div>
        <div id='sessionlist'>
        $Sessionslist
        </div>
    </div>";
}

// Manage users
elseif (!empty($_GET['op']) && $_GET['op'] == 'users') {
    $userlist = $Users->generateuserslist();

    $content = "
    <span id='pagename'>Manage Users</span>
    <p class='page_description'>Here you can modify users status and activate, deactivate or delete user accounts.</p>
    <div class='section_header'>Manage users</div>
    <div class='section_content'>
        <div class='formcontrol' style='width: 100px;'>
            <label for='order'>Sort by</label>
            <select name='order' class='user_select'>
                <option value='' selected></option>
                <option value='firstname'>First name</option>
                <option value='lastname'>Last name</option>
                <option value='username'>User name</option>
                <option value='email'>Email</option>
                <option value='active'>Activation date</option>
                <option value='status'>Status</option>
            </select>
        </div>
        <div class='feedback'></div>
        <div id='user_list'>
        $userlist
        </div>
    </div>";

// Plugins
} elseif (!empty($_GET['op']) && $_GET['op'] == 'plugins') {
    $plugins = new AppPlugins($db);
    $pluginsList = $plugins->getPlugins();
    $plugin_list = "
        <div class='list-container' id='pub_labels' style='font-size: 12px;'>
            <div style='text-align: center; font-weight: bold; width: 10%;'>Name</div>
            <div style='text-align: center; font-weight: bold; width: 5%;'>Version</div>
            <div style='text-align: center; font-weight: bold; width: 20%;'>Page</div>
            <div style='text-align: center; font-weight: bold; width: 30%;'>Options</div>
            <div style='text-align: center; font-weight: bold; width: 5%;'>Status</div>
            <div style='text-align: center; font-weight: bold; width: 10%;'></div>
        </div>";
    foreach ($pluginsList as $pluginName => $info) {
        $installed = $info['installed'];
        if ($installed) {
            $install_btn = "<div class='install_plugin install_btn' data-op='uninstall' data-plugin='$pluginName'>Uninstall</div>";
        } else {
            $install_btn = "<div class='install_plugin install_btn' data-op='install' data-plugin='$pluginName'>Install</div>";
        }
        $status = $info['status'];
        $option_list = '';
        foreach ($info['options'] as $option => $settings) {
            $option_list .= "<label>$option</label><input type='text' value='$settings' class='input_opt plugin_setting' data-plugin='$pluginName' data-option='$option'/><br>";
        }
        $plugin_list .= "
        <div class='list-container' style='font-size: 12px;' id='plugin_$pluginName'>
            <div style='width: 10%'><b>$pluginName</b></div>
            <div style='width: 5%'>" . $info['version'] . "</div>
            <div style='width: 20%'>" . $info['page'] . "</div>
            <div style='width: 30%; vertical-align: top;'>$option_list</div>
            <div style='width: 5%'>
                <select class='select_opt plugin_status' data-plugin='$pluginName'>
                <option value='$status' selected>$status</option>
                <option value='On'>On</option>
                <option value='Off'>Off</option>
                </select>
            </div>
            <div style='width: 10%'>$install_btn</div>
        </div>
        ";
    }
    $content = "
        <span id='pagename'>Plugins</span>
        <p class='page_description'>Here you can install, activate or deactivate plugins and manage their settings.
        Your plugins must be located in the 'plugins' directory in order to be automatically loaded by the Journal Club Manager.</p>
        <div class='feedback'></div>
        <div class='section_header'>Plugins list</div>
        <div class='section_content'>
            $plugin_list
        </div>
    ";

// Cronjobs settings
} elseif (!empty($_GET['op']) && $_GET['op'] == 'cronjobs') {
    $CronJobs = new AppCron($db);
    $jobsList = $CronJobs->getJobs();
    $cronList = "
        <div class='list-container' id='pub_labels' style='font-size: 12px;'>
            <div style='text-align: center; font-weight: bold; width: 10%;'>Name</div>
            <div style='text-align: center; font-weight: bold; width: 5%;'>Status</div>
            <div style='text-align: center; font-weight: bold; width: 40%;'>Time</div>
            <div style='text-align: center; font-weight: bold; width: 20%;'>Next run</div>
            <div style='text-align: center; font-weight: bold; width: 10%;'></div>
            <div style='text-align: center; font-weight: bold; width: 10%;'></div>
        </div>";
    foreach ($jobsList as $cronName => $info) {
        $installed = $info['installed'];
        if ($installed) {
            $install_btn = "<div class='install_cron install_btn' data-op='uninstall' data-cron='$cronName'>Uninstall</div>";
        } else {
            $install_btn = "<div class='install_cron install_btn' data-op='install' data-cron='$cronName'>Install</div>";
        }

        $runBtn = "<div class='run_cron install_btn' data-cron='$cronName'>Run</div>";
        $status = $info['status'];
        $time = $info['time'];

        $dayName_list = "";
        foreach ($CronJobs->daysNames as $day) {
            if ($day == $info['dayName']) {
                $dayName_list .= "<option value='$day' selected>$day</option>";
            } else {
                $dayName_list .= "<option value='$day'>$day</option>";
            }
        }

        $dayNb_list = "";
        foreach ($CronJobs->daysNbs as $i) {
            if ($i == $info['dayNb']) {
                $dayNb_list .= "<option value='$i' selected>$i</option>";
            } else {
                $dayNb_list .= "<option value='$i'>$i</option>";
            }
        }

        $hours_list = "";
        foreach ($CronJobs->hours as $i) {
            if ($i == $info['hour']) {
                $hours_list .= "<option value='$i' selected>$i:00</option>";
            } else {
                $hours_list .= "<option value='$i'>$i:00</option>";
            }
        }

        $cronList .= "
        <div class='list-container' id='cron_$cronName'>
            <div style='width: 10%;'><b>$cronName</b></div>
            <div style='width: 5%; text-align: center;'>
                <select class='select_opt cron_status' data-cron='$cronName'>
                <option value='$status' selected>$status</option>
                <option value='On'>On</option>
                <option value='Off'>Off</option>
                </select></div>
            <div style='width: 40%; text-align: center;'>
                <label>Day</label>
                    <select class='select_opt cron_setting' data-cron='$cronName' data-setting='dayName'>
                        $dayName_list
                    </select>
                <label>Date</label>
                    <select class='select_opt cron_setting' data-cron='$cronName' data-setting='dayNb'>
                        $dayNb_list
                    </select>
               <label>Time</label>
                    <select class='select_opt cron_setting' data-cron='$cronName' data-setting='hour'>
                        $hours_list
                    </select>
            </div>
            <div style='width: 20%; text-align: center;' id='cron_time_$cronName'>$time</div>
            <div style='width: 10%; text-align: center;'>$install_btn</div>
            <div style='width: 10%; text-align: center;'>$runBtn</div>
        </div>
        ";
    }
    $content = "
        <span id='pagename'>Scheduled tasks</span>
        <p class='page_description'>Here you can install, activate or deactivate scheduled tasks and manage their settings.
        Please note that in order to make these tasks running, you must have set a scheduled task pointing to 'cronjobs/run.php'
        either via a Cron Table (Unix server) or via the Scheduled Tasks Manager (Windows server)</p>
        <div class='feedback'></div>
        <div class='section_header'>Tasks list</div>
        <div class='section_content'>
            $cronList
        </div>
    ";


// Send mail
} elseif (!empty($_GET['op']) && $_GET['op'] == 'mail') {
    $content = "
		<span id='pagename'>Mailing list</span>
        <p class='page_description'>Here you can send an email to users who subscribed to the newsletter.</p>
        <div class='feedback'></div>
        <div class='section_header'>Send an email</div>
        <div class='section_content'>
            <form method='post' action=''>
                <div class='submit_btns'>
                    <input type='submit' name='send' value='Send' id='submit' class='mailing_send'>
                </div>
                <div class='formcontrol' style='width: 100%;'>
                    <label>Subject:</label>
                    <input type='text' size='40' id='spec_head' name='spec_head' placeholder='Subject' value='' />
                </div>
                <div class='formcontrol' style='width: 100%;'>
                    <label>Message</label>
                    <textarea name='spec_msg' id='spec_msg' cols='70' rows='15' class='tinymce'></textarea>
                </div>
            </form>
        </div>";

// Configuration
} elseif (!empty($_GET['op']) && $_GET['op'] == 'config') {
    // Make hours options list
    $timeopt = maketimeopt();

    $content = "
		<span id='pagename'>Configuration</span>
        <div class='section_header'>Site parameters</div>
        <div class='section_content'>
            <form method='post' action='' class='form' id='config_form_site'>
                <div class='submit_btns'>
                    <input type='submit' name='modify' value='Modify' id='submit' class='config_form_site'>
                </div>
                <input type='hidden' name='config_modify' value='true'/>
                <div class='formcontrol' style='width: 30%;'>
                    <label>Status</label>
                    <select name='status'>
                        <option value='$AppConfig->status' selected>$AppConfig->status</option>
                        <option value='On'>On</option>
                        <option value='Off'>Off</option>
                    </select>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label>Site title</label>
                    <input type='text' size='30' name='sitetitle' value='$AppConfig->sitetitle'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label>Site url</label>
                    <input type='text' size='30' name='site_url' value='$AppConfig->site_url'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label>Oldest DB backups to keep (in days)</label>
                    <input type='text' size='30' name='clean_day' value='$AppConfig->clean_day'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label>Allowed file types (upload)</label>
                    <input type='text' size='30' name='upl_types' value='$AppConfig->upl_types'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label>Maximum file size (in Kb)</label>
                    <input type='text' size='30' name='upl_maxsize' value='$AppConfig->upl_maxsize'>
                </div>
                <div class='feedback' id='feedback_site'></div>
            </form>
        </div>

        <div class='section_header'>Lab information</div>
        <div class='section_content'>
            <form method='post' action='' class='form' id='config_form_lab'>
                <div class='submit_btns'>
                    <input type='submit' name='modify' value='Modify' id='submit' class='config_form_lab'>
                </div>
                <input type='hidden' name='config_modify' value='true'/>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='lab_name'>Name</label>
                    <input type='text' size='50' name='lab_name' value='$AppConfig->lab_name'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='lab_street'>Street</label>
                    <input type='text' size='30' name='lab_street' value='$AppConfig->lab_street'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='lab_postcode'>Post Code</label>
                    <input type='text' size='30' name='lab_postcode' value='$AppConfig->lab_postcode'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='lab_city'>City</label>
                    <input type='text' size='30' name='lab_city' value='$AppConfig->lab_city'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='lab_country'>Country</label>
                    <input type='text' size='30' name='lab_country' value='$AppConfig->lab_country'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='lab_mapurl'>Google Map's URL</label>
                    <input type='text' size='30' name='lab_mapurl' value='$AppConfig->lab_mapurl'>
                </div>
                <div class='feedback' id='feedback_lab'></div>
            </form>
        </div>

        <div class='section_header'>Email host information</div>
        <div class='section_content'>
            <form method='post' action='' class='form' id='config_form_mail'>
                <div class='submit_btns'>
                    <input type='submit' name='modify' value='Modify' id='submit' class='config_form_mail'>
                </div>
                <input type='hidden' name='config_modify' value='true'/>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='mail_from'>Sender Email address</label>
                    <input name='mail_from' type='text' value='$AppConfig->mail_from'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='mail_from_name'>Sender name</label>
                    <input name='mail_from_name' type='text' value='$AppConfig->mail_from_name'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='mail_host'>Email host</label>
                    <input name='mail_host' type='text' value='$AppConfig->mail_host'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='SMTP_secure'>SMTP access</label>
                    <select name='SMTP_secure'>
                        <option value='$AppConfig->SMTP_secure' selected='selected'>$AppConfig->SMTP_secure</option>
                        <option value='ssl'>ssl</option>
                        <option value='tls'>tls</option>
                        <option value='none'>none</option>
                     </select>
                 </div>
                 <div class='formcontrol' style='width: 30%;'>
                    <label for='mail_port'>Email port</label>
                    <input name='mail_port' type='text' value='$AppConfig->mail_port'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='mail_username'>Email username</label>
                    <input name='mail_username' type='text' value='$AppConfig->mail_username'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='mail_password'>Email password</label>
                    <input name='mail_password' type='password' value='$AppConfig->mail_password'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='pre_header'>Email header prefix</label>
                    <input name='pre_header' type='text' value='$AppConfig->pre_header'>
                </div>
                <div class='feedback' id='feedback_mail'></div>
            </form>
        </div>";

// Add a post
} elseif (!empty($_GET['op']) && $_GET['op'] == 'post') {
    $user = new User($db,$_SESSION['username']);
    $last = new Posts($db);
    $last->getlastnews();

    // Make post selection list
    $postlist = $db->getinfo($db->tablesname['Posts'],"postid");
    $options = "
        <select class='select_post' data-user='$user->fullname'>
            <option value='' selected>Select a post to modify</option>
        ";
    if (!empty($postlist)) {

        foreach ($postlist as $postid) {
            $post = new Posts($db,$postid);
            if ($post->homepage==1) {
                $style = "style='background-color: rgba(207,81,81,.3);'";
            } else {
                $style = "";
            }
            $options .= "<option value='$post->postid' $style><b>$post->date |</b> $post->title</option>";
        }
    } else {
        $options .= "<option value='false'>Nohting yet</option>";
    }
    $options .= "</select>";

    $content = "
		<span id='pagename'>News</span>
         <p class='page_description'>Here you can add a post on the homepage.</p>
        <div style='display: block; width: 100%;'>
            <div style='display: inline-block'>$options</div>
            <div style='display: inline-block'>or</div>
            <div style='display: inline-block'>
                <input type='button' id='submit' class='post_new' value='Add a new post'/>
            </div>
        </div>
        <div class='section_header'>New post</div>
        <div class='section_content'>
            <div class='feedback'></div>
            <div class='postcontent'>
            </div>
        </div>
        ";

// Admin tools page
} elseif  (!empty($_GET['op']) && $_GET['op'] == 'tools') {
    $content = "
		<span id='pagename'>Admin tools</span>
		<div class='section_page'>
            <div class='section_header'>Tools</div>
            <div class='section_content'>
                <div id='db_backup' style='display: inline-block;'>
                <label for='backup'>Backup database</label>
                <input type='button' name='backup' value='Proceed' id='submit' class='dbbackup'/>
                </div>
                <div class='feedback' id='db_backup' style='display: inline-block;'></div><br>

                <div id='full_backup' style='display: inline-block;'>
                <label for='full_backup'>Full backup (database + files)</label>
                <input type='button' name='full_backup' value='Proceed' id='submit' class='fullbackup'/>
                </div>
                <div class='feedback' id='full_backup' style='display: inline-block;'></div>
            </div>
        </div>";
}

$result = "
<div id='content'>
$content
</div>
";

echo json_encode($result);
