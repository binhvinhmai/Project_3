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
    }
    
    echo $result;
}

function is_session_active() {
    return isset($_SESSION) && count($_SESSION) > 0 && time() < $_SESSION['start'] + 60 * 5; //check if it has been 5 minutes
}

function search($connection, $search) {
    /* select all cars where any of the description is similar to the input */
    $query = "SELECT * FROM car "
            . "INNER JOIN carspecs " 
            . "ON car.CarSpecsID = carspecs.ID " 
            . "WHERE Status = '2' AND ("
            . "Make like '%$search%' OR " 
            . "Model like '%$search%' OR "
            . "YearMade like '%$search%' OR "
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
            $item = array("id"=>$row["ID"],
                "picture_type"=>$row["Picture_type"],
                "picture"=>base64_encode($row["Picture"]), 
                "make"=>$row["Make"], 
                "model"=>$row["Model"], 
                "year"=>$row["YearMade"], 
                "color"=>$row["Color"], 
                "size"=>$row["Size"]);
            $final_result["items"][]=$item;
        }
    }
    return json_encode($final_result);
}

?>

