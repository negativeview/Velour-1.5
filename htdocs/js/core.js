$(document).ready(
	function() {
		$('#homelink').hover(
			function() {
				$('#homelink').stop().animate(
					{
						top: -40
					},
					1000
				)
			},
			function() {
				$('#homelink').stop().animate(
					{
						top: -20
					},
					1000
				);
			}
		);
		$('.option input').focusin(
			function(event) {
				$(event.target).parent().addClass('focus');
			}
		).focusout(
			function(event) {
				$(event.target).parent().removeClass('focus');
			}
		);
		
		$('.optional').each(
			function(idx, el) {
				var el = $(el);
				el.children().css('display', 'none');
				el.width(
					(el.width() - 16) + 'px'
				);
				el.css('float', 'right');
				el.css('marginBottom', '20px');
				
				var a = $('<a />');
				a.html('&gt;');
				a.css('display', 'block');
				a.css('width', '16px');
				a.css('float', 'left');
				a.css('position', 'relative');
				a.css('top', '-9px');
				a.css('textAlign', 'center');
				a.css('cursor', 'pointer');
				a.click(function() {
					el.children().toggle();
					if (el.children().first().css('display') == 'none') {
						a.html('&gt;');
					} else {
						a.html('v');
					}
				});
				
				a.insertBefore(el);
				
//				el.css('display', 'none');
			}
		);
		
		/*
		$('.projects').each(
			function(idx, el) {
				var project = $(el);
				var hiders = project.children('a:gt(4)');
				if (hiders.length) {
					hiders = project.children('a:gt(3)');
					hiders.css('display', 'none');
					
					var a = $('<a>');
					a.css('cursor', 'pointer');
					a.click(function() {
						hiders.slideToggle();
					});
					a.html('-more-');
					project.append(a);
				}
			}
		);
		*/
		
		var num_elements = 10;
		
		var one_el_height = parseInt($('.twocol a').css('height')) + (parseInt($('.twocol a').css('paddingTop')) * 2);
		
		// Hide overflow links. This will show exactly four links in Chrome at least.
		// TODO: Check the other browsers.
		$('.twocol div div').css('height', (one_el_height * num_elements) + 'px').css('overflow', 'hidden');
		
		$('.twocol div div').each(function(idx, el) {
			var el = $(el);
			
			// We hid some stuff.
			if (el.children('a').length > num_elements) {
				var pages = Math.ceil(el.children('a').length / num_elements);
				
				var d = $('<div />');
				d.addClass('paginate');
				if (pages > 11)
					pages = 11;
				
				var a = $('<a />');
				a.append('<<');
				d.append(a);
				for (var i = 1; i < (pages + 1); i++) {
					var a = $('<a />');
					if (i == 1)
						a.addClass('active');
					a.append(i);
					d.append(a);
				}
				var a = $('<a />');
				a.append('>>');
				d.append(a);
				var height = $(el).parent().height();
				$(this).parent().append(d);
				height = height + d.height() + parseInt(d.css('bottom')) + 6;
				$(this).parent().css('position', 'relative').css('height', height + 'px');
			}
		});
	}
);