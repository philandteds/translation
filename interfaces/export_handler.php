<?php
/**
 * @package Translation
 * @class   TranslationExportHandler
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    05 Jun 2013
 * */
abstract class TranslationExportHandler
{

    public static function save(array $data, $filename, $language, $targetLanguage)
    {

    }

    public static function getExportAttributes(eZContentClass $class)
    {
        $exportAttributes = array();
        $allowedDatatyps  = array(
            'ezstring',
            'eztext',
            'ezxmltext'
        );

        $dataMap = $class->attribute('data_map');
        foreach ($dataMap as $attribute) {
            if (
                (bool) $attribute->attribute('can_translate')
                && in_array($attribute->attribute('data_type_string'), $allowedDatatyps)
            ) {
                $exportAttributes[] = $attribute->attribute('identifier');
            }
        }

        return $exportAttributes;
    }

    public static function getTranslationData(eZContentObjectTreeNode $node, array $exportAttributes, $targetLanguage, $excludeTargetLang = true)
    {
        $object = $node->attribute('object');
        if ($object instanceof eZContentObject === false) {
            return null;
        }

        $dataMap          = $object->attribute('data_map');
        $objectAttriubtes = array();
        foreach ($exportAttributes as $attributeIdentifier) {
            $objectAttriubtes[$attributeIdentifier] = array(
                'type'    => $dataMap[$attributeIdentifier]->attribute('data_type_string'),
                'content' => $dataMap[$attributeIdentifier]->attribute('data_text')
            );
        }

        if (
            $excludeTargetLang
            && $object->attribute('current_language') == $targetLanguage
        ) {
            return null;
        }

        $data = array(
            'id'               => $object->attribute('id'),
            'remote_id'        => $object->attribute('remote_id'),
            'class_identifier' => $object->attribute('class_identifier'),
            'name'             => $object->attribute('name'),
            'language'         => $object->attribute('current_language'),
            'attributes'       => $objectAttriubtes
        );

        eZContentObject::clearCache($object->attribute('id'));
        $object->resetDataMap();

        return $data;
    }
}