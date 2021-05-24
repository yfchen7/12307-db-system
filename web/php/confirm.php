
<?php
include_once("utils.php");
 require_once '../header.php'?>
  <body>
    <?php require_once '../nav.php'?>
    <?php require_once '../checklogin.php'?>
    <div class="center w">
		<h4>订单确认</h4>
      <?php 
        gen_order();
        gen_usrinfo();
        ret_botton("取消");
        confirm_botton();
      ?>
    </div>    
    <?php require_once '../footer.php'?>
  </body>
</html>

<?php
function gen_order()
{
  $trainno = $_POST['trainno'];
  $sname = $_POST['sname'];
  $sday = $_POST['sday'];
  $stime = $_POST['stime'];
  $ename = $_POST['ename'];
  $eday = $_POST['eday'];
  $etime = $_POST['etime'];
  $seattype = $_POST['seattype'];
  $price = $_POST['price'];
  
  echo "success";
}
function gen_ticketinfo($trainno,$sname,$sday,$stime,$ename,$eday,$etime,$seattype,$price)
{
  echo "车次:$trainno<br>
    $sday $stime - $eday $etime <br>
    $seattype ¥$price<br>
  "
}

function gen_usrinfo()
{
  echo "乘车人信息<br>姓名:{$_SESSION['usr'][4]}<br>
  身份证:{$_SESSION['usr'][6]}<br>
  手机号:{$_SESSION['usr'][2]}<br>
  信用卡:{$_SESSION['usr'][6]}<br>
  ";
}

function confirm_botton()
{

}

?>