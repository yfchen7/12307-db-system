<?php 

include_once("utils.php");
check_access('login');
checkPasswd();

function checkPasswd()
{
	$err = '登录失败';	
	$username = $_POST['username'];
	//if(!preg_match("/^\d{11}$/",$phone)) {$err = '手机号格式有误'; goto FAIL;}
	$password = $_POST['password'];
	$passwdhash = $password;

	$conn = mypg_connect();
	$sql = "select  * from usr where u_username='$username' and u_passwdhash='$passwdhash' limit 1;";
	$ret = mypg_query($conn,$sql);
	$row = pg_fetch_row($ret);
	if(empty($row)) goto FAIL;
	$_SESSION['usr'] = $row;
	echo "<script>alert('登录成功');location.href = '../index.php'</script>";
	exit();
FAIL:
	echo "<script>alert('{$err}');history.go(-1);</script>";
	exit();
}



?>

