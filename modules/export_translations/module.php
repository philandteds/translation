<?php
/**
 * @package Translation
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    21 Nov 2013
 **/

$Module = array(
	'name'            => 'Export translations',
 	'variable_params' => true
);

$ViewList = array(
	'list' => array(
		'functions'           => array( 'export' ),
		'script'              => 'list.php',
		'params'              => array( 'Force' ),
		'unordered_params'    => array( 'NewJobID' => 'NewJobID' ),
		'single_post_actions' => array(
			'RemoveButton' => 'Remove',
			'NewButton'    => 'New'
		)
	),
	'clone' => array(
		'functions' => array( 'export' ),
		'script'    => 'clone.php',
		'params'    => array( 'JobID' )
	),
	'export' => array(
		'functions'           => array( 'export' ),
		'script'              => 'export.php',
		'single_get_actions'  => array(
			'RemoveParentNode',
			'RemoveExcludeParentNode',
			'AddParentNodes'
		),
		'single_post_actions' => array(
			'CancelButton'                  => 'Cancel',
			'SaveButton'                    => 'Save',
			'BrowseParentNodeButton'        => 'BrowseParentNode',
			'BrowseExcludeParentNodeButton' => 'BrowseExcludeParentNode'
		),
		'post_actions'        => array( 'BrowseActionName' ),

	),
	'download' => array(
		'functions' => array( 'downaload' ),
		'script'    => 'download.php',
		'params'    => array( 'JobID' )
	)
);

$FunctionList = array(
	'export'    => array(),
	'downaload' => array()
);
