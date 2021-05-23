<!DOCTYPE html>
<html lang="zh-CN">
<?php require_once 'header.php'?>
  <body>
    <?php require_once 'nav.php'?>
    <div class="w center"> 
      <span class="aera">
        <h4>登录</h4>
        <form action="php/login.php" method="post" accept-charset="utf-8" class="form">
        <ul>
          <li><label for="">用户名：</label><input type="text"name="username"required="required"maxlength=20>
          <span class="small">3-20字符</span></li>
          <li><label for="">密码：</label><input type="password"name="password"required="required"maxlength=20>
          <span class="small">3-20字符</span></li>
        </ul>
        <input type="submit"value="登录"name="login"class="botton">
        </form>
		  </span>
    </div>
    <?php require_once 'footer.php'?>
  </body>
</html>
