<?php

namespace PhpConsole\Controllers;

use MODX\Revolution\modExtraManagerController;
use PhpConsole\PhpConsoleConfig;

/**
 * Main Controller
 *
 * @package PhpConsole
 * @subpackage Controller
 */
class Console extends modExtraManagerController
{

    /**
     * The version hash
     *
     * @var string $versionHash
     */
    public $versionHash;

    /**
     * @return void
     */
    public function initialize()
    {
        $this->versionHash = '?v=' . dechex(crc32(PhpConsoleConfig::VERSION));
        parent::initialize();
    }

    public function loadCustomCssJs()
    {
        $options = array_merge([], [
            'connectorUrl' => PhpConsoleConfig::CONNECTOR_URL . '?page=main',
        ]);

        $options = json_encode($options, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $this->addHtml("<script type='text/javascript'>phpconsole.config={$options};</script>");

        $assetsUrl = PhpConsoleConfig::ASSETS_URL;
        $jsUrl = $assetsUrl . 'js/';
        $cssUrl = $assetsUrl . 'css/';

        $this->addCss($assetsUrl . 'vendor/tabs/DraggableTabs.css');
        $this->addCss($cssUrl . 'main.css');

        $this->addJavascript($assetsUrl . 'vendor/tabs/DraggableTabs.js');
        $this->addJavascript($assetsUrl . 'vendor/tabs/TabCloseMenu.js');

        $this->addJavascript($jsUrl . 'phpconsole.js');
        $this->addLastJavascript($jsUrl . 'panel.js');
        $this->addLastJavascript($jsUrl . 'page.js');

        $this->addHtml('<script>Ext.onReady(function() { MODx.add({ xtype: "phpconsole-page-console"});});</script>');
    }
    /**
     * @return array
     */
    public function getLanguageTopics()
    {
        return ['phpconsole:default'];
    }

    /**
     * @return bool
     */
    public function checkPermissions()
    {
        return true;
    }

    /**
     * @return null|string
     */
    public function getPageTitle()
    {
        return $this->modx->lexicon('phpconsole');
    }

    /**
     * @return string
     */
    public function getTemplateFile()
    {
        $this->content .= '<div id="phpconsole-panel-main-div"></div>';

        return '';
    }

    /**
     * @param string $script
     */
    public function addCss($script)
    {
        parent::addCss($script . $this->versionHash);
    }

    /**
     * @param string $script
     */
    public function addJavascript($script)
    {
        parent::addJavascript($script . $this->versionHash);
    }

    /**
     * @param string $script
     */
    public function addLastJavascript($script)
    {
        parent::addLastJavascript($script . $this->versionHash);
    }

}

return Console::class;