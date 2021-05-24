<?php 
    if(!(isset($_SESSION['usr']) and $_SESSION['usr'][1]=='admin')){
        echo "<script>alert('没有权限访问');history.go(-1);</script>";
        exit();
    }
?>