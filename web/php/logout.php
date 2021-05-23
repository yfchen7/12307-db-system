<?php 
    session_start();
    unset($_SESSION['usr']);
    echo "<script>location.href = '../index.php'</script>";
    exit();
?>