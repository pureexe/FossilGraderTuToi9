<?php session_start(); ?>
<?php
include_once 'z-db.php';
include_once 'z-util.php';
?>
<?php
function gen_grader_config($system, &$error){
	$template = file_get_contents('./data/grader.template', FILE_USE_INCLUDE_PATH);
	
	$template = str_replace('<<<MYSQL_USER>>>', MYSQL_USER  , $template);
	$template = str_replace('<<<MYSQL_PASS>>>', MYSQL_PASSWD, $template);
	
	if($template!=""){
		$config = fopen('../../grader/grader.conf', 'w');
		fputs($config, $template);
		fclose($config);
		return true;
	}
	else{
		$error .= "[-cannot create z-config.php.]";
		drop_db('master');
		drop_db($system);
		rmdir_recurse("../../grader/ev/$system");
		return false;
	}
}
function gen_z_config($system, &$error){
	$template = file_get_contents('./data/z-config.template', FILE_USE_INCLUDE_PATH);
	
	$template = str_replace('<<<MYSQL_USER>>>', MYSQL_USER  , $template);
	$template = str_replace('<<<MYSQL_PASS>>>', MYSQL_PASSWD, $template);
	
	if($template!=""){
		$config = fopen('./z-config.php', 'w');
		fputs($config, $template);
		fclose($config);
		return true;
	}
	else{
		$error .= "[-cannot create z-config.php.]";
		drop_db('master');
		drop_db($system);
		rmdir_recurse("../../grader/ev/$system");
		return false;
	}
}
function create_master_db($ad_user, $ad_pass, $system, &$error){
	$mysql = mysql_connect("localhost",MYSQL_USER,MYSQL_PASSWD);
	
	$msg = "";
	drop_db('master');
	$result = create_db('master', './data/master.sql', $msg);
	if($result==false){
		$error .= "[-cannot create master db.($msg)]";
		return false;
	}
	
	$msg = "";
	drop_db($system);
	$result = create_db($system, './data/template.sql', $msg);
	if($result==false){
		$error .= "[-cannot create master db.($msg)]";
		drop_db($system);
		return false;
	}
	
	mysql_select_db('master');
	mysql_query("INSERT INTO user_info (user_id,name,passwd,grp,type) VALUES (\"$ad_user\",\"$ad_user\",\"$ad_pass\",\"Admin\",\"A\")");
	$error .= mysql_error();
	mysql_query("INSERT INTO subj_info (subj_id,name,status,db_name)  VALUES (\"$system\",\"$system\",\"ON\",\"$system\")");
	$error .= mysql_error();
	mysql_query("INSERT INTO owner (subj_id,user_id)  VALUES (\"$system\",\"$ad_user\")");
	$error .= mysql_error();
	
	mysql_close($mysql);
	
	if($error==""){
		return true;
	}
	else{
		drop_db('master');
		drop_db($system);
		return false;
	}
}
function move_grader($system, &$error){
	if(!is_dir('../../grader')){
		$result = rename('./data/grader','../../grader');
		if($result==false){
			$error .= "[-cannot move grader.]";
			drop_db('master');
			drop_db($system);
			return false;
		}
	}
	
	gen_grader_config($system, &$error);
	
	$dir = "../../grader/ev/$system";
	if(is_dir($dir))
		rmdir_recurse($dir);
	$result = mkdir($dir);
	if($result==false){
		drop_db('master');
		drop_db($system);
		$error .= "[-cannot create system folder.]";
		return false;
	}
	
	return true;
}
?>
<?php
if( $_SESSION['first-config']=='action' ){
	$my_user = $_POST['my-user'];
	$my_pass = $_POST['my-pass'];
	$ad_user = $_POST['ad-user'];
	$ad_pass = $_POST['ad-pass'];
	$system	 = $_POST['system'];
	
	$error = "";
	if($my_user!=""&&$my_pass!=""&&$ad_user!=""&&$ad_pass!=""&&$system!=""){	
		define("MYSQL_USER"  , $my_user);
		define("MYSQL_PASSWD", $my_pass);
		if(create_master_db($ad_user, $ad_pass, $system, $error)){
			if(move_grader($system, $error)){
				if(gen_z_config($system, $error)){
					echo "success";
				}
				else echo "Cannot create config file.";
			}
			else echo "Cannot move grader.";
		}
		else echo "Cannot create db => $error";
	}
	else echo "Some field is void.";
}
else echo "Not have permission config.";
?>