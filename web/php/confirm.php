
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
        ret_index();
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
  
  if(isset($_POST['trainno1'])){
    $trainno1 = $_POST['trainno1'];
    $sname1 = $_POST['sname1'];
    $sday1 = $_POST['sday1'];
    $stime1 = $_POST['stime1'];
    $ename1 = $_POST['ename1']; 
    $eday1 = date('Y-m-d',strtotime($_POST['sday1'])+60*60*(24)*$_POST['count1']);
    $etime1 = $_POST['etime1'];
    $seattype1 = $_POST['seattype1'];
    $price1 = $_POST['price1'];
    $seattype1 = $seatarr[$seattype1];
  }
  gen_ticketinfo($trainno,$sname,$sday,$stime,$ename,$eday,$etime,$seattype,$price);
  if(isset($_POST['trainno1']))
    gen_ticketinfo($trainno1,$sname1,$sday1,$stime1,$ename1,$eday1,$etime1,$seattype1,$price1);
  gen_total_price($price,$price1);
  gen_usrinfo();
  if(isset($_POST['trainno1']))
    confirm_button1($trainno,$sname,$sday,$ename,$seattype,$trainno1,$sname1,$sday1,$ename1,$seattype1);
  else confirm_button($trainno,$sname,$sday,$ename,$seattype);
}
function gen_ticketinfo($trainno,$sname,$sday,$stime,$ename,$eday,$etime,$seattype,$price)
{
  $total = $price+5.1;
  echo "$trainno 号<br>
    $sday $stime - $eday $etime <br>
    $sname - $ename <br>
    $seattype ¥$price<br><br>
  ";
}

function gen_total_price($price,$price1=0)
{
  $total = $price+5;
  if(isset($_POST['trainno1'])){
    echo "订票费:5 元 × 2";
    $total += 5+$price1;
  }
  else echo "订票费:5 元";
  echo "<br>总票价:$total 元";
}

function gen_usrinfo()
{
  echo "<h4>乘车人信息</h5>姓名:{$_SESSION['usr'][4]}<br>
  身份证:{$_SESSION['usr'][3]}<br>
  手机号:{$_SESSION['usr'][2]}<br><br>
  ";
}

function confirm_button1($trainno,$sname,$sday,$ename,$seattype,$trainno1,$sname1,$sday1,$ename1,$seattype1)
{
  echo <<<EOF
  <div><button class="botton" 
  onclick=httpPost('buy.php',{"trainno":"$trainno","sname":"$sname","sday":"$sday","ename":"$ename","seattype":"$seattype","trainno1":"$trainno1","sname1":"$sname1","sday1":"$sday1","ename1":"$ename1","seattype1":"$seattype1"})>
  确认购买</button></div>
EOF;
}
function confirm_button($trainno,$sname,$sday,$ename,$seattype)
{
  echo <<<EOF
  <div><button class="botton" 
  onclick=httpPost('buy.php',{"trainno":"$trainno","sname":"$sname","sday":"$sday","ename":"$ename","seattype":"$seattype"})>
  确认购买</button></div>
EOF;
}

function ret_index()
{
    echo "<br><br><button onclick=\"location='/index.php'\">取消</button>";
}

?>