# http_client

一个基于 PHP cURL 扩展封装的轻量级 HTTP 请求库，支持 **同步请求** 与 **并发请求（curl_multi）** 两种模式，提供链式配置、Cookie / Header 处理、SSL 证书、代理、超时等能力。

> ⚠️ 该库仍在开发中，基本达到生产可用状态，任在开发的具体见文末 [TODO list](#todo-list)。

---

## 目录

- [环境要求](#环境要求)
- [安装](#安装)
- [快速开始](#快速开始)
- [同步请求](#同步请求)
- [并发请求（multi）](#并发请求multi)
- [配置方法一览](#配置方法一览)
- [各请求方式的数据格式规则](#各请求方式的数据格式规则)
- [测试](#测试)
- [注意事项](#注意事项)
- [TODO list](#todo-list)

---

## 环境要求

- PHP `>= 7.0`
- 扩展：`ext-curl`、`ext-json`

## 安装

通过 Composer 安装：

```bash
composer require tingyu/http_request
```

或克隆后引入自动加载：

```php
require_once __DIR__ . "/vendor/autoload.php";
```

命名空间遵循 PSR-4：`tingyu\HttpRequest\` → `src/`。

---

## 快速开始

```php
use tingyu\HttpRequest\Method\Get;

$request  = new Get();
$response = $request->do("http://example.com/api", ['id' => 1]);

echo $response;                        // 响应体
echo $request->getResponseStatusCode(); // HTTP 状态码
```

---

## 同步请求

同步模块位于 `tingyu\HttpRequest\Method` 命名空间，包含 `Get` / `Post` / `Put` / `Delete` 四个类，均继承自抽象基类 `tingyu\HttpRequest\HttpClient`。

### 基本用法

调用 `do(string $uri, $data)` 发起请求，返回值为**响应体字符串**。所有配置方法均支持链式调用，需在 `do()` 之前设置：

```php
use tingyu\HttpRequest\Method\Post;

$request  = new Post();
$response = $request
    ->setCookie('token', 'abc123')
    ->setHeader('auth', 'bearer xxx')
    ->setUserAgent('my-app')
    ->setTimeOut(5)                 // 超时 5 秒
    ->do("http://example.com/login", ['user' => 'tom', 'pwd' => '123']);
```

### 获取响应信息

请求完成后，可从实例上读取以下信息：

| 方法 / 属性 | 说明 |
|-------------|------|
| `do()` 返回值 | 响应体（字符串） |
| `getResponseStatusCode()` | HTTP 状态码 |
| `getErrNo()` | cURL 错误号（`0` / `CURLE_OK` 表示无错误） |
| `getErrMsg()` | cURL 错误信息 |
| `$request->responseHeader` | 完整响应头（原始文本，public 属性） |
| `$request->responseCookies` | 响应 Cookie，关联数组 `name => value`（public 属性） |

```php
if ($request->getErrNo() !== 0) {
    echo "请求失败：" . $request->getErrMsg();
} else {
    print_r($request->responseCookies);
}
```

### 静态调用

对于无需额外配置的一次性请求，可直接静态调用（内部自动创建实例并结束 cURL 生命周期）：

```php
$response = \tingyu\HttpRequest\HttpClient::Get("http://example.com/api", ['a' => 'b']);
$response = \tingyu\HttpRequest\HttpClient::Post("http://example.com/api", ['a' => 'b']);
```

---

## 并发请求（multi）

并发模块位于 `tingyu\HttpRequest\multi` 命名空间，基于 `curl_multi` 实现，可将多个请求打包**并发执行**，适用于批量接口调用、聚合请求等场景。

### 工作流程

1. 用 `multi\{Get,Post,Put,Delete}` 的 `do($uri, $data)` 构建请求，返回一个 **cURL 句柄**；
2. 通过 `MultiClient::addHandler($sign, $handler)` 将句柄加入队列，`$sign` 为结果索引标识（**必须唯一**）；
3. 调用 `MultiClient::exec()` 并发执行，返回 `Response[]`，数组的键即为 `$sign`。

### 示例

```php
use tingyu\HttpRequest\multi\Delete;
use tingyu\HttpRequest\multi\Get;
use tingyu\HttpRequest\multi\MultiClient;
use tingyu\HttpRequest\multi\Post;
use tingyu\HttpRequest\multi\Put;

$client = new MultiClient();

// GET（支持链式配置）
$client->addHandler('get', (new Get())
    ->setCookie('123', '456')
    ->setHeader('auth', 'bearer xxx')
    ->setTimeOut(0, 500)
    ->do("http://localhost/get", ['a' => 'b']));

// POST 表单
$client->addHandler('post_form', (new Post())
    ->do("http://localhost/post", ['a' => 1, 'b' => 2]));

// PUT（数组自动转 JSON）
$client->addHandler('put', (new Put())
    ->do("http://localhost/put", ['a' => 1, 'b' => 2]));

// DELETE（JSON 字符串）
$client->addHandler('delete', (new Delete())
    ->do("http://localhost/delete", json_encode(['a' => 1])));

// 并发执行
$responses = $client->exec();

foreach ($responses as $sign => $response) {
    echo "==== {$sign} ====" . PHP_EOL;
    var_dump($response->getCode());    // HTTP 状态码
    var_dump($response->getError());   // cURL 错误（无错误为 null）
    echo $response->getBody();         // 响应体
}
```

完整可运行示例见 [`test/multi.php`](test/multi.php)。

### Response 对象

`exec()` 返回的每个元素均为 `tingyu\HttpRequest\multi\Response` 实例：

| 方法 | 说明 |
|------|------|
| `getSign()` | 请求标识（与入队时的 `$sign` 一致） |
| `getCode()` | HTTP 状态码 |
| `getHeader()` | 完整响应头（原始文本） |
| `getBody()` | 响应体 |
| `getCookie()` | 响应 Cookie，关联数组 `name => value` |
| `getError()` | cURL 层错误，无错误返回 `null`，否则返回 `['No' => 错误号, 'Msg' => 错误信息]` |

> 说明：并发模式下每个请求的响应头 / Cookie 按 `$sign` **相互隔离**，不会串写。当某个请求在 cURL 层失败（如超时、连接被拒）时，`getError()` 返回错误详情，此时 `getHeader()` / `getBody()` 等为 `null`。

### MultiClient 方法

| 方法 | 说明 |
|------|------|
| `addHandler(string $sign, $handler)` | 加入请求队列；`$sign` 重复会抛出异常；返回 `$this` 支持链式 |
| `exec(): Response[]` | 并发执行全部请求并返回结果集；执行后队列自动清空，可复用实例再次组装 |

---

## 配置方法一览

以下配置方法由 `trait Http` 提供，同步与并发模块通用，均返回 `$this`（支持链式）：

| 方法 | 说明 |
|------|------|
| `setCookie(string $key, string $value)` | 设置请求携带的 Cookie，可多次调用累加 |
| `setHeader(string $key, string $value)` | 设置请求头；名称按 RFC 7230 校验，值中的 `\r` `\n` `\0` 会被过滤（防注入） |
| `setUserAgent(string $userAgent)` | 设置 User-Agent |
| `setTimeOut(int $second, int $msSecond = 0)` | 设置超时（秒 + 毫秒），传 `0` 表示该项不限制 |
| `setProxy(string $proxyHost, int $proxyPort)` | 设置代理 |
| `setSslVerifyPeer(string $caCertPemFile)` | 开启 HTTPS 证书校验并指定 CA 证书（[cacert.pem 下载](https://curl.haxx.se/ca/cacert.pem)） |
| `setCertificate(string $sslCertPath, string $sslKeyPath)` | 设置 SSL PEM 客户端证书与密钥（须为存在的 `.pem` 文件） |

---

## 各请求方式的数据格式规则

`do($uri, $data)` 中 `$data` 的处理规则因请求方式而异：

| 方式 | `$data` 为数组 / 对象 | `$data` 为 JSON 字符串 | `$data` 为普通字符串 |
|------|----------------------|------------------------|----------------------|
| **GET** | `http_build_query` 后拼接到 URL 查询串 | — | 直接拼接到 URL 查询串 |
| **POST** | 转 `application/x-www-form-urlencoded` | 设 `Content-Type: application/json` 并原样发送 | 设表单编码后原样发送 |
| **PUT** | `json_encode`，`Content-Type: application/json` | 原样发送（JSON 头） | 原样发送（JSON 头） |
| **DELETE** | `json_encode`，`Content-Type: application/json` | 原样发送（JSON 头） | 原样发送（JSON 头） |

> GET 请求不会发送 body，`$data` 仅用于拼接查询字符串。

---

## 测试

`test/` 目录提供了各请求方式的可运行示例，直接用 CLI 执行即可：

```bash
php test/get.php
php test/post.php
php test/put.php
php test/delete.php
php test/multi.php   # 并发请求，覆盖 GET/POST/PUT/DELETE
```

---

## 注意事项

- **HTTPS 证书校验默认关闭**：出于开箱即用考虑，默认不校验对端证书。生产环境请求 HTTPS 时，建议调用 `setSslVerifyPeer($caCertPemFile)` 开启校验。
- **不自动跟随重定向**：库未开启 `CURLOPT_FOLLOWLOCATION`，遇到 3xx 响应不会自动跳转，需自行处理。
- **配置为「单次请求」语义**：每次 `do()` 执行后，Cookie / Header / SSL 校验等配置会被重置。因此复用同一实例发起多个请求时，需在**每次** `do()` 前重新设置这些值（并发模式下推荐每个请求 `new` 一个实例）。
- **文件上传尚未实现**：`setUploadFile()` 目前为预留接口，暂未接入实际请求构建，请勿依赖（见 TODO）。

---

## TODO list

该 http 请求库还在开发中，暂未达到生产可用的状态

- [x] 基础功能实现，包含设置参数，头部，cookie等
- [x] 并发请求（curl_multi）支持，含 `MultiClient` / `Response`
- [x] post 发送 json 数据
- [x] 增加相关的测试案例（同步 + 并发）
- [ ] 增加cookie， header 的测试
- [ ] 增加https证书的测试
- [ ] 增加http请求加载ssl证书的测试
- [ ] 请求生命周期记录，包含参数、响应、错误
- [ ] 单文件上传
- [ ] 多文件上传
