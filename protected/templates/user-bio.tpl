<h3>{$user->getName()}</h3>
{$user->getImage()}
<div class="todo-body-wrapper inside-wrapper">
	<div class="header-wrap">
		{if $user == $logged_in_user}
			<a href="#" class="button top">Edit</a>
		{/if}
		<div class="header-top-line">Biography</div>
	</div>
	<div class="inside-entry">
		{$user->getBraggable()}
	</div>
</div>
{include file="object/comments.tpl" object=$user}