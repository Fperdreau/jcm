<?php
session_start();
require_once($_SESSION['path_to_includes']."presclass.php");

$pub = new presclass();
$filename = $pub->upload_file($_FILES['upl']);
if ($filename == "no_file") {
    echo '{"status":"no_file"}';	
} elseif ($filename == "failed") {
	echo '{"status":"error"}';	
} else {
	echo '{"status":"'.$filename.'"}';	
}
exit;