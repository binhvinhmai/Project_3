<?php

include "sanitization.php";
$salt="web";
$return = "fail";

if (isset($_POST['name']) && isset($_POST['password'])) {
    $username = sanitizeMYSQL($connection,$_POST['name']); //sanitize the username
    $password=md5(sanitizeMYSQL($connection,$_POST['password']));
    
    $query = "SELECT * FROM customer WHERE ID='" . $username . "' AND Password='" . $password . "'";
    $result = mysqli_query($connection,$query);
    if ($result) {
        $row_count = mysqli_num_rows($result);
        if ($row_count == 1) { //start a session
            $row = mysqli_fetch_array($result);
            session_start(); //we start a session
            $_SESSION['start'] = time(); //we set that to make the session expire after some time
            $_SESSION['username'] = $row["Name"];  //we save the customer name here, not the ID
            $_SESSION["ID"] = $row["ID"]; //we save the user ID here so SQL queries and updates work
            ini_set('session.use_only_cookies',1); //use cookies only, prevent session hijacking
            $return=  "success"; //login succeeded
        }
    }
}
    echo $return;
?>
