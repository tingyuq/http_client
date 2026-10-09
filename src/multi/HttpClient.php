<?php

namespace tingyu\HttpRequest\multi;

use tingyu\HttpRequest\Http;

/**
 * @method static Get($uri, $data)
 * @method static Post($uri, $data)
 * @method static Put($uri, $data)
 * @method static Delete($uri, $data)
 */
abstract class HttpClient
{
    // @var string 请求body体
    protected $requestBody;

    use Http;

    /**
     * 验证 curl 扩展有无安装，设置默认请求参数
     * @throws \Exception
     */
    public function __construct()
    {
        if (!extension_loaded('curl')) {
            throw new \Exception('curl 扩展没有安装');
        }



        // 设置默认的请求配置
        $curlVersion = curl_version();
        $this->requestUserAgent = "(" . PHP_OS . ") PHP/" . PHP_VERSION . " CURL/" . $curlVersion['version'];
    }

    /**
     * @param $url
     * @param $method
     * @return resource
     * @throws \Exception
     */
    protected function initialize($url, $method)
    {
        $handler = curl_init();
        if ($handler === false) {
            throw new \Exception('初始化curl错误');
        }

        $this->buildCookie($handler);
        $this->buildSslCert($handler);
        $this->buildSslVerifyPeer($handler);
        $this->buildUserAgent($handler);
        $this->buildRequestTimeout($handler);
        $this->buildHeader($handler);
        $this->buildProxy($handler);
        $this->buildEncoding($handler);

        // 设置请求结果不直接输出，而是返回数据
        curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);

        //  设置请求方式
        curl_setopt($handler, CURLOPT_CUSTOMREQUEST, $method);

        // 设置请求uri
        curl_setopt($handler, CURLOPT_URL, $url);

        // 设置请求提
        if ($method!= 'GET') {
            curl_setopt($handler, CURLOPT_POSTFIELDS, $this->requestBody);
        }

        $this->clear();
        return $handler;
    }

    protected function clear() {
        $this->requestCookies = [];
        $this->requestFile = [];
        $this->requestHeader = [];
        $this->requestSslCaCert = [
            'verifyPeer' => false,
            'caCert' => '',
        ];
        $this->requestSslCaCertFile = '';
    }

    /**
     * @param $method
     * @param $args
     * @return resource
     * @throws \Exception
     */
    public static function __callStatic($method, $args)
    {
        switch (ucfirst($method)) {
            case 'Post':
                return (new Post())->do(...$args);
            case 'Get':
                return (new Get())->do(...$args);
            case 'Put':
                return (new Put())->do(...$args);
            case 'Delete':
                return (new Delete())->do(...$args);
            default:
                throw new \Exception("未支持的类型");
        }
    }

    abstract function do(string $uri, $data);

}