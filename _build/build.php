<?php

declare(strict_types=1);

set_time_limit(0);
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 'On');

$config = [
    'name' => 'PhpConsole',
    'version' => '1.0.0',
    'release' => 'pl',
    'update' => [
        'chunks' => false,
        'menus' => true,
        'permission' => false,
        'plugins' => false,
        'policies' => false,
        'policy_templates' => false,
        'resources' => false,
        'settings' => true,
        'snippets' => false,
        'templates' => false,
        'widgets' => false,
    ],
    'static' => [
        'plugins' => false,
        'snippets' => false,
        'chunks' => false,
    ],
    'install' => true,
    'download' => false,
];

if (!defined('MODX_CORE_PATH')) {
    $path = dirname(__FILE__);
    while (!file_exists($path . '/core/config/config.inc.php') and (strlen($path) > 1)) {
        $path = dirname($path);
    }
    define('MODX_CORE_PATH', $path . '/core/');
}

@ob_start();

define('PKG_NAME', $config['name']);
define('PKG_NAME_LOWER', strtolower($config['name']));
define('PKG_VERSION', $config['version']);
define('PKG_RELEASE', $config['release']);

$root = dirname(dirname(__FILE__)) . '/';
$core = $root . 'core/components/' . PKG_NAME_LOWER . '/';
$assets = $root . 'assets/components/' . PKG_NAME_LOWER . '/';


require_once MODX_CORE_PATH . 'vendor/autoload.php';


use MODX\Revolution\modAccessPermission;
use MODX\Revolution\modAccessPolicyTemplate;
use xPDO\xPDO;
use xPDO\Om\xPDOGenerator;
use xPDO\Transport\xPDOObjectVehicle;
use xPDO\Transport\xPDOFileVehicle;
use xPDO\Transport\xPDOScriptVehicle;
use xPDO\Transport\xPDOTransport;

use MODX\Revolution\modNamespace;
use MODX\Revolution\modCategory;
use MODX\Revolution\modSystemSetting;
use MODX\Revolution\modMenu;
use MODX\Revolution\modPlugin;
use MODX\Revolution\modPluginEvent;
use MODX\Revolution\modAccessPolicy;

/* instantiate xpdo instance */
$xpdo = new xPDO(
    'mysql:host=localhost;dbname=modx;charset=utf8', 'root', '',
    [xPDO::OPT_TABLE_PREFIX => 'modx_', xPDO::OPT_CACHE_PATH => MODX_CORE_PATH . 'cache/'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING]
);
$cacheManager = $xpdo->getCacheManager();
define('LOG', xPDO::LOG_LEVEL_INFO);
$xpdo->setLogLevel(LOG);

$buildLogs = [];
$xpdo->setLogTarget(['target' => 'ARRAY', 'options' => ['var' => &$buildLogs]]);
$xpdo->log(LOG, print_r($config, true));

$signature = implode('-', [PKG_NAME_LOWER, PKG_VERSION, PKG_RELEASE]);
$filename = $signature . '.transport.zip';
$directory = MODX_CORE_PATH . 'packages/';

$xpdo->log(LOG, 'Package directory: "' . $directory . '"');
$xpdo->log(LOG, 'Package filename: "' . $filename . '"');

/* remove the package if it exists */
if (file_exists($directory . $filename)) {
    unlink($directory . $filename);
}
if (file_exists($directory . $signature) and is_dir($directory . $signature)) {
    $cacheManager = $xpdo->getCacheManager();
    if ($cacheManager) {
        $cacheManager->deleteTree($directory . $signature, true, false, []);
    }
}

$schemaFile = $core . 'schema/' . PKG_NAME_LOWER . '.mysql.schema.xml';
$outputDir = $core . 'src/';
if (!file_exists($schemaFile) or empty(file_get_contents($schemaFile))) {
    $xpdo->log(LOG, 'Schema is empty on file: "' . $schemaFile . '"');
} else {
    $manager = $xpdo->getManager();
    $generator = $manager->getGenerator();
    if (!$generator->parseSchema(
        $schemaFile,
        $outputDir,
        [
            "compile" => 0,
            "update" => 1,
            "regenerate" => 1,
            "namespacePrefix" => PKG_NAME . "\\",
        ]
    )) {
        $xpdo->log(LOG, "Model regeneration failed! Error parsing schema {$schemaFile}");
    } else {
        $xpdo->log(LOG, "Regeneration of model files completed successfully.");
    }
}

