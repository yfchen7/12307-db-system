
<?php
include_once("utils.php");
 require_once '../header.php'?>
  <body>
    <?php require_once '../nav.php'?>
    <?php require_once '../checklogin.php'?>
    <div class="center w">
		<h4>订单确认</h4>
      <?php 
        check_access("buy");
        gen_order();
        ret_botton();
      ?>
    </div>    
    <?php require_once '../footer.php'?>
  </body>
</html>

<?php
function gen_order()
{
  $sql = "
   
  "

}

?>