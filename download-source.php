<?php
include_once 'z-config.php';
include_once 'z-db.php';
include_once 'z-util.php';

checkauthen();
?>
<?php
function get_content($user_id, $prob_id, $sub_num){
	connect_db();
	$res = mysql_query("SELECT code FROM submission WHERE user_id=\"$user_id\" AND prob_id=\"$prob_id\" AND sub_num=$sub_num") or die(mysql_error());
	if($info=mysql_fetch_assoc($res)) $content = $info['code'];
	else							  $content = "";
	close_db();
	echo $content;
}
?>
<?php
	$user_id  = $_SESSION['user'];
	$prob_id  = $_GET['prob'];
	$sub_num  = $_GET['subnum'];
	$filename = $_GET['filename'];
	header("Content-Type: application/force-download");
	header("Content-Type: text/plain");
	header("Content-Type: application/download");
	header("Content-Disposition: attachment; filename=$filename;");
	get_content($user_id, $prob_id, $sub_num);
?>
