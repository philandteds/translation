<?php
/**
 * @package Translation
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    25 Nov 2013
 **/

ini_set( 'memory_limit', '512M' );

$jobs = TranslationImportJob::fetchList(
	//array( 'status' => TranslationImportJob::STATUS_INITIALIZED )
);

$cli->output( 'Processing ' . count( $jobs ) . ' import jobs' );
foreach( $jobs as $job ) {
	$cli->output( 'Starting translation import job#' . $job->attribute( 'id' ) );
	$job->run();
}