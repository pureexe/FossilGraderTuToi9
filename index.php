<?php
session_start();
if(file_exists('z-config.php')){
    echo"<script>window.location = 'login-main.php'; </script>";
}
else{
    $_SESSION['first-config'] = 'action';
    echo"<script>window.location = 'first-config-main.php';</script>";
}
?>
