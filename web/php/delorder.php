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
  $sql = "
UPDATE
  orders
SET
  o_status='已取消'
WHERE
  o_orderid=$orderid;";
  $ret = mypg_query($conn,$sql);
  echo "<script>alert('订单已取消');location.href = '../index.php'</script>";
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