			</div>
		</div>
		{if isset($current_user) && $current_user->superUser() && $directory == 'alpha'}
			{if count($queries)}
				<div>
					<b style="padding-left: 5px;">Queries: {$queries|@count}</b>
					{foreach from=$queries item=query}
						<div style="padding: 5px;">
							<i>{$query.time|string_format:"%0.4f"}</i> {$query.query}
						</div>
					{/foreach}
				</div>
			{/if}
		{/if}
	</body>
</html>