$package = new xPDOTransport($xpdo, $signature, $directory);

// Add files
$package->put(
    [
        'source' => $assets,
        'target' => "return MODX_ASSETS_PATH . 'components/';",
    ],
    ['vehicle_class' => xPDOFileVehicle::class]
);
$package->put(
    [
        'source' => $core,
        'target' => "return MODX_CORE_PATH . 'components/';",
    ],
    ['vehicle_class' => xPDOFileVehicle::class]
);

$package->put(
    [
        'source' => $root . 'readme.md',
        'target' => "return MODX_ASSETS_PATH . 'components/" . PKG_NAME_LOWER . "/';",
    ],
    ['vehicle_class' => xPDOFileVehicle::class]
);

// Add namespace
$namespace = new modNamespace($xpdo);
$namespace->fromArray(
    [
        'name' => PKG_NAME_LOWER,
        'path' => '{core_path}components/' . PKG_NAME_LOWER . '/',
        'assets_path' => '{assets_path}components/' . PKG_NAME_LOWER . '/',
    ], false, true
);

$package->put(
    $namespace,
    [
        'vehicle_class' => xPDOObjectVehicle::class,
        xPDOTransport::UNIQUE_KEY => 'name',
        xPDOTransport::PRESERVE_KEYS => true,
        xPDOTransport::UPDATE_OBJECT => true,
        xPDOTransport::ABORT_INSTALL_ON_VEHICLE_FAIL => true,
    ]
);

// TODO modSystemSetting
$settings = include __DIR__ . '/data/settings.php';
if (!is_array($settings)) {
    $xpdo->log(LOG, 'Could not package in System Settings');
} else {
    foreach ($settings as $name => $data) {
        /** @var modSystemSetting $setting */
        $setting = new modSystemSetting($xpdo);
        $setting->fromArray(
            array_merge([
                'key' => PKG_NAME_LOWER . '_' . $name,
                'namespace' => PKG_NAME_LOWER,
            ], $data), '', true, true
        );
        $package->put($setting, [
            xPDOTransport::UNIQUE_KEY => 'key',
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => !empty($config['update']['settings']),
            xPDOTransport::RELATED_OBJECTS => false,
            'class' => modSystemSetting::class,
            'namespace' => PKG_NAME_LOWER,
        ]);
    }
    $xpdo->log(LOG, 'Packaged in ' . count($settings) . ' System Settings');
}


// TODO modMenu
$menus = include __DIR__ . '/data/menus.php';
if (!is_array($menus)) {
    $xpdo->log(LOG, 'Could not package in Menus');
} else {
    foreach ($menus as $name => $data) {
        /** @var modMenu $menu */
        $menu = new modMenu($xpdo);
        $menu->fromArray(
            array_merge([
                'text' => $name,
                'parent' => 'components',
                'namespace' => PKG_NAME_LOWER,
                'icon' => '',
                'menuindex' => 0,
                'params' => '',
                'handler' => '',
            ], $data), '', true, true
        );

        $package->put($menu, [
            xPDOTransport::UNIQUE_KEY => 'text',
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => !empty($config['update']['menus']),
            xPDOTransport::RELATED_OBJECTS => true,
            'class' => modMenu::class,
            'namespace' => PKG_NAME_LOWER,
        ]);
    }
    $xpdo->log(LOG, 'Packaged in ' . count($menus) . ' Menus');
}

// TODO modAccessPolicy
$policies = include __DIR__ . '/data/policies.php';
if (!is_array($policies)) {
    $xpdo->log(LOG, 'Could not package in Access Policies');
} else {
    foreach ($policies as $name => $data) {
        if (isset($data['data'])) {
            $data['data'] = json_encode($data['data']);
        }

        /** @var modAccessPolicy $policy */
        $policy = new modAccessPolicy($xpdo);
        $policy->fromArray(array_merge([
                'name' => $name,
                'lexicon' => PKG_NAME_LOWER . ':permissions',
            ], $data)
            , '', true, true
        );
        $package->put($policy, [
            xPDOTransport::UNIQUE_KEY => ['name'],
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => !empty($config['update']['policies']),
            'class' => modAccessPolicy::class,
            'namespace' => PKG_NAME_LOWER,
        ]);
    }
    $xpdo->log(LOG, 'Packaged in ' . count($policies) . ' Access Policies');
}

