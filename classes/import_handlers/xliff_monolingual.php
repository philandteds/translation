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
		$target = $transUnit->getElementsByTagName( 'target' );
		if( (int) $source->length  === 0 ) {
			return null;
		}

		$target = $target->item( 0 );
		if( $target->getAttribute( 'xml:lang' ) !== $targetLanguage ) {
			return null;
		}

		$source = $source->item( 0 );
		$value  = $source->nodeValue;
		// Translations team changes space to &nbsp; in random order, it breaks ezxml
		$value  = str_replace( '&nbsp;', ' ',  $value );
		//$value  = self::fixAmpersand( $transUnit, $value );

		return $value;
	}
}
