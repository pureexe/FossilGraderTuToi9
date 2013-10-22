<?php
include_once 'z-config.php';
include_once 'z-db.php';
include_once 'z-util.php';

checkauthen();
checkauthorize(USERTYPE_SUPERVISOR, USERTYPE_ADMIN);
?>
<?php
function getuserlist(){
	if($_SESSION['type']==USERTYPE_SUPERVISOR) $team = " WHERE grp=\"".$_SESSION['team']."\"";
	else									   $team = "";
	$userlist = array();
	connect_db();
	mysql_query("LOCK TABLE user_info READ");
	$res = mysql_query("SELECT user_id FROM user_info $team") or die(mysql_error());
	while($info=mysql_fetch_assoc($res)){
		$userlist[] = $info['user_id'];
	}
	mysql_query("UNLOCK TABLES");
	close_db();
	return $userlist;
}
function prepare_source_zip($zip_dir, $zipname){
	$sys_dir = PATH_CONTENT_SOURCE."/".$_SESSION['subj'];
	
	if(is_dir($sys_dir)){
		if(is_dir($zip_dir))
			rmdir_recurse($zip_dir);
		if(mkdir($zip_dir)){
			$list_file = array();
			foreach(getuserlist() as $user){
				if(is_dir("$sys_dir/$user"))
					$list_file = array_merge($list_file, recurse_path("$sys_dir/$user"));
			}
			if(count($list_file)==0 || create_zip($list_file, "$zip_dir/$zipname", $sys_dir)){
				return "success";
			}
			return "Cannot create zip file => $zip_dir/$zipname.";
		}
		return "Cannot create folder team => $zip_dir.";
	}
	return "Not found $sys_dir.";
}
$team = $_SESSION['team'];
$zip_dir = "./data/temp/$team";
$zipname = "$team-Source.zip";
$msg = prepare_source_zip($zip_dir, $zipname);
if($msg=='success')
{
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename=$zipname");
    header("Content-Type: application/zip");
    header("Content-Transfer-Encoding: binary");
    
    readfile("$zip_dir/$zipname");
}
else{
	echo $msg;
}
?>