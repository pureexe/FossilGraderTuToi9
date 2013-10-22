<?php
include_once 'z-config.php';
include_once 'z-db.php';
include_once 'z-util.php';

checkauthen();
checkauthorize( USERTYPE_SUPERADMIN );

function delete(){
	global $database, $connect;
	$subj = $_POST['subj'];
	if((mysql_query("DELETE FROM $database WHERE subj_id=\"$subj\"") or die(mysql_error()))){		
		mysql_query("UNLOCK TABLES");
		drop_db($subj);
		$dir = PATH_CONTENT_PROBLEM."/$subj";
		if(is_dir($dir))
			rmdir_recurse($dir);
		echo "finish";
	}
}
function format($key, $value){
	return $key."=\"".mysql_real_escape_string($value)."\"";
};
function update( $data ){
	global $database, $field, $insert_list, $update_list, $connect;
	
	$keys = array_keys($field);
	$subj = $data[0];
	
	if( count($data)!=count($keys) || $subj=='') return;
	
	$res = mysql_query("SELECT * FROM $database WHERE subj_id=\"$subj\"") or die(mysql_error());
  
	if( mysql_num_rows($res)==0 ){
		if(mysql_query("INSERT INTO $database (".implode(',',$keys).",db_name) VALUES (\"".implode('","',$data)."\", \"$subj\")") or die(mysql_error())){				
			mysql_query("UNLOCK TABLES");
			create_db($subj, TEMPLATE_DB_PATH, $msg);
			$dir = PATH_CONTENT_PROBLEM."/$subj";
			if( is_dir($dir) )
				rmdir_recurse($dir);
			mkdir($dir);
			$insert_list[] = "<br/>\"+".json_encode(implode(", ", $data))."+\"";
		}
		else
			$insert_list[] = "<br/>[Error!]\"+".json_encode(implode(", ", $data))."+\"";
	}
	else{
		if(mysql_query("UPDATE $database SET ".implode(",",array_map("format", $keys, $data))." WHERE subj_id=\"$subj\"") or die(mysql_error()))
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
	<table id='subj-list-info' class='table table-bordered table-condensed' style='background-color: eee; font-size: 10pt; margin-bottom: 0px;'>
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
			<tr class='".$row['subj_id']."' ondblclick='edit_row(this);'>	
				<td style='text-align: right; width: 1%;'>$index</td>";
		foreach($row as $key=>$value){
			switch( $key ){
				default    : echo"<td class='".$key."' style='text-align:center;'>"; break;
			}
			echo"<div class='text' style='height:1px; display:nne; '>$value</div>";
			echo"<div class='edit' style='height:1px; display:none;'>";
			if( $download==false ){
				switch( $key ){
					case 'subj_id': echo"<input type='text' onkeypress='update_subj(event);' value='$value' style='text-align:center; width:100%;' disabled=disabled'/>"; break;
					case 'status' : echo"<select onkeypress='update_subj(event);' style='padding:0; margin:0; border:2px; height:18; width:100%;'>";
											$typelist = array('ON','OFF');
											foreach($typelist as $type){
												if( $type==$value ) echo "<option value='$type' selected>$type</option>";
												else                echo "<option value='$type'         >$type</option>";
											}
									echo"</select>"; break;
					default       : echo"<input type='text' onkeypress='update_subj(event);' value='$value' style='text-align:center; width:100%;'/>";
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
	$filename = $_SESSION['subj']."-subject-list.xls";
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
			<strong>Subject list infomation</strong><br>
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

	$subj 	= $_POST['subj'];
	$code	= $_POST['code'];
	$name 	= $_POST['name'];
	$year	= $_POST['year'];
	$term 	= $_POST['term'];
	$status = $_POST['status'];
	$file 	= $_FILES['file'];
	
	if( $subj!=''&&$code!=''&&$name!=''&&$status!='' ) update( array($subj, $code, $name, $year, $term, $status) );
	if( $file['size']>0 )				  upload_from_file($file['tmp_name']);

	$n_insert = count($insert_list);
	$n_update = count($update_list);
	$msg = "";
	if( $n_insert+$n_update == 0 ) $msg .= "<div class='alert alert-danger' style='text-align:center; width:100%; padding:20 0 20 0;'>[ Not detect subject data please check feild or file format are complete? ]</div>";
	else{						   $msg .= "<div class='alert alert-info'   ><b>Insert-List</b> [$n_insert subject".($n_insert>1?'s':'')."]".implode("",$insert_list)."</div>";
								   $msg .= "<div class='alert alert-warning'><b>Update-List</b> [$n_update subject".($n_update>1?'s':'')."]".implode("",$update_list)."</div>";
	}
	echo "<script> window.top.window.upload_message(\"$msg\");</script>";
}
?>
<?php
global $connect;
$field = array("subj_id"=>"subject name","code"=>"code","name"=>"name","year"=>"year","term"=>"term","status"=>"status");
$database = "subj_info";
$connect = connect_db(MASTER_TABLE);
mysql_query("LOCK TABLES $database WRITE");
switch( $_POST['mode'] ){
	case 'display' : display();     break;
	case 'upload'  : upload();      break;
	case 'update'  : update_list(); break;
	case 'delete'  : delete(); 		break;
	default        : download();
}
mysql_query("UNLOCK TABLES");
close_db();
?>