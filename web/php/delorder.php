<?php 

include_once("utils.php");
check_access('orderid');
delorder();

function delorder()
{
  $uid = $_SESSION['usr'][0];
  $orderid = $_POST['orderid'];
  $conn = mypg_connect();
  if($_SESSION['usr'][1]!='admin')
    checkoid($conn,$orderid,$uid);
    $sql = "select count(*) from orders where o_orderid=$orderid and o_status='有效'";
    $ret = mypg_query($conn,$sql);
    $row = pg_fetch_row($ret);
    if($row[0]!=1){
      echo "<script>alert('订单无效');history.go(-1);</script>";
      exit();
    }
  $sql = "
UPDATE
  orders
SET
  o_status='已取消'
WHERE
  o_orderid=$orderid;";
  $ret = mypg_query($conn,$sql);

  $sql="
  UPDATE
    seatleft
SET
    sl_seatleft=sl_seatleft+1
FROM
    orders,
    stop as SP1,
    stop as SP2,
    stop as SP3,
    runday
WHERE
    o_orderid=$orderid and
    o_trainid=SP1.sp_trainid and
    o_trainid=SP2.sp_trainid and
    o_trainid=SP3.sp_trainid and
    o_departstation=SP1.sp_stationid and
    o_arrivestation=SP3.sp_stationid and
    o_seattype=sl_seattype and
    sl_stationid=SP2.sp_stationid and
    sl_trainid=o_trainid and
    (
        (
        (
        SP1.sp_arrivetime>SP1.sp_departtime and
        sl_day+SP1.sp_count+1=o_travelday
    )or
    (
        SP1.sp_arrivetime<=SP1.sp_departtime and
        sl_day+SP1.sp_count=o_travelday
    )
    )
    )and
    sl_day=r_day and
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
  echo "<script>alert('订单已取消');history.go(-1);</script>";
  exit();
}

function checkoid($conn,$orderid,$uid)
{
  
  $sql = "select o_userid from orders where o_orderid=$orderid";
  $ret = mypg_query($conn,$sql);
  $row=pg_fetch_row($ret);
  if(empty($row) or $row[0]!=$uid){
    echo "错误的请求";
    exit();
  }
}

?>