<?php

/**
 * 生成一个随机的UUID
 *
 * @return string 返回生成的UUID
 */
function generateRandomUUID(): string
{
    $data = openssl_random_pseudo_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return sprintf(
        '%08s-%04s-%04s-%04s-%12s',
        bin2hex(substr($data, 0, 4)),
        bin2hex(substr($data, 4, 2)),
        bin2hex(substr($data, 6, 2)),
        bin2hex(substr($data, 8, 2)),
        bin2hex(substr($data, 10, 6))
    );
}

/**
 * 生成加密前的 JSON 字符串
 *
 * @param string $appVersion 应用版本号
 * @param string $uin QQ号
 * @return string 返回生成的 JSON 字符串
 */
function generateJsonString(string $appVersion, string $uin): string
{
    $timestamp = time();
    $data = array(
        "systemID" => "10016",
        "appID" => "4cd6974be1",
        "sign" => md5('10016$4cd6974be1$4$$'.$timestamp.'$'.$uin.'$rdelivery0ccc46ca-154c-4c6b-8b0b-4d8537ffcbcc'),
        "timestamp" => $timestamp,
        "pullType" => 4,
        "target" => 1,
        "pullParams" => array(
            "properties" => array(
                "platform" => 2,
                "language" => "zh",
                "sdkVersion" => "1.3.35-RC03",
                "guid" => $uin,
                "appVersion" => $appVersion,
                "osVersion" => "34",
                "is64Bit" => true,
                "bundleId" => "com.tencent.mobileqq",
                "uniqueId" => generateRandomUUID(),
                "model" => "2304FPN6DC"
            ),
            "isDebugPackage" => false,
            "customProperties" => array(
                "appid" => '537230561' // 发现不需要指定appid,那就固定起来吧
            )
        ),
        "taskChecksum" => "0",
        "context" => "H4sIAAAAAAAA/+Li5ni5T1WIVaBT1INRS8HS0MwyMdnCwMzQMCklxdQ81cTC1MzIIDnV0DIxydLYGAAAAP//AQAA//+OoFcLLwAAAA=="
    );
    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

/**
 * 解析JSON并返回第一个cipher_text的数据
 *
 * @param string $jsonString 待解析的JSON字符串
 * @return string|bool 成功时返回cipher_text内容，失败时返回false
 */
function getCipherText(string $jsonString): string|bool
{
    $data = json_decode($jsonString, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return false;
    }
    if (!empty($data['rsp_list'])) {
        foreach ($data['rsp_list'] as $value) {
            if (isset($value['cipher_text'])) {
                return $value['cipher_text'];
            }
        }
    }
    return false;
}