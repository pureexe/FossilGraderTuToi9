<?php
include_once 'z-config.php';
include_once 'z-db.php';
include_once 'z-util.php';

checkauthen();
?>

<script>
$(document).ready(function(){
	$('body').css('margin-bottom',55);
});
function prob_template( user, prob ){
	$.post(
		"submission-info.php", 
		{"user_id":user, "prob_id":prob, "mode":"all"},
		function(data){
			if(data){
				$("#probinfo-"+user+"-"+prob).html(data);
				$('#submitform-'+user+'-'+prob+' input').removeAttr("disabled");
				updatescore(user, prob);
			}
		}
	);
}
function check_progress( user, prob ){
	$.post(
		"submission-info.php", 
		{"user_id":user, "prob_id":prob, "mode":"check"},
		function(data){
			if($("#status-show-"+user+"-"+prob).length>0){
				if( data ){
					$("#status-show-"+user+"-"+prob).html(data);
					if( data.search("progress-striped")>=0 ){
						$('#submitform-'+user+'-'+prob+' input').attr("disabled", 'disabled');
						setTimeout(
							function (){
								check_progress(user, prob);
							}, 2000);
						return;
					}
					else{
						prob_template( user, prob );
					}
				}
			}
		}
	);
}
function recieve( error_msg, user, prob ){
	$("#submitform-"+user+"-"+prob)[0].reset();
	if( error_msg ){
		$("#error_msg").html(error_msg);
		$("#myModal").modal("show");
		$("#closemodal").focus();
	}
	else{
		prob_template( user, prob );
	}
}
function print_action(){
	$("#printer-form").hide();
	$("#printer-load").show();
}
function recieve_print( msg ){
	$("#printer-load").hide();
	$("#printer-form").show();
	$("#printer-form")[0].reset();
	$("#error_msg").html(msg);
	$("#myModal").modal("show");
	$("#closemodal").focus();
}
function focusprob( user, prob, move ){
	if(move)
		$('html,body').animate({scrollTop: $('#link-'+user+'-'+prob).offset().top-107},'slow');
	if($('#'+user+'-'+prob).hasClass('in')){
		$('#link-activate-'+prob).css('color', '#111');
		$('#link-'+user+'-'+prob+' font.score').css('color',$('#link-'+user+'-'+prob+' a.prob-name').css('color'));
	}
	else{
		$('#link-activate-'+prob).css('color', '#eee');
		$('#link-'+user+'-'+prob+' font.score').css('color','#555');
	}	
}
function expand_toggle( user ){
	$('div[id^='+user+'-]').each(function(i){
		if( $(this).hasClass('in')==$('#bottom-bar div.total-score a').hasClass('on') ){
			$('#link-'+$(this).attr('id')).find('a').click();
		}
	});
	if($('#bottom-bar div.total-score a').hasClass('on')){
		$('#bottom-bar div.total-score a').addClass('off');
		$('#bottom-bar div.total-score a').removeClass('on' );
		$('#bottom-bar div.total-score a').css('color', '#ffaa00');
		$('div.power').css('background-color', '#ffaa00');
	}
	else{
		$('#bottom-bar div.total-score a').addClass('on' );
		$('#bottom-bar div.total-score a').removeClass('off');
		$('#bottom-bar div.total-score a').css('color', '#555');
		$('div.power').css('background-color', '#333');
	}
}
function updatescore(user, prob){
	score = 0;
	$('#'+user+'-'+prob+' td.score').each(function(i){
		num = $(this).text();
		if($.isNumeric(num))
		score += parseFloat(num);
	});
	$('#link-'+user+'-'+prob+' span.score').text( ''+score );
	
	full_score = parseFloat($('#link-'+user+'-'+prob+' span.full-score').text());
	percent = score/full_score*100;
	if(percent>100) percent = 100; 
	$('#power-'+prob).width(percent+'%');
	
	total = 0;
	$('span.score').each(function(i){
		total += parseFloat($(this).text());
	});
	$('span.total').text(''+total);
}
</script>

