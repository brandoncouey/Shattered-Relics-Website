$(document).ready(function() {
    $('[data-toggle="tooltip"]').tooltip();
    
    $(".cart-dropdown").on("click", function(e) {
        e.stopPropagation();
    });

});