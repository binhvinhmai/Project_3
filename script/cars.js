$(document).ready(init);

function init(){
    //If user clicks the magnifying glass
    $("#find-car").on("click",view_cars);
    //If user hits 'Enter' after a search
    $("#find-car-input").on("keydown",function(event){view_cars_key(event);});
    $("#logout-link").on("click", logout);
    populate_tabs();
}

function populate_tabs() {
    show_rented();
    show_rental_history();
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
        dataType: "json", //Return text data
        data: {type: "search", search: search}, //Send the value of the search box
        success: function (data) {
            var search_item_template = $("#find-car-template").html();
            var html_maker = new htmlMaker(search_item_template);
            var html = html_maker.getHTML(data);
            $("#search_results").html(html);
            //Attach rent car event
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
                show_rented();//Refresh this tab
            }
        }
    }); 
}

function return_car(return_car_button) {
    var car_id=$(return_car_button).attr("data-rental-id");
    $.ajax({
        method: "POST",
        url: "./server/controller.php",
        dataType: "text",
        data: {type: "return",car_id:car_id},
        success: function (data) {
            alert("The car has been successfully returned");
            show_rented(); //Refresh page
            show_rental_history(); //Refresh history tab
        }
    })
}

function show_rented(){
    $.ajax({
        method: "POST",
        url: "./server/controller.php",
        dataType: "json",
        data: {type:"rentals"},
        success: function(data) {
            //Create HTML elements using rented-car-template
            var info_template=$("#rented-car-template").html();
            var html_maker=new htmlMaker(info_template);
            //Use data from database to populate HTML
            var html=html_maker.getHTML(data);    
            //Populate the #rented_cars block
            $("#rented_cars").html(html);
            //If return_car div clicked, return car and use AJAX to refresh
            $("div[class=return_car]").on("click",function(){return_car(this);});
        }
    });
}

function show_rental_history() {
    $.ajax({
        method: "POST",
        url: "./server/controller.php",
        dataType: "json",
        data: {type: "history"},
        success: function(data) {
            var returned_template=$("#returned-car-template").html();
            var html_maker = new htmlMaker(returned_template);
            var html = html_maker.getHTML(data);
            $("#returned_cars").html(html);
            
        }
    });
}

function logout() {
    $.ajax({ 
        method: "POST",
        url: "./server/controller.php",
        dataType: "text",
        data: { type: "logout" },
        success: function(data) { 
            if ($.trim(data) == "success")
            {
                alert("You have been successfully logged out");
                window.location.assign("index.html"); // redirect back home
            }
            else
                alert("You have not been successfully logged out.  Please try again");
        }
    });
}
