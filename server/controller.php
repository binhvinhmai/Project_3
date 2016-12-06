<?php

include "sanitization.php";
session_start(); //start the session
$result= "failure";

//Get the type and ensure that we have an active session
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
            $result = logout();
            break;
        case "history":
            $result = get_rent_history($connection);
            break;
        case "return":
            $result = return_car($connection, sanitizeMYSQL($connection, $_POST['car_id']));
            break;
        case "rentals":
            $result = show_rented($connection);
            break;
    }
    
    echo $result;
}

function is_session_active() {
    return isset($_SESSION) && count($_SESSION) > 0 && time() < $_SESSION['start'] + 60 * 5; //check if it has been 5 minutes
}

function search($connection, $search) {
    //Select all cars where any of the description is similar to the input
    $query = "SELECT car.ID, car.Status, car.Picture, car.Picture_type, car.Color, carspecs.Make, carspecs.Model, carspecs.YearMade, carspecs.Size FROM car "
            . "INNER JOIN carspecs " 
            . "ON car.CarSpecsID = carspecs.ID " 
            . "WHERE Status = '1' AND ("
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
    $final_result = array(); //One dimensional array
    if ($result) {
        $row_count = mysqli_num_rows($result);
        for($i=0;$i<$row_count;++$i){
            $row = mysqli_fetch_array($result); 
            //Get the data
            //Note that the imformation for the bitmap images are hardcoded into it
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
    //The following code performs two SQL queries
    //Make it '2' to mark that it's unavailable
    $query = "UPDATE car SET Status='2' WHERE ID='$car_id'";
    $result1 = mysqli_query($connection, $query); //Check the first one
    $query = "INSERT INTO rental(rentDate, returnDate, status, CustomerID, carID) "
            . "VALUES ('" . date("Y-m-d", time()) 
            . "', NULL, '2','" . $_SESSION['ID'] . "','" . $car_id . "'); ";
    $result2 = mysqli_query($connection, $query); //Check the second one
    if ((!$result1) AND (!$result2)) //If both failed
        return "fail";
    return "success";
}

function return_car($connection, $car_id) {
    $query = "UPDATE car SET Status='1' WHERE ID='$car_id'";
    $result1 = mysqli_query($connection, $query);
    $query = "INSERT INTO rental(rentDate, returnDate, status, CustomerID, carID) "
            . "VALUES ('" . date("Y-m-d", time()) 
            . "', NULL, '1','" . $_SESSION['ID'] . "','" . $car_id . "'); ";
    $result2 = mysqli_query($connection, $query);
    if ((!$result1) AND (!$result2)) //If both failed
        return "fail";
    return "success";
}

function logout() {
    $_SESSION = array(); //Everything we put into the session array (data, login info, ID, etc) is gone
    
    //Now we destroy the cookie
    //100% adapted from Kuhail's slides thank you Professor Kuhail
    //Set the cookie time a month in the past that should be enough
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 2592000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    
    //Last but not least we destroy the session
    session_destroy();
    return "success";
}

function get_rent_history($connection) {
    //Assumes rental history is the same regardless of who is logged in
    
    //Variables:
    $returned = Array();
    $returned["cars"] = Array();
    //We need the relevent info about the cars, but they're in two different databases
    //rental.status = 2 means it's been returned
    $query = "SELECT Car.ID, Car.Color, Car.Picture, CarSpecs.Make, CarSpecs.Model, CarSpecs.YearMade, CarSpecs.Size "
            . "FROM Car INNER JOIN CarSpecs ON Car.CarSpecsID = CarSpecs.ID "
            . "INNER JOIN Rental ON Car.ID = Rental.carID "
            . "WHERE Rental.Status = 2 ;";
    $result = mysqli_query($connection, $query);
    
    //If for some reason this hybrid database doesn't work, return "failed"
    if (!$result) 
        return json_encode($returned);
    
    //Retrieve data and stick everything into a display array
    else {
        $row_count = mysqli_num_rows($result);
        for ($i = 0; $i < $row_count; $i++) { 
            $array["ID"] = $row["ID"]; 
            $array["Make"] = $row["Make"]; 
            $array["Model"]=$row["Model"];
            $array["Year"]=$row["Year"];
            $array["Picture"]=$row["Picture"];
            $array["Size"]=$row["Size"];
            $array["rentDate"]=$row["rentDate"];
            $returned["rentals"][] = $array;
        }
    }
    
    //If we've gotten here the Returned Cars array should have been filled
    return json_encode($returned);
}

function show_rented($connection) {
    $final = Array();
    $final["rentals"] = Array();
    $query = "SELECT Car.Picture, CarSpecs.Make, CarSpecs.Model, CarSpecs.YearMade, CarSpecs.Size, "
            . "Rental.ID, Rental.rentDate"
            . "FROM Car INNER JOIN CarSpecs ON Car.CarSpecsID = CarSpecs.ID "
            . "INNER JOIN Rental ON Car.ID = Rental.carID "
            . "WHERE Rental.Status = 1 AND "
            . "WHERE Rental.customerID = '" . $_SESSION['ID'] . "';"; 

    //Use the stored ID in the session (the users) to grab the rentals that are not returned who also have
    //A customer ID that matches the ID stored in the session, and then grab the car related info associated with the rental
	// -- Jkarnes: Yes. That's exactly what the $*_SESSION['ID'] does.
    $result = mysqli_query($connection, $query);
    if (!$result)
        return json_encode($final);
    else {
        $row_count = mysqli_num_rows($result);
        for ($i = 0; $i < $row_count; $i++) {
            $array["ID"] = $row["ID"]; 
            $array["Make"] = $row["Make"]; 
            $array["Model"]=$row["Model"];
            $array["Year"]=$row["Year"];
            $array["Picture"]=$row["Picture"];
            $array["Size"]=$row["Size"];
            $array["rentDate"]=$row["rentDate"];
            $returned["rentals"][] = $array;
        }
    }
    
    return json_encode($final);
}
?>


