<?php
declare (strict_types=1);

namespace think\huaweicloud\extra\service;

use think\aliyun\extra\common\OssFactory;
use think\Service;

class ObsService extends Service
{
    public function register()
    {
        $this->app->bind('obs', function () {
            $config = $this->app->config
                ->get('huaweicloud');
            return new OssFactory($config);
        });
    }
}