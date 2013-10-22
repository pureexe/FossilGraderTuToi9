<?php
include_once 'z-config.php';
include_once 'z-db.php';
include_once 'z-util.php';

checkauthen();
?>
<?php
function table_score($user_id, $prob_id)
{
	$res = mysql_query("SELECT compiler_msg, grading_msg FROM grd_status
						WHERE user_id=\"$user_id\" 
						AND prob_id=\"$prob_id\"");
	$row = mysql_fetch_assoc($res);
	if( implode('', $row) ){
		$grading_msg = substr($row['grading_msg'], 1, -1);	
		if( $grading_msg=='' ){
			echo"
				<br/>
					<center>No Result</center>
				<br/>";
		}
		else if($grading_msg=="compile error"){
			$compiler_msg = explode("\n", $row['compiler_msg']);
			echo'
				<table class="table table-bordered  table-condensed" style=" font-size: 10pt; margin-bottom: 0px;">
					<tr><th style="text-align: center; background-color:#eee;">'.$compiler_msg[0].'</th></tr>
					<tr><td>'.implode("<br/>", array_slice($compiler_msg, 1)).'</td></tr>
				</table>';
		}
		else{
			$result = explode("-", $grading_msg);
			for( $i=0 ; $i<count($result) ; ++$i ){
				    if( $result[$i]=='T' ) $result[$i] = "time out";
			   else if( $result[$i]=='x' ) $result[$i] = "mem exceed";
			}
			echo'
				<table class="table table-bordered  table-condensed" style=" font-size: 10pt; margin-bottom: 0px;">
				<tr><th style="text-align: center; background-color:#eee; width: 10%;">Case <th style="text-align: center; width: 9%; background-color:#eee;">'.implode('<th style="text-align: center; width: 9%; background-color:#eee;">', range(1,count($result))).'
				<tr><th style="text-align: center; background-color:#eee; width: 10%;">Score<td style="text-align: center; width: 9%;" class="score">'.implode('<td style="text-align: center; width: 9%;" class="score">', $result).'
				</table>';
		}
		
		if(defined('SOURCE_DOWNLOAD'))
			for( $i=$sub_num-4 ; $i<=$sub_num ; ++$i )
				if( $i>0 )
					echo "<a href=\"viewcode.php?id=$user_id&pid=$prob_id&num=$i\"> [source#$i] </a>";				
	}
}
function check_status($user_id, $prob_id){
	$res = mysql_query("SELECT res_id FROM grd_status WHERE user_id=\"$user_id\" AND prob_id=\"$prob_id\"");
	if( $row=mysql_fetch_assoc($res) ){
			 if( $row['res_id']==SUBSTATUS_ACCEPTED ) echo '<span id="process-status" style="margin-top:1px; float:left;" class="label label-inverse"  ><b>accepted</b></span>';
		else if( $row['res_id']==SUBSTATUS_REJECTED ) echo '<span id="process-status" style="margin-top:1px; float:left;" class="label label-important"><b>rejected</b></span>';
		else 
			echo'
				<div class="progress progress-striped '.array_rand(array("progress-info"=>1,"progress-warning"=>2,"progress-success"=>3," "=>4,"progress-danger"=>5)).' active" style="margin-top:4px;width:1100; height:12px;float:left; margin-bottom:0px;">
					<div class="bar" style="width: 100%;"></div>
				</div>';
		return $row['res_id'];
	}
	else{
		return "None";
	}
	
	return $status;
}
function infooverall($user_id, $prob_id, $sub_num){
	$res = mysql_query("SELECT time, CHAR_LENGTH(code) AS len FROM submission 
						WHERE submission.user_id=\"$user_id\"
                        AND submission.prob_id=\"$prob_id\"
						AND submission.sub_num=\"$sub_num\"");
	$row = mysql_fetch_assoc($res);
	if( implode('', $row) ){
		echo"
			$sub_num submission(s), last on ".$row['time']." of size ".$row['len']." bytes <br/>
			<div style='float:left; margin-bottom:0px;'>submission result: </div>
			<div id='status-show-$user_id-$prob_id' style='float:left; margin-bottom:0px;'>";
				if( !in_array(check_status($user_id, $prob_id), array(SUBSTATUS_ACCEPTED,SUBSTATUS_REJECTED,'None')) )
					echo"<script> check_progress('$user_id','$prob_id'); </script>";
		echo"</div>
			<br/>
			<center>
			<div id='table-score-$user_id-$prob_id' style='width: 60%; margin-bottom: 0px;'>";
				table_score($user_id, $prob_id);
		echo"</div>
			</center>";
	}
}
function overall($user_id, $prob_id)
{
	mysql_query("LOCK TABLES submission READ, grd_status READ, res_desc READ");
	$res = mysql_query("SELECT MAX(sub_num) AS sub_num FROM submission WHERE user_id=\"$user_id\" " . "AND prob_id=\"$prob_id\"");
	$row=mysql_fetch_assoc($res);
	if( implode('', $row) )
		infooverall($user_id, $prob_id, $row['sub_num']);
	else
		echo "<br/><center>No Submission</center><br/>";
	mysql_query("UNLOCK TABLES");
}

connect_db();
	mysql_query("LOCK TABLES grd_status READ");
	switch( $_POST['mode'] ){
		case "check"  : check_status($_POST['user_id'], $_POST['prob_id']); break;
		case "render" :  table_score($_POST['user_id'], $_POST['prob_id']); break;
		case "all"    :      overall($_POST['user_id'], $_POST['prob_id']); break;
	}
	mysql_query("UNLOCK TABLES");
close_db();
?>