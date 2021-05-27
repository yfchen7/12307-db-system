
<?php
//为什么这一页代码写得这么烂。因为这是ddl最后一天的凌晨三点，我开始学习javascript和php。
include_once("utils.php");
 require_once '../header.php'?>
  <body>
    <?php require_once '../nav.php'?>
    <div class="center w">
		<!-- <h4>查询结果</h4> -->
      <?php 
      if(isset($_GET['querytrain'])) querytrain();
      else if(isset($_GET['querycity'])) querycity();
      else if(isset($_GET['querytrans'])) querytrans();
      ret_button();
      ?>
    </div>    
    <script>
  function sendpost(id,sday,count,stime,etime,sday1,count1,stime1,etime1)
  {
    
    var trainno = document.getElementById(id*100).innerHTML;
    var sname = document.getElementById(id*100+1).innerHTML;
    var ename = document.getElementById(id*100+2).innerHTML;
    var trainno1 = document.getElementById(id*100+21).innerHTML;
    var sname1 = document.getElementById(id*100+22).innerHTML;
    var ename1 = document.getElementById(id*100+23).innerHTML;
    var name1 = id;
    var name2 = id+100;
    var st1,st2,p1,p2;
    var temp = document.getElementsByName(name1);
    var found1=0, found2=0;
    for (var i=0, length = temp.length; i < length; i++) {
        if (temp[i].checked) {
            st1=parseInt(temp[i].value);
            p1=document.getElementById(id*100+12+st1).innerHTML.substring(1);
            found1=1;break;
        }
    }
    var temp1 = document.getElementsByName(name2);
    for (var i=0, length = temp1.length; i < length; i++) {
        if (temp1[i].checked) {
            st2=parseInt(temp1[i].value);
            p2=document.getElementById(id*100+33+st2).innerHTML.substring(1);
            found2=1;break;
        }
    }
    var param = {
      trainno:trainno,
      sname:sname,
      stime:stime,
      sday:sday,
      count:count,
      ename:ename,
      etime:etime,
      seattype:st1,
      price:p1,
      trainno1:trainno1,
      sname1:sname1,
      stime1:stime1,
      sday1:sday1,
      count1:count1,
      ename1:ename1,
      etime1:etime1,
      seattype1:st2,
      price1:p2
    };
    if(found1==1 && found2==1)
      httpPost("confirm.php", param);
    else
      alert("请先选择座位类型");

  }
</script>
    <?php require_once '../footer.php'?>
  </body>
</html>

<?php

function querytrain()
{
	$trainno = strtoupper($_GET['trainno']);
  if(!preg_match("/^[a-zA-Z0-9]{1,6}$/",$trainno)) fails("格式有误");
	$day = $_GET['day'];
  echo "<h4>$trainno 的查询结果</h4>$day<br>";
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
    echo "<a href=/php/buyday.php>查看所有发车日期</a>";
    return;
  }
  echo "<p class='small'>注：余票为从始发站到当前站的余票，点击余票的数字可以进入购买页面</p>";
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
  to_timestamp('$sday','yyyy-MM-dd')+SL.departtime,--从出发站离开日期时间
  SL.slday + SL.count2 + SL.arrivetime,
  (case when SL.departtime>=SL.arrivetime0
        then to_date('2020-9-1','yyyy-MM-dd')+SL.count2-SL.count1+SL.arrivetime-SL.departtime-to_timestamp('2020-9-1 00:00:00','yyyy-MM-dd hh24:mi:ss')
        else to_date('2020-9-1','yyyy-MM-dd')+SL.count2-SL.count1-1+SL.arrivetime-SL.departtime-to_timestamp('2020-9-1 00:00:00','yyyy-MM-dd hh24:mi:ss')
        end) 
    as total_time, 
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
          ST1.sp_arrivetime as arrivetime0,
          ST3.sp_arrivetime as arrivetime,
          ST1.sp_count as count1,
          ST3.sp_count as count2,
          sl_day as slday
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
          ST1.sp_trainid=ST3.sp_trainid and
          ST2.sp_trainid = ST3.sp_trainid and
          ST1.sp_departtime >= '$stime' and
          sl_trainid=ST1.sp_trainid and
          sl_stationid=ST2.sp_stationid and
          (
            (
              ST1.sp_departtime>=ST1.sp_arrivetime and
              '$sday'=ST1.sp_count+sl_day
            )
              or
            (
              ST1.sp_departtime<ST1.sp_arrivetime and
              '$sday'=ST1.sp_count+sl_day+1
            )
          )
          and 
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
          arrivetime0,
          arrivetime,
          count1,
          count2,
          slday
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
  SL.arrivetime0,
  SL.arrivetime,--到达终点站时间
  SL.count1,
  SL.count2,
  SL.slday
