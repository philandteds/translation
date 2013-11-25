<?php
/**
 * @package Translation
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    25 Nov 2013
 **/

$Module = array(
	'name'            => 'Import translations',
 	'variable_params' => true
);

$ViewList = array(
	'list' => array(
		'functions'           => array( 'import' ),
		'script'              => 'list.php',
		'single_post_actions' => array(
			'RemoveButton' => 'Remove',
			'NewButton'    => 'New'
		)
	),
	'download' => array(
		'functions' => array( 'downaload' ),
		'script'    => 'download.php',
		'params'    => array( 'JobID' )
	)
);

$FunctionList = array(
	'import'    => array(),
	'downaload' => array()
);
