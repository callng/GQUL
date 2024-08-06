# GQUL - 获取 QQ 更新链接

**GQUL** 是一个基于 PHP 的工具，可用于获取 Android QQ 的更新链接，包括 LiteGray 小范围灰度测试版本。

## 特性

- 检索不同版本的 Android QQ 更新链接。
- 支持 LiteGray 小范围灰度测试版本。

## 使用方法

- 并非每次访问都能成功获取更新数据！
- 实际上，腾讯 QQ 通过 uin 和版本来分发测试内容，并不是每个 uin 都能成功获取更新。
- 访问时需要正确拼接参数：

```get
send.php?uin=114514&version=9.0.70%236676%230
```

- 可选参数

```get
send.php?uin=114514&version=9.0.70%236676%230&appid=537228245
```

- `uin`: QQ 号，例如: `114514`
- `version`: 当前版本信息（需要 URL 编码），例如: `9.0.70%236676%230`
- `appid`(可选，默认内容为 `537230561`): 当前 QQ 版本的 appid，例如: `537228245`

## 原理

Android QQ 使用 [TDS 腾讯端服务 Shiply 发布平台](https://shiply.tds.qq.com/) 根据预设的 QQ 号及其配置库来分发更新包。

此项目通过模拟构建 Android QQ 向 Shiply 平台请求的数据以期望获得 Shiply 发布平台带有更新安装包下载链接的响应返回。

## 项目文件

- `core.php`: 核心功能。
- `curl.php`: cURL 操作。
- `send.php`: 获取更新链接的主脚本。
- `util.php`: 辅助函数。
