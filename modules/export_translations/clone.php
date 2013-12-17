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
eZHTTPTool::instance()->setSessionVariable( 'new_export_job', $job );

return $Params['Module']->redirectToView( 'export' );