<?php
/**
 * @package Translation
 * @class   TranslationExtendedAttributeFilters
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    31 May 2013
 **/

class TranslationExtendedAttributeFilters
{
	public static function excludeParentNodeIDs( $params ) {
		$conditions = array();
		$return     = array();

		foreach( $params['node_ids'] as $nodeID ) {
			$conditions[] = 'ezcontentobject_tree.path_string NOT LIKE CONCAT( \'%/\', ' . $nodeID . ' , \'/%\' )'; 
		}

		if( count( $conditions ) > 0 ) {
			$return = array(
				'joins' => implode( ' AND ', $conditions ) . ' AND '
			);
		}
		return $return;
	}
}
