<?php

namespace tingyu\HttpRequest\multi;

class Response
{
    protected $sign;

    protected $errorNo;

    protected $errorMsg;

    protected $code;

    protected $header;

    protected $cookie;

    protected $body;

    public function __construct($sign, $errorNo, $errorMsg, $code, $header, $cookie, $body) {
        $this->sign = $sign;
        $this->errorNo = $errorNo;
        $this->errorMsg = $errorMsg;
        $this->code = $code;
        $this->header = $header;
        $this->cookie = $cookie;
        $this->body = $body;
    }

    public function getHeader() {
        return $this->header;
    }

    public function getBody() {
        return $this->body;
    }

    public function getCookie() {
        return $this->cookie;
    }

    public function getSign() {
        return $this->sign;
    }

    public function getCode() {
        return $this->code;
    }

    public function getError() {
        if (empty($this->errorNo) || $this->errorNo == CURLE_OK) {
            return null;
        }

        return [
            "No" => $this->errorNo,
            "Msg" => $this->errorMsg
        ];
    }

}