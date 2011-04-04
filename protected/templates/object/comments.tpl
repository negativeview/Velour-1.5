{foreach from=$object->getSubObjects(11) item=comment}
	{include file="comment-single.tpl" object=$comment}
{/foreach}