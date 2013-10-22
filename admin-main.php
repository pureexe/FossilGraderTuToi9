<?php
include_once 'z-config.php';
include_once 'z-db.php';
include_once 'z-util.php';

checkauthen();
checkauthorize( USERTYPE_SUPERADMIN );
?>
<script>
	var sort_list = new Array();
	var direction = new Array();
	var key_focus = null;
	var del_admin = -1;
	$(document).ready(function(){
		user_information();
		$('#command').focus();
		$('#uploadModal').on('hidden', function () { $('#command').focus(); $('#upload-message').html(""); $('#upload-form').each(function(){this.reset();}); user_information();});
		$('#uploadModal').on( 'shown', function () { $('#closeuploadmodal').focus();});
		$(document).keypress(function(e){
			if( $('*:focus').attr('type')!='text' && e.keyCode==32 ){
				$('#command').focus();
			}
		});
		$('#command').keydown(function(e){
			if( e.keyCode!=13 ) return;
					
			command = $("#command").val();
			if( command.indexOf("upload")>=0 )  $('#uploadModal').modal('show'); 
			else						   	    $('#uploadModal').modal('hide');	
			if( command.indexOf("delete")>=0 ){
				del_admin *= -1;
				delete_mode();
			}
			if( command.indexOf("download")>=0 ) download_table();
		});
	});
	function user_information( fade ){
		var order_by = new Array();
		for( key in sort_list ){
			order_by[key] = sort_list[key]+(direction[sort_list[key]]==1?' ASC':' DESC');
		}
		order = order_by.join(', ');
		order = (order!='')?(' ORDER BY '+order):'';
		$.ajax({
				url: 'admin-table.php',
				type: 'post',
				data: {'mode':'display', 'order':order},
				success: function( content ){
					$('#user-info-table').html(content);
					$('#arrow-'+key_focus).html(direction[key_focus]==1?' &#x25B2;':' &#x25BC;');
					if( fade==null )
						$('#user-info-table').hide().fadeIn(500);
					delete_mode();
				}
		});
	}
	function delete_user( user ){
		$(user).parents('tr').children().css('background-color','#ffaa00');
		$.ajax({
				url: 'admin-table.php',
				type: 'post',
				data: {'mode':'delete', 'user':($(user).parent().parent().attr('class'))},
				success: function( msg ){
					if( msg.search('finish')>=0 ){
						$(user).parents('tr').fadeOut(500);
						setTimeout( function(){
							$(user).parents('tr').remove();
							for(row=1 ; row<$('#user-list-info tr').length ; ++row)
								$('#user-list-info tr:eq('+row+') td:eq(0)').html(row);	
						}, 501);
					}
				}
		});
	}
	function update_user( event ){
		if( event.keyCode!=13 ) return;
		
		var list = new Array();
		$('tr.update').each(function(i){
			$(this).find('.edit').children().attr("disabled","disabled");
			list[i] = new Array();
			$(this).find('.edit').each(function(ii){
				list[i][ii] = '"'+ii+'":"'+$(this).children().val()+'"';
			});
		});
		for(row=0 ; row<list.length ; ++row)
			list[row] = '{'+list[row].join(',')+'}';
		var temp = jQuery.parseJSON('{"mode":"update","list":['+list.join(',')+']}');
		$.ajax({
				url: 'admin-table.php',
				type: 'post',
				data: temp,
				success: function( msg ){
					if( msg.search('finish')>=0 ) close_edit();
					else						  user_information();
				}
		});		
	}
	function upload_message( msg ){
		$('#upload-message').fadeOut(500);
		$('#upload-message').html(msg);
		$('#upload-message').hide().fadeIn(500);
	}
	function delete_mode(){
		if( del_admin>0 ){
			$('#user-list-info td.user_id').each(function(){
				$(this).html('<a href="javascript:void(0)" onclick="delete_user(this);">'+$(this).text()+'</a>');
			});
		}
		else{
			$('#user-list-info td.user_id').each(function(){
				$(this).html($(this).text());
			});
		}
	}
	function edit_row( row ){
		if($(row).hasClass('update')) return;
		
		$(row).find('.text').hide();
		$(row).find('.edit').show();
		$(row).addClass('update');
	}
	function close_edit(){
		$('tr.update').each(function(i){
			$(this).find('td').each(function(){
				$(this).find('.text').text($(this).find('.edit').children().val());
			});
			$(this).find('.edit').hide();
			$(this).find('.text').show();
			$(this).find('.edit').children().removeAttr("disabled");
			$(this).removeClass('update');
		});
		delete_mode();
	}
	function download_table(){
		var order_by = new Array();
		for( key in sort_list ){
			order_by[key] = sort_list[key]+(direction[sort_list[key]]==1?' ASC':' DESC');
		}
		order = order_by.join(', ');
		order = (order!='')?(' ORDER BY '+order):'';
		window.location = 'admin-table.php?order='+order;
	}
	function sort_table( col, key ){
		if(sort_list.indexOf(key)>=0)
			sort_list.splice(sort_list.indexOf(key), 1);
		sort_list.unshift(key);
		if(key in direction) direction[key] *= -1;
		else				 direction[key]  =  1;
		
		key_focus = key;
		user_information( false );
	}
	function submit_type(event){
		if( event.keyCode==13 ){
			$('#upload-form').submit();
		}
	}
