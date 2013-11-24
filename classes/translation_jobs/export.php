<?php
/**
 * @package Translation
 * @class   TranslationExportJob
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    21 Nov 2013
 **/

class TranslationExportJob extends eZPersistentObject
{
	const STATUS_INITIALIZED = 1;
	const STATUS_RUNNING     = 2;
	const STATUS_COMPLETE    = 3;
	const FILE_EXTENSION     = 'xlf';

	private $cache = array(
		'parent_nodes'         => null,
		'exclude_parent_nodes' => null,
		'classes'              => null,
		'creator'              => null
	);

	public function __construct( $row = array() ) {
		$this->eZPersistentObject( $row );
	}

	public static function definition() {
		return array(
			'fields'              => array(
				'id' => array(
					'name'     => 'ID',
					'datatype' => 'integer',
					'default'  => 0,
					'required' => true
				),
				'status' => array(
					'name'     => 'Status',
					'datatype' => 'integer',
					'default'  => self::STATUS_INITIALIZED,
					'required' => true
				),
				'file' => array(
					'name'     => 'File',
					'datatype' => 'string',
					'default'  => null,
					'required' => true
				),
				'parent_node_ids' => array(
					'name'     => 'ParentNodeIDs',
					'datatype' => 'string',
					'default'  => null,
					'required' => false
				),
				'exclude_parent_node_ids' => array(
					'name'     => 'ExcludeParentNodeIDs',
					'datatype' => 'string',
					'default'  => null,
					'required' => false
				),
				'classes' => array(
					'name'     => 'Classes',
					'datatype' => 'string',
					'default'  => null,
					'required' => false
				),
				'siteaccess' => array(
					'name'     => 'Siteaccess',
					'datatype' => 'string',
					'default'  => null,
					'required' => true
				),

				'creator_id' => array(
					'name'     => 'CreatorID',
					'datatype' => 'integer',
					'default'  => eZUser::currentUserID(),
					'required' => true
				),
				'created_at' => array(
					'name'     => 'CreatedAt',
					'datatype' => 'integer',
					'default'  => time(),
					'required' => true
				)
			),
			'function_attributes' => array(
				'status_string'        => 'getStatusString',
				'parent_nodes'         => 'getParentNodes',
				'exclude_parent_nodes' => 'getExcludeParentNodes',
				'class_identifiers'    => 'getClassIdentifiers',
				'content_classes'      => 'getContentClasses',
				'siteaccess_language'  => 'getSiteAccessLanguage',
				'creator'              => 'getCreator'
			),
			'keys'                => array( 'id' ),
			'sort'                => array( 'id' => 'desc' ),
			'increment_key'       => 'id',
			'class_name'          => __CLASS__,
			'name'                => 'translation_export_jobs'
		);
	}

	public function getStatusString() {
		$status = 'Unknown';
		switch( $this->attribute( 'status' ) ) {
			case self::STATUS_INITIALIZED:
				$status = 'Initialized';
				break;
			case self::STATUS_RUNNING:
				$status = 'Running';
				break;
			case self::STATUS_COMPLETE:
				$status = 'Compete';
				break;
		}
		return ezpI18n::tr( 'extension/translation', $status );
	}

	public function getParentNodes() {
		if( $this->cache['parent_nodes'] === null ) {
			$this->cache['parent_nodes'] = $this->fetchAttributeNodes( 'parent_node_ids' );
		}

		return $this->cache['parent_nodes'];
	}

	public function getExcludeParentNodes() {
		if( $this->cache['exclude_parent_nodes'] === null ) {
			$this->cache['exclude_parent_nodes'] = $this->fetchAttributeNodes( 'exclude_parent_node_ids' );
		}

		return $this->cache['exclude_parent_nodes'];
	}

	private function fetchAttributeNodes( $attr ) {
		$nodes   = array();
		$nodeIDs = explode( ',', $this->attribute( $attr ) );
		foreach( $nodeIDs as $nodeID ) {
			$node = eZContentObjectTreeNode::fetch( (int) trim( $nodeID ) );
			if( $node instanceof eZContentObjectTreeNode ) {
				$nodes[] = $node;
			}
		}

		return $nodes;
	}

	public function getClassIdentifiers() {
		return (array) explode( ',', $this->attribute( 'classes' ) );
	}

