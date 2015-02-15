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

@session_start();
require_once($_SESSION['path_to_includes'].'includes.php');
check_login(array("organizer","admin"));

// Declare classes
$user = new users();
$user->get($_SESSION['username']);
$mail = new myMail();
$config = new site_config('get');
// Manage users
if (!empty($_GET['op']) && $_GET['op'] == 'users') {
    $userlist = $config -> generateuserslist();

    $result = "
	    <div id='content'>
			<span id='pagename'>Manage Users</span>
            <p class='page_description'>Here you can modify users status and activate, deactivate or delete user accounts.</p>
	        <div class='section_header'>Manage users</div>
            <div class='section_content'>
                <div class='feedback'></div>
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
            	<div id='user_list'>
				$userlist
				</div>
			</div>
		</div>
	";

// Send mail
} elseif (!empty($_GET['op']) && $_GET['op'] == 'mail') {
    $result = "
    <div id='content'>
		<span id='pagename'>Mailing list</span>
        <p class='page_description'>Here you can send an email to users who subscribed to the newsletter.</p>
        <div class='feedback'></div>
        <div class='section_header'>Send an email</div>
        <div class='section_content'>
            <form method='post' action='' class='form'>
                <label for='spec_head' class='label'>Subject:</label><input type='text' size='40' id='spec_head' name='spec_head' value='' /></br>
                <label for='spec_msg' class='label'>Message:</label></br><br>
                <textarea name='spec_msg' id='spec_msg' cols='70' rows='15' class='tinymce'></textarea></br>
                 <p style='text-align: right'><input type='submit' name='send' value='Send' id='submit' class='mailing_send'/></p>
            </form>
        </div>
    </div>
    ";

// Configuration
} elseif (!empty($_GET['op']) && $_GET['op'] == 'config') {
    // Make hours options list
    $start = "07:00";
    $end = "20:00";

    $tStart = strtotime($start);
    $tEnd = strtotime($end);
    $tNow = $tStart;
    $timeopt = "";
    while($tNow <= $tEnd){
        $opt =  date("H:i",$tNow);
        $timeopt .= "<option value='$opt'>$opt</option>";
        $tNow = strtotime('+30 minutes',$tNow);
    }

    $result = "
    <div id='content'>
		<span id='pagename'>Configuration</span>
        <div class='section_header'>Site parameters</div>
        <div class='section_content'>
            <form method='post' action='' class='form' id='config_form_site'>
                <div class='feedback_site'></div>
                <input type='hidden' name='config_modify' value='true'/>
                <label for='sitetitle' class='label'>Site title</label><input type='text' size='30' name='sitetitle' value='$config->sitetitle' /><br>
                <label for='site_url' class='label'>Site url</label><input type='text' size='30' name='site_url' value='$config->site_url' /></br>
                <label for='clean_day' class='label'>Oldest DB backups to keep (in days)</label><input type='text' size='30' name='clean_day' value='$config->clean_day' /></br>
                <label for='upl_types' class='label'>Allowed file types (upload)</label><input type='text' size='30' name='upl_types' value='$config->upl_types' /></br>
                <label for='upl_maxsize' class='label'>Maximum file size (in Kb)</label><input type='text' size='30' name='upl_maxsize' value='$config->upl_maxsize' />
                <p style='text-align: right'><input type='submit' name='modify' value='Modify' id='submit' class='config_form_site'/></p>
            </form>
        </div>

        <div class='section_header'>Lab information</div>
        <div class='section_content'>
            <form method='post' action='' class='form' id='config_form_lab'>
                <div class='feedback_lab'></div>
                <input type='hidden' name='config_modify' value='true'/>
                <label for='lab_name' class='label'>Name</label><input type='text' size='50' name='lab_name' value='$config->lab_name'/></br>
                <label for='lab_street' class='label'>Street</label><input type='text' size='30' name='lab_street' value='$config->lab_street'/></br>
                <label for='lab_postcode' class='label'>Post Code</label><input type='text' size='30' name='lab_postcode' value='$config->lab_postcode'/></br>
                <label for='lab_city' class='label'>City</label><input type='text' size='30' name='lab_city' value='$config->lab_city'/></br>
                <label for='lab_country' class='label'>Country</label><input type='text' size='30' name='lab_country' value='$config->lab_country'/><br>
                <label for='lab_mapurl' class='label'>Google Map's URL</label><input type='text' size='30' name='lab_mapurl' value='$config->lab_mapurl'/>
                <p style='text-align: right'><input type='submit' name='modify' value='Modify' id='submit' class='config_form_lab'/></p>
            </form>
        </div>

        <div class='section_header'>Journal club parameters</div>
        <div class='section_content'>
            <form method='post' action='' class='form' id='config_form_jc'>
                <div class='feedback_jc'></div>
                <input type='hidden' name='config_modify' value='true'/>
                <label for='room' class='label'>Room</label><input type='text' size='10' name='room' value='$config->room' /><br>
                <label for='jc_day' class='label'>Day</label>
                <select name='jc_day'>
                    <option value='$config->jc_day' selected='selected'>$config->jc_day</option>
                    <option value='monday'>Monday</option>
                    <option value='tuesday'>Tuesday</option>
                    <option value='wednesday'>Wednesday</option>
                    <option value='thursday'>Thursday</option>
                    <option value='friday'>Friday</option>
                </select></br>
                <label for='jc_time_from' class='label'>From</label>
                    <select name='jc_time_from'>
                        <option value='$config->jc_time_from' selected='selected'>$config->jc_time_from</option>
                        $timeopt;
                    </select>
                <label for='jc_time_to'>To</label>
                    <select name='jc_time_to'>
                        <option value='$config->jc_time_to' selected='selected'>$config->jc_time_to</option>
                        $timeopt;
                    </select><br>
                <label for='max_nb_session' class='label'>Nb of presentation per session</label><input type='text' size='3' name='max_nb_session' value='$config->max_nb_session'/><br>
                <label for='notification' class='label'>Notification day</label>
                    <select name='notification'>
                        <option value='$config->notification' selected='selected'>$config->notification</option>
                        <option value='monday'>Monday</option>
                        <option value='tuesday'>Tuesday</option>
                        <option value='wednesday'>Wednesday</option>
                        <option value='thursday'>Thursday</option>
                        <option value='friday'>Friday</option>
                        <option value='saturday'>Saturday</option>
                        <option value='sunday'>Sunday</option>
                    </select></br>
                <label for='reminder' class='label'>Reminder (D-)</label>
                    <input type='text' name='reminder' value='$config->reminder' size='1'/>
                    <p style='text-align: right'><input type='submit' name='modify' value='Modify' id='submit' class='config_form_jc'/></p>
            </form>
        </div>

        <div class='section_header'>Email host information</div>
        <div class='section_content'>
            <form method='post' action='' class='form' id='config_form_mail'>
                <div class='feedback_mail'></div>
                <input type='hidden' name='config_modify' value='true'/>
                <label for='mail_from' class='label'>Sender Email address</label><input name='mail_from' type='text' value='$config->mail_from'></br>
                <label for='mail_from_name' class='label'>Sender name</label><input name='mail_from_name' type='text' value='$config->mail_from_name'></br>
                <label for='mail_host' class='label'>Email host</label><input name='mail_host' type='text' value='$config->mail_host'></br>
                <label for='SMTP_secure' class='label'>SMTP access</label>
                    <select name='SMTP_secure'>
                        <option value='$config->SMTP_secure' selected='selected'>$config->SMTP_secure</option>
                        <option value='ssl'>ssl</option>
                        <option value='tls'>tls</option>
                        <option value='none'>none</option>
                     </select><br>
                <label for='mail_port' class='label'>Email port</label><input name='mail_port' type='text' value='$config->mail_port'></br>
                <label for='mail_username' class='label'>Email username</label><input name='mail_username' type='text' value='$config->mail_username'></br>
                <label for='mail_password' class='label'>Email password</label><input name='mail_password' type='password' value='$config->mail_password'></br>
                <label for='pre_header' class='label'>Email header prefix</label><input name='pre_header' type='text' value='$config->pre_header'>
                <p style='text-align: right'><input type='submit' name='modify' value='Modify' id='submit' class='config_form_mail'/></p>
            </form>
        </div>
    </div>

    ";

// Add a post
} elseif (!empty($_GET['op']) && $_GET['op'] == 'post') {
    $post = new Posts();
    $post->getlastnews();

    $result = "
    <div id='content'>
		<span id='pagename'>News</span>
         <p class='page_description'>Here you can add a post on the homepage.</p>
         <div class='feedback'></div>
        <div class='section_header'>New post</div>
        <div class='section_content'>
            <form method='post' action='' class='form'>
            <input type='hidden' id='fullname' value='$user->fullname'/>
            <label for='new_post'>Message</label></br><br>
            <textarea name='new_post' cols='70' rows='15' id='post' class='tinymce'></textarea></br>
            <p style='text-align: right'><input type='submit' name='submit_post' value='Post' id='submit' class='post_send'/></p>
            </form>
        </div>
    </div>
        ";

// Admin tools page
} elseif  (!empty($_GET['op']) && $_GET['op'] == 'tools') {
    $result = "
    <div id='content'>
		<span id='pagename'>Admin tools</span>
        <div class='section_header'>Tools</div>
        <div class='section_content'>

            <div id='db_backup'>
            <label for='backup'>Backup database</label>
            <input type='button' name='backup' value='Proceed' id='submit' class='dbbackup'/>
            </div><br>

            <div id='full_backup'>
            <label for='full_backup'>Full backup (database + files)</label>
            <input type='button' name='full_backup' value='Proceed' id='submit' class='fullbackup'/>
            </div>

        </div>
    </div>";
}

echo json_encode($result);
