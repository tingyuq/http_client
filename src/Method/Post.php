<?php


namespace tingyu\HttpRequest\Method;


use tingyu\HttpRequest\HttpClient;
use tingyu\HttpRequest\Util\Json;

class Post extends HttpClient
{
    private static $method = 'POST';

    public function do(string $uri, $data)
    {
        $this->requestUrl = $uri;
        $this->requestMethod = self::$method;

        if (is_array($data) || is_object($data)) {
            $this->setHeader("content-type", "application/x-www-form-urlencoded;charset=UTF-8");
            $this->requestBody = http_build_query($data);
        } else if (Json::IsJsonStr($data)) {
            $this->setHeader("content-type", "application/json;charset=UTF-8");
            $this->requestBody = $data;
        } else {
            $this->setHeader("content-type", "application/x-www-form-urlencoded;charset=UTF-8");
            $this->requestBody = $data;
        }

        $this->initialize();
        $this->execute(); 

        // todo 记录日志信息

        return $this->responseBody;
    }

}