function setup_reply() {
	tinyMCE.init({
		mode: "textareas",
		theme: "advanced",
		width: '540',
		height: '200',
		theme_advanced_buttons1: "bold,italic,underline,strikethrough,blockquote,|,forecolor,backcolor,|,link,unlink,|,sub,sup,|,formatselect,image,code,hr",
		theme_advanced_buttons2: ""
	});
}

function twoDigits(n) {
	if (n < 10)
		return "0" + n;
	return n;
}

function check_favicon() {
	$.get(
		'/project-favicon.php', function(data) {
			$('link[rel="icon"]').attr('href', data);
		});
}

function setup_date(t) {
	var d = new Date(t * 1000);
	return d.toDateString();
}

$(document).ready(
	function() {
		setInterval(check_favicon, 10000);
		$('.time').each(function(it, el) {
			$(el).html(setup_date($(el).html()));
		});
		setup_reply();
		if (document.sub_load) {
			sub_load();
		}
	}
);
