<?php
include_once 'z-config.php';
include_once 'z-db.php';
include_once 'z-util.php';

checkauthen();
checkauthorize( USERTYPE_ADMIN );
?>
<script>
$(document).ready(function(){
	display_teach_info();
});
function display_teach_info(){
	$.ajax({
			url: 'teach-table.php',
			type: 'post',
			data: {'mode':'display'},
			success: function(msg){
				if(msg){
					$('#teach-table-info').html(msg);				
					$('#max_source').keydown(function(e){
						if( e.keyCode!=13 ) return;
						update_teach_info();
						$(document).focus();
					});
				}
			}
	});
}
function update_teach_info($mode, $user, $subj){
	$.ajax({
			url: 'teach-table.php',
			type: 'post',
			data: {'mode':$mode, 'user':$user, 'subj':$subj},
			success: function(msg){
				if( msg!='success' ){
					display_teach_info();
				}
			}
	});
}
function action_toggle(target){
	if($(target).hasClass('active')){
		$(target).removeClass('btn-info');
		$(target).removeClass('active');
		$(target).text('No');
		$mode = 'delete';
	}
	else{
		$(target).addClass('btn-info');
		$(target).addClass('active');
		$(target).text('Yes');
		$mode = 'update';
	}
	$user = $(target).parents('tr').attr('class');
	$subj = $(target).parents('td').attr('class');
	
	update_teach_info($mode, $user, $subj);
}
</script>
<div id="teach-table-info" class="container" style="padding: 10 0 25 0;">
	
</div>