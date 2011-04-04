<h3>Todo &raquo; {$todo->getName()}</h3>
<div class="todo-body-wrapper inside-wrapper">
	<div class="header-wrap">
		<div class="header-top-line">
			Summary
			{if $todo->isOwned()}
				<a href="#" class="top button">Edit</a>
			{/if}
		</div>
	</div>
	<div class="todo-body-entry inside-entry">{$todo->getBraggable()}</div>
</div>
<div class="tags-wrapper inside-wrapper">
	<div class="header-wrap">
		<div class="header-top-line">
			Tagged
			{if $todo->isOwned()}
				<a href="#" class="top button">Edit</a>
			{/if}
		</div>
	</div>
	<div class="tags-entry inside-entry">
		{foreach from=$todo->getObjectsUnder() item=object}
			{$object->toLink()}
		{/foreach}
	</div>
</div>
{include file='object/comments.tpl' object=$todo}
