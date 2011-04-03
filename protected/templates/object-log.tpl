<h3>{$title} &raquo; {$object->getName()} &raquo; Logs</h3>
{foreach from=$object->getLogs() item=log}
	{$log->toHTML($this)}
{/foreach}