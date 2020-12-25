<?php

/**
 * Copyright 2019 Huawei Technologies Co.,Ltd.
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use
 * this file except in compliance with the License.  You may obtain a copy of the
 * License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software distributed
 * under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR
 * CONDITIONS OF ANY KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations under the License.
 *
 */

namespace Obs\Log;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use RuntimeException;

class ObsLog extends Logger
{
    public static $log = null;

    protected string $log_path = './';
    protected $log_name = null;
    protected int $log_level = Logger::DEBUG;
    protected int $log_maxFiles = 0;

    private $formatter = null;
    private string $filepath = '';

    public static function initLog($logConfig = []): void
    {
        $s3log = new self('');
        $s3log->setConfig($logConfig);
        $s3log->cheakDir();
        $s3log->setFilePath();
        $s3log->setFormat();
        $s3log->setHande();
    }

    private function setFormat(): void
    {
        $output = '[%datetime%][%level_name%]' . '%message%' . "\n";
        $this->formatter = new LineFormatter($output);

    }

    private function setHande(): void
    {
        self::$log = new Logger('obs_logger');
        $rotating = new RotatingFileHandler($this->filepath, $this->log_maxFiles, $this->log_level);
        $rotating->setFormatter($this->formatter);
        self::$log->pushHandler($rotating);
    }

    private function setConfig($logConfig = []): void
    {
        $arr = empty($logConfig) ? ObsConfig::LOG_FILE_CONFIG : $logConfig;
        $this->log_path = iconv('UTF-8', 'GBK', $arr['FilePath']);
        $this->log_name = iconv('UTF-8', 'GBK', $arr['FileName']);
        $this->log_maxFiles = is_numeric($arr['MaxFiles']) ? 0 : (int)$arr['MaxFiles'];
        $this->log_level = $arr['Level'];
    }

    private function cheakDir(): void
    {
        if (!is_dir($this->log_path)) {
            if (!mkdir($concurrentDirectory = $this->log_path, 0755, true) && !is_dir($concurrentDirectory)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
        }
    }

    private function setFilePath(): void
    {
        $this->filepath = $this->log_path . '/' . $this->log_name;
    }

    private static function writeLog($level, $msg): void
    {
        switch ($level) {
            case DEBUG:
                self::$log->debug($msg);
                break;
            case INFO:
                self::$log->info($msg);
                break;
            case NOTICE:
                self::$log->notice($msg);
                break;
            case WARNING:
                self::$log->warning($msg);
                break;
            case ERROR:
                self::$log->error($msg);
                break;
            case CRITICAL:
                self::$log->critical($msg);
                break;
            case ALERT:
                self::$log->alert($msg);
                break;
            case EMERGENCY:
                self::$log->emergency($msg);
                break;
            default:
                break;
        }

    }

    public static function commonLog($level, $format, $args1 = null, $arg2 = null): void
    {
        if (self::$log) {
            if ($args1 === null && $arg2 === null) {
                $msg = urldecode($format);
            } else {
                $msg = sprintf($format, $args1, $arg2);
            }
            $back = debug_backtrace();
            $line = $back[0]['line'];
            $filename = basename($back[0]['file']);
            $message = '[' . $filename . ':' . $line . ']: ' . $msg;
            self::writeLog($level, $message);
        }
    }
}
