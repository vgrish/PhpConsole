<?php

declare(strict_types=1);

/**
 * Abstract processor
 *
 * @package phpconsole
 * @subpackage processors
 */

namespace PhpConsole\Processors;

use JsonException;
use MODX\Revolution\modX;
use MODX\Revolution\Processors\Processor;

/**
 * Class Processor
 */
abstract class AbstractProcessor extends Processor
{
    /**
     * A reference to the modX object.
     *
     * @var modX $modx
     */
    public $modx = null;

    /** @var string $permission */
    public $permission = 'phpconsole';
    /** @var array $languageTopics */
    public $languageTopics = ['phpconsole:default'];

    /**
     * Creates a modProcessor object.
     *
     * @param modX $modx A reference to the modX instance
     * @param array $properties An array of properties
     */
    public function __construct(modX $modx, array $properties = [])
    {
        parent::__construct($modx, $properties);
    }

    /**
     * Load a collection of Language Topics for this processor.
     * Override this in your derivative class to provide the array of topics to load.
     *
     * @return array
     */
    public function getLanguageTopics()
    {
        return $this->languageTopics;
    }

    /**
     * Return true here to allow access to this processor.
     *
     * @return boolean
     */
    public function checkPermissions()
    {
        return $this->modx->hasPermission($this->permission);
    }

    /**
     * Run the processor and return the result. Override this in your derivative class to provide custom functionality.
     * Used here for pre-2.2-style processors.
     *
     * @return mixed
     */
    abstract public function process();


    public function response(bool $status = false, string $msg = '', $data = [])
    {
        $result = [
            'success' => $status,
            'message' => $this->modx->lexicon($msg),
            'data' => is_array($data) ? $data : [],
        ];
        try {
            $output = json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $e) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, $e->getMessage());
            $output = json_encode(['success' => false]);
        }

        return $output;
    }

    /**
     * Return a success message from the processor.
     *
     * @param string $msg
     * @param mixed $object
     *
     * @return string
     */
    public function success($msg = '', $object = null): string
    {
        return $this->response(true, $msg, $object);
    }

    /**
     * Return a failure message from the processor.
     *
     * @param string $msg
     * @param mixed $object
     *
     * @return string
     */
    public function failure($msg = '', $object = null): string
    {
        return $this->response(false, $msg, $object);
    }
}