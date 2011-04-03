<h3>Signup</h3>
{if $errors}
	{foreach from=$errors item=error}
		{$error}
	{/foreach}
{/if}
<form method="POST" id="signupform" action="/signup/first">
	<div class="inner">
		<table>
			<tr>
				<td valign="bottom" width="50%">
					<table width="100%">
						<tr>
							<th>Email:</th>
							<td><input type="email" name="email" /></td>
						</tr>
						<tr>
							<td></td>
							<td class="sub">I would sell a kidney before I would sell your email address. This is also your login.</td>
						</tr>
						<tr>
							<th>Password:</th>
							<td><input type="password" name="pass1" /></td>
						</tr>
						<tr>
							<th>Again:</th>
							<td><input type="password" name="pass2" /></td>
						</tr>
					</table>
				</td>
				<td valign="bottom" class="sub">
					<p>If you quick create now, the following choices will be made for you:</p>
					<ul>
						<li>You will be a private member.</li>
						<li>Your biography will be blank.</li>
						<li>You will not have any listed skills.</li>
						<li>You will not have any listed websites.</li>
						<li>You will not be listed as a PM.</li>
					</ul>
				</td>
			</tr>
		</table>
	</div>
	<div class="bottombar">
		<a href="#" class="bottombutton preferred">Step Two</a>
		<a href="#" class="bottombutton skip">Skip The Rest</a>
	</div>
</form>