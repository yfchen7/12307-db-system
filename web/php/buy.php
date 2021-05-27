<?php 

include_once("utils.php");
check_access('trainno');
include_once("../checklogin.php");

do_book();


function do_book()
{
	//$err = '信息有误，购买失败';	
  //TODO : check format of post value for safety
	$trainno = $_POST['trainno'];
  $sname = $_POST['sname'];
  $sday = $_POST['sday'];
  $ename = $_POST['ename'];
  $seattype = $_POST['seattype'];
  $userid = $_SESSION['usr'][0];
	$conn = mypg_connect();
  if(isset($_POST['trainno1'])){
    $trainno1 = $_POST['trainno1'];
    $sname1 = $_POST['sname1'];
    $sday1 = $_POST['sday1'];
    $ename1 = $_POST['ename1'];
    $seattype1 = $_POST['seattype1'];
  }
  $istrans = '否';

  if(!check_sl($conn,$sday,$trainno,$sname,$ename,$seattype))
    goto FAIL;
  if(isset($_POST['trainno1'])){
    $istrans = '第一次';
    if(!check_sl($conn,$sday1,$trainno1,$sname1,$ename1,$seattype1)) goto FAIL;
  }
  
  update_sl($conn,$sday,$trainno, $sname,$ename,$seattype);
  gen_orders($conn,$userid, $sday,$trainno, $sname,$ename,$seattype,$istrans);
  
  if(isset($_POST['trainno1'])){
    $istrans = '第二次';
    update_sl($conn,$sday1,$trainno1, $sname1,$ename1,$seattype1);
    gen_orders($conn,$userid, $sday1,$trainno1, $sname1,$ename1,$seattype1,$istrans);
  }  
  echo "<script>alert('购买成功');location.href = '../orders.php'</script>";
  exit();

FAIL:
  echo "<script>alert('余票不足');history.go(-2);</script>";
  exit();
}

function check_sl($conn, $sday,$trainno, $sname,$ename,$seattype)
{
  $sql = "
  SELECT
  min(sl_seatleft) as seatleft
FROM
  train,
  seatleft,
  station as S1,
  station as S2,
  stop as SP1,
  stop as SP2,
  stop as SP3,
  runday
WHERE
  sl_seattype='$seattype' and
  S1.s_name='$sname' and
  S2.s_name='$ename' and
  t_trainno='$trainno' and
  t_trainid=sl_trainid and
  SP1.sp_stationid=S1.s_stationid and
  SP3.sp_stationid=S2.s_stationid and
  (
  (
   SP1.sp_arrivetime>SP1.sp_departtime and
   sl_day+SP1.sp_count+1='$sday'
  )or
  (
   SP1.sp_arrivetime<=SP1.sp_departtime and
   sl_day+SP1.sp_count='$sday'
  )
  )and
  sl_day=r_day and
  SP1.sp_trainid=SP3.sp_trainid and
  sl_trainid=SP1.sp_trainid and
  SP1.sp_trainid=SP2.sp_trainid and
  sl_stationid=SP2.sp_stationid and
  (
      SP2.sp_count>SP1.sp_count
      or
      (
          SP2.sp_count=SP1.sp_count and
          SP1.sp_arrivetime<=SP2.sp_arrivetime
      )
  )and
  (
      SP3.sp_count>SP2.sp_count
      or
      (
          SP3.sp_count=SP2.sp_count and
          SP2.sp_arrivetime<SP3.sp_arrivetime
      )
  );
  ";
	$ret = mypg_query($conn,$sql);
	$row = pg_fetch_row($ret);
  echo "$row";
  if(!isset($row) or empty($row[0])) return false;
	return true;
}
	
function gen_orders($conn,$userid, $sday,$trainno, $sname,$ename,$seattype,$istrans)
{  
  $sql = "
  INSERT INTO
    orders(
        o_orderid,
        o_userid,
        o_trainid,
        o_status,
        o_price,
        o_departtime,
        o_arrivetime,
        o_travelday,
        o_departstation,
        o_arrivestation,
        o_seattype,
        o_istrans
    )
SELECT
    (case when O1.orderid is null then 1 else O1.orderid+1 end),
    $userid,
    t_trainid,
    '有效',
    P2.p_price-P1.p_price as price,
    SP1.sp_departtime,
    SP2.sp_arrivetime,
    '$sday',
    SP1.sp_stationid,
    SP2.sp_stationid,
    P1.p_seattype,
    '$istrans'
FROM
    (
        SELECT 
            max(o_orderid) as orderid
        FROM
            orders
    ) as O1,
    train,
    price as P1,
    price as P2,
    stop as SP1,
    stop as SP2,
    station as S1,
    station as S2
WHERE
    t_trainno='$trainno' and
    S1.s_name='$sname' and
    S2.s_name='$ename' and
    S1.s_stationid=SP1.sp_stationid and
    S2.s_stationid=SP2.sp_stationid and
    t_trainid=SP1.sp_trainid and
    t_trainid=SP2.sp_trainid and
    P1.p_seattype='$seattype' and
    P2.p_seattype=P1.p_seattype and
    P1.p_trainid=P2.p_trainid and
    P1.p_trainid=t_trainid and
    S1.s_stationid=P1.p_stationid and
    S2.s_stationid=P2.p_stationid;
  ";
  $ret = mypg_query($conn,$sql);
	return true;
  }

function update_sl($conn,$sday,$trainno, $sname,$ename,$seattype)
{
  $sql = "
  UPDATE
    seatleft
SET
    sl_seatleft=sl_seatleft-1
FROM
    train,
    station as S1,
    station as S2,
    stop as SP1,
    stop as SP2,
    stop as SP3,
    runday
WHERE
    sl_seattype='$seattype' and
    S1.s_name='$sname' and
    S2.s_name='$ename' and
    t_trainno='$trainno' and
    SP1.sp_stationid=S1.s_stationid and
    SP3.sp_stationid=S2.s_stationid and
    (
    (
     SP1.sp_arrivetime>SP1.sp_departtime and
     sl_day+SP1.sp_count+1='$sday'
    )or
    (
     SP1.sp_arrivetime<=SP1.sp_departtime and
     sl_day+SP1.sp_count='$sday'
    )
    )and
    sl_day=r_day and
    SP1.sp_trainid=SP3.sp_trainid and
    SP1.sp_trainid=SP2.sp_trainid and
    t_trainid=sl_trainid and
    sl_trainid=SP1.sp_trainid and
    sl_stationid=SP2.sp_stationid and
    (
        SP2.sp_count>SP1.sp_count
        or
        (
            SP2.sp_count=SP1.sp_count and
            SP1.sp_arrivetime<=SP2.sp_arrivetime
        )
    )and
    (
        SP3.sp_count>SP2.sp_count
        or
        (
            SP3.sp_count=SP2.sp_count and
            SP2.sp_arrivetime<SP3.sp_arrivetime
        )
    );
  ";
  $ret = pg_query($conn,$sql);
  if(!$ret) return false;
  return true;
}


?>

