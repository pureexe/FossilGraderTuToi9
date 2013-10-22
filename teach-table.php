<?php
include_once 'z-config.php';
include_once 'z-db.php';
include_once 'z-util.php';

checkauthen();
checkauthorize( USERTYPE_ADMIN );
?>
<?php
function get_subj_list(){
	$subj_list = array();
	$res = mysql_query("SELECT subj_id FROM subj_info ORDER BY subj_id") or die(mysql_error());
	for($subj_list=array() ; $row=mysql_fetch_assoc($res) ; $subj_list[] = $row["subj_id"]);
	return $subj_list;
}
function get_user_list(){
	$user_list = array();
	$res = mysql_query("SELECT user_id FROM user_info WHERE type=\"".USERTYPE_ADMIN."\" ORDER BY user_id") or die(mysql_error());
	for($user_list=array() ; $row=mysql_fetch_assoc($res) ; $user_list[] = $row["user_id"]);
	return $user_list;
}
function get_teach_list(){
	$teach_list = array();
	$res = mysql_query("SELECT user_id, subj_id FROM owner") or die(mysql_error());
	for($teach_list=array() ; $row=mysql_fetch_assoc($res) ; $teach_list[$row['user_id']][$row['subj_id']] = true);
	return $teach_list;
}
function delete(){
	$subj = $_POST['subj'];
	$user = $_POST['user'];
	
	if(mysql_query("DELETE FROM owner WHERE user_id=\"$user\" AND subj_id=\"$subj\"") or die(mysql_error()))	echo"success";
	else																										echo"fail";
}
function update(){
	$subj = $_POST['subj'];
	$user = $_POST['user'];
	
	if(mysql_query("INSERT INTO owner (user_id, subj_id) VALUES (\"$user\",\"$subj\") ") or die(mysql_error()))	echo"success";
	else																										echo"fail";
}
function display(){
	$subj_list  = get_subj_list();
	$user_list  = get_user_list();
	$teach_list = get_teach_list();
	echo "
	<table id='teach-list-info' class='table table-bordered table-condensed' style='background-color: eee; font-size: 10pt; margin-bottom: 0px;'>
		<thead>
			<tr>
				<th style='text-align: center; background-color:#eee;'>Privilage</th>";
				foreach($subj_list as $subj)
					echo "<th style='text-align: center; background-color:#eee;'>$subj</th>";
	echo "	</tr>
		</thead>
		<tbody>";
	foreach($user_list as $user){
		echo"<tr class='$user'>
				<td style='text-align: center;'><b>$user</b></td>";
				foreach($subj_list as $subj){
					echo"<td class='$subj' style='text-align: center;'>";
					if(isset($teach_list[$user][$subj])) echo"<button type='button' class='btn btn-info active' style='width:100%; margin:0; padding:0;' onclick='action_toggle(this);'>Yes</button>";
					else								 echo"<button type='button' class='btn 				  ' style='width:100%; margin:0; padding:0;' onclick='action_toggle(this);'>No </button>";
					echo"</td>";
				}				
		echo"</tr>";
	}
	echo"</tbody>
	</table>";
}
?>
<?php
connect_db(MASTER_TABLE);
mysql_query("LOCK TABLES user_info READ, subj_info READ, owner WRITE");
switch($_POST['mode']){
	case 'update': update(); break;
	case 'delete': delete(); break;
	default		 : display();
}
mysql_query("UNLOCK TABLES");	
close_db();
?>