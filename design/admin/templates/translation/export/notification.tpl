<p>This export contains the following subtrees:</p>
{if $job.parent_nodes|count|eq( 0 )}
<p>All contnet</p>
{else}
<ul>
	{foreach $job.parent_nodes as $node}
	<li>{include uri='design:translation/node_path.tpl' node=$node urls_type='full'}</li>
	{/foreach}
</ul>
{/if}

{if $job.exclude_parent_nodes|count|gt( 0 )}
<p>It excludes:</p>
<ul>
	{foreach $job.exclude_parent_nodes as $node}
	<li>{include uri='design:translation/node_path.tpl' node=$node urls_type='full'}</li>
	{/foreach}
</ul>
{/if}

<p>It includes only these content types:</p>
<ul>
	{foreach $job.content_classes as $class}
	<li>{$class.name}</li>
	{/foreach}
</ul>

<p>And is available for download at <a href="{concat( 'export_translations/download'|ezurl( 'no', 'full' ), '/', $job.id )}" target="blank">{concat( 'export_translations/download'|ezurl( 'no', 'full' ), '/', $job.id )}</a></p>
