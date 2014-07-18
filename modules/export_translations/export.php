<?php
/**
 * @package Translation
 * @author  Serhey Dolgushev <dolgushev.serhey@gmail.com>
 * @date    24 Nov 2013
 * */
$http   = eZHTTPTool::instance();
$module = $Params['Module'];
$errors = array();

$newJobSessionVar = 'new_export_job';
$browseParameters = array(
    'action_name' => 'AddParentNodes',
    'type'        => 'AddSubtreeSubscribingNode',
    'from_page'   => 'export_translations/export'
);

$classes      = TranslationExportJob::getAllContentClasses();
$siteAccesses = TranslationExportJob::getSiteAccesses();
$exportJob    = $http->sessionVariable($newJobSessionVar, new TranslationExportJob());
if (isset($Params['Force']) && $Params['Force'] !== null) {
    $exportJob = new TranslationExportJob();
}

// ezp kernel does not provide ability to use get actions
$getAction = $http->getVariable('action', false);
$viewDef   = $module->Functions[$module->currentView()];
if (
    isset($viewDef['single_get_actions'])
    && in_array($getAction, $viewDef['single_get_actions'])
) {
    $module->setCurrentAction($getAction);
}

if ($module->isCurrentAction('BrowseParentNode')) {
    return eZContentBrowse::browse($browseParameters, $Params['Module']);
} elseif ($module->isCurrentAction('AddParentNodes')) {
    $exportJob->addParentNodes((array) $http->variable('SelectedNodeIDArray', array()));
} elseif ($module->isCurrentAction('RemoveParentNode')) {
    $exportJob->removeParentNode((int) $http->getVariable('NodeID', -1));
} elseif ($module->isCurrentAction('BrowseExcludeParentNode')) {
    $browseParameters['action_name'] = 'AddExcludeParentNodes';
    return eZContentBrowse::browse($browseParameters, $Params['Module']);
} elseif ($module->isCurrentAction('AddExcludeParentNodes')) {
    $exportJob->addExcludeParentNodes((array) $http->postVariable('SelectedNodeIDArray', array()));
} elseif ($module->isCurrentAction('RemoveExcludeParentNode')) {
    $exportJob->removeExcludeParentNode((int) $http->getVariable('NodeID', -1));
} elseif ($module->isCurrentAction('BrowseDirectNode')) {
    $browseParameters['action_name'] = 'AddDirectNodes';
    return eZContentBrowse::browse($browseParameters, $Params['Module']);
} elseif ($module->isCurrentAction('AddDirectNodes')) {
    $exportJob->addDirectNodes((array) $http->postVariable('SelectedNodeIDArray', array()));
} elseif ($module->isCurrentAction('RemoveDirectNode')) {
    $exportJob->removeDirectNode((int) $http->getVariable('NodeID', -1));
} elseif ($module->isCurrentAction('Save')) {
    $input = $http->postVariable('new_job', array());

    // Check selected classes
    $selectedClasses = isset($input['classes']) ? (array) $input['classes'] : array();
    foreach ($selectedClasses as $key => $classIdentifier) {
        if (isset($classes[$classIdentifier]) === false) {
            unset($selectedClasses[$key]);
        }
    }

    if (
        count($selectedClasses) === 0
        && count($exportJob->attribute('direct_nodes')) === 0
    ) {
        $errors[] = ezpI18n::tr(
                'extension/translation', 'Please select at least one content class or some direct nodes'
        );
    }

    // Check selected siteaccess
    $siteAccess = isset($input['siteaccess']) ? $input['siteaccess'] : null;
    if (isset($siteAccesses[$siteAccess]) === false) {
        $errors[] = ezpI18n::tr(
                'extension/translation', 'Please select valid language'
        );
    }

    if (count($errors) === 0) {
        $exportJob->setAttribute('classes', implode(',', $selectedClasses));
        $exportJob->setAttribute('siteaccess', $siteAccess);
    }
} elseif ($module->isCurrentAction('Cancel')) {
    $http->removeSessionVariable($newJobSessionVar);
    return $module->redirectToView('list');
}

$http->setSessionVariable($newJobSessionVar, $exportJob);

if (
    $module->isCurrentAction('Save')
    && count($errors) === 0
) {
    $exportJob->store();
    $http->removeSessionVariable($newJobSessionVar);
    return $module->redirectToView(
            'list', array(), array('NewJobID' => $exportJob->attribute('id'))
    );
}

$tpl = eZTemplate::factory();
$tpl->setVariable('classes', $classes);
$tpl->setVariable('siteaccesses', $siteAccesses);
$tpl->setVariable('job', $exportJob);
$tpl->setVariable('errors', $errors);

$Result                    = array();
$Result['navigation_part'] = eZINI::instance('translation.ini')->variable('NavigationParts', 'Export');
$Result['content']         = $tpl->fetch('design:translation/export/export.tpl');
$Result['path']            = array(
    array(
        'text' => ezpI18n::tr('extension/translation', 'Translations export'),
        'url'  => 'export_translations/list'
    ),
    array(
        'text' => ezpI18n::tr('extension/translation', 'New export'),
        'url'  => false
    )
);
