<!DOCTYPE html>
<html lang="zh-CN">
<?php require_once 'header.php'?>
  <body>
    <?php require_once 'nav.php'?>
    <div class="center w">
        welcome to 12307 book system
        <a href="login.php">test<br></a>
        <?php 
            if(isset($_SESSION['usr']) and $_SESSION['usr'][1]=='admin')
              echo "欢迎您，管理员<br><a href=\"./admin.php\">管理入口</a>";
        ?>
    </div>
    
    <?php require_once 'footer.php'?>
  </body>
</html>