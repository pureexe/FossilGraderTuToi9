<?php
include_once 'z-config.php';
include_once 'z-db.php';
include_once 'z-util.php';

checkauthen();
checkauthorize( USERTYPE_ADMIN );

function delete(){
	global $database;
	$id = $_POST['id'];
	if((mysql_query("DELETE FROM $database WHERE id=\"$id\"") or die(mysql_error())))
		echo "finish";
}
function approve(){
	global $database;
	$subj = $_SESSION['subj'];
	if((mysql_query("UPDATE $database SET approve=\"Y\" WHERE subj_id=\"$subj\"") or die(mysql_error())))
		echo "finish";
}
function display( $download=false ){
	global $database, $field;
	$order = $_REQUEST['order'];
	$subj  = $_SESSION['subj'];
	echo "
	<table id='status-list-info' class='table table-bordered table-condensed ".($del>0?'link':'')."' style='background-color: eee; font-size: 10pt; margin-bottom: 0px;'>
		<thead>
			<tr>
				<th style='text-align: center; background-color:#eee;'>No.</th>";
				$index = 1;
				foreach( $field as $key=>$value ){
					if($key=='id') continue;
					if( $download==false ) echo "<th style='text-align: center; background-color:#eee;'><span style='color:eee;'>&#x25BC; </span><a href='javascript:void(0)' onclick='sort_table(".++$index.", \"$key\");'>$value</a><span id='arrow-$key'><span style='color:eee;'> &#x25BC;</span></span></th>";
					else				   echo "<th style='text-align: center; background-color:#eee;'>$value</th>";
				}
	echo "	</tr>
		</thead>
		<tbody>";
	$res = mysql_query("SELECT ".implode(',',array_keys($field))." FROM $database WHERE subj_id=\"$subj\" AND type!=\"".USERTYPE_ADMIN."\" AND approve=\"N\" ORDER BY $order id DESC") or die(mysql_error());
	for($index=1 ; $row=mysql_fetch_assoc($res) ; $index+=1){
			 if(strpos($row['status'],"user"   )!=FALSE){ echo"<tr class='".$row['id']."' style='background-color:#ffbb00;'>"; $check=1;}
		else if(strpos($row['status'],"subject")!=FALSE){ echo"<tr class='".$row['id']."' style='background-color:#ffffbb;'>"; $check=1;}
		else 											{ echo"<tr class='".$row['id']."'>";                                   $check=0;}
		echo"	<td style='text-align: right; width: 1%;'>$index</td>";
		foreach($row as $key=>$value){
			switch( $key ){
				case 'id'     : break;
				case 'user_id': if($check>0) echo"<td class='".$key."       ' style='text-align:center;'><a href='javascript:void(0)' onclick='delete_status(this);'>$value</a></td>";
								else		 echo"<td class='".$key." delete' style='text-align:center;'>$value</td>";
								break;
				default       : echo"<td class='".$key."' style='text-align:center;'>$value</td>";
								
			}
		}
		echo"
			</tr>";
	}
	echo "
		</tbody>
	</table>";
}
function download(){
	$filename = $_SESSION['subj']."-access-log.xls";
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
			<strong>Status log infomation</strong><br>
			<div id='SiXhEaD_Excel' align=center x:publishsource='Excel'>";
				display(TRUE);
	echo"	</div>
		</body>
	</html>";		
}
?>
<?php
$field = array("id"=>"id","user_id"=>"username","user_ip"=>"ip","mac"=>"mac address","online_time"=>"login time","status"=>"status");
$database = "log";
connect_db(MASTER_TABLE);
mysql_query("LOCK TABLES $database WRITE");
switch( $_POST['mode'] ){
	case 'display' : display();     break;
	case 'approve' : approve();		break;
	case 'delete'  : delete(); 		break;
	default        : download();
}
mysql_query("UNLOCK TABLES");
close_db();
?>