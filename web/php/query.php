
<?php
include_once("utils.php");
 require_once '../header.php'?>
  <body>
    <?php require_once '../nav.php'?>
    <div class="center w">
		<h4>查询结果</h4>
      <?php 
        check_access("querytrain");
        querytrain();
        ret_botton();
      ?>
    </div>    
    <?php require_once '../footer.php'?>
  </body>
</html>

<?php

function querytrain()
{
	$trainno = strtoupper($_POST['trainno']);
	$day = $_POST['day'];
	$conn = mypg_connect();
	$sql = "
SELECT 
s_name, sp_arrivetime,sp_departtime, sp_count,
max(sl_seatleft) filter(where sl_seattype='硬座') as yz,
max(sl_seatleft) filter(where sl_seattype='软座') as rz,
max(sl_seatleft) filter(where sl_seattype='硬卧上') as yws,
max(sl_seatleft) filter(where sl_seattype='硬卧中') as ywz,
max(sl_seatleft) filter(where sl_seattype='硬卧下') as ywx,
max(sl_seatleft) filter(where sl_seattype='软卧上') as rws,
max(sl_seatleft) filter(where sl_seattype='软卧下') as rwx,
max(p_price) filter(where sl_seattype='硬座') as yz,
max(p_price) filter(where sl_seattype='软座') as rz,
max(p_price) filter(where sl_seattype='硬卧上') as yws,
max(p_price) filter(where sl_seattype='硬卧中') as ywz,
max(p_price) filter(where sl_seattype='硬卧下') as ywx,
max(p_price) filter(where sl_seattype='软卧上') as rws,
max(p_price) filter(where sl_seattype='软卧下') as rwx
FROM
station,train,price,stop,seatleft,runday,
(select min(sl_seatleft) from seatleft 
where s
WHERE
t_trainno = '$trainno' and
s_stationid = p_stationid and 
p_trainid = t_trainid and
sp_trainid = t_trainid and
sp_stationid = s_stationid and 
sl_stationid = s_stationid and
sl_trainid = t_trainid and
sl_seattype = p_seattype and
sl_day = r_day and 
r_day = '$day'
GROUP BY 
s_name, s_stationid,sp_arrivetime,sp_departtime, sp_count
ORDER BY
sp_count, sp_departtime;
";
	$ret = mypg_query($conn,$sql);
  echo "<table class=\"default-table\"border=\"1\">";
  echo "<tr><th>车站</th><th>到达时间</th><th>出发时间</th><th>过夜天数</th>
  <th>硬座</th><th>软座</th><th>硬卧上</th><th>硬卧中</th><th>硬卧下</th><th>软卧上</th><th>软卧下</th>
  <th>硬座</th><th>软座</th><th>硬卧上</th><th>硬卧中</th><th>硬卧下</th><th>软卧上</th><th>软卧下</th>
  </tr>";
  $all = pg_fetch_all($ret);
  if(empty($all)){
    echo "没有找到<br>您可以";
    echo "<a href=/php/buyday.php>查看所有开放购票日期</a>";
    return;
  }
  for($j=0;$j<sizeof($all);$j++){
    echo "<tr>";
    $row = $all[$j];
    for ($i=0;$i<sizeof($row);$i++){
      if(!isset($row[$i])) echo "<td>-</td>";
      else if($i>=11) echo "<td>¥$row[$i]</td>";
      else if($i>=4 and $i<=10 and $row[$i]>0) 
        echo "<td onclick=httpPost('confirm.php',
        {\"trainno\":\"$trainno\"},{\"sname\":\"$trainno\"},{\"sname\":\"$trainno\"}
        )>
          $row[$i]</td>";
      else echo "<td>$row[$i]</td>";
    }
    echo"</tr>";
  }
  echo "</table>";

}

?>