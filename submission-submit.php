<?php
include_once 'z-util.php';
include_once 'z-config.php';
include_once 'z-db.php';

checkauthen();
?>
<?php
function getsubstatus($id, $probid)
{
	$res = mysql_query("SELECT * FROM grd_status WHERE user_id=\"$id\" AND prob_id=\"$probid\"");
	if(mysql_num_rows($res)==1) {
		$status = mysql_result($res,0,'res_id');
		settype($status,'integer');
		return $status;
	} 
	else
		return SUBSTATUS_UNDEFINED;
}

function setsubstatus($id, $probid, $status, $compiler, $sub_num)
{
	$res = mysql_query("SELECT * FROM grd_status WHERE user_id=\"$id\" AND prob_id=\"$probid\"");
    
	if(mysql_num_rows($res)==1)
		$res = mysql_query("UPDATE grd_status SET res_id=$status, compiler=\"$compiler\", sub_num=\"$sub_num\" WHERE user_id=\"$id\" AND prob_id=\"$probid\"");
	else
		$res = mysql_query("INSERT INTO grd_status (user_id, prob_id, res_id, compiler, sub_num) VALUES (\"$id\",\"$probid\",$status,\"$compiler\",\"$sub_num\")");
  
	return $res;
}

function putinqueue($id, $probid, $sub_num, $compiler)
{
	$res = mysql_query("SELECT q_id FROM grd_queue WHERE user_id=\"$id\" AND prob_id=\"$probid\"");
	if(mysql_num_rows($res)==1)
		$res = mysql_query("UPDATE grd_queue SET sub_num=$sub_num, compiler=\"$compiler\" WHERE user_id=\"$id\" AND prob_id=\"$probid\"");
	else
		$res = mysql_query("INSERT INTO grd_queue (user_id, prob_id, sub_num, compiler) VALUES (\"$id\",\"$probid\",$sub_num,\"$compiler\")");
  
	return $res;
}

function getsubcount($id,$probid)
{
	$query = mysql_query("select * from submission where user_id=\"$id\" and prob_id=\"$probid\"");
	return mysql_num_rows($query);
}

function builddate()
{
	return date("Y-m-d H:i:s");
}

function savesubmission($id, $probid, $filename, $extension)
{
	global $subj_info;
	$code = file_get_contents($filename);
	
	if( $subj_info['header']=='ON' ){
		$content = strtoupper($code);
			 if( strpos($content, "COMPILER:" ) ) $compiler = explode( "COMPILER:" , $content );
		else if( strpos($content, "COMPILER :") ) $compiler = explode( "COMPILER :", $content );
		$compiler = explode( "\n" , $compiler[1] );
		$compiler = trim($compiler[0]);
		
			 if( strpos($content, "LANG:" ) ) $lang = explode( "LANG:" , $content );
		else if( strpos($content, "LANG :") ) $lang = explode( "LANG :", $content );
		$lang = explode( "\n" , $lang[1] );
		$lang = trim($lang[0]);
	}
	else if($extension=='CPP'){
		$compiler = DEFAULT_COMPILER;
		$lang     = "C++";
		$code = "/*\nLANG: $lang\nCOMPILER: $compiler\n*/\n".$code;
	}
	else if($extension=='C'){
		$compiler = DEFAULT_COMPILER;
		$lang     = "C";
		$code = "/*\nLANG: $lang\nCOMPILER: $compiler\n*/\n".$code;
	}
	else{
		return "Extension invalid please choose file .c or .cpp again.";
	}

	$msg = NULL;
	
	if( in_array($compiler,array("WCB","WDC","LINUX")) && in_array($lang, array("C","C++")) ) {
		$content = str_replace("\r\n","\n",$code);
	  
		mysql_query("LOCK TABLE submission WRITE, grd_queue WRITE, " .
				  "grd_status WRITE");
	  
		// savesubmission: savefile, set status, add submission to queue
		$status = getsubstatus($id, $probid);
	  
		if($status!=SUBSTATUS_GRADING) {
			// savefile
			$subcount = getsubcount($id, $probid);
			$timestamp = builddate();
			$query = "insert into submission (user_id,prob_id,sub_num,time,code) values " .
					"(\"$id\",\"$probid\"," . ($subcount+1) . ",\"$timestamp\", ".
					"\"" . mysql_real_escape_string($content) . "\");";
			
			$res = mysql_query($query);
		
			if($res!=TRUE)
				$msg .= "Database problem (insertion error)<br/>";
			else {
				if(setsubstatus($id, $probid, SUBSTATUS_INQUEUE, $compiler, $subcount+1)!=TRUE)
					$msg .= "Database problem (grd_status)<br/>";
				else {
				if(putinqueue($id, $probid, $subcount+1, $compiler)!=TRUE)
					$msg .= "Database problem (grd_queue)<br/>";
				}
			}			
		}
	  
		mysql_query("UNLOCK TABLES");
	}
	else{
		$msg .= "This file is not define COMPILER or LANG. Please edit and sent again.<br/>";
	}

	return $msg;
}
function checkshellhack($file){
	$err_line = 1;
	$f = fopen($file,"r");
	while($lines = fgets($f)){
		$disable_command = array("system(","ifstream","ofstream","fprintf(","fscanf(","fread(","fwrite(");
		foreach($disable_command as &$disable_cmd)
			if(strpos($lines,$disable_cmd)) return $err_line;
		++$err_line;
	}
	return 0;
}
function processsubmission()
{
	global $subj_info;
	$subj_info = getsubjectinfo();
	connect_db();
	$user_id   = $_POST['id'];
	$prob_id   = $_POST['probid'];
	$fsize     = $_FILES['code']['size'];
	$fcode	   = $_FILES['code']['tmp_name'];
	$extension = strtoupper(pathinfo($_FILES['code']['name'], PATHINFO_EXTENSION));
	$check_shellhack = checkshellhack($fcode);
	
		 if( $subj_info['submit']=='OFF' )
							 echo "<script>window.top.window.recieve('Grader close can not submit file.','$user_id','$prob_id');</script>";
	else if( $fsize==0     ) echo '<script>window.top.window.recieve("File is empty, please choose file again.", "'.$user_id.'","'.$prob_id.'");</script>';
	else if( $fsize>100000 ) echo '<script>window.top.window.recieve("File too large, please choose file again.", "'.$user_id.'","'.$prob_id.'");</script>';
	else if( $check_shellhack!= 0 ) echo '<script>window.top.window.recieve("Unusable function at line '.$check_shellhack.'<br> Don\'t call system or file because it can take a server damage!", "'.$user_id.'","'.$prob_id.'");</script>';
	else{
		$res = savesubmission($user_id, $prob_id, $fcode, $extension);
		echo '<script>window.top.window.recieve("'.$res.'", "'.$user_id.'","'.$prob_id.'");</script>';
	}
	close_db();
}

processsubmission();
?>

