<?php
include_once 'z-config.php';
include_once 'z-db.php';
include_once 'z-util.php';

checkauthen();
//checkauthorize( USERTYPE_ADMIN );
?>
<script>
$(document).ready(function(){
	result_information(true);
	$('#command').focus();
	$(document).keypress(function(e){
		if( $('*:focus').attr('type')!='text' && e.keyCode==32 ){
			$('#command').focus();
		}
	});
	$('#command').keydown(function(e){
		if( e.keyCode!=13 ) return;
					
		command = $("#command").val();
		if( command.indexOf("download")>=0 ){ download_table(); }
	});
});
function result_information( refresh ){
	view   = $('#view').val();
	sort   = $('#sort').val();
	detail = $('#detail').val();
	time   = $('#time').val();
	$.ajax({
			url: 'result-table.php',
			type: 'post',
			data: {'mode':'display', 'view':view, 'sort':sort, 'detail':detail, 'time':time},
			success: function(msg){
				if($('#table_content').length==1){
					$('#table_content').html(msg);
					if( refresh ){
						setTimeout(function(){ result_information(true);}, 5000);
					}
				}
			}
	});
}
function download_table(){
	view   = $('#view').val();
	sort   = $('#sort').val();
	detail = $('#detail').val();
	time   = $('#time').val();
	window.location = 'result-table.php?view='+view+'&sort='+sort+'&detail='+detail+'&time='+time;
}
</script>
<div id='table_content' class='container-fluid' style='padding: 10 0 25 0;'></div>
<?php
switch($_SESSION['type']){
	case USERTYPE_ADMIN:
	case USERTYPE_SUPERVISOR:
			connect_db();
			$res = mysql_query('select prob_id, name FROM prob_info');
			$option = "";
			while($row=mysql_fetch_assoc($res))
				$option .= "<option value=".$row['prob_id'].">".$row['name']."</option>";
			close_db();
			echo"
				<div class='navbar navbar-fixed-bottom ' style='height: 25px; background-color: #222; padding: 0 0 0 0; width:100%; align:right;'>
					<div align='center' style='padding: 4 0 0 0;' >
					<font style='color: eee;'>
						I want to
							<input id='command' type='text' placeholder='view' style='width: 150px; margin:-4 0 0 0; text-align: center;'/>
						as
							<select id='view' onChange='result_information(false)' style='width: 100px; padding:0; margin:-3 0 0 0; border:0px; height:18;'>
								$option
								<option value='OFF'>offline</option>
								<option value='ON' selected>online</option>
								<option value='all'>all</option>
							</select>
						sort by
							<select id='sort' onChange='result_information(false)' style='width: 100px; padding:0; margin:-3 0 0 0; border:0px; height:18;'>
								<option value='user' selected>username</option>
								<option value='score'>score</option>
							</select>
						detail
							<select id='detail' onChange='result_information(false)' style='width: 100px; padding:0; margin:-3 0 0 0; border:0px; height:18;'>
								<option value=0 selected>summary</option>
								<option value=1>full</option>
							</select>
						start time
							<input id='time' type='text' placeholder='YYYY-MM-DD  H:M:S' style='width: 150px; text-align: center; margin:-3 0 0 0' onchange='result_information(false);'/>
					</font>
					</div>
				</div>";
			break;
	case USERTYPE_CONTESTANT:
			echo"<input id='sort'   type='hidden' value='score'/>
				 <input id='view'   type='hidden' value='ON'   />
				 <input id='detail' type='hidden' value=0      />
				 <input id='time'   type='hidden' value=''	   />";
}
?>


	
	
	
