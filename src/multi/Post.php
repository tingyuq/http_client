<?php


namespace tingyu\HttpRequest\multi;


use tingyu\HttpRequest\Util\Json;

class Post extends HttpClient
{
    private static $method = 'POST';

    public function do(string $uri, $data)
    {

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

        $handler = $this->initialize($uri, self::$method);


        return $handler;
    }

}
