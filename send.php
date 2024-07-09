<?php

require_once 'core.php';
require_once 'curl.php';
require_once 'util.php';

header('Content-Type: application/json');

$param = getParameters(); // 获取GET参数
if ($param === false) {
    exit(jsonExit(400, 'Bad Request')); // 参数错误
}
$key = generateAESKey(); // 生成AES密钥
$data = generateJsonString($param['version'], $param['uin']); // 生成JSON字符串
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
$text = getCipherText($result);
if ($text === false) {
    exit(jsonExit(500, 'Internal Server Error'));
}
$decode = aesDecrypt(base64_decode($text), $key); // 解密字符串
exit(gzdecode($decode)); // 解压字符串并返回