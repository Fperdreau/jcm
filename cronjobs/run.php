<?php
/**
 * Created by PhpStorm.
 * User: florian
 * Date: 07/04/15
 * Time: 08:33
 */

require('../includes/boot.php');

function run() {
    global $db;
    $AppCron = new AppCron($db);
    $runningCron = $AppCron->getRunningJobs();
    $nbJobs = count($runningCron);
    echo "There are $nbJobs task(s) to run.";

    foreach ($runningCron as $job) {
        echo "<p>Running '$job'...</p>";
        $thisJob = $AppCron->instantiateCron($job);
        $result = $thisJob->run();
        echo $result;
        echo "<p>...Done</p>";
    }
}

run();