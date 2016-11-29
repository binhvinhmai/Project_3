$(document).ready(init);


//Copied straight from search.hs in student_ajax, need to modify actually
function init(){
    $("#search_button").on("click",view_students);
    $("#search").on("keydown",function(event){view_students_key(event);});
}

function view_students_key(event) {
    if (event.keyCode == 13) //ENTER KEY
        view_students();
}

function view_students() {
    $.ajax({
        method: "POST",
        url: "search.php",
        dataType: "text", //return text data
        data: {search: $("#search").val()}, //send the value of the search box
        success: function (data) {
            $("#students").html(data); //the returned result is the HTML of the students div
        }
    });   
}
