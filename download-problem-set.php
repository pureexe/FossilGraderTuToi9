<?php
include_once 'z-config.php';
include_once 'z-db.php';
include_once 'z-util.php';

checkauthen();
checkauthorize(USERTYPE_SUPERVISOR, USERTYPE_ADMIN);

?>
<?php
function getproblist(){
	$problist = array();
	connect_db();
	mysql_query("LOCK TABLE prob_info READ");
	$res = mysql_query("SELECT prob_id FROM prob_info WHERE avail=\"ON\"");
	while($info=mysql_fetch_assoc($res)){
		$problist[] = $info['prob_id'];
	}
	mysql_query("UNLOCK TABLES");
	close_db();
	return $problist;
}
function prepare_problem_zip($zip_dir, $zipname){
	$sys_dir = PATH_CONTENT_PROBLEM."/".$_SESSION['subj'];
	
	if(is_dir($sys_dir)){
		if(is_dir($zip_dir))
			rmdir_recurse($zip_dir);
		if(mkdir($zip_dir)){
			$list_file = array();
			foreach(getproblist() as $prob){
				$list_file = array_merge($list_file, recurse_path("$sys_dir/$prob", array('.','..','config','thumbnails')));
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
$zipname = "Problem.zip";
if(prepare_problem_zip($zip_dir, $zipname)=='success')
{
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename=$zipname");
    header("Content-Type: application/zip");
    header("Content-Transfer-Encoding: binary");
    
    readfile("$zip_dir/$zipname");
}
?>