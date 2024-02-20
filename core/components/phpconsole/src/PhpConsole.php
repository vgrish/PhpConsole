<?php

namespace PhpConsole;

use MODX\Revolution\modX;

class PhpConsole
{
    /** @var \modX $modx */
    public $modx;

    /**
     * The namespace
     *
     * @var string $namespace
     */
    public $namespace = '';

    /**
     * The version
     *
     * @var string $version
     */
    public $version = '';

    /**
     * The class options
     *
     * @var array $options
     */
    public $options = [];

    /**
     * @param         $n
     * @param array $p
     */
    public function __call($n, array $p)
    {
        echo __METHOD__ . " says: " . $n;
    }

    public function __construct(modX $modx, array $options = [])
    {
        $this->modx = $modx;
        $this->version = PhpConsoleConfig::VERSION;
        $this->namespace = PhpConsoleConfig::NAMESPACE;

        $this->modx->lexicon->load('phpconsole:default');
    }

}
