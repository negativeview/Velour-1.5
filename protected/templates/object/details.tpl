<h3>{$object->getMenuTitle()} &raquo; {$object->getName()}</h3>
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
		<div class="inside-wrapper comments">
			<div class="header-wrap">
				<a href="#" class="button top edit" editable="add-comment">New</a>
				<div class="header-top-line">Comments</div>
			</div>
			<div class="inside-entry">
				<div class="left-side">
					{$logged_in_user->getImage()}
				</div>
				<div class="right-side">
					<div id="add-comment"></div>
				</div>
			</div>
			{include file='object/comments.tpl' object=$object}
		</div>
	{/if}
