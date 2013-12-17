<?php
/**
 * @package Translation
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    24 Nov 2013
 **/

$job = TranslationExportJob::fetch( (int) $Params['JobID'] );
if( $job instanceof TranslationExportJob === false ) {
	return $module->handleError( eZError::KERNEL_NOT_FOUND, 'kernel' );
}

$job->setAttribute( 'id', null );
$job->setAttribute( 'file', null );
$job->setAttribute( 'status', TranslationExportJob::STATUS_INITIALIZED );
$job->setAttribute( 'creator_id', eZUser::currentUserID() );
$job->setAttribute( 'created_at', time() );

eZHTTPTool::instance()->setSessionVariable( 'new_export_job', $job );

return $Params['Module']->redirectToView( 'export' );