<?php
include_once 'z-config.php';
include_once 'z-db.php';
include_once 'z-util.php';

checkauthen();
checkauthorize( USERTYPE_ADMIN );
?>
<?php
function update(){	
	connect_db(MASTER_TABLE);
	mysql_query("LOCK TABLES subj_info WRITE");
	$subj = $_SESSION['subj'];
	$sets = $_POST['sets'];
	$sets = str_replace('\\"', '"',$sets);
	$sets = str_replace("\\'", "'",$sets);
	
	mysql_query("UPDATE subj_info SET $sets WHERE subj_id=\"$subj\"");
	$result = mysql_error();
	if($result=="")	echo"success";
	else			echo $result;	
	mysql_query("UNLOCK TABLES");
	close_db();
}
function display(){
	$subj_info = getsubjectinfo();
	$topics = array("content"=>"Ploblem content download","source"=>"Last <input id='max_source' type='text' placeholder='number' value='".$subj_info['max_source']."' style='width:60px; text-align:center; margin-top:6px;'/> source code submission download","header"=>"Check source code header","submit"=>"Submission avaliable (Gradding)","printer"=>"Printer <input id='printer_name' type='text' placeholder='address name [ex. -> \\\\172.27.1.1\\HP-laserJet]' value='".$subj_info['printer_name']."' style='width:300px; text-align:center; margin-top:6px;'/> usable","link"=>"Problem link bottom","secure"=>"Check IP login");
	echo"
		<br/>
		<br/>
		<table width='100%' style='align:center;'>";
		foreach($topics as $name=>$topic){
			echo"
			<tr>
				<td width='55%' align='right'><font color='#00aaaa'>$topic&nbsp&nbsp&nbsp</font></td>
				<td width='45%'>
					<div id=$name class='btn-group' data-toggle='buttons-radio' style='float:left;'>
						<button type='button' class='btn on  ".($subj_info[$name]=='ON'?'btn-info active':'  ')."' onclick='action_toggle(this);'>ON </button>
						<button type='button' class='btn off ".($subj_info[$name]=='ON'?'':'btn-danger active')."' onclick='action_toggle(this);'>OFF</button>
					</div>
				</td>
			</tr>
			<tr><td colspan='2'><br/></td></tr>";
		}
	echo"</table>";
}
?>
<?php
switch($_POST['mode']){
	case 'update': update(); break;
	default		 : display();
}
?>