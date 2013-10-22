<?php session_start(); ?>
<?php
include_once 'z-config.php';
include_once 'z-db.php';
include_once 'z-util.php';

function check_hack($user, $subj, $type){
	$ip = $_SERVER['REMOTE_ADDR'];
	$mac = "-";
	$in  = date("Y-m-d H:i:s");
	$out = "-"; 
	connect_db(MASTER_TABLE);
	$case1 = mysql_query("SELECT * FROM subj_info WHERE subj_id=\"$subj\" AND status=\"ON\"");
	if( !($case1=mysql_fetch_assoc($case1)) ){
		mysql_query("INSERT INTO log (subj_id,user_id,user_ip,mac,online_time,offline_time,type,status) VALUES (\"$subj\",\"$user\",\"$ip\",\"$mac\",\"$in\",\"$out\",\"$type\",\"Hack [subject]\")");
		close_db();
		return "You trying login section is not online, please stop and explain to admin now!!";
	}
	$case2 = mysql_query("SELECT * FROM log WHERE user_ip=\"$ip\" AND user_id!=\"$user\" AND subj_id=\"$subj\" AND approve=\"N\" AND (status=\"online\" OR status=\"offline\")");
	if($case=mysql_fetch_assoc($case2)){
		$hacker = $case['user_id'];
		$case3 = mysql_query("SELECT * FROM user_info WHERE user_id=\"$hacker\"");
		if($case3=mysql_fetch_assoc($case3));
		else{	
			mysql_query("INSERT INTO log (subj_id,user_id,user_ip,mac,online_time,offline_time,type,status) VALUES (\"$subj\",\"$user\",\"$ip\",\"$mac\",\"$in\",\"$out\",\"$type\",\"Hack [user] by [$hacker]\")");
			close_db();
			return "You trying login another user, please stop and explain to admin now!!";
		}
	}
	else{
		mysql_query("INSERT INTO log (subj_id,user_id,user_ip,mac,online_time,offline_time,type,status) VALUES (\"$subj\",\"$user\",\"$ip\",\"$mac\",\"$in\",\"$out\",\"$type\",\"online\")");
	}
	close_db();
	return "Success";
}
function gateway($user, $pass, &$subj, &$type, &$team, &$db_name){
	connect_db(MASTER_TABLE);
	$res     = mysql_query("SELECT db_name FROM subj_info WHERE subj_id=\"$subj\"") or die(mysql_error());
	$info    = mysql_fetch_assoc($res);
	$db_name = $info['db_name'];
	$res = mysql_query("SELECT type, passwd FROM user_info WHERE user_id=\"$user\"") or die(mysql_error());
	close_db();
	if( $info=mysql_fetch_assoc($res) ){
		if( $info['passwd']==$pass ){
			$type = $info['type'];
		}
		else
			return "Wrong admin password, please try again.";

		if( $info["type"]==USERTYPE_SUPERADMIN ){
			$team 	 = 'super-admin';
			$subj 	 = 'MASTER_TABLE';
			$db_name = 'MASTER_TABLE';
			return "Success";
		}
		else{
			$team = 'admin';
			connect_db(MASTER_TABLE);
			$res = mysql_query("SELECT * FROM owner WHERE user_id=\"$user\" AND subj_id=\"$subj\"") or die(mysql_error());
			close_db();
			if( !($info=mysql_fetch_assoc($res)) )
				return "Sorry, you not have permission admin in this section.";
		}
	}
	else{
		connect_db($db_name);
		$res = mysql_query("SELECT * FROM user_info WHERE user_id=\"$user\"") or die(mysql_error());
		close_db();
		if( $info=mysql_fetch_assoc($res) )
			if( $info["passwd"]==$pass || $pass==NULL ){
				$type = $info["type"];
				$team = $info["grp"];
			}
			else
				return "Wrong password, please try again.";
		else
			return "Sorry, you not have permission in this section.";
	}
	$_SESSION['subj'] = $subj;
	$subj_info = getsubjectinfo();
	if( $subj_info['secure']=='ON' ) return check_hack($user, $subj, $type);
	else							 return "Success";
}

$user = $_POST['user'];
$pass = $_POST['pass'];
$subj = $_POST['subj'];

$message = gateway($user, $pass, $subj, $type, $team, $db_name);
if( $message=="Success" ){
	$_SESSION['user'] = $user;
	$_SESSION['type'] = $type;
	$_SESSION['team'] = $team;
	$_SESSION['subj'] = $subj;
	$_SESSION['db_name'] = $subj;
}
echo $message;

?>

