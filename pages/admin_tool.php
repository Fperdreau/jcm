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
    $Sessionstypes = explode(',',$AppConfig->session_type);
    $opttypedflt = "";
    foreach ($Sessionstypes as $type) {
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
                    <option value='$AppConfig->jc_day' selected='selected'>$AppConfig->jc_day</option>
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
                    <option value='$AppConfig->jc_time_from' selected='selected'>$AppConfig->jc_time_from</option>
                    $timeopt;
                </select>
            </div>
            <div class='formcontrol' style='width: 10%;'>
                <label>To</label>
                <select name='jc_time_to'>
                    <option value='$AppConfig->jc_time_to' selected='selected'>$AppConfig->jc_time_to</option>
                    $timeopt;
                </select>
            </div>
            <div class='formcontrol' style='width: 30%;'>
                <label>Presentations/Session</label>
                <input type='text' size='3' name='max_nb_session' value='$AppConfig->max_nb_session'/>
            </div>
            <div class='formcontrol' style='width: 30%;'>
                <label>Chair assignement</label>
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

        <div class='section_header'>Email notifications</div>
        <div class='section_content'>
            <form method='post' action='' class='form' id='config_form_jc'>
                <div class='submit_btns'>
                    <input type='submit' name='modify' value='Modify' id='submit' class='config_form_jc'>
                </div>
                <input type='hidden' name='config_modify' value='true'/>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='notification'>Notification day</label>
                    <select name='notification'>
                        <option value='$AppConfig->notification' selected='selected'>$AppConfig->notification</option>
                        <option value='monday'>Monday</option>
                        <option value='tuesday'>Tuesday</option>
                        <option value='wednesday'>Wednesday</option>
                        <option value='thursday'>Thursday</option>
                        <option value='friday'>Friday</option>
                        <option value='saturday'>Saturday</option>
                        <option value='sunday'>Sunday</option>
                    </select>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='reminder'>Reminder (D-)</label>
                    <input type='text' name='reminder' value='$AppConfig->reminder' size='1'>
                </div>
                <div class='feedback' id='feedback_jc'></div>
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
                <div id='db_check' style='display: inline-block;'>
                <label for='backup'>Check db integrity database</label>
                <input type='button' value='Proceed' id='submit' class='db_check'/>
                </div>
                <div class='feedback' id='db_check' style='display: inline-block;'></div><br>

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
