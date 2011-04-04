<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">		
<html>
	<head>
		<title>ARG Technologist - {$directory}</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<link rel="alternate" type="application/rss+xml" title="RSS" href="http://feeds.feedburner.com/ArgTechnologist" />
		<link href='/style.css' rel='stylesheet' type='text/css' />
		{if $current_user}
		<link type="image/x-icon" rel="icon" href="{$current_user->favicon()}" />
		{else}
		<link type="image/x-icon" rel="icon" href="/images/favicon-dark.png" />
		{/if}

		<script type="text/javascript" src="/js/jquery.js"></script>
		<script type="text/javascript" src="/js/jquery.mousewheel.js"></script>
		<script type="text/javascript">
			{literal}
			var _gaq = _gaq || [];
			_gaq.push(['_setAccount', 'UA-17366506-1']);
			_gaq.push(['_trackPageview']);
			
			(function() {
				var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
				ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
				var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
				
				var tmce = document.createElement('script'); tmce.type = 'text/javascript'; tmce.async = true;
				tmce.src = '/js/tiny_mce.js';
				s.parentNode.insertBefore(tmce, s);
				
				$(tmce).load(function() {
					var actions = document.createElement('script'); actions.type = 'text/javascript'; actions.async = true;
					actions.src = '/js/actions.js';
					s.parentNode.insertBefore(actions, s);					
				});
			})();
			{/literal}
		</script>
		<!-- This site is, and always will be, out of game for all client ARGs. That does not mean I can't use it for my own purposes. -->
	</head>
	<body>
		<div id="loginout">
			<a href="/user/">Users</a>
			<a href="/project/">Projects</a>
			{if $logged_in_user}
				<a href="/logout/">Logout</a>
			{else}
				<a href="/login/">Login</a>
				<a href="/signup/">Signup</a>
			{/if}
		</div>
		<div id="header">
			<h1><a href="/"><img src="/logo.png" /></a></h1>
			<h2>{$phrase}</h2>
		</div>
		<div id="wrapper">
			{if rand(1, 200) == 42}
				<div id="speechbubble"><div>
				Hello, my name is Percy. I'm here to help.
				</div></div>
			{/if}
			<div class="main">