{default $urls_type = 'relative'}
{def
	$node_path            = array()
	$recursion_protection = 0
}
{do}
	{set $node_path = $node_path|append( concat( '<a href="', $node.url_alias|ezurl( 'no', $urls_type ), '">', $node.name, '</a>' ) )}
	{set $recursion_protection = $recursion_protection|sum( 1 )}
	{set $node = $node.parent}
{/do while and( $recursion_protection|lt( 50 ), $node.depth|gt( 1 ) )}
{foreach $node_path|reverse as $node_link}{$node_link}{delimiter} / {/delimiter}{/foreach}
{undef $node_path}