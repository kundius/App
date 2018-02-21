<?php

if (empty($_REQUEST['action'])) {
    die('Access denied');
} else {
    $action = $_REQUEST['action'];
}

define('MODX_API_MODE', true);
if (file_exists(dirname(dirname(dirname(dirname(__FILE__)))) . '/index.php')) {
    /** @noinspection PhpIncludeInspection */
    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/index.php';
} else {
    require_once dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/index.php';
}

$modx->getService('error', 'error.modError');
$modx->getRequest();
$modx->setLogLevel(modX::LOG_LEVEL_ERROR);
$modx->setLogTarget('FILE');
$modx->error->message = null;

/** @var Tickets $Tickets */
define('MODX_ACTION_MODE', true);
$modelPath = $modx->getOption('app_core_path', array(), $modx->getOption('core_path') . 'components/app/') . 'model/';
/** @var App $App */
$App = $modx->getService('App', 'App', $modelPath);
if ($modx->error->hasError() || !($App instanceof App)) {
    die('Error');
}

switch ($action) {
    default:
        $message = $_REQUEST['action'] != $action
            ? 'tickets_err_register_globals'
            : 'tickets_err_unknown';
        $response = array(
            'success' => false,
            'message' => $modx->lexicon($message),
        );
}

if (is_array($response)) {
    $response = json_encode($response);
}

@session_write_close();
exit($response);
