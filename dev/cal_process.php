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

// Includes required files (classes)
require('../includes/boot.php');
require('calendar.php');

if (!empty($_POST['showCal'])) {
    $nbMonthToShow = $_POST['showCal'];
    $month = $_POST['month'];
    $year = $_POST['year'];
    $session = new Sessions($db);
    $booked = $session->getjcdates();
    $today = date('Y-m-d');
    $calendar = new calendar($db,"friday",$booked,$today,$nbMonthToShow);
    $result = $calendar->make();
    echo json_encode($result);
    exit;
}

if (!empty($_POST['getMonth'])) {
    $nbMonthToShow = $_POST['getMonth'];
    $month = $_POST['month'];
    $year = $_POST['year'];
    $session = new Sessions($db);
    $booked = $session->getjcdates();
    $today = date('Y-m-d');
    $calendar = new calendar($db,"friday",$booked,$today,$nbMonthToShow);
    echo json_encode($calendar->addmonth($month,$year));
    exit;
}
