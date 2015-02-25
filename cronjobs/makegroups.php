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
$_SESSION['app_name'] = basename(dirname(__DIR__));
$_SESSION['path_to_app'] = dirname(dirname(__FILE__))."/";
$_SESSION['path_to_includes'] = $_SESSION['path_to_app']."includes/";
date_default_timezone_set('Europe/Paris');

// Includes
require_once($_SESSION['path_to_includes'].'includes.php');

function makegroups($ngroups=2) {
    require($_SESSION['path_to_app'].'config/config.php');

    // Get presentations of the next session
    $pub = new Presentation();
    $ids = $pub->getsession();
    $npres = count($ids);
    $nextsession = new Presentation($ids[0]);

    // Set the number of groups equal to the number of presentation for this day in case it exceeds it.
    if ($ngroups > $npres) {$ngroups = $npres;}

    // Get emails from db
    $db_set = new DB_set();
    $sql = "SELECT username FROM $users_table WHERE notification=1 and active=1";
    $req = $db_set->send_query($sql);
    $users = array();
    while ($row = mysqli_fetch_assoc($req)) {
        $users[] = $row['username'];
    }

    // If the nb of users if smaller than the nb of groups, we shut this beast down!
    $nusers = count($users); // total nb of users
    if ($nusers <= $ngroups) {echo "Not enough users to make groups"; return false;}

    // First we assign speakers and chairs to each group
    $session = new Session($nextsession->date);
    $assigned_groups = array();
    $excludedusers = array():
    foreach ($ids as $pubid) {
        $pub = new Presentation($pubid);
        $assigned_groups[$ids[$i]] = array($pub->orator,$session->chairs[$i]);
        $excludesusers = array_push($excludedusers,array($pub->orator,$session->chairs[$i]));
    }
    $remainusers = array_diff($users,$excludedusers);

    // Shuffle the users
    shuffle($remainusers);

    // Make groups
    $qtity = ceil(count($remainusers/$ngroups); // nb of users per group
    $groups = array_chunk($remainusers,$qtity);

    // Assign presentation
    for ($i=0;$i<count($groups);$i++) {
        $assigned_groups[$ids[$i]] = $groups[$i];
    }

    return $assigned_groups;
}

// Execute cron job
function mailing($assigned_groups) {
    // Declare classes
    $mail = new myMail();
    $config = new site_config('get');
    $pub = new Presentation();
    $ids = $pub->getsession();
    $nextsession = new Presentation($ids[0]);

    // Number of users
    $ngroups = count($assigned_groups);
    $nusers = count($mail->get_mailinglist("reminder"));

    // Compare date of the next presentation to today
    $today   = new DateTime(date('Y-m-d'));
    $reminder_day = new DateTime(date("Y-m-d",strtotime($nextsession->date." - $config->reminder days")));
    $send = $today->format('Y-m-d') == $reminder_day->format('Y-m-d');

    // Make email
    if ($send === false) {
        $string = "";
        foreach ($assigned_groups as $presid=>$group) {
            $pres = new Presentation($presid);
            $type = ucfirst($pres->type);

            // Get file list for this presentation
            $filelist = explode(',',$pres->link);
            $filecontent = "";
            foreach ($filelist as $file) {
                $ext = explode('.',$file);
                $ext = strtoupper($ext[1]);
                $urllink = $config->site_url."uploads/".$file;
                $filecontent .= "<div style='display: inline-block; height: 15px; line-height: 15px; text-align: center; padding: 5px; white-space: pre-wrap; min-width: 40px; width: auto; margin: 5px; cursor: pointer; background-color: #bbbbbb; font-weight: bold;'><a href='$urllink' target='_blank'>$ext</a></div>";
            }

            // Display details about this presentation
            $pubcontent = "
            <div style='width: 95%; margin: 10px auto; border: 1px solid #aaaaaa;'>
                <div style='background-color: #CF5151; color: #eeeeee; padding: 5px; text-align: left; font-weight: bold; font-size: 16px;'>
                    Your assignment
                </div>

                <div style='width: 100%; padding-bottom: 5px; margin: auto auto 10px auto; background-color: rgba(255,255,255,.5); border: 1px solid #bebebe;'>
                    <div style='display: block; position: relative; margin: 0 0 5px; text-align: center; height: 20px; line-height: 20px; width: 100px; background-color: #555555; color: #FFF; padding: 5px;'>
                        $type
                    </div>
                    <div style='width: 95%; margin: auto; padding: 5px 10px 0px 10px; background-color: rgba(250,250,250,1); border-bottom: 5px solid #aaaaaa;'>
                        <span style='font-weight: bold;'>Title:</span> $pres->title<br>
                        <div style='display: inline-block; margin-left: 0;'><b>Authors:</b> $pres->authors</div>
                        <div style='display: inline-block; float:right;'><b>Presented by:</b> $pres->orator</div>
                    </div>
                    <div style='width: 95%; text-align: justify; margin: auto; background-color: #eeeeee; padding: 10px;'>
                        <span style='font-style: italic; font-size: 13px;'>$pres->summary</span>
                    </div>
                    <div style='display: block; test-align: justify; width: 95%; min-height: 20px; height: auto; margin: auto; background-color: #444444;'>
                        $filecontent
                    </div>
                 </div>
            </div>";

            foreach($group as $username) {
                $groupmembers = array_diff($group,array($username));
                $groupcontent = "";
                foreach($groupmembers as $grpmember) {
                    $grpuser = new users($grpmember);
                    if (in_array($grpuser,$session->chairs)) {
                        $role = "Chair";
                    } elseif (in_array($grpuser,$session->speakers)) {
                        $role = "Speaker";
                    } else {
                        $role = "Public";
                    }
                    $groupcontent .= "<li>$grpuser->fullname ($role)</li>";
                }
                $user = new users($username);
                $content = "
                    <div style='width: 95%; margin: auto; font-size: 16px;'>
                        <p>Hello $user->firstname,</p>
                        <p>Here is your assignment for our next journal club session that will be held on the $pres->date.</p>
                        <p>Your role for this session will be $role</p>
                        <p>Group members:</p>
                        <ul>$groupcontent</ul>
                    </div>
                    $pubcontent
                    $rolecontent
                    ";
                $body = $mail -> formatmail($content);
                print_r($body);exit;
                $subject = "Assignment - $pres->date";
                if ($mail->send_mail($user->email,$subject,$body)) {
                    $nsent += 1;
                } else {
                    $string .= "[".date('Y-m-d H:i:s')."]: ERROR message not sent to $user->email.\r\n";
                }
            }
        }
        $string .= "[".date('Y-m-d H:i:s')."]: Message sent to $nsent users.\r\n";

        echo($string);

        // Write log
        $cronlog = 'reminder_log.txt';
        if (!is_file($cronlog)) {
            $fp = fopen($cronlog,"w");
        } else {
            $fp = fopen($cronlog,"a+");
        }
        fwrite($fp,$string);
        fclose($fp);
    } else {
        echo "nothing to send";
    }
}

// Run cron job
$assigned_groups = makegroups(2); // Make groups
print_r($assigned_groups);
mailing($assigned_groups); // Send emails to users

