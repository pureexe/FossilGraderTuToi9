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
	var grade     = -1;
	$(document).ready(function(){
		grader_information(true, true);
		$('#command').focus();
		$(document).keypress(function(e){
			if( $('*:focus').attr('type')!='text' && e.keyCode==32 ){
				$('#command').focus();
			}
		});
		$('#command').keydown(function(e){
			if( e.keyCode!=13 ) return;
					
			command = $("#command").val();
				 if( command.indexOf("gradeall")>=0 ) gradeall(); 
			else if( command.indexOf("gradeone")>=0 ){
				grade *= -1;
				grading_mode();
			}
			else if( command.indexOf("clear")>=0 ) clear_queue(); 
			else if( command.indexOf("download")>=0 ) download_table();
		});
	});
	function grader_information( refresh, fade ){
		var order_by = new Array();
		for( key in sort_list ){
			order_by[key] = sort_list[key]+(direction[sort_list[key]]==1?' ASC':' DESC');
		}
		order = order_by.join(', ');
		order = (order!='')?('ORDER BY '+order):'';
		view = $('#view').val();
		$.ajax({
				url: 'grader-table.php',
				type: 'post',
				data: {'mode':'display', 'order':order, 'view':view, 'grade':grade},
				success: function( content ){
					if($('#grader-info-table').length==1){
						$('#grader-info-table').html(content);
						$('#arrow-'+key_focus).html(direction[key_focus]==1?' &#x25B2;':' &#x25BC;');
						if( fade==null )
							$('#grader-info-table').hide().fadeIn(500);
						if(refresh)
							setTimeout(function(){ grader_information(true, false); }, 5000);
						grading_mode();
					}
				}
		});
	}
	function clear_queue(){
		$.ajax({
				url: 'grader-table.php',
				type: 'post',
				data: {'mode':'clear'},
				success: function( msg ){
					grader_information(false, true);
				}
		});
	}
	function gradeone( grader ){
		$(grader).parents('tr').children().css('background-color','#ffaa00');
		user     = $(grader).parents('tr').children('td.user_id' ).text();
		prob     = $(grader).parents('tr').children('td.prob_id' ).text();
		subnum   = $(grader).parents('tr').children('td.sub_num' ).text();
		compiler = $(grader).parents('tr').children('td.compiler').text();
		$.ajax({
				url: 'grader-table.php',
				type: 'post',
				data: {'mode':'gradeone', 'user':user, 'prob':prob, 'subnum':subnum, 'compiler':compiler},
				success: function( msg ){
					grader_information(false, true);
				}
		});
	}
	function gradeall(){
		$('#grader-list-info tbody tr').children().css('background-color','#ffaa00');
		var order_by = new Array();
		for( key in sort_list ){
			order_by[key] = sort_list[key]+(direction[sort_list[key]]==1?' ASC':' DESC');
		}
		order = order_by.join(', ');
		order = (order!='')?('ORDER BY '+order):'';
		view = $('#view').val();
		$.ajax({
				url: 'grader-table.php',
				type: 'post',
				data: {'mode':'gradeall', 'order':order, 'view':view, 'grade':grade},
				success: function( msg ){
					grader_information(false, true);
				}
		});
	}
	function grading_mode(){
		if( grade>0 ){
			$('#grader-list-info td.user_id').each(function(){
				$(this).html('<a href="javascript:void(0)" onclick="gradeone(this);">'+$(this).text()+'</a>');
			});
		}
		else{
			$('#grader-list-info td.user_id').each(function(){
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
		order = (order!='')?('ORDER BY '+order):'';
		view  = $('#view').val();
		window.location = 'grader-table.php?order='+order+'&view='+view;
	}
	function sort_table( col, key ){
		if(sort_list.indexOf(key)>=0)
			sort_list.splice(sort_list.indexOf(key), 1);
		sort_list.unshift(key);
		if(key in direction) direction[key] *= -1;
		else				 direction[key]  =  1;
		
		key_focus = key;
		grader_information(false, false);
	}
</script>
<div id="grader-info-table" class="container" style="padding: 10 0 25 0;"></div>
<?php
connect_db();
$res = mysql_query('select prob_id, name FROM prob_info');
$option = "";
while($row=mysql_fetch_assoc($res))
	$option .= "<option value=".$row['prob_id'].">".$row['name']."</option>";
close_db();;
echo"
	<div class='navbar navbar-fixed-bottom ' style='height: 25px; background-color: #222; padding: 0 0 0 0; width:100%; align:right;'>
		<div align='center' style='padding: 4 0 0 0;' >
		<font style='color: eee;'>
			I want to
				<input id='command' type='text' placeholder='view' style='width: 150px; margin:-4 0 0 0; text-align: center;'/>
			as
				<select id='view' onChange='grader_information(false, true)' style='width: 100px; padding:0; margin:-3 0 0 0; border:0px; height:18;'>
					$option
					<option value='offline'>  offline </option>
					<option value='online'>    online </option>
					<option value='all' selected> all </option>
				</select>
			.
		</font>
		</div>			
	</div>";
?>