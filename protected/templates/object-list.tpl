<h3>{$title}</h3>
<table cellspacing="0" cellpadding="0" class="obj-list">
	<tr class="header-wrap">
		<td class="image">Image</td>
		<td>Name</td>
		<td>Created</td>
		<td>Owner</td>
		<td>Actions</td>
	</tr>
	{assign var=s value=`$page_number-1`}
	{assign var=start value=`$s*$per_page`}
	{section start=$start loop=$objects max=$per_page name="objectloop"}
		{assign var=object value=`$objects[$smarty.section.objectloop.index]`}
		<tr>
			<td class="img">{$object->getImage()}</td>
			<td>{$object->toLink()}</td>
			<td>{$object->getCreated()}</td>
			<td>{if $object->getOwner()}{assign var=owner value=$object->getOwner()}{$owner->toLink}{/if}</td>
			<td>
				<a href="{$object->getDiscussURL()}">Discuss</a>
				<a href="{$object->getLogURL()}">Log</a>
				<a href="{$object->getSubscribeURL()}">Subscribe</a>
			</td>
		</tr>
	{/section}
</table>
{if $page_number != 1}
	<a href="?page={$page_number-1}">&lt;</a>
{/if}
Showing {$start+1} - {$smarty.section.objectloop.index} of {$object_count}
{if $max_pages > $page_number}
	<a href="?page={$page_number+1}">&gt;</a>
{/if}
