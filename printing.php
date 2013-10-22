<?php
include_once 'z-config.php';
include_once 'z-db.php';
include_once 'z-util.php';

checkauthen();
//checkauthorize( USERTYPE_ADMIN );
?>
<?php
function printing($user, $code, $type){
    $subj_info = getsubjectinfo();
    $printer   = $subj_info['printer_name']; 
    if($printer=="") return "Not found printer service.";
    
    $msg = "";
    connect_db();
    mysql("LOCK TABLE user_info READ");
    $res = mysql("SELECT seat FROM user_info WHERE user_id=\"$user\"");
    if(($info=mysql_fetch_assoc($res)) || $type=USERTYPE_ADMIN){
        $seat = $info['seat'];
        
        $content  = "***************************************************\r\n";
        $content .= "*    USER: $id                          ($seat)\r\n";
        $content .= "***************************************************\r\n";
        
        $fcode = fopen($code, "r");
            for( $line=1 ; !feof($fcode) ; $line++ ){
                $buffer = fgets($fcode, 1000);
                $content .= sprintf("%3d: %s\r",$line,$buffer);
            }
        fclose($fcode);
        
        $code = tempnam("", "");
        $fcode = fopen($code, "w");
            fputs($fcode, $content);
        fclose($fcode);
        
        $cmd = "print /d:$printer $code";
        $res = exec($cmd);
        $msg = $res;
    }
    else{
        $msg = "Not found you in this subject please login again.";
    }
    mysql("UNLOCK TABLES");
    close_db();
    return $msg;
}
?>
<?php
global $msg;
$msg = "";

$user = $_SESSION['user'];
$type = $_SESSION['type'];
$code = $_FILES['code']['tmp_name'];
$size = $_FILES['code']['size'];

     if($size<=0     ) $msg = "File size is zero, please choose file again.";
else if($size>=100000) $msg = "File too large, please choose file again.";
else                   $msg = printing($user, $code, $type);

echo "<script>window.top.window.recieve_print(\"$msg\");</script>";
?>