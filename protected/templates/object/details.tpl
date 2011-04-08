{assign var=type value=$object->getType()}

<h3>{$type->toLink()} &raquo; {$object->getName()}</h3>
{if $object->userIsUnder()}
	{include file="project-sidebar.tpl" project=$object}
	<div id="project-left-wrapper">
{/if}
	<div class="bio-wrapper inside-wrapper">
		<div class="header-wrap">
			<div class="header-top-line">
				Summary
				<a href="#" class="top button edit" editable="summary">Edit</a>
			</div>
		</div>
		<div class="biography-entry inside-entry" id="summary">{$object->getBraggable()}</div>
	</div>
	{if $type->canHaveChild(11)}
		<div class="inside-wrapper">
			<div class="header-wrap">
				<a href="#" class="button top edit" editable="add-comment">New</a>
				<div class="header-top-line">Comments</div>
			</div>
			<div style="margin: 5px; margin-right: 10px;">
				<div id="add-comment"></div>
			</div>
			{include file='object/comments.tpl' object=$object}
		</div>
	{/if}
{if $object->userIsUnder()}
</div>
{/if}