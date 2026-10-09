<?php

namespace tingyu\HttpRequest\multi;



class MultiClient
{

    private $handlers = [];


    /**
     * @return Response[]
     * @throws \Exception
     */
    public function exec() :array
    {
        if (empty($this->handlers)) {
            throw new \Exception("无请求句柄");
        }

        // 临时收集处理的header， cookie
        $tmpResp = [];

        // 初始化
        $multiHandler = curl_multi_init();
        if ($multiHandler === false) {
            throw new \Exception('初始化curl_multi错误');
        }

        // 组装
        foreach ($this->handlers as $sign => $handler) {
            $tmpResp[$sign] = [
                'header' => '',
                'cookies' => [],
            ];
            curl_setopt($handler, CURLOPT_HEADERFUNCTION, function ($ch, $headerLine) use (&$tmpResp, $sign){
                $tmpResp[$sign]['header'] .= $headerLine;

                // 解析响应的cookie信息
                if (preg_match('/^Set-Cookie:\s*([^=]+)=([^;]+)/mi', $headerLine, $cookie) === 1) {
                    $tmpResp[$sign]['cookies'][$cookie[1]] = trim($cookie[2], " \n\r\t\0\x0B");
                }

                return strlen($headerLine);
            });


            $addHandler = curl_multi_add_handle($multiHandler, $handler);
            if ($addHandler != CURLM_OK) {
                throw new \Exception("$sign 加入执行队列异常");
            }
        }

        // 执行
        do {
            $status = curl_multi_exec($multiHandler, $active);
            if ($active && $status === CURLM_OK) {
                if (curl_multi_select($multiHandler) === -1) {
                    usleep(1000);
                }
            }
        }while($active && $status === CURLM_OK);

        // 声明结果集
        $resps = [];

        // 收集
        foreach ($this->handlers as $sign => $handler) {
            $errorNo = curl_errno($handler);
            $errorMsg = curl_error($handler);
            if ($errorNo != CURLE_OK) {
                $resp = new Response($sign,$errorNo, $errorMsg, null, null, null, null);
            } else {
                $respBody = curl_multi_getcontent($handler);
                $respCode = curl_getinfo($handler, CURLINFO_HTTP_CODE);

                $resp = new Response($sign,$errorNo, $errorMsg, $respCode, $tmpResp[$sign]['header'], $tmpResp[$sign]['cookies'], $respBody);
            }

            $resps[$sign] = $resp;
            curl_multi_remove_handle($multiHandler, $handler);
            curl_close($handler);
        }

        // 关闭
        curl_multi_close($multiHandler);
        $this->handlers = [];

        return $resps;
    }


    /**
     * 添加请求进请求队列
     * @param string $sign
     * @param resource|\CurlHandle $handler do() 返回的 curl 句柄
     * @return $this
     */
    public function addHandler(string $sign, $handler) {
        if (array_key_exists($sign, $this->handlers)) {
            throw new \Exception("已存在相同 Sign 的请求");
        }
        $this->handlers[$sign] = $handler;
        return $this;
    }

}