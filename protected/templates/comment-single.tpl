<div class="todo-body-entry inside-entry">
	<div class="left-side">
		{assign var=owner value=$object->getOwner()}
		{$owner->getImage()}
		<div class="created-ts">{$object->getCreated()}</div>
	</div>
	<div class="right-side">
		{$object->getBraggable()}
	</div>
</div>
