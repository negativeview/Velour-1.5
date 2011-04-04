<div class="sidebar">
	<div class="header-wrap">
		<a href="#" class="button top">Edit</a>
		<div class="header-top-line">Sidebar</div>
	</div>
	<a href="{$project->getSummaryURL()}">Public Summary Page</a>
	{if $project->userIsUnder()}
		<a href="{$project->getDashboardURL()}">Dashboard</a>

		{assign var=todos value=$project->getToDos()}
		<a href="{$project->getToDoURL()}">To Do Items <div class="sidebarcount">{$todos|@count}</div></a>

		{assign var=milestones value=$project->getSubObjects(6)}
		<a href="#">Milestones <div class="sidebarcount">{$milestones|@count}</div></a>

		{assign var=roster value=$project->getSubObjects(1)}
		<a href="#">Roster <div class="sidebarcount">{$roster|@count}</div></a>

		{assign var=characters value=$project->getSubObjects(5)}
		<a href="#">Characters <div class="sidebarcount">{$characters|@count}</div></a>

		{assign var=files value=$project->getSubObjects(7)}
		<a href="#">Files <div class="sidebarcount">{$files|@count}</div></a>

		{assign var=conversations value=$project->getSubObjects(8)}
		<a href="{$project->getDiscussURL()}">Conversations <div class="sidebarcount">{$conversations|@count}</div></a>

		{assign var=wikis value=$project->getSubObjects(9)}
		<a href="#">Wiki <div class="sidebarcount">{$wikis|@count}</div></a>

		{assign var=vikis value=$project->getSubObjects(10)}
		<a href="#">Viki <div class="sidebarcount">{$vikis|@count}</div></a>
	{/if}
</div>