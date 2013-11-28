<?php
/**
 * @package Translation
 * @class   XLIFFImportHandler
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    22 Jun 2013
 **/

class XLIFFImportHandler extends TranslationImportHandler
{
	public final static function extractData( $sourceFile, $sourceLanguage, $targetLanguage ) {
		$sourceLanguage = XLIFFExportHandler::getHTTPLanguageCode( $sourceLanguage );
		$targetLanguage = XLIFFExportHandler::getHTTPLanguageCode( $targetLanguage );

		$dom = new DOMDocument;
		if(
			file_exists( $sourceFile ) === false
			|| @$dom->loadXML( file_get_contents( $sourceFile ) ) === false
		) {
			throw new Exception( '"' . $sourceFile . '" is not valid XML file' );
		}

		$return  = array();
		$objects = $dom->getElementsByTagName( 'file' );
		foreach( $objects as $objectData ) {
			// We should not check the source language
			if( $objectData->getAttribute( 'target-language' ) !== $targetLanguage ) {
				continue;
			}

			$attributes       = array();
			$attrTranslations = $objectData->getElementsByTagName( 'trans-unit' );
			foreach( $attrTranslations as $attrTranslation ) {
				$value = static::processTransUnit( $attrTranslation, $sourceLanguage, $targetLanguage );
				if( $value === null || strlen( $value ) === 0 ) {
					continue;
				}
				$attributes[ $attrTranslation->getAttribute( 'id' ) ] = $value;
			}

			if( count( $attributes ) === 0 ) {
				continue;
			}

			$item = array(
				'id'         => $objectData->getAttribute( 'original' ),
				'attributes' => $attributes
			);
			$return[] = $item;
		}

		return $return;
	}

	protected static function processTransUnit( $transUnit, $sourceLanguage, $targetLanguage ) {
		$target = $transUnit->getElementsByTagName( 'target' );
		if( (int) $target->length  === 0 ) {
			return null;
		}

		$target = $target->item( 0 );
		if(
			$target->getAttribute( 'xml:lang' ) !== $targetLanguage
			|| !in_array( $target->getAttribute( 'state' ), array( 'needs-review-adaptation', 'translated' ) )
		) {
			return null;
		}

		$value = $target->nodeValue;
		$value = self::fixAmpersand( $transUnit, $value );

		return $value;
	}

	protected static function fixAmpersand( $transUnit, $value ) {
		if(
			$transUnit->hasAttribute( 'restype' )
			&& $transUnit->getAttribute( 'restype' ) === 'rcdata'
		) {
			$value = html_entity_decode( $value );
			$value = str_replace( '&', '&amp;', $value );
		}

		return $value;
	}
}
