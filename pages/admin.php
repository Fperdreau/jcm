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
                <div class='type_name'>".ucfirst($type)."</div>
                <div class='type_del' data-type='$type' data-class='session'>
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
                <div class='type_name'>".ucfirst($type)."</div>
                <div class='type_del' data-type='$type' data-class='pres'>
                </div>
            </div>
        ";
    }

    $content = "
    <h1>Manage Sessions</h1>
    <p class='page_description'>Here you can manage the journal club sessions, change their type, time, etc.</p>

    <section>
        <h2>Sessions settings</h2>
        <form method='post' action='' class='form' id='config_form_session'>
            <div class='feedback' id='feedback_jcsession'></div>
            <input type='hidden' name='config_modify' value='true'>
            <div class='formcontrol'>
                <label>Room</label>
                <input type='text' name='room' value='$AppConfig->room'>
            </div>
            <div class='formcontrol'>
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
            <div class='formcontrol'>
                <label>From</label>
                <select name='jc_time_from'>
                    <option value='$AppConfig->jc_time_from' selected>$AppConfig->jc_time_from</option>
                    $timeopt;
                </select>
            </div>
            <div class='formcontrol'>
                <label>To</label>
                <select name='jc_time_to'>
                    <option value='$AppConfig->jc_time_to' selected>$AppConfig->jc_time_to</option>
                    $timeopt;
                </select>
            </div>
            <div class='formcontrol'>
                <label>Presentations/Session</label>
                <input type='text' size='3' name='max_nb_session' value='$AppConfig->max_nb_session'/>
            </div>
            <p style='text-align: right'><input type='submit' name='modify' value='Modify' id='submit' class='config_form_session'/></p>
        </form>
    </section>

    <section>
        <h2>Session/Presentation</h2>
        <h3>Sessions</h3>
        <div class='formcontrol'>
            <label>Default session type </label>
            <select class='session_type_default'>
                $opttypedflt
            </select>
        </div><br>
        <input type='button' id='submit' class='type_add' data-class='session' value='Add a category'/>
        <input id='new_session_type' type='text' placeholder='New Category'/>
        <div class='feedback' id='feedback_session'></div>
        <div class='type_list' id='session'>$Sessionstype</div>
        <h3>Presentations</h3>
        <input type='button' id='submit' class='type_add'  data-class='pres' value='Add a category'/>
        <input id='new_pres_type' type='text' placeholder='New Category'/>
        <div class='feedback' id='feedback_pres'></div>
        <div class='type_list' id='pres'>$prestype</div>
    </section>

    <section>
        <h2>Manage Sessions</h2>
        <div class='formcontrol'>
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
    </section>";
}

// Manage users
elseif (!empty($_GET['op']) && $_GET['op'] == 'users') {
    $userlist = $Users->generateuserslist();

    $content = "
    <h1>Manage Users</h1>
    <p class='page_description'>Here you can modify users status and activate, deactivate or delete user accounts.</p>
    <section>
        <h2>Manage users</h2>
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
    </section>";

// Plugins
} elseif (!empty($_GET['op']) && $_GET['op'] == 'plugins') {
    $plugins = new AppPlugins($db);
    $plugin_list = $plugins->show();
    $content = "
        <h1>Plugins</h1>
        <p class='page_description'>Here you can install, activate or deactivate plugins and manage their settings.
        Your plugins must be located in the 'plugins' directory in order to be automatically loaded by the Journal Club Manager.</p>
        <div class='feedback'></div>
        <section>
            <h2>Plugins list</h2>
            $plugin_list
        </section>
    ";

// Cronjobs settings
} elseif (!empty($_GET['op']) && $_GET['op'] == 'cronjobs') {
    $AppCron = new AppCron($db);
    $cronOpt = $AppCron->show();
    $content = "
        <h1>Scheduled tasks</h1>
        <p class='page_description'>Here you can install, activate or deactivate scheduled tasks and manage their settings.
        Please note that in order to make these tasks running, you must have set a scheduled task pointing to 'cronjobs/run.php'
        either via a Cron AppTable (Unix server) or via the Scheduled Tasks Manager (Windows server)</p>
        <div class='feedback'></div>
        <section>
            <h2>Tasks list</h2>
            $cronOpt
        </section>
    ";

// Send mail
} elseif (!empty($_GET['op']) && $_GET['op'] == 'mail') {
    $content = "
		<h1>Mailing list</h1>
        <p class='page_description'>Here you can send an email to users who subscribed to the newsletter.</p>
        <div class='feedback'></div>
        <section>
            <h2>Send an email</h2>
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
        </section>";

// Configuration
} elseif (!empty($_GET['op']) && $_GET['op'] == 'config') {
    // Make hours options list
    $timeopt = maketimeopt();

    $content = "
		<h1>Configuration</h1>
        <section>
            <h2>Site parameters</h2>
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
                    <input type='text' name='sitetitle' value='$AppConfig->sitetitle'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label>Allowed file types (upload)</label>
                    <input type='text' name='upl_types' value='$AppConfig->upl_types'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label>Maximum file size (in Kb)</label>
                    <input type='text' name='upl_maxsize' value='$AppConfig->upl_maxsize'>
                </div>
                <div class='feedback' id='feedback_site'></div>
            </form>
        </section>

        <section>
            <h2>Lab information</h2>
            <form method='post' action='' class='form' id='config_form_lab'>
                <div class='submit_btns'>
                    <input type='submit' name='modify' value='Modify' id='submit' class='config_form_lab'>
                </div>
                <input type='hidden' name='config_modify' value='true'/>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='lab_name'>Name</label>
                    <input type='text' name='lab_name' value='$AppConfig->lab_name'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='lab_street'>Street</label>
                    <input type='text' name='lab_street' value='$AppConfig->lab_street'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='lab_postcode'>Post Code</label>
                    <input type='text' name='lab_postcode' value='$AppConfig->lab_postcode'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='lab_city'>City</label>
                    <input type='text' name='lab_city' value='$AppConfig->lab_city'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='lab_country'>Country</label>
                    <input type='text' name='lab_country' value='$AppConfig->lab_country'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='lab_mapurl'>Google Map's URL</label>
                    <input type='text' name='lab_mapurl' value='$AppConfig->lab_mapurl'>
                </div>
                <div class='feedback' id='feedback_lab'></div>
            </form>
        </section>

        <section>
            <h2>Email host information</h2>
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
        </section>";

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
		<h1>News</h1>
        <p class='page_description'>Here you can add a post on the homepage.</p>
        <div style='display: block; width: 100%;'>
            <div style='display: inline-block'>$options</div>
            <div style='display: inline-block'>or</div>
            <div style='display: inline-block'>
                <input type='button' id='submit' class='post_new' value='Add a new post'/>
            </div>
        </div>
        <section>
            <h2>New post</h2>
            <div class='feedback'></div>
            <div class='postcontent'>
            </div>
        </section>
        ";
}

$result = "
<div id='content'>
$content
</div>
";

echo json_encode($result);
