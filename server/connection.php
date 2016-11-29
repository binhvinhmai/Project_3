<?php 

  $db_hostname = 'kc-sce-appdb01';
  $db_database = "bvmvw5";
  $db_username = "bvmvw5";
  $db_password = "baFKeuIubq43iMYewfTB";
  

 $connection = mysqli_connect($db_hostname, $db_username,$db_password,$db_database);
 
 if (!$connection)
    die("Unable to connect to MySQL: " . mysqli_connect_errno());


?>