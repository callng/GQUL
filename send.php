<?php

require_once 'core.php';
require_once 'curl.php';
require_once 'util.php';

header('Content-Type: application/json');
$param = getParameters(); // 获取GET参数
if ($param === false) {
    exit(jsonExit(400, 'Bad Request'));
}
$key = generateAESKey(); // 生成AES密钥
$data = generateJsonString($param['version'], $param['uin'], $param['appid'], $param['targetApp']); // 生成JSON字符串
$encode = aesEncrypt($data, $key); // 加密JSON字符串
$rsaPublicKey = base64ToRsaPublicKey('MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC/rT6ULqXC32dgz4t/Vv4WS9pTks5Z2fPmbTHIXEVeiOEnjOpPBHOi1AUz+Ykqjk11ZyjidUwDyIaC/VtaC5Z7Bt/W+CFluDer7LiiDa6j77if5dbcvWUrJbgvhKqaEhWnMDXT1pAG2KxL/pNFAYguSLpOh9pK97G8umUMkkwWkwIDAQAB'); // 解析公钥
$encode2 = rsaEncrypt($key, $rsaPublicKey); // 使用RSA公钥加密AES密钥
$post = [
    "req_list" => [
        [
            "cipher_text" => $encode,
            "public_key_version" => 1,
            "pull_key" => $encode2
        ]
    ]
]; // 生成POST数据
$result = postJsonWithCurl('https://rdelivery.qq.com/v3/config/batchpull', $post); // 发送POST请求
$text = getCipherText($result); // 解析JSON并返回cipher_text
if ($text === false) {
    exit(jsonExit(500, 'cipher_text does not exist'));
}
$data = json_decode(gzdecode(aesDecrypt(base64_decode($text), $key)), true); // 解密并解析返回数据
if (json_last_error() !== JSON_ERROR_NONE) exit(jsonExit(501, 'cannot parse returned data'));
if ($data['msg'] == 'request illegal') exit(jsonExit(502, 'Shiply 平台返回“请求非法”'));
if (!isset($data['configs'])) exit(jsonExit(502, '该 QQ 目前不存在更新推送或已经是最新版本'));
$value_content = json_decode($data['configs'][0]['value'], true);
$config_value_content = json_decode($value_content['config_value'], true);
exit(json_encode($config_value_content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
