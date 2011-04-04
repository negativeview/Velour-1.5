<h3>Projects &raquo; {$project->getName()}</h3>
{if $project->userIsUnder()}
	{include file="project-sidebar.tpl" project=$project}
	<div id="project-left-wrapper">
{/if}
	<div class="bio-wrapper inside-wrapper">
		<div class="header-wrap">
			<div class="header-top-line">
				Biography
				{if $project->isOwned()}
					<a href="#" class="top button">Edit</a>
				{/if}
			</div>
		</div>
		<div class="biography-entry inside-entry">{$project->getBraggable()}</div>
	</div>
	<div class="roster-wrapper inside-wrapper">
		<div class="header-wrap">
			<div class="header-top-line">
				Roster
				{if $project->isOwned()}
					<a href="#" class="top button">Edit</a>
				{/if}
			</div>
		</div>
		<div style="overflow: auto;">
			{foreach from=$project->getSubObjects(1) item=user}
				<div class="roster-entry inside-entry" style="float: left;">
					{$user->getImage()}
				</div>
			{foreachelse}
				<div class="roster-entry inside-entry">
					There are no users in this project.
				</div>
			{/foreach}
		</div>
	</div>
	<div class="log-wrapper inside-wrapper">
		<div class="header-wrap">
			<div class="header-top-line">Activity</div>
		</div>
		{foreach from=$project->getLogs() item=log}
			<div class="log-entry inside-entry">
				{$log->toHTML()}
			</div>
		{foreachelse}
			<div class="log-entry inside-entry">
				There is no activity to report.
			</div>
		{/foreach}
	</div>
{if $project->userIsUnder()}
</div>
{/if}