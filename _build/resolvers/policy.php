<?php

use MODX\Revolution\modAccessPolicy;
use MODX\Revolution\modAccessPolicyTemplate;
use xPDO\xPDO;

/** @var xPDO\Transport\xPDOTransport $transport */
/** @var array $options */

/** @var  MODX\Revolution\modX $modx */

if ($transport->xpdo) {
    $modx = $transport->xpdo;

    switch ($options[xPDOTransport::PACKAGE_ACTION]) {
        case xPDOTransport::ACTION_INSTALL:
        case xPDOTransport::ACTION_UPGRADE:
            // Assign policy to template
            $policy = $modx->getObject(modAccessPolicy::class, ['name' => 'PhpConsolePolicy']);
            if ($policy) {
                $template = $modx->getObject(modAccessPolicyTemplate::class, ['name' => 'PhpConsolePolicyTemplate']);
                if ($template) {
                    $policy->set('template', $template->get('id'));
                    $policy->save();
                } else {
                    $modx->log(
                        xPDO::LOG_LEVEL_ERROR,
                        '[PhpConsole] Could not find PhpConsolePolicyTemplate Access Policy Template'
                    );
                }
            } else {
                $modx->log(xPDO::LOG_LEVEL_ERROR, '[PhpConsole] Could not find PhpConsolePolicyTemplate Access Policy');
            }
            break;
    }
}
return true;