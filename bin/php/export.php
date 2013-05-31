#!/usr/bin/env php
<?php
/**
 * @package StrakerIntegration
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    28 May 2012
 **/

ini_set( 'memory_limit', '512M' );

require 'autoload.php';

$cli = eZCLI::instance();
$cli->setUseStyles( true );

$scriptSettings = array();
$scriptSettings['description']    = 'Exports Info about content objects';
$scriptSettings['use-session']    = false;
$scriptSettings['use-modules']    = false;
$scriptSettings['use-extensions'] = true;

$script = eZScript::instance( $scriptSettings );
$script->startup();
$script->initialize();
$options = $script->getOptions(
	'[classes:][language:][parent_node_ids:][exclude_parent_node_ids:][file:]',
	'',
 	array(
 		'classes'                 => 'List of content class identifiers (separated by comma)',
	 	'language'                => 'Locale code which will be used for export (current local will be used by default)',
 		'parent_node_ids'         => 'List of parent node IDs (separated by comma)',
 		'exclude_parent_node_ids' => 'List of exclude parent node IDs (separated by comma)',
 		'file'                    => 'File in which export results will be saved'
	 )
);

$classes = $options['classes'] !== null ? explode( ',', $options['classes'] ) : array();
if( count( $classes ) === 0 ) {
	$cli->error( 'You should specify content class identifiers' );
	$script->shutdown( 1 );
}

$language             = $options['language'] !== null ? $options['language'] : eZLocale::currentLocaleCode();
$parentNodeIDs        = $options['parent_node_ids'] !== null ? explode( ',', $options['parent_node_ids'] ) : array( 1 );
$excludeParentNodeIDs = $options['exclude_parent_node_ids'] !== null ? explode( ',', $options['exclude_parent_node_ids'] ) : array();
$filename             = $options['file'] !== null
	? $options['file']
	: 'var/straker_export_' . $language . '_' . md5( rand() . '-' . microtime( true ) ). '.xml';

$data            = array();
$allowedDatatyps = array(
	'ezstring',
	'eztext',
	'ezxmltext'
);
foreach( $classes as $classIdentifier ) {
	$memoryUsage = number_format( memory_get_usage( true ) / ( 1024 * 1024 ), 2 );
	$output      = 'Memory usage: ' . $memoryUsage . ' Mb';
	$cli->output( $output );

	$class = eZContentClass::fetchByIdentifier( trim( $classIdentifier ) );
	if( $class instanceof eZContentClass === false ) {
		$cli->error( 'Can not fetch "' . $classIdentifier . '" content class...' );
		continue;
	}

	$cli->output( 'Processing "' . $classIdentifier . '" content class' );

	$exportAttributes = array();
	$dataMap          = $class->attribute( 'data_map' );
	foreach( $dataMap as $attribute ) {
		if(
			(bool) $attribute->attribute( 'can_translate' )
			&& in_array( $attribute->attribute( 'data_type_string' ), $allowedDatatyps )
		) {
			$exportAttributes[] = $attribute->attribute( 'identifier' );
		}
	}

	$nodes       = array();
	$fetchParams = array(
        'Depth'            => false,
        'Limitation'       => array(),
        'LoadDataMap'      => false,
        'AsObject'         => true,
        'ClassFilterType'  => 'include',
        'ClassFilterArray' => array( $class->attribute( 'identifier' ) ),
        'Language'         => $language
    );
    if( count( $excludeParentNodeIDs ) > 0 ) {
		$fetchParams['ExtendedAttributeFilter'] = array(
			'id'     => 'exclude_parent_node_ids',
			'params' => array( 'node_ids' => $excludeParentNodeIDs )
		);
	}

	foreach( $parentNodeIDs as $parentNodeID ) {
		$nodes = array_merge(
			$nodes,
			eZContentObjectTreeNode::subTreeByNodeID( $fetchParams, (int) trim( $parentNodeID ) )
		);
	}

	foreach( $nodes as $node ) {
		$object = $node->attribute( 'object' );
		if( $object instanceof eZContentObject === false ) {
			continue;
		}

		$dataMap          = $object->attribute( 'data_map' );
		$objectAttriubtes = array();
		foreach( $exportAttributes as $attributeIdentifier ) {
			$objectAttriubtes[ $attributeIdentifier ] = $dataMap[ $attributeIdentifier ]->attribute( 'data_text' );
		}

		$data[] = array(
			'id'               => $object->attribute( 'id' ),
			'remote_id'        => $object->attribute( 'remote_id' ),
			'class_identifier' => $class->attribute( 'identifier' ),
			'language'         => $object->attribute( 'current_language' ),
			'attributes'       => $objectAttriubtes
		);

		eZContentObject::clearCache( $object->attribute( 'id' ) );
		$object->resetDataMap();
	}
}

$memoryUsage = number_format( memory_get_usage( true ) / ( 1024 * 1024 ), 2 );
$output      = 'Memory usage: ' . $memoryUsage . ' Mb';
$cli->output( $output );
$cli->output( 'Saving export results...' );

$doc = new DOMDocument( '1.0', 'UTF-8' );
$doc->formatOutput = true;

$root = $doc->createElement( 'all' );
$doc->appendChild( $root );

foreach( $data as $item ) {
	$entry = $doc->createElement( 'entry' );
	$entry->setAttribute( 'id', $item['id'] );
	$entry->setAttribute( 'remote_id', $item['remote_id'] );
	$entry->setAttribute( 'type', $item['class_identifier'] );
	$entry->setAttribute( 'language', $item['language'] );

	foreach( $item['attributes'] as $identifier => $value ) {
		$field = $doc->createElement( 'field' );
		$field->setAttribute( 'name', $identifier );
		$field->appendChild( $doc->createCDATASection( $value ) );
		$entry->appendChild( $field );
	}

	$root->appendChild( $entry );
}

if( $doc->save( $filename ) === false ) {
	$cli->error( 'Can not save "' . $filename . '" file' );
} else {
	$cli->output( 'Data is exported to "' . $filename . '" file' );
}

$script->shutdown( 0 );
