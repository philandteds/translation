<?php
/**
 * @package Translation
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    24 Nov 2013
 **/

ini_set( 'memory_limit', '512M' );

$jobs = TranslationExportJob::fetchList(
	array( 'status' => TranslationExportJob::STATUS_INITIALIZED )
);

if( count( $jobs ) > 0 ) {
	TranslationExportJob::checkStorageDir();
}
$cli->output( 'Processing ' . count( $jobs ) . ' export jobs' );
foreach( $jobs as $job ) {
	$cli->output( 'Starting translation export job#' . $job->attribute( 'id' ) );
	$job->run();
}
