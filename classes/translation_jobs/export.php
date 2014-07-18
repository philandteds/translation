<?php
/**
 * @package Translation
 * @class   TranslationExportJob
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    21 Nov 2013
 * */
class TranslationExportJob extends TranslationJob
{
    private $cache = array(
        'parent_nodes'         => null,
        'exclude_parent_nodes' => null,
        'direct_nodes'         => null,
        'classes'              => null,
        'creator'              => null
    );

    public static function definition()
    {
        return array(
            'fields'              => array(
                'id'                      => array(
                    'name'     => 'ID',
                    'datatype' => 'integer',
                    'default'  => 0,
                    'required' => true
                ),
                'status'                  => array(
                    'name'     => 'Status',
                    'datatype' => 'integer',
                    'default'  => self::STATUS_INITIALIZED,
                    'required' => true
                ),
                'file'                    => array(
                    'name'     => 'File',
                    'datatype' => 'string',
                    'default'  => null,
                    'required' => true
                ),
                'parent_node_ids'         => array(
                    'name'     => 'ParentNodeIDs',
                    'datatype' => 'string',
                    'default'  => null,
                    'required' => false
                ),
                'exclude_parent_node_ids' => array(
                    'name'     => 'ExcludeParentNodeIDs',
                    'datatype' => 'string',
                    'default'  => null,
                    'required' => false
                ),
                'direct_node_ids'         => array(
                    'name'     => 'DirectNodeIDs',
                    'datatype' => 'string',
                    'default'  => null,
                    'required' => false
                ),
                'classes'                 => array(
                    'name'     => 'Classes',
                    'datatype' => 'string',
                    'default'  => null,
                    'required' => false
                ),
                'siteaccess'              => array(
                    'name'     => 'Siteaccess',
                    'datatype' => 'string',
                    'default'  => null,
                    'required' => true
                ),
                'creator_id'              => array(
                    'name'     => 'CreatorID',
                    'datatype' => 'integer',
                    'default'  => eZUser::currentUserID(),
                    'required' => true
                ),
                'created_at'              => array(
                    'name'     => 'CreatedAt',
                    'datatype' => 'integer',
                    'default'  => time(),
                    'required' => true
                )
            ),
            'function_attributes' => array(
                'status_string'        => 'getStatusString',
                'parent_nodes'         => 'getParentNodes',
                'exclude_parent_nodes' => 'getExcludeParentNodes',
                'direct_nodes'         => 'getDirectNodes',
                'class_identifiers'    => 'getClassIdentifiers',
                'content_classes'      => 'getContentClasses',
                'siteaccess_language'  => 'getSiteAccessLanguage',
                'siteaccess_locale'    => 'getSiteAccessLocale',
                'creator'              => 'getCreator'
            ),
            'keys'                => array('id'),
            'sort'                => array('id' => 'desc'),
            'increment_key'       => 'id',
            'class_name'          => __CLASS__,
            'name'                => 'translation_export_jobs'
        );
    }

    public function getParentNodes()
    {
        if ($this->cache['parent_nodes'] === null) {
            $this->cache['parent_nodes'] = $this->fetchAttributeNodes('parent_node_ids');
        }

        return $this->cache['parent_nodes'];
    }

    public function getExcludeParentNodes()
    {
        if ($this->cache['exclude_parent_nodes'] === null) {
            $this->cache['exclude_parent_nodes'] = $this->fetchAttributeNodes('exclude_parent_node_ids');
        }

        return $this->cache['exclude_parent_nodes'];
    }

    public function getDirectNodes()
    {
        if ($this->cache['direct_nodes'] === null) {
            $this->cache['direct_nodes'] = $this->fetchAttributeNodes('direct_node_ids');
        }

        return $this->cache['direct_nodes'];
    }

    private function fetchAttributeNodes($attr)
    {
        $nodes   = array();
        $nodeIDs = explode(',', $this->attribute($attr));
        foreach ($nodeIDs as $nodeID) {
            $node = eZContentObjectTreeNode::fetch((int) trim($nodeID));
            if ($node instanceof eZContentObjectTreeNode) {
                $nodes[] = $node;
            }
        }

        return $nodes;
    }