	public function getContentClasses() {
		if( $this->cache['classes'] === null ) {
			$classes          = array();
			$classIdentifiers = $this->attribute( 'class_identifiers' );
			foreach( $classIdentifiers as $identifiers ) {
				$class = eZContentClass::fetchByIdentifier( trim( $identifiers ) );
				if( $class instanceof eZContentClass ) {
					$classes[] = $class;
				}
			}

			$this->cache['classes'] = $classes;
		}

		return $this->cache['classes'];
	}

	public function getSiteAccessLanguage() {
		$ini = eZINI::getSiteAccessIni( $this->attribute( 'siteaccess' ), 'site.ini' );
		return $ini->hasVariable( 'RegionalSettings', 'Locale' )
			? $ini->variable( 'RegionalSettings', 'Locale' )
			: null;
	}

	public function getCreator() {
		if( $this->cache['creator'] === null ) {
			$this->cache['creator'] = eZContentObject::fetch( $this->attribute( 'creator_id' ) );
		}

		return $this->cache['creator'];
	}

	public static function fetch( $id ) {
		return eZPersistentObject::fetchObject(
			self::definition(),
			null,
			array( 'id' => $id ),
			true
		);
	}

	public static function fetchList( $conditions = null, $limitations = null ) {
		return eZPersistentObject::fetchObjectList(
			self::definition(),
			null,
			$conditions,
			null,
			$limitations
		);
	}

	public function addParentNodes( array $nodeIDs ) {
		$this->addNodes( 'parent_node_ids', $nodeIDs );
	}

	public function addExcludeParentNodes( array $nodeIDs ) {
		$this->addNodes( 'exclude_parent_node_ids', $nodeIDs );
	}

	private function addNodes( $attr, array $nodeIDs ) {
		$currentNodeIDs = explode( ',', $this->attribute( $attr ) );
		$nodeIDs        = array_unique( array_merge( $currentNodeIDs, $nodeIDs ) );

		foreach( $nodeIDs as $key => $nodeID ) {
			$nodeID = (int) trim( $nodeID );
			if( $nodeID > 0 ) {
				// Store new trimmed value
				$nodeIDs[ $key ] = $nodeID;
			} else {
				unset( $nodeIDs[ $key ] );
			}
		}

		$this->setAttribute( $attr, implode( ',', $nodeIDs ) );
		$this->clearNodesCache( $attr );
	}

	public function removeParentNode( $nodeID ) {
		$this->removeNode( 'parent_node_ids', $nodeID );
	}

	public function removeExcludeParentNode( $nodeID ) {
		$this->removeNode( 'exclude_parent_node_ids', $nodeID );
	}

	private function removeNode( $attr, $removeNodeID ) {
		$nodeIDs = explode( ',', $this->attribute( $attr ) );
		foreach( $nodeIDs as $key => $nodeID ) {
			if( (int) $nodeID === $removeNodeID ) {
				unset( $nodeIDs[ $key ] );
			}
		}
		$this->setAttribute( $attr, implode( ',', $nodeIDs ) );
		$this->clearNodesCache( $attr );
	}

	private function clearNodesCache( $attr ) {
		switch( $attr ) {
			case 'parent_node_ids':
				$cacheKey = 'parent_nodes';
				break;
			case 'exclude_parent_node_ids':
				$cacheKey = 'exclude_parent_nodes';
				break;
		}
		$this->cache[ $cacheKey ] = null;
	}

