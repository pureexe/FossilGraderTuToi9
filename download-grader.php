<?php
include_once 'z-config.php';
include_once 'z-db.php';
include_once 'z-util.php';

checkauthen();
checkauthorize( USERTYPE_ADMIN );
?>
<?php
	$dbname   = $_SESSION['db_name'];
	$subj     = $_SESSION['subj'];
	$dir      = getcwd();
	$path	  = PATH_CONTENT_GRADER;
	$filename = "grader-$subj.bat";
	header("Content-Type: application/force-download");
	header("Content-Type: text/plain");
	header("Content-Type: application/download");
	header("Content-Disposition: attachment; filename=$filename;");
	echo "start \"$subj's grader\" /d $dir\\$path\\ $dir\\$path\\grader.exe $dbname";
?>