<?php
function getteamlist($team){
	global $teamlist;
	$res = mysql_query("SELECT * FROM user_info WHERE grp=\"$team\"  ORDER BY user_id") or die(mysql_error());
	for( $teamlist=array() ; $row=mysql_fetch_assoc($res) ; $teamlist[$row["user_id"]] = $row["name"] );
	return $teamlist;
}
function getproblist($user){
	global $problist;
	$res = mysql_query("SELECT * FROM prob_info WHERE avail=\"".PROBLEM_ONLINE."\" AND ready=\"".PROBLEM_READY."\" ORDER BY prob_order") or die(mysql_error());
	for($problist = array() ; $row=mysql_fetch_assoc($res) ; $problist[$row["prob_id"]]['total'] = array_sum(explode('-',$row['score'])))
		$problist[$row["prob_id"]] = $row;
	
	return $problist;
}
function displayprobinfo($user, $prob){
	echo "
	<div id='probinfo-$user-$prob'>
		<script> prob_template('$user','$prob'); </script>							
	</div>";
}
function displaydownload($user, $prob){
	global $problist, $subj_info;
	
	if($subj_info['content']=='ON'){
		$path=PATH_CONTENT_PROBLEM."/".$_SESSION['subj']."/$prob";
		if(is_dir($path)){
			$listfile = scandir($path);
		
			echo "<div class='btn-toolbar'>";
			foreach( array('pdf'=>'success','txt'=>'warning','data'=>'info') as $extend=>$color ){
				echo "<div class='btn-group'>";
					foreach( $listfile as $file ){
						if( pathinfo( $file , PATHINFO_EXTENSION )==$extend ){
							$size = filesize_extend("$path/$file");
							echo "<a class='btn btn-mini btn-$color' href='download-content.php?pathname=$path&filename=$file'><i class='icon-file icon-white'></i> $file - $size</a>";						
						}
					}
				echo "</div>";
			}
			echo "</div>";
		}
	}
}
function download_source($user, $prob, $name){
	global $subj_info;
	
	if($subj_info['source']=='ON'){
		$res = mysql_query("SELECT max(sub_num) as max_subnum FROM submission WHERE user_id=\"$user\" AND prob_id=\"$prob\"") or die(mysql_error());
		if($info=mysql_fetch_assoc($res)){
			echo"<div align='center'>";
				$max_subnum = $info['max_subnum'];
				for( $subnum=$max_subnum-$subj_info['max_source']+1 ; $subnum<=$max_subnum ; ++$subnum )
					if( $subnum>0 )
						echo "<a href=\"download-source.php?prob=$prob&subnum=$subnum&filename=$name.cpp\"> [source#$subnum] </a>";
			echo"</div>";
		}
	}
}
function displaysubmited($user, $prob){
	echo'
	<div class="container-fluid" style="margin-top:0px; float:right;">
		<form id="submitform-'.$user.'-'.$prob.'" align="right" action="submission-submit.php" target="submit_page" method="post" enctype="multipart/form-data" style="margin-bottom: 0px; margin-top: 0px;">
			<input type="hidden" name="id"     id="id" 	   value="'.$user.'">
			<input type="hidden" name="probid" id="probid" value="'.$prob.'">
			<input type="file"   name="code"   id="code" style="opacity: 0.7; margin-top: 15px;" size="20"/>
			<input type="submit" class="btn btn-inverse btn-mini" name="submit" value="   Submit   "/>
		</form>
	</div>';
}
function listprob($user){
	global $problist;
	$problist = getproblist($user);
	
	foreach( $problist as $prob=>$info ){
		echo" 
			<div id='link-$user-$prob' class='accordion-heading' style='background-color:#111; margin-top: 8px; margin-bottom: 8px;'>
				<table width='100%'>
				<tr>
					<td width='30%' align='left'>
						<font class='score' size='4' color='#555'>".$info['time']."</font>
					</td>
					<td width='40%' align='center'>
						<a class='navbar prob-name' data-toggle='collapse' data-parent='#' style='color:".$info['color'].";' href='#$user-$prob' onclick='focusprob(\"".$user."\", \"$prob\", false);'>
							<font size='4'>".$info['name']."</font>
						</a>
					</td>
					<td width='30%' align='right'>
						<font class='score' size='4' color='#555'><span class='score'>0</span>/<span class='full-score'>".$info['total']."</span></font>
					</td>
				</tr>
				</table>
			</div>
			<div id='$user-$prob' class='accordion-body collapse in' style='background-color:#eee; width:100%;'>
				<div class='accordion-inner'>";
				switch($_SESSION['type']){
					case USERTYPE_SUPERADMIN :
					case USERTYPE_ADMIN      :
					case USERTYPE_CONTESTANT :
												displaydownload($user, $prob);
												displayprobinfo($user, $prob);
												download_source($user, $prob, $info['name']);
												displaysubmited($user, $prob);
												break;
					case USERTYPE_SUPERVISOR :
												displayprobinfo($user, $prob);
												break;
					default:;
				}
		echo"	</div>
			</div>";
	}
	echo "<br/>";
}
function listteam($team)
{
	$teamlist = getteamlist($team);
	
	foreach( $teamlist as $user=>$name ) {
		echo"
			<div class='accordion-heading' style='background-color:#111; margin-top: 8px; margin-bottom: 8px;'>
				<a class='navbar' data-toggle='collapse' data-parent='#' href='#$team-$user' >
					<i class='icon-user icon-white'></i><font style='color: #aaffaa; font-size:18px;'> $user : $name</font>
				</a>
			</div>
			<div id='$team-$user' class='accordion-body collapse' style='background-color:#111'>
				<div class='accordion-inner' style='padding-left: 10%; padding-bottom: 30px'>";
					listprob($user);
		echo"	</div>
			</div>";
	}
}
function displayprintingbox(){
	global $subj_info;
	
	if($subj_info['printer']=='ON'){
		echo '
		<div class="printer" class="container-fluid" style="display:table-cell; width:350px; position:relative; top:-14px;">
			<form id="printer-form" align="left" action="printing.php" target="submit_page" method="post" enctype="multipart/form-data" style="" onsubmit="print_action();">
				<i class="icon-print icon-white" style="margin-top: 2px;"></i>
				<input type="submit" style="height:20px; padding-top:0" class="btn btn-inverse btn-mini" name="print" value="   Print   "/>
				<input type="file" style="opacity: 0.7; margin-top: 14px;" name="code" size="20"/>
			</form>
			<div id="printer-load" class="progress progress-striped '.array_rand(array("progress-info"=>1,"progress-warning"=>2,"progress-success"=>3," "=>4,"progress-danger"=>5)).' active" style="margin-top:15px;width:95%; height:15px;float:left; margin-bottom:0px;">
				<div class="bar" style="width: 100%;"></div>
			</div>
		</div>';
	}
}
function displayproblink(){
	global $problist, $total, $subj_info;
	
	if($subj_info['link']=='ON'){
		echo "<div id='prob-bottom-cover' style='display:table-cell;'>
				<div style='display:table; width:100%; position:relative; top:5px; right:5px;'>";
		foreach($problist as $prob=>$info){
			echo "<div id='cover-$prob' style='display:table-cell; height:16px; width:".($info['total']/$total*100)."%; border:2px; border-style:inset; border-color:#333; background-color:#555;'>
					<div id='power-$prob' class='power' style='height:100%; width:0%;  background-color:#333;'>
					</div>
					<div style='position:relative; top:-18px; text-align:center;'>
						<a id='link-activate-$prob' data-toggle='collapse' style='color:#eee;' href='#".$_SESSION['user']."-$prob' onclick='focusprob(\"".$_SESSION['user']."\", \"$prob\", true);'><i>".$info['name']."</i></a>
					</div>
				  </div>";
		}
		echo "	</div>
			</div>
			<div class='total-score' style='position:relative; top:-11px; display:table-cell; width:1px;'>
				<a class='on' href='#' onclick='expand_toggle(\"".$_SESSION['user']."\");' style='color:#555;'>
					<h3>
						<span class='total'>0</span>/<span class='full-total'>$total</span>
					</h3>
				</a>
			</div>";
	}
}
function displaybottombar(){
	global $problist, $total;
	$total = 0;
	foreach(array_reverse($problist) as $prob=>$info) $total += $info['total'];
	echo "<div class='navbar navbar-fixed-bottom ' style='display:block; height: 30px; width:100%; background-color: #222; margin:0; padding:0'>
			<div id='bottom-bar'  style='display:table; width:100%; background-color: #222; margin:0 0 0 0; padding:0 '>";
				displayprintingbox();
				displayproblink();
	echo "	</div>
		</div>";
}
?>

<?php
echo '<iframe id="submit_page" name="submit_page" style="width:0;height:0;border:0px solid #fff;"></iframe>';
global $subj_info;
$subj_info = getsubjectinfo();
connect_db();
switch($_SESSION['type']){
	case USERTYPE_SUPERADMIN:
	case USERTYPE_ADMIN     :
	case USERTYPE_CONTESTANT:
								listprob($_SESSION['user']);
								break;
	case USERTYPE_SUPERVISOR:
								listteam($_SESSION['team']);
								break;
	default: ;
}
close_db();
switch($_SESSION['type']){
	case USERTYPE_SUPERVISOR: break;
	default: displaybottombar();
}
?>
