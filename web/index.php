<!DOCTYPE html>
<html lang="zh-CN">
<?php require_once 'header.php'?>
  <body>
    <?php require_once 'nav.php'?>
    <div class="center w">
        <h3>欢迎使用12307订票系统</h3>
        <?php 
            if(isset($_SESSION['usr'])){
              echo "欢迎您，{$_SESSION['usr'][4]}<br><br>";
              if($_SESSION['usr'][1]=='admin')
                echo "<button onclick =\"location='./admin.php'\">管理入口</button><br>";
              //else 
                //echo "<img src=\"/images/baoxian1.png\">";
            }
            //else echo "<img src=\"/images/baoxian1.png\">";
        ?>
        <br><br><br><br><br><br><br><br><br><br><br><br>
    </div>
    
    <?php require_once 'footer.php'?>
  </body>
</html>
