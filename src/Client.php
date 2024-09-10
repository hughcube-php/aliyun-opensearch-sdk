<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2021/4/20
 * Time: 4:21 下午.
 */

namespace HughCube\AliYun\OpenSearch\SDK;

use GuzzleHttp\HandlerStack;
use HughCube\AliYun\OpenSearch\SDK\Util\OpenApiUtil;
use HughCube\GuzzleHttp\Client as HttpClient;
use HughCube\GuzzleHttp\HttpClientTrait;
use HughCube\GuzzleHttp\LazyResponse;

class Client
{
    use HttpClientTrait;

    /**
     * @var array
     */
    protected $config;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function getAccessKey()
    {
        return $this->config['AccessKey'];
    }

    public function getAccessSecret()
    {
        return $this->config['AccessSecret'];
    }

    public function getEndpoint()
    {
        return $this->config['Endpoint'];
    }

    public function getVersion()
    {
        return $this->config['Version'] ?? null;
    }

    public function getOptions()
    {
        return $this->config['Options'] ?? [];
    }

    public function createHttpClient(): HttpClient
    {
        $options = $this->getOptions();

        $options['handler'] = $handler = HandlerStack::create();

        /** 补齐请求的信息 */
        $handler->push(function (callable $handler) {
            return OpenApiUtil::completeRequestMiddleware($this, $handler);
        });

        /** 签名 */
        $handler->push(function (callable $handler) {
            return OpenApiUtil::signatureRequestMiddleware($this, $handler);
        });

        return new HttpClient(array_merge(['base_uri' => $this->getEndpoint()], $options));
    }

    public function request(string $method, $uri, array $options = []): ResponseInterface
    {
        return $this->getHttpClient()->request($method, $uri, $options);
    }

    public function lazyRequest(string $method, $uri, array $options = []): ResponseInterface
    {
        return $this->getHttpClient()->requestLazy($method, $uri, $options);
    }
}
