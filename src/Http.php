<?php

namespace tingyu\HttpRequest;

trait Http
{
    // @var int 请求超时时间
    private $requestSecond = 30;

    // 请求超时时间 毫秒
    private $requestMsSecond;

    // @var string 请求浏览器标识
    private $requestUserAgent;

    // @var array 请求cookie array
    private $requestCookies;

    // https证书存放路径
    private $requestSslCaCert = [
        'verifyPeer' => false,
        'caCert' => '',
    ];

    // @var array 证书请求参数
    private $requestSslCert;

    // @var string https证书存放路径文件
    protected $requestSslCaCertFile = '';

    // 代理信息
    private $proxy;

    // @var array 请求头 array
    private $requestHeader;

    // @var array 上传文件
    protected $requestFile;

    private $requestEncoding = 'gzip, deflate';

    /**
     * 设置请求携带的cookie值
     * @param string $key
     * @param string $value
     * @return HttpClient
     */
    public function setCookie($key, $value)
    {
        if (!is_string($key) || !is_string($value)) {
            throw new \Exception('cookie的设置值非法');
        }

        if ($key == '') {
            throw new \Exception('cookie的键值不能为空');
        }

        $this->requestCookies[$key] = $value;
        return $this;
    }


    /**
     * SSL PEM 证书验证
     * @param string $sslCertPath ssl证书绝对路径
     * @param string $sslKeyPath ssl密钥结对路径
     * @return HttpClient
     * @throws \Exception
     */
    public function setCertificate($sslCertPath, $sslKeyPath)
    {
        if (strrchr($sslCertPath, '.pem') !== '.pem') {
            throw new \Exception('SSL CERT证书不是pem格式的文件');
        }

        if (!file_exists($sslCertPath)) {
            throw new \Exception('SSL CERT证书文件不存在,请传入绝对路径');
        }

        if (strrchr($sslKeyPath, '.pem') !== '.pem') {
            throw new \Exception('SSL KEY证书不是pem格式的文件');
        }

        if (!file_exists($sslKeyPath)) {
            throw new \Exception('SSL KEY证书文件不存在,请传入绝对路径');
        }

        $this->requestSslCert['key'] = $sslKeyPath;
        $this->requestSslCert['cert'] = $sslCertPath;
        return $this;
    }


    /**
     * 设置https请求的时候是否进行证书校验
     * @param string $caCertPemFile 当进行证书校验的时候，应该加载证书的地址，证书可从https://curl.haxx.se/ca/cacert.pem下载
     * @return HttpClient
     * @throws \Exception
     */
    public function setSslVerifyPeer(string $caCertPemFile)
    {
        $isVerifyPeer = false;

        if (!empty($caCertPemFile)) {

            if (!file_exists($caCertPemFile)) {
                throw new \Exception('ssl证书文件不存在');
            } else {
                $this->requestSslCaCertFile = $caCertPemFile;
                $isVerifyPeer = true;
            }
        }

        $this->requestSslCaCert = [
            'verifyPeer' => $isVerifyPeer,
            'caCert' => $caCertPemFile,
        ];

        return $this;
    }


    /**
     * 设置请求标识 UA
     * @param string $userAgent
     * @throws \Exception
     * @return HttpClient
     */
    public function setUserAgent(string $userAgent)
    {
        $this->requestUserAgent = $userAgent;
        return $this;
    }


    /**
     * 设置超时时间， 单位秒
     * @param int $second 超时秒超时
     * @param int $msSecond 请求毫秒超时
     * @throws \Exception
     * @return HttpClient
     */
    public function setTimeOut(int $second, int $msSecond = 0)
    {
        if ($second < 0) {
            throw new \Exception("超时时间：秒 值非法");
        }

        if ($msSecond < 0) {
            throw new \Exception("超时时间：毫秒 值非法");
        }

        $this->requestSecond = $second;
        $this->requestMsSecond = $msSecond;

        return $this;
    }

    /**
     * 设置代理
     * @param string $proxyHost 代理地址
     * @param int $proxyPort 代理端口
     * @return HttpClient
     */
    public function setProxy(string $proxyHost, int $proxyPort)
    {
        $this->proxy['host'] = $proxyHost;
        $this->proxy['port'] = $proxyPort;
        return $this;
    }