// TODO modAccessPolicy
$policy_templates = include __DIR__ . '/data/policy_templates.php';
if (!is_array($policy_templates)) {
    $xpdo->log(LOG, 'Could not package in Policy Templates');
} else {
    foreach ($policy_templates as $name => $data) {
        $permissions = [];
        if (isset($data['permissions']) && is_array($data['permissions'])) {
            foreach ($data['permissions'] as $name2 => $data2) {
                /** @var $permission modAccessPermission */
                $permission = new modAccessPermission($xpdo);
                $permission->fromArray(array_merge([
                        'name' => $name2,
                        'description' => $name2,
                        'value' => true,
                    ], $data2)
                    , '', true, true
                );
                $permissions[] = $permission;
            }
        }

        /** @var $permission modAccessPolicyTemplate */
        $permission = new modAccessPolicyTemplate($xpdo);
        $permission->fromArray(array_merge([
                'name' => $name,
                'lexicon' => PKG_NAME_LOWER . ':permissions',
            ], $data)
            , '', true, true
        );
        if (!empty($permissions)) {
            $permission->addMany($permissions);
        }

        $package->put($permission, [
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UNIQUE_KEY => ['name'],
            xPDOTransport::UPDATE_OBJECT => !empty($config['update']['policy_templates']),
            xPDOTransport::RELATED_OBJECTS => true,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
                'Permissions' => [
                    xPDOTransport::PRESERVE_KEYS => false,
                    xPDOTransport::UPDATE_OBJECT => !empty($config['update']['permission']),
                    xPDOTransport::UNIQUE_KEY => ['template', 'name'],
                ],
            ],
            'class' => modAccessPolicyTemplate::class,
            'namespace' => PKG_NAME_LOWER,
        ]);
    }
    $xpdo->log(LOG, 'Packaged in ' . count($policy_templates) . ' Access Policy Templates');
}

// Add validators
$validators = [];

// Add resolvers
$resolvers = [];
$files = scandir(__DIR__ . '/resolvers/');
foreach ($files as $file) {
    if (in_array($file[0], ['_', '.'])) {
        continue;
    }
    $resolvers[] = ['type' => 'php', 'source' => __DIR__ . '/resolvers/' . $file];
    $xpdo->log(LOG, 'Added resolver "' . preg_replace('#\.php$#', '', $file) . '"');
}


$category = new modCategory($xpdo);
$category->fromArray(
    [
        'id' => 1, 'category' => PKG_NAME, 'parent' => 0,
    ], false, true
);

$package->put(
    $category,
    [
        xPDOTransport::UNIQUE_KEY => 'category',
        xPDOTransport::PRESERVE_KEYS => false,
        xPDOTransport::UPDATE_OBJECT => true,
        xPDOTransport::ABORT_INSTALL_ON_VEHICLE_FAIL => true,
        xPDOTransport::NATIVE_KEY => true,
        xPDOTransport::RELATED_OBJECTS => true,
        'package' => 'modx',
        'resolve' => $resolvers,
    ],
);

$package->setAttribute('changelog', file_get_contents($root . 'changelog.md'));
$package->setAttribute('license', file_get_contents($root . 'license.md'));
$package->setAttribute('readme', file_get_contents($root . 'readme.md'));
$package->setAttribute(
    'requires',
    [
        'php' => '>=7.4',
        'modx' => '>=3.0',
    ]
);

if ($package->pack()) {
    $xpdo->log(LOG, "Package built");
}

if (!empty($config['install'])) {
    /* Create an instance of the modX class */
    $modx = new \MODX\Revolution\modX();
    if (is_object($modx) and ($modx instanceof \MODX\Revolution\modX)) {
        $modx->initialize('mgr');

        $modx->setLogLevel(xPDO::LOG_LEVEL_INFO);
        $modx->setLogTarget();
        $modx->runProcessor('Workspace/Packages/ScanLocal');
        if ($r = $modx->runProcessor('Workspace/Packages/Install', ['signature' => $signature])) {
            $response = $r->getResponse();
        }
        $xpdo->log(LOG, $response['message']);
        $modx->getCacheManager()->refresh(['system_settings' => []]);
        $modx->reloadConfig();
    }
}

@ob_clean();
$buildLog = '';
foreach ($buildLogs as $info) {
    $buildLog .= xPDOGenerator::varExport($info) . "\n";
}
if (XPDO_CLI_MODE) {
    echo $buildLog;
} else {
    echo "<pre>" . $buildLog . "</pre>";
}

