
<?php
include_once("utils.php");
 require_once '../header.php'?>
  <body>
    <?php require_once '../nav.php'?>
    <div class="center w">
		<h4>查询结果</h4>
      <?php 
      if(isset($_GET['querytrain'])) querytrain();
      else if(isset($_GET['querycity'])) querycity();
        ret_button();
      ?>
    </div>    
    <?php require_once '../footer.php'?>
  </body>
</html>

<?php

function querytrain()
{
	$trainno = strtoupper($_GET['trainno']);
	$day = $_GET['day'];
  echo "$trainno<br>$day<br>";
	$conn = mypg_connect();
	$sql = "
  with  t1(t1_seatleft,t1_stationid,t1_trainid,t1_seattype) as
  (select min(case when sp2.sp_seq=1 then null else sl_seatleft end),
  sp2.sp_stationid,t_trainid,sl_seattype
    from
      stop as sp1, stop as sp2, seatleft, train
    where 
      sp1.sp_trainid = sp2.sp_trainid and
      sp2.sp_trainid = t_trainid and
      t_trainno = '$trainno' and
      sp1.sp_stationid = sl_stationid and
      sl_day = '$day' and
      sl_trainid = t_trainid and
      (sp1.sp_seq<sp2.sp_seq or sp2.sp_seq=1)
    group BY
      sp2.sp_stationid,t_trainid,sl_seattype
    )
SELECT 
  sp_seq, s_name, sp_arrivetime,sp_departtime, sp_count,
  max(t1_seatleft) filter(where t1_seattype='硬座') as yz,
  max(t1_seatleft) filter(where t1_seattype='软座') as rz,
  max(t1_seatleft) filter(where t1_seattype='硬卧上') as yws,
  max(t1_seatleft) filter(where t1_seattype='硬卧中') as ywz,
  max(t1_seatleft) filter(where t1_seattype='硬卧下') as ywx,
  max(t1_seatleft) filter(where t1_seattype='软卧上') as rws,
  max(t1_seatleft) filter(where t1_seattype='软卧下') as rwx,
  max(p_price) filter(where t1_seattype='硬座') as yz,
  max(p_price) filter(where t1_seattype='软座') as rz,
  max(p_price) filter(where t1_seattype='硬卧上') as yws,
  max(p_price) filter(where t1_seattype='硬卧中') as ywz,
  max(p_price) filter(where t1_seattype='硬卧下') as ywx,
  max(p_price) filter(where t1_seattype='软卧上') as rws,
  max(p_price) filter(where t1_seattype='软卧下') as rwx
FROM
  station,price,stop,t1
WHERE
  t1_stationid = p_stationid and 
  s_stationid = t1_stationid and
  p_trainid = t1_trainid and
  sp_trainid = t1_trainid and
  sp_stationid = t1_stationid and 
  t1_seattype = p_seattype 
GROUP BY 
  sp_seq, s_name, t1_stationid,sp_arrivetime,sp_departtime, sp_count
ORDER BY
  sp_seq;
";
	$ret = mypg_query($conn,$sql);
  $row=pg_fetch_row($ret);
  if(empty($row)){
    echo "没有找到<br>您可以";
    echo "<a href=/php/buyday.php>查看所有开放购票日期</a>";
    return;
  }
  echo "注：余票为从始发站到当前站的余票，点击余票的数字可以进入购买页面<br><br>";
  echo "<table class=\"default-table\"border=\"1\">";
  echo "<tr><th>序号</th><th>车站</th><th>到达时间</th><th>出发时间</th><th>过夜天数</th>
  <th>硬座</th><th>软座</th><th>硬卧上</th><th>硬卧中</th><th>硬卧下</th><th>软卧上</th><th>软卧下</th>
  <th>硬座</th><th>软座</th><th>硬卧上</th><th>硬卧中</th><th>硬卧下</th><th>软卧上</th><th>软卧下</th>
  </tr>";
  while($row){
    if($row[0]==1) {
      $sname=$row[1]; $stime=$row[3]; 
    }
    echo "<tr>"; 
    for ($i=0;$i<sizeof($row);$i++){
      if(!isset($row[$i])) echo "<td>-</td>";
      else if($i>=12) echo "<td>¥$row[$i]</td>";
      else if($i>=5 and $i<=11 and $row[$i]>0) {
        $j = $i+7; 
        $seattype = $i-5;
        $str =<<<EOF
        <td>
        <a onclick=httpPost('confirm.php',{"trainno":"$trainno","sname":"$sname","sday":"$day","stime":"$stime","ename":"$row[1]","count":"$row[4]","etime":"$row[2]","seattype":$seattype,"price":"$row[$j]"}) href='#'>
          $row[$i]</a></td>
EOF;
        echo $str;
      }
      else echo "<td>$row[$i]</td>";
    }
    $row=pg_fetch_row($ret);
    echo"</tr>";
  }
  echo "</table>";

}


