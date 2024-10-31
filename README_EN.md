# GQUL - Get QQ Update Link

[简体中文](README.md) | English

**GQUL** is a PHP-based tool designed to fetch update links for various Android QQ and TIM versions on [Tencent Device-oriented Service (TDS) Shiply Containing and Distributing Platform](https://shiply.tds.qq.com/), including LiteGray testing versions.

## Features

- Retrieve Android QQ update links for different versions.
- Supports LiteGray testing versions.
- Can try to obtain the update links for Android TIM.


## Usage

- Not every visit succeeds in fetching the updated data!!!
- Actually it's not simple, Android QQ and TIM distributes the test content via uin and version, and not every uin is successfully fetched.
- Accessed via get spliced with the correct parameters:

```get
send.php?uin=114514&version=9.0.70%236676%230
```

- Optional:

```get
send.php?uin=114514&version=9.0.70%236676%230&appid=537228245&targetApp=TIM
```

- `uin`: A QQ account number e.g: `114514`
- `version`: Current version information (requires url encoding) e.g: `9.0.70%236676%230`
- `appid`(optional, the default content is `537230561`): The appid used by the current QQ version e.g: `537228245`
- `targetApp`(optional): Select the target application. The request target application is TIM if and only if the value of the `targetApp` parameter is `TIM`. Otherwise, the request target application is `QQ`.

## Principle

Android QQ and Android TIM (versions >= 4.0) use [Tencent Device-oriented Service (TDS) Shiply Containing and Distributing Platform](https://shiply.tds.qq.com/) to distribute update packages based on pre-defined QQ numbers and configuration libraries. 

This project simulates the construction of the data requested by Android QQ and TIM to Shiply Platform and expects to get the response from Shiply Platform with the download link of the update installation package.

## Files

- `core.php`: Core functionalities.
- `curl.php`: cURL operations.
- `send.php`: Main script to fetch the update link.
- `util.php`: Utility functions.
