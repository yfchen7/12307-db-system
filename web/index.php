<!DOCTYPE html>
<html lang="zh-CN">
<?php require_once 'header.php'?>
  <body>
    <?php require_once 'nav.php'?>
    <div class="center w">
        welcome to 12307 book system
        <button onclick ="location='php/orders.php?userid=1'")>test</button>
        <?php 
            if(isset($_SESSION['usr'])){
              echo "欢迎您，{$_SESSION['usr'][1]}<br>";
              if($_SESSION['usr'][1]=='admin')
                echo "<a href=\"./admin.php\">管理入口</a>";
            }
        ?>
    </div>
    
    <?php require_once 'footer.php'?>
  </body>
</html>
