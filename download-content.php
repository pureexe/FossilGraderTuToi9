<?php
include_once 'z-config.php';
include_once 'z-db.php';
include_once 'z-util.php';

checkauthen();
?>
<?php
	$filename = $_GET['filename'];
	$pathname = $_GET['pathname'];    
	header("Content-disposition: attachment; filename=$filename");
	header("Content-type: application/pdf");
	readfile("$pathname/$filename");
?>