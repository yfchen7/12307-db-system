<?php
    function mypg_connect()
    {
        $conn = pg_connect("host=localhost dbname=b12307 user=root password=root");
	    if(!$conn) die('connection failed');
        return $conn;
    }
    function mypg_query($conn,$sql)
    {
        $ret = pg_query($conn,$sql);
	    if(!$ret) die(pg_last_error());
        return $ret;
    }
    function ret_button($str="返回")
    {
        echo "<br><br><button onclick=\"history.go(-1)\">$str</button>";
    }
    function check_access($str)
    {
        if (!isset($_POST[$str])) {
            echo "<script>alert('没有权限访问');history.go(-1);</script>";
            exit();
        }
    }
    function check_get($str)
    {
        if (!isset($_GET[$str])) {
            echo "<script>alert('没有权限访问');history.go(-1);</script>";
            exit();
        }
    }

    function fails($str='')
    {
        if(empty($str)) $str="错误的请求";
        echo "<script>alert('$str');history.go(-1);</script>";
        exit();
    }

    if(!isset($_SESSION)) {
        session_start();
    } 
    
?>

<script>
function httpPost(URL, PARAMS) {
 var temp = document.createElement("form");
 temp.action = URL;
 temp.method = "post";
 temp.style.display = "none";
 
 for (var x in PARAMS) {
  var opt = document.createElement("textarea");
  opt.name = x;
  opt.value = PARAMS[x];
  temp.appendChild(opt);
 }
 
 document.body.appendChild(temp);
 temp.submit();
 
 return temp;
}


</script>