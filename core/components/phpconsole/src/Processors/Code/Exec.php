<?php

declare(strict_types=1);

namespace PhpConsole\Processors\Code;

use ParseError;
use Throwable;
use xPDO\xPDO;
use PhpConsole\Processors\AbstractProcessor;
use PhpConsole\Model\Code;

class Exec extends AbstractProcessor
{
    /** @var array */
    protected array $stat = [];
    /** @var array */
    protected array $log = [];
    /** @var integer */
    protected int $logLevel;
    /** @var integer */
    protected $logTarget;
    /** @var bool */
    protected bool $isDie = true;

    public function initialize()
    {
        if (!parent::initialize()) {
            return false;
        }

        $this->stat = $this->log = [];
        $this->logLevel = $this->modx->getLogLevel();
        $this->logTarget = $this->modx->getLogTarget();

        return true;
    }

    public function process()
    {
        if ($id = (int)$this->getProperty('code_id')) {
            /** @var $code Code */
            $code = $this->modx->getObject(Code::class, [
                'code_id' => $id,
                'user_id' => $this->modx->getLoginUserID(),
            ]);
        } else {
            /** @var $code Code */
            $code = $this->modx->newObject(Code::class);
        }

        if (!$code) {
            return $this->failure('phpconsole_err_code_exec');
        }

        $code->set('content', $this->getProperty('content'));
        if (!$code->save()) {
            return $this->failure('phpconsole_err_code_save');
        }

        $data = $this->exec($code);

        return $this->success('', $data);
    }

    public function initLog($original = false)
    {
        if ($original) {
            $this->modx->setLogTarget($this->logTarget);
            $this->modx->setLogLevel($this->logLevel);
        } else {
            $this->modx->setLogTarget(['target' => 'ARRAY_EXTENDED', 'options' => ['var' => &$this->log]]);
            $this->modx->setLogLevel(xPDO::LOG_LEVEL_ERROR);
        }
    }

    public function log($msg, $def = '', $line = '')
    {
        $this->modx->log(xPDO::LOG_LEVEL_ERROR, $msg, ['target' => 'ARRAY_EXTENDED', 'options' => ['var' => &$this->log]], $def, ' eval()', $line);
    }

    public function exec(Code $code)
    {
        register_shutdown_function([$this, 'shutdown']);

        $data = $code->toArray();
        unset($data['content']);

        $this->initLog();
        $this->getStat();

        ob_start();
        extract([
            'modx' => $this->modx,
        ], EXTR_SKIP);

        try {
            $result = eval("\n" . $code->get('content'));
            if ($result === null) {
                $result = ob_get_contents();
            }
            if (!is_string($result)) {
                $result = var_export($result, true);
            }

        } catch (ParseError $e) {
            $result = "The PHP code generate in not valid: " . $e->getMessage();
            $this->log($e->getMessage(), 'CODE', $e->getLine());
        } catch (Throwable $e) {
            $result = "The PHP code generate in not valid: " . $e->getMessage();
            $this->log($e->getMessage(), 'CODE', $e->getLine());
        }

        $this->isDie = false;
        ob_end_clean();

        $this->initLog(true);

        return array_merge($data, ['reexecute' => $REEXECUTE ?? false, 'result' => $result, 'log' => $this->getLog()], $this->getStat(true));
    }

    public function getStat(bool $process = false)
    {
        if (empty($this->stat)) {
            $this->stat = [
                'total_time' => microtime(true),
                'total_sql_time' => $this->modx->queryTime ?? 0,
                'total_queries' => $this->modx->executedQueries ?? 0,
                'total_memory' => memory_get_usage(true),
                'total_peak_memory' => memory_get_peak_usage(true),
            ];
        } else {
            $this->stat = [
                'total_time' => microtime(true) - $this->stat['total_time'],
                'total_sql_time' => ($this->modx->queryTime ?? 0) - $this->stat['total_sql_time'],
                'total_queries' => ($this->modx->executedQueries ?? 0) - $this->stat['total_queries'],
                'total_memory' => memory_get_usage(true) - $this->stat['total_memory'],
                'total_peak_memory' => memory_get_peak_usage(true) - $this->stat['total_peak_memory'],
            ];
        }

        if ($process) {
            $this->stat['total_php_time'] = sprintf("%2.3f s", ($this->stat['total_time'] - $this->stat['total_sql_time']));
            $this->stat['total_time'] = sprintf("%2.3f s", $this->stat['total_time']);
            $this->stat['total_sql_time'] = sprintf("%2.3f s", $this->stat['total_sql_time']);
            $this->stat['total_memory'] = sprintf("%2.3f mb", round($this->stat['total_memory'] / 1048576, 3));
            $this->stat['total_peak_memory'] = sprintf("%2.3f mb", round($this->stat['total_peak_memory'] / 1048576, 3));
        }

        return $this->stat;
    }

    public function getLog()
    {
        $log = '';
        foreach ($this->log as $info) {
            $time = substr($info['content'], 0, strpos($info['content'], ']') + 1);
            if (strpos($info['file'], 'eval()')) {
                $log .= sprintf("%s (%s %s%s) %s\n", $time, $info['level'], '@ code', $info['line'], $info['msg']);
            } elseif (strpos($info['file'], 'Code/Exec')) {
                $log .= sprintf("%s (%s %s%s) %s\n", $time, $info['level'], '@ code', '', $info['msg']);
            } else {
                $log .= sprintf("%s (%s%s%s) %s\n", $time, $info['level'], $info['file'], $info['line'], $info['msg']);
            }
        }
        return $log;
    }

    public function shutdown()
    {
        $result = null;
        $error = error_get_last();
        if (is_array($error) && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            if ($result = ob_get_contents()) {
                $result = trim(strip_tags(str_replace(['<br>', '<br />'], "\n", $result)));
            }
            if (empty($result)) {
                $result = $error['message'] ?? 'Fatal error';
            }

            $this->log($error['message'] ?? '', 'CODE', $error['line'] ?? '');

        } elseif ($this->isDie) {
            $result = ob_get_contents();
            if (empty($result)) {
                $result = 'Script die stopped';
            }

            $this->log('Script die stopped', 'CODE', '');
        }

        if ($result !== null) {
            while (ob_get_level()) ob_end_clean();

            $this->initLog(true);

            echo json_encode([
                'success' => true,
                'message' => '',
                'data' => [
                    'result' => $result,
                    'log' => $this->getLog(),
                    'shutdown' => true,
                ],
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
    }

}