			</div>
		</div>
		{if count($queries)}
			{assign var=time value=0}
			{foreach from=$queries item=query}
				{assign var=time value=`$time+$query.time`}
			{/foreach}
			<div style="clear: both;">
				<b style="padding-left: 5px;">Queries: {$queries|@count}: {$time|string_format:"%0.4f"}</b>
				{foreach from=$queries item=query}
					<div style="padding: 5px;">
						<i>{$query.time|string_format:"%0.4f"}</i> {$query.query}
					</div>
				{/foreach}
			</div>
		{/if}
	</body>
</html>