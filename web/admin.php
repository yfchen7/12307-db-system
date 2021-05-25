<!DOCTYPE html>
<html lang="zh-CN">

<?php require_once 'header.php'?>
  <body>
    <?php require_once 'nav.php'?>
    <?php require_once 'checkadmin.php'?>
    <div class="center w">
      欢迎您，管理员<br>
      <span class="aera">
        <h4>总体情况</h4>
        总订单数： <span id=totalorder></span> <br><br>
        总票价：   <span id=totalprice></span>
        <h4>开放购票日期</h4>
        <form action="php/admin.php" method="get" accept-charset="utf-8" class="form">
          <ul><li><label>日期：</label><input type="date"name="fromday"id="fromday" required="required"></ul>
          <!-- <ul><li><label>到：</label><input type="date"name="today"id="today" required="required"></ul> -->
          <input type="submit"value="开放购票"name="openbuy" onclick="document.getElementById('wait').innerHTML='请稍等'">
          <span id="wait"></span>
        </form>
        <form action="php/admin.php" method="get">
          <br><input type="submit"value="查看开放购票日期"name="buyday">
        </form>
        <h4>热点车次</h4>
        <form action="php/admin.php" method="get" accept-charset="utf-8" class="form">
          <input type="submit"value="查看"name="hottrain">
        </form>
        <h4>注册用户列表及订单</h4>
        <form action="php/admin.php" method="get" accept-charset="utf-8" class="form">
          <input type="submit"value="查看"name="alluser">
        </form>
        
		  </span>
    </div>    
    <?php setTotal(); require_once 'footer.php'?>
  </body>
</html>

<?php
function setTotal()
{
  include_once("php/utils.php");
  $conn = mypg_connect();
  $sql = "select count(*) from orders;";
  $ret = mypg_query($conn,$sql);
  $row = pg_fetch_row($ret);
  $totalorder = $row[0];
  $sql = "select sum(o_price) from orders;";
  $ret = mypg_query($conn,$sql);
  $row = pg_fetch_row($ret);
  $totalprice = empty($row[0]) ? 0 : $row[0];
  echo "<script>document.getElementById(\"totalorder\").innerHTML={$totalorder};</script>";
  echo "<script>document.getElementById(\"totalprice\").innerHTML={$totalprice};</script>";
}
?>