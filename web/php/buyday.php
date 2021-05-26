
<?php
include_once("utils.php");
 require_once '../header.php'?>
  <body>
    <?php require_once '../nav.php'?>
    <?php require_once '../checklogin.php'?>
    <div class="center w">
      <?php 
        buyday();
        ret_button();
      ?>
    </div>    
    <?php require_once '../footer.php'?>
  </body>
</html>

<?php

function buyday()
{
  $conn = mypg_connect();
	$sql = "select * from runday order by r_day;";
	$ret = mypg_query($conn,$sql);
  echo "<table class=\"default-table\"border=\"1\"><tr><th>所有发车日期</th></tr>";
  while($row = pg_fetch_row($ret)){
    echo "<tr><td>$row[0]</td></tr>";
  }
  echo "</table>";
}


?>