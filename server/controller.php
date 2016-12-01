<?php

include "sanitization.php";
session_start(); //start the session
$result= "failure";

// Get the type and ensure that we have an active session
if (isset($_POST['type']) && is_session_active()) {
    
    $request_type = sanitizeMYSQL($connection, $_POST['type']);
    $_SESSION['start'] = time(); //reset the session start time
     switch ($request_type) { //check the request type
        case "search":
            $result = search($connection, sanitizeMYSQL($connection, $_POST['search']));
            break;
        case "rent":
            $result = rent($connection, sanitizeMYSQL($connection, $_POST['car_id']));
            break;
        case "logout":
		    logout();
		    $result = "success";
		    break;
    }
    
    echo $result;
}

function is_session_active() {
    return isset($_SESSION) && count($_SESSION) > 0 && time() < $_SESSION['start'] + 60 * 5; //check if it has been 5 minutes
}

function search($connection, $search) {
    /* select all cars where any of the description is similar to the input */
    $query = "SELECT car.ID, car.Status, car.Picture, car.Picture_type, car.Color, carspecs.Make, carspecs.Model, carspecs.YearMade, carspecs.Size FROM car "
            . "INNER JOIN carspecs " 
            . "ON car.CarSpecsID = carspecs.ID " 
            . "WHERE Status = '2' AND ("
            . "Make like '%$search%' OR " 
            . "Model like '%$search%' OR "
            . "YearMade like '%$search%' OR "
            . "Size like '%$search%' OR "
            . "Color like '%$search%')";
    $result = mysqli_query($connection,$query);
    $html="";
    //If failed
    if (!$result)
        die("Database access failed: " . mysqli_error($connection));
    
    //Otherwise
    $final_result = array();
    if ($result) {
        $row_count = mysqli_num_rows($result);
        for($i=0;$i<$row_count;++$i){
            $row = mysqli_fetch_array($result);
            $item = array("ID"=>$row["ID"],
                "picture"=>'data:' . $row["Picture_Type"] . ';base64,' . base64_encode($row["Picture"]), 
                "make"=>$row["Make"], 
                "model"=>$row["Model"], 
                "year"=>$row["YearMade"], 
                "color"=>$row["Color"], 
                "size"=>$row["Size"]);
            array_push($final_result, $item);
        }
    }
    return json_encode($final_result);
}

function rent($connection, $car_id) {
    $query = "UPDATE car SET Status='1' WHERE ID='$car_id'";
    $result1 = mysqli_query($connection, $query);
    /*
    $query = "INSERT INTO rental(rentDate, returnDate, status, CustomerID, carID) "
            . "VALUES ('" . date("Y-m-d", time()) 
            . "', NULL, '1','" . $_SESSION['ID'] . "','" . $car_id . "'); ";
    $result2 = mysqli_query($connection, $query);
    
     */
    if ((!$result) or (!$result2))
        return "fail";
    return "success";
}

function logout() {
    $_SESSION = array(); //everything we put into the session array (data, login info, ID, etc) is gone
    
    //now we destroy the cookie
    // 100% adapted from Kuhail's slides thank you Professor Kuhail
    // set the cookie time a month in the past that should be enough
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 2592000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    
    // last but not least we destroy the session
    session_destroy();
}
?>

