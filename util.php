<?php

function generateAESKey(): string|false
{
    if (!function_exists('openssl_random_pseudo_bytes')) {
        error_log('请开启openssl扩展');
        return false;
    }
    return openssl_random_pseudo_bytes(16);
}

function aesEncrypt($data, $key): string|false
{
    if (!function_exists('openssl_encrypt')) {
        error_log('请开启openssl扩展');
        return false;
    }
    $method = 'aes-128-ctr';
    $iv = str_repeat("\x00", 16);
    if (strlen($key) !== 16) {
        error_log('密钥长度必须为16字节（128位）');
        return false;
    }
    $encryptedData = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, $iv);
    if ($encryptedData === false) {
        error_log("aesEncrypt error");
        return false;
    }
    return base64_encode($encryptedData);
}

function aesDecrypt($data, $key): string|false
{
    if (!function_exists('openssl_decrypt')) {
        error_log('请开启openssl扩展');
        return false;
    }
    $method = 'aes-128-ctr';
    $iv = str_repeat("\x00", 16);
    if (strlen($key) !== 16) {
        error_log('密钥长度必须为16字节（128位）');
        return false;
    }
    $decryptedData = openssl_decrypt($data, $method, $key, OPENSSL_RAW_DATA | OPENSSL_NO_PADDING, $iv);
    if ($decryptedData === false) {
        error_log("aesDecrypt error");
        return false;
    }
    return $decryptedData;
}

function base64ToRsaPublicKey($base64String): OpenSSLAsymmetricKey|false
{
    if (!function_exists('openssl_pkey_get_public')) {
        error_log('请开启openssl扩展');
        return false;
    }
    $pem = "-----BEGIN PUBLIC KEY-----\n"
        . wordwrap($base64String, 64, "\n", true)
        . "\n-----END PUBLIC KEY-----";
    return openssl_pkey_get_public($pem);
}

function rsaEncrypt($data, $publicKey): false|string
{
    if (!function_exists('openssl_public_encrypt')) {
        error_log('请开启openssl扩展');
        return false;
    }
    $encryptedData = '';
    $success = openssl_public_encrypt($data, $encryptedData, $publicKey, OPENSSL_PKCS1_PADDING);
    if (!$success) {
        error_log("rsaEncrypt: Encryption failed");
        return false;
    }
    return base64_encode($encryptedData);
}

function jsonExit($code, $msg): string
{
    $response = [
        'code' => $code,
        'msg' => $msg
    ];
    $jsonResponse = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($jsonResponse === false) {
        return json_encode([
            'code' => 500,
            'msg' => 'JSON encoding error.'
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
    return ($jsonResponse);
}
