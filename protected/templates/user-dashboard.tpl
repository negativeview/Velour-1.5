<h3>Dashboard</h3>

<div class="todo-body-wrapper inside-wrapper">
	<div class="header-wrap">
		<div class="header-top-line">Projects</div>
	</div>
	<div style="overflow: auto;">
		{foreach from=$user->getSubObjects(2) item=project}
			<a href="{$project->toURL()}" title="{$project->getName()}">{$project->getImage()}</a>
		{/foreach}
	</div>
</div>

<div class="todo-body-wrapper inside-wrapper">
	<div class="header-wrap">
		<div class="header-top-line">ToDos</div>
	</div>
	{foreach from=$user->getSubObjects(4) item=todo}
		<div class="inside-entry">
		{$todo->toLink()}
		</div>
	{/foreach}
</div>