    public function getClassIdentifiers()
    {
        return (array) explode(',', $this->attribute('classes'));
    }

    public function getContentClasses()
    {
        if ($this->cache['classes'] === null) {
            $classes          = array();
            $classIdentifiers = $this->attribute('class_identifiers');
            foreach ($classIdentifiers as $identifiers) {
                $class = eZContentClass::fetchByIdentifier(trim($identifiers));
                if ($class instanceof eZContentClass) {
                    $classes[] = $class;
                }
            }

            $this->cache['classes'] = $classes;
        }

        return $this->cache['classes'];
    }

    public function getSiteAccessLanguage()
    {
        $ini = eZINI::instance($this->attribute('siteaccess_locale') . '.ini', 'share/locale');
        return $ini->hasVariable('RegionalSettings', 'InternationalLanguageName') ? $ini->variable('RegionalSettings', 'InternationalLanguageName') : null;
    }

    public function getSiteAccessLocale()
    {
        $ini = eZINI::getSiteAccessIni($this->attribute('siteaccess'), 'site.ini');
        return $ini->hasVariable('RegionalSettings', 'Locale') ? $ini->variable('RegionalSettings', 'Locale') : null;
    }

    public function getCreator()
    {
        if ($this->cache['creator'] === null) {
            $this->cache['creator'] = eZContentObject::fetch($this->attribute('creator_id'));
        }

        return $this->cache['creator'];
    }

    public function addParentNodes(array $nodeIDs)
    {
        $this->addNodes('parent_node_ids', $nodeIDs);
    }

    public function addExcludeParentNodes(array $nodeIDs)
    {
        $this->addNodes('exclude_parent_node_ids', $nodeIDs);
    }

    public function addDirectNodes(array $nodeIDs)
    {
        $this->addNodes('direct_node_ids', $nodeIDs);
    }

    private function addNodes($attr, array $nodeIDs)
    {
        $currentNodeIDs = explode(',', $this->attribute($attr));
        $nodeIDs        = array_unique(array_merge($currentNodeIDs, $nodeIDs));

        foreach ($nodeIDs as $key => $nodeID) {
            $nodeID = (int) trim($nodeID);
            if ($nodeID > 0) {
                // Store new trimmed value
                $nodeIDs[$key] = $nodeID;
            } else {
                unset($nodeIDs[$key]);
            }
        }

        $this->setAttribute($attr, implode(',', $nodeIDs));
        $this->clearNodesCache($attr);
    }

    public function removeParentNode($nodeID)
    {
        $this->removeNode('parent_node_ids', $nodeID);
    }

    public function removeExcludeParentNode($nodeID)
    {
        $this->removeNode('exclude_parent_node_ids', $nodeID);
    }

    public function removeDirectNode($nodeID)
    {
        $this->removeNode('direct_node_ids', $nodeID);
    }

    private function removeNode($attr, $removeNodeID)
    {
        $nodeIDs = explode(',', $this->attribute($attr));
        foreach ($nodeIDs as $key => $nodeID) {
            if ((int) $nodeID === $removeNodeID) {
                unset($nodeIDs[$key]);
            }
        }
        $this->setAttribute($attr, implode(',', $nodeIDs));
        $this->clearNodesCache($attr);
    }

    private function clearNodesCache($attr)
    {
        switch ($attr) {
            case 'parent_node_ids':
                $cacheKey = 'parent_nodes';
                break;
            case 'exclude_parent_node_ids':
                $cacheKey = 'exclude_parent_nodes';
                break;
            case 'direct_node_ids':
                $cacheKey = 'direct_nodes';
                break;
        }
        $this->cache[$cacheKey] = null;
    }

