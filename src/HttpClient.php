<?php

namespace tingyu\HttpRequest;

use tingyu\HttpRequest\Method\Delete;
use tingyu\HttpRequest\Method\Get;
use tingyu\HttpRequest\Method\Post;
use tingyu\HttpRequest\Method\Put;

/**
 * @method static Get($uri, $data)
 * @method static Post($uri, $data)
 * @method static Put($uri, $data)
 * @method static Delete($uri, $data)
 */
abstract class HttpClient
{
    // @var mixed curl 句柄
    protected $handler;

    // @var int 错误号
    protected $errNo;

    // @var string 错误信息
    protected $errMsg;

    // @var string 请求地址
    protected $requestUrl;

    // @var string 请求body体
    protected $requestBody;

    // 请求的方法
    protected $requestMethod;

    // @var array 响应头信息
    public $responseHeader;

    // @var string 响应结果
    protected $responseBody;

    // @var array 响应cookie信息 数组
    public $responseCookies;

    // @var int 响应的状态码信息
    public $responseStatusCode;


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

    protected function clear() {
        $this->requestCookies = [];
        $this->requestFile = [];
        $this->requestHeader = [];
        $this->requestSslCaCert = [
            'verifyPeer' => false,
            'caCert' => '',
        ];
        $this->requestSslCaCertFile = '';
        $this->responseHeader = '';
        $this->responseCookies = [];
        $this->responseBody = null;
    }

    /**
     * curl 的初始化设置
     * @throws \Exception
     */
    protected function initialize()
    {
        $handler = curl_init();
        if ($handler === false) {
            throw new \Exception('初始化curl错误');
        }

        $this->handler = $handler;

        $this->buildCookie($handler);
        $this->buildSslCert($handler);
        $this->buildSslVerifyPeer($handler);
        $this->buildUserAgent($handler);
        $this->buildRequestTimeout($handler);
        $this->buildHeader($handler);
        $this->buildProxy($handler);
        $this->buildEncoding($handler);

        // 设置请求结果不直接输出，而是返回数据
        curl_setopt($this->handler, CURLOPT_RETURNTRANSFER, true);

        // 获取响应头部及响应的cookie
        curl_setopt($this->handler, CURLOPT_HEADERFUNCTION, [$this, 'dealResponseHeader']);

        //  设置请求方式
        curl_setopt($this->handler, CURLOPT_CUSTOMREQUEST, $this->requestMethod);

        // 设置请求uri
        curl_setopt($this->handler, CURLOPT_URL, $this->requestUrl);

        // 设置请求提
        if ($this->requestMethod != 'GET') {
            curl_setopt($this->handler, CURLOPT_POSTFIELDS, $this->requestBody);
        }

        $this->clear();
    }

    /**
     * 执行
     */
    protected function execute() {

        $this->responseBody = curl_exec($this->handler);

        $this->responseStatusCode = curl_getinfo($this->handler, CURLINFO_HTTP_CODE);

        $this->errNo = curl_errno($this->handler);
        $this->errMsg = curl_error($this->handler);

        // PHP 8.0+ 句柄为对象会自动回收，curl_close() 为空操作且在 8.5 被弃用，仅在 PHP 7 下显式释放
        if (PHP_VERSION_ID < 80000) {
            curl_close($this->handler);
        }
    }



    /**
     * 获取curl错误编号
     * @return mixed
     */
    public function getErrNo()
    {
        return $this->errNo;
    }

    /**
     * 获取curl错误信息
     * @return mixed
     */
    public function getErrMsg()
    {
        return $this->errMsg;
    }

    /**
     * 获取响应的http状态码信息
     * @return mixed
     */
    public function getResponseStatusCode()
    {
        return $this->responseStatusCode;
    }

    /**
     * 处理响应的header头部回调函数
     * 设置一个回调函数，这个函数有两个参数，第一个是cURL的资源句柄，第二个是输出的header数据。header数据的输出必须依赖这个函数，返回已写入的数据大小。
     * @param resource $handler curl资源句柄
     * @param string $header 响应的头部信息
     * @return int 返回响应头部的长度
     */
    public function dealResponseHeader($handler, $header)
    {
        // 获取头部信息
        $this->responseHeader .= $header;

        // 解析响应的cookie信息
        if (preg_match('/^Set-Cookie:\s*([^=]+)=([^;]+)/mi', $header, $cookie) === 1) {
            $this->responseCookies[$cookie[1]] = trim($cookie[2], " \n\r\t\0\x0B");
        }

        return strlen($header);
    }

    /**
     * 发起请求
     * @param string $uri 请求地址
     * @param string|array|object $data 请求提
     * @return mixed
     */
    abstract function do(string $uri, $data);


    /**
     * @param $method
     * @param $args
     * @return void
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

}