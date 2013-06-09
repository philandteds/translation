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
		$doc = new DOMDocument( '1.0', 'UTF-8' );
		$doc->formatOutput = true;

		$root = $doc->createElement( 'xliff' );
		$root->setAttribute( 'version', '1.2' );
		$root->setAttribute( 'xml:lang', $language );
		$doc->appendChild( $root );

		foreach( $data as $item ) {
			$file = $doc->createElement( 'file' );
			$file->setAttribute( 'tool-id', $item['id'] );
			$file->setAttribute( 'source-language', $item['language'] );
			$file->setAttribute( 'target-language', $targetLanguage );
			$file->setAttribute( 'datatype', 'database' );
			$file->setAttribute( 'original', $item['name'] );
			$root->appendChild( $file );

			$body = $doc->createElement( 'body' );
			$file->appendChild( $body );

			foreach( $item['attributes'] as $identifier => $value ) {
				$datatype = $value['type'] == 'ezxmltext' ? 'rcdata' : 'plaintext';

				$unit = $doc->createElement( 'trans-unit' );
				$unit->setAttribute( 'datatype', $datatype );
				$unit->setAttribute( 'id', $identifier );
				$body->appendChild( $unit );

				$source = $doc->createElement( 'source' );
				$source->setAttribute( 'xml:lang', $item['language'] );
				if( $value['type'] == 'ezxmltext' ) {
					$doc->validate();
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
				$target->setAttribute( 'state', 'need-translation' );
				$unit->appendChild( $target );
			}
		}

		return $doc->save( $filename );
	}
}