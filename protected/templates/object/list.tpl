<h3>{$title}</h3>
<div class="inside-wrapper">
	<div class="header-wrap">
		<div class="header-top-line">{$title}</div>
	</div>
	<table cellspacing="0" cellpadding="0">
		<tr>
			<th>Name</th>
			<th>Created</th>
			<th>Actions</th>
		</tr>
		{foreach from=$objects item=object}
		<tr>
			<td>{$object->getName()}</td>
			<td>{$object->getCreated()}</td>
			<td>
				<a href="{$object->getDiscussURL()}">Discuss</a>
				<a href="{$object->getLogURL()}">Log</a>
				<a href="{$object->getSubscribeURL()}">Subscribe</a>
			</td>
		</tr>
		{foreachelse}
			<tr>
				<td class="inside-entry">
					I don't see any of those around here...
				</td>
			</tr>
		{/foreach}
	</table>
</div>
