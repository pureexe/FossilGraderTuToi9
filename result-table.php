<?php
include_once 'z-config.php';
include_once 'z-db.php';
include_once 'z-util.php';

checkauthen();
//checkauthorize( USERTYPE_ADMIN );
?>
<?php
function prob_list($select=''){
	$prob = array();
	connect_db();
	mysql_query("LOCK TABLES prob_info READ");
	$res = mysql_query("SELECT prob_id, score, name, color FROM prob_info WHERE ready=\"ready\" AND prob_order>0 $select ORDER BY prob_order") or die(mysql_error());
	while($info=mysql_fetch_assoc($res)){
		$score = explode('-', $info["score"]);
		$prob[$info["prob_id"]]['color'] = $info['color'];
		$prob[$info["prob_id"]]['name']  = $info['name'];
		$prob[$info["prob_id"]]['score'] = $score;
		$prob[$info["prob_id"]]['total'] = array_sum($score);
		$prob[$info["prob_id"]]['N']     = count($score);
	}	
	mysql_query("UNLOCK TABLES");
	close_db();
	return $prob;
}
function user_list(){
	if($_SESSION['type']==USERTYPE_SUPERVISOR) $select_team = " AND grp=\"".$_SESSION['team']."\" ";
	else									   $select_team = "";	
	$user = array();
	connect_db();
	mysql_query("LOCK TABLES user_info READ");
	$res = mysql_query("SELECT user_id, name, grp FROM user_info WHERE type=\"".USERTYPE_CONTESTANT."\" $select_team ORDER BY user_id") or die(mysql_error());
	while($info=mysql_fetch_assoc($res)){
		$user[$info["user_id"]]['name'] = $info['name'];
		$user[$info["user_id"]]['team'] = $info['grp' ];
	}
	mysql_query("UNLOCK TABLES");
	close_db();
	return $user;
}
function login_list(){
	$status = array();
	connect_db(MASTER_TABLE);
	mysql_query("LOCK TABLES log READ");
	$res = mysql_query("SELECT user_id FROM log WHERE subj_id=\"".$_SESSION['subj']."\" AND status=\"online\"") or die(mysql_error());
	while($info=mysql_fetch_assoc($res)){
		$status[$info['user_id']]='online';
	}
	mysql_query("UNLOCK TABLES");
	close_db();
	return $status;
}
function score_list($user_list, $prob_list, $start_time, $sort){
	$content = array();
	
	foreach($user_list as $user_id=>$user_info){
		$content[$user_id]['No']       = 0;
		if($_SESSION['type']!=USERTYPE_CONTESTANT){
			$content[$user_id]['username'] = $user_id;
			$content[$user_id]['name']     = $user_info['name'];
			$content[$user_id]['group']    = $user_info['team'];
		}
		foreach($prob_list as $prob_id=>$prob_info){
			$content[$user_id]['prob'][$prob_id]['score'] = array_fill(0, $prob_info['N'], '-');
			$content[$user_id]['prob'][$prob_id]['total'] = 0;
		}
		$content[$user_id]['total']    = 0;
		$content[$user_id]['time']     = 0;
	}
	connect_db();
	mysql_query("LOCK TABLES grd_status READ, submission READ");
	foreach($prob_list as $prob_id=>$prob_info){
		$res = mysql_query("SELECT grd_status.user_id as user_id, grd_status.grading_msg as score, submission.time as time FROM grd_status, submission WHERE grd_status.prob_id=\"$prob_id\" AND grd_status.user_id=submission.user_id AND grd_status.prob_id=submission.prob_id and grd_status.sub_num=submission.sub_num") or die(mysql_error());
		while($info=mysql_fetch_assoc($res)){
			$user_id = $info['user_id'];
			if(isset($user_list[$user_id])){
				$info['score'] = substr($info['score'], 1, -1);
				if($info['score']!='compile error') $info['score'] = explode('-', $info['score']);
				$content[$user_id]['prob'][$prob_id]['score'] = $info['score'];
				$content[$user_id]['prob'][$prob_id]['total'] = (is_array($info['score'])?array_sum($info['score']):0);
				$content[$user_id]['total']          		 += $content[$user_id]['prob'][$prob_id]['total'];
				$content[$user_id]['time']           		 += (int)(strtotime($info['time'])-$start_time);
			}
		}
	}
	mysql_query("UNLOCK TABLES");
	close_db();
	if($sort=='score'){
		$case_total = array(); foreach($content as $user_id=>$user_info) $case_total[$user_id]=$user_info['total'];
		$case_time  = array(); foreach($content as $user_id=>$user_info) $case_time [$user_id]=$user_info['time' ];
		$case_login = array(); foreach($content as $user_id=>$user_info) $case_login[$user_id]=isset($status[$user_id])?'online':'offline';
		
		array_multisort($case_total, SORT_DESC, $case_time, SORT_DESC, $case_login, SORT_ASC, $content);
	}
	$index = 0;
	foreach($content as $user_id=>$user_info){
		$content[$user_id]['No'] = ++$index;
	}
	return $content;
}
function display(){
	$view   = isset($_REQUEST['view'  ])?$_REQUEST['view'  ]:'all';
	$sort   = isset($_REQUEST['sort'  ])?$_REQUEST['sort'  ]:'user';;	
	$detail = isset($_REQUEST['detail'])?$_REQUEST['detail']:0;
	$time   = isset($_REQUEST['time'  ])?$_REQUEST['time'  ]:'';
	
		 if($view=='all'  ) 		   $prob_list = prob_list();
	else if($view=="ON"||$view=="OFF") $prob_list = prob_list("AND avail=\"$view\"");
	else		  	  				   $prob_list = prob_list("AND prob_id=\"$view\"");
	
	$fullscore = 0;
	foreach($prob_list as $prob_info)
		$fullscore += $prob_info['total'];
	
	$user_list  = user_list();
	$score_list = score_list($user_list, $prob_list, strtotime($time), $sort);
	
	echo"<table class='table table-bordered table-condensed' style='background-color: eee; font-size: 10pt; margin-bottom: 0px;'>
			<thead>
				<tr>
					<th rowspan='2' style='vertical-align: middle; text-align: center; width:1px;'>No.     </th>";
	if( $_SESSION['type']!=USERTYPE_CONTESTANT )
		echo"		<th rowspan='2' style='vertical-align: middle; text-align: center;           '>username</th>
					<th rowspan='2' style='vertical-align: middle; text-align: center;           '>name    </th>
					<th rowspan='2' style='vertical-align: middle; text-align: center;           '>group   </th>";
	foreach($prob_list as $prob_id=>$prob)
		echo"		<th colspan='".($prob['N']*$detail+1)."' style='color:".$prob['color']."; vertical-align: middle; text-align: center;'>".$prob['name']."</th>";
	echo"			<th rowspan='2' style='vertical-align: middle; text-align: center;           '>Total   </th>
					<th rowspan='2' style='vertical-align: middle; text-align: center;           '>Time(s) </th>
				</tr>
				<tr>";
	foreach($prob_list as $prob_id=>$prob){
		for($case=1 ; $case<=$prob['N']*$detail ; ++$case)
			echo"	<th style='color:".$prob['color']."; vertical-align: middle; text-align: center;'>$case</th>";						
		echo"		<th style='color:".$prob['color']."; vertical-align: middle; text-align: center;'>score</th>";						
	}
	echo"		</tr>
			</thead>
			<tbody>";
	foreach($score_list as $user_id=>$info){
		if($info['total']>=$fullscore && $info['total']>0) echo"<tr style='background-color:#aaff00;'>";
		else						   					   echo"<tr>";

		foreach($info as $type=>$value)
			switch($type){
				case 'No'   : echo"<td style='text-align:right;'>$value</td>"; break;
				case 'name' : echo"<td style='white-space: nowrap;'>$value</td>"; break;
				case 'prob' : foreach($value as $prob_id=>$score){
								if($detail)
									if($score['score']=='compile error') echo"<td colspan='".$prob_list[$prob_id]['N']."' style='color:".$prob_list[$prob_id]['color']."; text-align:center; white-space: nowrap;'>".$score['score']."</td>";
									else								 echo"<td style='color:".$prob_list[$prob_id]['color']."; text-align:center; white-space: nowrap;'>".implode("</td><td style='color:".$prob_list[$prob_id]['color']."; text-align:center; white-space: nowrap;'>",$score['score'])."</td>";
								echo"<td style='color:".$prob_list[$prob_id]['color']."; text-align:center; white-space: nowrap;'>".$score['total']."</td>";
							  }
							  break;
				default     : echo"<td style='text-align:center; white-space: nowrap;'>$value</td>"; break;
			}
		echo"	</tr>";
	}
	echo"	</tbody>
		</table>";
}
function download(){
	$filename = $_SESSION['subj']."-result.xls";
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
			<strong>Result infomation</strong><br>
			<div id='SiXhEaD_Excel' align=center x:publishsource='Excel'>";
				display();
	echo"	</div>
		</body>
	</html>";		
}
?>
<?php
switch($_POST['mode']){
	case 'display': display(); break;
	default       : download();    
}
?>