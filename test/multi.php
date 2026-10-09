<?php

require_once dirname(__DIR__) . "/vendor/autoload.php";

use tingyu\HttpRequest\multi\Delete;
use tingyu\HttpRequest\multi\Get;
use tingyu\HttpRequest\multi\MultiClient;
use tingyu\HttpRequest\multi\Post;
use tingyu\HttpRequest\multi\Put;

try {

    $client = new MultiClient();

    // GET：演示链式配置（cookie / header / UA / 超时）后再入队
    $client->addHandler('get', (new Get())
        ->setCookie('123', '456')
        ->setHeader('auth', 'bearer xxx')
        ->setUserAgent("test")
        ->setTimeOut(0, 500)
        ->setCookie('789', '110')
        ->do("http://localhost/get", ['a' => 'b', 'c' => 'd']));

    // POST：表单
    $client->addHandler('post_form', (new Post())
        ->do("http://localhost/post", ['a' => 1, 'b' => 2]));

    // POST：json 字符串
    $client->addHandler('post_json', (new Post())
        ->do("http://localhost/post", json_encode(['a' => 1, 'b' => 2])));

    // PUT：数组自动转 json
    $client->addHandler('put', (new Put())
        ->do("http://localhost/put", ['a' => 1, 'b' => 2]));

    // DELETE：json 字符串
    $client->addHandler('delete', (new Delete())
        ->do("http://localhost/delete", json_encode(['a' => 1, 'b' => 2])));

    // 并发执行，返回 Response[]，键为入队时的 sign
    $responses = $client->exec();

    foreach ($responses as $sign => $response) {
        echo "==== {$sign} ====" . PHP_EOL;

        // sign 与 http 状态码
        var_dump($response->getSign());
        var_dump($response->getCode());

        // curl 层错误（无错误返回 null）
        var_dump($response->getError());

        // 响应 cookie（数组）
        var_dump($response->getCookie());

        // 响应头
        echo $response->getHeader();
        echo PHP_EOL;

        // 响应体
        echo $response->getBody();
        echo PHP_EOL . PHP_EOL;
    }
} catch (\Exception $e) {
    var_dump($e->getMessage());
}
