<?php

declare(strict_types=1);

namespace PhpConsole\Processors\Code;

use PhpConsole\Processors\AbstractProcessor;
use PhpConsole\Model\Code;

class Clear extends AbstractProcessor
{
    public function process()
    {
        $this->modx->removeCollection(Code::class, [
            'user_id:=' => $this->modx->getLoginUserID()
        ]);

        return $this->success();
    }

}