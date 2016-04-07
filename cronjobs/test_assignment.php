<?php
/**
 * Created by PhpStorm.
 * User: Florian
 * Date: 30/03/2016
 * Time: 20:04
 */

include '../includes/boot.php';

$db = new AppDb();
// Get list of users
$Users = new Users($db);
$usersList = $Users->getUsers(true);
$userCount = array();
foreach ($usersList as $key=>$user) {
    $userCount[$user['username']] = 0;
}

//$result = $Assign->assign();

include '../plugins/Assignment/Assignment.php';
$assignement = new Assignment($db);
$assignement->assign(10);
exit;

foreach ($result['content'] as $day=>$assigned) {
    foreach ($assigned as $key=>$speaker) {
        $userCount[$speaker] += 1;
    }
}

var_dump($userCount); exit;

