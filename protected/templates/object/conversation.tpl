<h3>{$title} &raquo; {$object->toLink()} &raquo; Conversation</h3>
{if $object->getTypeId() == 2 && $object->userIsUnder()}
	{include file="project-sidebar.tpl" project=$object}
	<div id="project-left-wrapper">
{/if}
{foreach from=$object->getSubObjects(8) item=comment}
	{include file="object/conversation-single.tpl" object=$comment}
{/foreach}
{if $object->getTypeId() == 2 && $object->userIsUnder()}
	</div>
{/if}
