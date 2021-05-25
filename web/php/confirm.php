
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
        //gen_usrinfo();
        ret_button("取消");
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
  $eday = date('Y-m-d',strtotime($_POST['sday'])+60*60*(24)*$_POST['count']);
  $etime = $_POST['etime'];
  $seattype = $_POST['seattype'];
  $price = $_POST['price'];
  $seatarr = array("硬座","软座","硬卧上","硬卧中","硬卧下","软卧上","软卧下");
  $seattype = $seatarr[$seattype];
  gen_ticketinfo($trainno,$sname,$sday,$stime,$ename,$eday,$etime,$seattype,$price);
  gen_usrinfo();
  confirm_button($trainno,$sname,$sday,$stime,$ename,$eday,$etime,$seattype,$price);
}
function gen_ticketinfo($trainno,$sname,$sday,$stime,$ename,$eday,$etime,$seattype,$price)
{
  $total = $price+5.1;
  echo "$trainno 号<br>
    $sday $stime - $eday $etime <br>
    $sname - $ename <br>
    $seattype ¥$price<br>
    订票费5元<br>
    总价格$total 元<br>
  ";
}

function gen_usrinfo()
{
  echo "<h4>乘车人信息</h5>姓名:{$_SESSION['usr'][4]}<br>
  身份证:{$_SESSION['usr'][3]}<br>
  手机号:{$_SESSION['usr'][2]}<br>
  信用卡:{$_SESSION['usr'][6]}<br><br>
  ";
}

function confirm_button($trainno,$sname,$sday,$stime,$ename,$eday,$etime,$seattype,$price)
{
  echo <<<EOF
  <div><button class="botton" 
  onclick=httpPost('buy.php',{"trainno":"$trainno","sname":"$sname","sday":"$sday","ename":"$ename","seattype":"$seattype"})>
  确认购买</button></div>
EOF;
}

?>