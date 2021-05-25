<!DOCTYPE html>
<html lang="zh-CN">
<?php require_once 'header.php'?>
  <body>
    <?php require_once 'nav.php'?>
    <div class="w"> 
      <span class="aera">
        <h4>订单查询</h4>
        <form action="php/orders.php" method="get" accept-charset="utf-8" class="form">
        <ul>
          <li><label>从：</label><input type="date"name="sday"id="sday" required="required">
          <span class="small"></span></li>
          <li><label>到：</label><input type="date"name="eday"id="eday" required="required">
          <span class="small"></span></li>
        </ul>
        <input type="submit"value="查询"name="orders" class="botton">
        </form>
		  </span>
    </div>
    
    <script>
      var laskweek = new Date(new Date().getTime()-(24*7+8)*60*60*1000);
      var nextweek = new Date(new Date().getTime()+(24*7+8)*60*60*1000);
      document.getElementById('sday').valueAsDate = laskweek;
      document.getElementById('eday').valueAsDate = nextweek;
    </script>
    <?php require_once 'footer.php'?>
  </body>
</html>
