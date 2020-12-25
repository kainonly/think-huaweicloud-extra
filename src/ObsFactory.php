<?php
declare (strict_types=1);

namespace think\huaweicloud\extra;

use Carbon\Carbon;
use Exception;
use Obs\ObsClient;
use think\facade\Request;

/**
 * 对象存储处理类
 * Class ObsFactory
 * @package think\huaweicloud\extra\common
 */
class ObsFactory
{
    /**
     * 华为云配置
     * @var array
     */
    private array $option;

    /**
     * 对象存储客户端
     * @var ObsClient
     */
    private ObsClient $client;

    /**
     * OssFactory constructor.
     * @param array $option
     */
    public function __construct(array $option)
    {
        $this->option = $option;
    }

    /**
     * 创建客户端
     * @return ObsClient
     */
    private function setClient(): ObsClient
    {
        if (!empty($this->client)) {
            return $this->client;
        }
        $this->client = new ObsClient([
            'key' => $this->option['accessKeyId'],
            'secret' => $this->option['accessKeySecret'],
            'endpoint' => $this->option['obs']['endpoint']
        ]);
        return $this->client;
    }

    /**
     * 获取对象存储客户端
     * @return ObsClient
     */
    public function getClient(): ObsClient
    {
        return $this->setClient();
    }

    /**
     * 上传至对象存储
     * @param string $name 文件名称
     * @return string
     * @throws Exception
     */
    public function put(string $name): string
    {
        $file = Request::file($name);
        $fileName = date('Ymd') . '/' .
            uuid()->toString() . '.' .
            $file->getOriginalExtension();

        $client = $this->setClient();
        $client->putObject([
            'Bucket' => $this->option['obs']['bucket'],
            'Key' => $fileName,
            'SourceFile' => $file->getRealPath()
        ]);
        return $fileName;
    }

    /**
     * 生成客户端上传 OBS 对象存储签名
     * @param array $conditions 表单域的合法值
     * @param int $expired 过期时间
     * @return array
     * @throws Exception
     */
    public function generatePostPresigned(array $conditions, int $expired = 600): array
    {
        $date = Carbon::now()->setTimezone('UTC');
        $policy = base64_encode(json_encode([
            'expiration' => $date->addSeconds($expired)->toISOString(),
            'conditions' => [
                ['bucket' => $this->option['obs']['bucket']],
                ['starts-with', '$key', ''],
                ...$conditions
            ]
        ]));
        $signature = base64_encode(hash_hmac('sha1', $policy, $this->option['accessKeySecret'], true));
        return [
            'filename' => date('Ymd') . '/' . uuid()->toString(),
            'type' => 'obs',
            'option' => [
                'access_key_id' => $this->option['accessKeyId'],
                'policy' => $policy,
                'signature' => $signature
            ],
        ];
    }
}