<h3>{$title} &raquo; {$object->toLink()} &raquo; Logs</h3>
{if $object->getTypeId() == 2 && $object->userIsUnder()}
	{include file="project-sidebar.tpl" project=$object}
	<div id="project-left-wrapper">
{/if}
{foreach from=$object->getLogs() item=log}
	{$log->toHTML($this)}
{/foreach}
{if $object->getTypeId() == 2 && $object->userIsUnder()}
	</div>
{/if}
