<?php
/**
 * @package Translation
 * @class   XLIFFExportHandler
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    05 Jun 2013
 **/

class XLIFFExportHandler extends TranslationExportHandler
{
	public static function save( array $data, $filename, $language, $targetLanguage ) {
		$language       = self::getHTTPLanguageCode( $language );
		$targetLanguage = self::getHTTPLanguageCode( $targetLanguage );

		$doc = new DOMDocument( '1.0', 'UTF-8' );
		$doc->formatOutput = true;

		$root = $doc->createElement( 'xliff' );
		$root->setAttribute( 'version', '1.2' );
		$root->setAttribute( 'xml:lang', $language );
		$root->setAttribute( 'xmlns', 'urn:oasis:names:tc:xliff:document:1.2' );
		$doc->appendChild( $root );

		foreach( $data as $item ) {
			$itemLanguage =
			$file = $doc->createElement( 'file' );
			$file->setAttribute( 'original', $item['id'] );
			$file->setAttribute( 'source-language', $language );
			$file->setAttribute( 'target-language', $targetLanguage );
			$file->setAttribute( 'datatype', 'database' );
			$root->appendChild( $file );

			$body = $doc->createElement( 'body' );
			$file->appendChild( $body );

			foreach( $item['attributes'] as $identifier => $value ) {
				$datatype = $value['type'] == 'ezxmltext' ? 'rcdata' : 'string';

				$unit = $doc->createElement( 'trans-unit' );
				$unit->setAttribute( 'restype', $datatype );
				$unit->setAttribute( 'id', $identifier );
				$body->appendChild( $unit );

				$source = $doc->createElement( 'source' );
				$source->setAttribute( 'xml:lang', $language );
				if( $value['type'] == 'ezxmltext' ) {
					@$doc->validate();
					$source->appendChild( $doc->createCDATASection( $value['content'] ) );
					/*
					$fragment = $doc->createDocumentFragment();
					$fragment->appendXML( $value['content'] );
					$source->appendChild( $fragment );
					*/
				} else {
					$source->appendChild( $doc->createTextNode( $value['content'] ) );
				}
				$unit->appendChild( $source );

				$target = $doc->createElement( 'target' );
				$target->setAttribute( 'xml:lang', $targetLanguage );
				$target->setAttribute( 'state', 'needs-translation' );
				$unit->appendChild( $target );
			}
		}

        $xml = $doc->saveXML();
        return eZClusterFileHandler::instance($filename)->storeContents($xml);
//
//
//		return $doc->save( $filename );
	}

	public static function getHTTPLanguageCode( $locale ) {
		return eZINI::instance( $locale . '.ini', 'share/locale' )->variable( 'HTTP', 'ContentLanguage' );
	}
}