	public function run() {
		// Change status
		$this->setAttribute( 'status', self::STATUS_RUNNING );
		$this->store();

		// create unique file
		$dir = self::getStorageDir();
		if( strlen( $this->attribute( 'file' ) ) === 0 ) {
			$prefix = $this->attribute( 'siteaccess_language' ) . '_' . $this->attribute( 'id' ) . '_';
			$file   = uniqid( $prefix ) . '.' . self::FILE_EXTENSION;
			$fp     = fopen( $dir . '/' . $file, 'w' );
			fwrite( $fp, null );
			fclose( $fp );
			$this->setAttribute( 'file', $file );
			$this->store();
		}

		// Run CLI script
		$command = 'php extension/translation/bin/php/export.php';
		$command .= ' -s ' . $this->attribute( 'siteaccess' );
		$command .= ' --use_siteaccess_languages';
		$command .= ' --language=' . $this->attribute( 'siteaccess_language' );
		$command .= ' --target_language=' . $this->attribute( 'siteaccess_language' );
		$command .= ' --classes=' . $this->attribute( 'classes' );
		$command .= ' --file=' . $dir . '/' . $this->attribute( 'file' );
		$command .= ' --export_handler=XLIFFExportHandler';
		if( strlen( $this->attribute( 'parent_node_ids' ) ) > 0 ) {
			$command .= ' --parent_node_ids=' . $this->attribute( 'parent_node_ids' );
		}
		if( strlen( $this->attribute( 'exclude_parent_node_ids' ) ) > 0 ) {
			$command .= ' --exclude_parent_node_ids=' . $this->attribute( 'exclude_parent_node_ids' );
		}
		exec( $command );

		// Change status
		$this->setAttribute( 'status', self::STATUS_COMPLETE );
		$this->store();

		$this->sendNotification();
	}

	public function sendNotification() {
		$ini       = eZINI::instance( 'translation.ini' );
		$receivers = (array) $ini->variable( 'General', 'NotificationsReceivers' );
		$creator   = $this->attribute( 'creator' );
		if( $creator instanceof eZContentObject ) {
			$dataMap = $creator->attribute( 'data_map' );
			if( isset( $dataMap['user_account'] ) ) {
				$user = $dataMap['user_account']->attribute( 'content' );
				if( $user instanceof eZUser ) {
					$receivers[] = $user->attribute( 'email' );
					$receivers   = array_unique( $receivers );
				}
			}
		}
		if( count( $receivers ) === 0 ) {
			return false;
		}

		$title = 'Content export ' . $this->attribute( 'file' )
			. ' complete ' . $this->attribute( 'siteaccess' )
			. ' (' . $this->attribute( 'siteaccess_language' ) . ')';

		$tpl  = eZTemplate::factory();
		$tpl->setVariable( 'job', $this );
		$body = $tpl->fetch( 'design:translation/export/notification.tpl' );

		$mail = new eZMail();
		$mail->setContentType( 'text/html' );
		$mail->setSender( eZINI::instance()->variable( 'MailSettings', 'AdminEmail' ) );
		$mail->setSubject( $title );
		$mail->setBody( $body );
		foreach( $receivers as $email ) {
			$mail->addReceiver( $email );
		}

		eZMailTransport::send( $mail );
	}

	public function download() {
		$path = self::getStorageDir() . '/' . $this->attribute( 'file' );

		eZSession::stop();
		ob_end_clean();

		eZFile::downloadHeaders(
			$path,
			true,
			$this->attribute( 'file' )
		);
		readfile( $path );
		eZExecution::cleanExit();
	}

	public function remove( $conditions = null, $extraConditions = null ) {
		@unlink( $this->attribute( 'file' ) );

		parent::remove( $conditions, $extraConditions );
	}

	public static function getAllContentClasses() {
		$classes = eZContentClass::fetchList(
			eZContentClass::VERSION_STATUS_DEFINED,
			true,
			false,
			array( 'name' => false )
		);

		$return = array();
		foreach( $classes as $class ) {
			$return[ $class->attribute( 'identifier' ) ] = $class;
		}
		return $return;
	}

	public static function getSiteAccesses() {
		$siteAccesses = eZINI::instance()->variable( 'SiteAccessSettings', 'AvailableSiteAccessList' );

		$return = array();
		foreach( $siteAccesses as $siteAccess ) {
			// skip admin siteaccesses
			if( mb_strpos( $siteAccess, 'admin' ) !== false ) {
				continue;
			}

			$ini    = eZINI::getSiteAccessIni( $siteAccess, 'site.ini' );
			$locale = $ini->hasVariable( 'RegionalSettings', 'Locale' )
				? $ini->variable( 'RegionalSettings', 'Locale' )
				: null;
			$return[ $siteAccess ] = $locale;
		}

		return $return;
	}

	public static function getStorageDir() {
		return eZINI::instance( 'translation.ini' )->variable( 'General', 'StorageDir' );
	}

	public static function checkStorageDir() {
		$dir = self::getStorageDir();
		if( file_exists( $dir ) === false ) {
			@mkdir( $dir, 0777, true );
		}
	}
}
