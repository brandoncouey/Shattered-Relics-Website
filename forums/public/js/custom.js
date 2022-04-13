$(document).ready(function() {

	$('[data-toggle="tooltip"]').tooltip();

	$('#toTop').on('click',function (e) {
		e.preventDefault();

		var target = this.hash;
		var $target = $(target);

		$('html, body').stop().animate({
			'scrollTop': 0
		}, 900, 'swing');
	});

    $(".sidebar-dropdown > a").click(function() {
        $(".sidebar-submenu").slideUp(200);
        if ($(this).parent().hasClass("active")) {
          $(".sidebar-dropdown").removeClass("active");
          $(this).parent().removeClass("active");
        } else {
          $(".sidebar-dropdown").removeClass("active");
          $(this).next(".sidebar-submenu").slideDown(200);
          $(this).parent().addClass("active");
        }
    });
      
    $("#close-sidebar").click(function() {
        $(".page-wrapper").removeClass("toggled");
    });

    $("#show-sidebar").click(function() {
        $(".page-wrapper").addClass("toggled");
    });

    $('#dark-toggle').click(function(event) {
        event.preventDefault();
        
        let cookie = Cookies.get("dark-mode");

        if (typeof cookie === "undefined") {
            Cookies.set("dark-mode", true, { expires: 365 });
        } else {
            Cookies.remove("dark-mode");
        }

        window.location.reload();
    });
      
});