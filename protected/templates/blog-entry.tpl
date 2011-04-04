<div class="todo-body-wrapper inside-wrapper">
	<div class="header-wrap">
		<div class="header-top-line">
			<a href="/blog/">Blog</a> &raquo; {$blog->getName()}
			{if $blog->isOwned()}
				<a href="#" class="top button">Edit</a>
			{/if}
		</div>
	</div>
	<div class="todo-body-entry inside-entry">{$blog->getBody()}</div>
</div>
{include file='object/comments.tpl' object=$blog}
