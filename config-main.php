<?php
include_once 'z-config.php';
include_once 'z-db.php';
include_once 'z-util.php';

checkauthen();
checkauthorize( USERTYPE_ADMIN );
?>
<script>
$(document).ready(function(){
	display_subject_info();
});
function display_subject_info(){
	$.ajax({
			url: 'config-table.php',
			type: 'post',
			data: {'mode':'display'},
			success: function(msg){
				if(msg){
					$('#config-table-info').html(msg);				
					$('#max_source').keydown(function(e){
						if( e.keyCode!=13 ) return;
						update_subject_info();
						$(document).focus();
					});
					$('#printer_name').keydown(function(e){
						if( e.keyCode!=13 ) return;
						update_subject_info();
						$(document).focus();
					});
				}
			}
	});
}
function update_subject_info(){
	sets = new Array();
	index = -1;
	$('button').each(function(){
		if($(this).hasClass('active')){
			key = $(this).parent().attr('id');
			val = $(this).hasClass('on')?'ON':'OFF';
			sets[++index] = key+'="'+val+'"';
		}
	});
	sets[++index] = 'printer_name="'+$('#printer_name').val()+'"';
	sets[++index] = 'max_source='+$('#max_source').val();
	sets = sets.join(', ');
	$.ajax({
			url: 'config-table.php',
			type: 'post',
			data: {'mode':'update', 'sets':sets},
			success: function(msg){
				if( msg!='success' ){
					display_subject_info();
				}
			}
	});
}
function action_toggle(target){
	if($(target).hasClass('on')){	
		$(target).parent().children('.off').removeClass('btn-danger');
		$(target).parent().children('.off').removeClass('active');
		$(target).addClass('btn-info');
		$(target).addClass('active');
	}
	else{
		$(target).parent().children('.on').removeClass('btn-info');
		$(target).parent().children('.on').removeClass('active');
		$(target).addClass('btn-danger');
		$(target).addClass('active');
	}
	update_subject_info();
}
</script>
<div id='config-table-info' style='width:100%;'>
	
</div>