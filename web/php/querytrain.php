<?php
include_once("utils.php");
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
    $sql = 

}

?>