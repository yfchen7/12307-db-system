<!DOCTYPE html>
<html lang="zh-CN">
<?php require_once 'header.php'?>
  <body>
    <?php require_once 'nav.php'?>
    <div class="w"> 
      <span class="aera">
        <h4>登录</h4>
        <form action="php/login.php" method="post" accept-charset="utf-8" class="form">
        <ul>
          <li><label>用户名：</label><input type="text"name="username"required="required"maxlength=20>
          <span class="small"></span></li>
          <li><label>密码：</label><input type="password"name="password"required="required"maxlength=20>
          <span class="small"></span></li>
        </ul>
        <input type="submit"value="登录"name="login"class="botton">
        </form>
		  </span>
    </div>
    <?php require_once 'footer.php'?>
  </body>
</html>
