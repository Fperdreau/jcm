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


/**
 * Send email notification
 */
function mailing() {
    // Declare classes
    global $AppMail, $AppConfig, $Sessions;

    $ids = $Sessions->getsessions(true);
    $nextsession = new Session($ids[0]);

	// Number of users
    $nusers = count($AppMail->get_mailinglist("reminder"));

	// Compare date of the next presentation to today
    $today   = new DateTime(date('Y-m-d'));
    $reminder_day = new DateTime(date("Y-m-d",strtotime($nextsession->date." - $AppConfig->reminder days")));
    $send = $today->format('Y-m-d') == $reminder_day->format('Y-m-d');

    if ($send === true) {
        $content = $AppMail->reminder_Mail();
        $body = $AppMail -> formatmail($content['body']);
        $subject = $content['subject'];
        if ($AppMail->send_to_mailinglist($subject,$body,"reminder")) {
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
        echo "Reminder day: ".$reminder_day->format('Y-m-d');
        echo "nothing to send";
    }
}

/**
 * Run cron job
 */
mailing();

