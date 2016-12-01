$(document).ready(init);

//Copied straight from search.hs in student_ajax, need to modify actually
function init(){
    //If user clicks the magnifying glass
    $("#find-car").on("click",view_cars);
    //If user hits 'Enter' after a search
    $("#find-car-input").on("keydown",function(event){view_cars_key(event);});
}

function view_cars_key(event) {
    if (event.keyCode == 13) //ENTER KEY
        view_cars();
}

function view_cars() {
    var search = $("#find-car-input").val();
    console.log(search)
    $.ajax({
        method: "POST",
        url: "server/controller.php",
        dataType: "json", //return text data
        data: {type: "search", search: search}, //send the value of the search box
        success: function (data) {
            console.log(data);
            var search_item_template = $("#find-car-template").html();
            var html_maker = new htmlMaker(search_item_template);
            var html = html_maker.getHTML(data);
            $("#search_results").html(html);
        }
    });   
}
