<?php

declare(strict_types=1);

namespace PhpConsole\Processors\Code;

use PhpConsole\Processors\AbstractProcessor;
use PhpConsole\Model\Code;

class Get extends AbstractProcessor
{
    public function process()
    {
        $pk = [
            'code_id' => (int)$this->getProperty('code_id'),
            'user_id' => $this->modx->getLoginUserID(),
        ];
        /** @var $code Code */
        if (!$code = $this->modx->getObject(Code::class, $pk)) {
            $code = $this->modx->newObject(Code::class);
            $code->fromArray($pk, '', true, false);
        }
        $data = $code->toArray();

        return $this->success('', $data);
    }

}