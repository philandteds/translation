<?php
/**
 * @package Translation
 * @class   TranslationImportJob
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    24 Nov 2013
 **/

class TranslationImportJob extends TranslationJob
{
	private $cache = array(
		'creator' => null
	);

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
				'language' => array(
					'name'     => 'Language',
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
				'status_string'            => 'getStatusString',
				'creator'                  => 'getCreator',
				'affected_siteaccess_urls' => 'getAffectedSiteaccessURLs'
			),
			'keys'                => array( 'id' ),
			'sort'                => array( 'id' => 'desc' ),
			'increment_key'       => 'id',
			'class_name'          => __CLASS__,
			'name'                => 'translation_import_jobs'
		);
	}

	public function getCreator() {
		if( $this->cache['creator'] === null ) {
			$this->cache['creator'] = eZContentObject::fetch( $this->attribute( 'creator_id' ) );
		}

		return $this->cache['creator'];
	}

	public function getAffectedSiteaccessURLs() {
		$siteAccesses = eZINI::instance()->variable( 'SiteAccessSettings', 'AvailableSiteAccessList' );

		$return = array();
		foreach( $siteAccesses as $siteAccess ) {
			$ini = eZINI::getSiteAccessIni( $siteAccess, 'site.ini' );

			$locale    = $ini->hasVariable( 'RegionalSettings', 'Locale' )
				? $ini->variable( 'RegionalSettings', 'Locale' )
				: null;
			$languages = $ini->hasVariable( 'RegionalSettings', 'SiteLanguageList' )
				? $ini->variable( 'RegionalSettings', 'SiteLanguageList' )
				: array();

			if(
				$this->attribute( 'language' ) === $locale
				|| in_array( $this->attribute( 'language' ), $languages )
			) {
				$return[ $siteAccess ] = $ini->variable( 'SiteSettings', 'SiteURL' );
			}
		}

		return $return;
	}

	public function run() {
		// Change status
		$this->setAttribute( 'status', self::STATUS_RUNNING );
		$this->store();

		// Run CLI script
		$dir = self::getStorageDir();
		$command = 'php extension/translation/bin/php/import.php';
		$command .= ' --source_language=' . $this->attribute( 'language' );
		$command .= ' --target_language=' . $this->attribute( 'language' );
		$command .= ' --source_file="' . $dir . '/' . $this->attribute( 'file' ) . '"';
		$command .= ' --import_handler=XLIFFImportHandler';
		$command .= ' --translation_creator_id=' . $this->attribute( 'creator_id' );
		$command .= ' --default_attributes=all';
		exec( $command );

		// Change status
		$this->setAttribute( 'status', self::STATUS_COMPLETE );
		$this->store();

		$this->sendNotification();
	}

	public function sendNotification() {
		$receivers = $this->getNotificationReceivers();
		if( count( $receivers ) === 0 ) {
			return false;
		}

		$title = 'Translation import ' . $this->attribute( 'file' )
			. ' complete ' . $this->attribute( 'language' );

		$tpl  = eZTemplate::factory();
		$tpl->setVariable( 'job', $this );
		$body = $tpl->fetch( 'design:translation/import/notification.tpl' );

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

	public static function getStorageDir() {
		return eZINI::instance( 'translation.ini' )->variable( 'General', 'ImportStorageDir' );
	}

	public static function fetchLocaleByLanguage( $lang ) {
		$languages = eZContentLanguage::fetchList();
		foreach( $languages as $language ) {
			$ini = eZINI::instance( $language->attribute( 'locale' ) . '.ini', 'share/locale' );
			if(
				$ini->hasVariable( 'HTTP', 'ContentLanguage' )
				&& $ini->variable( 'HTTP', 'ContentLanguage' ) == $lang
			) {
				return $language->attribute( 'locale' );
			}
		}

		return null;
	}
}
