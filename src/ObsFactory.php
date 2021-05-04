<?php
declare (strict_types=1);

namespace think\huaweicloud\extra;

use Carbon\Carbon;
use Exception;
use Obs\ObsClient;
use think\facade\Request;

class ObsFactory implements ObsInterface
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
     * @return ObsClient
     * @inheritDoc
     */
    public function getClient(): ObsClient
    {
        return $this->setClient();
    }

    /**
     * @param string $name
     * @return string
     * @throws Exception
     * @inheritDoc
     */
    public function put(string $name): string
    {
        $file = Request::file($name);
        $fileName = date('Ymd') . '/' . uuid()->toString() . '.' . $file->getOriginalExtension();
        $client = $this->setClient();
        $client->putObject([
            'Bucket' => $this->option['obs']['bucket'],
            'Key' => $fileName,
            'SourceFile' => $file->getRealPath()
        ]);
        return $fileName;
    }

    /**
     * @param array $keys
     * @throws Exception
     * @inheritDoc
     */
    public function delete(array $keys): void
    {
        $client = $this->setClient();
        $client->deleteObjects([
            'Bucket' => $this->option['obs']['bucket'],
            'Objects' => [...array_map(static fn($v) => ['Key' => $v], $keys)],
            'Quiet' => true
        ]);
    }

    /**
     * @param array $conditions
     * @param int $expired
     * @return array
     * @throws Exception
     * @inheritDoc
     */
    public function generatePostPresigned(array $conditions, int $expired = 600): array
    {
        $date = Carbon::now()->setTimezone('UTC');
        $filename = date('Ymd') . '/' . uuid()->toString();
        $policy = base64_encode(json_encode([
            'expiration' => $date->addSeconds($expired)->toISOString(),
            'conditions' => [
                ['bucket' => $this->option['obs']['bucket']],
                ['starts-with', '$key', $filename],
                ...$conditions
            ]
        ]));
        $signature = base64_encode(hash_hmac('sha1', $policy, $this->option['accessKeySecret'], true));
        return [
            'filename' => $filename,
            'type' => 'obs',
            'option' => [
                'access_key_id' => $this->option['accessKeyId'],
                'policy' => $policy,
                'signature' => $signature
            ],
        ];
    }
}