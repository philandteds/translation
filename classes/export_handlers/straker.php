<?php
/**
 * @package Translation
 * @class   StrakerExportHandler
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    05 Jun 2013
 **/

class StrakerExportHandler extends TranslationExportHandler
{
	public static function save( array $data, $filename, $language, $targetLanguage ) {
		$doc = new DOMDocument( '1.0', 'UTF-8' );
		$doc->formatOutput = true;

		$root = $doc->createElement( 'all' );
		$doc->appendChild( $root );

		foreach( $data as $item ) {
			$entry = $doc->createElement( 'entry' );
			$entry->setAttribute( 'id', $item['id'] );
			$entry->setAttribute( 'remote_id', $item['remote_id'] );
			$entry->setAttribute( 'type', $item['class_identifier'] );
			$entry->setAttribute( 'language', $item['language'] );

			foreach( $item['attributes'] as $identifier => $value ) {
				$field = $doc->createElement( 'field' );
				$field->setAttribute( 'name', $identifier );
				$field->appendChild( $doc->createCDATASection( $value['content'] ) );
				$entry->appendChild( $field );
			}

			$root->appendChild( $entry );
		}

		return $doc->save( $filename );
	}
}
