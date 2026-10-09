<?php


namespace tingyu\HttpRequest\multi;


class Delete extends HttpClient
{
    private static $method = 'DELETE';

    public function do(string $uri, $data)
    {

        $this->setHeader("content-type", "application/json;charset=UTF-8");
        if (is_array($data) || is_object($data)) {
            $this->requestBody = json_encode($data);
        } else {
            $this->requestBody = $data;
        }

        $handler = $this->initialize($uri, self::$method);


        return $handler;
    }

}
