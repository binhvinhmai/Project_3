<?php

include "sanitization.php";
$result= "failure";

// Get the type and ensure that we have an active session
// TODO: make this line work: if (isset($_POST['type']) && is_session_active()) 
if (isset($_POST['type'])) {
    
    $request_type = sanitizeMYSQL($connection, $_POST['type']);
     switch ($request_type) { //check the request type
        case "search":
            $result = search($connection, sanitizeMYSQL($connection, $_POST['search']));
            break;
        case "rent":
            $result = drop($connection, sanitizeMYSQL($connection, $_POST['car_id']));
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

function drop($connection, $car_id) {
    $query = "UPDATE car SET Status='1' WHERE ID='$car_id'";
    $result = mysqli_query($connection, $query);
    if (!$result)
        return "fail";
    return "success";
}
?>