ORDER BY
   price,total_time,SL.departtime
limit 10;
  ";
  $ret = mypg_query($conn,$sql);
  $row=pg_fetch_row($ret);
  echo "<h4>$scity-$ecity 的查询结果</h4>出发日期: $sday<br>";
  if(empty($row)){
    echo "没有找到<br>您可以";
    echo "<a href=/php/buyday.php>查看所有发车日期</a>";
    return;
  }
  echo "<p class='small'>注：点击余票的数字可以进入购买页面</p>";
  echo "<table class=\"default-table\"border=\"1\">";
  echo "<tr><th>车次</th><th>出发站</th><th>到达站</th><th>出发时间</th><th>到达时间</th><th>总耗时</th>
  <th>硬座</th><th>软座</th><th>硬卧上</th><th>硬卧中</th><th>硬卧下</th><th>软卧上</th><th>软卧下</th>
  <th>硬座</th><th>软座</th><th>硬卧上</th><th>硬卧中</th><th>硬卧下</th><th>软卧上</th><th>软卧下</th>
  <th>最低价格</th></tr>";
  while($row){
    echo "<tr>"; 
    $row[0] = trim($row[0]);
    $count = intval(date("d",strtotime($row[4])))-intval(date("d",strtotime($row[3])));
    $endhour = date("H:i",strtotime($row[4]));
    $row[3] = date("H:i",strtotime($row[3]));
    $total_tmp = strtotime("$row[5]",'1970-01-01');
    $total_day = intval(date("d",$total_tmp))-1;
    $total_hour = date("H时i分",$total_tmp);
    $row[5] = $total_hour;
    if($total_day>0) $row[5] = "{$total_day}天".$row[5];

    $row[4] = $endhour;
    if($count>0) $row[4] = $row[4]."<br>+{$count}天";
    for ($i=0;$i<sizeof($row);$i++){
      if(!isset($row[$i])) echo "<td>-</td>";
      else if($i>=13) echo "<td>¥$row[$i]</td>";
      else if($i>=6 and $i<=12 and $row[$i]>0) {
        $j = $i+7; 
        $seattype = $i-6;
        $str =<<<EOF
        <td><a onclick=httpPost('confirm.php',{"trainno":"$row[0]","sname":"$row[1]","sday":"$sday","stime":"$row[3]","ename":"$row[2]","count":"$count","etime":"$endhour","seattype":"$seattype","price":"$row[$j]"}) href='#'>
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
  $sql = <<<EOF
  -- 第一步选出所有的换成可能
  with transfer_table (
      day1, day2,
      s1c, s2c, s3c, s4c,
      s1s, s2s, s3s, s4s,
      s1_at, s1_dt, s2_at, 
      s3_at, s3_dt, s4_at,
    s1_id, s2_id, s3_id, s4_id, t1_id, t2_id
    ) as
      (
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
          SP1.sp_arrivetime,
          SP1.sp_departtime,
          SP2.sp_arrivetime,
          SP3.sp_arrivetime,
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
      ),
  -- 第二步计算换乘的总价格
   tmp(
      day1, day2, c1,c2,c3,c4,
      seq1,seq2,seq3,seq4,
      at1, dt1, at2,
      at3, dt3, at4,
      t1,t2,s1,s2,s3,s4,
      p1,p2,seat1,seat2,
      total_price
  )as
  (
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
      transfer_table.s1_at,
      transfer_table.s1_dt,
      transfer_table.s2_at,
      transfer_table.s3_at,
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
  LIMIT 160
  ),
  -- 第三步查询是否有余票，计算总时间
  final(
      total_price,d1,d2,
      s1,s2,t1,
      at1,dt1,at2,seat1,p1,sl1,
      s3,s4,t2,
      dt3,at4,seat2,p2,sl2,total_time
  )as
  (
  SELECT
      tmp.total_price as price,
      tmp.day1 as d1,
      tmp.day2 as d2,
      S1.s_name as station1,
      S2.s_name as station2,
      T1.t_trainno as train1,
      tmp.at1 as arrivetime1,
      to_date('$sday', 'yyyy-MM-dd') + tmp.dt1 as departtime1,
      tmp.day1 + tmp.c2 + tmp.at2 as arrivetime2,
      tmp.seat1 as seattype1,
      tmp.p1 as price1,
      min(SL1.sl_seatleft) as seatleft1,
      S3.s_name as station3,
      S4.s_name as station4,
      T2.t_trainno as train2,
  
      (case when tmp.at3<=tmp.dt3 then tmp.day2 + tmp.c3 + tmp.dt3
            else tmp.day2 + 1 + tmp.c3 + tmp.dt3 end)  as departtime3,
      tmp.day2 + tmp.c4 + tmp.at4 as arrivetime4,
      tmp.seat2 as seattype2,
      tmp.p2 as price2,
      min(SL2.sl_seatleft) as seatleft2,
      (case when tmp.at1<=tmp.dt1 then (tmp.day2+tmp.c4+tmp.at4)-(tmp.day1+tmp.c1+tmp.dt1)
            else (tmp.day2+tmp.c4+tmp.at4)-(tmp.day1+tmp.c1+1+tmp.dt1) end) as total_time
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
      arrivetime1,
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
  )
  ,
  
  SNLT(
      d1,d2,
      s1,s2,t1,
      dt1,at2,
      s3,s4,t2,
      dt3,at4,total_time,
      total_price
  )as
  (
  SELECT
      d1,d2,
      s1,s2,t1,
      dt1,at2,
      s3,s4,t2,
      dt3,at4,total_time,
      min(final.total_price) filter(where final.sl1>0 and final.sl2>0) as price
      
  FROM 
      -- NLT
      final
  GROUP BY
      d1,d2,
      s1,s2,t1,
      dt1,at2,
      s3,s4,t2,
      dt3,at4,total_time
  ORDER BY
      price
  LIMIT 10
  ),
  -- 第四步对上面的组合进一步查询两次换乘车辆的详细信息
  LT(
      s1,s2,t1,seat1,dt1,at2,sl1,s3,s4,t2,seat2,dt3,at4,sl2,p1,p2,price,total_time
  )as
  (
  SELECT
      
      SNLT.s1,
      SNLT.s2,
      SNLT.t1,
      SL1.sl_seattype as seattype1,
      SNLT.dt1 as dptime1,
      SNLT.at2,
      min(SL1.sl_seatleft) as seatleft1,
      SNLT.s3,
      SNLT.s4,
      SNLT.t2,
      SL2.sl_seattype as seattype2,
      SNLT.dt3,
      SNLT.at4,
      min(SL2.sl_seatleft) as seatleft2,
      P2.p_price - P1.p_price as price1,
      P4.p_price - P3.p_price as price2,
      SNLT.total_price as price,
      SNLT.total_time as total_time
  FROM
      SNLT,
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
      stop as SP5,
      stop as SP6,
      seatleft as SL1,
      seatleft as SL2,
      price as P1,
      price as P2,
      price as P3,
      price as P4
  WHERE
      S1.s_name = SNLT.s1 and
      S2.s_name = SNLT.s2 and
      S3.s_name = SNLT.s3 and
      S4.s_name = SNLT.s4 and
      T1.t_trainno = SNLT.t1 and
      T2.t_trainno = SNLT.t2 and
      SL1.sl_day = SNLT.d1 and
      SL2.sl_day = SNLT.d2 and
      SP1.sp_trainid = T1.t_trainid and
      SP2.sp_trainid = T1.t_trainid and
      SP3.sp_trainid = T1.t_trainid and
      SP4.sp_trainid = T2.t_trainid and
      SP5.sp_trainid = T2.t_trainid and
      SP6.sp_trainid = T2.t_trainid and
      SP1.sp_stationid = S1.s_stationid and
      SP2.sp_stationid = SL1.sl_stationid and
      SP3.sp_stationid = S2.s_stationid and
      SP4.sp_stationid = S3.s_stationid and
      SP5.sp_stationid = SL2.sl_stationid and
      SP6.sp_stationid = S4.s_stationid and
      SL1.sl_trainid = T1.t_trainid and
      SL2.sl_trainid = T2.t_trainid and
      P1.p_trainid = T1.t_trainid and
      P2.p_trainid = T1.t_trainid and
      P3.p_trainid = T2.t_trainid and
      P4.p_trainid = T2.t_trainid and
      P1.p_stationid = S1.s_stationid and
      P2.p_stationid = S2.s_stationid and
      P3.p_stationid = S3.s_stationid and
      P4.p_stationid = S4.s_stationid and
      P1.p_seattype = P2.p_seattype and
      P1.p_seattype = SL1.sl_seattype and
      P3.p_seattype = P4.p_seattype and
      P3.p_seattype = SL2.sl_seattype and
      SP2.sp_seq >= SP1.sp_seq and
      SP2.sp_seq < SP3.sp_seq and
      SP5.sp_seq >= SP4.sp_seq and
      SP5.sp_seq < SP6.sp_seq
  GROUP BY
      SNLT.s1,
      SNLT.s2,
      SNLT.t1,
      seattype1,
      SNLT.dt1,
      SNLT.at2,
      SNLT.s3,
      SNLT.s4,
      SNLT.t2,
      seattype2,
      SNLT.dt3,
      SNLT.at4,
      total_time,
      price,
      price1,
      price2
  ORDER BY
      price
  )
  -- 对详细信息进行归并（座位信息变为多列），选出最终的10个车辆、经停站站点组合
  SELECT
      LT.t1,
      LT.s1,
      LT.s2,
      LT.dt1,
      LT.at2,
      max(LT.sl1) filter(where LT.seat1='硬座') as yz_left,
      max(LT.sl1) filter(where LT.seat1='软座') as rz_left,
      max(LT.sl1) filter(where LT.seat1='硬卧上') as yws_left,
      max(LT.sl1) filter(where LT.seat1='硬卧中') as ywz_left,
      max(LT.sl1) filter(where LT.seat1='硬卧下') as ywx_left,
      max(LT.sl1) filter(where LT.seat1='软卧上') as rws_left,
      max(LT.sl1) filter(where LT.seat1='软卧下') as rwx_left,
      max(LT.p1) filter(where LT.seat1='硬座') as yz_price,
      max(LT.p1) filter(where LT.seat1='软座') as rz_price,
      max(LT.p1) filter(where LT.seat1='硬卧上') as yws_price,
      max(LT.p1) filter(where LT.seat1='硬卧中') as ywz_price,
      max(LT.p1) filter(where LT.seat1='硬卧下') as ywx_price,
      max(LT.p1) filter(where LT.seat1='软卧上') as rws_price,
      max(LT.p1) filter(where LT.seat1='软卧下') as rwx_price,
      LT.price,

      LT.total_time,
      LT.t2,
      LT.s3,
      LT.s4,
      LT.dt3,
      LT.at4,
      max(LT.sl2) filter(where LT.seat2='硬座') as yz_left,
      max(LT.sl2) filter(where LT.seat2='软座') as rz_left,
      max(LT.sl2) filter(where LT.seat2='硬卧上') as yws_left,
      max(LT.sl2) filter(where LT.seat2='硬卧中') as ywz_left,
      max(LT.sl2) filter(where LT.seat2='硬卧下') as ywx_left,
      max(LT.sl2) filter(where LT.seat2='软卧上') as rws_left,
      max(LT.sl2) filter(where LT.seat2='软卧下') as rwx_left,
      max(LT.p2) filter(where LT.seat2='硬座') as yz_price,
      max(LT.p2) filter(where LT.seat2='软座') as rz_price,
      max(LT.p2) filter(where LT.seat2='硬卧上') as yws_price,
      max(LT.p2) filter(where LT.seat2='硬卧中') as ywz_price,
      max(LT.p2) filter(where LT.seat2='硬卧下') as ywx_price,
      max(LT.p2) filter(where LT.seat2='软卧上') as rws_price,
      max(LT.p2) filter(where LT.seat2='软卧下') as rwx_price
 
  FROM
      LT
  GROUP BY
      LT.s1,
      LT.s2,
      LT.t1,
      LT.dt1,
      LT.at2,
      LT.s3,
      LT.s4,
      LT.t2,
      LT.dt3,
      LT.at4,
      LT.price,
      LT.total_time
  ORDER BY
      LT.price,
      LT.total_time,
      LT.dt1
  LIMIT
      10
  ;
EOF;
  //echo "$sql";
  //exit();
  $ret = mypg_query($conn,$sql);
  $row=pg_fetch_row($ret);
  echo "<h4>$scity-$ecity 的连续查询结果</h4>出发日期: $sday<br>";
  if(empty($row)){
    echo "没有找到<br>您可以";
    echo "<a href=/php/buyday.php>查看所有发车日期</a>";
    return;
  }
  echo "<p class='small'>注：选择座位类型后点预订可以进入购买页面</p>";
  echo "<table class=\"default-table\"border=\"1\">";
  echo "<tr><th>序号</th>
  <th>车次</th><th>出发站</th><th>到达站</th><th>出发时间</th><th>到达时间</th>
  <th>硬座</th><th>软座</th><th>硬卧上</th><th>硬卧中</th><th>硬卧下</th><th>软卧上</th><th>软卧下</th>
  <th>硬座</th><th>软座</th><th>硬卧上</th><th>硬卧中</th><th>硬卧下</th><th>软卧上</th><th>软卧下</th>
  <th>最低价格</th><th>行程总时间</th><th>预订</th>
  </tr>";
  $id = 0;
  while($row){
    echo "<tr>"; 
    $total_tmp = strtotime("$row[20]",'1970-01-01');
    $total_day = intval(date("d",$total_tmp))-1;
    $total_hour = date("H时i分",$total_tmp);
    $row[20] = $total_hour;
    if($total_day>0) $row[20] = "{$total_day}天".$row[20];

    $row[0] = trim($row[0]);
    $row[21] = trim($row[21]);
    
    $count = intval(date("d",strtotime($row[4])))-intval(date("d",strtotime($row[3])));
    $count1 = intval(date("d",strtotime($row[25])))-intval(date("d",strtotime($row[24])));
    $endhour = date("H:i",strtotime($row[4]));
    $endhour1 = date("H:i",strtotime($row[25]));
    $sday1 = date("Y-m-d",strtotime($row[24]));
    $stime1 = date("H:i",strtotime($row[24]));
    $stime0 = date("H:i",strtotime($row[3]));
    $row[3] = date("Y-m-d H:i",strtotime($row[3]));
    $row[4] = date("Y-m-d H:i",strtotime($row[4]));
    $row[24] = date("Y-m-d H:i",strtotime($row[24]));
    $row[25] = date("Y-m-d H:i",strtotime($row[25]));
    //if($count>0) $row[4] = $row[4]."<br>+{$count}天";
    //if($count1>0) $row[25] = $row[25]."<br>+{$count1}天";
    $id++;
    echo "<td rowspan=\"2\">$id</td>"; 
    for ($i=0;$i<sizeof($row);$i++){
      if($i==21) 
      echo <<<EOF
<td rowspan="2"><button onclick=sendpost($id,"$sday","$count","$stime0","$endhour","$sday1","$count1","$stime1","$endhour1")>预订</button></td></tr><tr>
EOF;

      $thisid = $id*100+$i;
      echo "<td id=$thisid";
      if(!isset($row[$i])) echo ">-</td>";
      else if($i>=12 and $i<=18 or $i>=33 and $i<=39) {
        echo ">¥$row[$i]</td>";
      }
      else if(($i>=5 and $i<=11 or $i>=26 and $i<=32)and $row[$i]>0) {
        $st = $i-5;
        $name = $id;
        if($i>20) {$st=$i-26;$name=$id+100;}
        echo <<<EOF
>$row[$i]<input type="radio" name="$name" value=$st></td>
EOF;
      }
      else if($i==19) echo " rowspan=\"2\">¥$row[$i]</td>";
      else if($i==20) echo " rowspan=\"2\">$row[$i]</td>";
      else echo ">$row[$i]</td>";
    }
    $row=pg_fetch_row($ret);
    echo"</tr>";
  }
  echo "</table>";
  $nextday = date("Y-m-d",strtotime("+1 day",strtotime($sday)));
  queryreturn($ecity,$scity,$nextday);

}
?>
