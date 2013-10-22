<?php
session_start();
if(isset($_SESSION['user'])){
	include_once 'z-config.php';
	include_once 'z-db.php';
	include_once 'z-util.php';
	connect_db(MASTER_TABLE);
	$subj = $_SESSION['subj'];
	$user = $_SESSION['user'];
	mysql_query("UPDATE log SET status=\"offline\" WHERE subj_id=\"$subj\" AND user_id=\"$user\" AND status=\"online\"") or die(mysql_error());
	close_db();
}
$_SESSION['user'] = "";
session_destroy();
include_once 'z-config.php';
include_once 'z-db.php';

function select_subject(){
	connect_db(MASTER_TABLE);
	$res = mysql_query("SELECT * FROM subj_info WHERE status=\"ON\" ORDER BY year DESC, term ASC, name ASC") or die(mysql_error());
	echo "<select id='subj' style='height:20px; width:285px;  padding:0'>";
	while( $info=mysql_fetch_assoc($res) ){
		echo "<option style='text-align:right' value='".$info['subj_id']."'>";
		if($info['year']!=""){
			echo "[".$info['year'];
			if($info['term']!="")
				echo "-".$info['term'];
			echo "]";
		}
		if($info['code']!="")
			echo "(".$info['code'].") ";
		echo $info['name']."</option>";
	}
	echo "</select>";
	close_db();
}
?>

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=TIS-620"/>
	<link href="bootstrap/css/bootstrap.css" rel="stylesheet">
	<link href="bootstrap/css/bootstrap-responsive.css" rel="stylesheet">
	
	<script src="bootstrap/js/jquery.js"></script>
	<script src="bootstrap/js/bootstrap-transition.js"></script>
	<script src="bootstrap/js/bootstrap-alert.js"></script>
	<script src="bootstrap/js/bootstrap-modal.js"></script>
	<script src="bootstrap/js/bootstrap-dropdown.js"></script>
	<script src="bootstrap/js/bootstrap-scrollspy.js"></script>
	<script src="bootstrap/js/bootstrap-tab.js"></script>
	<script src="bootstrap/js/bootstrap-tooltip.js"></script>
	<script src="bootstrap/js/bootstrap-popover.js"></script>
	<script src="bootstrap/js/bootstrap-button.js"></script>
	<script src="bootstrap/js/bootstrap-collapse.js"></script>
	<script src="bootstrap/js/bootstrap-carousel.js"></script>
	<script src="bootstrap/js/bootstrap-typeahead.js"></script>
	
	<script>
		$(document).ready(function(){
			$('body').fadeIn(500);
			
			$('#myModal').on('hidden', function () { $('#user').focus(); if($(location).attr('href').indexOf('?')>=0) window.location = $(location).attr('href').substring(0, $(location).attr('href').indexOf('?'));});
			$('#myModal').on( 'shown', function () { $('#closemodal').focus();});
			
			if( $('#errmsg').html()!="" ) $('#myModal').modal('show');
			else					      $('#user'   ).focus();
			
			$('#loading').hide();
			
			set_position();
			$(window).resize(set_position);
			
			
		});
		
		function set_position(){
			$('#cover').css('margin-top', ($( window ).height()-$('#cover').height())/2    );
			$('#dform').css('margin-top', ($("#cover").height()-$('#dform').height())/2+18 );
			$('#dform').css('padding-right', $("#cover").width()*0.18);
		}
		
		function check_authen(){
			user = $('#user').val();
			pass = $('#pass').val();
			subj = $('#subj').val();

			if( user=='' || pass=='' || subj=='' ) return false;
			
			$("#dform").fadeOut(500, function(){$('#login-from').hide();$('#loading').show();});
			$("#dform").fadeIn(250);
			
			
			$.ajax({
				type: 'POST',
				url: 'login-authen.php',
				data: {'user':user, 'pass':pass, 'subj':subj},
				success: function( msg ){
					if( msg.indexOf("Success")==0 ){
						$("body").fadeOut(500, function(){ window.location = "./header.php"; });
					}
					else{
						$('#errmsg').html( msg );
						$('#myModal').modal('show');
						$("#dform").fadeOut(500, function(){$('#login-from').show();$('#loading').hide();});
						$("#dform").fadeIn(250);
					}
				}
			})
			return false;
		}
	</script>
</head>
<body style="background-color: #222; display: none">
	<div class="modal hide fade" id="myModal" style="display: none;">
		<div class="modal-header">
			<button class="close" data-dismiss="modal" id="closemodal">x</button>
			<h3>Error</h3>
		</div>
		<div class="modal-body">
			<p><br/><br/><br/><center><b><span id="errmsg"><?php if($_GET['error']){ echo $_GET['error']; unset($_GET['error']); } ?></span></b></center><br/><br/><br/><br/><br/></p>
		</div>
	</div>
	<div id="cover" style="background: url(images/firstpage.jpg) no-repeat; background-size: 100% 100%; background-repeat:norepeat; width: 100%; height: 300px;">
		<div id="dform" style="padding-right: 220px;  float: right; text-align:right;">
			<img id="loading" src="./images/loading-login.gif" style="margin-top:-10px; padding-right:130px; max-height:40%; opacity:0.5;"/>
			<form id="login-from" method="post" name="form" onsubmit="return check_authen();">
				<b>username : </b><input id="user" type="text"     placeholder="your student code."/><br/>
				<b>password : </b><input id="pass" type="password" placeholder="password today!"   /><br/>
				<?php select_subject(); ?></br>
				<input id="ok" type="submit" class="btn btn-inverse" style="visibility: hidde; height:22px; margin: 0 0 0 0; padding-top: 0; vertical-align : middle;" value="  login  "/>
			</form>
		</div>
		
	</div>
</body>
</html>
