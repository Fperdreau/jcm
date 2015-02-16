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

// Execute cron job
function mailing() {
    // Declare classes
    $mail = new myMail();
    $config = new site_config('get');
    $pub = new Press();
    $ids = $pub->getsession();
    $nextsession = new Press($ids[0]);

	// Number of users
    $nusers = count($mail->get_mailinglist("reminder"));

	// Compare date of the next presentation to today
    $today   = new DateTime(date('Y-m-d'));
    $reminder_day = new DateTime(date("Y-m-d",strtotime($nextsession->date." - $config->reminder days")));
    $send = $today->format('Y-m-d') == $reminder_day->format('Y-m-d');

    //echo "<pre>Today: ".var_dump($today->format('Y-m-d'));
    //echo "<pre>Reminder: ".var_dump($reminder_day->format('Y-m-d'));

    if ($send === true) {
        $content = $mail->reminder_Mail();
        $body = $mail -> formatmail($content['body']);
        $subject = $content['subject'];
        if ($mail->send_to_mailinglist($subject,$body,"reminder")) {
            $string = "[".date('Y-m-d H:i:s')."]: message sent successfully to $nusers users.\r\n";
        } else {
            $string = "[".date('Y-m-d H:i:s')."]: ERROR message not sent.\r\n";
        }
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
mailing();

