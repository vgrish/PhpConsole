<?php

namespace PhpConsole\Model;

use xPDO\Om\xPDOObject;
use MODX\Revolution\modX;

/**
 * Class Code
 *
 *
 * @package PhpConsole\Model
 */
class Code extends xPDOObject
{
    /**
     * @param null $cacheFlag
     *
     * @return bool
     */
    public function save($cacheFlag = null)
    {
        if (parent::isNew()) {
            $userId = 0;
            if ($this->xpdo instanceof modX) {
                $contextKey = isset($this->xpdo->context) ? $this->xpdo->context->key : '';
                if ($contextKey && isset($_SESSION['modx.user.contextTokens']) && isset($_SESSION['modx.user.contextTokens'][$contextKey])) {
                    $userId = (int)$_SESSION['modx.user.contextTokens'][$contextKey];
                }
            }
            parent::set('user_id', $userId);

            $count = $this->xpdo->getCount(Code::class, ['user_id' => $userId]);
            parent::set('code_id', $count + 1);

        } else {
            parent::set('updatedon', time());
        }

        $saved = parent::save($cacheFlag);

        return $saved;
    }

    public function set($k, $v = null, $vType = '')
    {
        if ($k === 'content') {
            $v = preg_replace('/^ *(<\?php|<\?)/mi', '', $v);
            $v = substr($v, 1);
        }

        return parent::set($k, $v, $vType);
    }

    public function toArray($keyPrefix = '', $rawValues = false, $excludeLazy = false, $includeRelated = false)
    {
        $array = parent::toArray($keyPrefix, $rawValues, $excludeLazy, $includeRelated);
        $array['content'] = "<?php\n{$array['content']}";

        return $array;
    }

}
