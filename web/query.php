<!DOCTYPE html>
<html lang="zh-CN">
<?php require_once 'header.php'?>

  <body>
    <?php require_once 'nav.php'?>
    <div class="w"> 
      <span class="aera">
        <h4>按车次查询</h4> 
        <form action="php/query.php" method="get" accept-charset="utf-8" class="form">
        <ul>
          <li><label>车次号：</label><input type="text"name="trainno"required="required"maxlength=6>
          <span class="small">不区分大小写</span></li>
          <li><label>始发日期：</label><input type="date"name="day"id="day" required="required">
          <span class="small"></span></li>
        </ul>
        <input type="submit"value="查询"name="querytrain" class="botton">
        </form>
        <h4 id="searchcity">按地点查询</h4>
        <form action="php/query.php" method="get" accept-charset="utf-8" class="form">
        <ul>
          <li><label>出发地：</label><input type="text"name="scity"id="scity"required="required"maxlength=20>
          <span class="small"></span></li>
          <li><label>到达地：</label><input type="text"name="ecity"id="ecity"required="required"maxlength=20>
          <span class="small"></span></li>
          <li><label>出发日期：</label><input type="date"name="sday"id="sday" required="required">
          <span class="small"></span></li>
          <li><label>出发时间：</label><input type="time"name="stime"id="stime" value="00:00" required="required">
          <span class="small"></span></li>
        </ul>
        <input type="submit"value="直达查询"name="querycity" class="botton">
        <br><br>
        <input type="submit"value="一次换乘查询"name="querytrans" class="botton">
        </form>
		  </span>
    </div>
    <script>
      var tomorrow = new Date(new Date().getTime()+(24+8)*60*60*1000);
      document.getElementById('day').valueAsDate = tomorrow;
  <?php
  if(!isset($_GET['scity']))
    echo <<<EOF
      document.getElementById('sday').valueAsDate = tomorrow;
EOF;
  else
    echo <<<EOF
      var nday = new Date("{$_GET['sday']}");
      document.getElementById('sday').valueAsDate=nday;
      document.getElementById('scity').value="{$_GET['scity']}";
      document.getElementById('ecity').value="{$_GET['ecity']}";
EOF;
  
  ?>
  </script>
    <?php require_once 'footer.php'?>
  </body>
</html>
