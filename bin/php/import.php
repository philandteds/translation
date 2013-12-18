#!/usr/bin/env php
<?php
/**
 * @package Translation
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>a
 * @date    22 Jun 2012
 **/

function statusMessage( $cli, $message ) {
	$memoryUsage = number_format( memory_get_usage( true ) / ( 1024 * 1024 ), 2 );
	$output      = 'Memory usage: ' . $memoryUsage . ' Mb';
	$cli->output( $output );
	$cli->output( $message );
}

ini_set( 'memory_limit', '512M' );

require 'autoload.php';

$cli = eZCLI::instance();
$cli->setUseStyles( true );

$scriptSettings = array();
$scriptSettings['description']    = 'Imports content objects translations';
$scriptSettings['use-session']    = true;
$scriptSettings['use-modules']    = true;
$scriptSettings['use-extensions'] = true;

$script = eZScript::instance( $scriptSettings );
$script->startup();
$script->initialize();
$options = $script->getOptions(
	'[source_language:][target_language:][source_file:][import_handler:][translation_creator_id:][default_attributes:]',
	'',
	array(
		'source_language'        => 'Source language',
		'target_language'        => 'Target language',
		'source_file'            => 'Source file, from which translations will be extracted',
		'import_handler'         => 'Export handler (defualt value is XLIFFImportHandler)',
		'translation_creator_id' => 'User object ID behalf who translations will be created',
		'default_attributes'     => 'Attributes which will be copied from the source language'
	)
);

// Login as administrator to have rights to create new translations
$userCreatorID = $options['translation_creator_id'] !== null
	? (int) $options['translation_creator_id']
	: eZINI::instance()->variable( 'UserSettings', 'UserCreatorID' );
$user = eZUser::fetch( $userCreatorID );
if( ( $user instanceof eZUser ) === false ) {
	$cli->error( 'Can not get user object by ID = "' . $userCreatorID . '"' );
	$script->shutdown( 1 );
}
eZUser::setCurrentlyLoggedInUser( $user, $userCreatorID );

// Check parameters
$params = array(
	'source_language' => null,
	'target_language' => null,
	'source_file'     => null
);
foreach( $params as $key => $value ) {
	if( $options[ $key ] === null ) {
		$cli->error( 'Please specify "' . $key . '" parameter' );
		$script->shutdown( 1 );
	}

	$params[ $key ] = $options[ $key ];
}

$defaultAttributes = 'all';
if( isset( $options['default_attributes'] ) ) {
	$defaultAttributes = $options['default_attributes'];
}

$targetLanguage = eZContentLanguage::fetchByLocale( $params['target_language'] );
if( $targetLanguage instanceof eZContentLanguage === false ) {
	$cli->error( '"' . $params['target_language'] . '" is wrong locale code' );
	$script->shutdown( 1 );
}

// Checking import handler
$importHandler        = $options['import_handler'] !== null ? $options['import_handler'] : 'XLIFFImportHandler';
$isWrongImportHandler = false;
if( class_exists( $importHandler ) === false ) {
	$isWrongImportHandler = true;
} else {
	$reflector = new ReflectionClass( $importHandler );
	if( $reflector->isSubclassOf( 'TranslationImportHandler' ) === false ) {
		$isWrongImportHandler = true;
	}
}
if( $isWrongImportHandler ) {
	$cli->error( '"' . $importHandler . '" is not valid import handler' );
	$script->shutdown( 1 );
}

// Extract the translations from source
statusMessage( $cli, 'Parsing translations source file...' );
try{
	$data = call_user_func(
		array( $importHandler, 'extractData' ),
		$params['source_file'],
		$params['source_language'],
		$params['target_language']
	);
} catch( Exception $e ) {
	$cli->error( $e->getMessage() );
	$script->shutdown( 1 );
}

// Importing translations
statusMessage( $cli, 'Importing translations...' );
$counter = 0;
foreach( $data as $item ) {
	$object = eZContentObject::fetch( $item['id'] );
	if( $object instanceof eZContentObject === false ) {
		continue;
	}

	$tmpFiles = array();
	$defaultAttributeValues = array();
	foreach( $object->attribute( 'data_map' ) as $identifier => $attr ) {
		$clasAttr = $attr->attribute( 'contentclass_attribute' );
		if(
			$defaultAttributes === 'non-translatable'
			&& (bool) $clasAttr->attribute( 'can_translate' )
		) {
			continue;
		}

		if( $attr->attribute( 'has_content' ) == false ) {
			continue;
		}

		$attrString = $attr->toString();
		if( in_array( $clasAttr->attribute( 'data_type_string' ), array( 'ezmedia', 'ezbinaryfile' ) ) ) {
			$tmp        = explode( '|', $attrString );
			$file       = $tmp[0];
			$attrString = TranslationImportJob::getStorageDir() . '/' . $tmp[1];

			if(
				file_exists( $file )
				|| (int) @filesize( $file ) === 0
			) {
				eZClusterFileHandler::instance( $file )->fetch();
			}
			@copy( $file, $attrString );
			$tmpFiles[] = $attrString;
		}

		if( in_array( $clasAttr->attribute( 'data_type_string' ), array( 'ezimage' ) ) ) {
			$tmp  = explode( '|', $attrString );
			$file = $tmp[0];

			if(
				file_exists( $file )
				|| (int) @filesize( $file ) === 0
			) {
				eZClusterFileHandler::instance( $file )->fetch();
			}
		}

		$defaultAttributeValues[ $identifier ] = $attrString;
	}

	$publishParams = array(
		'attributes' => array_merge( $defaultAttributeValues, $item['attributes'] ),
		'language'   => $params['target_language']
	);
	if( eZContentFunctions::updateAndPublishObject( $object, $publishParams ) ) {
		$counter++;
	}
	foreach( $tmpFiles as $file ) {
		@unlink( $file );
	}
}

$message = 'Processed ' . $counter . ' translations';
statusMessage( $cli, $message );

$script->shutdown( 0 );
