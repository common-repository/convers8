$(document).ready(function () {
	// check if the user wants to logout.
	if ($.cookie("convers8-logout") != null) {	
		convers8_logout();
		$.cookie("convers8-logout", null);
	}
	
	// hook on the logout event and refresh the page.
	$(convers8).bind('logout', function(event) {		
		$.ajax({
			type: "POST",
			url: "/wp-admin/admin-ajax.php",
			data: {action: "convers8_logout"}		
		});
		
		$.get($(location).attr('href'), function(data) {
			window.location.reload(true);
		});
	});
	
	// hook on the login event
	$(convers8).bind("login_credentials", function(event, convers8_data) {		
		if (convers8_data != undefined) {		
			$.ajax({
				type: "POST",
				url: "/wp-admin/admin-ajax.php",
				dataType: "json",
				data: {action : "convers8_login", convers8 : convers8_data},
				success: function(response) {						
					if (parseInt(response.result) == 1) {							
						if (wordpress_admin_login !== false) {						
							window.location = "/wp-admin/";								
						} else {
							window.location.reload(true);
						}
					}		
				}
			});		
		}
	});	
	
	$('.convers8_userpic').hover(function(){
		$(this).find('.convers8_attached_networks').stop(true,true).fadeIn();
	},function(){
		$(this).find('.convers8_attached_networks').stop(true,true).fadeOut();
	});
	
	$('.convers8_userpic .convers8_icon_small').hover(function(){
			$('.convers8_userpic .convers8_icon_small').not(this).stop(true,true).fadeTo('fast','0.5');
		},function(){
			$('.convers8_userpic .convers8_icon_small').not(this).stop(true,true).fadeTo('fast','1');
	});
});

var wordpress_admin_login = false;

function convers8_login(network, admin_login) {
	if (admin_login != undefined) {
		wordpress_admin_login  = true;
	}
	
	convers8_createLoginPopup(network, true);
}