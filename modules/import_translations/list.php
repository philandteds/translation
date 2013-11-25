<?php
/**
 * @package Translation
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    21 Nov 2013
 **/

$module = $Params['Module'];
$newJob = null;
$errors = array();

if( $module->isCurrentAction( 'New' ) ) {
	$storageDir = TranslationImportJob::getStorageDir();
	$fileInfo   = isset( $_FILES['import_file'] ) ? $_FILES['import_file'] : null;

	if( $fileInfo === null ) {
		$errors[] = ezpI18n::tr( 'extension/translation', 'Please upload import file' );
	}

	if( count( $errors ) === 0 && $fileInfo['error'] > 0 ) {
		$error = null;
		switch( $fileInfo['error'] ) {
			case UPLOAD_ERR_FORM_SIZE:
			case UPLOAD_ERR_INI_SIZE:
				$error = 'Uploaded file size exceed upload limits';
				break;
			case UPLOAD_ERR_NO_FILE:
				$error = 'Please provide import file';
				break;
			default:
				$error = 'Error occurred during file upload';
		}

		if( $error !== null ) {
			$errors[] = ezpI18n::tr( 'extension/translation', $error );
		}
	}

	if(
		count( $errors ) === 0
		&& pathinfo( $fileInfo['name'], PATHINFO_EXTENSION ) != TranslationJob::FILE_EXTENSION
	) {
		$errors[] = ezpI18n::tr(
			'extension/translation',
			'Uploaded file has wrong extension. Allowed extension is: ' . TranslationJob::FILE_EXTENSION
		);
	}

	if(
		count( $errors ) === 0
		&& file_exists( $storageDir . '/' . $fileInfo['name'] )
	) {
		$errors[] = ezpI18n::tr(
			'extension/translation',
			'Import file with the same name already exists. Please change file`s name and upload it again.'
		);
	}

	$dom = new DOMDocument;
	if(
		count( $errors ) === 0
		&& @$dom->loadXML( file_get_contents( $fileInfo['tmp_name'] ) ) === false
	) {
		$errors[] = ezpI18n::tr( 'extension/translation', 'Uploaded file is not valid XML file' );
	}

	$locale = null;
	if( count( $errors ) === 0 ) {
		$objects  = $dom->getElementsByTagName( 'file' );
		if( $objects->length > 0 ) {
			$firstItem = $objects->item( 0 );
			if( $firstItem->hasAttribute( 'target-language' ) ) {
				$locale = TranslationImportJob::fetchLocaleByLanguage( $firstItem->getAttribute( 'target-language' ) );
			}
		}
		if( $locale === null ) {
			$errors[] = ezpI18n::tr( 'extension/translation', 'Language can not be extracted from import file' );
		}
	}

	TranslationImportJob::checkStorageDir();
	if(
		count( $errors ) === 0
		&& move_uploaded_file( $fileInfo['tmp_name'], $storageDir . '/' . $fileInfo['name'] ) === false
	) {
		$errors[] = ezpI18n::tr( 'extension/translation', 'System error' );
	}

	if( count( $errors ) === 0 ) {
		$newJob = new TranslationImportJob();
		$newJob->setAttribute( 'file', $fileInfo['name'] );
		$newJob->setAttribute( 'language', $locale );
		$newJob->store();
	}
} elseif ( $module->isCurrentAction( 'Remove' ) ) {
	$jobIDs = (array) eZHTTPTool::instance()->postVariable( 'JobIDs', array() );
	TranslationImportJob::removeList( $jobIDs );
}

$tpl = eZTemplate::factory();
$tpl->setVariable( 'jobs', TranslationImportJob::fetchList() );
$tpl->setVariable( 'new_job', $newJob );
$tpl->setVariable( 'errors', $errors );

$Result = array();
$Result['navigation_part'] = eZINI::instance( 'translation.ini' )->variable( 'NavigationParts', 'Import' );
$Result['content']         = $tpl->fetch( 'design:translation/import/list.tpl' );
$Result['path']            = array(
	array(
		'text' => ezpI18n::tr( 'extension/translation', 'Translations import' ),
		'url'  => false
	)
);