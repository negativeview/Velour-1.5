<div style="padding: 5px;">
	<form method="POST">
		<input type="hidden" name="action" value="update_bio" />
		<b>Display Name:</b> <input type="text" name="disp_name" value="{$current_user->name()}" />
		<textarea name="bio">{$current_user->getSetting('bio')|escape}</textarea>
		<input type="submit" value="Save" />
	</form>
	<script>
		{literal}
		tinyMCE.init({
			mode: "textareas",
			theme: "simple",
			width: '538',
			height: '200'
		});
		{/literal}
	</script>
</div>