#!/usr/bin/env php
<?php
/**
 * @package Translation
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
$options = $script->getOptions(
	'[classes:][language:][parent_node_ids:][exclude_parent_node_ids:][file:][export_handler:][target_language:][exclude_target_language][use_siteaccess_languages]',
	'',
 	array(
 		'classes'                  => 'List of content class identifiers (separated by comma)',
	 	'language'                 => 'Locale code which will be used for export (current local will be used by default)',
 		'parent_node_ids'          => 'List of parent node IDs (separated by comma)',
 		'exclude_parent_node_ids'  => 'List of exclude parent node IDs (separated by comma)',
 		'file'                     => 'File in which export results will be saved',
 		'export_handler'           => 'Export handler (defualt value is StrakerExportHandler)',
 		'target_language'          => 'Target locale code (used only in XLIFF export handler, current local will be used by default)',
 		'exclude_target_language'  => 'Exclude objects which source language is equal to target language (disabled by default)',
		'use_siteaccess_languages' => 'If this option is not set, only source language will be used to fetch content. Otherwise all languages of specified siteaccess will be used'
	 )
);
$script->initialize();
$script->startup();

// Checking classes
$classes = $options['classes'] !== null ? explode( ',', $options['classes'] ) : array();
if( count( $classes ) === 0 ) {
	$cli->error( 'You should specify content class identifiers' );
	$script->shutdown( 1 );
}

// Checking export handler
$exportHandler        = $options['export_handler'] !== null ? $options['export_handler'] : 'StrakerExportHandler';
$isWrongExportHandler = false;
if( class_exists( $exportHandler ) === false ) {
	$isWrongExportHandler = true;
} else {
	$reflector = new ReflectionClass( $exportHandler );
	if( $reflector->isSubclassOf( 'TranslationExportHandler' ) === false ) {
		$isWrongExportHandler = true;
	}
}
if( $isWrongExportHandler ) {
	$cli->error( '"' . $exportHandler . '" is not valid export handler' );
	$script->shutdown( 1 );
}

// Checking the rest options
$language               = $options['language'] !== null ? $options['language'] : eZLocale::currentLocaleCode();
$parentNodeIDs          = $options['parent_node_ids'] !== null ? explode( ',', $options['parent_node_ids'] ) : array( 1 );
$excludeParentNodeIDs   = $options['exclude_parent_node_ids'] !== null ? explode( ',', $options['exclude_parent_node_ids'] ) : array();
$targetLanguage         = $options['target_language'] !== null ? $options['target_language'] : eZLocale::currentLocaleCode();
$filename               = $options['file'] !== null
	? $options['file']
	: 'var/translation_export_' . $targetLanguage . '_' . md5( rand() . '-' . microtime( true ) ). '.xlf';
$excludeTargetLang      = $options['exclude_target_language'] === true;
$useSiteaccessLanguages = $options['use_siteaccess_languages'] === true;

// Collection the data
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
        'ClassFilterArray' => array( $class->attribute( 'identifier' ) )
    );
	if( $useSiteaccessLanguages === false ) {
		$fetchParams['Language'] = $language;
	}
    if( count( $excludeParentNodeIDs ) > 0 ) {
		$fetchParams['ExtendedAttributeFilter'] = array(
			'id'     => 'exclude_parent_node_ids',
			'params' => array( 'node_ids' => $excludeParentNodeIDs )
		);
	}

	foreach( $parentNodeIDs as $parentNodeID ) {
		$parentNodeID = (int) trim( $parentNodeID );
		$parentNode   = eZContentObjectTreeNode::fetch( $parentNodeID );

		$nodes = array_merge(
			$nodes,
			eZContentObjectTreeNode::subTreeByNodeID( $fetchParams, $parentNodeID )
		);

		// Include parent node
		if(
			$parentNode instanceof eZContentObjectTreeNode
			&& $parentNode->attribute( 'class_identifier' ) == $classIdentifier
		) {
			$nodes = array_merge( $nodes, array( $parentNode ) );
		}

	}

	foreach( $nodes as $node ) {
		$object = $node->attribute( 'object' );
		if( $object instanceof eZContentObject === false ) {
			continue;
		}

		$dataMap          = $object->attribute( 'data_map' );
		$objectAttriubtes = array();
		foreach( $exportAttributes as $attributeIdentifier ) {
			$objectAttriubtes[ $attributeIdentifier ] = array(
				'type'    => $dataMap[ $attributeIdentifier ]->attribute( 'data_type_string' ),
				'content' => $dataMap[ $attributeIdentifier ]->attribute( 'data_text' )
			);
		}

		if(
			$excludeTargetLang
			&& $object->attribute( 'current_language' ) == $targetLanguage
		) {
			continue;
		}

		$data[] = array(
			'id'               => $object->attribute( 'id' ),
			'remote_id'        => $object->attribute( 'remote_id' ),
			'class_identifier' => $class->attribute( 'identifier' ),
			'name'             => $object->attribute( 'name' ),
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

$callback = array( $exportHandler, 'save' );
if( call_user_func( $callback, $data, $filename, $language, $targetLanguage ) === false ) {
	$cli->error( 'Can not save "' . $filename . '" file' );
} else {
	$cli->output( 'Data is exported to "' . $filename . '" file' );
}

$script->shutdown( 0 );
