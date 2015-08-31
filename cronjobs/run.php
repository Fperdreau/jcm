<?php
/**
 * File for function run
 *
 * PHP version 5
 *
 * @author Florian Perdreau (fp@florianperdreau.fr)
 * @copyright Copyright (C) 2014 Florian Perdreau
 * @license <http://www.gnu.org/licenses/agpl-3.0.txt> GNU Affero General Public License v3
 *
 * This file is part of Journal Club Manager.
 *
 * Journal Club Manager is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Journal Club Manager is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with Journal Club Manager.  If not, see <http://www.gnu.org/licenses/>.
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
    echo "There are $nbJobs task(s) to run.\n";

    if ($nbJobs > 0) {
        $logs = "There are $nbJobs task(s) to run.\n";
        foreach ($runningCron as $job) {
            echo "<p>Running '$job'...</p>";
            $result = null;

            // Instantiate job object
            $thisJob = $AppCron->instantiateCron($job);
            $thisJob->get();

            // Run job
            try {
                $result = $thisJob->run();
                echo $result;
                $logs .= "<p>".date('[Y-m-d H:i:s]') . " $job: $result</p>";
            } catch (Exception $e) {
                $logs .= "<p>Job $job encountered an error: $e->getMessage()</p>";
            }

            // Update new running time
            if ($thisJob->updateTime()) {
                $logs .= "<p>".date('[Y-m-d H:i:s]') . " $job: Next running time: $thisJob->time</p>";
            } else {
                $logs .= "<p>".date('[Y-m-d H:i:s]') . " $job: Could not update the next running time</p>";
            }

            // Write log
            try {
                $AppCron->logger("$thisJob->name.txt", $result);
            } catch (Exception $e) {
                echo "Could not write log";
            }
            echo "<p>...Done</p>";
        }
        return $logs;
    } else {
        return false;
    }
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
            <p>Hello, </p>
            <p>Please find below the logs of the scheduled tasks.</p>
            <div style='display: block; padding: 10px; margin: 0 30px 20px 0; border: 1px solid #ddd; background-color: rgba(255,255,255,1);'>
                <div style='color: #444444; margin-bottom: 10px;  border-bottom:1px solid #DDD; font-weight: 500; font-size: 1.2em;'>
                    Logs
                </div>
                <div style='padding: 5px; background-color: rgba(255,255,255,.5); display: block;'>
                    $logs
                </div>
            </div>";
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
if ($logs !== false) {
    mailLogs($logs);
}