function querycity()
{
  $scity = $_GET['scity'];
  $ecity = $_GET['ecity'];
	$sday = $_GET['sday'];
  $stime = $_GET['stime'];
	$conn = mypg_connect();
	$sql = "
  SELECT
  t_trainno,
  S1.s_name,--始发站
  S2.s_name,--终点站
  SL.departtime,--从始发站离开时间
  SL.arrivetime,--到达终点站时间
  (to_date('1970-01-01','yyyy-MM-dd')+SL.count2-SL.count1+SL.arrivetime-SL.departtime) as total_time,
  max(SL.seatleft) filter(where SL.seattype='硬座') as yz_left,
  max(SL.seatleft) filter(where SL.seattype='软座') as rz_left,
  max(SL.seatleft) filter(where SL.seattype='硬卧上') as yws_left,
  max(SL.seatleft) filter(where SL.seattype='硬卧中') as ywz_left,
  max(SL.seatleft) filter(where SL.seattype='硬卧下') as ywx_left,
  max(SL.seatleft) filter(where SL.seattype='软卧上') as rws_left,
  max(SL.seatleft) filter(where SL.seattype='软卧下') as rwx_left,
  max(P2.p_price-P1.p_price) filter(where P1.p_trainid=P2.p_trainid and P1.p_seattype=P2.p_seattype and P1.p_seattype='硬座') as yz_price,
  max(P2.p_price-P1.p_price) filter(where P1.p_trainid=P2.p_trainid and P1.p_seattype=P2.p_seattype and P1.p_seattype='软座') as rz_price,
  max(P2.p_price-P1.p_price) filter(where P1.p_trainid=P2.p_trainid and P1.p_seattype=P2.p_seattype and P1.p_seattype='硬卧上') as yws_price,
  max(P2.p_price-P1.p_price) filter(where P1.p_trainid=P2.p_trainid and P1.p_seattype=P2.p_seattype and P1.p_seattype='硬卧中') as ywz_price,
  max(P2.p_price-P1.p_price) filter(where P1.p_trainid=P2.p_trainid and P1.p_seattype=P2.p_seattype and P1.p_seattype='硬卧下') as ywx_price,
  max(P2.p_price-P1.p_price) filter(where P1.p_trainid=P2.p_trainid and P1.p_seattype=P2.p_seattype and P1.p_seattype='软卧上') as rws_price,
  max(P2.p_price-P1.p_price) filter(where P1.p_trainid=P2.p_trainid and P1.p_seattype=P2.p_seattype and P1.p_seattype='软卧下') as rwx_price,
  min(P2.p_price-P1.p_price) filter(where P1.p_trainid=P2.p_trainid and P1.p_seattype=P2.p_seattype and SL.seatleft>0) as price
FROM    
  station as S1,
  station as S2,
  train,
  price as P1,
  price as P2,
  (
      SELECT
          ST1.sp_stationid as departstation,
          ST3.sp_stationid as arrivestation,
          sl_trainid as trainid,
          sl_seattype as seattype,
          min(sl_seatleft) as seatleft,
          ST1.sp_departtime as departtime,
          ST3.sp_arrivetime as arrivetime,
          ST1.sp_count as count1,
          ST3.sp_count as count2
      FROM
          seatleft,
          stop as ST1,
          stop as ST2,
          stop as ST3,
          city as CI1,
          city as CI2,
          station as SI1,
          station as SI2
      WHERE
          CI1.c_name='$scity' and
          CI2.c_name='$ecity' and
          CI1.c_cityid=SI1.s_cityid and
          CI2.c_cityid=SI2.s_cityid and
          ST1.sp_stationid=SI1.s_stationid and
          ST3.sp_stationid=SI2.s_stationid and
          sl_day+ST1.sp_count='$sday' and
          ST1.sp_trainid=ST3.sp_trainid and
          ST2.sp_trainid = ST3.sp_trainid and
          ST1.sp_departtime >= '$stime' and
          sl_trainid=ST1.sp_trainid and
          sl_stationid=ST2.sp_stationid and
          (
              ST2.sp_count>ST1.sp_count
              or
              (
                  ST2.sp_count=ST1.sp_count and
                  ST1.sp_arrivetime<=ST2.sp_arrivetime
              )
          )and
          (
              ST3.sp_count>ST2.sp_count
              or
              (
                  ST3.sp_count=ST2.sp_count and
                  ST2.sp_arrivetime<ST3.sp_arrivetime
              )
          )
      GROUP BY
          departstation,
          arrivestation,
          trainid,
          seattype,
          departtime,
          arrivetime,
          count1,
          count2
  )as SL
WHERE 
  S1.s_stationid=SL.departstation and
  S2.s_stationid=SL.arrivestation and
  SL.seattype=P2.p_seattype and
  P1.p_seattype=P2.p_seattype and
  P1.p_stationid=SL.departstation and
  P2.p_stationid=SL.arrivestation and
  P1.p_trainid=SL.trainid and
  P2.p_trainid=SL.trainid and
  t_trainid=SL.trainid 
GROUP BY
  S1.s_name,--始发站
  S2.s_name,--终点站
  t_trainno,
  SL.departtime,--从始发站离开时间
  SL.arrivetime,--到达终点站时间
  SL.count1,
  SL.count2
ORDER BY
   price;
  ";
  $ret = mypg_query($conn,$sql);
  $row=pg_fetch_row($ret);
  if(empty($row)){
    echo "没有找到<br>您可以";
    echo "<a href=/php/buyday.php>查看所有开放购票日期</a>";
    return;
  }

  echo "<table class=\"default-table\"border=\"1\">";
  echo "<tr><th>车次</th><th>出发站</th><th>到达站</th><th>出发时间</th><th>到达时间</th><th>总耗时</th>
  <th>硬座</th><th>软座</th><th>硬卧上</th><th>硬卧中</th><th>硬卧下</th><th>软卧上</th><th>软卧下</th>
  <th>硬座</th><th>软座</th><th>硬卧上</th><th>硬卧中</th><th>硬卧下</th><th>软卧上</th><th>软卧下</th>
  <th>最低价格</th></tr>";
  while($row){
    echo "<tr>"; 
    $total = strtotime($row[5]);
    $d = intval(date("d",$total)) - 1;
    $h = date("h时i分",$total);
    $row[5] = $d>0? "{$d}天"."$h":"$h";
    $row[0] = trim($row[0]);
    $row[3] = date("h:i",strtotime($row[3]));
    $row[4] = date("h:i",strtotime($row[4]));
    for ($i=0;$i<sizeof($row);$i++){
      if(!isset($row[$i])) echo "<td>-</td>";
      else if($i>=13) echo "<td>¥$row[$i]</td>";
      else if($i>=6 and $i<=12 and $row[$i]>0) {
        $j = $i+7; 
        $seattype = $i-6;
        $str =<<<EOF
        <td><a onclick=httpPost('confirm.php',{"trainno":"$row[0]","sname":"$row[1]","sday":"$sday","stime":"$row[3]","ename":"$row[2]","count":"$d","etime":"$row[4]","seattype":"$seattype","price":"$row[$j]"}) href='#'>
        $row[$i]</a></td>
EOF;
        echo "$str";
      }
      else echo "<td>$row[$i]</td>";
    }
    $row=pg_fetch_row($ret);
    echo"</tr>";
  }
  echo "</table>";
  $nextday = date("Y-m-d",strtotime("+1 day",strtotime($sday)));
  echo $sday, $nextday;
  queryreturn($ecity,$scity,$nextday);
}

function queryreturn($scity,$ecity,$sday)
{
  echo <<<EOF
  <br><button onclick ="location='/query.php?scity=$scity&ecity=$ecity&sday=$sday'")>返程查询</button><br>
EOF;
}

?>