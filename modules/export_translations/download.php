<?php
/**
 * @package Translation
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    24 Nov 2013
 **/

$module = $Params['Module'];
$job    = TranslationExportJob::fetch( (int) $Params['JobID'] );
if( $job instanceof TranslationExportJob === false ) {
	return $module->handleError( eZError::KERNEL_NOT_FOUND, 'kernel' );
}

$job->download();