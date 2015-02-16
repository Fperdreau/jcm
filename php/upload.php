<?php
session_start();
require_once($_SESSION['path_to_includes']."Press.php");

$pub = new Press();
$result = $pub->upload_file($_FILES['file']);
$result['name'] = false;
if ($result['error'] == true) {
    $name = explode('.',$result['status']);
    $name = $name[0];
    $result['name'] = $name;
}

echo json_encode($result);
exit;
