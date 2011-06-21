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
	}
);