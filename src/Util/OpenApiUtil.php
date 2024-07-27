<?php
/**
 * Created by PhpStorm.
 * User: hugh.li
 * Date: 2024/1/14
 * Time: 12:15.
 */

namespace HughCube\AliYun\OpenSearch\SDK\Util;

use Closure;
use HughCube\AliYun\OpenSearch\Client;
use Psr\Http\Message\RequestInterface;

class OpenApiUtil
{
    public static function genNonce(): string
    {
        return strval(intval(microtime(true) * 1000) . mt_rand(10000, 99999));
    }

    public static function makeContentMd5($request): string
    {
        if ('GET' !== strtoupper($request->getMethod())) {
            return md5($request->getBody()->getContents());
        }
        return '';
    }

    public static function completeRequestMiddleware(Client $client, callable $handler): Closure
    {
        return function (RequestInterface $request, array $options) use ($client, $handler) {

            if (!$request->hasHeader('Date')) {
                $request = $request->withHeader('Date', gmdate('D, d M Y H:i:s T'));
            }

            if (!$request->hasHeader('Content-Type')) {
                $request = $request->withHeader('Content-Type', 'application/json');
            }

            if (!$request->hasHeader('Accept-Language')) {
                $request = $request->withHeader('Accept-Language', 'zh-cn');
            }

            $request = $request->withHeader('User-Agent', sprintf('OpenSearch:%s', $client->getVersion()));

            return $handler($request, $options);
        };
    }

    public static function signatureRequestMiddleware(Client $client, callable $handler): Closure
    {
        return function (RequestInterface $request, array $options) use ($client, $handler) {

            /** 补充签名需要的参数 */
            $request = $request->withHeader('X-Opensearch-Nonce', static::genNonce());
            $request = $request->withHeader('Content-Md5', static::makeContentMd5($request));

            /** 签名 */
            $string = strtoupper($request->getMethod()) . "\n";
            $string .= $request->getHeaderLine('Content-Md5') . "\n";
            $string .= $request->getHeaderLine('Content-Type') . "\n";
            $string .= $request->getHeaderLine('Date') . "\n";
            foreach ($request->getHeaders() as $name => $_) {
                if (0 === stripos($name, 'x-opensearch-')) {
                    $string .= sprintf('%s:%s', strtolower($name), $request->getHeaderLine($name)) . "\n";
                }
            }
            $signature = base64_encode(hash_hmac('sha1', $string, $client->getAccessSecret(), true));

            /** 设置鉴权参数 */
            $request = $request->withHeader(
                'Authorization',
                sprintf('OPENSEARCH %s:%s', $client->getAccessKey(), $signature)
            );

            return $handler($request, $options);
        };
    }
}
