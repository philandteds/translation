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
	'[source_language:][target_language:][source_file:][import_handler:]',
	'',
	array(
		'source_language'         => 'Source language',
		'target_language'         => 'Target language',
		'source_file'             => 'Source file, from which translations will be extracted',
		'import_handler'          => 'Export handler (defualt value is XLIFFImportHandler)'
	)
);

// Login as administrator to have rights to create new translations
$ini           = eZINI::instance();
$userCreatorID = $ini->variable( 'UserSettings', 'UserCreatorID' );
$user          = eZUser::fetch( $userCreatorID );
if( ( $user instanceof eZUser ) === false ) {
	$cli->error( 'Cannot get user object by userID = "' . $userCreatorID . '". ( See site.ini [UserSettings].UserCreatorID )' );
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
	if( $options[ $key ] === null) {
		$cli->error( 'Please specify "' . $key . '" parameter' );
		$script->shutdown( 1 );
	}

	$params[ $key ] = $options[ $key ];
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

	$publishParams = array(
		'attributes' => $item['attributes'],
		'language'   => $params['target_language']
	);
	if( eZContentFunctions::updateAndPublishObject( $object, $publishParams ) ) {
		$counter++;
	}
}

$message = 'Processed ' . $counter . ' translations';
statusMessage( $cli, $message );

$script->shutdown( 0 );
