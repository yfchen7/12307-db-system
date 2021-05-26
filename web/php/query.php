
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
      else if(isset($_GET['querytrans'])) querytrans();
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

function querytrans()
{
  $scity = $_GET['scity'];
  $ecity = $_GET['ecity'];
	$sday = $_GET['sday'];
  $stime = $_GET['stime'];
	$conn = mypg_connect();
	$sql1 = "
  create view transfer_table (
    day1, day2,
    s1c, s2c, s3c, s4c,
    s1s, s2s, s3s, s4s,
    s1_dt, s2_at, s3_dt, s4_at,
	s1_id, s2_id, s3_id, s4_id, t1_id, t2_id
	) as
     SELECT
        R1.r_day,
        R2.r_day,
        SP1.sp_count,
        SP2.sp_count,
        SP3.sp_count,
        SP4.sp_count,
        SP1.sp_seq,
        SP2.sp_seq,
        SP3.sp_seq,
        SP4.sp_seq,
        SP1.sp_departtime,
        SP2.sp_arrivetime,
        SP3.sp_departtime,
        SP4.sp_arrivetime,
        S1.s_stationid,
        S2.s_stationid,
        S3.s_stationid,
        S4.s_stationid,
        T1.t_trainid,
        T2.t_trainid
    FROM
        city as C1,
        city as C2,
        city as C3,
        station as S1,
        station as S2,
        station as S3,
        station as S4,
        train as T1,
        train as T2,
        stop as SP1,
        stop as SP2,
        stop as SP3,
        stop as SP4,
        runday as R1,
        runday as R2
    WHERE
        -- A start
        C1.c_name='$scity' and
        S1.s_cityid=C1.c_cityid and
        -- B end
        C3.c_name='$ecity' and
        S4.s_cityid=C3.c_cityid and
        -- C transfer
        S2.s_cityid = C2.c_cityid and
        S3.s_cityid = C2.c_cityid and
        SP1.sp_stationid=S1.s_stationid and
        SP2.sp_stationid=S2.s_stationid and
        SP3.sp_stationid=S3.s_stationid and
        SP4.sp_stationid=S4.s_stationid and
        SP1.sp_departtime>'$stime' and
        SP1.sp_trainid=T1.t_trainid and
        SP2.sp_trainid=T1.t_trainid and
        SP3.sp_trainid = T2.t_trainid and
        SP4.sp_trainid = T2.t_trainid and
        T1.t_trainid != T2.t_trainid and
        (
            SP2.sp_count>SP1.sp_count
            or
            (
                SP2.sp_count=SP1.sp_count and
                SP1.sp_arrivetime<SP2.sp_arrivetime
            )
        )and
        (
            SP4.sp_count>SP3.sp_count
            or
            (
                SP4.sp_count=SP3.sp_count and
                SP3.sp_arrivetime<SP4.sp_arrivetime
            )
        )and
        -- date
        (
            (
                R1.r_day + SP1.sp_count = '$sday' and
                SP1.sp_arrivetime <= SP1.sp_departtime 
            )or(
                SP1.sp_arrivetime > SP1.sp_departtime and
                R1.r_day + SP1.sp_count + 1 = '$sday'
            )
        )
        and
        (
            (

                SP2.sp_stationid=SP3.sp_stationid and
                (
                    R2.r_day+SP3.sp_count+SP3.sp_departtime-
                    (R1.r_day+SP2.sp_count+SP2.sp_arrivetime)<=interval '4 hour' and
                    R2.r_day+SP3.sp_count+SP3.sp_departtime-
                    (R1.r_day+SP2.sp_count+SP2.sp_arrivetime)>=interval '1 hour'
                )
            )
            or
            (
                SP2.sp_stationid!=SP3.sp_stationid and
                (
                    R2.r_day+SP3.sp_count+SP3.sp_departtime-
                    (R1.r_day+SP2.sp_count+SP2.sp_arrivetime)<=interval '4 hour' and
                    R2.r_day+SP3.sp_count+SP3.sp_departtime-
                    (R1.r_day+SP2.sp_count+SP2.sp_arrivetime)>=interval '2 hour'
                )
            )
        )
    ORDER BY
        T1.t_trainid, 
        T2.t_trainid
;
  ";
  $sql2 = "
  create view tmp(
    day1, day2, c1,c2,c3,c4,
    seq1,seq2,seq3,seq4,
    dt1,at2,dt3,at4,
    t1,t2,s1,s2,s3,s4,
    p1,p2,seat1,seat2,
    total_price
)as
SELECT
    transfer_table.day1,
    transfer_table.day2,
    transfer_table.s1c,
    transfer_table.s2c,
    transfer_table.s3c,
    transfer_table.s4c,
    transfer_table.s1s,
    transfer_table.s2s,
    transfer_table.s3s,
    transfer_table.s4s,
    transfer_table.s1_dt,
    transfer_table.s2_at,
    transfer_table.s3_dt,
    transfer_table.s4_at,
    transfer_table.t1_id,
    transfer_table.t2_id,
    transfer_table.s1_id,
    transfer_table.s2_id,
    transfer_table.s3_id,
    transfer_table.s4_id,
    P2.p_price-P1.p_price as price1,
    P4.p_price-P3.p_price as price2,
    P1.p_seattype,
    P3.p_seattype,
    P2.p_price-P1.p_price+P4.p_price-P3.p_price as price
FROM
    price as P1,
    price as P2,
    price as P3,
    price as P4,
    transfer_table
WHERE
    P1.p_stationid=transfer_table.s1_id and
    P2.p_stationid=transfer_table.s2_id and
    P1.p_trainid=transfer_table.t1_id and
    P2.p_trainid=transfer_table.t1_id and
    p1.p_seattype = P2.p_seattype and 
    P3.p_stationid=transfer_table.s3_id and
    P4.p_stationid=transfer_table.s4_id and
    P3.p_trainid=transfer_table.t2_id and
    P4.p_trainid=transfer_table.t2_id and
    P3.p_seattype = P4.p_seattype
ORDER BY
    price
LIMIT 100
;
  ";
  $sql3 = "
  create view final(
    total_price,s1,s2,t1,dt1,at2,seat1,p1,sl1,s3,s4,t2,dt3,at4,seat2,p2,sl2,total_time
)as
SELECT
    tmp.total_price as price,
    S1.s_name as station1,
    S2.s_name as station2,
    T1.t_trainno as train1,
    tmp.dt1 as departtime1,
    tmp.at2 as arrivetime2,
    tmp.seat1 as seattype1,
    tmp.p1 as price1,
    min(SL1.sl_seatleft) as seatleft1,
    S3.s_name as station3,
    S4.s_name as station4,
    T2.t_trainno as train2,
    tmp.dt3 as departtime3,
    tmp.at4 as arrivetime4,
    tmp.seat2 as seattype2,
    tmp.p2 as price2,
    min(SL2.sl_seatleft) as seatleft2,
    ((tmp.day2+tmp.c4+tmp.at4)-(tmp.day1+tmp.c1+tmp.dt1)) as total_time
FROM
    station as S1,
    station as S2,
    station as S3,
    station as S4,
    train as T1,
    train as T2,
    stop as SP1,
    stop as SP2,
    seatleft as SL1,
    seatleft as SL2,
    runday,
    tmp
WHERE
    S1.s_stationid=tmp.s1 and
    S2.s_stationid=tmp.s2 and
    S3.s_stationid=tmp.s3 and
    S4.s_stationid=tmp.s4 and
    T1.t_trainid=tmp.t1 and
    T2.t_trainid=tmp.t2 and
    SL1.sl_trainid=tmp.t1 and
    SL2.sl_trainid=tmp.t2 and
    SL1.sl_stationid=SP1.sp_stationid and
    SP1.sp_trainid=tmp.t1 and 
    SP2.sp_trainid=tmp.t2 and 
    SL1.sl_day=tmp.day1 and
    SL2.sl_day=tmp.day2 and
    SP1.sp_seq>=tmp.seq1 and
    SP1.sp_seq<tmp.seq2 and
    SL2.sl_stationid=SP2.sp_stationid and
    SP2.sp_seq>=tmp.seq3 and
    SP2.sp_seq<tmp.seq4
GROUP BY
    day1, day2,
    price,
    station1,
    station2,
    train1,
    departtime1,
    arrivetime2,
    seattype1,
    price1,
    station3,
    station4,
    train2,
    departtime3,
    arrivetime4,
    seattype2,
    price2,
    total_time
ORDER BY
    price
;
  ";
  $sql4 = "
  
  "
}

?>