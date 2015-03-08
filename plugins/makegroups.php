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

function makegroups($ngroups=2) {
    global $Sessions, $db;

    // Get presentations of the next session
    $nextdate = $Sessions->getsessions(1);
    $session = new Session($db,$nextdate[0]);

    // Set the number of groups equal to the number of presentation for this day in case it exceeds it.
    if ($ngroups > $session->nbpres) $ngroups = $session->nbpres;
    // Get emails from db
    $sql = "SELECT username FROM ".$db->tablesname['User']." WHERE notification=1 and active=1";
    $req = $db->send_query($sql);
    $users = array();
    while ($row = mysqli_fetch_assoc($req)) {
        $users[] = $row['username'];
    }

    // If the nb of users if smaller than the nb of groups, we shut this beast down!
    $nusers = count($users); // total nb of users

    if ($nusers <= $ngroups) {echo "<p>we found $nusers users for $ngroups groups</p> => Not enough users to make groups"; return false;}
    if ($session->type == "none") {echo "No meeting for the next session"; return false;}

    // First we assign speakers and chairs to each group
    $chairs = explodecontent(',',$session->chairs);
    $speakers = explodecontent(',',$session->speakers);
    $presids = explodecontent(',',$session->presid);

    $assigned_groups = array();
    $excludedusers = array();
    for ($i=0;$i<$session->nbpres;$i++) {
        $assigned_groups[$presids[$i]] = array($speakers[$i],$chairs[$i]);
        array_merge($excludedusers,array($speakers[$i],$chairs[$i]));
    }
    $remainusers = array_values(array_diff($users,$excludedusers));

    // Shuffle the remaining users
    shuffle($remainusers);

    // Make groups
    $qtity = ceil(count($remainusers)/$ngroups); // nb of users per group
    $groups = array_chunk($remainusers,$qtity);

    // Assign presentation
    for ($i=0;$i<$session->nbpres;$i++) {
        array_merge($assigned_groups[$presids[$i]],$groups[$i]);
    }

    return $assigned_groups;
}

// Execute cron job
/**
 * @param $assigned_groups
 */
function mailing($assigned_groups) {
    global $Sessions, $db, $AppMail, $AppConfig;

    // Declare classes
    $nextdate = $Sessions->getsessions(1);
    $session = new Session($db,$nextdate[0]);
    $chairs = explodecontent(',',$session->chairs);
    $presids = explodecontent(',',$session->presid);
    $speakers = explodecontent(',',$session->speakers);

    $rooms = array("B.2.15","B.1.38");

    // Compare date of the next presentation to today
    $today   = new DateTime(date('Y-m-d'));
    $reminder_day = new DateTime(date("Y-m-d",strtotime($session->date." - $AppConfig->reminder days")));
    $send = $today->format('Y-m-d') == $reminder_day->format('Y-m-d');
    $nsent = 0;
    // Make email
    if ($send === false) {
        $string = "";
        for ($i=0;$i<$session->nbpres;$i++) {
            $presid = $presids[$i];
            $group = $assigned_groups[$presid];
            $chair = new User($db,$chairs[$i]);

            /** @var Presentation $pres */
            $pres = new Presentation($db,$presid);
            $type = ucfirst($pres->type);

            // Get file list for this presentation
            $filelist = explodecontent(',',$pres->link);
            $filecontent = "";
            if (!empty($filelist)) {
                foreach ($filelist as $file) {
                    $ext = explode('.',$file);
                    $ext = strtoupper($ext[1]);
                    $urllink = $AppConfig->site_url."uploads/".$file;
                    $filecontent .= "<div style='display: inline-block; height: 15px; line-height: 15px;
                text-align: center; padding: 5px; white-space: pre-wrap; min-width: 40px; width: auto;
                 margin: 5px; cursor: pointer; background-color: #bbbbbb; font-weight: bold;'>
                 <a href='$urllink' target='_blank'>$ext</a></div>";
                }
            }

            // Display details about this presentation
            $pubcontent = "
            <div style='width: 95%; margin: 10px auto; border: 1px solid #aaaaaa;'>
                <div style='background-color: #CF5151; color: #eeeeee; padding: 5px; text-align: left; font-weight: bold; font-size: 16px;'>
                    Your Group Presentation
                </div>

                <div style='width: 100%; padding-bottom: 5px; margin: auto auto 10px auto; background-color: rgba(255,255,255,.5); border: 1px solid #bebebe;'>
                    <div style='display: block; position: relative; margin: 0 0 5px; text-align: center; height: 20px; line-height: 20px; width: 100px; background-color: #555555; color: #FFF; padding: 5px;'>
                        $type
                    </div>
                    <div style='width: 95%; margin: auto; padding: 5px 10px 0 10px; background-color: rgba(250,250,250,1); border-bottom: 5px solid #aaaaaa;'>
                        <span style='font-weight: bold;'>Title:</span> $pres->title<br>
                        <div style='display: inline-block; margin-left: 0;'><b>Authors:</b> $pres->authors</div>
                        <div style='display: inline-block; float:right;'><b>Speaker:</b> $pres->orator</div>
                        <div style='margin-left: 30px; display: inline-block;'><b>Chair:</b> $chair->fullname</div>
                    </div>
                    <div style='width: 95%; text-align: justify; margin: auto; background-color: #eeeeee; padding: 10px;'>
                        <span style='font-style: italic; font-size: 13px;'>$pres->summary</span>
                    </div>
                    <div style='display: block; text-align: justify; width: 95%; min-height: 20px; height: auto; margin: auto; background-color: #444444;'>
                        $filecontent
                    </div>
                 </div>
            </div>";

            $g = 0;
            foreach($group as $username) {
                $room = $rooms[$g];
                $g++;
                $groupcontent = "";
                foreach($group as $grpmember) {
                    $grpuser = new User($db,$grpmember);
                    if (in_array($grpuser->username,$chairs)) {
                        $role = "Chair";
                    } elseif (in_array($grpuser->username,$speakers)) {
                        $role = "Speaker";
                    } else {
                        $role = "Public";
                    }
                    if ($grpuser->username == $username) {
                        $groupcontent .= "<li><span style='color: #CF5151'>YOU</span> ($role)</li>";
                    } else {
                        $groupcontent .= "<li>$grpuser->fullname ($role)</li>";
                    }
                }

                $user = new User($db,$username);
                $content = "
                <div style='width: 95%; margin: auto; font-size: 16px;'>
                    <p>Hello <span style='font-weight: 600;'>$user->firstname</span>,</p>
                    <p>Here is your assignment for our next journal club session that will be held on the $pres->date in room <b>$room</b>.</p>
                    <p>Your group:</p>
                    <ul>$groupcontent</ul>
                </div>
                $pubcontent
                ";

                $body = $AppMail -> formatmail($content); print_r($body); exit;
                $subject = "Your group assignment - $pres->date";
                if ($AppMail->send_mail($user->email,$subject,$body)) {
                    $nsent += 1;
                } else {
                    $string .= "[".date('Y-m-d H:i:s')."]: ERROR message not sent to $user->email.\r\n";
                }

            }
        }
        $string .= "[".date('Y-m-d H:i:s')."]: Message sent to $nsent users.\r\n";

        echo($string);

        // Write log
        $cronlog = 'assignchairs.txt';
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
$day = "Saturday"; // Line to change if you want to run the cron job another day
$today = date('l');
if ($day == $today) {
    $assigned_groups = makegroups(2); // Make groups
    if ($assigned_groups !== false)
        mailing($assigned_groups); // Send emails to users
} else {
    echo "Assignment Day is $day but we are on $today. So nothing to send today!";
}




