<?php
include_once 'z-config.php';
include_once 'z-db.php';
include_once 'z-util.php';

checkauthen();
checkauthorize( USERTYPE_ADMIN );

function random_password( $length = 7 ){
	$alphabet = str_split("abcdefghijklmnopqrstuvwxyz");
	for( $password='' ; $length-- ; $password.=$alphabet[array_rand($alphabet)] );
	return $password;
}
function random(){
	global $database;
	$type = str_replace('|', '" OR type="', $_POST['type']);
	$res = mysql_query("SELECT user_id FROM $database WHERE type='$type'");
	
	for($pass=array() ; $row=mysql_fetch_assoc($res) ; mysql_query("UPDATE $database SET passwd=\"".$pass[$row['user_id']]."\" WHERE user_id=\"".$row['user_id']."\"")){
		$pass[$row['user_id']] = random_password();
	}
	echo json_encode($pass);
}
function delete(){
	global $database;
	$user = $_POST['user'];
	
	$res = mysql_query("SELECT * FROM $database WHERE user_id=\"$user\"");
	if(mysql_num_rows($res)>0){
		mysql_query("DELETE FROM $database WHERE user_id=\"$user\"");
		echo "finish";
	}
	else{
		echo "Not found user has been delete.";
	}
}
function format($key, $value){
	return $key."=\"".mysql_real_escape_string($value)."\"";
};
function update( $data ){
	global $database, $field, $insert_list, $update_list;
	
	$keys = array_keys($field);
	$user = $data[0];
	
	if( count($data)!=count($keys) || $user==''){
		echo "Data not complete.";
		return;
	}
	
	$res = mysql_query("SELECT * FROM $database WHERE user_id=\"$user\"");
  
	if( mysql_num_rows($res)==0 ){
		if(mysql_query("INSERT INTO $database (".implode(',',$keys).") VALUES (\"".implode('","',$data)."\")"))	
			$insert_list[] = "<br/>\"+".json_encode(implode(", ", $data))."+\"";	
		else
			$insert_list[] = "<br/>[Error!]\"+".json_encode(implode(", ", $data))."+\"";
	}
	else{
		if(mysql_query("UPDATE $database SET ".implode(",",array_map("format", $keys, $data))." WHERE user_id=\"$user\""))
			$update_list[] = "<br/>\"+".json_encode(implode(", ", $data))."+\"";
		else
			$update_list[] = "<br/>[Error!]\"+".json_encode(implode(", ", $data))."+\"";
	}
}
function update_list(){
	global $insert_list, $update_list;
	$datas = $_POST['list'];
	foreach( $datas as $data ){
		update( $data );
	}
	$log  = $insert_list?implode(' ', $insert_list):"";
	$log .= $update_list?implode(' ', $update_list):"";
	echo substr_count($log,'[Error!]')?"lost":"finish";
}
function upload_from_file( $filename ){
	$alphabet = str_split("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_-:,");
	foreach( file($filename) as $record ){
		$length = strlen($record);
		for($i=0 ; $i<$length ; $i++)
			if( in_array($record[$i], $alphabet) )
				break;
		for($j=$length-1 ; $j>=0 ; $j--)
			if( in_array($record[$j], $alphabet) )
				break;
		if($i<$length){
			$record = substr($record, $i, $j+1);
				if( substr_count($record, ':') ) update( explode(':', $record) );
			elseif( substr_count($record, ',') ) update( explode(',', $record) );
		}
	}
}
function display( $download=false ){
	global $database, $field;
	$order = $_REQUEST['order'];
	echo "
	<table id='user-list-info' class='table table-bordered table-condensed' style='background-color: eee; font-size: 10pt; margin-bottom: 0px;'>
		<thead>
			<tr>
				<th style='text-align: center; background-color:#eee;'>No.</th>";
				$index = 1;
				foreach( $field as $key=>$value ){
					if( $download==false ) echo "<th style='text-align: center; background-color:#eee;'><span style='color:eee;'>&#x25BC; </span><a href='javascript:void(0)' onclick='sort_table(".++$index.", \"$key\");'>$value</a><span id='arrow-$key'><span style='color:eee;'> &#x25BC;</span></span></th>";
					else				   echo "<th style='text-align: center; background-color:#eee;'>$value</th>";
				}
	echo "	</tr>
		</thead>
		<tbody>";
	$res = mysql_query("SELECT ".implode(',',array_keys($field))." FROM $database $order") or die(mysql_error());
	for($index=1 ; $row=mysql_fetch_assoc($res) ; $index+=1){
		echo"
			<tr class='".$row['user_id']."' ondblclick='edit_row(this);'>	
				<td style='text-align: right; width: 1%;'>$index</td>";
		foreach($row as $key=>$value){
			switch( $key ){
				case 'name': echo"<td class='".$key."' style='padding-left:5px;               '>"; break;
				case 'type': echo"<td class='".$key."' style='text-align:center; width: 100px;'>"; break;
				default    : echo"<td class='".$key."' style='text-align:center;              '>"; break;
			}
			echo"<div class='text' style='height:1px; display:nne; '>$value</div>";
			echo"<div class='edit' style='height:1px; display:none;'>";
			if( $download==false ){
				switch( $key ){
					case 'user_id': echo"<input  type='text' onkeypress='update_user(event);' value='$value' style='text-align:center; width:100%;' disabled=disabled'/>"; break;
					case 'type'   : echo"<select onkeypress='update_user(event);' style='padding:0; margin:0; border:2px; height:18; width:100%;'>";
											$typelist = array(USERTYPE_CONTESTANT,USERTYPE_SUPERVISOR);
											foreach($typelist as $type){
												if( $type==$value ) echo "<option value='$type' selected>$type</option>";
												else                echo "<option value='$type'         >$type</option>";
											}
									echo"</select>"; break;
					default       : echo"<input type='text' onkeypress='update_user(event);' value='$value' style='text-align:center; width:100%;'/>";
				}
			}
			echo"</div>
				</td>";
		}
		echo"
			</tr>";
	}
	echo "
		</tbody>
	</table>";
}
function download(){
	$filename = $_SESSION['subj']."-user-list.xls";
	header("Content-Type: application/vnd.x-msexcel; name='$filename'");
	header("Content-Disposition: inline; filename='$filename'");
	header("Pragma: no-cache");
	echo"
		<html xmlns:o='urn:schemas-microsoft-com:office:office'
			  xmlns:x='urn:schemas-microsoft-com:office:excel'
			  >
		<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
		<html>
		<head>
			<meta http-equiv='Content-type' content='text/html;charset=utf-8' />
			<style id='SiXhEaD_Excel_Styles'></style>
		</head>
		<body>
			<strong>User list infomation</strong><br>
			<div id='SiXhEaD_Excel' align=center x:publishsource='Excel'>";
				display(TRUE);
	echo"	</div>
		</body>
	</html>";		
}
function upload(){
	global $insert_list, $update_list;
	$insert_list = array();
	$update_list = array();

	$user = $_POST['user'];
	$name = $_POST['name'];
	$pass = $_POST['pass'];
	$team = $_POST['team'];
	$mail = $_POST['mail'];
	$type = $_POST['type'];
	$seat = $_POST['seat'];
	$file = $_FILES['file'];
	
	if( $user!=''&&$name!=''&&$pass!=''&&$team!=''&&$type!='' ) update( array($user, $name, $pass, $team, $mail, $type, $seat) );
	if( $file['size']>0 )										upload_from_file($file['tmp_name']);

	$n_insert = count($insert_list);
	$n_update = count($update_list);
	$msg = "";
	if( $n_insert+$n_update == 0 ) $msg .= "<div class='alert alert-danger' style='text-align:center; width:100%; padding:20 0 20 0;'>[ Not detect user data please check feild or file format are complete? ]</div>";
	else{						   $msg .= "<div class='alert alert-info'   ><b>Insert-List</b> [$n_insert user".($n_insert>1?'s':'')."]".implode("",$insert_list)."</div>";
								   $msg .= "<div class='alert alert-warning'><b>Update-List</b> [$n_update user".($n_update>1?'s':'')."]".implode("",$update_list)."</div>";
	}
	echo "<script> window.top.window.upload_message(\"$msg\");</script>";
}
?>
<?php
$field = array("user_id"=>"username","name"=>"name","passwd"=>"password","grp"=>"group","email"=>"e-mail","type"=>"type","seat"=>"seat");
$database = "user_info";
connect_db();
mysql_query("LOCK TABLES $database WRITE");
switch( $_POST['mode'] ){
	case 'display' : display();     break;
	case 'upload'  : upload();      break;
	case 'random'  : random(); 		break;
	case 'update'  : update_list(); break;
	case 'delete'  : delete(); 		break;
	default        : download();
}
mysql_query("UNLOCK TABLES");
close_db();
?>