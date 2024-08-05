# GQUL - 获取QQ更新链接

**GQUL** 是一个基于PHP的工具，设计用于获取各种Android版QQ的不同版本更新链接，包括LiteGray测试版本。

## 特性

- 检索不同版本的Android QQ更新链接。
- 支持LiteGray测试版本。

## 使用方法

- 并非每次访问都能成功获取更新数据！
- 实际上，Android QQ是通过uin和版本号分发测试内容的，并非每个uin都能成功获取到信息。
- 访问时需要正确拼接参数：

```get
send.php?uin=114514&version=9.0.70%236676%230
```

- 可选参数

```get
send.php?uin=114514&version=9.0.70%236676%230&appid=537228245
```

- `uin`: QQ账号号码，例如: `114514`
- `version`: 当前版本信息（需要URL编码），例如: `9.0.70%236676%230`
- `appid`(可选，默认内容为 `537230561`): 当前QQ版本使用的appid，例如: `537228245`

## 原理

QQ 使用 [腾讯面向设备的服务(TDS) Shiply分发平台](https://shiply.tds.qq.com/) 根据预定义的QQ号码和配置库来分发更新包

本项目模拟构建QQ向Shiply平台请求的数据，并期望从Shiply平台获得响应，其中包含更新安装包的下载链接

## 项目文件

- `core.php`: 核心功能。
- `curl.php`: CURL操作。
- `send.php`: 主脚本以获取更新链接。
- `util.php`: 辅助函数。
