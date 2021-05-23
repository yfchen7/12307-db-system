<?php 
/**
* login
*/
class Login
{
    public $username;
	public $password;
    public $conn;
	function __construct()
	{
		if (!isset($_POST['login'])) {
			echo "<script>alert('You access the page does not exist!');history.go(-1);</script>";
			exit();
		}
		session_start();
		$this->username = $_POST['username'];
		$this->password = $_POST['password'];
        $conn = pg_connect("host=localhost dbname=b12307 user=root password=root") or die('connection failed');
	}


	public function checkPwd()
	{
		$strlen = strlen($this->password);
		echo $this->password, $this->username;
		
	}


	public function checkUser()
	{
		//数据库验证
		$db = new mysqli(DB_HOST,DB_USER,DB_PWD,DB_NAME) or die('数据库连接异常');
		$sql = "SELECT username FROM users WHERE email = '".$this->email."' and password = '".$this->password."'";
		$result = mysqli_fetch_row($db->query($sql))[0];
		if (!$result) {
			echo "<script>alert('Email or password is incorrect.please try again!');history.go(-1);</script>";
			exit();
		}else{
			$db->close();
			$_SESSION['user'] = $result;
			if ($this->rem == 1) {
			  $_SESSION['rem'] = '1';
			}
			echo "<script>alert('Login Success!');location.href = '/index.php'</script>";
			exit();
		}
	}

	public function doLogin()
	{
		$this->checkPwd();
	}
}

$login = new Login();
$login->doLogin();

