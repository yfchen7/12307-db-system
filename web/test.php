

<?php 

$conn = pg_connect("host=localhost dbname=b12307 user=root password=root")
or die('connection failed');
if(!$conn){
    echo "<script>alert('fail');</script>";
   } else {
    echo "<script>alert('success');</script>";
   }
            //or echo "<script>alert('fail');</script>";
            //die('Could not connect: ' . pg_last_error());
//echo "<script>alert('success');</script>";

?>
