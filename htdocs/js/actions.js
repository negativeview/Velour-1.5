function edit_inline_rt() {
	var button = $(this);
	var id = button.attr('editable');
	
	var m = $('<textarea />');
	var to_replace = $('#' + id);
	var w = to_replace.width();
	var h = to_replace.height();
	
	// Add the HTML contents from the replaced bit to the textarea.
	m.html(to_replace.html());
	m.attr('id', id + '_edit');
	m.css('width', w + 'px');
	m.css('height', h + 'px');
	
	to_replace.wrapInner(m);
	
	var ed = new tinymce.Editor(
		document.getElementById(id + '_edit'),
		{
			theme: "advanced",
			theme_advanced_buttons1: "bold,italic,underline,strikethrough,blockquote,|,forecolor,backcolor,|,link,unlink,|,sub,sup,|,formatselect,image,code,hr",
			theme_advanced_buttons2: "",
			convert_urls: false,
			id: id + '_edit_raw'
		}
	);
	ed.render(true);
	
	button.attr('original_html', button.html());
	button.html('Save');
	button.unbind('click');
	button.click(
		function() {
			var content = tinyMCE.activeEditor.getContent();
			$.post(
				document.location.href,
				{
					action: 'generic-post',
					id: id,
					value: content,
				},
				function(res) {
					if (res) {
						alert(res);
						return;
					}
					$('#' + id + '_edit').detach();
					tinyMCE.activeEditor.remove();
					to_replace.wrapInner(content);
					button.html(button.attr('original_html'));
					button.unbind('click');
					button.click(edit_inline_rt);
				}
			);
			return false;
		}
	);
	
	return false;
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
		$('.button.edit').each(function(idx, el) {
			var e = $(el);
			if (e.attr('editable')) {
				var el = $('#' + e.attr('editable'));
				if (el) {
					e.click(edit_inline_rt);
				}
			}
		});
	}
);