    /**
     * 设置请求头信息
     * @param string $key 键 content-type
     * @param string $value 值 text/html; charset=utf-8
     * @throws \Exception
     * @return HttpClient
     */
    public function setHeader(string $key, string $value)
    {
        // 验证 Header 名称（RFC 7230 token）
        if (!preg_match('/^[a-zA-Z0-9\-!#$%&\'*+.^_`|~]+$/', $key)) {
            throw new \Exception('请求头名称包含非法字符');
        }

        // 过滤 Header 值中的换行符和 NUL 字节
        $value = str_replace(["\r", "\n", "\0"], '', $value);

        $this->requestHeader[$key] = $value;

        return $this;
    }

    /**
     * 添加上传文件
     * @param $filePath
     * @param $field
     * @param string $mimeType
     */
    public function setUploadFile($field, $filePath, $mimeType = '')
    {
        $filePath = trim($filePath);
        if ($filePath == '') {
            throw new \Exception('请传入文件绝对路径');
        }

        if (!file_exists($filePath)) {
            throw new \Exception('文件资源不存在');
        }

        if ($field == '') {
            throw new \Exception('字段名不能为空');
        }

        $info = pathinfo($filePath);
        $this->requestFile[$field] = new \CURLFile($filePath, $mimeType, $info['basename']);
    }

    /**
     * 构建请求 user-agent
     * @return void
     */
    private function buildUserAgent($handler)
    {
        curl_setopt($handler, CURLOPT_USERAGENT, $this->requestUserAgent);
    }

    /**
     * 构建 ssl 证书请求
     * @return void
     */
    private function buildSslCert($handler)
    {
        if (empty($this->requestSslCert)) {
            return;
        }
        curl_setopt($handler, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt($handler, CURLOPT_SSLCERT, $this->requestSslCert['cert']);
        curl_setopt($handler, CURLOPT_SSLKEYTYPE, 'PEM');
        curl_setopt($handler, CURLOPT_SSLKEY, $this->requestSslCert['key']);
    }

    /**
     * 构建 https 请求验证证书
     * @return void
     */
    private function buildSslVerifyPeer($handler)
    {
        curl_setopt($handler, CURLOPT_SSL_VERIFYPEER, $this->requestSslCaCert['verifyPeer']);
        curl_setopt($handler, CURLOPT_SSL_VERIFYHOST, $this->requestSslCaCert['verifyPeer']);
        if ($this->requestSslCaCert['verifyPeer']) {
            curl_setopt($handler, CURLOPT_CAINFO, $this->requestSslCaCert['caCert']);
        }
    }

    /**
     * 构建请求超时
     * @return void
     */
    private function buildRequestTimeout($handler)
    {
        if ($this->requestSecond != 0) {
            curl_setopt($handler, CURLOPT_TIMEOUT, $this->requestSecond);
        }
        if ($this->requestMsSecond != 0) {
            curl_setopt($handler, CURLOPT_TIMEOUT_MS, $this->requestMsSecond);
        }
    }

    /**
     * 构建请求 cookie
     * @return void
     */
    private function buildCookie($handler)
    {
        if (empty($this->requestCookies)) {
            return;
        }

        $cookie = '';
        foreach ($this->requestCookies as $key => $value) {
            $cookie .= rawurlencode($key) .'='.rawurlencode($value) .';';
        }

        curl_setopt($handler, CURLOPT_COOKIE, rtrim($cookie, ';'));
    }

    /**
     * 构建代理请求
     * @return void
     */
    private function buildProxy($handler)
    {
        if (empty($this->proxy)) {
            return;
        }

        curl_setopt($handler, CURLOPT_PROXY, $this->proxy['host']);
        curl_setopt($handler, CURLOPT_PROXYPORT, $this->proxy['port']);
    }

    private function buildEncoding($handler) {
        if (defined('CURLOPT_ACCEPT_ENCODING')) {
            curl_setopt($handler, CURLOPT_ACCEPT_ENCODING, $this->requestEncoding);
        } else {
            curl_setopt($handler, CURLOPT_ENCODING, $this->requestEncoding);
        }
    }


    /**
     * 构建 header 请求头
     * @return void
     */
    private function buildHeader($handler)
    {
        if (empty($this->requestHeader)) {
            return;
        }

        $header = [];
        foreach ($this->requestHeader as $key => $value) {
            $header[] = "{$key}: {$value}";
        }

        curl_setopt($handler, CURLOPT_HTTPHEADER, $header);
    }
}