<?php
include_once 'z-config.php';
include_once 'z-db.php';
include_once 'z-util.php';

checkauthen();
checkauthorize( USERTYPE_ADMIN );

function format($key){
	return "grd_status.".$key;
};
function get_grade_list(){
	global $table, $field;
	$view  = isset($_REQUEST['view' ])?$_REQUEST['view' ]:'all';
	$order = isset($_REQUEST['order'])?$_REQUEST['order']:'';
	switch($view){
		case 'online' : $condition = "AND prob_info.avail=\"ON\" ";  break;
		case 'offline': $condition = "AND prob_info.avail=\"OFF\""; break;
		case 'all'    : $condition = ""; 							   break;
		default       : $condition = "AND prob_info.prob_id=\"$view\"";
	}
	$res = mysql_query("SELECT ".implode(',',array_map('format',array_keys($field)))." FROM $table, prob_info WHERE $table.prob_id=prob_info.prob_id AND prob_info.ready=\"ready\" $condition $order") or die(mysql_error());
	for($list=array(); $info=mysql_fetch_assoc($res); $list[]=$info);
	if($_REQUEST['order']==null) return array_reverse($list);
	else 						 return $list;
}
function clear_queue(){
	$res = mysql_query("SELECT * FROM grd_queue");
	while($info=mysql_fetch_assoc($res)){
		$user 	= $info['user_id'];
		$prob 	= $info['prob_id'];
		mysql_query("UPDATE grd_status SET res_id=4 WHERE user_id=\"$user\" AND prob_id=\"$prob\"");
	}
	mysql_query("DELETE FROM grd_queue");
}
function gradeone($user, $prob, $subnum, $compiler){
	global $table;
	if($user && $prob && $subnum && $compiler){
		$res = mysql_query("SELECT * FROM grd_queue WHERE user_id=\"$user\" AND prob_id=\"$prob\"") or die(mysql_error());
		echo "$user, $prob, $subnum, $compiler";
		if(!mysql_fetch_assoc($res)){
			mysql_query("UPDATE $table SET res_id=1,compiling=\"\" WHERE user_id=\"$user\" AND prob_id=\"$prob\"") or die(mysql_error());
			mysql_query("INSERT INTO grd_queue (user_id,prob_id,sub_num,compiler) VALUES (\"$user\",\"$prob\",\"$subnum\",\"$compiler\")") or die(mysql_error());
		}
	}
}
function gradeall(){
	$list = get_grade_list();
	foreach($list as $info){
		gradeone($info['user_id'], $info['prob_id'], $info['sub_num'], $info['compiler']);
	}
}
function display( $download=false ){
	global $field;
	$subj  = $_SESSION['subj'];
	echo "
	<table id='grader-list-info' class='table table-bordered table-condensed' style='background-color: eee; font-size: 10pt; margin-bottom: 0px;'>
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
	$index = 1;
	$list = get_grade_list();
	foreach($list as $row){
		switch($row['res_id']){
			case 1 : echo"<tr class='".$row['id']." alert-success'>"; break;
			case 2 : echo"<tr class='".$row['id']." alert-danger'>"; break;
			case 3 : echo"<tr class='".$row['id']." alert-info   '>"; break;
			case 4 : echo"<tr class='".$row['id']." alert        '>"; break;
			default: echo"<tr class='".$row['id']."              '>";
		}
		echo"	<td style='text-align: right; width: 1%;'>$index</td>";
		foreach($row as $key=>$value){
			switch( $key ){
				case 'id'     : break;
				case 'res_id' : switch($value){
									case 1: echo"<td class='".$key."' style='text-align:center;'>in queue</td>";break;
									case 2: echo"<td class='".$key."' style='text-align:center;'> grading</td>";break;
									case 3: echo"<td class='".$key."' style='text-align:center;'>accepted</td>";break;
									case 4: echo"<td class='".$key."' style='text-align:center;'>rejected</td>";break;		
								}
								break;
				case 'user_id': echo"<td class='".$key."' style='text-align:center;'>$value</td>";
								break;
				default       : echo"<td class='".$key."' style='text-align:center;'>$value</td>";
								
			}
		}
		echo"
			</tr>";
		++$index;
	}
	echo "
		</tbody>
	</table>";
}
function download(){
	$filename = $_SESSION['subj']."-grading-status.xls";
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
			<strong>Grading status infomation</strong><br>
			<div id='SiXhEaD_Excel' align=center x:publishsource='Excel'>";
				display(TRUE);
	echo"	</div>
		</body>
	</html>";		
}
?>
<?php
$field = array("user_id"=>"username","prob_id"=>"problem","sub_num"=>"number","res_id"=>"status","compiler"=>"language","compiling"=>"progress");
$table = "grd_status";
connect_db();
mysql_query("LOCK TABLES $table WRITE, prob_info READ, grd_queue WRITE");
switch( $_POST['mode'] ){
	case 'display' : display();     break;
	case 'clear'   : clear_queue(); break;
	case 'gradeone': gradeone($_POST['user'], $_POST['prob'], $_POST['subnum'], $_POST['compiler']); break;
	case 'gradeall': gradeall(); 	break;
	default        : download();
}
mysql_query("UNLOCK TABLES");
close_db();
?>