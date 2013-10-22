<?php
include_once 'z-config.php';
include_once 'z-db.php';
include_once 'z-util.php';

checkauthen();

?>
<?php
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
//<li><a href='./download-content.php?pathname=./data&filename=description.pdf'><i class='icon-file icon-white'></i> description.pdf</span></a></li>				
function list_superadmin_tools(){
	echo "
	<ul class='nav'>
		<li id='l1'><a onclick='load_content(\"   admin-main.php\",this)' href='javascript:void(0)'>Admin    </a></li>
		<li id='l2'><a onclick='load_content(\"    subj-main.php\",this)' href='javascript:void(0)'>Subject  </a></li>
		<li id='l3'><a onclick='load_content(\"   teach-main.php\",this)' href='javascript:void(0)'>Privilage</a></li>
	</ul>
	<ul class='nav pull-right'>
		<li><a href='./login-main.php'> logout </a></li>
	</ul>";
}
function list_admin_tools(){
	echo "
	<ul class='nav'>
		<li id='l1'><a onclick='load_content(\"submission-main.php\",this)' href='javascript:void(0)'>Main   </a></li>
		<li id='l2'><a onclick='load_content(\"      user-main.php\",this)' href='javascript:void(0)'>User   </a></li>
		<li id='l3'><a onclick='load_content(\"    status-main.php\",this)' href='javascript:void(0)'>Status </a></li>
		<li id='l4'><a onclick='load_content(\"	     prob-main.php\",this)' href='javascript:void(0)'>Problem</a></li>
		<li id='l5'><a onclick='load_content(\"	   result-main.php\",this)' href='javascript:void(0)'>Result </a></li>
		<li id='l6'><a onclick='load_content(\"	   grader-main.php\",this)' href='javascript:void(0)'>Grader </a></li>
		<li id='l7'><a onclick='load_content(\"    config-main.php\",this)' href='javascript:void(0)'>Config </a></li>
	</ul>
	<ul class='nav pull-right'>
		<li><a href='./download-grader.php'     ><u><i><font color='#00aaff'>>>Grader<<</font></i></u></a></li>
		<li><a href='./download-problem-set.php'>                            problem.zip              </a></li>
		<li><a href='./download-all-source.php '>                             source.zip              </a></li>
		<li><a href='./login-main.php'          >                              logout                 </a></li>
	</ul>";
}
function list_contestant_tools(){
	echo "
	<ul class='nav'>
		<li id='l1'><a onclick='load_content(\"submission-main.php\",this)' href='javascript:void(0)'>Main   </a></li>
		<li id='l2'><a onclick='load_content(\"	   result-main.php\",this)' href='javascript:void(0)'>Result </a></li>
	</ul>
	<ul class='nav pull-right'>
		<li><a href='./login-main.php'> logout </a></li>
	</ul>";
}
function list_supervisor_tools(){
	$result_name = $_SESSION['subj']."-result.xls";
	echo "
	<ul class='nav'>
		<li id='l1'><a onclick='load_content(\"submission-main.php\",this)' href='javascript:void(0)'>Main   </a></li>
		<li id='l2'><a onclick='load_content(\"	   result-main.php\",this)' href='javascript:void(0)'>Result </a></li>
	</ul>
	<ul class='nav pull-right'>
		<li><a href='./download-problem-set.php '> problem.zip</a></li>
		<li><a href='./download-all-source.php  '>  source.zip</a></li>
		<li><a href='./result-table.php?detail=1'>$result_name</a></li>
		<li><a href='./login-main.php           '>    logout  </a></li>
	</ul>";
}

?>

<html>
<head>
	<!--<META HTTP-EQUIV="Refresh" CONTENT="300">-->
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<link href="./bootstrap/css/bootstrap.css" rel="stylesheet">
    
	<link href="./bootstrap/css/colorpicker.css" rel="stylesheet">
	
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
	<script src="./bootstrap/js/bootstrap-colorpicker.js"></script>
	<script>
		$(document).ready(function(){
			$('#l1 a').click();
			set_position();
			$('body').fadeIn(500);
			setTimeout( function(){$('#loading').fadeOut(500);$('#content').fadeIn(500); $('#l1').addClass('active'); }, 300);
		});
		function set_position(){
			$('#loadimg').css('padding-top', ($( window ).height()-$('#loading').height())/2  );
		}
		function load_content( target, index ){
			$("#content").fadeOut(100);
			$("#loading").fadeIn(100);
			$('li[id^=l]').each(function(){ $(this).removeClass('active'); });
			$(index).parent().addClass('active');
			
			$.ajax({
				url: target,
				success: function( content ){
					$('#content').html( content );
					$(document).ready(function(){
						$('#content').fadeIn(500);
						$('#loading').fadeOut(500);
					});
				}
			});
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
				<a class="brand" href="#" onclick="location.reload();"><?php echo getname($_SESSION['id']); ?></a>
				<div class="nav-collapse collapse">
					<?php
						switch( $_SESSION['type'] ){
							case USERTYPE_SUPERADMIN : list_superadmin_tools(); break;
							case USERTYPE_ADMIN      : list_admin_tools();  	break;
							case USERTYPE_CONTESTANT : list_contestant_tools(); break;
							case USERTYPE_SUPERVISOR : list_supervisor_tools(); break;
						}
					?>
				</div>
			</div>
		</div>
    </div>
	<div id="loading" style="width:100%; height:100%; background: rgba(0, 0, 0, 0.8); position:absolute; z-index:1; display: non" align="center">
		<img id="loadimg" src="./images/loading-main.gif" style="height:30%; opacity: 0.5;"/>
	</div>
	<?php displaymessage(); ?>
	<div id="content" style="display: none; padding-top:100px;">
		
	</div>
</body>
</html>