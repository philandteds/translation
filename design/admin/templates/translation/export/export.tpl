{if $erros|count|gt( 0 )}
<div class="message-error">
	<h2><span class="time">[{currentdate()|l10n( shortdatetime )}]</span> {'Following errors occurred:'|i18n( 'extension/translation' )}</h2>
	<ul>
		{foreach $erros as $error}
		<li>{$error}</li>
		{/foreach}
	</ul>
</div>
{/if}

<form method="post" action="{'export_translations/export'|ezurl( 'no' )}">

	<div class="box-header">
		<h1 class="context-title">{'New translations export'|i18n( 'extension/translation' )}</h1>
		<div class="header-mainline"></div>
	</div>

	<div class="box-content">
		<div class="context-attributes">
			<div class="object">

				<div class="block">
    				<label>{'Subtrees'|i18n( 'extension/translation' )}:</label>
					{if $job.parent_nodes|count|eq( 0 )}
						<p>{'All nodes'|i18n( 'extension/translation' )}</p>
					{else}
						<ul>
							{foreach $job.parent_nodes as $node}
								<li>{include uri='design:translation/node_path.tpl' node=$node} <a href="{concat( 'export_translations/export'|ezurl( 'no' ), '?action=RemoveParentNode&NodeID=', $node.node_id )}"><img src={'trash-icon-16x16.gif'|ezimage()} alt="{'Remove'|i18n( 'extension/translation' )}"/></a></li>
							{/foreach}
						</ul>
					{/if}
					<input class="button" type="submit" name="BrowseParentNodeButton" value="{'Add node'|i18n( 'extension/translation' )}" />
				</div>

				<div class="block">
    				<label>{'Exclude Subtrees'|i18n( 'extension/translation' )}:</label>
					{if $job.exclude_parent_nodes|count|eq( 0 )}
						<p>{'No any exclude subtrees'|i18n( 'extension/translation' )}</p>
					{else}
						<ul>
							{foreach $job.exclude_parent_nodes as $node}
								<li>{include uri='design:translation/node_path.tpl' node=$node} <a href="{concat( 'export_translations/export'|ezurl( 'no' ), '?action=RemoveExcludeParentNode&NodeID=', $node.node_id )}"><img src={'trash-icon-16x16.gif'|ezimage()} alt="{'Remove'|i18n( 'extension/translation' )}"/></a></li>
							{/foreach}
						</ul>
					{/if}
					<input class="button" type="submit" name="BrowseExcludeParentNodeButton" value="{'Add node'|i18n( 'extension/translation' )}" />
				</div>
				
				<div class="block">
    				<label>{'Content classes'|i18n( 'extension/pt' )}</label>
					<select name="new_job[classes][]" multiple="multiple" size="10">
						{foreach $classes as $class}
						<option value="{$class.identifier}"{if $job.class_identifiers|contains( $class.identifier )} selected="selected"{/if}>{$class.name|wash}</option>
						{/foreach}
					</select>
				</div>

				<div class="block">
    				<label>{'Language'|i18n( 'extension/pt' )}:</label>
					<select name="new_job[siteaccess]">
						<option value="">{'- Please select -'|i18n( 'extension/translation' )}</option>
						{foreach $siteaccesses as $siteaccess => $locale}
						<option value="{$siteaccess}"{if eq( $siteaccess, $job.siteaccess )} selected="selected"{/if}>{$siteaccess} ({$locale})</option>
						{/foreach}
					</select>
				</div>

			</div>
		</div>
	</div>

	<div class="controlbar">
		<div class="block">
    		<input class="button" type="submit" name="SaveButton" value="{'Create new'|i18n( 'extension/translation' )}" />
			<input class="button" type="submit" name="CancelButton" value="{'Cancel'|i18n( 'extension/translation' )}" />
		</div>
	</div>

</form>