<?php
/**
 * @package Translation
 * @class   XLIFFImportHandler
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    22 Jun 2013
 **/

class XLIFFImportHandler extends TranslationImportHandler
{
	public static function extractData( $sourceFile, $sourceLanguage, $targetLanguage ) {
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
				$target = $attrTranslation->getElementsByTagName( 'target' );
				if( (int) $target->length  === 0 ) {
					continue;
				}
				$target = $target->item( 0 );

				if(
					$target->getAttribute( 'xml:lang' ) !== $targetLanguage
					|| $target->getAttribute( 'state' ) !== 'translated'
					|| $target->getAttribute( 'state' ) !== 'needs-review-adaptation'
				) {
					continue;
				}

				$value = $target->nodeValue;
				if( strlen( $value ) === 0 ) {
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
}
