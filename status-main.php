<?php
include_once 'z-config.php';
include_once 'z-db.php';
include_once 'z-util.php';

checkauthen();
checkauthorize( USERTYPE_ADMIN );
?>
<script>
	var sort_list = new Array();
	var direction = new Array();
	var key_focus = null;
	var del_mode  = -1;
	$(document).ready(function(){
		status_information(true, true);
		$('#command').focus();
		$(document).keypress(function(e){
			if( $('*:focus').attr('type')!='text' && e.keyCode==32 ){
				$('#command').focus();
			}
		});
		$('#command').keydown(function(e){
			if( e.keyCode!=13 ) return;
					
			command = $("#command").val();
			if( command.indexOf("approve")>=0 ) approve_status();
			if( command.indexOf("delete" )>=0 ){
				del_mode *= -1;
				delete_mode();
			}
			if( command.indexOf("download")>=0) download_table();
		});
	});
	function status_information( refresh, fade ){
		var order_by = new Array();
		for( key in sort_list ){
			order_by[key] = sort_list[key]+(direction[sort_list[key]]==1?' ASC':' DESC');
		}
		order = order_by.join(', ');
		order = (order!='')?(order+', '):'';
		$.ajax({
				url: 'status-table.php',
				type: 'post',
				data: {'mode':'display', 'order':order},
				success: function( content ){
					if($('#status-info-table').length==1){
						$('#status-info-table').html(content);
						$('#arrow-'+key_focus).html(direction[key_focus]==1?' &#x25B2;':' &#x25BC;');
						if( fade==true )
							$('#status-info-table').hide().fadeIn(500);
						if(refresh)
							setTimeout(function(){ status_information(true, false); }, 5000);
						delete_mode();
					}
				}
		});
	}
	function delete_status( status ){
		$(status).parents('tr').children().css('background-color','#ffaa00');
		$.ajax({
				url: 'status-table.php',
				type: 'post',
				data: {'mode':'delete', 'id':($(status).parents('tr').attr('class'))},
				success: function( msg ){
					if( msg.search('finish')>=0 ){
						$(status).parents('tr').fadeOut(500);
						setTimeout( function(){
							$(status).parents('tr').remove();
							for(row=1 ; row<$('#status-list-info tr').length ; ++row)
								$('#status-list-info tr:eq('+row+') td:eq(0)').html(row);	
						}, 501);
					}
					else{
						status_information(false, true);
					}
				}
		});
	}
	function approve_status(){
		$('#status-list-info').fadeOut(500);
		$.ajax({
				url: 'status-table.php',
				type: 'post',
				data: {'mode':'approve'},
				success: function( msg ){
					status_information(false, true);
				}
		});
	}
	function delete_mode(){
		if( del_mode>0 ){
			$('#status-list-info td.delete').each(function(){
				$(this).html('<a href="javascript:void(0)" onclick="delete_status(this);">'+$(this).text()+'</a>');
			});
		}
		else{
			$('#status-list-info td.delete').each(function(){
				$(this).html($(this).text());
			});
		}
	}
	function download_table(){
		var order_by = new Array();
		for( key in sort_list ){
			order_by[key] = sort_list[key]+(direction[sort_list[key]]==1?' ASC':' DESC');
		}
		order = order_by.join(', ');
		order = (order!='')?(order+', '):'';
		window.location = 'status-table.php?order='+order;
	}
	function sort_table( col, key ){
		if(sort_list.indexOf(key)>=0)
			sort_list.splice(sort_list.indexOf(key), 1);
		sort_list.unshift(key);
		if(key in direction) direction[key] *= -1;
		else				 direction[key]  =  1;
		
		key_focus = key;
		status_information(false, false);
	}
</script>
<div id="status-info-table" class="container" style="padding: 10 0 25 0;">

</div>
<div class="navbar navbar-fixed-bottom " style="height: 21px; background-color: #222; padding: 4 0 0 0; width:100%; align:right;">
	<center><font style="color: eee;">I want to <input id="command" type="text" placeholder="manage status" style="width: 150px; margin:-4 0 0 0; text-align: center;"/> .</font></center>
</div>