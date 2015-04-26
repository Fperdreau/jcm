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
 * Run scheduled tasks and send a notification to the admins
 * @return bool
 */
function run() {
    global $db;

    $AppCron = new AppCron($db);
    $runningCron = $AppCron->getRunningJobs();
    $nbJobs = count($runningCron);
    echo "There are $nbJobs task(s) to run.";

    if ($nbJobs == 0) {
        return false;
    } else {
        $logs = '';
        foreach ($runningCron as $job) {
            echo "<p>Running '$job'...</p>";
            $thisJob = $AppCron->instantiateCron($job);
            $result = $thisJob->run();
            echo $result;
            echo "<p>...Done</p>";
            $logs .= "$job: $result<br>";
        }
        return $logs;
    }
}

/**
 * Send logs to admins
 * @param $logs
 * @return bool
 */
function mailLogs($logs) {
    global $db, $AppMail;

    // Send an email to the admins
    $admin = new User($db);
    $admin->get('admin');
    $content = "
            Hello, <br>
            <p>Please find below the logs of the scheduled tasks.</p>
            <div>$logs</div>
            ";
    $body = $AppMail -> formatmail($content);
    $subject = "Scheduled tasks logs";
    if ($AppMail->send_mail($admin->email,$subject,$body)) {
        return true;
    } else {
        return false;
    }
}

// Run scheduled tasks
$logs = run();

// Send logs to admins
if ($logs !== false) {
    mailLogs($logs);
}
