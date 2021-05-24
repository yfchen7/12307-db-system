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
    function ret_botton()
    {
        echo "<br><button onclick=\"history.go(-1)\">返回</button>";
    }
    
?>