    public function run()
    {
        // Change status
        $this->setAttribute('status', self::STATUS_RUNNING);
        $this->store();

        // create unique file
        $dir = self::getStorageDir();
        if (strlen($this->attribute('file')) === 0) {
            $prefix = $this->attribute('siteaccess_language') . '_' . $this->attribute('id') . '_';
            $file   = uniqid($prefix) . '.' . self::FILE_EXTENSION;
            $fp     = fopen($dir . '/' . $file, 'w');
            fwrite($fp, null);
            fclose($fp);
            $this->setAttribute('file', $file);
            $this->store();
        }

        // Run CLI script
        $command = 'php extension/translation/bin/php/export.php';
        $command .= ' -s ' . $this->attribute('siteaccess');
        $command .= ' --use_siteaccess_languages';
        $command .= ' --exclude_target_language';
        $command .= ' --language=' . $this->attribute('siteaccess_locale');
        $command .= ' --target_language=' . $this->attribute('siteaccess_locale');
        if (strlen($this->attribute('classes')) > 0) {
            $command .= ' --classes=' . $this->attribute('classes');
        }
        $command .= ' --file="' . $dir . '/' . $this->attribute('file') . '"';
        $command .= ' --export_handler=XLIFFExportHandler';
        if (strlen($this->attribute('parent_node_ids')) > 0) {
            $command .= ' --parent_node_ids=' . $this->attribute('parent_node_ids');
        }
        if (strlen($this->attribute('exclude_parent_node_ids')) > 0) {
            $command .= ' --exclude_parent_node_ids=' . $this->attribute('exclude_parent_node_ids');
        }
        if (strlen($this->attribute('direct_node_ids')) > 0) {
            $command .= ' --direct_node_ids=' . $this->attribute('direct_node_ids');
        }
        exec($command);

        // Change status
        $this->setAttribute('status', self::STATUS_COMPLETE);
        $this->store();

        $this->sendNotification();
    }

    public function sendNotification()
    {
        $receivers = $this->getNotificationReceivers();
        if (count($receivers) === 0) {
            return false;
        }

        $title = 'Content export ' . $this->attribute('file')
            . ' complete ' . $this->attribute('siteaccess')
            . ' (' . $this->attribute('siteaccess_language') . ')';

        $tpl  = eZTemplate::factory();
        $tpl->setVariable('job', $this);
        $body = $tpl->fetch('design:translation/export/notification.tpl');

        $mail = new eZMail();
        $mail->setContentType('text/html');
        $mail->setSender(eZINI::instance()->variable('MailSettings', 'AdminEmail'));
        $mail->setSubject($title);
        $mail->setBody($body);
        foreach ($receivers as $email) {
            $mail->addReceiver($email);
        }

        eZMailTransport::send($mail);
    }

    public static function getAllContentClasses()
    {
        $classes = eZContentClass::fetchList(
                eZContentClass::VERSION_STATUS_DEFINED, true, false, array('name' => false)
        );

        $return = array();
        foreach ($classes as $class) {
            $return[$class->attribute('identifier')] = $class;
        }
        return $return;
    }

    public static function getSiteAccesses()
    {
        $siteAccesses = eZINI::instance()->variable('SiteAccessSettings', 'AvailableSiteAccessList');

        $return = array();
        foreach ($siteAccesses as $siteAccess) {
            // skip admin siteaccesses
            if (mb_strpos($siteAccess, 'admin') !== false) {
                continue;
            }

            $ini    = eZINI::getSiteAccessIni($siteAccess, 'site.ini');
            $locale = $ini->hasVariable('RegionalSettings', 'Locale') ? $ini->variable('RegionalSettings', 'Locale') : null;

            $ini                 = eZINI::instance($locale . '.ini', 'share/locale');
            $language            = $ini->hasVariable('RegionalSettings', 'InternationalLanguageName') ? $ini->variable('RegionalSettings', 'InternationalLanguageName') : null;
            $return[$siteAccess] = $language;
        }

        return $return;
    }

    public static function getStorageDir()
    {
        return eZINI::instance('translation.ini')->variable('General', 'ExportStorageDir');
    }
}