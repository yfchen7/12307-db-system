
<?php
include_once("utils.php");
 require_once '../header.php'?>
  <body>
    <?php require_once '../nav.php'?>
    <?php require_once '../checklogin.php'?>
    <div class="center w">
      <?php 
        show_orders();
        ret_button();
      ?>
    </div>    
    <?php require_once '../footer.php'?>
  </body>
</html>

<?php

function show_orders()
{
  $conn = mypg_connect();
  $userid = "";
  if($_SESSION['usr'][1]=='admin' and isset($_GET['userid']))
    $userid = $_GET['userid'];
  else $userid = $_SESSION['usr'][0];
  echo "{$_GET['userid']}<br>";
  $sday = "1970-01-01";
  $eday = "2070-01-01";
  if(isset($_GET['sday'])) $sday = $_GET['sday'];
  if(isset($_GET['sday'])) $eday = $_GET['eday'];

	$sql = "
  SELECT  
    o_orderid,
    t_trainno,
    S1.s_name,
    S2.s_name,
    (o_travelday+o_departtime) as departtime,
    (o_travelday+SP2.sp_count-SP1.sp_count+o_arrivetime) as arrivetime, 
    o_seattype,
    o_price+5 as price,
    o_status
FROM 
    orders,
    stop as SP1,
    stop as SP2,
    train,
    station as S1,
    station as S2
WHERE
    o_userid='$userid' and
    o_travelday>='$sday' and
    o_travelday<='$eday' and
    o_trainid=t_trainid and
    SP1.sp_trainid=o_trainid and
    SP1.sp_stationid=o_departstation and
    SP2.sp_trainid=o_trainid and
    SP2.sp_stationid=o_arrivestation and
    S1.s_stationid=o_departstation and
    S2.s_stationid=o_arrivestation
    ;";
	$ret = mypg_query($conn,$sql);
  $row=pg_fetch_row($ret);
  echo "{$_SESSION['usr'][4]}的订单信息<br><br>";
  echo "<table class=\"default-table\"border=\"1\">";
  echo "<tr><th>订单号</th><th>车次</th><th>出发站</th><th>到达站</th><th>出发时间</th><th>到达时间</th>
  <th>座位类型</th><th>总票价</th><th>状态</th><th>&nbsp;&nbsp;&nbsp;</th>
  </tr>";
  while($row){
    echo "<tr>"; 
    for ($i=0;$i<sizeof($row);$i++){
      echo "<td>$row[$i]</td>";
    }
    if($row[8]=='有效')
    echo<<<EOF
    <td><button onclick=httpPost('delorder.php',{"orderid":"$row[0]"})>取消订单</button></td>
EOF;
    else echo "<td></td>";
    $row=pg_fetch_row($ret);
    echo"</tr>";
  }
  echo "</table>";
}


?>