<h3>Best of the Best</h3>
{foreach from=$braggables item=braggable}
	<div class="braggable">
		<div class="topbar">
			<div class="name"><a href="{$braggable->toURL()}">{$braggable->getTypeName()}: {$braggable->getName()}</a></div>
			<div class="image">{$braggable->getImage()}</div>
			<div class="subwrap">
				<div class="created">Created: {$braggable->getCreated()}</div>
				{if $braggable->getOwner()}
					{assign var=owner value=$braggable->getOwner()}
					<div class="owner">Owner: {$owner->toLink()}</div>
				{/if}
			</div>
		</div>
		<div class="body">{$braggable->getBraggable()}</div>
		<div class="bottombar">
			<a href="{$braggable->getDiscussURL()}" class="bottom button">Discuss</a>
			<a href="{$braggable->getLogURL()}" class="bottom button">Log</a>
			<a href="{$braggable->getSubscribeURL()}" class="bottom button">Subscribe</a>
		</div>
	</div>
{/foreach}