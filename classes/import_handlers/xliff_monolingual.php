<?php
/**
 * @package Translation
 * @class   XLIFFImportHandler
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    28 Nov 2013
 **/

class XLIFFImportHandlerMonolingual extends XLIFFImportHandler
{
	protected static function processTransUnit( $transUnit, $sourceLanguage, $targetLanguage ) {
		$source = $transUnit->getElementsByTagName( 'source' );
		if( (int) $source->length  === 0 ) {
			return null;
		}

		$source = $source->item( 0 );
		if( $source->getAttribute( 'xml:lang' ) !== $sourceLanguage ) {
			return null;
		}

		$value = $source->nodeValue;
		$value = self::fixAmpersand( $transUnit, $value );

		return $value;
	}
}
