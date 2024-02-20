<?php

namespace PhpConsole;

class PhpConsoleConfig
{

    public const VERSION = '1.0.0';
    public const NAMESPACE = 'phpconsole';
    public const CORE_PATH = MODX_CORE_PATH . 'components/phpconsole/src/';
    public const MODEL_PATH = MODX_CORE_PATH . 'components/phpconsole/src/Model/';
    public const PROCESSORS_PATH = MODX_CORE_PATH . 'components/phpconsole/src/Processors/';
    public const PROCESSORS_ACTION_PREFIX = 'PhpConsole\\Processors\\';
    public const ASSETS_PATH = MODX_ASSETS_PATH . 'components/phpconsole/';
    public const ASSETS_URL = MODX_ASSETS_URL . 'components/phpconsole/';
    public const CONNECTOR_URL = MODX_ASSETS_URL . 'components/phpconsole/connector.php';

}