<?php


namespace tingyu\HttpRequest\Method;


use tingyu\HttpRequest\HttpClient;

class Delete extends HttpClient
{
    private static $method = 'DELETE';

    public function do(string $uri, $data)
    {
        $this->requestUrl = $uri;
        $this->requestMethod = self::$method;

        $this->setHeader("content-type", "application/json;charset=UTF-8");
        if (is_array($data) || is_object($data)) {
            $this->requestBody = json_encode($data);
        } else {
            $this->requestBody = $data;
        }

        $this->initialize();
        $this->execute();

        // todo 记录日志信息

        return $this->responseBody;
    }

}