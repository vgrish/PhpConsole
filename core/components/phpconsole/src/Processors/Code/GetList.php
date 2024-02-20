<?php

declare(strict_types=1);

namespace PhpConsole\Processors\Code;

use PhpConsole\Processors\AbstractProcessor;
use PhpConsole\Model\Code;

class GetList extends AbstractProcessor
{
    public function process()
    {
        $c = $this->modx->newQuery(Code::class);
        $c->sortby('code_id', 'ASC');
        $c->limit(0);
        $c->where(['user_id:=' => $this->modx->getLoginUserID()]);
        $c->select('code_id');

        $data = [];
        if ($c->prepare() and $c->stmt->execute()) {
            $data = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        return $this->success('', $data);
    }

}