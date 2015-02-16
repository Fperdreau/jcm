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

	// Count number of users
    $nusers = count($mail->get_mailinglist("notification"));

	// today's day
    $cur_date = strtolower(date("l"));

    if ($cur_date == $config->notification) {
        $content = $mail->advertise_mail();
        $body = $mail -> formatmail($content['body']);
        $subject = $content['subject'];
        if ($mail->send_to_mailinglist($subject,$body,"notification")) {
            $string = "[".date('Y-m-d H:i:s')."]: message sent successfully to $nusers users.\r\n";
        } else {
            $string = "[".date('Y-m-d H:i:s')."]: ERROR message not sent.\r\n";
        }

	    echo($string);

	    // Write log
	    $cronlog = 'mailing_log.txt';
	    if (!is_file($cronlog)) {
	        $fp = fopen($cronlog,"w");
	        chmod($cronlog,0777);
	    } else {
	        $fp = fopen($cronlog,"a+");
	        chmod($cronlog,0777);
	    }
	    fwrite($fp,$string);
	    fclose($fp);
	    chmod($cronlog,0644);
	} else {
		echo "<p>notification day: $config->notification</p>";
		echo "<p>Today: $cur_date</p>";
	}

}

// Run cron job
mailing();

