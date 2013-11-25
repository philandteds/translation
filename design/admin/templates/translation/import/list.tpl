{literal}
<style type="text/css">
form.job_list table td {vertical-align: top;padding-top: 10px;}
form.job_list table td ul {margin-top: 0px;}
</style>
{/literal}

{if $errors|count|gt( 0 )}
<div class="message-error">
	<h2><span class="time">[{currentdate()|l10n( shortdatetime )}]</span> {'Following errors occurred:'|i18n( 'extension/translation' )}</h2>
	<ul>
		{foreach $errors as $error}
		<li>{$error}</li>
		{/foreach}
	</ul>
</div>
{/if}

{if $new_job}
<div class="message-feedback">
	<h2><span class="time">[{currentdate()|l10n( shortdatetime )}]</span> {'Thanks! Your translations are being imported - when ready, you’ll receive an email notification.'|i18n( 'extension/translation' )}</h2>
</div>
{/if}

<div class="context-block">

	<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">
		<h1 class="context-title">&nbsp;{'New translation import'|i18n( 'extension/translation' )}</h1>
		<div class="header-subline"></div>
	</div></div></div></div></div></div>

	<form action="{'import_translations/list'|ezurl( 'no' )}" method="post" enctype="multipart/form-data">
		<div class="box-content"><div class="context-attributes"><div class="object">
			<div class="block">
				<label>{'Import file'|i18n( 'extension/translation' )}:</label>
				<input type="file" name="import_file" />
			</div>
		</div></div></div>
		
		<div class="controlbar">
			<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">
				<div class="block">
					<input class="button" type="submit" name="NewButton" value="{'Upload'|i18n( 'extension/translation' )}" title="{'Upload new import file'|i18n( 'extension/translation' )}">
				</div>
			</div></div></div></div></div></div>
		</div>
	</form>

</div>

<div class="context-block">

	<div class="box-header"><div class="box-tc"><div class="box-ml"><div class="box-mr"><div class="box-tl"><div class="box-tr">
		<h1 class="context-title">&nbsp;{'Translation import jobs'|i18n( 'extension/translation' )} ({$jobs|count})</h1>
		<div class="header-subline"></div>
	</div></div></div></div></div></div>

	<form class="job_list" name="jobs_list" action="{'import_translations/list'|ezurl( 'no' )}" method="post">
		<div class="box-ml"><div class="box-mr"><div class="box-content">
			{if $jobs|count|gt( 0 )}
				<table class="list" cellspacing="0" cellpadding="0">
					<thead>
						<tr>
							<th class="tight">
								<img src="{'toggle-button-16x16.gif'|ezimage( 'no' )}" alt="{'Invert selection.'|i18n( 'extension/translation' )}" title="{'Invert selection.'|i18n( 'extension/translation' )}" onclick="ezjs_toggleCheckboxes( document.jobs_list, 'JobIDs[]' ); return false;">
							</th>
							<th class="tight">{'ID'|i18n( 'extension/translation' )}</th>
							<th>{'File'|i18n( 'extension/translation' )}</th>
							<th>{'Status'|i18n( 'extension/translation' )}</th>
							<th>{'Language'|i18n( 'extension/translation' )}</th>
							<th>{'Creator'|i18n( 'extension/translation' )}</th>
							<th>{'Created'|i18n( 'extension/translation' )}</th>
						</tr>
					</thead>
					<tbody>
						{foreach $jobs as $job sequence array( 'bgdark', 'bglight' ) as $style }
						<tr class="{$style}">
							<td><input type="checkbox" name="JobIDs[]" value="{$job.id}" /></td>
							<td>{$job.id}</td>
							<td><a href="{concat( 'import_translations/download'|ezurl( 'no' ), '/', $job.id )}" target="blank">{$job.file}</a></td>
							<td>{$job.status_string}</td>
							<td>{$job.language}</td>
							<td>{if $job.creator}<a href="{$job.creator.main_node.url_alias|ezurl( 'no' )}">{$job.creator.name}</a>{else}{'Creator is removed'|i18n( 'extension/translation' )}{/if}</td>
							<td>{$job.created_at|datetime( 'custom', '%d.%m.%Y %H:%i:%s' )}</td>
						</tr>
						{/foreach}
					</tbody>
				</table>
			{/if}
		</div></div></div>

		<div class="controlbar">
			<div class="box-bc"><div class="box-ml"><div class="box-mr"><div class="box-tc"><div class="box-bl"><div class="box-br">
				<div class="block">
					{if $jobs|count|gt( 0 )}<input class="button" type="submit" name="RemoveButton" value="{'Remove selected'|i18n( 'extension/translation' )}" title="{'Remove selected'|i18n( 'extension/translation' )}" onclick="return confirm('{'Do you really want to remove selected records?'|i18n( 'extension/translation' )}');">{/if}
				</div>
			</div></div></div></div></div></div>
		</div>
	</form>

</div>