</script>

<iframe id="submit-page" name="submit-page" style="width:0px;height:0px;border:0px solid #fff;"></iframe>
<div class="modal hide fade" id="uploadModal" style="display: none;">
	<div class="modal-header">
		<button id="closeuploadmodal" class="close" data-dismiss="modal">x</button>
		<h3>Upload User</h3>
	</div>
	<div class="modal-body">
		<form id="upload-form" action="admin-table.php" target="submit-page" method="post" enctype="multipart/form-data" style="margin: 0 0 20 0; width:100%;">
			<div class="alert alert-success" style="padding-right: 0px; margin-bottom: 8px;">
				<b>Format:</b> one line per user: <br/>
				<div style="margin:10 0 0 7;">
					<input id="user" name="user" type="text" placeholder="username" style="width: 15%; text-align: center;"/> :
					<input id="name" name="name" type="text" placeholder="name"     style="width: 20%; text-align: center;"/> :
					<input id="pass" name="pass" type="text" placeholder="password" style="width: 15%; text-align: center;"/> :
					<input id="team" name="team" type="text" placeholder="group"    style="width: 15%; text-align: center;"/> :
					<input id="mail" name="mail" type="text" placeholder="e-mail"   style="width: 15%; text-align: center;"/> :
					<select id="type" name="type" type="text" placeholder="type"    style="width:  7%; text-align: center; padding:0; margin:-9 0 0 0; border:2; height:18px;" onkeypress="submit_type(event);">
						<option value="A" > A</option>
						<option value="SA">SA</option>
					</select>
				</div>
				last selection is <tt>type</tt> following: <tt>(A)</tt>dmin,<tt>(SA)</tt>uper[]dmin<br/>
			</div>
			<input id="mode" name="mode" type="hidden" value="upload"/>
			<input type="submit" class="btn btn-success btn-mini" value=" Upload " style="margin: 0 0 0 0; float:right;"/>
			<input id="file" name="file" type="file" style="opacity: 0.7; margin: 0 0 0 0; float:right;" size="20"/>
		</form>
		</br>
		<div id="upload-message">
		
		</div>
	</div>
</div>
<div id="user-info-table" class="container" style="padding: 10 0 25 0;">

</div>
<div class="navbar navbar-fixed-bottom " style="height: 21px; background-color: #222; padding: 4 0 0 0; width:100%; align:right;">
	<center><font style="color: eee;">I want to <input id="command" type="text" placeholder="manage user" style="width: 150px; margin:-4 0 0 0; text-align: center;"/> .</font></center>
</div>