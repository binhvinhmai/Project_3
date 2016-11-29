$(document).ready(init);

//Copied straight from search.hs in student_ajax, need to modify actually
function init(){
    //If user clicks the magnifying glass
    $("#find-car").on("click",view_cars());
    //If user hits 'Enter' after a search
    $("#find-car-input").on("keydown",function(event){view_cars_key(event);});
}

function view_cars_key(event) {
    if (event.keyCode == 13) //ENTER KEY
        view_cars();
}

function view_cars() {
    console.log("Search");
    $.ajax({
        method: "POST",
        url: "server/search.php",
        dataType: "text", //return text data
        data: {search: $("#search").val()}, //send the value of the search box
        success: function (data) {
            console.log(data);
            $("#returned_cars").html(data); //the returned result is the HTML of the students div
        }
    });   
}
