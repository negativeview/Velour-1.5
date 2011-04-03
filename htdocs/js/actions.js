function setup_reply() {
	$('.conv_reply').each(function(idx, el) {
		$(el).click(
			function() {
				tinyMCE.activeEditor.remove();
				$(el).parent().parent().after($('#conv_reply').detach());
				$('#reply_to').val($(el).attr('post_id'));
				$('#conv_reply').children('input[type=text]').focus();
				tinyMCE.init({
					mode: "textareas",
					theme: "simple",
					width: '540',
					height: '200'
				});
			}
		);
	});
	$('.nosub').click(subreq);
	$('.yessub').click(unsubreq);
	
	tinyMCE.init({
		mode: "textareas",
		theme: "advanced",
		width: '540',
		height: '200',
		theme_advanced_buttons1: "bold,italic,underline,strikethrough,blockquote,|,forecolor,backcolor,|,link,unlink,|,sub,sup,|,formatselect,image,code,hr",
		theme_advanced_buttons2: ""
	});
}

function subreq(ev) {
	$.post(
		'./',
		{'action': 'subscribe'},
		function(data, textStatus, xml) { 
			$(ev.currentTarget).attr('src', '/fugue/bonus/icons-24/star.png').unbind('click', subreq).click(unsubreq);
		}
	);
}

function unsubreq(ev) {
	$.post(
		'./',
		{'action': 'unsubscribe'},
		function(data, textStatus, xml) {
			$(ev.currentTarget).attr('src', '/fugue/bonus/icons-24/star-empty.png').unbind('click', unsubreq).click(subreq);
		}
	);
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
		$('#charactertable .value.editable').each(function(idx, el) {
			var ee = $(el);

			ee.click(function() {
				var old = ee.html();
				var f = $('<form />').attr('method', 'POST');

				var i = $('<input />').attr('type', 'hidden').attr('name', 'action').attr('value', 'updatecharacterkey');
				f.append(i);
				
				var parts = ee.attr('id').split('-');
				i = $('<input />').attr('type', 'hidden').attr('name', 'id').attr('value', parts[1]);
				f.append(i);
				
				i = $('<input />').attr('type', 'text').attr('name', 'value').attr('value', old);
				f.append(i);
				
				i = $('<input />').attr('type', 'submit').attr('value', 'Update');
				f.append(i);
				
				ee.html('');
				ee.append(f);
				
				ee.unbind('click');
			});
		});
		$('#charinfo.editable').click(function() {
			var old = $('#charinfo').html();
			var f = $('<form />').attr('method', 'POST');
			
			var i = $('<input />').attr('type', 'hidden').attr('name', 'action').attr('value', 'updatecharinfo');
			f.append(i);
			
			var t = $('<textarea />');
			t.attr('name', 'body');
			t.html(old);
			f.append(t);
			
			i = $('<input />').attr('type', 'submit').attr('value', 'Update');
			f.append(i);
				
			$('#charinfo').replaceWith(f);
			
			tinyMCE.init({
				mode: "textareas",
				theme: "advanced",
				width: '540',
				height: '200',
				theme_advanced_buttons1: "bold,italic,underline,strikethrough,blockquote,|,forecolor,backcolor,|,link,unlink,|,sub,sup,|,formatselect,image,code,hr",
				theme_advanced_buttons2: ""
			});
		});
		setup_reply();
		if (sub_load) {
			sub_load();
		}
	}
);
