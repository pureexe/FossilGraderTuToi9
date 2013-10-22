<?php
include_once 'z-config.php';
include_once 'z-db.php';
include_once 'z-util.php';

checkauthen();
checkauthorize( USERTYPE_ADMIN );

function getevaluator($prob){
	$evaluator = array();
	$subj = $_SESSION['subj'];
	$dir  = PATH_CONTENT_PROBLEM;
	
	foreach(scandir("$dir/$subj/$prob") as $file){
		$extension = strtoupper(pathinfo($file, PATHINFO_EXTENSION));
		if($extension=="EXE"){
			$evaluator["$file"] = $file;	
		}
	}
	foreach(scandir("$dir") as $file){
		$extension = strtoupper(pathinfo($file, PATHINFO_EXTENSION));
		if($extension=="EXE"){
			$evaluator["../../$file"] = $file;	
		}
	}
	return $evaluator;
}
function delete(){
	global $database;
	$subj = $_SESSION['subj'];
	$prob = $_POST['prob'];
	if((mysql_query("DELETE FROM $database WHERE prob_id=\"$prob\"") or die(mysql_error()))){
		$prob_dir = PATH_CONTENT_PROBLEM."/$subj/$prob";
		if(is_dir($prob_dir))
			rmdir_recurse($prob_dir);
		echo "finish";
	}
}
function format($key, $value){
	return $key."=\"".mysql_real_escape_string($value)."\"";
};
function update( $data ){
	global $database, $field, $insert_list, $update_list;
	
	$keys = array_keys($field);
	$prob = $data[0];
	
	if( count($data)!=count($keys) || $prob=='') return;
	
	$res = mysql_query("SELECT * FROM $database WHERE prob_id=\"$prob\"") or die(mysql_error());
  
	if( mysql_num_rows($res)==0 ){
		$subj = $_SESSION['subj'];
		$dir = PATH_CONTENT_PROBLEM."/$subj/$prob";
		if( is_dir($dir) )
			rmdir_recurse($dir);
		$check = mkdir($dir);
		$check = mkdir($dir.'/thumbnails');
		$check = mkdir($dir.'/config');
		if($check && (mysql_query("INSERT INTO $database (".implode(',',$keys).") VALUES (\"".implode('","',$data)."\")") or die(mysql_error()))){		
			$insert_list[] = "<br/>\"+".json_encode(implode(", ", $data))."+\"";
			return true;
		}
		else{
			$insert_list[] = "<br/>[Error!]\"+".json_encode(implode(", ", $data))."+\"";
			return false;
		}
	}
	else{
		if(mysql_query("UPDATE $database SET ".implode(",",array_map("format", $keys, $data))." WHERE prob_id=\"$prob\"") or die(mysql_error())){
			$update_list[] = "<br/>\"+".json_encode(implode(", ", $data))."+\"";
			return true;
		}
		else{
			$update_list[] = "<br/>[Error!]\"+".json_encode(implode(", ", $data))."+\"";
			return false;
		}
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
function upload_from_zip( $prob, $filename, $zipname ){
	global $error_list, $insert_list, $database;
	$destination = './data/temp';
	
	if(is_dir($destination)) rmdir_recurse($destination);
	if(!mkdir($destination)) return false;
	
	if($prob && $filename){
		$prob_dir = "$destination/$prob"; 
		if(!is_dir($prob_dir))
			mkdir($prob_dir);
		foreach($filename['tmp_name'] as $index=>$tmp_name){
			move_uploaded_file($tmp_name, "$prob_dir/".$filename['name'][$index]);
		}
	}
	
	if($zipname){
		$zip = new ZipArchive();
		echo "<script> window.top.window.upload_message(\"$zipname\");</script>";

		if ($zip->open($zipname) !== TRUE) {
			$error_list[] = 'Could not open archive';
			return false;
		}
		$zip->extractTo($destination);
		$zip->close();
	}
	
	$subj = $_SESSION['subj'];
	$root_dir = PATH_CONTENT_PROBLEM."/$subj"; 
	foreach(scandir($destination) as $folder){
		$dir_folder = "$destination/$folder";
		if(is_dir($dir_folder) && $folder!='.' && $folder!='..'){
			$res   = mysql_query("SELECT max(prob_order) as max_order FROM $database") or die(mysql_error());
			$check = update(array($folder, $folder, '', 1, 32, 'compare_unsort.exe', (($row=mysql_fetch_assoc($res))?$row['max_order']+1:0), 'OFF', 'none', 'unready'));
			if( $check ){
				$count_in  = 0;
				$count_sol = 0;
				foreach(scandir($dir_folder) as $file){
					$dir_file = "$dir_folder/$file";
					if(!is_dir($dir_file)){
						rename($dir_file, "$root_dir/$folder/$file");
						$extension = pathinfo($file, PATHINFO_EXTENSION);
						if( $extension=='in' ) $count_in++;
						if( $extension=='sol' ) $count_sol++;
					}
				}
				$score = implode('-', array_fill(0, max($count_in, $count_sol), 10));
				mysql_query("UPDATE $database SET score=\"$score\" WHERE prob_id=\"$folder\"") or die(mysql_error());
			}	
		}
	}
	rmdir_recurse($destination);
	return true;
}
function check_config($prob=""){
	global $database;
	if( $prob=="" )
		$prob = $_POST['prob'];
	
	$subj = $_SESSION['subj'];
	$dir = PATH_CONTENT_PROBLEM."/$subj/$prob"; 
	if(file_exists("$dir/config/conf"))
		unlink("$dir/config/conf");
	$data = array();
	
	$res = mysql_query("SELECT * FROM $database WHERE prob_id=\"$prob\"") or die();
	if(mysql_error()) return;
	
	$errormsg   = array();
	$warningmsg = array();
	if( $row=mysql_fetch_assoc($res) ){
		$scorelist = explode('-', $row['score']);
		$numcase   = count($scorelist);
		if($numcase>1 || is_numeric($scorelist[0])){
			foreach($scorelist as $key=>$score){
				if(!is_numeric($score)){
					$errormsg[] = "Testcase ".($key+1)." score [\"$score\"] is false format, please edit.";
				}
				else if( (float)$score==0 ){
					$warningmsg[] = "Testcase ".($key+1)." is 0 score, realy.";
				}
				$data[] = "score: $score";
			}
			if(!is_numeric($row['timelimit'  ])) $errormsg[] = "<b>Time limit  </b> is not number, please check again.";
			if(!is_numeric($row['memorylimit'])) $errormsg[] = "<b>Memory limit</b> is not number, please check again.";	
			
			$data[] = "cases: $numcase";
			$data[] = "total: ".array_sum($scorelist);
			$data[] = "evaluator: ".$row['evaluator'];
			$data[] = "timelimit: ".$row['timelimit'];
			$data[] = "memorylimit: ".$row['memorylimit'];
			
			$filelist = scandir($dir);
			$numfile  = 0;
			for($case=1; $case<=$numcase ;++$case){
				$check = true;
				if(!in_array("$case.in", $filelist)){
					$errormsg[] = "Not found file [$case.in] please check file for case number or extension.";
					$check = false;
				}
				if(!in_array("$case.sol", $filelist)){
					$errormsg[] = "Not found file [$case.sol] please check file for case number or extension.";
					$check = false;
				}
				if($check) ++$numfile;
			}
			if(!$errormsg){
				if($numcase != $numfile){
					$warningmsg[] = "Number of Testcase scores is $numcase and number of files is $numfile, not equa.";
				}
			}
		}
		else{
			$errormsg[] = "Not have testcase score, please edit.";
		}
	}
	else{
		$errormsg[] = "Not found config for this problem.";
	}
	$HTMLdisplay = "";
	if( $errormsg   ) $HTMLdisplay .= "<div class='alert alert-danger' >".implode('<br/>', $errormsg  )."</div>";
	else{
		$conf = fopen("$dir/config/conf", "w+");
		fwrite($conf, implode("\n", $data)); 
		fclose($conf);
	}
	if( $warningmsg ) $HTMLdisplay .= "<div class='alert alert-warning'>".implode('<br/>', $warningmsg)."</div>";
	
	mysql_query("UPDATE $database SET ready=".($errormsg?'"unready"':'"ready"')." WHERE prob_id=\"$prob\"") or die(mysql_error());
	
	if(isset($_POST['prob'])) echo   $HTMLdisplay;
	else      				  return $HTMLdisplay;
}
function display( $download=false ){
	global $database, $field;
	
	$res = mysql_query("SELECT prob_id FROM prob_info") or die(mysql_error());
	while($row=mysql_fetch_assoc($res))
		$config_message[$row['prob_id']] = check_config($row['prob_id']);
	
	$order = $_REQUEST['order']?$_REQUEST['order']:'ORDER BY prob_order';
	echo "
	<table id='prob-list-info' class='table table3 table-bordered table-condensed' style='width:100%; background-color: eee; font-size: 10pt; margin-bottom: 0px;'>
		<thead>
			<tr>
				<th style='text-align: center; background-color:#eee; width:1px;'>No.</th>";
				$index = 1;
				foreach( $field as $key=>$value ){
					if( $download==false ) echo "<th style='text-align: center; background-color:#eee;'><span style='color:eee;'>&#x25BC; </span><a href='javascript:void(0)' onclick='sort_table(".++$index.", \"$key\");'>$value</a><span id='arrow-$key'><span style='color:eee;'> &#x25BC;</span></span></th>";
					else				   echo "<th style='text-align: center; background-color:#eee;'>$value</th>";
				}
	echo "	</tr>
		</thead>
		<tbody>";
	$res = mysql_query("SELECT ".implode(',',array_keys($field))." FROM prob_info $order");
	for($index=1 ; $row=mysql_fetch_assoc($res) ; $index+=1){
		echo"
			<tr class='".$row['prob_id']."' ondblclick='edit_row(this);'>	
				<td class='number' rowspan='1' style='text-align: right; height:1%;'>$index</td>";
		foreach($row as $key=>$value){
			if($key=='evaluator') $value = basename($value);
			switch( $key ){
				case 'prob_order':
				case 'avail':
				case 'ready': echo"<td class='".$key."' style='text-align:center;'>"; break;
				case 'color': echo"<td class='".$key."s' style='text-align:center;'>"; break;
				default     : echo"<td class='".$key."' style='text-align:center;'>"; break;
			}
			echo"<div class='text' style='height:1px;'>";
			switch( $key ){
				case 'color'	: echo"<font class='act' color=".$row['color'].">$value</font>"; break;
				case 'ready'	: if($download==false) echo"<a style='color:".($value==PROBLEM_READY?'#00aa11':'#aa0011').";' data-toggle='collapse' data-parent='#' href='#span-".$row['prob_id']."'>$value</a>";						  
								  else				   echo"<font class='act' color=".$row['color'].">$value</font>"; break;		
				default	 		: echo"<font class='act' color=".$row['color'].">$value</font>";
			}
			echo"</div>";
			if( $download==false ){
				echo"<div class='edit' style='height:1px; width:100%; display:none;'>";
				switch( $key ){
					case 'evaluator': echo"<select onkeypress='update_prob(event);' style='padding:0; margin:0; border:2px; height:18; width:100%;'>";
									  $evaluator = getevaluator($row['prob_id']);
									  foreach($evaluator as $path=>$ev){
										  if( $ev==$value ) echo "<option value='$path' selected>$ev</option>";
										  else              echo "<option value='$path'         >$ev</option>";
									  }
									  echo"</select>"; break;
					case 'ready'  	: echo"<a style='color:".($value==PROBLEM_READY?'#00aa11':'#aa0011').";' data-toggle='collapse' data-parent='#' href='#span-".$row['prob_id']."'>$value</a>"; break;
					case 'prob_id'	: echo"<input type='text' onkeypress='update_prob(event);' value='$value' style='text-align:center; width:100%;' disabled=disabled'/>"; break;
					case 'avail'  	: echo"<select onkeypress='update_prob(event);' style='padding:0; margin:0; border:0px; height:18; width:100%;'>";
										$statuslist = array('ON', 'OFF');
										foreach($statuslist as $status){
											if( $status==$value ) echo "<option value='$status' selected>$status</option>";
											else                  echo "<option value='$status'         >$status</option>";
										}
									  echo"</select>"; break;
					case 'color'    : echo"<input class='colors pick' type='text' onkeypress='update_prob(event);' value='$value' style='text-align:center; width:100%;'/>";
									  break;
					default       	: echo"<input type='text' onkeypress='update_prob(event);' value='$value' style='text-align:center; width:100%;'/>";
				}
				echo"</div>";
			}
			echo"</td>";
		}
		echo"</tr>";
		if( $download==false ){
			echo"
			<tr class='file-".$row['prob_id']."' style='boder:0;'>
				<td colspan=".(count($field)+1)." style='padding:0; border:0;'>
					<div id='span-".$row['prob_id']."' class='accordion-body collapse ' style='background-color:#eee; width:100%; '>
						<div class='accordion-inner alert alert-info' style='background-color:#222; margin:0;'>
							
							<form id=manage-".$row['prob_id']." class='manage' action='blueimp/server/php/?folder=../../../".PATH_CONTENT_PROBLEM."/".$_SESSION['subj']."/".$row['prob_id']."' method='POST' enctype='multipart/form-data'>
								<div class='row fileupload-buttonbar' style='margin:0px;'>
									<div class='span7' style='float:left;'>
										<span class='btn btn-success btn-mini fileinput-button' style='opacity: 0.95;'>
											<i class='icon-plus icon-white'></i>
											<span>Add files...</span>
											<input type='file' name='files[]' multiple>
										</span>
										<button type='submit' class='btn btn-primary btn-mini start'>
											<i class='icon-upload icon-white'></i>
											<span>Start upload</span>
										</button>
										<button type='reset' class='btn btn-warning btn-mini cancel'>
											<i class='icon-ban-circle icon-white'></i>
											<span>Cancel upload</span>
										</button>
										<button type='button' class='btn btn-danger btn-mini delete'>
											<i class='icon-trash icon-white'></i>
											<span>Delete</span>
										</button>
										<input type='checkbox' class='toggle'>
									</div>
									<div class='span5 fileupload-progress fade' style='float:left;'>
										<div class='progress progress-success progress-striped active' role='progressbar' aria-valuemin='0' aria-valuemax='100'>
											<div class='bar' style='width:0%;'></div>
										</div>
										<div class='progress-extended'></div>
									</div>
								</div>
								<div class='fileupload-loading' style='display:none;'></div>
								<div class='error-panel'>".
									$config_message[$row['prob_id']]."
								</div>
								<table role='presentation' class='table alert-success tablefile' style='font-size: 10pt; margin-bottom:0;'><tbody class='files' data-toggle='modal-gallery' data-target='#modal-gallery'></tbody></table>
							</form>
							
						</div>
					</div>
				</td>
			</tr>";
		}
	}
	echo "
		</tbody>
	</table>";
}
function download(){
	$filename = $_SESSION['subj']."-prob-list.xls";
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
			<strong>Problem list infomation</strong><br>
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
	$error_list  = array();

	$prob = $_POST['prob-name'];
	$file = $_FILES['file'];
	$zip  = $_FILES['zip'];
	
	upload_from_zip($prob, $file, $zip['tmp_name']);

	$n_insert = count($insert_list);
	$n_update = count($update_list);
	if( $n_insert+$n_update == 0 ) $error_list[] = '*'.$zip['tmp_name'].'[ Not detect prob data please check feild or file format are complete? ]';
	
	$msg = "";
	if(count($error_list)) $msg .= "<div class='alert alert-danger' style='text-align:center; width:100%; padding:20 0 20 0;'>".implode("<br/>", $error_list)."</div>";
	else{				   $msg .= "<div class='alert alert-info'   ><b>Insert-List</b> [$n_insert problem".($n_insert>1?'s':'')."]".implode("",$insert_list)."</div>";
						   $msg .= "<div class='alert alert-warning'><b>Update-List</b> [$n_update problem".($n_update>1?'s':'')."]".implode("",$update_list)."</div>";
	}
	echo "<script> window.top.window.upload_message(\"$msg\");</script>";
}
?>
<?php
$field = array("prob_id"=>"problem","name"=>"name","score"=>"score","timelimit"=>"time(sec)","memorylimit"=>"mem(MB)","evaluator"=>"evaluator","prob_order"=>"order","avail"=>"status","color"=>"color","ready"=>"config");
$database = "prob_info";
connect_db();
mysql_query("LOCK TABLES $database WRITE");
switch( $_POST['mode'] ){
	case 'display' : display();     include('./blueimp/manage.script.php'); break;
	case 'upload'  : upload();      break;
	case 'update'  : update_list(); break;
	case 'delete'  : delete(); 		break;
	case 'config'  : check_config();break;
	default        : download();
}
mysql_query("UNLOCK TABLES");
close_db();
?>