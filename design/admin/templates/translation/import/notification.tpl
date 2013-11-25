<p>Translation import results can be viewed at:</p>
<ul>
	{foreach $job.affected_siteaccess_urls as $siteacces_url}
	<li><a href="{$siteacces_url}">{$siteacces_url}</a></li>
	{/foreach}
</ul>
