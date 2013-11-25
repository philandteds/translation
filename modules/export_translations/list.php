<?php
/**
 * @package Translation
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    21 Nov 2013
 **/

$module = $Params['Module'];
$newJob = TranslationExportJob::fetch( $Params['NewJobID'] );

if( $module->isCurrentAction( 'New' ) ) {
	return $module->redirectToView( 'export' );
} elseif ( $module->isCurrentAction( 'Remove' ) ) {
	$jobIDs = (array) eZHTTPTool::instance()->postVariable( 'JobIDs', array() );
	TranslationExportJob::removeList( $jobIDs );
}

$tpl = eZTemplate::factory();
$tpl->setVariable( 'jobs', TranslationExportJob::fetchList() );
$tpl->setVariable( 'new_job', $newJob );

$Result = array();
$Result['navigation_part'] = eZINI::instance( 'translation.ini' )->variable( 'NavigationParts', 'Export' );
$Result['content']         = $tpl->fetch( 'design:translation/export/list.tpl' );
$Result['path']            = array(
	array(
		'text' => ezpI18n::tr( 'extension/translation', 'Translations export' ),
		'url'  => false
	)
);