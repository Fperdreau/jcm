<?php
/**
 * Created by PhpStorm.
 * User: U648170
 * Date: 7-4-2015
 * Time: 17:08
 */


function parseTime($dayNb,$dayName,$hour) {

    $today = date('Y-m-d');
    $month = date('m');
    $year = date('Y');
    $now = date('H:i:s');

    $timestamp = mktime(0,0,0,$month,1,$year);
    $maxday = date("t",$timestamp); // Last day of the current month
    echo "max day: $maxday<br>";
    $dayNb = ($dayNb>$maxday) ? $maxday:$dayNb;
    echo "day: $dayNb<br>";
    if ($dayNb > 0) {
        $strday = date('Y-m-d',strtotime("$year-$month-$dayNb"));
    } elseif ($dayName !=='All') {
        $strday = date('Y-m-d',strtotime("next $dayName"));
    } else {
        $strday = ($hour < $now) ? date('Y-m-d',strtotime('+ 1 day')) : $today;
    }
    echo "strday: $strday<br>";
    $strtime = date('H:i:s',strtotime("$hour:00:00"));
    $time = $strday.' '.$strtime;
    return $time;
}

$time = parseTime(0,'Wednesday',1);
echo $time;