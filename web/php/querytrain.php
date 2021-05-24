
<?php
include_once("utils.php");
 require_once '../header.php'?>
  <body>
    <?php require_once '../nav.php'?>
    <div class="center w">
		<h4>查询结果</h4>
      <?php 
        checkAccess();
        querytrain();
        ret_botton();
      ?>
    </div>    
    <?php require_once '../footer.php'?>
  </body>
</html>


<?php
function checkAccess()
{
	if (!isset($_POST['querytrain'])) {
		echo "<script>alert('没有权限访问');history.go(-1);</script>";
		exit();
	}
	session_start();
}

function querytrain()
{
	$trainno = $_POST['trainno'];
	$day = $_POST['day'];
	$conn = mypg_connect();
	$sql = "
		
	";

}

?>