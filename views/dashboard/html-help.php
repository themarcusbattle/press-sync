<div class="wrap about-wrap press-sync">
    <?php \Press_Sync\Press_Sync::init()->include_page( 'dashboard/nav' ); ?>
	<div id="cli-commands" style="max-width: 800px; margin-bottom: 20px;">
		<h3>Credentials</h3>
		<p class="question">Q: What is a Press Sync Key?</p>
		<p>A: Your Press Sync Key is a password that your site uses to allow other WordPress sites to send data to it. If we didn't have this key in place, then any website using Press Sync could push content into your website. We definitely wouldn't want that!</p>
	</div>
	<div id="cli-commands" style="max-width: 800px; margin-bottom: 20px;">
		<h3>WP-CLI Commands (For Advanced Users)</h3>
		<p class="question">Q: What is WP-CLI?</p>
		<p>A: WP-CLI is the command-line interface for WordPress. You can update plugins, configure multisite installs and much more, without using a web browser.</p>
		<p class="question">Q: What are the CLI Commands available for Press Sync?</p>
		<ul>
			<li>wp press-sync posts</li>
			<li>wp press-sync media</li>
			<li>wp press-sync users</li>
			<li>wp press-sync options</li>
		</ul>
		<p>Each command above has additional paraemters that can be vewied by passing in the --prompt argument.</p>
	</div>
	<div id="json-import" style="max-width: 800px; margin-bottom: 20px;">
		<h3>Local JSON Import (For Advanced Users)</h3>
		<p class="question">Q: What is "Local JSON Import"?</p>
		<p>A: Local JSON Import is a CLI parameter that allows you to import/sync content with any of the existing CLI commands mentioned above.</p>
		<p class="question">Q: How do I use "Local JSON Import"?</p>
		<p>A: If you have a JSON export of your site, then using the --local_folder parameter with any CLI command, input the folder path and run the command.</p>
	</div>
	<hr />
</div>
<style>
	p.question {
		font-weight: bold;
	}
</style>
