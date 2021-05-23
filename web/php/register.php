<?php 

include_once("utils.php");

function checkAccess()
{
	if (!isset($_POST['register'])) {
		echo "<script>alert('没有权限访问');history.go(-1);</script>";
		exit();
	}
	session_start();
}

function checkRegister()
{
    $err = '注册失败';
	$phone = $_POST['phone'];
    $username = $_POST['username'];
    $realname = $_POST['realname'];
    $realid = strtoupper($_POST['realid']);
    $ccard = $_POST['ccard'];
    $password = $_POST['password'];
    echo $realname;
    if(!preg_match("/^\d{11}$/",$phone)) {$err = '手机号格式有误'; goto FAIL;}
    if(!preg_match("/^\d{16}$/",$ccard)) {$err = '信用卡号格式有误'; goto FAIL;}
    if(!preg_match("/^\d{17}[\dxX]$/",$realid)) {$err = '身份证号格式有误'; goto FAIL;}
    if(!preg_match("/^[\x{4e00}-\x{9fa5}]{1,10}$/u",$realname) and 
        !preg_match("/^[a-zA-Z ]{1,20}$/",$realname)) {$err = '姓名格式有误'; goto FAIL;}
    if(!preg_match("/^\w{1,20}$/",$username)) {$err = '用户名格式有误'; goto FAIL;}
    if(!preg_match("/^.{1,20}$/",$password)) {$err = '密码格式有误'; goto FAIL;}
	$passwdhash = $password;

    $conn = mypg_connect();
    $sql = "
    insert into usr 
    select (case when maxid is null then 1 else maxid+1 end),
    '$username','$phone','$realid','$realname','$passwdhash','$ccard' 
    from (select max(u_userid) as maxid from usr) as xxx;
    ";
    $ret = pg_query($conn,$sql);
    if(!$ret) {$err = '手机/用户名/身份证已存在'; goto FAIL;}
	echo "<script>alert('注册成功');location.href = '../login.php'</script>";
	exit();
FAIL:
	echo "<script>alert('{$err}');history.go(-1);</script>";
	exit();
}

checkAccess();
checkRegister();

?>

