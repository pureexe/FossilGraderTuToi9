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
	var del_prob  = -1;
	$(document).ready(function(){
		prob_information();
		$('#command').focus();
		$('#uploadModal').on('hidden', function () { $('#command').focus(); $('#upload-message').html(""); $('#upload-form').each(function(){this.reset();}); prob_information();});
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
				del_prob *= -1;
				delete_mode();
			}
			if( command.indexOf("download")>=0 ){
				download_table();
			}
		});
	});
	function prob_information( fade ){
		var order_by = new Array();
		for( key in sort_list ){
			order_by[key] = sort_list[key]+(direction[sort_list[key]]==1?' ASC':' DESC');
		}
		order = order_by.join(', ');
		order = (order!='')?(' ORDER BY '+order):'';
		$.ajax({
				url: 'prob-table.php',
				type: 'post',
				data: {'mode':'display', 'order':order},
				success: function( content ){
					$('#prob-info-table').html(content);
					$('#arrow-'+key_focus).html(direction[key_focus]==1?' &#x25B2;':' &#x25BC;');
					if( fade==null )
						$('#prob-info-table').hide().fadeIn(500);
					delete_mode();
					$('.colorpicker').remove();
					$('.pick').colorpicker({format: 'hex'});
					$('.pick').colorpicker().on('changeColor', function(ev){$(this).val(ev.color.toHex());});
				}
		});
	}
	function delete_prob( prob ){
		$(prob).parents('tr').children().css('background-color','#ffaa00');
		$.ajax({
				url: 'prob-table.php',
				type: 'post',
				data: {'mode':'delete', 'prob':($(prob).text())},
				success: function( msg ){
					if( msg.search('finish')>=0 ){
						$(prob).parents('tr').next().fadeOut(500);
						$(prob).parents('tr').fadeOut(500);
						setTimeout( function(){
							$(prob).parents('tr').next().remove();
							$(prob).parents('tr').remove();
							$('#prob-list-info tr td.number').each(function(index){
								$(this).text(index+1);	
							});
						}, 501);
					}
					else{
						prob_information();
					}
				}
		});
	}
	function check_config( prob ){
		$('tr.file-'+prob+' div.error-panel').fadeOut(200);
		$.ajax({
				url: 'prob-table.php',
				type: 'post',
				data: {'mode':'config', 'prob':prob},
				success: function( msg ){
					$('tr.'+prob+' td.ready div.text a').text(msg.search('danger')>=0?'unready':'ready');
					$('tr.'+prob+' td.ready div.edit a').text(msg.search('danger')>=0?'unready':'ready');
					$('tr.'+prob+' td.ready div.text a').css('color', msg.search('danger')>=0?'#aa0011':'#00aa11');
					$('tr.'+prob+' td.ready div.edit a').css('color', msg.search('danger')>=0?'#aa0011':'#00aa11');
					$('tr.file-'+prob+' div.error-panel').html(msg);
					$('tr.file-'+prob+' div.error-panel').fadeIn(500);
				}
		});
	}
	function update_prob( event ){
		if( event.keyCode!=13 ) return;
		
		$('.pick').colorpicker('hide');
		
		var list = new Array();
		$('tr.update').each(function(i){
			$(this).find('.edit').children().attr("disabled","disabled");
			list[i] = new Array();
			$(this).find('.edit').each(function(ii){
				switch($(this).children().get(0).tagName){
					case 'A' : data = $(this).children().text(); break;
					default  : data = $(this).children().val();
				}
				list[i][ii] = '"'+ii+'":"'+data+'"';
			});
		});
		for(row=0 ; row<list.length ; ++row)
			list[row] = '{'+list[row].join(',')+'}';
		var temp = jQuery.parseJSON('{"mode":"update","list":['+list.join(',')+']}');
		$.ajax({
				url: 'prob-table.php',
				type: 'post',
				data: temp,
				success: function( msg ){
					if( msg.search('finish')>=0 ) close_edit();
					else						  prob_information();
				}
		});		
	}
	function upload_message( msg ){
		$('#upload-message').fadeOut(500);
		$('#upload-message').html(msg);
		$('#upload-message').hide().fadeIn(500);
	}
	function delete_mode(){
		if( del_prob>0 ){
			$('#prob-list-info td.prob_id div.text font').each(function(){
				$(this).html('<a href="javascript:void(0)" onclick="delete_prob(this);">'+$(this).html()+'</a>');
			});
		}
		else{
			$('#prob-list-info td.prob_id div.text font').each(function(){
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
			var classname = $(this).attr('class').split(/\s+/);
			check_config( classname[0] );
			color = $(this).find('div.edit input.colors').val();
			$(this).find('td').each(function(){
					 if($(this).find('.edit select option:selected').length) data = $(this).find('.edit select option:selected').text();
				else if($(this).find('.edit input                 ').length) data = $(this).find('.edit').children().val();
				else									   				  	 data = $(this).find('.edit').children().text();
				$(this).find('.text').children().text(data);
				
				$(this).find('div.text font.act').attr('color',color);
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
		window.location = 'prob-table.php?order='+order;
	}
	function sort_table( col, key ){
		if(sort_list.indexOf(key)>=0)
			sort_list.splice(sort_list.indexOf(key), 1);
		sort_list.unshift(key);
		if(key in direction) direction[key] *= -1;
		else				 direction[key]  =  1;
		
		key_focus = key;
		prob_information( false );
	}
	function submit_type( event ){
		if( event.keyCode==13 ){
			$('#upload-form').submit();
		}
	}
	function getfilelist( target ){
		$(target).next().next().next().empty();
		for( i=0 ; i<target.files.length ; ++i ){
			$(target).next().next().next().append((target.files[i].name)+'<br/>');
		}
	}
</script>
<iframe id="submit-page" name="submit-page" style="width:0px;height:0px;border:0px solid #fff;"></iframe>
<div class="modal hide fade" id="uploadModal" style="display: none;">
	<div class="modal-header">
		<button id="closeuploadmodal" class="close" data-dismiss="modal">x</button>
		<h3>Upload Problem</h3>
	</div>
	<div class="modal-body">
		<form id="upload-form" action="prob-table.php" target="submit-page" method="post" enctype="multipart/form-data" style="margin: 0 0 40 0; padding:0 0 0 0; width:100%;">
			<div class="alert alert-success" style="padding-right:15px; margin-bottom:8px;">
				<b>Format:</b> one folder per problem: <br/>
				<input id="prob-name" name="prob-name" type="text" placeholder="folder problem name" style="text-align: center; width: 30%; margin-bottom:-8px;color:#c09853; backgroud-color:#fcf8e3; border-color:#fbeed5"/>
				<div class="alert alert-warning" style="padding-right:15px; margin-bottom:8px;">
					<div style="margin-top: 8px;">
						<input id="description-file" name="file[]" type="file" multiple="" onchange="getfilelist(this);"  style="opacity: 0.7; margin: -2 0 0 0; float:left;" size="20"/>
						<div style="float: right;">&ltdescription_file&gt<b>.pdf</b></div><br/>
						<div style="text-align: right; margin-top:8px; margin-bottom: 8px;">
						</div>
					</div>
					<hr style="margin-top: 14px;margin-bottom: 14px;">
					<div style="margin-top: 8px;">
						<input id="in-file" name="file[]" type="file" multiple="" onchange="getfilelist(this);" style="opacity: 0.7; margin: -2 0 0 0; float:left;" size="20"/>
						<div style="float: right;">&ltnumber&gt<b>.in</b></div><br>
						<div style="text-align: right; margin-top:8px; margin-bottom: 8px;">
						</div>
					</div>
					<hr style="margin-top: 14px;margin-bottom: 14px;">
					<div style="margin-top: 8px;">
						<input id="sol-file" name="file[]" type="file" multiple="" onchange="getfilelist(this);" style="opacity: 0.7; margin: -2 0 0 0; float:left;" size="20"/>
						<div style="float: right;">&ltnumber&gt<b>.sol</b></div><br>
						<div style="text-align: right; margin-top:8px; margin-bottom: 8px;">
						</div>
					</div>
					<hr style="margin-top: 14px;margin-bottom: 0px;">
					Can choose many file at once.
				</div>
				<div>Attach these folder to one <tt>zip</tt> for upload.</div>
			</div>
			<input id="mode" name="mode" type="hidden" value="upload"/>
			<input type="submit" class="btn btn-success btn-mini" value=" Upload " style="margin: 0 0 0 0; float:right;"/>
			<input id="zip" name="zip" type="file" style="opacity: 0.7; margin: 0 0 0 0; float:right;" size="20"/>
		</form>
		<div id="upload-message">
		
		</div>
	</div>
</div>
<div id="prob-info-table" class="container" style="width:97%; padding: 10 0 100 0;">

</div>
<div class="navbar navbar-fixed-bottom " style="height: 21px; background-color: #222; padding: 4 0 0 0; width:100%; align:right;">
	<center><font style="color: eee;">I want to <input id="command" type="text" placeholder="manage prob" style="width: 150px; margin:-4 0 0 0; text-align: center;"/> .</font></center>
</div>
<?php $zip = new ZipArchive();?>