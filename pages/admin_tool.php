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

// Manage Sessions
if (!empty($_GET['op']) && $_GET['op'] == 'sessions') {
    $sessionlist = Sessions::managesessions();
    $timeopt = maketimeopt();

    //Get session types
    $sessiontype = "";
    $sessiontypes = explode(',',$config->session_type);
    $opttypedflt = "";
    foreach ($sessiontypes as $type) {
        $sessiontype .= "
            <div class='type_div' id='session_$type'>
                <div class='type_name'>$type</div>
                <div class='type_del' data-type='$type' data-class='session'>
                <img src='images/delete.png' style='width: 15px; height: auto;'>
                </div>
            </div>
        ";
        if ($type == $config->session_type_default) {
            $opttypedflt .= "<option value='$type' selected>$type</option>";
        } else {
            $opttypedflt .= "<option value='$type'>$type</option>";
        }
    }

    //Get session types
    $prestype = "";
    $prestypes = explode(',',$config->pres_type);
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

    $result = "
        <div id='content'>
            <span id='pagename'>Manage Sessions</span>
            <p class='page_description'>Here you can manage the journal club sessions, change their type, etc.</p>

            <div class='section_header'>Sessions settings</div>
            <div class='section_content'>
                <form method='post' action='' class='form' id='config_form_session'>
                    <div class='feedback_jc'></div>
                    <input type='hidden' name='config_modify' value='true'>
                    <div class='formcontrol' style='width: 100px;'>
                        <label>Room</label>
                        <input type='text' name='room' value='$config->room'>
                    </div>
                    <div class='formcontrol' style='width: 20%;'>
                        <label for='jc_day'>Day</label>
                        <select name='jc_day'>
                            <option value='$config->jc_day' selected='selected'>$config->jc_day</option>
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
                            <option value='$config->jc_time_from' selected='selected'>$config->jc_time_from</option>
                            $timeopt;
                        </select>
                    </div>
                    <div class='formcontrol' style='width: 10%;'>
                        <label>To</label>
                        <select name='jc_time_to'>
                            <option value='$config->jc_time_to' selected='selected'>$config->jc_time_to</option>
                            $timeopt;
                        </select>
                    </div>
                    <div class='formcontrol' style='width: 30%;'>
                        <label>Presentations/Session</label>
                        <input type='text' size='3' name='max_nb_session' value='$config->max_nb_session'/>
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
                <div class='feedback_session'></div>
                <div class='type_list' id='session'>$sessiontype</div>
                <div class='section_sub'>Presentations</div>
                <input type='button' id='submit' class='type_add'  data-class='pres' value='Add a category'/>
                <input id='new_pres_type' type='text' placeholder='New Category'/>
                <div class='feedback_pres'></div>
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
                $sessionlist
                </div>
            </div>
        </div>
    ";

}

// Manage users
elseif (!empty($_GET['op']) && $_GET['op'] == 'users') {
    $userlist = $config -> generateuserslist();

    $result = "
	    <div id='content'>
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
            <form method='post' action=''>
                <div class='formcontrol' style='width: 100%;'>
                    <label>Subject:</label>
                    <input type='text' size='40' id='spec_head' name='spec_head' placeholder='Subject' value='' />
                </div>
                <div class='formcontrol' style='width: 100%;'>
                    <label>Message</label>
                    <textarea name='spec_msg' id='spec_msg' cols='70' rows='15' class='tinymce'></textarea>
                </div>
                 <p style='text-align: right'><input type='submit' name='send' value='Send' id='submit' class='mailing_send'/></p>
            </form>
        </div>
    </div>
    ";

// Configuration
} elseif (!empty($_GET['op']) && $_GET['op'] == 'config') {
    // Make hours options list
    $timeopt = maketimeopt();

    $result = "
    <div id='content'>
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
                    <input type='text' size='30' name='sitetitle' value='$config->sitetitle'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label>Site url</label>
                    <input type='text' size='30' name='site_url' value='$config->site_url'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label>Oldest DB backups to keep (in days)</label>
                    <input type='text' size='30' name='clean_day' value='$config->clean_day'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label>Allowed file types (upload)</label>
                    <input type='text' size='30' name='upl_types' value='$config->upl_types'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label>Maximum file size (in Kb)</label>
                    <input type='text' size='30' name='upl_maxsize' value='$config->upl_maxsize'>
                </div>
                <div class='feedback_site'></div>
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
                    <input type='text' size='50' name='lab_name' value='$config->lab_name'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='lab_street'>Street</label>
                    <input type='text' size='30' name='lab_street' value='$config->lab_street'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='lab_postcode'>Post Code</label>
                    <input type='text' size='30' name='lab_postcode' value='$config->lab_postcode'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='lab_city'>City</label>
                    <input type='text' size='30' name='lab_city' value='$config->lab_city'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='lab_country'>Country</label>
                    <input type='text' size='30' name='lab_country' value='$config->lab_country'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='lab_mapurl'>Google Map's URL</label>
                    <input type='text' size='30' name='lab_mapurl' value='$config->lab_mapurl'>
                </div>
                <div class='feedback_lab'></div>
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
                        <option value='$config->notification' selected='selected'>$config->notification</option>
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
                    <input type='text' name='reminder' value='$config->reminder' size='1'>
                </div>
                <div class='feedback_jc'></div>
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
                    <input name='mail_from' type='text' value='$config->mail_from'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='mail_from_name'>Sender name</label>
                    <input name='mail_from_name' type='text' value='$config->mail_from_name'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='mail_host'>Email host</label>
                    <input name='mail_host' type='text' value='$config->mail_host'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='SMTP_secure'>SMTP access</label>
                    <select name='SMTP_secure'>
                        <option value='$config->SMTP_secure' selected='selected'>$config->SMTP_secure</option>
                        <option value='ssl'>ssl</option>
                        <option value='tls'>tls</option>
                        <option value='none'>none</option>
                     </select>
                 </div>
                 <div class='formcontrol' style='width: 30%;'>
                    <label for='mail_port'>Email port</label>
                    <input name='mail_port' type='text' value='$config->mail_port'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='mail_username'>Email username</label>
                    <input name='mail_username' type='text' value='$config->mail_username'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='mail_password'>Email password</label>
                    <input name='mail_password' type='password' value='$config->mail_password'>
                </div>
                <div class='formcontrol' style='width: 30%;'>
                    <label for='pre_header'>Email header prefix</label>
                    <input name='pre_header' type='text' value='$config->pre_header'>
                </div>
                <div class='feedback_mail'></div>
            </form>
        </div>
    </div>

    ";

// Add a post
} elseif (!empty($_GET['op']) && $_GET['op'] == 'post') {
    $db_set = new DB_set();
    $user = new users($_SESSION['username']);

    $last = new Posts();
    $last->getlastnews();

    // Make post selection list
    $postlist = $db_set -> getinfo($post_table,"postid");
    $options = "
        <select class='select_post' data-user='$user->fullname'>
            <option value='' selected>Select a post to modify</option>
        ";
    if (!empty($postlist)) {

        foreach ($postlist as $postid) {
            $post = new Posts($postid);
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

    $result = "
    <div id='content'>
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
