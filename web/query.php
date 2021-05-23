<!DOCTYPE html>
<html lang="zh-CN">
<?php require_once 'header.php'?>

  <body>
    <?php require_once 'nav.php'?>
    <div class="w"> 
      <span class="aera">
        <h4>按车次查询</h4>
        <form action="php/login.php" method="post" accept-charset="utf-8" class="form">
        <ul>
          <li><label>车次号：</label><input type="text"name="trainno"required="required"maxlength=6>
          <span class="small"></span></li>
          <li><label>始发日期：</label><input type="date"name="day"id="day" required="required">
          <span class="small"></span></li>
        </ul>
        <input type="submit"value="查询"name="querytrain"class="botton form">
        </form>
		  </span>
    </div>
    
    <?php require_once 'footer.php'?>
  </body>
  <script>
      document.getElementById('day').valueAsDate = new Date(new Date().getTime()+24*60*60*1000);
  </script>
</html>