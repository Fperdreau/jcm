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

    $logs = "There are $nbJobs task(s) to run.\n";
    foreach ($runningCron as $job) {
        echo "<p>Running '$job'...</p>";
        try {
            $thisJob = $AppCron->instantiateCron($job);
            $thisJob->get();
            $result = $thisJob->run();
            echo $result;
            echo "<p>...Done</p>";
            $logs .= date('[Y-m-d H:i:s]')." $job: $result<br>";
            $thisJob->updateTime();
        } catch (Exception $e) {
            $logs .= "Job $job encountered an error: $e->getMessage()";
        }
    }
    return $logs;
}

/**
 * Send logs to admins
 * @param $logs
 * @return bool
 */
function mailLogs($logs) {
    global $db, $AppMail;

    // Get admins email
    $adminMails = $db->getinfo($db->tablesname['User'],'email',array('status'),array("'admin'"));
    $content = "
            Hello, <br>
            <p>Please find below the logs of the scheduled tasks.</p>
            <div>$logs</div>
            ";
    $body = $AppMail -> formatmail($content);
    $subject = "Scheduled tasks logs";
    if ($AppMail->send_mail($adminMails,$subject,$body)) {
        return true;
    } else {
        return false;
    }
}

// Run scheduled tasks
$logs = run();

// Send logs to admins
mailLogs($logs);

