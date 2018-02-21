<?php
/** @var modX $modx */
/** @var array $scriptProperties */
/** @var App $App */
switch ($modx->event->name) {
    case 'OnMODXInit':
        $modelPath = $modx->getOption('app_core_path', array(), $modx->getOption('core_path') . 'components/app/') . 'model/';

        if ($App = $modx->getService('App', 'App', $modelPath)) {
            $App->initialize();
        }
        break;
    default:
        $modelPath = $modx->getOption('app_core_path', array(), $modx->getOption('core_path') . 'components/app/') . 'model/';
        
        if ($App = $modx->getService('App', 'App', $modelPath)) {
            $App->handleEvent($modx->event, $scriptProperties);
        }
}
