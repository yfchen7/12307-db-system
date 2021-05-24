<?php 
    if(!isset($_SESSION['usr'])){
        echo "<script>alert('请先登录');location.href = '/index.php';</script>";
        exit();
    }
?>