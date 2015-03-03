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

// Execute cron job
function mailing() {
    global $AppMail, $AppConfig;

	// Count number of users
    $nusers = count($AppMail->get_mailinglist("notification"));

	// today's day
    $cur_date = strtolower(date("l"));

    if ($cur_date == $AppConfig->notification) {
        $content = $AppMail->advertise_mail();
        $body = $AppMail -> formatmail($content['body']);

        $subject = $content['subject'];
        if ($AppMail->send_to_mailinglist($subject,$body,"notification")) {
            $string = "[".date('Y-m-d H:i:s')."]: message sent successfully to $nusers users.\r\n";
        } else {
            $string = "[".date('Y-m-d H:i:s')."]: ERROR message not sent.\r\n";
        }

	    echo($string);

	    // Write log
	    $cronlog = 'mailing_log.txt';
	    if (!is_file($cronlog)) {
	        $fp = fopen($cronlog,"w");
	    } else {
	        $fp = fopen($cronlog,"a+");
	    }
	    fwrite($fp,$string);
	    fclose($fp);
	} else {
		echo "<p>notification day: $AppConfig->notification</p>";
		echo "<p>Today: $cur_date</p>";
	}

}

// Run cron job
mailing();

