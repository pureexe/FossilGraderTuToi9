<?php
session_start();
function displaymessage(){
	echo"
		<div id='myModal' class='modal hide fade' style='display: none;'>
			<div class='modal-header'>
				<button id='closemodal' class='close' data-dismiss='modal' >x</button>
				<h3>Error</h3>
			</div>
			<div class='modal-body'>
				<p><br/><br/><br/><center><b><span id='error_msg'>..</span></b></center><br/><br/><br/><br/></br></p>
			</div>
		</div>";
}
?>
<html>
<head>
	<!--<META HTTP-EQUIV="Refresh" CONTENT="300">-->
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<link href="./bootstrap/css/bootstrap.css" rel="stylesheet">
    
	<link href="./bootstrap/css/bootstrap-responsive.css" rel="stylesheet">
	<link href="./bootstrap/less/bootstrap.less"          rel="stylesheet/less">
		
	<link rel="stylesheet" href="blueimp/css/bootstrap-responsive.min.css">
	<link rel="stylesheet" href="blueimp/css/bootstrap-image-gallery.min.css">
	<link rel="stylesheet" href="blueimp/css/jquery.fileupload-ui.css">
	
	<script src="./bootstrap/js/jquery.js"></script>
	<script src="./bootstrap/js/jquery-table-sort.js"></script>
	<script src="./bootstrap/js/jquery.cookie.js"></script>
	<script src="./bootstrap/js/bootstrap-transition.js"></script>
	<script src="./bootstrap/js/bootstrap-alert.js"></script>
	<script src="./bootstrap/js/bootstrap-modal.js"></script>
	<script src="./bootstrap/js/bootstrap-dropdown.js"></script>
	<script src="./bootstrap/js/bootstrap-scrollspy.js"></script>
	<script src="./bootstrap/js/bootstrap-tab.js"></script>
	<script src="./bootstrap/js/bootstrap-tooltip.js"></script>
	<script src="./bootstrap/js/bootstrap-popover.js"></script>
	<script src="./bootstrap/js/bootstrap-button.js"></script>
	<script src="./bootstrap/js/bootstrap-collapse.js"></script>
	<script src="./bootstrap/js/bootstrap-carousel.js"></script>
	<script src="./bootstrap/js/bootstrap-typeahead.js"></script>
	
	<script>
		$(document).ready(function(){
			set_position();
			$('body').fadeIn(500);
			$('#loading').fadeOut(500);
		});
		function set_position(){
			$('#loadimg').css('padding-top', ($( window ).height()-$('#loading').height())/2  );
		}
		function config_action(){
			mysql_username = $('#mysql-username').val();
			mysql_password = $('#mysql-password').val();	
			admin_username = $('#admin-username').val();
			admin_password = $('#admin-password').val();
			system_name	   = $('#system-name').val();
			$('#content').fadeOut(500);
			$('#loading').fadeIn(500);
			$.ajax({
				url: 'first-config-action.php',
				type: 'post',
				data: {'my-user':mysql_username,'my-pass':mysql_password,'ad-user':admin_username,'ad-pass':admin_password,'system':system_name},
				success: function( msg ){
					if(msg=='success'){
						$('body').fadeOut(500);
						window.location = "login-main.php";
					}
					else{
						$('#error_msg').text(msg);
						$("#myModal").modal("show");
						$("#closemodal").focus();
						
						$('#loading').fadeOut(500);
						$('#content').fadeIn(500);
					}
				}
			});	
		}
		function submit_config(event){
			if( event.keyCode==13 ){
				config_action();
			}
		}
	</script>	
</head>
<body style="background-color:#111; display:none;">
	<div  id="menubar" class="navbar navbar-fixed-top ">
		<div id="baner">
			<img src="./images/mainpage.jpg" style="width:100%; height:60px;"/>
		</div>
		<div class="navbar-inner">
			<div class="container-fluid">
				<a class="brand" href="#">First Configuration</a>
				<div class="nav-collapse collapse">
					<ul class="nav">
					</ul>
					<ul class="nav pull-right">
					</ul>
				</div>
			</div>
		</div>
    </div>
	<div id="loading" style="width:100%; height:100%; background: rgba(0, 0, 0, 0.8); position:absolute; z-index:1; display: non" align="center">
		<img id="loadimg" src="./images/loading-main.gif" style="height:30%; opacity: 0.5;"/>
	</div>
	<?php displaymessage(); ?>
	<div id="content" align="center">
		<div style="padding-top:100px; text-align:right; width:500px;">
			<br/><br/>
			<font color="#444" size="4">
				<b><i>Cofiguration</i></b>
			</font>
			<hr class="btn-inverse" style="margin-top:0px;"/>
			<font color="#55aaaa" size="4">
				MySQL username: <input id="mysql-username" class="alert-info" type="text" style="margin-top:5px; text-align:center;" onkeypress="submit_config(event);"><br>
				MySQL password: <input id="mysql-password" class="alert-info" type="text" style="margin-top:5px; text-align:center;" onkeypress="submit_config(event);"><br>
			</font>
			<font color="#55aaaa" size="1">
				*Username and password you set when install appserv.
			</font>
			<hr class="btn-inverse" style="margin-top:0px;"/>
			<font color="#aa5555" size="4">
				System name: <input id="system-name" class="alert-danger" type="text" style="margin-top:5px; text-align:center;" onkeypress="submit_config(event);"><br>
			</font>
			<font color="#aa5555" size="1">
				*Use [0-9|A-Z|a-z|_] to create.
			</font>
			<hr class="btn-inverse" style="margin-top:0px;"/>
			<font color="#55aa55" size="4">
				Admin username: <input id="admin-username" class="alert-success" type="text" style="margin-top:5px; text-align:center;" onkeypress="submit_config(event);"><br>
				Admin password: <input id="admin-password" class="alert-success" type="text" style="margin-top:5px; text-align:center;" onkeypress="submit_config(event);"><br>
			</font>
			<font color="#55aa55" size="1">
				*Use [0-9|A-Z|a-z|_] to create.
			</font>
			<hr class="btn-inverse" style="margin-top:0px; margin-bottom:5px;"/>
			<button class="btn btn-mini btn-inverse" onclick="config_action();"> <i>Configuration</i> </button>
		</div>
	</div>
</body>
</html>