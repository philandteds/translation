<?php
/**
 * @package Translation
 * @class   TranslationJob
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    25 Nov 2013
 **/

class TranslationJob extends eZPersistentObject
{
	const STATUS_INITIALIZED = 1;
	const STATUS_RUNNING     = 2;
	const STATUS_COMPLETE    = 3;
	const FILE_EXTENSION     = 'xlf';

	public function __construct( $row = array() ) {
		$this->eZPersistentObject( $row );
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
				$status = 'Complete';
				break;
		}
		return ezpI18n::tr( 'extension/translation', $status );
	}

	public function remove( $conditions = null, $extraConditions = null ) {
		@unlink( static::getStorageDir() . '/' . $this->attribute( 'file' ) );

		parent::remove( $conditions, $extraConditions );
	}

	public function download() {
		$path = static::getStorageDir() . '/' . $this->attribute( 'file' );

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

	protected function getNotificationReceivers() {
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

		return $receivers;
	}

	public static function fetch( $id ) {
		return eZPersistentObject::fetchObject(
			static::definition(),
			null,
			array( 'id' => $id ),
			true
		);
	}

	public static function fetchList( $conditions = null, $limitations = null ) {
		return eZPersistentObject::fetchObjectList(
			static::definition(),
			null,
			$conditions,
			null,
			$limitations
		);
	}

	public static function checkStorageDir() {
		$dir = static::getStorageDir();
		if( file_exists( $dir ) ) {
			return true;
		}

		return @mkdir( $dir, 0777, true );
	}

	public static function getStorageDir() {
		return null;
	}

	public static function removeList( array $IDs ) {
		foreach( $IDs as $id ) {
			$job = static::fetch( $id );
			if( $job instanceof TranslationJob ) {
				$job->remove();
			}
		}
	}
}
