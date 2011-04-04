<div class="todo-body-wrapper inside-wrapper">
	<div class="header-wrap">
		<div class="header-top-line">
			{$object->getTitle()}
		</div>
	</div>
	<div class="todo-body-entry inside-entry">{$object->getBody()}</div>
	<div class="bottombar">
		{assign var=comments value=$object->getSubObjects(11)}
		<a href="/conversation/{$object->getId()}/comments/" class="button bottom">More ({$comments|@count})</a>
	</div>
</div>