<h3>{$title} &raquo; {$object->toLink()} &raquo; Todos</h3>
{if $object->getTypeId() == 2 && $object->userIsUnder()}
	{include file="project-sidebar.tpl" project=$object}
	<div id="project-left-wrapper">
{/if}
<div class="project-left-wrapper">
	<div class="bio-wrapper inside-wrapper">
		<div class="header-wrap">
			<div class="header-top-line">
				Todos
				{if $object->isOwned()}
					<a href="#" class="top button">New</a>
					<a href="#" class="top button">Edit</a>
				{/if}
			</div>
		</div>
		<table cellspacing="0" cellpadding="0">
			<tr>
				<th>Name</th>
				<th>Priority</th>
				<th>Creator</th>
				<th>Assigned To</th>
			</tr>
			{foreach from=$object->getTodos(4) item=todo}
				<tr>
					<td class="inside-entry">{$todo->toLink()}</td>
					<td class="inside-entry">{$todo->getPriorityName()}</td>
					{assign var=creator value=$todo->getCreator()}
					<td class="inside-entry">{$creator->toLink()}</td>
					{assign var=assigned value=$todo->getAssigned()}
					<td class="inside-entry">{$assigned->toLink()}</td>
				</tr>
			{foreachelse}
				<tr>
					<td class="inside-entry">
						There are no todos in this project.
					</td>
				</tr>
			{/foreach}
		</table>
	</div>
</div>
{if $object->getTypeId() == 2 && $object->userIsUnder()}
	</div>
{/if}