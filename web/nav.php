<div class="header w">
    <div class = "logo">
        <a href="/index.php"><img src="/images/logo1.png" alt=""></a>
    </div>
    <div class="nav">
        <ul>
            <li><a href="/index.php">首页</a></li>
            <li><a href="/query.php">购票</a></li>
            <li><a href="/orders.php">订单</a></li>
        </ul>
    </div>
    <div class="user">
        <?php
            if(!isset($_SESSION)) {
                session_start();
            } 
            if(isset($_SESSION['usr']))
                echo "{$_SESSION['usr'][1]} &nbsp;<a href=\"/php/logout.php\">注销</a>";
            else echo '<a href="/login.php">登录</a>&nbsp;&nbsp;
                        <a href="/register.php">注册</a>';
        ?>
    </div>
</div>