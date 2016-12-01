$(document).ready(init);

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
    $.ajax({
        method: "POST",
        url: "./server/controller.php",
        dataType: "json", //return text data
        data: {type: "search", search: search}, //send the value of the search box
        success: function (data) {
            var search_item_template = $("#find-car-template").html();
            var html_maker = new htmlMaker(search_item_template);
            var html = html_maker.getHTML(data);
            $("#search_results").html(html);
            //attach rent car event
            $("div[class=car_rent]").on("click",function(){rent_car(this);});
        }
    });
}

function rent_car(rent_car_button) {
    var car_id=$(rent_car_button).attr("id");
    $.ajax({
        method: "POST",
        url: "./server/controller.php",
        dataType: "text", //return text data
        data: {type: "rent",car_id:car_id},
        success: function (data) {
            if ($.trim(data)=="success") {
                alert("The car has been rented successfully");
                view_cars(); //Refresh page so that the rented car doesn't show
            }
        }
    }); 
}


