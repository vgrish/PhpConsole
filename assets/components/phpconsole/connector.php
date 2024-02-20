<?php

/**
 * PhpConsole connector
 *
 * @package phpconsole
 * @subpackage connector
 *
 * @var MODX\Revolution\modX $modx
 *
 */

require_once dirname(__FILE__, 4) . '/config.core.php';
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
require_once MODX_CONNECTORS_PATH . 'index.php';

/** @var MODX\Revolution\modConnectorRequest $request */
$request = $modx->request;
$request->handleRequest([
    'action' => PhpConsole\PhpConsoleConfig::PROCESSORS_ACTION_PREFIX . $_REQUEST['action'] ?? '',
    'processors_path' => PhpConsole\PhpConsoleConfig::PROCESSORS_PATH,
]);