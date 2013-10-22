<?php
function checkauthen()
{
	session_start();
	if(!isset($_SESSION['user'])) {
		session_destroy();
		echo "<script> window.location = './login-main.php?error=You do not login please login again.';</script>";
		exit();
	}
}
function checkauthorize( $type1, $type2="" )
{
	session_start();
	$type_list = array(USERTYPE_SUPERADMIN, $type1);
	if( $type2!="" )
		$type_list[] = $type2;
	if(!in_array($_SESSION['type'], $type_list)){
		session_destroy();
		echo "<script> window.location = './login-main.php?error=You do not have the permission to access this script.';</script>";
		exit();
	}
}
function getname($user)
{
	$user = $_SESSION['user'];
	$subj = $_SESSION['subj'];
	
	connect_db(MASTER_TABLE);
	$res = mysql_query("SELECT code FROM subj_info WHERE subj_id=\"$subj\"") or die(mysql_error());
	if($info=mysql_fetch_assoc($res)) $message1 = "[".$info['code']."] ";
	else							  $message1 = "";
	close_db();
	
	if($_SESSION['type']==USERTYPE_SUPERADMIN || $_SESSION['type']==USERTYPE_ADMIN) connect_db(MASTER_TABLE);
	else						   													connect_db();
	$res = mysql_query("SELECT name FROM user_info WHERE user_id=\"$user\"");
	if($info=mysql_fetch_assoc($res)) $message2 = $info['name'];
	else							  $message2 = "(none)";
	close_db();
	
	switch($_SESSION['type']){
		case USERTYPE_SUPERADMIN: $message2 = "<font color='#aaffff'>".$message2."</font>"; break;
		case USERTYPE_ADMIN     : $message2 = "<font color='#ffaaff'>".$message2."</font>"; break;
		case USERTYPE_SUPERVISOR: $message2 = "<font color='#ffffaa'>".$message2."</font>"; break;
		case USERTYPE_CONTESTANT: $message2 = "<font color='#aaaaaa'>".$message2."</font>"; break;
		default :;
	}
	return $message2;
}

function getsubjectinfo(){
	connect_db(MASTER_TABLE);
	$res = mysql_query("SELECT * FROM subj_info WHERE subj_id=\"".$_SESSION['subj']."\"") or die(mysql_error());
	close_db();
	return mysql_fetch_assoc($res);
}

function filesize_extend( $pathfile ){
	$units = array('B', 'KB', 'MB', 'GB', 'TB');
	
	$byte = filesize( $pathfile );
	$unit = intval(log($byte, 1024));
	$unit = isset($units[$unit])?$unit:count($units)-1;
	$size = round($byte/pow(1024, $unit), 2)."".$units[$unit];
	
	return $size;
}
function rmdir_recurse($path) {
    $path = rtrim($path, '/').'/';
    $handle = opendir($path);
    while(false !== ($file = readdir($handle))) {
        if($file != '.' and $file != '..' ) {
            $fullpath = $path.$file;
            if(is_dir($fullpath)) rmdir_recurse($fullpath); else unlink($fullpath);
        }
    }
    closedir($handle);
    rmdir($path);
}
function create_zip($files = array(), $destination = '', $root = '', $overwrite = false) {
	if(file_exists($destination) && !$overwrite) { return false; }
	
	$valid_files = array();
	if(is_array($files)) {
		foreach($files as $file) {
			if(file_exists($file)) {
				$valid_files[] = $file;
			}
		}
	}
	if(count($valid_files)) {
		$zip = new ZipArchive();
		if($zip->open($destination,$overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
			return false;
		}
		foreach($valid_files as $file) {
			$zip->addFile($file,str_replace($root,'',$file));
		}
		$zip->close();
		return file_exists($destination);
	}
	else{
		return false;
	}
}
function recurse_path($dir, $break_list=array('.','..')){
	$list_dir = array();
	if(is_dir($dir)){
		foreach(scandir($dir) as $file){
			if(!in_array($file, $break_list)){
				$list_dir = array_merge($list_dir, recurse_path("$dir/$file", $break_list));
			}
		}
	}
	else{
		$list_dir[] = $dir;
	}
	return $list_dir;
}

?>