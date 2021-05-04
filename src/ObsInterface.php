<?php
declare(strict_types=1);

namespace think\huaweicloud\extra;

use Obs\ObsClient;

interface ObsInterface
{
    /**
     * 获取 Obs 客户端
     * @return ObsClient
     */
    public function getClient(): ObsClient;

    /**
     * 上传一个对象至存储桶
     * @param string $name 请求接收的文件参数名
     * @return string 文件路径
     */
    public function put(string $name): string;

    /**
     * 在存储桶中批量删除对象
     * @param array $keys 要删除的目标对象的对象键
     */
    public function delete(array $keys): void;

    /**
     * 生成对象存储 API 签名
     * @param array $conditions 表单域的合法值
     * @param int $expired 过期时间
     * @return array
     */
    public function generatePostPresigned(array $conditions, int $expired = 600): array;
}