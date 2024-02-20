<?php

declare(strict_types=1);

namespace PhpConsole\Processors\Tree;

use MODX\Revolution\Sources\modMediaSource;
use PhpConsole\Processors\AbstractProcessor;

class Get extends AbstractProcessor
{

    public function process()
    {
        $source = modMediaSource::getDefaultSource($this->modx, $this->getProperty('source', 1));
        if (!$source or !$source->getWorkingContext()) {
            return $this->failure('permission_denied');
        }
        $source->setRequestProperties($this->getProperties());
        if (!$source->initialize()) {
            return $this->failure('permission_denied');
        }

        $data = [];
        $row = $source->getObjectContents(rawurldecode($this->getProperty('path')));
        if (is_array($row) and empty($source->errors['file'])) {
            if (in_array($row['mime'], ['text/x-php'])) {
                $data = [
                    'name' => $row['name'],
                    'content' => $row['content'],
                ];
            }
        }

        return $this->success('', $data);
    }

}