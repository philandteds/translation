<?php
/**
 * @package Translation
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    24 Nov 2013
 **/

$module = $Params['Module'];
$job    = TranslationImportJob::fetch( (int) $Params['JobID'] );
if( $job instanceof TranslationImportJob === false ) {
	return $module->handleError( eZError::KERNEL_NOT_FOUND, 'kernel' );
}

$job->download();