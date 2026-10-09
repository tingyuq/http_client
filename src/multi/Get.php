<?php


namespace tingyu\HttpRequest\multi;


class Get extends HttpClient
{
    private static $method = "GET";

    public function do(string $uri, $data)
    {
        if (is_array($data) && !empty($data)) {
            $this->requestBody = $data;
            $queryParam = http_build_query($data);
        } elseif (is_string($data) && $data != '') {
            $queryParam = $data;
            $this->requestBody[] = $data;
        }

        if (isset($queryParam)) {
            if (strpos($uri, '?') === false) {
                $uri .= '?' . $queryParam;
            } else {
                $uri .= '&' . $queryParam;
            }
        }


        $handler = $this->initialize($uri, self::$method);


        return $handler;
    }

}

