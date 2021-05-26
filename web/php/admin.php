
<?php
include_once("utils.php");
 require_once '../header.php'?>
  <body>
    <?php require_once '../nav.php'?>
    <?php require_once '../checkadmin.php'?>
    <div class="center w">
      <?php 
        if(isset($_GET['hottrain'])) hottrain();
        else if(isset($_GET['openbuy'])) openbuy();
        else if(isset($_GET['alluser'])) alluser();
        else if(isset($_GET['buyday'])) buyday();
        ret_button();
      ?>
    </div>    
    <?php require_once '../footer.php'?>
  </body>
</html>

<?php
function alluser()
{
  $conn = mypg_connect();
	$sql = "select * from usr order by u_userid;";
	$ret = mypg_query($conn,$sql);
  echo "<table class=\"default-table\"border=\"1\"><tr>
  <th>id</th><th>用户名</th><th>手机号</th><th>身份证</th><th>真实姓名</th>
  <th>信用卡</th><th></th>
  </tr>";
  while($row = pg_fetch_row($ret)){
    echo "<tr>";
    for($i=0;$i<7;$i++){
      if($i==5) continue;
      echo "<td>$row[$i]</td>";
    }
    echo "<td><button onclick =\"location='orders.php?userid=$row[0]'\")>查看订单</button></td>";
    echo "</tr>";
  }
  echo "</table>";
}

function hottrain()
{
  $conn = mypg_connect();
	$sql = "
SELECT
  t_trainno,
  count(*) as sum
FROM
  train,
  orders
WHERE
  t_trainid=o_trainid and o_status='有效'
group by 
  t_trainno
order by 
  sum desc limit 10;
";
	$ret = mypg_query($conn,$sql);
  echo "<table class=\"default-table\"border=\"1\"><tr><th>热点车次</th><th>有效订单数</th></tr>";
  while($row = pg_fetch_row($ret)){
    echo "<tr><td>$row[0]</td><td>$row[1]</td</tr>";
  }
  echo "</table>";
}

function buyday()
{
  $conn = mypg_connect();
	$sql = "select * from runday order by r_day;";
	$ret = mypg_query($conn,$sql);
  echo "<table class=\"default-table\"border=\"1\"><tr><th>所有开放购票日期</th></tr>";
  while($row = pg_fetch_row($ret)){
    echo "<tr><td>$row[0]</td></tr>";
  }
  echo "</table>";
}

function openbuy()
{
  $fromday = ($_GET['fromday']);
  $conn = mypg_connect();
	$sql = "insert into runday values('$fromday');";
	$ret = pg_query($conn,$sql);
  if(!$ret) {
    echo "<script>alert('该日期已经开放过购票');history.go(-1);</script>";
    exit();
  }
  $sql = "
  INSERT INTO 
    seatleft(
      sl_day,
      sl_trainid,
      sl_stationid,
      sl_seattype,
      sl_seatleft
    )
SELECT
    '$fromday',
    p_trainid, 
    p_stationid, 
    p_seattype,
    5
FROM
    price;
  ";
  $ret = mypg_query($conn,$sql);
  echo "<script>alert('{$fromday}购票已开放');history.go(-1);</script>";
  exit();
}


?>