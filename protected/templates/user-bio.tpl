<h3>{$user->getName()}</h3>
{$user->getImage()}
<div class="todo-body-wrapper inside-wrapper">
	<div class="header-wrap">
		{if $user->isEditable()}
			<a href="#" class="button top edit" editable="user-bio">Edit</a>
		{/if}
		<div class="header-top-line">Biography</div>
	</div>
	<div class="inside-entry" id="user-bio">
		{$user->getBraggable()}
	</div>
</div>
{include file="object/comments.tpl" object=$user}