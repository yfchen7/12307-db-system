<!DOCTYPE html>
<html lang="zh-CN">
<?php require_once 'header.php'?>
<script>
    function checkPassword(){
        var x = document.forms['registerform'].password.value;
        var y = document.forms['registerform'].checkpassword.value;
        if(x!=y){
            alert("密码不一致");
            return false;
        }
    }
</script>
  <body>
    <?php require_once 'nav.php'?>
    <div class="w"> 
      <span class="aera">
        <h4>注册新用户</h4>
        <form action="php/register.php" name="registerform" method="post" accept-charset="utf-8" class="form" onsubmit="return checkPassword()">
        <ul>
          <li><label>手机号：</label><input type="number"name="phone"required="required"maxlength=11>
          <span class="small">*11位数字</span></li>
          <li><label>用户名：</label><input type="text"name="username"required="required"maxlength=20>
          <span class="small">*1-20位字符</span></li>
          <li><label>真实姓名：</label><input type="text"name="realname"required="required"maxlength=20>
          <span class="small">*1-20位字符</span></li>
          <li><label>身份证号：</label><input type="text"name="realid"required="required"maxlength=18>
          <span class="small">*18位数字或X</span></li>
          <li><label>信用卡号：</label><input type="number"name="ccard"required="required"maxlength=16>
          <span class="small">*16位数字</span></li>
          <li><label>密码：</label><input type="password"name="password"required="required"maxlength=20>
          <span class="small">*1-20字符</span></li>
          <li><label>确认密码：</label><input type="password"name="checkpassword"required="required"maxlength=20>
          <span class="small">*1-20字符</span></li>
        </ul>
        <input type="submit"value="注册"name="register"class="botton">
        </form>
		  </span>
    </div>
    <?php require_once 'footer.php'?>
  </body>
</html>
