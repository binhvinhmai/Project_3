<?php

require_once 'connection.php';
$returned_cars = ""; //HTML for the cars

if (isset($_POST['find-car-input']) && trim($_POST['find-car-input']) != "") {
    $data = $_POST['find-car-input'];
    
    /* select all cars where any of the description is similar to the input */
    $query = "SELECT * FROM car INNER JOIN carspecs ON car.CarSpecsID = carspecs.ID WHERE Make like '%$data%' OR Model like '%$data%' OR YearMade like '%$data%'";
    $result = mysqli_query($connection,$query);

    if (!$result)
        die("Database access failed: " . mysqli_error($connection));

    $row_count = mysqli_num_rows($result);

    //Create the HTML for each car
    for ($j = 0; $j < $row_count; ++$j) {
        $row = mysqli_fetch_array($result); //fetch the next row
        $returned_cars.=display_car($row);
    }
    
    mysqli_close($connection);
}

//Make HTML for a returned car
    function display_car($row) {
        $car = "";
    }

?>
