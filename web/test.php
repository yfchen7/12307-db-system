

<?php 

function checkRegister()
{
    $err = '注册失败';
	$phone = '12341234';
    $username = 'dsdad';
    $realname = 'asdfdsd';
    $realid = strtoupper('359002200001253518');
    $ccard = '1234123412341234';
    $password = '233';
    if(!preg_match("/^\d{8,11}$/",$phone)) {$err = '手机号格式有误'; goto FAIL;}
    if(!preg_match("/^\d{16}$/",$ccard)) {$err = '信用卡号格式有误'; goto FAIL;}
    if(!preg_match('/^\d{17}[\dxX]$/',$realid)) {$err = '身份证号格式有误'; goto FAIL;}
    if(!preg_match("/^\w{1,20}$/",$realname)) {$err = '姓名格式有误'; goto FAIL;}
    if(!preg_match("/^\w{1,20}$/",$username)) {$err = '用户名格式有误'; goto FAIL;}
    if(!preg_match("/^.{1,20}$/",$password)) {$err = '密码格式有误'; goto FAIL;}
	
	$passwdhash = $password;
    
    $conn = pg_connect("host=localhost dbname=b12307 user=root password=root");
	if(!$conn) die('connection failed');
	$sql = "select  * from usr where u_phone='$phone' and u_passwdhash='$passwdhash' limit 1;";
	$ret = pg_query($conn,$sql);
	if(!$ret) die(pg_last_error());
	$row = pg_fetch_row($ret);
	if(empty($row)) goto FAIL;
	$_SESSION['usr'] = $row;
	echo "<script>alert('登录成功');location.href = '../index.php'</script>";
	exit();
FAIL:
	echo "<script>alert({$err});history.go(-1);</script>";
	exit();
}
checkRegister();
exit();
$conn = pg_connect("host=localhost dbname=b12307 user=root password=root")
    or die('connection failed');
$phone = 123;
$passwdhash='p';
		$sql = "select u_username from usr where u_phone='$phone' and u_passwdhash='$passwdhash';";
		echo $sql;
		$ret = pg_query($conn,$sql);
        if(!$ret) die(pg_last_error());
		$row = pg_fetch_row($ret);
        echo "<br>";
        if(empty($row)) echo "注册失败";
		else echo $row[1];