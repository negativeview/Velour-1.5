$(document).ready(
	function() {
		$('.option input').focusin(
			function(event) {
				$(event.target).parent().addClass('focus');
			}
		).focusout(
			function(event) {
				$(event.target).parent().removeClass('focus');
			}
		);
		
		$('.projects').each(
			function(idx, el) {
				var project = $(el);
				var hiders = project.children('a:gt(4)');
				if (hiders.length) {
					hiders = project.children('a:gt(3)');
					hiders.css('display', 'none');
					
					var a = $('<a>');
					a.click(function() {
						hiders.slideToggle();
					});
					a.html('-more-');
					project.append(a);
				}
			}
		);
	}
);