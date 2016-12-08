<?php
include "sanitization.php";
session_start(); //Start the session
$result= "failure";
//Get the type and ensure that we have an active session
if (isset($_POST['type']) && is_session_active()) {
    
    $request_type = sanitizeMYSQL($connection, $_POST['type']);
    $_SESSION['start'] = time(); //Reset the session start time
     switch ($request_type) { //Check the request type
        case "name":
            $result = get_name($connection);
            break;
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

function get_name($connection) {
    $name = $_SESSION['username'];
    return $name;
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
    $query1 = "UPDATE car SET Status='2' WHERE ID='$car_id'";
    $result1 = mysqli_query($connection, $query1); //Check the first one

    //Add row to rental SQL 
    $query2 = "INSERT INTO rental (status, rentDate, CustomerID, carID) VALUES ('1',CURDATE(),'" . $_SESSION['ID'] . "', '" . $car_id . "');";     
    $result2 = mysqli_query($connection, $query2); //Check the second one
    
    if ((!$result1) AND (!$result2)) //If both failed
        return "fail";
    return "success";
}
function return_car($connection, $car_id) {
 
    //Set Car Status to 1(available)
    $query = "UPDATE car SET Status='1' WHERE ID='$car_id'";
    $result1 = mysqli_query($connection, $query);
    
    //Set Rental Status to 2(car is returned)
    $query = "UPDATE rental SET status='2',returnDate=CURDATE() "
            . "WHERE rental.CustomerID='" . $_SESSION['ID'] . "' AND rental.carID='" . $car_id . "';";

    $result2 = mysqli_query($connection, $query);
    
    if ((!$result1) AND (!$result2)) //If both failed
        return "fail";
    return "success";

}

function logout() {
    //Everything we put into the session array (data, login info, ID, etc) is gone
    $_SESSION = array(); 
    
    //Now we destroy the cookie
    //100% adapted from Kuhail's slides thank you Professor Kuhail
    //Set the cookie time a month in the past that should be enough
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 2592000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    
    //Last, but not least, we destroy the session
    session_destroy();
    return "success";
}

function get_rent_history($connection) {
    //Assumes rental history is the same regardless of who is logged in
    
    //Variables:
    $returned = array();
    //$returned["cars"] = Array();
    //We need the relevent info about the cars, but they're in two different databases
    //rental.status = 2 means it's been returned
    $query = "SELECT car.ID, car.Color, car.Picture, carspecs.Make, carspecs.Model, carspecs.YearMade, carspecs.Size, rental.returnDate "
            . "FROM car INNER JOIN carspecs ON car.CarSpecsID = CarSpecs.ID "
            . "INNER JOIN Rental ON car.ID = rental.carID WHERE "
            . "rental.customerID = '" . $_SESSION["ID"] . "' AND "
            . "rental.returnDate IS NOT NULL;";
    $result = mysqli_query($connection, $query);
    
    //If for some reason this hybrid database doesn't work, return "failed"
    if (!$result) 
        return json_encode($returned);
    
    //Retrieve data and stick everything into a display array
    else {
        $row_count = mysqli_num_rows($result);
        for($i=0;$i<$row_count;++$i){
            $row = mysqli_fetch_array($result);
            $item = array("ID"=>$row["ID"],
                "picture"=>'data:' . $row["Picture_Type"] . ';base64,' . base64_encode($row["Picture"]), 
                "make"=>$row["Make"], 
                "model"=>$row["Model"], 
                "year"=>$row["YearMade"], 
                "color"=>$row["Color"],
                "rental_ID"=>$row["ID"],
                "return_date"=>$row["returnDate"],
                "size"=>$row["Size"]);
            array_push($returned, $item);
        }
        
    }
    
    //If we've gotten here the Returned Cars array should have been filled
    return json_encode($returned);
}

function show_rented($connection) {

    $returned = array();
    
    $query = "SELECT car.Picture, car.Picture_Type, carspecs.Make, carspecs.Model, carspecs.YearMade, carspecs.Size, "
            . "rental.carID, rental.rentDate "
            . "FROM car INNER JOIN carspecs ON car.CarSpecsID = carspecs.ID "
            . "INNER JOIN rental ON car.ID = rental.carID "
            . "WHERE rental.status = '1' AND "
            . "rental.customerID = '" . $_SESSION["ID"] . "';"; 
    
    $result = mysqli_query($connection, $query);
    
    if (!$result)
        die("Database access failed: " . mysqli_error($connection));
    else {
        //Count the number of rows
        $row_count = mysqli_num_rows($result);
        //Iterate through the array
        for ($i = 0; $i < $row_count; $i++) {
            $row = mysqli_fetch_array($result);
            $item = array("ID"=>$row["ID"],
                "picture"=>'data:' . $row["Picture_Type"] . ';base64,' . base64_encode($row["Picture"]),
                "make"=>$row["Make"], 
                "model"=>$row["Model"], 
                "year"=>$row["YearMade"],
                "rental_ID"=>$row["carID"],
                "rent_date"=>$row["rentDate"],
                "size"=>$row["Size"]);
            array_push($returned, $item);
        }
    }
    
    return json_encode($returned);

}
?>
